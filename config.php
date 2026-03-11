<?php
// ============================================================
//  CERTIFICATE GENERATOR — CONFIGURATION
//  Edit this file before uploading to your server.
// ============================================================

// --- AUTH ---
define('AUTH_USERNAME', 'USERNAME');
define('AUTH_PASSWORD', 'YOUR_PASSWORD');   // CHANGE THIS

// --- SMTP ---
define('SMTP_HOST',       'smtp.example.com');
define('SMTP_PORT',       587);                      // 465 for SSL, 587 for TLS
define('SMTP_ENCRYPTION', 'tls');                    // 'tls' or 'ssl'
define('SMTP_USERNAME',   'example@example.com');
define('SMTP_PASSWORD',   'PASSWORD');
define('SMTP_FROM_EMAIL', 'example@example.com');
define('SMTP_FROM_NAME',  'NAME OR ORGANIZATION');

// --- EMAIL CONTENT ---
define('EMAIL_SUBJECT',   'Certificate of Completion - {NAME}');
define('EMAIL_BODY_HTML', '
<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;">
  <h2 style="color:#1d4ed8;">Congratulations, {NAME}!</h2>
  <p>We are pleased to present you with your certificate. Please find it attached to this email.</p>
  <p>Best regards,<br><strong>' . SMTP_FROM_NAME . '</strong></p>
</div>
');

// --- PATHS (relative to this file) ---
define('UPLOAD_DIR',  __DIR__ . '/uploads/');
define('TEMP_DIR',    __DIR__ . '/temp/');
define('LOG_DIR',     __DIR__ . '/logs/');
define('FONTS_DIR',   __DIR__ . '/fonts/');

// --- BATCH SIZE for email sending ---
define('BATCH_SIZE', 50);

// --- SESSION ---
define('SESSION_NAME', '0;fahifrwhpgeofiowgah4efpshfoidf');  // CHANGE THIS TO A RANDOM STRING

// --- SECURITY ---
define('ALLOWED_IMG_TYPES', ['image/jpeg', 'image/png', 'image/jpg']);
define('ALLOWED_PDF_TYPE',  'application/pdf');
define('MAX_UPLOAD_MB',     20);
