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
| v1.00 | v1.00 | `06bed4a76838b78f73a309717af4a8e23ce2f9ca` |
| v1.01 | v1.01 | `b79c5c4bd162be690b3d3a6fff964bf378470e39` |
| v1.02 | v1.02 | `1a09ed4bebcf839ff75787b00b6cdbf9ffa50e20` |
| v1.03 | v1.03 | `35a19c47843cbdbb77dece5404a38d711f24e050` |
| v1.04 | v1.04 | `661ad366aa12faa210dfb23b4fa3a71308cb57ac` |
| v1.05 | v1.05 | _(fill after push)_ |
| v1.06 | v1.06 | _(fill after push)_ |
| v1.07 | v1.07 | _(fill after push)_ |
| v1.08 | v1.08 | _(fill after push)_ |
| v1.09 | v1.09 | _(fill after push)_ |
| v1.10 | v1.10 | _(fill after push)_ |
| v1.11 | v1.11 | _(fill after push)_ |
| v1.12 | v1.12 | _(fill after push)_ |
| v1.13 | v1.13 | _(fill after push)_ |

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
