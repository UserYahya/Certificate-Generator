<?php
/**
 * CertMailer.php
 * Minimal but robust SMTP mailer — no Composer, no dependencies.
 * Supports STARTTLS (port 587), SSL/TLS (port 465), plain (port 25).
 * Supports HTML body, plain-text fallback, file attachments.
 */

class CertMailer
{
    /* ── config ── */
    public string $host        = '';
    public int    $port        = 587;
    public string $encryption  = 'tls';   // 'tls' | 'ssl' | ''
    public string $username    = '';
    public string $password    = '';
    public string $fromEmail   = '';
    public string $fromName    = '';
    public int    $timeout     = 30;
    public bool   $debug       = false;   // set true to capture SMTP conversation

    /* ── per-message ── */
    public string $subject     = '';
    public string $htmlBody    = '';
    public string $textBody    = '';
    public string $replyTo     = '';

    /** @var array<array{name:string,data:string,mime:string}> */
    private array $attachments = [];

    public string $lastError   = '';
    public string $debugLog    = '';

    /* ── public API ─────────────────────────────────────────── */

    public function addAttachment(string $filename, string $binaryData, string $mime = 'application/pdf'): void
    {
        $this->attachments[] = ['name' => $filename, 'data' => $binaryData, 'mime' => $mime];
    }

    public function clearAttachments(): void
    {
        $this->attachments = [];
    }

    /**
     * Send an email.
     * @param string $toEmail  Recipient email address
     * @param string $toName   Recipient display name (optional)
     * @return bool
     */
    public function send(string $toEmail, string $toName = ''): bool
    {
        $this->lastError = '';
        $this->debugLog  = '';
        $toName          = $toName ?: $toEmail;

        try {
            $sock = $this->connect();
            $this->ehlo($sock);

            if (strtolower($this->encryption) === 'tls') {
                $this->write($sock, 'STARTTLS');
                $this->expect($sock, 220, 'STARTTLS');
                if (!stream_socket_enable_crypto($sock, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    throw new \RuntimeException('STARTTLS: failed to enable TLS encryption.');
                }
                $this->ehlo($sock); // re-EHLO after TLS
            }

            $this->auth($sock);

            $this->write($sock, 'MAIL FROM:<' . $this->fromEmail . '>');
            $this->expect($sock, 250, 'MAIL FROM');

            $this->write($sock, 'RCPT TO:<' . $toEmail . '>');
            $this->expect($sock, [250, 251], 'RCPT TO');

            $this->write($sock, 'DATA');
            $this->expect($sock, 354, 'DATA');

            $message = $this->buildMessage($toEmail, $toName);
            // Dot-stuffing: lines beginning with a dot need an extra dot
            $message = preg_replace('/^(\.)/', '..$1', $message);
            fwrite($sock, $message . "\r\n.\r\n");
            $this->expect($sock, 250, 'Message accepted');

            $this->write($sock, 'QUIT');
            fclose($sock);
            return true;

        } catch (\Throwable $e) {
            $this->lastError = $e->getMessage();
            if (isset($sock) && is_resource($sock)) {
                try { fwrite($sock, "QUIT\r\n"); } catch (\Throwable $_) {}
                fclose($sock);
            }
            return false;
        }
    }

    /* ── private: connect ────────────────────────────────────── */
    private function connect()
    {
        $ctx = stream_context_create([
            'ssl' => [
                'verify_peer'       => false,
                'verify_peer_name'  => false,
                'allow_self_signed' => true,
            ],
        ]);

        if (strtolower($this->encryption) === 'ssl') {
            $host = 'ssl://' . $this->host;
        } else {
            $host = $this->host;
        }

        $errno  = 0;
        $errstr = '';
        $sock   = stream_socket_client(
            $host . ':' . $this->port,
            $errno, $errstr,
            $this->timeout,
            STREAM_CLIENT_CONNECT,
            $ctx
        );

        if ($sock === false) {
            throw new \RuntimeException(
                "Cannot connect to SMTP {$this->host}:{$this->port} — $errstr (code $errno). " .
                "Check SMTP_HOST, SMTP_PORT, and that your hosting allows outbound SMTP."
            );
        }

        stream_set_timeout($sock, $this->timeout);
        $banner = $this->readResponse($sock);
        $this->checkCode($banner, 220, 'Server banner');
        return $sock;
    }

    /* ── private: EHLO ───────────────────────────────────────── */
    private function ehlo($sock): void
    {
        $domain = $_SERVER['SERVER_NAME'] ?? gethostname() ?: 'localhost';
        $this->write($sock, 'EHLO ' . $domain);
        $this->expect($sock, 250, 'EHLO');
    }

    /* ── private: AUTH LOGIN ─────────────────────────────────── */
    private function auth($sock): void
    {
        $this->write($sock, 'AUTH LOGIN');
        $this->expect($sock, 334, 'AUTH LOGIN');

        $this->write($sock, base64_encode($this->username));
        $this->expect($sock, 334, 'AUTH username');

        $this->write($sock, base64_encode($this->password));
        $this->expect($sock, 235, 'AUTH password');
    }

    /* ── private: write a command ────────────────────────────── */
    private function write($sock, string $cmd): void
    {
        if ($this->debug) $this->debugLog .= ">>> $cmd\n";
        fwrite($sock, $cmd . "\r\n");
    }

    /* ── private: read multi-line response ───────────────────── */
    private function readResponse($sock): string
    {
        $response = '';
        while (true) {
            $line = fgets($sock, 1024);
            if ($line === false) break;
            if ($this->debug) $this->debugLog .= "<<< $line";
            $response .= $line;
            // Continuation lines have a dash at position 3; final line has a space
            if (strlen($line) < 4 || $line[3] === ' ' || $line[3] === "\r") break;
        }
        return $response;
    }

    /* ── private: check response code ───────────────────────── */
    private function checkCode(string $response, $expected, string $context): void
    {
        $code     = (int) substr(trim($response), 0, 3);
        $expected = (array) $expected;
        if (!in_array($code, $expected, true)) {
            $msg = trim($response);
            throw new \RuntimeException("SMTP $context failed (expected " . implode('/', $expected) . ", got $code): $msg");
        }
    }

    /* ── shortcut: write + read + check ─────────────────────── */
    private function expect($sock, $codes, string $context): string
    {
        $resp = $this->readResponse($sock);
        $this->checkCode($resp, $codes, $context);
        return $resp;
    }

    /* ── build RFC 2822 email message ────────────────────────── */
    private function buildMessage(string $toEmail, string $toName): string
    {
        $boundary = '==certgen_' . md5(uniqid('', true));
        $msgId    = '<' . md5(uniqid('', true)) . '@' . $this->host . '>';
        $date     = date('r');

        $from    = $this->encodeHeader($this->fromName) . ' <' . $this->fromEmail . '>';
        $to      = $this->encodeHeader($toName) . ' <' . $toEmail . '>';
        $subject = $this->encodeHeader($this->subject, true);

        // Headers
        $h  = "From: $from\r\n";
        $h .= "To: $to\r\n";
        $h .= "Subject: $subject\r\n";
        $h .= "Date: $date\r\n";
        $h .= "Message-ID: $msgId\r\n";
        $h .= "MIME-Version: 1.0\r\n";
        $h .= "X-Mailer: CertGen-Mailer/2.0\r\n";
        $h .= "X-Priority: 3 (Normal)\r\n";
        $h .= "Importance: Normal\r\n";
        if ($this->replyTo) {
            $h .= "Reply-To: {$this->replyTo}\r\n";
        }

        $textPart = $this->textBody ?: strip_tags(str_replace(['<br>','<br/>','</p>','</div>'], "\n", $this->htmlBody));

        if (!empty($this->attachments)) {
            // multipart/mixed wrapping multipart/alternative
            $altBoundary = 'alt_' . $boundary;
            $h .= "Content-Type: multipart/mixed; boundary=\"$boundary\"\r\n";

            $body  = "--$boundary\r\n";
            $body .= "Content-Type: multipart/alternative; boundary=\"$altBoundary\"\r\n\r\n";
            $body .= "--$altBoundary\r\n";
            $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
            $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
            $body .= chunk_split(base64_encode($textPart)) . "\r\n";
            $body .= "--$altBoundary\r\n";
            $body .= "Content-Type: text/html; charset=UTF-8\r\n";
            $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
            $body .= chunk_split(base64_encode($this->htmlBody)) . "\r\n";
            $body .= "--$altBoundary--\r\n\r\n";

            foreach ($this->attachments as $att) {
                $safeName = addslashes($att['name']);
                $body .= "--$boundary\r\n";
                $body .= "Content-Type: {$att['mime']}; name=\"$safeName\"\r\n";
                $body .= "Content-Disposition: attachment; filename=\"$safeName\"\r\n";
                $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
                $body .= chunk_split(base64_encode($att['data'])) . "\r\n";
            }
            $body .= "--$boundary--\r\n";
        } else {
            // Simple multipart/alternative
            $h .= "Content-Type: multipart/alternative; boundary=\"$boundary\"\r\n";

            $body  = "--$boundary\r\n";
            $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
            $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
            $body .= chunk_split(base64_encode($textPart)) . "\r\n";
            $body .= "--$boundary\r\n";
            $body .= "Content-Type: text/html; charset=UTF-8\r\n";
            $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
            $body .= chunk_split(base64_encode($this->htmlBody)) . "\r\n";
            $body .= "--$boundary--\r\n";
        }

        return $h . "\r\n" . $body;
    }

    /* ── RFC 2047 encoded word for headers ───────────────────── */
    private function encodeHeader(string $str, bool $forSubject = false): string
    {
        if (preg_match('/[^\x20-\x7E]/', $str) || $forSubject) {
            return '=?UTF-8?B?' . base64_encode($str) . '?=';
        }
        return $str;
    }
}
