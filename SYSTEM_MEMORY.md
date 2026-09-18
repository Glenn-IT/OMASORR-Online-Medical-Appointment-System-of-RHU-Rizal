# 🧠 RHU Rizal System Memory & Synchronization Matrix

> **Single Source of Truth for Architecture, Dependencies, and Multi-File Synchronization**  
> **Project:** Rural Health Unit of Rizal — Online Medical Appointment System (OMASORR)  
> **Repository:** `C:\xampp\htdocs\rhu-appointment-system`  
> **Stack:** PHP 8+ (Native/PDO), MySQL, PHPMailer, Vanilla JS (ES6+), CSS3, Apache (XAMPP)  
> **Last Verified:** September 2026

---

## ⚡ 1. The Core Law of System Synchronization

```
                                  ┌────────────────────────┐
                                  │   DATABASE / SCHEMA    │
                                  │   (Tables, Enums, FKs) │
                                  └───────────┬────────────┘
                                              │
                       ┌──────────────────────┴──────────────────────┐
                       ▼                                             ▼
           ┌────────────────────────┐                    ┌────────────────────────┐
           │   BACKEND ACTIONS      │◄──────────────────►│   CONFIG / HELPERS     │
           │   (actions/*.php)      │                    │   (auth, mailer, db)   │
           └───────────┬────────────┘                    └───────────┬────────────┘
                       │                                             │
                       ├──────────────────────┬──────────────────────┤
                       ▼                      ▼                      ▼
           ┌────────────────────────┐ ┌────────────────┐ ┌────────────────────────┐
           │   VIEWS / UI TEMPLATES │ │ INCLUDES / NAV │ │ NOTIFICATIONS / MAILER │
           │   (views/**/*.php)     │ │ (sidebar, etc) │ │ (mailer.php templates) │
           └───────────┬────────────┘ └────────────────┘ └────────────────────────┘
                       │
                       ▼
           ┌────────────────────────┐
           │   CLIENT ASSETS        │
           │   (app.js, style.css)  │
           └────────────────────────┘
```

> [!IMPORTANT]
> **NEVER EDIT A FILE IN ISOLATION.**  
> Any change to an entity, column, action, status, parameter, or session variable has cascading effects across the entire system. Before making or accepting any change, developers and AI agents **MUST** consult this document, identify all impacted files and connections, and update every linked component simultaneously.

---

## 🗺️ 2. Global Architecture & Runtime Standards

### 2.1 Configuration & Core Singletons

| Component | File Path | Global Scope / Responsibility |
|---|---|---|
| **System Constants** | [`config/config.php`](file:///C:/xampp/htdocs/rhu-appointment-system/config/config.php) | `BASE_URL`, `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`, `DB_CHARSET`, `SESSION_NAME`, `MAIL_*` credentials. |
| **Database Singleton** | [`config/database.php`](file:///C:/xampp/htdocs/rhu-appointment-system/config/database.php) | `db(): PDO` — Returns singleton PDO instance configured with `PDO::ERRMODE_EXCEPTION` and `PDO::FETCH_ASSOC`. |
| **Auth & Session Guard** | [`config/auth.php`](file:///C:/xampp/htdocs/rhu-appointment-system/config/auth.php) | Session initialization (`SameSite=Strict`, `HttpOnly`), CSRF protection, auth guards (`requireLogin`), flash messaging. |
| **Email Dispatcher** | [`config/mailer.php`](file:///C:/xampp/htdocs/rhu-appointment-system/config/mailer.php) | PHPMailer SMTP client (`getMailer()`), HTML email templating (`emailLayout()`), automated triggers for notifications. |

### 2.2 Global Session Schemas

When authentication succeeds, session structures are strictly mapped:

#### Patient Session (`$_SESSION['patient']`)
```php
$_SESSION['patient'] = [
    'id'         => (int) $patient['id'],        // patients.id (NOT users.id)
    'patient_no' => (string) $patient['patient_no'], // e.g. "P-001"
    'full_name'  => (string) $patient['full_name'],  // e.g. "Juan Dela Cruz"
    'username'   => (string) $user['username'],      // users.username
    'status'     => (string) $user['status'],        // 'Active' | 'Inactive'
];
```

#### Admin Session (`$_SESSION['admin']`)
```php
$_SESSION['admin'] = [
    'id'        => (int) $admin['id'],              // admin_users.id
    'full_name' => (string) $admin['full_name'],    // e.g. "System Administrator"
    'username'  => (string) $admin['username'],     // admin_users.username
    'email'     => (string) $admin['email'],        // admin_users.email
    'phone'     => (string) ($admin['phone'] ?? ''),// admin_users.phone
    'role'      => (string) $admin['role'],         // e.g. "System Administrator"
];
```

#### Flash Messaging (`$_SESSION['flash']`)
```php
$_SESSION['flash'][$key] = [
    'message' => (string) $message,
    'type'    => (string) $type, // 'success' | 'danger' | 'warning' | 'info'
];
```

#### CSRF Token
```php
$_SESSION['csrf_token'] = (string) bin2hex(random_bytes(32));
```

---

## 📊 3. Master Feature Synchronization Matrix

Use this matrix to trace **every single connection** whenever you refactor, rename, or modify a feature.

| Module ID & Name | DB Tables & Columns | Backend Controllers (`actions/`) | Views & Pages (`views/` & root) | Layouts & Includes | Helpers & Functions | Assets (JS / CSS) | Mailer Triggers | What Breaks If You Touch This |
|---|---|---|---|---|---|---|---|---|
| **M1: Patient Registration** | `users` (`id`, `username`, `password`, `role`, `status`),<br>`patients` (`id`, `user_id`, `patient_no`, `full_name`, `email`, `phone`, `address`, `birthdate`, `gender`, `blood_type`) | [`actions/register.php`](file:///C:/xampp/htdocs/rhu-appointment-system/actions/register.php) | [`views/user/signup.php`](file:///C:/xampp/htdocs/rhu-appointment-system/views/user/signup.php),<br>redirects to [`index.php`](file:///C:/xampp/htdocs/rhu-appointment-system/index.php) | [`includes/header.php`](file:///C:/xampp/htdocs/rhu-appointment-system/includes/header.php),<br>[`includes/footer.php`](file:///C:/xampp/htdocs/rhu-appointment-system/includes/footer.php) | `db()`,<br>`verifyCsrf()`,<br>`flashMessage()`,<br>`redirectTo()` | Step navigation form validation in `signup.php`,<br>`assets/css/style.css` | `sendWelcomeEmail($email, $name, $username, $patientNo)` | Patient ID numbering (`P-XXX`), Patient-User 1-to-1 foreign key, Login username lookup, Email uniqueness checks. |
| **M2: Patient Authentication** | `users` (`username`, `password`, `status`, `failed_attempts`, `locked_until`),<br>`patients` (`id`, `patient_no`, `full_name`) | [`actions/login.php`](file:///C:/xampp/htdocs/rhu-appointment-system/actions/login.php),<br>[`actions/logout.php`](file:///C:/xampp/htdocs/rhu-appointment-system/actions/logout.php) | [`index.php`](file:///C:/xampp/htdocs/rhu-appointment-system/index.php) (Patient Login),<br>redirects to [`views/user/dashboard.php`](file:///C:/xampp/htdocs/rhu-appointment-system/views/user/dashboard.php) | [`includes/header.php`](file:///C:/xampp/htdocs/rhu-appointment-system/includes/header.php),<br>[`includes/footer.php`](file:///C:/xampp/htdocs/rhu-appointment-system/includes/footer.php) | `setPatientSession()`,<br>`requireLogin('patient')`,<br>`verifyCsrf()`,<br>`flashMessage()`,<br>`getFlash()` | Form submit handlers, lockout countdown / messages, `style.css` | None | Account lockout lock logic (5 attempts = 15m lockout), all patient-protected pages (`requireLogin('patient')`), patient sidebar user badge. |
| **M3: Admin Authentication** | `admin_users` (`id`, `username`, `password`, `full_name`, `email`, `phone`, `role`, `status`, `failed_attempts`, `locked_until`) | [`actions/admin/login.php`](file:///C:/xampp/htdocs/rhu-appointment-system/actions/admin/login.php),<br>[`actions/logout.php`](file:///C:/xampp/htdocs/rhu-appointment-system/actions/logout.php) | [`views/admin/login.php`](file:///C:/xampp/htdocs/rhu-appointment-system/views/admin/login.php),<br>redirects to [`views/admin/dashboard.php`](file:///C:/xampp/htdocs/rhu-appointment-system/views/admin/dashboard.php) | [`includes/header.php`](file:///C:/xampp/htdocs/rhu-appointment-system/includes/header.php),<br>[`includes/footer.php`](file:///C:/xampp/htdocs/rhu-appointment-system/includes/footer.php) | `setAdminSession()`,<br>`requireLogin('admin')`,<br>`verifyCsrf()`,<br>`flashMessage()` | Admin login styling, password reveal toggles | None | All admin pages (`requireLogin('admin')`), admin sidebar profile info, audit log user attribution (`changed_by`). |
| **M4: Password Recovery / OTP** | `password_resets` (`account_type`, `account_id`, `otp_hash`, `attempts`, `expires_at`, `verified_at`),<br>`users`,<br>`admin_users` | [`actions/forgot-password/request.php`](file:///C:/xampp/htdocs/rhu-appointment-system/actions/forgot-password/request.php),<br>[`actions/forgot-password/verify.php`](file:///C:/xampp/htdocs/rhu-appointment-system/actions/forgot-password/verify.php),<br>[`actions/forgot-password/reset.php`](file:///C:/xampp/htdocs/rhu-appointment-system/actions/forgot-password/reset.php) | Forms rendered in `index.php` (modal/page) or standalone OTP reset views | [`includes/header.php`](file:///C:/xampp/htdocs/rhu-appointment-system/includes/header.php),<br>[`includes/footer.php`](file:///C:/xampp/htdocs/rhu-appointment-system/includes/footer.php) | `db()`,<br>`verifyCsrf()`,<br>`flashMessage()`,<br>`redirectTo()` | OTP digit input focus handling in `assets/js/app.js` | `sendPasswordResetOtpEmail($email, $name, $code)` | Password hashing compatibility (`password_hash`), expiry time window (10 mins), account lock status verification. |
| **M5: Appointment Booking (Patient)** | `appointments` (`appt_no`, `patient_id`, `doctor_id`, `service`, `date`, `time`, `reason`, `status`),<br>`appointment_logs`,<br>`services`,<br>`doctors`,<br>`holidays` | [`actions/book-appointment.php`](file:///C:/xampp/htdocs/rhu-appointment-system/actions/book-appointment.php),<br>[`actions/api/get-booked-dates.php`](file:///C:/xampp/htdocs/rhu-appointment-system/actions/api/get-booked-dates.php) | [`views/user/book-appointment.php`](file:///C:/xampp/htdocs/rhu-appointment-system/views/user/book-appointment.php),<br>redirects to [`views/user/my-appointments.php`](file:///C:/xampp/htdocs/rhu-appointment-system/views/user/my-appointments.php) | [`includes/header.php`](file:///C:/xampp/htdocs/rhu-appointment-system/includes/header.php),<br>[`includes/user-sidebar.php`](file:///C:/xampp/htdocs/rhu-appointment-system/includes/user-sidebar.php),<br>[`includes/footer.php`](file:///C:/xampp/htdocs/rhu-appointment-system/includes/footer.php) | `requireLogin('patient')`,<br>`getPatientSession()`,<br>`verifyCsrf()`,<br>`flashMessage()` | `RHUCalendar` widget in `app.js`, date picker, slot selection buttons, doctor change slot reloader, `style.css` | `sendBookingConfirmation($email, $patientName, $apptData)` | Double-booking prevention query, same-day elapsed time slot blocking (`time <= date('H:i')`), future date slot availability, doctor-specific booking query, `APT-XXX` ID sequencing. |
| **M6: Appointment Self-Cancellation (Patient)** | `appointments` (`status`, `admin_note`),<br>`appointment_logs` | [`actions/cancel-appointment.php`](file:///C:/xampp/htdocs/rhu-appointment-system/actions/cancel-appointment.php) | [`views/user/my-appointments.php`](file:///C:/xampp/htdocs/rhu-appointment-system/views/user/my-appointments.php) | [`includes/user-sidebar.php`](file:///C:/xampp/htdocs/rhu-appointment-system/includes/user-sidebar.php),<br>[`includes/footer.php`](file:///C:/xampp/htdocs/rhu-appointment-system/includes/footer.php) | `requireLogin('patient')`,<br>`getPatientSession()`,<br>`verifyCsrf()` | Cancel modal trigger (`openModal`), confirmation alert | `sendCancellationEmail($email, $patientName, $apptData)` | Allowed status transitions (`Pending` / `Approved` -> `Cancelled`), audit log entry with `patient` username, calendar slot release. |
| **M7: Appointment Management (Admin)** | `appointments` (`status`, `date`, `time`, `admin_note`),<br>`appointment_logs` (`appointment_id`, `changed_by`, `old_status`, `new_status`, `note`),<br>`consultation_records` (`appointment_id`, `patient_id`, `doctor_id`, `patient_name`, `dob`, `age`, `gender`, `address`, `folder_no`, `tin_no`, `mode_of_transaction`, `consultation_date`, `consultation_time`, `nature_of_visit`, `chief_complaints`, `height`, `weight`, `bp`, `rr`, `pr`, `temperature`, `history_of_illness`, `past_medical_history`, `pertinent_pe`, `diagnosis`, `treatment`, `lab_findings`),<br>`patients`,<br>`doctors` | [`actions/admin/update-appointment.php`](file:///C:/xampp/htdocs/rhu-appointment-system/actions/admin/update-appointment.php) | [`views/admin/appointments.php`](file:///C:/xampp/htdocs/rhu-appointment-system/views/admin/appointments.php),<br>[`views/admin/dashboard.php`](file:///C:/xampp/htdocs/rhu-appointment-system/views/admin/dashboard.php) | [`includes/admin-sidebar.php`](file:///C:/xampp/htdocs/rhu-appointment-system/includes/admin-sidebar.php),<br>[`includes/footer.php`](file:///C:/xampp/htdocs/rhu-appointment-system/includes/footer.php) | `requireLogin('admin')`,<br>`getAdminSession()`,<br>`verifyCsrf()`,<br>`flashMessage()` | Consultation Modal, Reason Modal, Reschedule Modal, View Sheet Modal, status badges (`statusBadge`) | `sendAppointmentStatusEmail($patientEmail, $patientName, $appt, $newStatus, $note)` | Status workflow state machine (`Pending` -> `Approved`/`Rejected`, `Approved` -> `Completed`/`Cancelled`), consultation record required before completion, note validation on Reject/Cancel. |
| **M8: Calendar & Schedule (Admin)** | `appointments`,<br>`doctors`,<br>`services`,<br>`holidays` | Read-only PDO queries in view | [`views/admin/calendar.php`](file:///C:/xampp/htdocs/rhu-appointment-system/views/admin/calendar.php) | [`includes/admin-sidebar.php`](file:///C:/xampp/htdocs/rhu-appointment-system/includes/admin-sidebar.php) | `requireLogin('admin')`,<br>`db()` | Interactive calendar rendering, doctor dropdown filter, slot modal preview | None | Synchronization with booked appointment dates/times, doctor active availability status. |
| **M9: Doctor Schedule & Management** | `doctors` (`id`, `name`, `specialty`, `schedule`, `available`, `created_at`),<br>`appointments` (`doctor_id`) | [`actions/admin/save-doctor.php`](file:///C:/xampp/htdocs/rhu-appointment-system/actions/admin/save-doctor.php),<br>[`actions/admin/delete-doctor.php`](file:///C:/xampp/htdocs/rhu-appointment-system/actions/admin/delete-doctor.php),<br>[`actions/admin/toggle-doctor.php`](file:///C:/xampp/htdocs/rhu-appointment-system/actions/admin/toggle-doctor.php) | [`views/admin/doctors.php`](file:///C:/xampp/htdocs/rhu-appointment-system/views/admin/doctors.php) | [`includes/admin-sidebar.php`](file:///C:/xampp/htdocs/rhu-appointment-system/includes/admin-sidebar.php) | `requireLogin('admin')`,<br>`verifyCsrf()`,<br>`flashMessage()` | Add/Edit Doctor modal, schedule checkboxes, toggle switch | None | Booking dropdown in [`book-appointment.php`](file:///C:/xampp/htdocs/rhu-appointment-system/views/user/book-appointment.php), Foreign key `SET NULL` on doctor deletion in `appointments.doctor_id`. |
| **M10: Patient Profile & Medical History** | `patients`,<br>`users`,<br>`appointments` (filtered strictly by `Completed`),<br>`consultation_records`,<br>`doctors` | [`actions/user/update-profile.php`](file:///C:/xampp/htdocs/rhu-appointment-system/actions/user/update-profile.php),<br>[`actions/user/change-password.php`](file:///C:/xampp/htdocs/rhu-appointment-system/actions/user/change-password.php) | [`views/user/profile.php`](file:///C:/xampp/htdocs/rhu-appointment-system/views/user/profile.php),<br>[`views/user/medical-history.php`](file:///C:/xampp/htdocs/rhu-appointment-system/views/user/medical-history.php),<br>[`views/user/print-medical-history.php`](file:///C:/xampp/htdocs/rhu-appointment-system/views/user/print-medical-history.php),<br>[`views/admin/patients.php`](file:///C:/xampp/htdocs/rhu-appointment-system/views/admin/patients.php) | [`includes/user-sidebar.php`](file:///C:/xampp/htdocs/rhu-appointment-system/includes/user-sidebar.php),<br>[`includes/admin-sidebar.php`](file:///C:/xampp/htdocs/rhu-appointment-system/includes/admin-sidebar.php) | `requireLogin()`,<br>`getPatientSession()`,<br>`verifyCsrf()` | Profile form validation, history consultation sheet modal, printable Individual Treatment Record (ITR / Sample.pdf) | None | Updating `full_name` requires syncing `$_SESSION['patient']['full_name']`; Medical History strictly displays `Completed` consultations with full clinical details and printable official ITR. |
| **M11: User & Account Control (Admin)** | `users` (`status`, `failed_attempts`, `locked_until`),<br>`patients`,<br>`admin_users` | [`actions/admin/toggle-user-status.php`](file:///C:/xampp/htdocs/rhu-appointment-system/actions/admin/toggle-user-status.php),<br>[`actions/admin/update-profile.php`](file:///C:/xampp/htdocs/rhu-appointment-system/actions/admin/update-profile.php),<br>[`actions/admin/change-password.php`](file:///C:/xampp/htdocs/rhu-appointment-system/actions/admin/change-password.php) | [`views/admin/users.php`](file:///C:/xampp/htdocs/rhu-appointment-system/views/admin/users.php),<br>[`views/admin/profile.php`](file:///C:/xampp/htdocs/rhu-appointment-system/views/admin/profile.php) | [`includes/admin-sidebar.php`](file:///C:/xampp/htdocs/rhu-appointment-system/includes/admin-sidebar.php) | `requireLogin('admin')`,<br>`verifyCsrf()`,<br>`flashMessage()` | User table search/filter, status toggle button, admin profile edit form | None | Inactive patient accounts are blocked at login; Resetting lock clears `failed_attempts=0, locked_until=NULL`. Admin profile changes update `$_SESSION['admin']`. |
| **M12: Reports & Analytics (Admin)** | `appointments`,<br>`patients`,<br>`doctors`,<br>`services` | [`actions/admin/export-report.php`](file:///C:/xampp/htdocs/rhu-appointment-system/actions/admin/export-report.php) | [`views/admin/reports.php`](file:///C:/xampp/htdocs/rhu-appointment-system/views/admin/reports.php),<br>[`views/admin/dashboard.php`](file:///C:/xampp/htdocs/rhu-appointment-system/views/admin/dashboard.php) | [`includes/admin-sidebar.php`](file:///C:/xampp/htdocs/rhu-appointment-system/includes/admin-sidebar.php) | `requireLogin('admin')`,<br>`db()` | Chart.js graphs, date range pickers, CSV download trigger | None | SQL aggregate queries (`COUNT`, `GROUP BY status`, `DATE(date)`), CSV export header formatting. |
| **M13: Public Schedule & Vacant Slots (Guest)** | `doctors`,<br>`services`,<br>`holidays`,<br>`appointments` (read-only) | [`actions/api/get-booked-dates.php`](file:///C:/xampp/htdocs/rhu-appointment-system/actions/api/get-booked-dates.php) | [`views/schedule-viewer.php`](file:///C:/xampp/htdocs/rhu-appointment-system/views/schedule-viewer.php),<br>[`schedule.php`](file:///C:/xampp/htdocs/rhu-appointment-system/schedule.php),<br>linked from [`index.php`](file:///C:/xampp/htdocs/rhu-appointment-system/index.php) & [`signup.php`](file:///C:/xampp/htdocs/rhu-appointment-system/views/user/signup.php) | [`includes/header.php`](file:///C:/xampp/htdocs/rhu-appointment-system/includes/header.php),<br>[`includes/footer.php`](file:///C:/xampp/htdocs/rhu-appointment-system/includes/footer.php) | `db()`,<br>`isLoggedIn()`,<br>`getPatientSession()`,<br>`getAdminSession()` | Real-time vacancy checker, dynamic slot cards, prompt modal, `style.css` | None | Public view (NO `requireLogin`). Integrates with `views/user/book-appointment.php` via URL query params (`date`, `time`, `doctor_id`) and safe login redirect. |

---

## 🔄 4. The Synchronization Engine (Change Propagation Rules)

Follow these explicit rules whenever touching any layer of the application:

### Rule 1: Modifying Database Schema, Columns, or Enums
When you alter a table (e.g. adding a column, changing an ENUM value):
1. **Migration / SQL Script:** Add change to [`database/schema.sql`](file:///C:/xampp/htdocs/rhu-appointment-system/database/schema.sql) and create a script in `database/migrations/`.
2. **Backend Insertion & Updates:** Check all `INSERT` and `UPDATE` statements in `actions/` touching that table. Ensure new fields are sanitized, validated, and included.
3. **Frontend Forms:** Add matching inputs to the relevant view files (`views/**/*.php`) with correct `name` and validation attributes.
4. **Data Presentation:** Update tables, cards, detail modals, and history views displaying the record.
5. **API & Reporting:** Check [`actions/api/get-booked-dates.php`](file:///C:/xampp/htdocs/rhu-appointment-system/actions/api/get-booked-dates.php) and [`actions/admin/export-report.php`](file:///C:/xampp/htdocs/rhu-appointment-system/actions/admin/export-report.php) to ensure CSV exports or JSON responses match the new structure.

### Rule 2: Modifying an Action Controller (`actions/**/*.php`)
When you edit an action script:
1. **HTTP Method & CSRF:** Enforce `$_SERVER['REQUEST_METHOD'] === 'POST'` and call `verifyCsrf()`.
2. **Auth Guard:** Ensure `requireLogin('patient')` or `requireLogin('admin')` is at the very top.
3. **Form Target:** Verify the form in the matching view has `<form action="<?= $base ?>/actions/..." method="POST">` and contains `<?= csrfField() ?>`.
4. **Flash Message Handlers:** Ensure success/error flash keys set in `flashMessage('key', 'msg', 'type')` are retrieved and rendered via `getFlash('key')` in the destination view.
5. **Audit Trail:** If this modifies an appointment, an entry **must** be written to `appointment_logs`.
6. **Notifications:** If a status changes, call the corresponding mailer function in [`config/mailer.php`](file:///C:/xampp/htdocs/rhu-appointment-system/config/mailer.php).

### Rule 3: Modifying Session Keys or User Profile Data
When you change session data or allow users/admins to update their info:
1. **Session Schema:** Verify against Section 2.2 of this document.
2. **Live Session Refresh:** When a user or admin updates their name/phone in the database, immediately update `$_SESSION['patient']` or `$_SESSION['admin']` so layout headers and sidebars reflect changes without forcing a re-login.
3. **Sidebars:** Verify [`includes/user-sidebar.php`](file:///C:/xampp/htdocs/rhu-appointment-system/includes/user-sidebar.php) and [`includes/admin-sidebar.php`](file:///C:/xampp/htdocs/rhu-appointment-system/includes/admin-sidebar.php) continue to read the correct session keys.

### Rule 4: Modifying Appointment Statuses or Workflow Rules
The appointment status flow is strictly defined:
```
                 ┌─────────────┐
                 │   Pending   │
                 └──────┬──────┘
            ┌───────────┴───────────┐
            ▼                       ▼
     ┌─────────────┐         ┌─────────────┐
     │  Approved   │         │  Rejected   │ (Requires admin note)
     └──────┬──────┘         └─────────────┘
      ┌─────┴─────┐
      ▼           ▼
┌───────────┐ ┌───────────┐
│ Completed │ │ Cancelled │ (Requires admin note OR patient self-cancellation)
└───────────┘ └───────────┘
```
If you alter or add a status:
1. Update `ENUM` in `database/schema.sql` (`appointments.status`).
2. Update allowed transition array `$transitions` in [`actions/admin/update-appointment.php`](file:///C:/xampp/htdocs/rhu-appointment-system/actions/admin/update-appointment.php).
3. Update double-booking exclusion query in [`actions/book-appointment.php`](file:///C:/xampp/htdocs/rhu-appointment-system/actions/book-appointment.php) (`status NOT IN ('Cancelled','Rejected')`).
4. Update badge helper `statusBadge()` in [`assets/js/app.js`](file:///C:/xampp/htdocs/rhu-appointment-system/assets/js/app.js) and corresponding CSS classes in [`assets/css/style.css`](file:///C:/xampp/htdocs/rhu-appointment-system/assets/css/style.css).
5. Update email dispatcher `sendAppointmentStatusEmail()` in [`config/mailer.php`](file:///C:/xampp/htdocs/rhu-appointment-system/config/mailer.php) to handle the new status email template.
6. Update filter tabs in [`views/admin/appointments.php`](file:///C:/xampp/htdocs/rhu-appointment-system/views/admin/appointments.php) and [`views/user/my-appointments.php`](file:///C:/xampp/htdocs/rhu-appointment-system/views/user/my-appointments.php).

### Rule 5: Modifying Email Notifications
When editing email templates or dispatch functions:
1. Keep the layout wrapped in `emailLayout($title, $body)`.
2. Wrap email dispatch in `try { ... } catch (Exception $e) { error_log(...); }` so an SMTP failure never breaks the user flow or aborts a database transaction.
3. Test using [`test-email.php`](file:///C:/xampp/htdocs/rhu-appointment-system/test-email.php) to verify SMTP transport and layout aesthetics.

---

## 🗂️ 5. Global Symbol & Function Registry

Reference this table whenever calling, renaming, or refactoring shared functions:

| Function Signature | File Location | Purpose | Known Callers & Dependent Files |
|---|---|---|---|
| `db(): PDO` | [`config/database.php`](file:///C:/xampp/htdocs/rhu-appointment-system/config/database.php) | Singleton PDO connection with UTF8mb4. | Used across ALL `actions/*.php`, `views/**/*.php`, and `config/auth.php`. |
| `csrfToken(): string` | [`config/auth.php`](file:///C:/xampp/htdocs/rhu-appointment-system/config/auth.php) | Generates or retrieves existing CSRF token. | `csrfField()`, AJAX headers in `assets/js/app.js`. |
| `csrfField(): string` | [`config/auth.php`](file:///C:/xampp/htdocs/rhu-appointment-system/config/auth.php) | Outputs hidden input tag containing CSRF token. | Every `<form>` in `views/**/*.php` and `index.php`. |
| `verifyCsrf(): void` | [`config/auth.php`](file:///C:/xampp/htdocs/rhu-appointment-system/config/auth.php) | Validates `$_POST['csrf_token']` with `hash_equals`. Dies with HTTP 403 on failure. | Called at top of EVERY POST handler in `actions/**/*.php`. |
| `requireLogin(string $role): void` | [`config/auth.php`](file:///C:/xampp/htdocs/rhu-appointment-system/config/auth.php) | Checks session role ('patient' or 'admin'). Sends no-cache headers. Redirects to login on failure. | Top of all protected pages in `views/user/*.php`, `views/admin/*.php`, and `actions/*.php`. |
| `getPatientSession(): ?array` | [`config/auth.php`](file:///C:/xampp/htdocs/rhu-appointment-system/config/auth.php) | Returns patient session array or null. | `actions/book-appointment.php`, `actions/cancel-appointment.php`, `views/user/*.php`. |
| `getAdminSession(): ?array` | [`config/auth.php`](file:///C:/xampp/htdocs/rhu-appointment-system/config/auth.php) | Returns admin session array or null. | `actions/admin/*.php`, `views/admin/*.php`. |
| `setPatientSession(array $patient, array $user): void` | [`config/auth.php`](file:///C:/xampp/htdocs/rhu-appointment-system/config/auth.php) | Populates `$_SESSION['patient']` with normalized keys. | `actions/login.php`, `actions/user/update-profile.php`. |
| `setAdminSession(array $admin): void` | [`config/auth.php`](file:///C:/xampp/htdocs/rhu-appointment-system/config/auth.php) | Populates `$_SESSION['admin']` with normalized keys. | `actions/admin/login.php`, `actions/admin/update-profile.php`. |
| `flashMessage(string $key, string $message, string $type): void` | [`config/auth.php`](file:///C:/xampp/htdocs/rhu-appointment-system/config/auth.php) | Sets temporary session flash alert. | Called in all `actions/` before `redirectTo()`. |
| `getFlash(string $key): ?array` | [`config/auth.php`](file:///C:/xampp/htdocs/rhu-appointment-system/config/auth.php) | Retrieves and automatically unsets flash alert. | Rendered in `views/**/*.php` and `index.php`. |
| `redirectTo(string $path): void` | [`config/auth.php`](file:///C:/xampp/htdocs/rhu-appointment-system/config/auth.php) | Prepend `BASE_URL` if needed and triggers `header('Location: ...')` + `exit`. | All `actions/**/*.php`. |
| `isLoggedIn(string $role): bool` | [`config/auth.php`](file:///C:/xampp/htdocs/rhu-appointment-system/config/auth.php) | Returns boolean if role is authenticated. | `index.php`, `views/admin/login.php` (auto-redirect if already logged in). |
| `sendWelcomeEmail($to, $name, $username, $patientNo)` | [`config/mailer.php`](file:///C:/xampp/htdocs/rhu-appointment-system/config/mailer.php) | Sends branded account creation confirmation. | `actions/register.php`. |
| `sendBookingConfirmation($to, $name, array $appt)` | [`config/mailer.php`](file:///C:/xampp/htdocs/rhu-appointment-system/config/mailer.php) | Sends pending booking receipt with details. | `actions/book-appointment.php`. |
| `sendAppointmentStatusEmail($to, $name, array $appt, $newStatus, $note)` | [`config/mailer.php`](file:///C:/xampp/htdocs/rhu-appointment-system/config/mailer.php) | Sends status update notice (Approved, Rejected, Completed, Cancelled). | `actions/admin/update-appointment.php`. |
| `sendPasswordResetOtpEmail($to, $name, $code)` | [`config/mailer.php`](file:///C:/xampp/htdocs/rhu-appointment-system/config/mailer.php) | Dispatches 6-digit OTP code (10m expiry). | `actions/forgot-password/request.php`. |
| `sendCancellationEmail($to, $name, array $appt)` | [`config/mailer.php`](file:///C:/xampp/htdocs/rhu-appointment-system/config/mailer.php) | Confirms patient self-cancellation. | `actions/cancel-appointment.php`. |
| `showToast(msg, type, title)` | [`assets/js/app.js`](file:///C:/xampp/htdocs/rhu-appointment-system/assets/js/app.js) | Displays animated floating UI toast notice. | Client-side feedback across user and admin portals. |
| `openModal(id)` / `closeModal(id)` | [`assets/js/app.js`](file:///C:/xampp/htdocs/rhu-appointment-system/assets/js/app.js) | Manages modal overlay visibility & body scroll locks. | Status change modals, Add Doctor modals, cancel dialogs. |
| `RHUCalendar` (Class) | [`assets/js/app.js`](file:///C:/xampp/htdocs/rhu-appointment-system/assets/js/app.js) | Interactive grid calendar for slot booking & schedules. | `views/user/book-appointment.php`, `views/admin/calendar.php`. |
| `statusBadge(status)` | [`assets/js/app.js`](file:///C:/xampp/htdocs/rhu-appointment-system/assets/js/app.js) | Returns HTML badge string for given status string. | Dynamic JS tables and AJAX appointment updates. |
| `showLoader(subtext)` / `hideLoader()` | [`assets/js/app.js`](file:///C:/xampp/htdocs/rhu-appointment-system/assets/js/app.js) | Controls global RHU branded loading screen overlay. | Triggers on page load and standard form submissions across all pages. |

---

## 📋 6. Refactoring & Editing Verification Checklist

Before considering any refactoring or feature modification complete, walk through this checklist:

- [ ] **1. Schema & Data Check:** Did you change any table structure, column name, or enum? If yes, are all `SELECT`, `INSERT`, and `UPDATE` queries across `actions/` and `views/` updated?
- [ ] **2. Auth & CSRF Check:** If creating or updating a form, does it include `<?= csrfField() ?>` and does the receiving script call `verifyCsrf()`?
- [ ] **3. Session Consistency:** If modifying user profile data, did you synchronize both the database table AND the active `$_SESSION` array?
- [ ] **4. Action-View Redirect Loop:** Does the action script redirect to an existing view, and does the view have a matching `getFlash('...')` call to display errors/success?
- [ ] **5. Audit Logging:** If an appointment was modified, did you insert a record into `appointment_logs`?
- [ ] **6. Email Dispatch:** If this action triggers a user notification, did you call the corresponding function in `mailer.php` inside a `try/catch` block?
- [ ] **7. Client Asset Sync:** If adding or changing button classes, modal IDs, or status names, did you update `assets/js/app.js` and `assets/css/style.css`?
- [ ] **8. Memory File Update:** Did you add new files, functions, or dependencies to this `SYSTEM_MEMORY.md`?

---

> 💡 **Tip for AI Agents:** When asked to edit or refactor any feature in this repository, always state:  
> *"I have verified the affected dependencies in `SYSTEM_MEMORY.md` and will synchronize all linked actions, views, helpers, and assets."*
