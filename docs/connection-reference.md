# RHU Rizal — Database & Connection Reference

## Database Configuration

| Setting   | Value          | File                    |
|-----------|----------------|-------------------------|
| Host      | `localhost`    | `config/config.php`     |
| Database  | `rhu_rizal`    | `config/config.php`     |
| User      | `root`         | `config/config.php`     |
| Password  | *(empty)*      | `config/config.php`     |
| Charset   | `utf8mb4`      | `config/config.php`     |

XAMPP default credentials are used. Change `DB_USER` / `DB_PASS` if your MySQL instance is configured differently.

---

## File Structure

```
config/
  config.php       — App constants, DB credentials, BASE_URL
  database.php     — PDO singleton: db() function
  auth.php         — Session start, CSRF helpers, flash messages, redirectTo()
  config/.htaccess — Deny all (blocks direct browser access to config files)

database/
  schema.sql       — Full DDL for all tables
  seed.sql         — Sample/seed data
  fix_passwords.php— One-time bcrypt rehash utility
  database/.htaccess — Deny all

actions/
  register.php     — POST: create patient account (users + patients insert)
  login.php        — POST: patient login
  logout.php       — POST: destroy session
  book-appointment.php — POST: create appointment
  cancel-appointment.php — POST: cancel appointment
  admin/           — Admin-only action scripts
  api/             — JSON endpoints (GET allowed)
    get-booked-dates.php — Returns booked time slots for a given date
  actions/.htaccess — NOTE: must NOT deny all (see Known Issues below)
```

---

## Database Tables

### `users`
Stores patient login credentials.

| Column     | Type                        | Notes                  |
|------------|-----------------------------|------------------------|
| id         | INT PK AUTO_INCREMENT       |                        |
| username   | VARCHAR(50) UNIQUE          |                        |
| password   | VARCHAR(255)                | bcrypt via password_hash |
| role       | ENUM('patient')             |                        |
| status     | ENUM('Active','Inactive')   | Default: Active        |
| created_at | DATETIME                    |                        |

### `patients`
Profile data, 1-to-1 with `users`.

| Column     | Type                            | Notes                  |
|------------|---------------------------------|------------------------|
| id         | INT PK AUTO_INCREMENT           |                        |
| user_id    | INT UNIQUE FK → users.id        | CASCADE DELETE         |
| patient_no | VARCHAR(10) UNIQUE              | Format: P-001, P-002…  |
| full_name  | VARCHAR(100)                    |                        |
| email      | VARCHAR(100)                    |                        |
| phone      | VARCHAR(20)                     | Format: 09XXXXXXXXX    |
| address    | TEXT                            |                        |
| birthdate  | DATE                            |                        |
| gender     | ENUM('Male','Female','Other')   |                        |
| blood_type | VARCHAR(5)                      | Nullable               |
| created_at | DATETIME                        |                        |

### `admin_users`
Separate admin accounts (not linked to `users`).

| Column    | Type          | Notes                      |
|-----------|---------------|----------------------------|
| id        | INT PK        |                            |
| username  | VARCHAR(50)   | UNIQUE                     |
| password  | VARCHAR(255)  | bcrypt                     |
| full_name | VARCHAR(100)  |                            |
| email     | VARCHAR(100)  |                            |
| phone     | VARCHAR(20)   |                            |
| role      | VARCHAR(50)   | Default: System Administrator |
| status    | ENUM          | Active / Inactive          |

### `appointments`

| Column     | Type                                              | Notes           |
|------------|---------------------------------------------------|-----------------|
| id         | INT PK                                            |                 |
| appt_no    | VARCHAR(10) UNIQUE                                | APT-001, APT-002… |
| patient_id | INT FK → patients.id                              | CASCADE DELETE  |
| doctor_id  | INT FK → doctors.id                               | SET NULL on delete |
| service    | VARCHAR(100)                                      |                 |
| date       | DATE                                              |                 |
| time       | TIME                                              |                 |
| reason     | TEXT                                              |                 |
| status     | ENUM(Pending,Approved,Rejected,Completed,Cancelled) | Default: Pending |

### Other Tables
- **`doctors`** — Doctor profiles and schedules
- **`services`** — Medical services offered
- **`appointment_logs`** — Audit trail of status changes
- **`holidays`** — Closed/holiday dates for the booking calendar

---

## Registration Flow

1. User fills multi-step form at `views/user/signup.php`
2. Form POSTs to `actions/register.php`
3. Server validates all fields, checks username/email uniqueness
4. Wraps two inserts in a transaction:
   - `INSERT INTO users` → gets `lastInsertId()`
   - `INSERT INTO patients` → uses that user_id
5. On success: flash message → redirect to `index.php` (login page)
6. On failure: flash error → redirect back to signup form

---

## Known Issues & Fixes

### `actions/.htaccess` — Deny from all (FIXED 2026-06-02)

**Problem:** The file originally contained `Deny from all`, which caused Apache to return **HTTP 403 Forbidden** for all requests to `actions/` — including POST form submissions. PHP never ran.

**Root cause:** Apache evaluates `.htaccess` rules before PHP. `Deny from all` blocks at the server level regardless of HTTP method.

**Fix:** Removed the `Deny from all` directive. PHP action scripts already redirect GET requests back to their respective forms (`$_SERVER['REQUEST_METHOD'] !== 'POST'` checks), so no security is lost.

**Note:** `config/.htaccess` and `database/.htaccess` correctly keep `Deny from all` because those directories contain sensitive files that should never be served.

---

## Session Keys

| Key                   | Set by              | Contents                                    |
|-----------------------|---------------------|---------------------------------------------|
| `$_SESSION['patient']`| `setPatientSession()` | id, patient_no, full_name, username, status |
| `$_SESSION['admin']`  | `setAdminSession()`   | id, full_name, username, email, phone, role |
| `$_SESSION['flash']`  | `flashMessage()`      | One-time messages (errors, success notices) |
| `$_SESSION['csrf_token']` | `csrfToken()`    | CSRF token for form validation              |

---

## Setup Checklist (Fresh Install)

1. Start Apache and MySQL in XAMPP Control Panel
2. Open `http://localhost/phpmyadmin`
3. Create database: `CREATE DATABASE rhu_rizal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;`
4. Import `database/schema.sql`
5. Import `database/seed.sql` (optional — adds sample doctors, services, and admin account)
6. Visit `http://localhost/rhu-appointment-system`
