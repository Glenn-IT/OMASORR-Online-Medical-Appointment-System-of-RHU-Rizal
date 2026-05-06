# 🏥 RHU Rizal — Online Medical Appointment System

> **Prototype Version 1.0** — Frontend only (HTML/CSS/JS + localStorage)  
> Rural Health Unit – Municipality of Rizal

---

## � Table of Contents

1. [Overview](#-overview)
2. [Quick Start](#-quick-start)
3. [Demo Credentials](#-demo-credentials)
4. [Project Structure](#-project-structure)
5. [Tech Stack](#️-tech-stack)
6. [Features](#-features)
7. [Available Services](#-available-services)
8. [Doctors](#-doctors)
9. [Pages](#-pages)
10. [Data & Storage](#-data--storage)
11. [Core JS Utilities](#-core-js-utilities)
12. [Appointment Status Flow](#-appointment-status-flow)
13. [Prototype Limitations](#️-prototype-limitations)
14. [License](#-license)

---

## 📖 Overview

The **RHU Rizal Online Medical Appointment System** is a frontend prototype for the Rural Health Unit of the Municipality of Rizal. It allows community patients to register, book medical appointments, and view their medical history online — while giving health unit administrators a complete dashboard to manage appointments, doctors, patients, and reports.

This prototype is built entirely with vanilla HTML, CSS, and JavaScript, using `localStorage` as a simulated database. It is intended for demonstration and academic purposes.

---

## �🚀 Quick Start

1. **Clone or download** this repository into your XAMPP `htdocs` folder:
   ```
   C:\xampp\htdocs\rhu-appointment-system\
   ```
2. **Start XAMPP** and ensure **Apache** is running.
3. **Open in browser:**
   - 🧑‍⚕️ Patient Login: [`http://localhost/rhu-appointment-system/`](http://localhost/rhu-appointment-system/)
   - 🔐 Admin Login: [`http://localhost/rhu-appointment-system/views/admin/login.html`](http://localhost/rhu-appointment-system/views/admin/login.html)

> ⚠️ Must be served via XAMPP (not opened directly as a file) due to JS module loading.

---

## 🔑 Demo Credentials

| Role    | Username     | Password     |
| ------- | ------------ | ------------ |
| Patient | `juandc`     | `patient123` |
| Patient | `mcsantos`   | `patient123` |
| Patient | `rmangubat`  | `patient123` |
| Patient | `lfernandez` | `patient123` |
| Admin   | `admin`      | `admin123`   |

---

## 📁 Project Structure

```
rhu-appointment-system/
├── index.html                        # Patient login (entry point)
├── Progress.md                       # Development progress tracker
├── README.md                         # Project documentation (this file)
├── assets/
│   ├── css/
│   │   └── style.css                 # Global styles (custom, no framework)
│   └── js/
│       └── app.js                    # Core JS utilities & shared logic
├── components/
│   ├── admin-sidebar.html            # Admin sidebar component
│   └── user-sidebar.html            # Patient sidebar component
├── data/
│   └── mockData.js                   # Mock data + localStorage DB helpers
├── docs/                             # Documentation folder (empty in prototype)
└── views/
    ├── user/                         # Patient-facing pages
    │   ├── signup.html               # 3-step patient registration
    │   ├── dashboard.html            # Patient home + stats
    │   ├── book-appointment.html     # Book appointment with calendar
    │   ├── my-appointments.html      # View & manage own appointments
    │   ├── medical-history.html      # Past appointment records
    │   └── profile.html             # Patient profile management
    └── admin/                        # Admin-facing pages
        ├── login.html                # Admin login
        ├── dashboard.html            # Analytics & overview
        ├── appointments.html         # Manage all appointments
        ├── doctors.html              # Doctor schedules & management
        ├── calendar.html             # Monthly appointment calendar
        ├── patients.html             # Patient records
        ├── users.html                # User account management
        ├── reports.html              # Printable reports
        └── profile.html             # Admin profile
```

---

## 🛠️ Tech Stack

| Technology               | Purpose                                       |
| ------------------------ | --------------------------------------------- |
| **HTML5**                | Page structure and markup                     |
| **CSS3** (custom)        | Styling — no framework, all hand-written      |
| **Vanilla JavaScript**   | App logic, DOM manipulation, data management  |
| **FontAwesome 6.5.0**    | Icons (via CDN)                               |
| **Chart.js**             | Admin dashboard analytics charts              |
| **Google Fonts – Inter** | Typography                                    |
| **localStorage**         | Client-side data persistence (prototype only) |

---

## ✨ Features

### 🧑‍⚕️ Patient Side

- **3-step Registration** — personal info, contact details, account setup
- **Login / Logout** with session management via `localStorage`
- **Dashboard** — upcoming appointments, quick stats, recent activity
- **Book Appointment** — interactive calendar, service & doctor selection, time slot picker
- **My Appointments** — view all bookings, cancel pending appointments
- **Medical History** — view completed appointment records
- **Profile Management** — update personal and contact information

### 🔐 Admin Side

- **Secure Admin Login** — separate admin portal
- **Analytics Dashboard** — total patients, appointments, doctors; Chart.js visualizations
- **Appointment Management** — approve, reject, or complete appointments
- **Doctor Management** — add/edit/toggle doctor availability and schedules
- **Calendar View** — monthly overview of all appointments
- **Patient Records** — view all registered patients and their details
- **User Account Management** — manage patient accounts (activate/deactivate)
- **Reports** — printable summary reports
- **Admin Profile** — update admin account info

### 🔧 Shared Utilities

- Toast notification system (success, error, warning, info)
- Modal system (open/close with overlay click support)
- Responsive sidebar with mobile toggle
- Tab navigation component
- Status badge renderer
- Date & time formatters (Philippine locale)

---

## 🏥 Available Services

| #   | Service              |
| --- | -------------------- |
| 1   | General Consultation |
| 2   | Prenatal Care        |
| 3   | Pediatrics           |
| 4   | Dental Services      |
| 5   | Family Planning      |
| 6   | Immunization         |
| 7   | Laboratory Services  |
| 8   | TB-DOTS Program      |
| 9   | Nutrition Counseling |
| 10  | Eye Care             |

---

## 👨‍⚕️ Doctors

| Name                 | Specialty         | Schedule                  |
| -------------------- | ----------------- | ------------------------- |
| Dr. Maria Santos     | General Medicine  | Mon – Wed – Fri           |
| Dr. Jose Reyes       | Pediatrics        | Tue – Thu                 |
| Dr. Ana Dela Cruz    | OB-Gynecology     | Mon – Thu                 |
| Dr. Carlos Mendoza   | Dentistry         | Wed – Fri                 |
| Dr. Rosa Flores      | Internal Medicine | Tue – Fri _(unavailable)_ |
| Dr. Eduardo Bautista | Ophthalmology     | Mon – Wed                 |

---

## 📋 Pages

### Patient Side

| File                               | Page             | Description                             |
| ---------------------------------- | ---------------- | --------------------------------------- |
| `index.html`                       | Login            | Patient login entry point               |
| `views/user/signup.html`           | Register         | 3-step patient registration form        |
| `views/user/dashboard.html`        | Dashboard        | Home with stats and recent appointments |
| `views/user/book-appointment.html` | Book Appointment | Calendar + service/doctor/time picker   |
| `views/user/my-appointments.html`  | My Appointments  | List and manage personal bookings       |
| `views/user/medical-history.html`  | Medical History  | Completed appointment records           |
| `views/user/profile.html`          | Profile          | Update personal information             |

### Admin Side

| File                            | Page         | Description                              |
| ------------------------------- | ------------ | ---------------------------------------- |
| `views/admin/login.html`        | Admin Login  | Separate admin authentication            |
| `views/admin/dashboard.html`    | Dashboard    | Analytics with Chart.js graphs           |
| `views/admin/appointments.html` | Appointments | Approve / reject / complete appointments |
| `views/admin/doctors.html`      | Doctors      | Manage doctors and schedules             |
| `views/admin/calendar.html`     | Calendar     | Monthly appointment calendar view        |
| `views/admin/patients.html`     | Patients     | All patient records                      |
| `views/admin/users.html`        | Users        | Manage user accounts                     |
| `views/admin/reports.html`      | Reports      | Generate and print reports               |
| `views/admin/profile.html`      | Profile      | Admin account management                 |

---

## 💾 Data & Storage

All data is stored in the browser's `localStorage` under the following keys:

| Key                | Contents                                        |
| ------------------ | ----------------------------------------------- |
| `rhu_patients`     | Array of registered patient objects             |
| `rhu_appointments` | Array of appointment records                    |
| `rhu_doctors`      | Array of doctor records                         |
| `rhu_booked_dates` | Array of fully-booked date strings (YYYY-MM-DD) |
| `rhu_session`      | Currently logged-in user session object         |

Data is **automatically seeded** on first load from `data/mockData.js` via `initMockData()`.

### Patient Object Shape

```json
{
  "id": "P-001",
  "fullName": "Juan dela Cruz",
  "username": "juandc",
  "password": "patient123",
  "email": "juan@example.com",
  "phone": "09171234567",
  "address": "Brgy. Rizal, Rizal",
  "birthdate": "1990-05-15",
  "gender": "Male",
  "bloodType": "O+",
  "status": "Active",
  "registeredDate": "2025-01-10"
}
```

### Appointment Object Shape

```json
{
  "id": "APT-001",
  "patientId": "P-001",
  "patientName": "Juan dela Cruz",
  "service": "General Consultation",
  "doctor": "Dr. Maria Santos",
  "date": "2026-04-15",
  "time": "09:00",
  "reason": "Routine check-up",
  "status": "Pending",
  "createdAt": "2026-04-10"
}
```

---

## 🔧 Core JS Utilities (`assets/js/app.js`)

| Function / Class                   | Description                                                             |
| ---------------------------------- | ----------------------------------------------------------------------- |
| `checkAuth(role)`                  | Redirects unauthenticated users; enforces role access                   |
| `logout()`                         | Clears session and redirects to login                                   |
| `showToast(message, type, title)`  | Displays a toast notification (`success`, `error`, `warning`, `info`)   |
| `openModal(id)` / `closeModal(id)` | Opens or closes a modal overlay by element ID                           |
| `closeAllModals()`                 | Dismisses all open modals                                               |
| `initSidebar()`                    | Enables mobile sidebar toggle                                           |
| `initTabs()`                       | Initialises tab navigation components                                   |
| `statusBadge(status)`              | Returns a styled HTML badge for an appointment/patient status           |
| `RHUCalendar`                      | Calendar widget class with availability, booked, and closed date states |
| `populateServices(selectId)`       | Populates a `<select>` element with all available services              |
| `populateDoctors(selectId)`        | Populates a `<select>` element with available doctors                   |
| `setSidebarActive()`               | Highlights the current page's nav item in the sidebar                   |
| `formatDate(dateStr)`              | Formats a date string in Philippine locale (e.g., _April 15, 2026_)     |
| `formatTime(timeStr)`              | Converts 24-hour time to 12-hour AM/PM format                           |

### DB Helper (`const DB`)

```js
DB.getPatients(); // Read all patients
DB.savePatients(data); // Save patients array
DB.getAppointments(); // Read all appointments
DB.saveAppointments(data); // Save appointments array
DB.getDoctors(); // Read all doctors
DB.getSession(); // Get current logged-in session
DB.setSession(user); // Set login session
DB.clearSession(); // Remove session (logout)
DB.generateId(prefix); // Generate next "P-XXX" or "APT-XXX" ID
```

---

## 🔄 Appointment Status Flow

```
[Booked by Patient]
        │
        ▼
    [ Pending ]
     /        \
    ▼          ▼
[Approved]  [Rejected]  ← Admin action
    │
    ▼
[Completed]             ← Admin marks as done

Patient can also:
[Pending] ──► [Cancelled]
```

---

## ⚠️ Prototype Limitations

| Limitation           | Details                                                            |
| -------------------- | ------------------------------------------------------------------ |
| No backend           | All data lives in the browser's `localStorage` only                |
| Plain-text passwords | Passwords are stored unencrypted — **never do this in production** |
| Device-specific data | Data cannot be shared across browsers or devices                   |
| No real email/SMS    | Notification features are UI-only; no messages are actually sent   |
| No file uploads      | Profile photo and document uploads are not functional              |
| No real-time updates | No WebSocket or polling; pages must be manually refreshed          |
| Single admin account | Only one hardcoded admin account (`admin` / `admin123`)            |

---

## 📄 License

For academic and government prototype use only — RHU Rizal, Municipality of Rizal.  
Not intended for production deployment without a proper backend implementation.
