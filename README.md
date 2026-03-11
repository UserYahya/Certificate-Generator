# 📜 Bulk Certificate Generator — Setup Guide

## Folder Structure to Upload

Upload ALL of the following to your cPanel `public_html/certgen/` (or any subfolder):

```
certgen/
├── index.php            ← Main app
├── login.php            ← Login page
├── logout.php
├── auth.php
├── config.php           ← ⚠️ EDIT THIS FIRST
├── generate.php         ← PDF generation API
├── send_batch.php       ← Email sending API
├── download.php         ← ZIP download handler
├── logs.php             ← Error log viewer
├── .htaccess            ← Security rules
│
├── libs/
│   ├── CertMailer.php   ← Built-in SMTP (no Composer needed)
│   ├── fpdf/
│   │   └── X     ← Download from fpdf.org, and place everything here
│   └── fpdi/
│       ├── autoload.php
│       └── src/         ← Download from github.com/setasign/fpdi
│
├── fonts/
│   └── custom/          ← The folder where custom fonts will be stored
│
├── uploads/             ← Must be writable (chmod 755)
├── temp/                ← Must be writable (chmod 755)
└── logs/                ← Must be writable (chmod 755)
```

---

## Step 1 — Edit config.php

Open `config.php` and update:

```php
define('AUTH_USERNAME', 'admin');          // Your login username
define('AUTH_PASSWORD', 'your_password'); // Your login password

define('SMTP_HOST',       'mail.yourdomain.com');
define('SMTP_PORT',       587);
define('SMTP_ENCRYPTION', 'tls');          // 'tls' for port 587, 'ssl' for port 465
define('SMTP_USERNAME',   'you@yourdomain.com');
define('SMTP_PASSWORD',   'smtp_password');
define('SMTP_FROM_EMAIL', 'you@yourdomain.com');
define('SMTP_FROM_NAME',  'Your Organization');
```

---

## Step 2 — Download FPDF (required)

1. Go to: https://www.fpdf.org/
2. Download the latest FPDF release (fpdf18x.zip)
3. Extract all the files into: `libs/fpdf/` folder

---

## Step 3 — Download FPDI (required for PDF templates)

FPDI allows overlaying text onto existing PDF templates.

**Free version (works for non-encrypted PDFs):**
1. Go to: https://github.com/setasign/fpdi/releases
2. Download the latest release zip
3. Extract and place the `src/` folder into: `libs/fpdi/src/`

So the path should be: `libs/fpdi/src/Fpdi.php` etc.


## Step 4 — Set Folder Permissions

In cPanel File Manager, right-click and set permissions to **755** on:
- `uploads/`
- `temp/`
- `logs/`

---

## Step 5 — CSV Format

Your CSV must have at minimum a `Name` column. For email sending, add an `Email` column:

```csv
Name,Email
John Smith,john@example.com
Jane Doe,jane@example.com
Alice Brown,alice@example.com
```

---

## Step 6 — Using the App

1. Go to `https://yourdomain.com/certgen/` → you'll see the login page
2. Log in with your credentials from `config.php`
3. **Generator tab:**
   - Upload template (JPG, PNG, or PDF)
   - Draw a box where the name should appear
   - Upload your CSV
   - Choose font and color
   - Click **Download as ZIP** for just certificates
   - Click **Generate & Queue Emails** to also send certificates by email
4. **Email Queue tab:** Monitor and control batch email sending
5. **Error Logs tab:** View any failed emails or generation errors

---

## Batch Size

Default batch size is **50 emails per batch** (set in config.php as `BATCH_SIZE`).

---

## Troubleshooting

- **Blank page after login:** Check PHP error logs in cPanel. Usually a missing library.
- **SMTP error:** Test your SMTP credentials with a simple PHP mail script first.
- **PDF template not working:** Make sure FPDI `src/` folder is correctly placed.
- **Font not rendering in PDF:** Confirm the `.ttf` file is in `fonts/custom/` with the exact filename from the table above.
