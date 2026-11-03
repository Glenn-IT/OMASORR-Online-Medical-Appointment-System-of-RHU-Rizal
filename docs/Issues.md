# Resolved System Issues & Enhancements

- [x] **1. Forgot Password OTP Expiration & Show Password**
  - Reduced OTP validity window from 10 minutes to **1 minute** in `actions/forgot-password/request.php`, `config/mailer.php`, `login.php`, and `views/admin/login.php`.
  - Added eye toggle icons and show/hide password functionality on both **New Password** and **Confirm Password** fields across patient and admin password reset dialogs.

- [x] **2. Service-to-Doctor Auto-Filtering & Sample Doctor Expansion**
  - Added sample doctors matching key RHU services in database: Eye Care (`Dr. Roberto Cruz`), Pediatrics (`Dr. Elena Reyes`), Dental Services (`Dr. Jose Dizon`), Prenatal & OB-GYN (`Dr. Carmela Mendoza`), TB-DOTS & Internal Medicine (`Dr. Antonio Ramos`), and Nutrition & Family Planning (`Dr. Patricia Gomez`).
  - Implemented dynamic service-to-specialty filtering and automatic selection in `views/user/book-appointment.php`.

- [x] **3. 1 Transaction / Active Booking Limit Per Day Rule**
  - Enforced a rule in `actions/book-appointment.php` restricting patients to 1 active appointment per day.
  - If previous appointment on that day was `Cancelled` or `Rejected`, the patient is permitted to book again.

- [x] **4. Duplicate Doctor Entry Prevention**
  - Added case-insensitive name uniqueness validation in `actions/admin/save-doctor.php` to prevent duplicate doctor registrations.

- [x] **5. Reports Filter for Doctors & Total Clients**
  - Added doctor dropdown filter in `views/admin/reports.php` and `actions/admin/export-report.php`.
  - Added a dedicated "Doctors & Total Clients Volume" breakdown table showing Total Appointments, Completed Visits, Pending/Approved, and Cancelled/Rejected counts per doctor.

- [x] **6. Profile Picture Upload for Patient & Admin**
  - Added `profile_photo` columns to `patients` and `admin_users` tables.
  - Implemented photo file validation, safe storage in `assets/uploads/avatars/`, and live session refresh in `actions/user/update-profile.php` and `actions/admin/update-profile.php`.
  - Rendered profile avatars across patient and admin profile views, topbars, and navigation sidebars.

- [x] **7. Unavailability Notifications & Announcements**
  - Created `announcements` database table supporting doctor unavailability, service suspensions, and general advisories.
  - Built an Admin Announcements Management module (`views/admin/announcements.php`, `actions/admin/save-announcement.php`, `actions/admin/toggle-announcement.php`, `actions/admin/delete-announcement.php`).
  - Auto-synchronized doctor unavailability alerts when toggled in `actions/admin/toggle-doctor.php`.
  - Displayed live broadcast advisories on Patient Dashboard, Booking Page, Public Schedule Board, and Admin Dashboard.
