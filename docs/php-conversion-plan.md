# 🔄 RHU Rizal — PHP + MySQL Conversion Plan

> **Document Version:** 1.0  
> **Prepared:** May 6, 2026  
> **Scope:** Audit of localStorage dependency + full PHP/MySQL conversion roadmap

---

## 📋 Table of Contents

1. [localStorage Audit](#-part-1--localstorage-audit)
2. [What to Remove](#what-should-be-removed-in-phases)
3. [What to Keep Temporarily](#what-can-remain-temporarily-safe-to-keep)
4. [PHP Conversion Checklist](#-part-2--php-conversion-checklist)
5. [Database Structure](#-part-3--database-structure-overview)

---

## 🔍 PART 1 — localStorage Audit

### Files Using localStorage / DB Helpers

| File                               | What It Uses                                                                                                 |
| ---------------------------------- | ------------------------------------------------------------------------------------------------------------ |
| `data/mockData.js`                 | **Root of everything** — defines `RHU_DATA`, `DB` object, `initMockData()`, all seeding logic                |
| `assets/js/app.js`                 | `DB.getSession()`, `DB.clearSession()`, `RHUCalendar` reads `DB.getBookedDates()` and `RHU_DATA.closedDates` |
| `index.html`                       | Patient login — `DB.getPatients()`, `DB.setSession()` (frontend auth)                                        |
| `views/admin/login.html`           | Admin login — `RHU_DATA.adminUser` (hardcoded credentials), `DB.setSession()`                                |
| `views/user/signup.html`           | `DB.getPatients()`, `DB.savePatients()`, `DB.generateId()`                                                   |
| `views/user/dashboard.html`        | `DB.getSession()`, `DB.getAppointments()`                                                                    |
| `views/user/book-appointment.html` | `DB.getAppointments()`, `DB.saveAppointments()`, `DB.generateId()`, `DB.getBookedDates()`                    |
| `views/user/my-appointments.html`  | `DB.getAppointments()`, `DB.saveAppointments()` (cancel logic)                                               |
| `views/user/medical-history.html`  | `DB.getAppointments()`, `DB.getPatients()`                                                                   |
| `views/user/profile.html`          | `DB.getPatients()`, `DB.savePatients()`, `DB.setSession()`, `DB.clearSession()`                              |
| `views/admin/dashboard.html`       | `DB.getAppointments()`, `DB.getPatients()`, `DB.getDoctors()`                                                |
| `views/admin/appointments.html`    | `DB.getAppointments()`, `DB.saveAppointments()`, `DB.getPatients()`                                          |
| `views/admin/doctors.html`         | `DB.getDoctors()`, `DB.saveDoctors()`                                                                        |
| `views/admin/calendar.html`        | `DB.getAppointments()`, `DB.getBookedDates()`                                                                |
| `views/admin/patients.html`        | `DB.getPatients()`, `DB.getAppointments()`                                                                   |
| `views/admin/users.html`           | `DB.getPatients()`, `DB.savePatients()`                                                                      |
| `views/admin/reports.html`         | `DB.getAppointments()`, `RHU_DATA.services`                                                                  |
| `views/admin/profile.html`         | `DB.getSession()`, `DB.getAppointments()`, direct `localStorage.getItem/setItem("rhu_admin_profile")`        |

---

### What Should Be **Removed** (in phases)

| Item                                                          | Why                                                         |
| ------------------------------------------------------------- | ----------------------------------------------------------- |
| `data/mockData.js` — entire file                              | Replaces DB, seeding, fake credentials — all moves to MySQL |
| `DB` object in `mockData.js`                                  | All `DB.*` calls replaced by PHP API / form submissions     |
| `initMockData()` in `mockData.js`                             | Data will come from MySQL database instead                  |
| `RHU_DATA.adminUser` in `mockData.js`                         | Hardcoded admin credentials — moves to `admin_users` table  |
| `RHU_DATA.samplePatients` / `sampleAppointments`              | Seed data — moves to a SQL seed file                        |
| `checkAuth()` in `app.js` using `DB.getSession()`             | Replaced with PHP `$_SESSION` checks                        |
| `logout()` in `app.js` using `DB.clearSession()`              | Replaced with `logout.php` that destroys PHP session        |
| All `DB.getXxx()` / `DB.saveXxx()` calls in every view        | Replaced by PHP rendering data into the page directly       |
| `RHUCalendar` using `DB.getBookedDates()`                     | Will fetch booked dates from PHP/API instead                |
| `populateDoctors()` using `DB.getDoctors()`                   | Doctors list will come from the database                    |
| `localStorage.getItem("rhu_admin_profile")` in `profile.html` | Admin profile stored in DB                                  |

---

### What Can **Remain Temporarily** (safe to keep)

| Item                                                | Why It's Safe                                                                     |
| --------------------------------------------------- | --------------------------------------------------------------------------------- |
| `showToast()`                                       | Pure UI utility, no storage dependency                                            |
| `openModal()` / `closeModal()` / `closeAllModals()` | Pure UI utility                                                                   |
| `initSidebar()`                                     | Pure UI utility                                                                   |
| `initTabs()`                                        | Pure UI utility                                                                   |
| `statusBadge()`                                     | Pure display helper                                                               |
| `formatDate()` / `formatTime()`                     | Pure formatting helpers                                                           |
| `setSidebarActive()`                                | Pure DOM helper                                                                   |
| `RHU_DATA.services`                                 | Can temporarily remain as a JS list until a services table is ready               |
| `RHU_DATA.closedDates`                              | Can temporarily remain — will later come from a `settings` or `holidays` DB table |
| All HTML layouts, CSS, and visual structure         | **Nothing in the UI changes**                                                     |

---

## 📋 PART 2 — PHP Conversion Checklist

---

### PHASE 1 — Convert HTML Pages to PHP Layout Structure

**Objective:** Replace `.html` files with `.php` files and introduce shared PHP includes so headers, sidebars, and footers are not duplicated.

**Files to create:**

- `includes/header.php`
- `includes/footer.php`
- `includes/user-sidebar.php`
- `includes/admin-sidebar.php`

**Files to rename/convert:**

- All `views/user/*.html` → `views/user/*.php`
- All `views/admin/*.html` → `views/admin/*.php`
- `index.html` → `index.php`

**Database tables involved:** None yet

**What works after this phase:**

- All pages load as PHP files
- Layout includes are shared — edit sidebar once, updates everywhere
- Static UI is identical to the original

---

### PHASE 2 — Database Design

**Objective:** Create the MySQL database and all tables.

**Files to create:**

- `database/schema.sql` — full table definitions
- `database/seed.sql` — sample data (the old `mockData.js` entries converted to SQL `INSERT` statements)

**Database tables involved:** All (see Part 3 below)

**What works after this phase:**

- Database is ready and seeded
- Can be verified directly in phpMyAdmin

---

### PHASE 3 — Core Configuration

**Objective:** Set up the PHP foundation: DB connection, session handling, shared config.

**Files to create:**

- `config/database.php` — PDO MySQL connection
- `config/config.php` — app constants (app name, base URL, timezone)
- `config/auth.php` — `requireLogin($role)` function using `$_SESSION`

**Database tables involved:** None directly

**What works after this phase:**

- PHP can connect to the database
- Session-based auth guard is ready to be used on all pages
- `initMockData()` and `DB.getSession()` can be replaced

---

### PHASE 4 — Authentication Backend

**Objective:** Replace the fake JS login/logout with real PHP session authentication.

**Files to create/update:**

- `actions/login.php` — handles patient login form POST
- `actions/admin-login.php` — handles admin login form POST
- `actions/logout.php` — destroys `$_SESSION` and redirects
- `index.php` — use `actions/login.php`
- `views/admin/login.php` — use `actions/admin-login.php`

**Database tables involved:** `users`, `admin_users`

**What works after this phase:**

- Real login and logout for both patients and admins
- Sessions stored server-side via PHP `$_SESSION`
- All `DB.setSession()`, `DB.clearSession()`, `DB.getSession()`, `checkAuth()` are replaced

---

### PHASE 5 — Patient Registration Backend

**Objective:** Replace the JS signup logic with a PHP form that writes to the database.

**Files to create/update:**

- `actions/register.php` — validates and inserts new patient into `users` + `patients` tables
- `views/user/signup.php` — form POSTs to `actions/register.php`

**Database tables involved:** `users`, `patients`

**What works after this phase:**

- New patients can register and are saved to MySQL
- Username uniqueness and basic validation enforced server-side
- `DB.savePatients()` and `DB.generateId("P")` are replaced

---

### PHASE 6 — Appointment Booking Backend

**Objective:** Replace all appointment JS CRUD with PHP.

**Files to create/update:**

- `actions/book-appointment.php` — inserts new appointment
- `actions/cancel-appointment.php` — updates status to Cancelled
- `views/user/book-appointment.php` — calendar and form, doctors/services from DB
- `views/user/my-appointments.php` — reads appointments from DB
- `views/user/medical-history.php` — reads completed appointments from DB
- `views/user/dashboard.php` — stats and recent appointments from DB
- `actions/api/get-booked-dates.php` — JSON endpoint for the calendar widget

**Database tables involved:** `appointments`, `appointment_logs`

**What works after this phase:**

- Patients can book, view, and cancel appointments (real DB)
- Calendar shows real booked dates
- All patient-side `DB.getAppointments()` / `DB.saveAppointments()` replaced

---

### PHASE 7 — Admin Appointment Management

**Objective:** Give admins the ability to approve, reject, and complete appointments from the database.

**Files to create/update:**

- `actions/admin/update-appointment.php` — approve / reject / complete
- `views/admin/appointments.php` — reads all appointments from DB with filters
- `views/admin/dashboard.php` — counts and charts from DB
- `views/admin/calendar.php` — monthly calendar from DB

**Database tables involved:** `appointments`, `appointment_logs`, `patients`

**What works after this phase:**

- Full admin appointment workflow is live
- All `DB.saveAppointments()` in admin views replaced
- Audit trail written to `appointment_logs`

---

### PHASE 8 — Doctor Management

**Objective:** Manage doctors from the database instead of `localStorage`.

**Files to create/update:**

- `actions/admin/save-doctor.php` — add or edit doctor
- `actions/admin/toggle-doctor.php` — toggle availability
- `actions/admin/delete-doctor.php` — remove doctor
- `views/admin/doctors.php` — reads from `doctors` table

**Database tables involved:** `doctors`

**What works after this phase:**

- Doctors are fully managed from the database
- `DB.getDoctors()` / `DB.saveDoctors()` fully replaced
- `populateDoctors()` fetches from a small PHP endpoint

---

### PHASE 9 — Patient Records & User Account Management

**Objective:** Admin can view and manage patient records and account statuses.

**Files to create/update:**

- `actions/admin/toggle-user-status.php` — activate / deactivate patient
- `views/admin/patients.php` — reads from DB
- `views/admin/users.php` — reads from DB, allows status toggle

**Database tables involved:** `users`, `patients`

**What works after this phase:**

- Admin patient records page reads live data
- Activate/deactivate affects the `users.status` column

---

### PHASE 10 — Reports

**Objective:** Generate server-side reports instead of JS-filtered `localStorage` data.

**Files to create/update:**

- `views/admin/reports.php` — filters and renders from DB
- `actions/admin/export-report.php` _(optional)_ — CSV export

**Database tables involved:** `appointments`, `patients`, `doctors`

**What works after this phase:**

- Reports reflect real database records
- Print view works the same as before

---

### PHASE 11 — Validation & Security Cleanup

**Objective:** Harden the system before any production consideration.

**Files to update:**

- All `actions/*.php` — add prepared statements, input sanitization, CSRF tokens
- `config/auth.php` — ensure no page is accessible without a valid session
- All `.php` views — add `requireLogin()` at top of every protected page
- `config/config.php` — move DB credentials to `.env` or a non-web-accessible location
- Add `.htaccess` to block direct access to `config/`, `actions/`, `database/`

**Database tables involved:** All

**What works after this phase:**

- No plain-text passwords — uses `password_hash()` / `password_verify()`
- No SQL injection exposure — all queries use PDO prepared statements
- All pages are properly access-controlled by role

---

## 🗄️ PART 3 — Database Structure Overview

### Target Folder Structure

```
rhu-appointment-system/
│
├── index.php
├── config/
│   ├── config.php
│   ├── database.php
│   └── auth.php
│
├── includes/
│   ├── header.php
│   ├── footer.php
│   ├── user-sidebar.php
│   └── admin-sidebar.php
│
├── views/
│   ├── user/
│   │   ├── dashboard.php
│   │   ├── signup.php
│   │   ├── book-appointment.php
│   │   ├── my-appointments.php
│   │   ├── medical-history.php
│   │   └── profile.php
│   └── admin/
│       ├── login.php
│       ├── dashboard.php
│       ├── appointments.php
│       ├── doctors.php
│       ├── calendar.php
│       ├── patients.php
│       ├── users.php
│       ├── reports.php
│       └── profile.php
│
├── actions/
│   ├── login.php
│   ├── logout.php
│   ├── register.php
│   ├── book-appointment.php
│   ├── cancel-appointment.php
│   ├── api/
│   │   └── get-booked-dates.php
│   └── admin/
│       ├── admin-login.php
│       ├── update-appointment.php
│       ├── save-doctor.php
│       ├── toggle-doctor.php
│       ├── delete-doctor.php
│       ├── toggle-user-status.php
│       └── export-report.php
│
├── assets/
│   ├── css/
│   │   └── style.css
│   └── js/
│       └── app.js
│
└── database/
    ├── schema.sql
    └── seed.sql
```

---

### MySQL Table Definitions

```sql
-- Users (login credentials for patients)
users
  id            INT AUTO_INCREMENT PRIMARY KEY
  username      VARCHAR(50) UNIQUE NOT NULL
  password      VARCHAR(255) NOT NULL        -- bcrypt hash
  role          ENUM('patient') DEFAULT 'patient'
  status        ENUM('Active','Inactive') DEFAULT 'Active'
  created_at    DATETIME DEFAULT CURRENT_TIMESTAMP

-- Patient profiles (linked 1-to-1 with users)
patients
  id            INT AUTO_INCREMENT PRIMARY KEY
  user_id       INT UNIQUE NOT NULL          -- FK → users.id
  patient_no    VARCHAR(10) UNIQUE           -- e.g. P-001
  full_name     VARCHAR(100) NOT NULL
  email         VARCHAR(100)
  phone         VARCHAR(20)
  address       TEXT
  birthdate     DATE
  gender        ENUM('Male','Female','Other')
  blood_type    VARCHAR(5)
  created_at    DATETIME DEFAULT CURRENT_TIMESTAMP

-- Doctors
doctors
  id            INT AUTO_INCREMENT PRIMARY KEY
  name          VARCHAR(100) NOT NULL
  specialty     VARCHAR(100)
  schedule      VARCHAR(100)                 -- e.g. "Mon-Wed-Fri"
  available     TINYINT(1) DEFAULT 1
  created_at    DATETIME DEFAULT CURRENT_TIMESTAMP

-- Appointments
appointments
  id            INT AUTO_INCREMENT PRIMARY KEY
  appt_no       VARCHAR(10) UNIQUE           -- e.g. APT-001
  patient_id    INT NOT NULL                 -- FK → patients.id
  doctor_id     INT                          -- FK → doctors.id
  service       VARCHAR(100)
  date          DATE NOT NULL
  time          TIME NOT NULL
  reason        TEXT
  status        ENUM('Pending','Approved','Rejected','Completed','Cancelled')
                DEFAULT 'Pending'
  created_at    DATETIME DEFAULT CURRENT_TIMESTAMP

-- Appointment audit log
appointment_logs
  id             INT AUTO_INCREMENT PRIMARY KEY
  appointment_id INT NOT NULL                -- FK → appointments.id
  changed_by     VARCHAR(100)               -- admin username or patient username
  old_status     VARCHAR(20)
  new_status     VARCHAR(20)
  note           TEXT
  changed_at     DATETIME DEFAULT CURRENT_TIMESTAMP

-- Admin users (separate from patient users)
admin_users
  id            INT AUTO_INCREMENT PRIMARY KEY
  username      VARCHAR(50) UNIQUE NOT NULL
  password      VARCHAR(255) NOT NULL        -- bcrypt hash
  full_name     VARCHAR(100)
  email         VARCHAR(100)
  role          VARCHAR(50) DEFAULT 'System Administrator'
  created_at    DATETIME DEFAULT CURRENT_TIMESTAMP
```

> **Note:** A `services` table is optional for now. Services can remain as a PHP constant array and be promoted to a DB table in a later refinement phase.

---

## ✅ Phase Summary

| Phase | Description                       | Key Outcome                                           |
| ----- | --------------------------------- | ----------------------------------------------------- |
| 1     | HTML → PHP layout structure       | Shared includes, `.php` pages                         |
| 2     | Database design                   | `schema.sql` + `seed.sql` ready                       |
| 3     | Core configuration                | PDO connection + session auth guard                   |
| 4     | Authentication backend            | Real login/logout via PHP sessions                    |
| 5     | Patient registration              | Signup writes to MySQL                                |
| 6     | Appointment booking               | Full patient CRUD via PHP                             |
| 7     | Admin appointment management      | Approve/reject/complete from DB                       |
| 8     | Doctor management                 | Doctors managed from DB                               |
| 9     | Patient records & user management | Admin views live data                                 |
| 10    | Reports                           | Server-side filtered reports                          |
| 11    | Validation & security cleanup     | Hashed passwords, prepared statements, access control |

---

_For academic and government prototype use only — RHU Rizal, Municipality of Rizal._
