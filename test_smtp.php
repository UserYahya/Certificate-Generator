<?php
require_once __DIR__ . '/auth.php';
requireLogin();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/libs/CertMailer.php';

header('Content-Type: application/json');

$to   = trim($_POST['to'] ?? '');
$subj = trim($_POST['subject'] ?? 'CertGen SMTP Test');

if (!$to || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'error' => 'Provide a valid "to" email address.']);
    exit;
}

$mailer             = new CertMailer();
$mailer->host       = SMTP_HOST;
$mailer->port       = SMTP_PORT;
$mailer->encryption = SMTP_ENCRYPTION;
$mailer->username   = SMTP_USERNAME;
$mailer->password   = SMTP_PASSWORD;
$mailer->fromEmail  = SMTP_FROM_EMAIL;
$mailer->fromName   = SMTP_FROM_NAME;
$mailer->subject    = $subj;
$mailer->htmlBody   = '<p>This is a test email from your <strong>Certificate Generator</strong> application.</p><p>If you received this, SMTP is working correctly! ✅</p>';
$mailer->debug      = true;

$ok = $mailer->send($to, 'Test Recipient');

echo json_encode([
    'success'   => $ok,
    'error'     => $ok ? null : $mailer->lastError,
    'smtp_log'  => $mailer->debugLog,
    'config'    => [
        'host'       => SMTP_HOST,
        'port'       => SMTP_PORT,
        'encryption' => SMTP_ENCRYPTION,
        'username'   => SMTP_USERNAME,
        'from'       => SMTP_FROM_EMAIL,
    ],
]);
