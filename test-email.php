<?php
// ============================================================
// RHU Rizal — Email Diagnostics & Test Page
// ============================================================

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/mailer.php';
require_once __DIR__ . '/config/database.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

$result = null;
$debugLog = '';
$toEmail = $_POST['to_email'] ?? MAIL_USERNAME;
$emailType = $_POST['email_type'] ?? 'otp';
$enableDebug = isset($_POST['enable_debug']) || $_SERVER['REQUEST_METHOD'] !== 'POST';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['action']) && $_POST['action'] === 'send_test') {
    $toEmail = trim($_POST['to_email'] ?? '');
    
    if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
        $result = [
            'status' => 'error',
            'message' => 'Invalid email address provided.'
        ];
    } else {
        try {
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
            $mail->CharSet    = 'UTF-8';

            // Capture SMTP debug output
            $debugLog = '';
            $mail->SMTPDebug = $enableDebug ? SMTP::DEBUG_SERVER : SMTP::DEBUG_OFF;
            $mail->Debugoutput = function($str, $level) use (&$debugLog) {
                $debugLog .= htmlspecialchars($str) . "\n";
            };

            $mail->addAddress($toEmail, 'RHU Test Recipient');

            if ($emailType === 'otp') {
                $otpCode = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                $mail->Subject = 'Your RHU Rizal Password Reset Code (Test)';
                $body = <<<HTML
                <h2 style="margin:0 0 6px;font-size:20px;color:#1a6b3c;">Password Reset Code (Diagnostics Test)</h2>
                <p style="margin:0 0 22px;font-size:14px;color:#6c757d;">Hi Test User, use the code below to reset your password. This is a diagnostic test email.</p>
                <table cellpadding="0" cellspacing="0" style="width:100%;margin-bottom:24px;">
                  <tr><td align="center">
                    <span style="display:inline-block;padding:14px 28px;font-size:28px;font-weight:700;letter-spacing:6px;color:#1a6b3c;background:#f0f4f8;border-radius:8px;">{$otpCode}</span>
                  </td></tr>
                </table>
                <p style="font-size:13px;color:#6c757d;margin:0;">Generated timestamp: <strong>%s</strong></p>
                HTML;
                $body = sprintf($body, date('Y-m-d H:i:s'));
                $mail->Body    = emailLayout('Password Reset Code', $body);
                $mail->AltBody = "Your RHU Rizal password reset test code is {$otpCode}.";
            } elseif ($emailType === 'booking') {
                $mail->Subject = 'Appointment Booked — APT-TEST-999 (Test)';
                $date = date('F j, Y');
                $rows = emailRow('Appointment No.', 'APT-TEST-999')
                      . emailRow('Status', '<span style="color:#d97706;font-weight:700;">Pending Approval</span>')
                      . emailRow('Date', htmlspecialchars($date))
                      . emailRow('Time', '09:00 AM')
                      . emailRow('Service', 'General Consultation')
                      . emailRow('Doctor', 'Dr. Maria Santos, MD')
                      . emailRow('Reason', 'Diagnostic SMTP test booking email');
                $body = <<<HTML
                <h2 style="margin:0 0 6px;font-size:20px;color:#1a6b3c;">Appointment Booked (Test)</h2>
                <p style="margin:0 0 22px;font-size:14px;color:#6c757d;">This is a test notification for appointment bookings.</p>
                <table cellpadding="0" cellspacing="6" style="width:100%;margin-bottom:24px;">{$rows}</table>
                HTML;
                $mail->Body    = emailLayout('Appointment Booked', $body);
                $mail->AltBody = "RHU Test Booking: APT-TEST-999 on {$date} at 09:00 AM.";
            } else {
                $mail->Subject = 'RHU Rizal SMTP Diagnostic Test Ping';
                $body = '<h2 style="color:#1a6b3c;">SMTP Connectivity Test</h2><p>If you are reading this email, your PHPMailer SMTP credentials and Gmail App Password are <strong>working perfectly!</strong></p><p>Server Time: ' . date('Y-m-d H:i:s') . '</p>';
                $mail->Body    = emailLayout('SMTP Test Ping', $body);
                $mail->AltBody = 'SMTP Connectivity Test - RHU Rizal Clinic email is working!';
            }

            $mail->send();
            $result = [
                'status' => 'success',
                'message' => 'Email was sent successfully to ' . htmlspecialchars($toEmail) . '!'
            ];
        } catch (Exception $e) {
            $result = [
                'status' => 'error',
                'message' => 'Failed to send email: ' . $e->getMessage()
            ];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Email Diagnostics &amp; SMTP Test – RHU Rizal</title>
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    body { background: #f0f4f8; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif; color: #1e293b; margin: 0; padding: 30px 15px; }
    .diag-container { max-width: 860px; margin: 0 auto; }
    .diag-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
    .diag-title { font-size: 24px; font-weight: 700; color: #0f172a; margin: 0; }
    .diag-subtitle { font-size: 14px; color: #64748b; margin-top: 4px; }
    .diag-card { background: #fff; border-radius: 12px; padding: 24px 28px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); margin-bottom: 20px; }
    .diag-card h3 { margin: 0 0 16px; font-size: 17px; font-weight: 600; color: #1e293b; display: flex; align-items: center; gap: 8px; }
    .config-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px; margin-bottom: 10px; }
    .config-item { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px 14px; }
    .config-label { font-size: 11px; font-weight: 700; text-transform: uppercase; color: #64748b; letter-spacing: 0.5px; }
    .config-value { font-size: 14px; font-weight: 600; color: #0f172a; margin-top: 4px; word-break: break-all; font-family: monospace; }
    .alert-box { padding: 14px 18px; border-radius: 8px; margin-bottom: 20px; display: flex; gap: 12px; align-items: center; }
    .alert-box.success { background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; }
    .alert-box.error { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
    .log-box { background: #0f172a; color: #38bdf8; border-radius: 8px; padding: 16px; font-family: Consolas, Monaco, monospace; font-size: 12px; line-height: 1.5; max-height: 320px; overflow-y: auto; white-space: pre-wrap; word-break: break-all; margin-top: 14px; }
    .form-group { margin-bottom: 16px; }
    .form-label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: #334155; }
    .form-control, .form-select { width: 100%; box-sizing: border-box; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; }
    .form-control:focus, .form-select:focus { outline: none; border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,0.15); }
    .btn-send { background: #16a34a; color: #fff; border: none; padding: 12px 24px; border-radius: 8px; font-weight: 600; font-size: 15px; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: background .15s; }
    .btn-send:hover { background: #15803d; }
    .btn-back { display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; border-radius: 6px; background: #e2e8f0; color: #334155; text-decoration: none; font-size: 13px; font-weight: 600; }
    .btn-back:hover { background: #cbd5e1; }
  </style>
</head>
<body>
  <div class="diag-container">
    <div class="diag-header">
      <div>
        <h1 class="diag-title"><i class="fa-solid fa-envelope-circle-check" style="color:#16a34a;"></i> Email &amp; SMTP Diagnostics</h1>
        <p class="diag-subtitle">Test and verify Gmail SMTP settings, App Password, and OTP delivery.</p>
      </div>
      <a href="<?= BASE_URL ?>/index.php" class="btn-back"><i class="fa-solid fa-arrow-left"></i> Back to App</a>
    </div>

    <!-- Active Settings Card -->
    <div class="diag-card">
      <h3><i class="fa-solid fa-sliders"></i> Active Mailer Configuration (`config/config.php`)</h3>
      <div class="config-grid">
        <div class="config-item">
          <div class="config-label">SMTP Host</div>
          <div class="config-value"><?= htmlspecialchars(MAIL_HOST) ?></div>
        </div>
        <div class="config-item">
          <div class="config-label">SMTP Port / Encryption</div>
          <div class="config-value"><?= htmlspecialchars(MAIL_PORT) ?> (STARTTLS)</div>
        </div>
        <div class="config-item">
          <div class="config-label">SMTP Username (From)</div>
          <div class="config-value"><?= htmlspecialchars(MAIL_USERNAME) ?></div>
        </div>
        <div class="config-item">
          <div class="config-label">App Password</div>
          <div class="config-value"><?= htmlspecialchars(substr(MAIL_PASSWORD, 0, 4) . ' **** **** ' . substr(MAIL_PASSWORD, -4)) ?></div>
        </div>
      </div>
    </div>

    <!-- Result Banner -->
    <?php if ($result): ?>
      <div class="alert-box <?= $result['status'] ?>">
        <i class="fa-solid <?= $result['status'] === 'success' ? 'fa-circle-check' : 'fa-triangle-exclamation' ?>" style="font-size:22px;"></i>
        <div>
          <strong><?= $result['status'] === 'success' ? 'Success!' : 'Delivery Error' ?></strong>
          <div><?= htmlspecialchars($result['message']) ?></div>
        </div>
      </div>
    <?php endif; ?>

    <!-- Test Form Card -->
    <div class="diag-card">
      <h3><i class="fa-solid fa-paper-plane"></i> Send Test Message</h3>
      <form method="POST" action="">
        <input type="hidden" name="action" value="send_test">
        
        <div class="form-group">
          <label class="form-label" for="to_email">Recipient Email Address</label>
          <input type="email" class="form-control" id="to_email" name="to_email" value="<?= htmlspecialchars($toEmail) ?>" placeholder="e.g. your_email@gmail.com" required>
          <small style="color:#64748b;display:block;margin-top:4px;">Enter the email where you want to receive the test OTP/message.</small>
        </div>

        <div class="form-group">
          <label class="form-label" for="email_type">Message Template to Test</label>
          <select class="form-select" id="email_type" name="email_type">
            <option value="otp" <?= $emailType === 'otp' ? 'selected' : '' ?>>🔑 Password Reset 6-Digit OTP (Simulated Forgot Password)</option>
            <option value="booking" <?= $emailType === 'booking' ? 'selected' : '' ?>>📅 Appointment Booking Confirmation</option>
            <option value="ping" <?= $emailType === 'ping' ? 'selected' : '' ?>>✉️ Basic SMTP Ping / Hello World</option>
          </select>
        </div>

        <div class="form-group" style="margin-bottom:20px;">
          <label style="display:flex;align-items:center;gap:8px;font-size:13.5px;cursor:pointer;">
            <input type="checkbox" name="enable_debug" value="1" <?= $enableDebug ? 'checked' : '' ?>>
            Show real-time SMTP Connection Log (Recommended for diagnosing failures)
          </label>
        </div>

        <button type="submit" class="btn-send">
          <i class="fa-solid fa-paper-plane"></i> Send Test Email Now
        </button>
      </form>

      <?php if (!empty($debugLog)): ?>
        <h4 style="margin:24px 0 6px;font-size:14px;color:#334155;"><i class="fa-solid fa-terminal"></i> SMTP Conversation Log:</h4>
        <div class="log-box"><?= $debugLog ?></div>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>
