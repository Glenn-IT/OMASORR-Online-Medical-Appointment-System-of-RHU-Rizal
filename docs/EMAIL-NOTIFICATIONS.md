# Email Notification System — RHU Rizal

## Overview

The RHU Rizal appointment system sends automated email notifications to patients using **PHPMailer** over **Gmail SMTP**. Emails are triggered at key points in the appointment lifecycle — registration, booking, status changes, and cancellations.

All email sending is **non-blocking**: if Gmail is unreachable or the send fails for any reason, the core action (e.g. booking an appointment) still completes successfully. Errors are written to PHP's error log.

---

## How It Works

### Technology Stack

| Component | Details |
|-----------|---------|
| Library | PHPMailer v7.1.1 (installed via Composer) |
| Protocol | SMTP with STARTTLS encryption |
| Provider | Gmail (`smtp.gmail.com`, port 587) |
| Auth | Gmail App Password (not the account password) |

### Request Flow

```
Patient/Admin action (POST)
        │
        ▼
  Action file runs
  (register.php, book-appointment.php, etc.)
        │
        ├── Core logic executes (DB insert/update)
        │
        └── sendXxxEmail() called after success
                │
                ▼
         config/mailer.php
                │
                ├── getMailer() — builds PHPMailer instance with Gmail credentials
                ├── Builds HTML email body with inline styles
                └── PHPMailer sends via smtp.gmail.com:587 (STARTTLS)
```

### Key Files

| File | Purpose |
|------|---------|
| `config/config.php` | Holds Gmail SMTP constants (`MAIL_HOST`, `MAIL_USERNAME`, `MAIL_PASSWORD`, etc.) |
| `config/mailer.php` | Central email helper — `getMailer()`, HTML layout, and all send functions |
| `vendor/` | PHPMailer library installed by Composer |
| `composer.json` | Declares `phpmailer/phpmailer ^7.1` dependency |

---

## Gmail Credentials

Credentials are stored as PHP constants in `config/config.php`:

```php
define('MAIL_HOST',      'smtp.gmail.com');
define('MAIL_PORT',      587);
define('MAIL_USERNAME',  'rhurizalcagayan@gmail.com');
define('MAIL_PASSWORD',  'xxxx xxxx xxxx xxxx');  // 16-char App Password
define('MAIL_FROM',      'rhurizalcagayan@gmail.com');
define('MAIL_FROM_NAME', 'RHU Rizal Clinic');
```

> **Important:** `MAIL_PASSWORD` is a **Google App Password**, not the Gmail account password.
> App Passwords are generated at: Google Account → Security → 2-Step Verification → App Passwords.
> A new App Password must be generated if 2-Step Verification is ever reset.

---

## Email Notifications

### 1. Welcome Email
- **Trigger:** Patient completes registration
- **File:** `actions/register.php`
- **Function:** `sendWelcomeEmail()`
- **Subject:** `Welcome to RHU Rizal — Your Account is Ready`
- **Contains:** Patient No., username, email address

### 2. Booking Confirmation
- **Trigger:** Patient successfully books an appointment
- **File:** `actions/book-appointment.php`
- **Function:** `sendBookingConfirmation()`
- **Subject:** `Appointment Booked — APT-XXX`
- **Contains:** Appointment No., status (Pending), date, time, service, doctor, reason for visit

### 3. Appointment Approved
- **Trigger:** Admin approves a Pending appointment
- **File:** `actions/admin/update-appointment.php`
- **Function:** `sendAppointmentStatusEmail(..., 'Approved')`
- **Subject:** `Appointment Approved — APT-XXX`
- **Contains:** Full appointment details, green Approved badge, reminder to arrive 10 minutes early

### 4. Appointment Rejected
- **Trigger:** Admin rejects a Pending appointment
- **File:** `actions/admin/update-appointment.php`
- **Function:** `sendAppointmentStatusEmail(..., 'Rejected', $note)`
- **Subject:** `Appointment Rejected — APT-XXX`
- **Contains:** Full appointment details, rejection reason from admin

### 5. Appointment Completed
- **Trigger:** Admin marks an Approved appointment as Completed
- **File:** `actions/admin/update-appointment.php`
- **Function:** `sendAppointmentStatusEmail(..., 'Completed')`
- **Subject:** `Appointment Completed — APT-XXX`
- **Contains:** Full appointment details, thank-you message

### 6. Appointment Cancelled by Admin
- **Trigger:** Admin cancels an appointment
- **File:** `actions/admin/update-appointment.php`
- **Function:** `sendAppointmentStatusEmail(..., 'Cancelled', $note)`
- **Subject:** `Appointment Cancelled — APT-XXX`
- **Contains:** Full appointment details, cancellation reason from admin

### 7. Appointment Cancelled by Patient
- **Trigger:** Patient cancels their own appointment
- **File:** `actions/cancel-appointment.php`
- **Function:** `sendCancellationEmail()`
- **Subject:** `Appointment Cancelled — APT-XXX`
- **Contains:** Appointment details, confirmation that cancellation was processed

---

## mailer.php Function Reference

```php
// Returns a configured PHPMailer instance ready to use
getMailer(): PHPMailer

// Wraps HTML body in the standard RHU email layout (header, content, footer)
emailLayout(string $title, string $body): string

// Renders a single label/value row for use inside an email detail table
emailRow(string $label, string $value): string

// Sends a welcome email after patient registration
sendWelcomeEmail(string $to, string $name, string $username, string $patientNo): void

// Sends booking confirmation after a new appointment is created
sendBookingConfirmation(string $to, string $name, array $appt): void

// Sends a status-change notification (Approved / Rejected / Completed / Cancelled)
// $note is the admin reason — shown in a highlighted block when present
sendAppointmentStatusEmail(string $to, string $name, array $appt, string $newStatus, string $note = ''): void

// Sends a cancellation confirmation after a patient cancels their own appointment
sendCancellationEmail(string $to, string $name, array $appt): void
```

The `$appt` array passed to booking/status/cancellation functions uses these keys:

```php
[
    'appt_no' => 'APT-001',
    'date'    => '2026-06-15',       // Y-m-d format
    'time'    => '09:00:00',         // H:i:s format
    'service' => 'General Check-up',
    'doctor'  => 'Dr. Juan dela Cruz',
    'reason'  => 'Routine check-up', // booking confirmation only
]
```

---

## Email Template Design

All emails share a consistent HTML layout with inline CSS (required for email client compatibility):

- **Header:** Dark green (`#1a6b3c`) with clinic name and tagline
- **Body:** White card with appointment detail rows on a light grey background
- **Status badges:** Color-coded — green (Approved), red (Rejected), blue (Completed), grey (Cancelled)
- **Admin note block:** Yellow left-bordered highlight block, shown when a note is present
- **Footer:** Light grey with "automated message" disclaimer
- **Plain-text fallback:** Every email includes an `AltBody` for clients that don't render HTML

---

## Error Handling

Every send function wraps PHPMailer in a `try/catch`. On failure:

- The exception is **not re-thrown** — the calling action continues normally
- The error is written to PHP's error log with the format:

```
RHU Mailer [welcome] to patient@example.com: Could not connect to SMTP host.
RHU Mailer [status:Approved] to patient@example.com: ...
```

To view errors in XAMPP: check `C:\xampp\php\logs\php_error_log`.

---

## What Was Accomplished

| # | Task | Status |
|---|------|--------|
| 1 | Installed PHPMailer v7.1.1 via Composer | Done |
| 2 | Added Gmail SMTP constants to `config/config.php` | Done |
| 3 | Created `config/mailer.php` with full HTML email templates | Done |
| 4 | Welcome email on patient registration | Done |
| 5 | Booking confirmation email on appointment creation | Done |
| 6 | Approved / Rejected / Completed / Cancelled email on admin action | Done |
| 7 | Cancellation confirmation email when patient cancels | Done |

**Total: 7 email notifications across 4 action files — all non-blocking.**
