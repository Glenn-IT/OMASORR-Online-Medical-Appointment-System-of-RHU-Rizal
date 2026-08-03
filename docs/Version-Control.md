# Version Control — RHU Rizal Appointment System

## Week-by-Week Rollout Schedule

| Version | Feature | Pages Unlocked | Pages Still Gated |
|---------|---------|---------------|-------------------|
| v1.00 | Login & Registration | `index.php`, `views/user/signup.php`, `views/admin/login.php` | All others (13 pages) |
| v1.01 | Admin Dashboard | `views/admin/dashboard.php` | 12 pages |
| v1.02 | Admin: Manage Appointments | `views/admin/appointments.php` | 11 pages |
| v1.03 | Admin: View Calendar | `views/admin/calendar.php` | 10 pages |
| v1.04 | Admin: Doctor Schedule | `views/admin/doctors.php` | 9 pages |
| v1.05 | Admin: Patient Record | `views/admin/patients.php` | 8 pages |
| v1.06 | Admin: Manage Users | `views/admin/users.php` | 7 pages |
| v1.07 | Admin: Reports | `views/admin/reports.php` | 6 pages |
| v1.08 | Admin: Admin Profile | `views/admin/profile.php` | 5 pages |
| v1.09 | User: Patient Dashboard | `views/user/dashboard.php` | 4 pages |
| v1.10 | User: Book Appointment | `views/user/book-appointment.php` | 3 pages |
| v1.11 | User: My Appointments | `views/user/my-appointments.php` | 2 pages |
| v1.12 | User: Medical History | `views/user/medical-history.php` | 1 page |
| v1.13 | User: Patient Profile (Full System) | `views/user/profile.php` | None |
| v2.00 | Presentation Reset — Login/Register + Admin Dashboard + Patient Dashboard | `index.php`, `views/user/signup.php`, `views/admin/login.php`, `views/admin/dashboard.php`, `views/user/dashboard.php` | 11 pages (`appointments.php`, `calendar.php`, `doctors.php`, `patients.php`, `users.php`, `reports.php`, `admin/profile.php`, `book-appointment.php`, `my-appointments.php`, `medical-history.php`, `user/profile.php`) |
| v2.01 | Full System Unlock — all remaining pages | All 11 previously gated pages (`appointments.php`, `calendar.php`, `doctors.php`, `patients.php`, `users.php`, `reports.php`, `admin/profile.php`, `book-appointment.php`, `my-appointments.php`, `medical-history.php`, `user/profile.php`) | None |
| v3.00 | Presentation Reset (v2.00 base) + Profile Unlocked for Both Roles | `index.php`, `views/user/signup.php`, `views/admin/login.php`, `views/admin/dashboard.php`, `views/user/dashboard.php`, `views/admin/profile.php`, `views/user/profile.php` | 9 pages (`appointments.php`, `calendar.php`, `doctors.php`, `patients.php`, `users.php`, `reports.php`, `book-appointment.php`, `my-appointments.php`, `medical-history.php`) |
| v3.01 | Full System Unlock — all remaining pages | All 9 previously gated pages (`appointments.php`, `calendar.php`, `doctors.php`, `patients.php`, `users.php`, `reports.php`, `book-appointment.php`, `my-appointments.php`, `medical-history.php`) | None |
| v4.00 | Presentation Reset (v3.00 base) + Manage Appointments, Patient Record, Book Appointment Unlocked | `index.php`, `views/user/signup.php`, `views/admin/login.php`, `views/admin/dashboard.php`, `views/user/dashboard.php`, `views/admin/profile.php`, `views/user/profile.php`, `views/admin/appointments.php`, `views/admin/patients.php`, `views/user/book-appointment.php` | 6 pages (`calendar.php`, `doctors.php`, `users.php`, `reports.php`, `my-appointments.php`, `medical-history.php`) |
| v5.00 | v4.00 base + Admin: View Calendar, Doctor Schedule Unlocked | `index.php`, `views/user/signup.php`, `views/admin/login.php`, `views/admin/dashboard.php`, `views/user/dashboard.php`, `views/admin/profile.php`, `views/user/profile.php`, `views/admin/appointments.php`, `views/admin/patients.php`, `views/user/book-appointment.php`, `views/admin/calendar.php`, `views/admin/doctors.php` | 4 pages (`users.php`, `reports.php`, `my-appointments.php`, `medical-history.php`) |
| v5.01 | Full System Unlock — all remaining pages | All 4 previously gated pages (`users.php`, `reports.php`, `my-appointments.php`, `medical-history.php`) | None |

---

## Under Construction Strategy

- **`components/under-construction.php`** is included at the top of every locked page.
- It renders a styled "Under Construction" page with the current version number and calls `exit` — nothing below it runs.
- To **unlock** a page: remove the `require_once` gate line at the top of that file.
- To **update the version number**: change `CURRENT_VERSION` in `components/under-construction.php`.
- A page's own features must work fully when unlocked. If a button/link inside it leads to a still-locked page, that link will naturally hit the under-construction gate.

---

## Git Commands Per Version

Run these steps each time you present a new version:

```bash
# 1. Remove the gate from the page(s) being unlocked this week
#    (delete the require_once under-construction line at the top of the file)

# 2. Update the version number in components/under-construction.php
#    Change: define('CURRENT_VERSION', 'v1.XX');

# 3. Stage, commit, tag, and push
git add .
git commit -m "feat: implement vX.XX - unlock [Feature Name]"
git tag vX.XX
git push origin main
git push origin vX.XX
```

---

## How Git Tags Work

A **tag** is a permanent snapshot of your code at a specific commit. Unlike branches that move forward, tags stay fixed — so `v1.01` will always point to exactly the code you had during Week 1's presentation, even as you keep developing.

Tags are useful for:
- Going back to a previous presentation state: `git checkout v1.01`
- Letting your prof verify what was shown on a specific week
- Creating GitHub Releases for clean download links

---

## GitHub Release Tags Table

Update this table after each presentation using:
```bash
git tag | sort | xargs -I{} git log -1 --format="{} %H" {}
```

| Version | Tag Name | Commit Hash |
|---------|----------|-------------|
| v1.00 | v1.00 | `30e2d669a25979e5b714478e50e0c17ba2c582b6` (retagged: added case-sensitive login, lockout, OTP reset; removed demo credentials) |
| v1.01 | v1.01 | `b79c5c4bd162be690b3d3a6fff964bf378470e39` |
| v1.02 | v1.02 | `1a09ed4bebcf839ff75787b00b6cdbf9ffa50e20` |
| v1.03 | v1.03 | `35a19c47843cbdbb77dece5404a38d711f24e050` |
| v1.04 | v1.04 | `661ad366aa12faa210dfb23b4fa3a71308cb57ac` |
| v1.05 | v1.05 | `902e59b2badf816634d03c0f2e7b4475ea25d6ec` |
| v1.06 | v1.06 | `84e691ecbdf52af1481bf6d61e24c13df5b3ca93` |
| v1.07 | v1.07 | `7c5f3ee73f965dbe1681db514bcd06d61bdeb4df` |
| v1.08 | v1.08 | `0aec1e1303d2849df9de6f1a54e8627ef8985c6b` |
| v1.09 | v1.09 | `82687b769a632acb3861949d2e3395fb20fd78f8` |
| v1.10 | v1.10 | `68c225daa82c7bf5f31446e1693be2f7ce843b97` |
| v1.11 | v1.11 | `5018adf0b001779c1b54d5919eda855b56d20c28` |
| v1.12 | v1.12 | `3343ff451bb8639b33ca8a20093854b1327cca1b` |
| v1.13 | v1.13 | `139faaabac04ac7b40bc577d08a938f86836ff63` |
| v2.00 | v2.00 | `a241599f31db55027aabbb542a3ef46d2fdc4a18` |
| v3.00 | v3.00 | `ff4eb19d734bea3d536aa4ed2b3e995879d85054` (retagged: added 11-digit PH phone number validation on Admin/Patient profile) |
| v3.01 | v3.01 | `cdc5ec7` |
| v4.00 | v4.00 | `146d4d9cc04739097c553644fc576a62f9e694f6` |
| v5.00 | v5.00 | `5606c782843e290454324376af1e93aa5bb753b0` |
| v5.01 | v5.01 | *(fill in after commit)* |

---

**Note on v4.00:** Built on the v3.00 base (login/signup, dashboards, admin/patient profile unlocked, everything else re-gated), with `views/admin/appointments.php` (Manage Appointments), `views/admin/patients.php` (Patient Record), and `views/user/book-appointment.php` (Book Appointment) additionally unlocked.

---

**Note on v5.01:** All pages unlocked (full system), same as the v2.00 → v2.01 and v3.00 → v3.01 pattern.

---

**Note on v5.00:** Built on the v4.00 base, with `views/admin/calendar.php` (View Calendar) and `views/admin/doctors.php` (Doctor Schedule) additionally unlocked.

---

**Note on v3.01:** All pages unlocked (full system), same as the v2.00 → v2.01 pattern.

---

**Note on v3.00:** Built on the v2.00 base (login/signup, dashboards unlocked, everything else re-gated), with `views/admin/profile.php` and `views/user/profile.php` additionally unlocked so both the admin and patient Profile tabs are viewable. Retagged afterward to include the 11-digit PH phone number validation fix on both profile forms.

---

**Note on v2.00:** Unlike v1.00–v1.13 (one incremental unlock per version), v2.00 is a presentation-reset snapshot: it carries all v1.00 content (login/signup, case-sensitive auth, 3-attempt lockout, OTP password reset) plus the Admin Dashboard and Patient Dashboard unlocked, with every other page re-gated behind `components/under-construction.php`.

---

## When a Prof or Client Requests Changes After a Presentation

Fix on `main` first, then re-tag the upcoming version:

```bash
# Fix on main
git checkout main
git add .
git commit -m "feat: update [page] per feedback"
git push origin main

# Delete old tag and re-create it pointing to the new commit
git tag -d vX.XX
git push origin :refs/tags/vX.XX
git tag vX.XX
git push origin vX.XX
```

This keeps the upcoming version tag accurate without affecting already-presented versions.
