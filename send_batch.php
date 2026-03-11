<?php
/**
 * send_batch.php
 * Sends up to BATCH_SIZE pending emails from a queue folder.
 * POST: queue_id, offset, email_subject (optional), email_body (optional)
 */

require_once __DIR__ . '/auth.php';
requireLogin();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/libs/CertMailer.php';

header('Content-Type: application/json');
set_time_limit(180);
ini_set('memory_limit', '256M');

function je(string $m): void { echo json_encode(['success' => false, 'error' => $m]); exit; }

/* ── inputs ───────────────────────────────────────────────── */
$queueId = preg_replace('/[^a-zA-Z0-9_\-]/', '', $_POST['queue_id'] ?? '');
$offset  = max(0, (int)($_POST['offset'] ?? 0));
$subjOvr = (isset($_POST['email_subject']) && trim($_POST['email_subject']) !== '') ? trim($_POST['email_subject']) : null;
$bodyOvr = (isset($_POST['email_body'])    && trim($_POST['email_body'])    !== '') ? $_POST['email_body']         : null;

if (!$queueId) je('No queue_id provided.');

/* ── load manifest ────────────────────────────────────────── */
$queueDir  = TEMP_DIR . $queueId . '/';
$maniPath  = $queueDir . 'manifest.json';
if (!file_exists($maniPath)) je("Queue not found: $queueId — it may have expired.");

$data = json_decode(file_get_contents($maniPath), true);
if (!$data) je('Manifest file unreadable.');

$queue    = &$data['queue'];
$total    = count($queue);
$subject  = $subjOvr ?? $data['email_subject'] ?? EMAIL_SUBJECT;
$bodyHTML = $bodyOvr  ?? $data['email_body']    ?? EMAIL_BODY_HTML;

/* ── collect pending items from $offset ──────────────────── */
$toSend = [];
for ($i = $offset; $i < $total; $i++) {
    if (($queue[$i]['status'] ?? '') === 'pending') {
        $toSend[$i] = $queue[$i];
        if (count($toSend) >= BATCH_SIZE) break;
    }
}

// Next offset = one past the last index we examined
$lastIdx    = empty($toSend) ? ($total - 1) : max(array_keys($toSend));
$nextOffset = $lastIdx + 1;

/* ── setup mailer ─────────────────────────────────────────── */
$mailer            = new CertMailer();
$mailer->host      = SMTP_HOST;
$mailer->port      = SMTP_PORT;
$mailer->encryption= SMTP_ENCRYPTION;
$mailer->username  = SMTP_USERNAME;
$mailer->password  = SMTP_PASSWORD;
$mailer->fromEmail = SMTP_FROM_EMAIL;
$mailer->fromName  = SMTP_FROM_NAME;

/* ── send ─────────────────────────────────────────────────── */
$batchSent = $batchFailed = 0;
$errLog    = [];

foreach ($toSend as $idx => $item) {
    $name  = $item['name'];
    $email = $item['email'];
    $ref   = &$queue[$idx];

    /* validate */
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $ref['status'] = 'failed';
        $ref['error']  = 'Invalid email address';
        $batchFailed++;
        $errLog[] = ['name'=>$name,'email'=>$email,'error'=>'Invalid email address','time'=>date('c')];
        continue;
    }

    /* check PDF file */
    $pdfPath = $queueDir . $item['file'];
    if (empty($item['file']) || !file_exists($pdfPath)) {
        $ref['status'] = 'failed';
        $ref['error']  = 'Certificate PDF missing from server';
        $batchFailed++;
        $errLog[] = ['name'=>$name,'email'=>$email,'error'=>'Certificate PDF missing','time'=>date('c')];
        continue;
    }

    /* personalise */
    $mailer->subject  = str_replace('{NAME}', $name, $subject);
    $mailer->htmlBody = str_replace('{NAME}', htmlspecialchars($name, ENT_QUOTES, 'UTF-8'), $bodyHTML);
    $mailer->clearAttachments();
    $mailer->addAttachment(basename($pdfPath), file_get_contents($pdfPath), 'application/pdf');

    /* send */
    if ($mailer->send($email, $name)) {
        $ref['status']  = 'sent';
        $ref['sent_at'] = date('c');
        $batchSent++;
        usleep(300000); // 300 ms pause — anti-spam
    } else {
        $ref['status'] = 'failed';
        $ref['error']  = $mailer->lastError;
        $batchFailed++;
        $errLog[] = ['name'=>$name,'email'=>$email,'error'=>$mailer->lastError,'time'=>date('c')];
    }
}

/* ── save manifest ────────────────────────────────────────── */
file_put_contents($maniPath, json_encode($data, JSON_PRETTY_PRINT));

/* ── error log ────────────────────────────────────────────── */
if (!empty($errLog)) {
    if (!is_dir(LOG_DIR)) mkdir(LOG_DIR, 0755, true);
    $logFile  = LOG_DIR . 'email_' . $queueId . '.json';
    $existing = file_exists($logFile) ? (json_decode(file_get_contents($logFile), true) ?? []) : [];
    file_put_contents($logFile, json_encode(array_merge($existing, $errLog), JSON_PRETTY_PRINT));
}

/* ── totals ───────────────────────────────────────────────── */
$totSent = $totFailed = $totPending = $totSkipped = 0;
foreach ($queue as $q) {
    $s = $q['status'] ?? '';
    if      ($s === 'sent')             $totSent++;
    elseif  ($s === 'failed')           $totFailed++;
    elseif  ($s === 'pending')          $totPending++;
    elseif  ($s === 'skipped_no_email') $totSkipped++;
}

echo json_encode([
    'success'       => true,
    'batch_sent'    => $batchSent,
    'batch_failed'  => $batchFailed,
    'total_sent'    => $totSent,
    'total_failed'  => $totFailed,
    'total_pending' => $totPending,
    'total_skipped' => $totSkipped,
    'offset'        => $nextOffset,
    'done'          => ($totPending === 0),
]);
