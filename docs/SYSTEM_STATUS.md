# RHU Rizal Appointment System — System Status & Developer Guide

> **Last updated:** June 2, 2026 | **Overall progress:** ~45% complete

---

## Table of Contents

1. [Project Overview](#1-project-overview)
2. [How to Set Up Locally](#2-how-to-set-up-locally)
3. [Test Credentials](#3-test-credentials)
4. [Architecture & File Structure](#4-architecture--file-structure)
5. [Database Schema Reference](#5-database-schema-reference)
6. [Feature Status by Role](#6-feature-status-by-role)
7. [Bugs Found & Fixed](#7-bugs-found--fixed)
8. [Remaining Work / Roadmap](#8-remaining-work--roadmap)
9. [Appointment Workflow](#9-appointment-workflow)
10. [Security Notes](#10-security-notes)

---

## 1. Project Overview

**RHU Rizal Online Medical Appointment System** is a PHP + MySQL web app for the Rural Health Unit of Rizal, Cagayan Valley. It lets patients register, book appointments online, and track their visit history. Admins manage doctors, schedules, and appointment approvals.

| Item | Value |
|------|-------|
| Stack | PHP 8+, MySQL (PDO), Vanilla JS, Custom CSS |
| Server | XAMPP (Apache + MySQL) |
| Database | `rhu_rizal` |
| Base URL | `http://localhost/rhu-appointment-system` |
| Timezone | Asia/Manila |

---

## 2. How to Set Up Locally

### Prerequisites
- XAMPP installed and running (Apache + MySQL services started)
- PHP 8.0 or higher

### Steps

1. **Place files** in `C:\xampp\htdocs\rhu-appointment-system\`

2. **Create the database** — open `http://localhost/phpmyadmin` and run:
   ```sql
   source C:/xampp/htdocs/rhu-appointment-system/database/schema.sql
   source C:/xampp/htdocs/rhu-appointment-system/database/seed.sql
   ```
   Or via terminal:
   ```bash
   mysql -u root rhu_rizal < database/schema.sql
   mysql -u root rhu_rizal < database/seed.sql
   ```

3. **Check config** — open `config/config.php` and verify:
   ```php
   define('DB_HOST',     'localhost');
   define('DB_NAME',     'rhu_rizal');
   define('DB_USER',     'root');
   define('DB_PASSWORD', '');          // empty = XAMPP default
   ```

4. **Open in browser:**
   - Patient login: `http://localhost/rhu-appointment-system/`
   - Admin login:   `http://localhost/rhu-appointment-system/views/admin/login.php`

---

## 3. Test Credentials

### Patient Accounts

| Username | Password | Status |
|----------|----------|--------|
| `juandc` | `patient123` | Active |
| `mcsantos` | `patient123` | Active |
| `lfernandez` | `patient123` | Active |
| `rmangubat` | `patient123` | **Inactive** |

### Admin Account

| Username | Password | Role |
|----------|----------|------|
| `admin` | `admin123` | System Administrator |

---

## 4. Architecture & File Structure

```
rhu-appointment-system/
├── config/
│   ├── config.php          # App constants, DB credentials, BASE_URL
│   ├── database.php        # PDO singleton: db() function
│   └── auth.php            # Session helpers, CSRF, requireLogin()
│
├── database/
│   ├── schema.sql          # Table definitions (run first)
│   └── seed.sql            # Demo data (run after schema)
│
├── actions/                # POST handlers — never accessed directly via GET
│   ├── login.php           # Patient login
│   ├── register.php        # Patient registration
│   ├── logout.php          # Session cleanup
│   ├── book-appointment.php
│   ├── cancel-appointment.php
│   ├── user/
│   │   ├── update-profile.php
│   │   └── change-password.php
│   ├── admin/
│   │   ├── login.php
│   │   ├── update-appointment.php  # Approve/reject/complete
│   │   ├── save-doctor.php
│   │   ├── delete-doctor.php
│   │   ├── toggle-doctor.php
│   │   ├── toggle-user-status.php
│   │   ├── update-profile.php
│   │   ├── change-password.php
│   │   └── export-report.php
│   └── api/
│       └── get-booked-dates.php    # AJAX: available time slots
│
├── views/
│   ├── user/               # Patient-facing pages
│   │   ├── signup.php
│   │   ├── dashboard.php
│   │   ├── book-appointment.php
│   │   ├── my-appointments.php
│   │   ├── medical-history.php
│   │   └── profile.php
│   └── admin/              # Admin-facing pages
│       ├── login.php
│       ├── dashboard.php
│       ├── appointments.php
│       ├── doctors.php
│       ├── patients.php
│       ├── users.php
│       ├── calendar.php
│       ├── reports.php
│       └── profile.php
│
├── includes/
│   ├── header.php          # HTML <head>, CSS links
│   ├── footer.php          # Toast container, JS scripts
│   ├── user-sidebar.php    # Patient navigation sidebar
│   └── admin-sidebar.php   # Admin navigation sidebar
│
├── assets/
│   ├── css/style.css       # Custom CSS (no framework)
│   └── js/app.js           # Modals, toasts, formatters
│
├── index.php               # Patient login entry point
└── docs/                   # This documentation folder
```

### Key Conventions

- **`requireLogin('patient')`** / **`requireLogin('admin')`** — call at the top of every protected page. Redirects to login if no session.
- **`getPatientSession()`** returns `$_SESSION['patient']` which is a flat array with keys: `id, patient_no, full_name, username, status`.
- **`getAdminSession()`** returns `$_SESSION['admin']` with keys: `id, full_name, username, email, phone, role`.
- All form POSTs include a CSRF token via `<?= csrfField() ?>` and are verified with `verifyCsrf()`.
- Flash messages: `flashMessage($key, $text, $type)` → `getFlash($key)` (one-shot, cleared on read).

---

## 5. Database Schema Reference

### `users` — Patient login accounts
| Column | Type | Notes |
|--------|------|-------|
| id | INT PK | Auto-increment |
| username | VARCHAR(50) UNIQUE | Login name |
| password | VARCHAR(255) | bcrypt hash |
| role | ENUM('patient') | Extend later for doctor/staff |
| status | ENUM('Active','Inactive') | Inactive = cannot log in |
| created_at | DATETIME | |

### `patients` — Patient profile (1-to-1 with users)
| Column | Type | Notes |
|--------|------|-------|
| id | INT PK | |
| user_id | INT FK→users | Cascade delete |
| patient_no | VARCHAR(10) UNIQUE | P-001, P-002… |
| full_name | VARCHAR(100) | Combined first+last |
| email | VARCHAR(100) | |
| phone | VARCHAR(20) | |
| address | TEXT | |
| birthdate | DATE | |
| gender | ENUM | Male/Female/Other |
| blood_type | VARCHAR(5) | |

### `doctors`
| Column | Type | Notes |
|--------|------|-------|
| id | INT PK | |
| name | VARCHAR(100) | e.g. "Dr. Maria Santos" |
| specialty | VARCHAR(100) | |
| schedule | VARCHAR(100) | e.g. "Mon-Wed-Fri" |
| available | TINYINT(1) | 1=available, 0=unavailable |

### `appointments`
| Column | Type | Notes |
|--------|------|-------|
| id | INT PK | |
| appt_no | VARCHAR(10) UNIQUE | APT-001, APT-002… |
| patient_id | INT FK→patients | |
| doctor_id | INT FK→doctors (nullable) | |
| service | VARCHAR(100) | Service name (not FK) |
| date | DATE | |
| time | TIME | |
| reason | TEXT | |
| status | ENUM | Pending/Approved/Rejected/Completed/Cancelled |

### `appointment_logs` — Audit trail
Every status change creates a row here with `changed_by` (username), `old_status`, `new_status`, and a note.

### `admin_users` — Admin login (separate from `users`)
| Column | Type |
|--------|------|
| id, username, password, full_name, email, phone, role, status | — |

### `services` — Seeded with 10 services
General Consultation, Prenatal Care, Pediatrics, Dental Services, Family Planning, Immunization, Laboratory Services, TB-DOTS Program, Nutrition Counseling, Eye Care.

### `holidays` — Closed dates
Dates on which appointment booking should be blocked.

---

## 6. Feature Status by Role

### Patient (✅ Fully Implemented)

| Feature | Entry Point | Status |
|---------|-------------|--------|
| Register (3-step) | `/views/user/signup.php` → `/actions/register.php` | ✅ Complete |
| Login / Logout | `/index.php` → `/actions/login.php` | ✅ Complete |
| Dashboard | `/views/user/dashboard.php` | ✅ Complete |
| Book Appointment | `/views/user/book-appointment.php` → `/actions/book-appointment.php` | ✅ Complete |
| My Appointments | `/views/user/my-appointments.php` | ✅ Complete |
| Cancel Appointment | POST `/actions/cancel-appointment.php` | ✅ Complete |
| Medical History | `/views/user/medical-history.php` | ⚠️ Placeholder (no clinical notes) |
| Edit Profile | `/views/user/profile.php` → `/actions/user/update-profile.php` | ✅ Complete |
| Change Password | POST `/actions/user/change-password.php` | ✅ Complete |

### Admin (✅ Mostly Implemented)

| Feature | Entry Point | Status |
|---------|-------------|--------|
| Login / Logout | `/views/admin/login.php` → `/actions/admin/login.php` | ✅ Complete |
| Dashboard + Charts | `/views/admin/dashboard.php` | ✅ Complete |
| Manage Appointments | `/views/admin/appointments.php` | ✅ Complete |
| Approve/Reject/Complete | POST `/actions/admin/update-appointment.php` | ✅ Complete |
| Calendar View | `/views/admin/calendar.php` | ✅ Complete |
| Doctor Schedule | `/views/admin/doctors.php` | ✅ Complete |
| Add/Edit/Delete Doctor | POST `/actions/admin/save-doctor.php` | ✅ Complete |
| Patient Records | `/views/admin/patients.php` | ✅ Complete |
| User Management | `/views/admin/users.php` | ✅ Complete |
| Reports / Export | `/views/admin/reports.php` | ✅ Complete |
| Admin Profile | `/views/admin/profile.php` | ✅ Complete (fixed) |
| Change Admin Password | POST `/actions/admin/change-password.php` | ✅ Complete |

### Doctor Portal — ❌ Not Built
No doctor login, dashboard, or pages exist yet.

### Staff Portal — ❌ Not Built
No staff role in database or PHP code.

---

## 7. Bugs Found & Fixed

### Bug 1 — `actions/login.php`: Wrong column names in SQL query *(Critical)*
**Problem:** The SELECT query referenced `p.first_name`, `p.last_name`, `p.date_of_birth` — columns that don't exist in the `patients` table (schema uses `full_name` and `birthdate`). Patient login would throw a PDO exception on every attempt.

**Also:** The status check used `!== 'active'` (lowercase) but the DB stores `'Active'` (capitalized).

**Fixed in:** `actions/login.php`

---

### Bug 2 — `views/user/dashboard.php` & `views/user/book-appointment.php`: Wrong session access *(Critical)*
**Problem:** Both pages did `$patient = $session['patient']` — but `getPatientSession()` returns the patient data *directly* (i.e., `$session` IS the patient array, not a wrapper). Accessing `$session['patient']` always returns `null`.

**Fixed in:** `views/user/dashboard.php`, `views/user/book-appointment.php`

---

### Bug 3 — `includes/user-sidebar.php`: Wrong session key *(High)*
**Problem:** Tried to read `$_SESSION['user']['full_name']` — the key `user` is never stored in the session. Only `$_SESSION['patient']` is set after login.

**Fixed in:** `includes/user-sidebar.php`

---

### Bug 4 — `actions/cancel-appointment.php`: Wrong session key access *(High)*
**Problem:** Used `$session['patient']['id']` and `$session['user']['username']` — both nested incorrectly. Session keys are flat: `$session['id']` and `$session['username']`.

**Fixed in:** `actions/cancel-appointment.php`

---

### Bug 5 — `views/admin/profile.php`: Empty file *(Medium)*
**Problem:** The file was 0 bytes. Clicking "Admin Profile" in the sidebar showed a blank page.

**Fixed:** Implemented the full admin profile page.

---

## 8. Remaining Work / Roadmap

### High Priority (system incomplete without these)

| Task | Details |
|------|---------|
| **Forgot Password** | UI modal exists on login page but no backend token/email flow. Needs: password_resets table, token generation, email sending (PHPMailer or mail()), and a reset form. |
| **Doctor availability enforcement** | Booking page should block dates when the chosen doctor's schedule doesn't include that day. Currently only checks double-booking, not schedule days. |
| **Medical History — clinical data** | Currently shows completed appointments only. No diagnosis, prescription, or findings stored. Needs: a `medical_records` table and admin form to add clinical notes. |

### Medium Priority (improves usability)

| Task | Details |
|------|---------|
| **Patient appointment reschedule** | Backend partially supports it (`update-appointment.php` has date/time update logic) but no patient-facing UI to request a reschedule. |
| **Email notifications** | Send confirmation email on booking, approval, and cancellation. Needs PHPMailer + SMTP config. |
| **Doctor portal** | New role: doctor login, view own appointments for the day/week, mark as completed. Requires: `doctor_users` table (or add role to `users`), session handling, and 3–4 new views. |

### Low Priority (nice to have)

| Task | Details |
|------|---------|
| **SMS notifications** | Integrate SMS gateway (e.g., Semaphore PH) for appointment reminders. |
| **Profile photo upload** | Add file upload for patient/admin profile pictures. |
| **Real-time pending badge** | Poll `/actions/api/get-booked-dates.php` or a new endpoint to show live pending count in admin sidebar. |
| **Staff role** | Clinic staff who can check in patients but not manage the full system. |

---

## 9. Appointment Workflow

```
Patient Books Appointment
         ↓
    [PENDING]  ← awaiting admin action
        |
   ┌────┴────┐
   ↓         ↓
[APPROVED] [REJECTED] ← admin decision
   |
   ├─→ [COMPLETED]  ← admin marks done after visit
   └─→ [CANCELLED]  ← patient or admin cancels
         ↑
  Patient can also cancel [PENDING]
```

**Audit log:** Every status change writes a row to `appointment_logs` with timestamp, actor username, old status, new status, and an optional note.

---

## 10. Security Notes

| Control | Implementation |
|---------|---------------|
| Password hashing | bcrypt via `password_hash()`, cost = 10 |
| CSRF protection | 32-byte random token in session, verified on every POST |
| SQL injection | PDO prepared statements throughout |
| XSS | `htmlspecialchars()` on all output |
| Session hardening | httponly, samesite=Strict; secure=false (set to true on HTTPS) |
| Role enforcement | `requireLogin('patient')` / `requireLogin('admin')` on every protected page |
| Inactive accounts | Login blocked if `users.status = 'Inactive'` |

> **Note:** Set `'secure' => true` in `config/auth.php` session cookie params when deploying to HTTPS.
