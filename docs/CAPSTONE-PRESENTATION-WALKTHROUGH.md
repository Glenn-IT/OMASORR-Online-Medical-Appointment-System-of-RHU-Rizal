# OMASORR — Capstone Defense Presentation Walkthrough & Panelist Demonstration Guide
<!-- System: Online Medical Appointment System of RHU Rizal (OMASORR) -->
<!-- Target Audience: Capstone Panelists, Advisers, Evaluators, and Health Program Stakeholders -->

---

## 🧭 Executive Summary & Timing Strategy

| Phase | Section | Recommended Duration | Primary Interface |
| :--- | :--- | :--- | :--- |
| **Phase 1** | Project Rationale & Rural Healthcare Context | 1.5 mins | Title Slide / [index.php](file:///C:/xampp/htdocs/rhu-appointment-system/index.php) |
| **Phase 2** | Technical Architecture, RBAC & Security Baseline | 1.0 min | [SYSTEM_MEMORY.md](file:///C:/xampp/htdocs/rhu-appointment-system/SYSTEM_MEMORY.md) |
| **Phase 3** | Public Gateway & Rural Health Identity | 1.0 min | [index.php](file:///C:/xampp/htdocs/rhu-appointment-system/index.php) |
| **Phase 4** | Public Schedule & Real-Time Vacant Slots Board | 1.0 min | [schedule.php](file:///C:/xampp/htdocs/rhu-appointment-system/schedule.php) |
| **Phase 5** | Patient Registration & Auto-Age Demographics | 1.5 mins | [views/user/signup.php](file:///C:/xampp/htdocs/rhu-appointment-system/views/user/signup.php) |
| **Phase 6** | Patient Portal & Interactive Slot Booking Engine | 2.0 mins | [views/user/book-appointment.php](file:///C:/xampp/htdocs/rhu-appointment-system/views/user/book-appointment.php) |
| **Phase 7** | Real-Time Patient Tracking & Self-Cancellation | 1.0 min | [views/user/my-appointments.php](file:///C:/xampp/htdocs/rhu-appointment-system/views/user/my-appointments.php) |
| **Phase 8** | Admin Command Center & Real-Time Health KPIs | 1.0 min | [views/admin/dashboard.php](file:///C:/xampp/htdocs/rhu-appointment-system/views/admin/dashboard.php) |
| **Phase 9** | Appointment Triage, Rejection & Rescheduling | 1.5 mins | [views/admin/appointments.php](file:///C:/xampp/htdocs/rhu-appointment-system/views/admin/appointments.php) |
| **Phase 10** | Clinical Consultation Recording (DOH ITR Standards) | 2.0 mins | [views/admin/appointments.php](file:///C:/xampp/htdocs/rhu-appointment-system/views/admin/appointments.php) (Consultation Modal) |
| **Phase 11** | Doctor Schedule Roster & Master Calendar | 1.0 min | [views/admin/doctors.php](file:///C:/xampp/htdocs/rhu-appointment-system/views/admin/doctors.php) / [views/admin/calendar.php](file:///C:/xampp/htdocs/rhu-appointment-system/views/admin/calendar.php) |
| **Phase 12** | Printable Official Medical Consultation Sheet (ITR) | 1.0 min | [views/user/print-medical-history.php](file:///C:/xampp/htdocs/rhu-appointment-system/views/user/print-medical-history.php) |
| **Phase 13** | Healthcare Analytics & Filtered CSV Export | 1.0 min | [views/admin/reports.php](file:///C:/xampp/htdocs/rhu-appointment-system/views/admin/reports.php) |
| **Phase 14** | Audit Logs, Account Lockout & Transition to Panel Q&A | 0.5 min | [actions/admin/update-appointment.php](file:///C:/xampp/htdocs/rhu-appointment-system/actions/admin/update-appointment.php) / [index.php](file:///C:/xampp/htdocs/rhu-appointment-system/index.php) |
| **Total** | **Full System Presentation** | **~17.0 mins** | — |

---

## 🛠️ Pre-Defense Staging & Credentials Setup

Before starting the defense presentation, prepare your presentation workstation:

1. **Browser Setup**:
   * **Window 1 (Main Browser):** Logged in as **Admin / Medical Staff** on [views/admin/dashboard.php](file:///C:/xampp/htdocs/rhu-appointment-system/views/admin/dashboard.php).
   * **Window 2 (Incognito / Private Window):** Ready for the **Patient** live booking flow on [index.php](file:///C:/xampp/htdocs/rhu-appointment-system/index.php). This eliminates the awkward delay of logging in and out during role transitions.
2. **Standard Authentication Accounts**:
   * **Admin Account:** Username: `admin` | Password: `admin123` | Email: `admin@rhurjzal.gov.ph`
   * **Sample Patient Accounts:** Password is uniform: `patient123`
     * `juandc` (Juan dela Cruz — `juan@example.com` | `P-001`)
     * `mcsantos` (Maria Clara Santos — `maria@example.com` | `P-002`)
     * `lfernandez` (Liza Fernandez — `liza@example.com` | `P-004`)
3. **Database Seed & Restoration**:
   * Pre-seeded via [`database/schema.sql`](file:///C:/xampp/htdocs/rhu-appointment-system/database/schema.sql) and [`database/seed.sql`](file:///C:/xampp/htdocs/rhu-appointment-system/database/seed.sql). You can re-import at any time using:
     ```bash
     mysql -u root rhu_rizal < database/schema.sql
     mysql -u root rhu_rizal < database/seed.sql
     ```

### 👥 Seeded Patients & Clinical Profile Records
All patients represent realistic municipal residents with unique ID series (`P-00X`) and normalized demographic profiles:

| # | Patient Name | Username (`patient123`) | Patient ID | Contact / Email | Barangay | Status |
| :- | :--- | :--- | :--- | :--- | :--- | :--- |
| 1 | **Juan dela Cruz** | `juandc` | `P-001` | `juan@example.com` / 09171234567 | Brgy. Rizal, Rizal | **Active** |
| 2 | **Maria Clara Santos** | `mcsantos` | `P-002` | `maria@example.com` / 09187654321 | Brgy. Poblacion, Rizal | **Active** |
| 3 | **Roberto Mangubat** | `rmangubat` | `P-003` | `roberto@example.com` / 09201112233 | Brgy. San Jose, Rizal | **Inactive** (Account Control Demo) |
| 4 | **Liza Fernandez** | `lfernandez` | `P-004` | `liza@example.com` / 09334455667 | Brgy. Kalinawan, Rizal | **Active** |

### 👨‍⚕️ Attending Doctors & Specialty Clinics

| # | Doctor Name | Specialty Clinic | Clinic Days | Availability Status |
| :- | :--- | :--- | :--- | :--- |
| 1 | **Dr. Maria Santos** | General Medicine | Mon - Wed - Fri | **Active (1)** |
| 2 | **Dr. Jose Reyes** | Pediatrics | Tue - Thu | **Active (1)** |
| 3 | **Dr. Ana Dela Cruz** | OB-Gynecology / Prenatal | Mon - Thu | **Active (1)** |
| 4 | **Dr. Carlos Mendoza** | Dentistry | Wed - Fri | **Active (1)** |
| 5 | **Dr. Rosa Flores** | Internal Medicine | Tue - Fri | **On Leave / Inactive (0)** |
| 6 | **Dr. Eduardo Bautista** | Ophthalmology | Mon - Wed | **Active (1)** |

### 🏥 The 10 Primary Healthcare Programs

| Service Code | Clinical Service Description | Service Code | Clinical Service Description |
| :--- | :--- | :--- | :--- |
| **SRV-01** | General Consultation | **SRV-06** | Immunization (EPI) |
| **SRV-02** | Prenatal Care & Maternal Health | **SRV-07** | Laboratory Services |
| **SRV-03** | Pediatrics & Child Care | **SRV-08** | TB-DOTS Program |
| **SRV-04** | Dental Examination & Procedures | **SRV-09** | Nutrition Counseling |
| **SRV-05** | Family Planning & Reproductive Health | **SRV-10** | Eye Care & Vision Screening |

---

## 🎬 Step-by-Step Presentation Script (From First to Last)

---

### Step 1: Project Rationale & Rural Healthcare Context
* **Screen Display:** Slide Deck or [index.php](file:///C:/xampp/htdocs/rhu-appointment-system/index.php) hero header
* **Estimated Time:** 1.5 minutes
* **Screen Action:** Present the title slide with the official municipal health identity and Rural Health Unit seal.
* **🗣️ Verbal Script:**
  > *"Good morning, honorable chairman, esteemed members of the panel, and our capstone adviser. Today, we are proud to present **OMASORR — the Online Medical Appointment System of the Rural Health Unit of Rizal**.*
  >
  > *In rural municipalities like Rizal, Cagayan, healthcare delivery faces persistent operational challenges. Traditionally, patients from remote barangays travel early in the morning—often spending hard-earned transportation money—only to find long, congested queues at the RHU. Vulnerable individuals such as pregnant mothers, infants, and elderly citizens often endure hours in crowded waiting areas, only to discover that their required physician is off-duty or that daily consultation quotas have already been exhausted.*
  >
  > *On the administrative side, RHU personnel manually manage paper triage slips and physical patient folders, leading to misplaced medical records, double-booked slots, and no reliable channels to notify patients of schedule disruptions.*
  >
  > *OMASORR solves this by providing a unified, web-based appointment reservation, doctor scheduling, and clinical consultation recording platform tailored specifically to municipal rural health unit workflows."*

---

### Step 2: Technical Architecture, RBAC & Security Baseline
* **Screen Display:** [SYSTEM_MEMORY.md](file:///C:/xampp/htdocs/rhu-appointment-system/SYSTEM_MEMORY.md) or System Architecture Slide
* **Estimated Time:** 1.0 minute
* **Screen Action:** Briefly emphasize the modular backend structure and security defenses.
* **🗣️ Verbal Script:**
  > *"OMASORR is engineered with a strict, defensive architecture:
  > 1. **Core Stack:** Native PHP 8+ with Object-Oriented PDO database abstraction, MySQL with InnoDB foreign key constraints, vanilla JavaScript (ES6+), and responsive CSS.
  > 2. **Role-Based Access Control (RBAC):** Strict isolation between `patient` accounts in [`actions/login.php`](file:///C:/xampp/htdocs/rhu-appointment-system/actions/login.php) and `admin` accounts in [`actions/admin/login.php`](file:///C:/xampp/htdocs/rhu-appointment-system/actions/admin/login.php), protected by session guards in [`config/auth.php`](file:///C:/xampp/htdocs/rhu-appointment-system/config/auth.php).
  > 3. **Brute Force Protection:** Automated account lockout logic that disables login attempts for 30 seconds after 3 failed tries, complete with a real-time countdown timer.
  > 4. **Data Integrity & Injection Defense:** 100% prepared SQL statements with parameter binding across all endpoints, CSRF token verification on all POST requests, and password hashing using BCrypt (`password_hash`).
  > 5. **Automated Notification Dispatcher:** Integrated PHPMailer SMTP delivering branded HTML confirmations for registrations, booking updates, and 6-digit OTP password recoveries."*

---

### Step 3: Public Gateway & Municipal Health Portal
* **Screen Display:** [index.php](file:///C:/xampp/htdocs/rhu-appointment-system/index.php)
* **Estimated Time:** 1.0 minute
* **Screen Action:** Show the clean login portal, municipal branding, and notice links.
* **🗣️ Verbal Script:**
  > *"Starting at our public entry point, patients are greeted by an accessible and responsive interface. The portal prominently displays RHU Rizal branding and direct navigation to account creation, password recovery, and the public schedule viewer. Notice the separate, protected administrative portal link at the bottom."*

---

### Step 4: Public Schedule & Real-Time Vacant Slots Board
* **Screen Display:** Click **"View Available Schedules"** to open [schedule.php](file:///C:/xampp/htdocs/rhu-appointment-system/schedule.php) (or [views/schedule-viewer.php](file:///C:/xampp/htdocs/rhu-appointment-system/views/schedule-viewer.php))
* **Estimated Time:** 1.0 minute
* **Screen Action:**
  1. Show how any resident or guest can inspect clinic availability without logging in.
  2. Select an active doctor (e.g., `Dr. Maria Santos`) or date.
  3. Point out color-coded slot pills showing available vs. occupied hours.
  4. Click an open slot to demonstrate the seamless prompt inviting the patient to log in or register to lock that appointment.
* **🗣️ Verbal Script:**
  > *"One major usability innovation of OMASORR is the **Public Schedule Board**. Rural citizens should never have to create an account or log in just to see if a doctor is available.
  >
  > *Here, residents can immediately check real-time clinic schedules, attending doctor assignments, and open time slots. When a patient clicks an available slot, the system remembers their selection and smoothly routes them through registration or login to finalize their booking."*

---

### Step 5: Multi-Step Patient Registration & Auto-Age Demographics
* **Screen Display:** [views/user/signup.php](file:///C:/xampp/htdocs/rhu-appointment-system/views/user/signup.php)
* **Estimated Time:** 1.5 minutes
* **Screen Action:**
  1. Open the 3-step registration wizard.
  2. In Step 1, select a date of birth (DOB) and highlight the **auto-calculated age field**.
  3. Fill in contact number, blood type, and barangay address.
  4. Advance to Step 2 to set up username and password with live validation.
  5. Show the review screen in Step 3 and submit.
  6. Mention the automated welcome email generated via [`actions/register.php`](file:///C:/xampp/htdocs/rhu-appointment-system/actions/register.php) and sequential patient ID (`P-00X`).
* **🗣️ Verbal Script:**
  > *"To keep registration simple for rural patients, we designed a guided 3-step wizard. Notice that as the patient selects their Date of Birth, their exact age is dynamically calculated on the fly and validated on the backend.
  >
  > *Upon submission, [`actions/register.php`](file:///C:/xampp/htdocs/rhu-appointment-system/actions/register.php) checks for unique usernames and emails, generates a permanent patient reference number (such as `P-005`), creates the secure user credentials, and dispatches a branded welcome email via PHPMailer."*

---

### Step 6: Patient Dashboard & Interactive Slot Booking Engine
* **Screen Display:** Switch to logged-in patient window ([views/user/dashboard.php](file:///C:/xampp/htdocs/rhu-appointment-system/views/user/dashboard.php) then navigate to [views/user/book-appointment.php](file:///C:/xampp/htdocs/rhu-appointment-system/views/user/book-appointment.php))
* **Estimated Time:** 2.0 minutes
* **Screen Action:**
  1. Point out the patient dashboard KPI cards (*Total, Pending, Approved, Completed*).
  2. Navigate to **"Book Appointment"**.
  3. Select **Service Type** (e.g., *General Consultation*).
  4. Select **Attending Doctor** (e.g., *Dr. Maria Santos*).
  5. Demonstrate the custom **RHUCalendar** interactive calendar widget:
     - Closed clinic days and holidays are disabled.
     - Dates with no vacancies are marked.
  6. Select an available date and pick an open time slot (e.g., `09:30 AM`).
  7. Show the double-booking validation and past-time restriction: if booking for today, elapsed hours are automatically disabled.
  8. Enter the reason for visit (e.g., *"Persistent cough and mild fever for 3 days"*) and click **"Confirm Appointment"**.
* **🗣️ Verbal Script:**
  > *"Inside the patient portal, patients have complete visibility over their healthcare journey.
  >
  > *When scheduling an appointment, our interactive `RHUCalendar` widget enforces critical healthcare business rules:
  > - Doctor availability schedules are dynamically checked.
  > - Declared holidays and closed days are locked.
  > - Past time slots on the current day are filtered out.
  > - Strict concurrency checks in [`actions/book-appointment.php`](file:///C:/xampp/htdocs/rhu-appointment-system/actions/book-appointment.php) ensure that no two patients can claim the same doctor slot at the same time.
  >
  > *Once confirmed, a unique reference number like `APT-009` is issued, the audit trail is initialized, and an appointment booking confirmation is emailed to the patient."*

---

### Step 7: Real-Time Patient Tracking & Self-Cancellation
* **Screen Display:** [views/user/my-appointments.php](file:///C:/xampp/htdocs/rhu-appointment-system/views/user/my-appointments.php)
* **Estimated Time:** 1.0 minute
* **Screen Action:**
  1. Show the newly booked appointment displayed at the top with a yellow `Pending` badge.
  2. Point out the filter tabs (*All, Upcoming, Completed, Cancelled*).
  3. Show the **"Cancel Appointment"** action: click to open the cancellation modal, input a reason, and confirm cancellation.
  4. Note that the status transitions to `Cancelled`, the calendar slot is immediately released for other patients, and an email confirmation is sent.
* **🗣️ Verbal Script:**
  > *"Patients no longer need to wonder if their request was received. The 'My Appointments' screen offers real-time status tracking.
  >
  > *Furthermore, patients can self-cancel their upcoming appointments if their schedule changes. As handled in [`actions/cancel-appointment.php`](file:///C:/xampp/htdocs/rhu-appointment-system/actions/cancel-appointment.php), this immediately frees up the doctor's time slot for other patients in need and logs the cancellation note into the audit table."*

---

### Step 8: Administrator Command Center & Live Clinic Metrics
* **Screen Display:** Switch to Admin window: [views/admin/dashboard.php](file:///C:/xampp/htdocs/rhu-appointment-system/views/admin/dashboard.php)
* **Estimated Time:** 1.0 minute
* **Screen Action:**
  1. Highlight the top statistical summary cards (*Total Appointments, Pending Requests, Completed Consultations, Total Registered Patients*).
  2. Showcase the Chart.js visual analytics: Monthly appointment volume trends and status distribution breakdown.
  3. Highlight the **Pending Appointment Alerts** feed alerting staff to unreviewed bookings.
* **🗣️ Verbal Script:**
  > *"Now switching to the Administrator and RHU Medical Staff Command Center. The dashboard gives the Municipal Health Officer and triage staff immediate situational awareness: overall patient volume, daily pending triage queues, and service utilization graphs powered by Chart.js."*

---

### Step 9: Appointment Triage, Rejection & Rescheduling
* **Screen Display:** [views/admin/appointments.php](file:///C:/xampp/htdocs/rhu-appointment-system/views/admin/appointments.php)
* **Estimated Time:** 1.5 minutes
* **Screen Action:**
  1. Filter the queue by clicking the **"Pending"** tab badge.
  2. Locate a pending appointment.
  3. Demonstrate the triage options:
     - **Approve:** One-click approval transitioning the status to `Approved` and sending an approval email to the patient.
     - **Reschedule:** Pick a new date and time with administrative remarks.
     - **Reject:** Open the rejection modal and explain that an administrative justification note is mandatory before rejecting.
* **🗣️ Verbal Script:**
  > *"In the 'Manage Appointments' triage table, administrative staff can process requests systematically.
  >
  > *Approving an appointment changes its state from `Pending` to `Approved` and automatically dispatches a notification email with arrival instructions. If an appointment cannot be accommodated, the system strictly enforces an administrative explanation note so the patient is informed of the exact reason."*

---

### Step 10: Clinical Consultation Recording (DOH ITR Standards)
* **Screen Display:** [views/admin/appointments.php](file:///C:/xampp/htdocs/rhu-appointment-system/views/admin/appointments.php) (Click **"Complete / Consult"** on an `Approved` appointment)
* **Estimated Time:** 2.0 minutes
* **Screen Action:**
  1. Open the comprehensive **Clinical Consultation Record Modal**.
  2. Point out that the modal is modeled directly after the **Department of Health (DOH) Individual Treatment Record (ITR)**:
     - **Patient Demographics:** Patient Name, DOB, auto-computed age, sex, Folder/Patient ID (`P-00X`), and address.
     - **Consultation Specifics:** Mode of transaction (*Online appointment*), consultation date & time, nature of visit.
     - **Vital Signs & Anthropometrics:** Height, Weight, Blood Pressure (BP), Respiratory Rate (RR), Pulse Rate (PR), and Body Temperature (°C).
     - **Clinical Assessment:** Chief Complaints, History of Present Illness, Past Medical History, Pertinent Physical Examination (PE).
     - **Diagnosis & Medical Management:** Clinical Diagnosis / ICD impression, Treatment / Rx Prescriptions, and Laboratory Findings.
  3. Submit the form to transition the appointment to `Completed`.
* **🗣️ Verbal Script:**
  > *"This is the clinical core of OMASORR. Unlike commercial booking systems that simply mark an appointment 'Done' with a single click, our system bridges appointment booking with official clinical recording.
  >
  > *In compliance with Department of Health (DOH) standards, an appointment cannot be marked `Completed` without recording an official **Individual Treatment Record (ITR)**. Attending health officers input vital signs, physical examination notes, diagnosis, and prescription treatments. This guarantees clinical accountability and eliminates lost paper records."*

---

### Step 11: Doctor Schedule Roster & Master Calendar
* **Screen Display:** [views/admin/doctors.php](file:///C:/xampp/htdocs/rhu-appointment-system/views/admin/doctors.php) and [views/admin/calendar.php](file:///C:/xampp/htdocs/rhu-appointment-system/views/admin/calendar.php)
* **Estimated Time:** 1.0 minute
* **Screen Action:**
  1. On the Doctors page, demonstrate adding or editing a doctor: highlight how schedule assignment eliminates error-prone typing through interactive weekday selection chips (`Mon`-`Sat`) and quick presets (`Monday to Saturday`, `Mon-Fri`, `Mon-Wed-Fri`), with live badge previews. Show instant availability toggling on/off.
  2. Switch to **Master Calendar** ([views/admin/calendar.php](file:///C:/xampp/htdocs/rhu-appointment-system/views/admin/calendar.php)) to view district-wide booked appointments plotted across the monthly grid.
* **🗣️ Verbal Script:**
  > *"Administrators manage the clinic roster in [views/admin/doctors.php](file:///C:/xampp/htdocs/rhu-appointment-system/views/admin/doctors.php). If a doctor goes on emergency leave or attends a municipal medical mission, toggling their status immediately deactivates them from the patient booking calendar.
  >
  > *The Master Calendar provides clinic supervisors with a high-level visual grid of scheduled consultations across all physicians."*

---

### Step 12: Printable Official Medical Consultation Sheet (ITR)
* **Screen Display:** Navigate to [views/user/print-medical-history.php?id=3](file:///C:/xampp/htdocs/rhu-appointment-system/views/user/print-medical-history.php?id=3) (or open via Patient Medical History [views/user/medical-history.php](file:///C:/xampp/htdocs/rhu-appointment-system/views/user/medical-history.php))
* **Estimated Time:** 1.0 minute
* **Screen Action:**
  1. Display the formatted DOH Individual Treatment Record (ITR) layout.
  2. Point out the official Republic of the Philippines / Department of Health header, municipal seal, patient demographics, recorded vitals, and physician signature block.
  3. Click **"Print Record"** or demonstrate the print preview styling (CSS `@media print`).
* **🗣️ Verbal Script:**
  > *"Whenever a patient needs an official clinical summary for employment, school requirements, hospital referral, or PhilHealth validation, OMASORR generates a standardized, print-ready **Individual Treatment Record (ITR)**.
  >
  > *The layout faithfully reproduces the official DOH consultation sheet—complete with institutional headers, vital sign grids, physician diagnosis, and certification blocks—ensuring institutional acceptance."*

---

### Step 13: Healthcare Analytics & Filtered CSV Export
* **Screen Display:** [views/admin/reports.php](file:///C:/xampp/htdocs/rhu-appointment-system/views/admin/reports.php)
* **Estimated Time:** 1.0 minute
* **Screen Action:**
  1. Show filter controls: Filter by Month, Appointment Status, or Clinical Service (e.g., *Prenatal Care*).
  2. Show dynamic service distribution charts.
  3. Click **"Export CSV"** to demonstrate instant generation via [`actions/admin/export-report.php`](file:///C:/xampp/htdocs/rhu-appointment-system/actions/admin/export-report.php).
* **🗣️ Verbal Script:**
  > *"For LGU health budgeting and Provincial Health Office reporting, OMASORR delivers comprehensive reporting. Clinic administrators can filter records by month, status, or medical service, analyze morbidity trends, and export verified CSV datasets in one click."*

---

### Step 14: Security Defenses, Audit Logging & Transition to Q&A
* **Screen Display:** [views/admin/users.php](file:///C:/xampp/htdocs/rhu-appointment-system/views/admin/users.php) & Recent Logs in [views/admin/dashboard.php](file:///C:/xampp/htdocs/rhu-appointment-system/views/admin/dashboard.php)
* **Estimated Time:** 0.5 minute
* **Screen Action:**
  1. Show user account control (activating/deactivating patient accounts).
  2. Point out immutable audit entries in `appointment_logs` recording who changed each status, the timestamp, and reasons.
  3. Conclude and face the panel for questions.
* **🗣️ Verbal Script:**
  > *"Every single status transition—whether initiated by the patient or administrator—is immutably recorded in `appointment_logs` with timestamps and user attribution. Inactive or fraudulent accounts can be instantly frozen via User Management.
  >
  > *In conclusion, OMASORR modernizes the Rural Health Unit of Rizal by reducing patient waiting times, preventing scheduling conflicts, safeguarding patient privacy, and digitizing clinical consultation records.
  >
  > *Thank you very much, honorable members of the panel. We are now ready for your questions."*

---

## 🛡️ Capstone Defense Panelist Q&A Cheat Sheet

| Question | Recommended Answer |
| :--- | :--- |
| **Q1: Why build a custom appointment system instead of using Google Forms or Calendly?** | *"Commercial tools like Google Forms or Calendly cannot enforce rural health unit business rules. They lack integration with attending doctor clinic schedules, cannot enforce DOH-compliant Individual Treatment Records (ITR) upon consultation, do not provide patient clinical histories, and cannot guarantee data sovereignty required for confidential patient health information under the Philippine Data Privacy Act of 2012."* |
| **Q2: What happens if two patients try to book the exact same doctor and time slot simultaneously?** | *"We enforce concurrency protection at both the controller and query levels in [`actions/book-appointment.php`](file:///C:/xampp/htdocs/rhu-appointment-system/actions/book-appointment.php). Before an appointment is committed, the backend verifies slot availability with `status NOT IN ('Cancelled','Rejected')`. If another user booked the slot a split second earlier, the transaction rejects the second attempt and displays a friendly notice to choose another time."* |
| **Q3: How does the system handle rural residents with limited digital literacy or no personal email?** | *"First, our **Public Schedule Board** ([schedule.php](file:///C:/xampp/htdocs/rhu-appointment-system/schedule.php)) allows walk-in patients or Barangay Health Workers (BHWs) to check available slots without requiring credentials. Second, family members or BHWs can register and book on behalf of dependent patients using their municipal contact details."* |
| **Q4: How do you protect confidential medical records from unauthorized access?** | *"Security is multi-layered: 1) Session guards (`requireLogin('patient')` and `requireLogin('admin')`) in [`config/auth.php`](file:///C:/xampp/htdocs/rhu-appointment-system/config/auth.php) prevent direct URL access. 2) In medical history and printing endpoints ([views/user/print-medical-history.php](file:///C:/xampp/htdocs/rhu-appointment-system/views/user/print-medical-history.php)), the system verifies that `patient_id` matches `$_SESSION['patient']['id']`—blocking horizontal privilege escalation. 3) Passwords use bcrypt hashing, and all database interactions use parameterized PDO statements."* |
| **Q5: Why did you require clinical consultation details (vitals, diagnosis, treatment) before marking an appointment Completed?** | *"In Philippine public health facilities, an appointment is not merely an administrative event—it is a medical encounter. Requiring vitals, chief complaints, diagnosis, and treatment directly satisfies DOH recording protocols and immediately generates the official Individual Treatment Record (ITR), ensuring that patient medical history is maintained continuously across visits."* |
| **Q6: How does the system prevent brute-force attacks on user and admin logins?** | *"Both [`actions/login.php`](file:///C:/xampp/htdocs/rhu-appointment-system/actions/login.php) and [`actions/admin/login.php`](file:///C:/xampp/htdocs/rhu-appointment-system/actions/admin/login.php) monitor failed attempts. After 3 incorrect credentials, the account is temporarily locked for 30 seconds. On the frontend, a live JavaScript countdown disables input fields and the submit button, protecting the server against automated credential stuffing."* |
| **Q7: What happens if a doctor suddenly becomes unavailable on their scheduled clinic day?** | *"Administrators can immediately toggle the doctor's availability switch in [views/admin/doctors.php](file:///C:/xampp/htdocs/rhu-appointment-system/views/admin/doctors.php). Once toggled to inactive, that doctor is instantly hidden from patient booking options. For existing bookings on that date, staff can open [views/admin/appointments.php](file:///C:/xampp/htdocs/rhu-appointment-system/views/admin/appointments.php) to reschedule patients or reassign them to an alternate physician, automatically emailing patients the revised schedule."* |
| **Q8: How does the system handle database synchronization when an appointment status changes?** | *"All status modifications flow through [`actions/admin/update-appointment.php`](file:///C:/xampp/htdocs/rhu-appointment-system/actions/admin/update-appointment.php) or [`actions/cancel-appointment.php`](file:///C:/xampp/htdocs/rhu-appointment-system/actions/cancel-appointment.php). Every status shift validates allowed state transitions (`Pending` -> `Approved`/`Rejected`, `Approved` -> `Completed`/`Cancelled`), writes an immutable entry into `appointment_logs`, updates the appointment record, and triggers the corresponding email template in [`config/mailer.php`](file:///C:/xampp/htdocs/rhu-appointment-system/config/mailer.php)."* |

---

## 💡 Pro-Tips for Defense Day

1. **Dual-Browser Split Screen:** Open the Admin portal in **Window 1** (left half) and the Patient portal in **Window 2 (Incognito)** (right half). When you submit a booking as a patient, refresh the admin screen to demonstrate the instant appearance of the new request.
2. **Highlight Localized Rural Realities:** Remind the panel that this system was designed for the **Municipality of Rizal, Cagayan**, where patients often travel from distant barangays like Brgy. Kalinawan or San Jose. Emphasize how eliminating unnecessary trips saves rural families money and avoids health risks.
3. **Showcase the DOH ITR Consultation Sheet:** Panelists with healthcare or government administration background are deeply impressed by the printable **Individual Treatment Record (ITR)** because it proves the system was designed around authentic government health forms rather than generic templates.
4. **Offline / Network Failsafe:** PHPMailer operations are protected with `try/catch` blocks so that even if the presentation venue experiences intermittent internet connectivity, appointment bookings and status changes continue to succeed smoothly without throwing fatal errors.
