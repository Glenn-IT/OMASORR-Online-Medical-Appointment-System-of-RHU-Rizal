<?php
// ============================================================
// RHU Rizal — Email Notification Helper (PHPMailer + Gmail)
// ============================================================

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

// ── Base mailer instance ─────────────────────────────────────

function getMailer(): PHPMailer
{
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host       = MAIL_HOST;
    $mail->SMTPAuth   = true;
    $mail->Username   = MAIL_USERNAME;
    $mail->Password   = MAIL_PASSWORD;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = MAIL_PORT;
    $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
    $mail->isHTML(true);
    $mail->CharSet = 'UTF-8';
    return $mail;
}

// ── HTML layout wrapper ──────────────────────────────────────

function emailLayout(string $title, string $body): string
{
    return <<<HTML
    <!DOCTYPE html>
    <html lang="en">
    <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>{$title}</title>
    </head>
    <body style="margin:0;padding:0;background:#f0f4f8;font-family:Arial,Helvetica,sans-serif;">
      <table width="100%" cellpadding="0" cellspacing="0" style="background:#f0f4f8;padding:30px 0;">
        <tr><td align="center">
          <table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.08);">
            <!-- Header -->
            <tr>
              <td style="background:#1a6b3c;padding:28px 36px;text-align:center;">
                <p style="margin:0;font-size:12px;color:#a8d5b8;letter-spacing:1px;text-transform:uppercase;">Rural Health Unit</p>
                <h1 style="margin:6px 0 0;font-size:22px;color:#ffffff;font-weight:700;">RHU Rizal Cagayan</h1>
                <p style="margin:4px 0 0;font-size:12px;color:#a8d5b8;">Online Medical Appointment System</p>
              </td>
            </tr>
            <!-- Body -->
            <tr>
              <td style="padding:36px 36px 28px;">
                {$body}
              </td>
            </tr>
            <!-- Footer -->
            <tr>
              <td style="background:#f8f9fa;padding:18px 36px;border-top:1px solid #e9ecef;text-align:center;">
                <p style="margin:0;font-size:12px;color:#6c757d;">
                  This is an automated message from <strong>RHU Rizal Cagayan</strong>.<br>
                  Please do not reply to this email.
                </p>
              </td>
            </tr>
          </table>
        </td></tr>
      </table>
    </body>
    </html>
    HTML;
}

// ── Detail row helper ────────────────────────────────────────

function emailRow(string $label, string $value): string
{
    return '<tr>'
         . '<td style="padding:7px 12px;font-size:14px;color:#495057;background:#f8f9fa;border-radius:4px;font-weight:600;width:145px;vertical-align:top;">' . $label . '</td>'
         . '<td style="padding:7px 12px;font-size:14px;color:#212529;vertical-align:top;">' . $value . '</td>'
         . '</tr>';
}

// ── 1. Welcome email (after patient registration) ────────────

function sendWelcomeEmail(string $to, string $name, string $username, string $patientNo): void
{
    try {
        $rows = emailRow('Patient No.', htmlspecialchars($patientNo))
              . emailRow('Username',    htmlspecialchars($username))
              . emailRow('Email',       htmlspecialchars($to));

        $body = <<<HTML
        <h2 style="margin:0 0 6px;font-size:20px;color:#1a6b3c;">Welcome, {$name}!</h2>
        <p style="margin:0 0 22px;font-size:14px;color:#6c757d;">Your patient account has been created successfully.</p>
        <table cellpadding="0" cellspacing="6" style="width:100%;margin-bottom:24px;">
          {$rows}
        </table>
        <p style="font-size:14px;color:#212529;margin:0 0 8px;">You can now log in and book appointments online at your convenience.</p>
        <p style="font-size:13px;color:#6c757d;margin:0;">If you did not create this account, please contact our clinic immediately.</p>
        HTML;

        $mail = getMailer();
        $mail->addAddress($to, $name);
        $mail->Subject = 'Welcome to RHU Rizal — Your Account is Ready';
        $mail->Body    = emailLayout('Welcome to RHU Rizal', $body);
        $mail->AltBody = "Welcome {$name}! Your RHU Rizal patient account has been created. Patient No: {$patientNo}, Username: {$username}.";
        $mail->send();
    } catch (Exception $e) {
        error_log("RHU Mailer [welcome] to {$to}: " . $e->getMessage());
    }
}

// ── 2. Booking confirmation ──────────────────────────────────

function sendBookingConfirmation(string $to, string $name, array $appt): void
{
    try {
        $date = date('F j, Y', strtotime($appt['date']));
        $time = date('g:i A',  strtotime($appt['time']));

        $rows = emailRow('Appointment No.', htmlspecialchars($appt['appt_no']))
              . emailRow('Status',          '<span style="color:#d97706;font-weight:700;">Pending Approval</span>')
              . emailRow('Date',            htmlspecialchars($date))
              . emailRow('Time',            htmlspecialchars($time))
              . emailRow('Service',         htmlspecialchars($appt['service']))
              . emailRow('Doctor',          htmlspecialchars($appt['doctor']))
              . emailRow('Reason',          htmlspecialchars($appt['reason']));

        $body = <<<HTML
        <h2 style="margin:0 0 6px;font-size:20px;color:#1a6b3c;">Appointment Booked</h2>
        <p style="margin:0 0 22px;font-size:14px;color:#6c757d;">Hi {$name}, your appointment has been received and is awaiting admin approval.</p>
        <table cellpadding="0" cellspacing="6" style="width:100%;margin-bottom:24px;">
          {$rows}
        </table>
        <p style="font-size:13px;color:#6c757d;margin:0;">You will receive another email once your appointment is reviewed.</p>
        HTML;

        $mail = getMailer();
        $mail->addAddress($to, $name);
        $mail->Subject = "Appointment Booked — {$appt['appt_no']}";
        $mail->Body    = emailLayout('Appointment Booked', $body);
        $mail->AltBody = "Hi {$name}, your appointment {$appt['appt_no']} has been booked and is awaiting approval. Date: {$date}, Time: {$time}, Doctor: {$appt['doctor']}, Service: {$appt['service']}.";
        $mail->send();
    } catch (Exception $e) {
        error_log("RHU Mailer [booking] to {$to}: " . $e->getMessage());
    }
}

// ── 3. Status change: Approved / Rejected / Completed / Cancelled ──

function sendAppointmentStatusEmail(string $to, string $name, array $appt, string $newStatus, string $note = ''): void
{
    $subjects = [
        'Approved'  => "Appointment Approved — {$appt['appt_no']}",
        'Rejected'  => "Appointment Rejected — {$appt['appt_no']}",
        'Completed' => "Appointment Completed — {$appt['appt_no']}",
        'Cancelled' => "Appointment Cancelled — {$appt['appt_no']}",
    ];
    if (!isset($subjects[$newStatus])) return;

    try {
        $date = date('F j, Y', strtotime($appt['date']));
        $time = date('g:i A',  strtotime($appt['time']));

        $statusStyle = [
            'Approved'  => ['color' => '#15803d', 'bg' => '#dcfce7'],
            'Rejected'  => ['color' => '#b91c1c', 'bg' => '#fee2e2'],
            'Completed' => ['color' => '#1d4ed8', 'bg' => '#dbeafe'],
            'Cancelled' => ['color' => '#6b7280', 'bg' => '#f3f4f6'],
        ];
        $sc    = $statusStyle[$newStatus];
        $badge = "<span style=\"display:inline-block;padding:3px 10px;border-radius:12px;font-size:13px;font-weight:700;color:{$sc['color']};background:{$sc['bg']}\">{$newStatus}</span>";

        $rows = emailRow('Appointment No.', htmlspecialchars($appt['appt_no']))
              . emailRow('Status',          $badge)
              . emailRow('Date',            htmlspecialchars($date))
              . emailRow('Time',            htmlspecialchars($time))
              . emailRow('Service',         htmlspecialchars($appt['service']))
              . emailRow('Doctor',          htmlspecialchars($appt['doctor']));

        $noteHtml = $note
            ? '<p style="margin:18px 0 0;font-size:14px;color:#212529;background:#fff3cd;border-left:4px solid #ffc107;padding:12px 16px;border-radius:4px;">'
              . '<strong>Note from clinic:</strong> ' . htmlspecialchars($note) . '</p>'
            : '';

        $headlines = [
            'Approved'  => ['Your Appointment is <span style="color:#15803d;">Approved</span>',  "Great news, {$name}! Your appointment has been confirmed. Please arrive 10 minutes early."],
            'Rejected'  => ['Your Appointment was <span style="color:#b91c1c;">Rejected</span>',  "We're sorry, {$name}. Your appointment could not be approved at this time. You may book a new one."],
            'Completed' => ['Appointment <span style="color:#1d4ed8;">Completed</span>',           "Thank you for visiting RHU Rizal, {$name}. We hope you received the care you needed."],
            'Cancelled' => ['Appointment <span style="color:#6b7280;">Cancelled</span>',           "Hi {$name}, your appointment has been cancelled. You may book a new appointment at any time."],
        ];
        [$headline, $subtitle] = $headlines[$newStatus];

        $body = <<<HTML
        <h2 style="margin:0 0 6px;font-size:20px;color:#212529;">{$headline}</h2>
        <p style="margin:0 0 22px;font-size:14px;color:#6c757d;">{$subtitle}</p>
        <table cellpadding="0" cellspacing="6" style="width:100%;margin-bottom:4px;">
          {$rows}
        </table>
        {$noteHtml}
        HTML;

        $mail = getMailer();
        $mail->addAddress($to, $name);
        $mail->Subject = $subjects[$newStatus];
        $mail->Body    = emailLayout($subjects[$newStatus], $body);
        $mail->AltBody = "Hi {$name}, your appointment {$appt['appt_no']} has been {$newStatus}." . ($note ? " Note: {$note}" : '');
        $mail->send();
    } catch (Exception $e) {
        error_log("RHU Mailer [status:{$newStatus}] to {$to}: " . $e->getMessage());
    }
}

// ── 4. Patient self-cancellation confirmation ────────────────

function sendCancellationEmail(string $to, string $name, array $appt): void
{
    try {
        $date = date('F j, Y', strtotime($appt['date']));
        $time = date('g:i A',  strtotime($appt['time']));

        $rows = emailRow('Appointment No.', htmlspecialchars($appt['appt_no']))
              . emailRow('Date',            htmlspecialchars($date))
              . emailRow('Time',            htmlspecialchars($time))
              . emailRow('Service',         htmlspecialchars($appt['service']))
              . emailRow('Doctor',          htmlspecialchars($appt['doctor']));

        $body = <<<HTML
        <h2 style="margin:0 0 6px;font-size:20px;color:#212529;">Appointment <span style="color:#6b7280;">Cancelled</span></h2>
        <p style="margin:0 0 22px;font-size:14px;color:#6c757d;">Hi {$name}, your cancellation request has been processed.</p>
        <table cellpadding="0" cellspacing="6" style="width:100%;margin-bottom:24px;">
          {$rows}
        </table>
        <p style="font-size:14px;color:#212529;margin:0;">You may book a new appointment at any time.</p>
        HTML;

        $mail = getMailer();
        $mail->addAddress($to, $name);
        $mail->Subject = "Appointment Cancelled — {$appt['appt_no']}";
        $mail->Body    = emailLayout('Appointment Cancelled', $body);
        $mail->AltBody = "Hi {$name}, your appointment {$appt['appt_no']} on {$date} at {$time} has been cancelled as requested.";
        $mail->send();
    } catch (Exception $e) {
        error_log("RHU Mailer [cancel] to {$to}: " . $e->getMessage());
    }
}
