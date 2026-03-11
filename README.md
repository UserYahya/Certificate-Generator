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

---

## Step 4 — Download Custom Fonts (optional but recommended)

Download these TTF files from Google Fonts (fonts.google.com):

| Font Name        | TTF filename to save as                  |
|-----------------|------------------------------------------|
| Great Vibes      | `GreatVibes-Regular.ttf`                 |
| Pinyon Script    | `PinyonScript-Regular.ttf`               |
| Parisienne       | `Parisienne-Regular.ttf`                 |
| Libre Caslon Text| `LibreCaslonText-Regular.ttf`            |

Place all TTF files in: `fonts/custom/`

> **Note:** Garamond and Baskerville fall back to Times New Roman in PDF output
> (they render correctly in the canvas preview via Google Fonts web).
> For perfect PDF output, download EB Garamond or Libre Baskerville TTFs too.

---

## Step 5 — Set Folder Permissions

In cPanel File Manager, right-click and set permissions to **755** on:
- `uploads/`
- `temp/`
- `logs/`

---

## Step 6 — CSV Format

Your CSV must have at minimum a `Name` column. For email sending, add an `Email` column:

```csv
Name,Email
John Smith,john@example.com
Jane Doe,jane@example.com
Alice Brown,alice@example.com
```

---

## Step 7 — Using the App

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

## Anti-Spam Measures Implemented

- Proper SMTP authentication (not PHP mail())
- Plain-text alternative body included
- Message-ID header set
- X-Mailer header identifies sender
- 300ms delay between emails to avoid rate limits
- Personalized body (reduces spam score vs bulk identical emails)
- HTML + Plain text multipart format
- SPF/DKIM: Make sure your domain has SPF and DKIM records set up in cPanel → Email → Email Deliverability

---

## Batch Size

Default batch size is **50 emails per batch** (set in config.php as `BATCH_SIZE`).

For 350 emails with the "Send All" button, it will automatically run ~7 batches with a 1.5-second pause between each. Total time: roughly 5–10 minutes depending on your SMTP server.

---

## Troubleshooting

- **Blank page after login:** Check PHP error logs in cPanel. Usually a missing library.
- **SMTP error:** Test your SMTP credentials with a simple PHP mail script first.
- **PDF template not working:** Make sure FPDI `src/` folder is correctly placed.
- **Font not rendering in PDF:** Confirm the `.ttf` file is in `fonts/custom/` with the exact filename from the table above.
