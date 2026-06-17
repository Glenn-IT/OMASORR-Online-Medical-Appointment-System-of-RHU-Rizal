-- ============================================================
-- RHU Rizal Online Medical Appointment System
-- Database Schema
-- Version: 1.0 | Created: May 6, 2026
-- ============================================================

CREATE DATABASE IF NOT EXISTS rhu_rizal
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE rhu_rizal;

-- ============================================================
-- TABLE: services
-- List of medical services offered by the RHU.
-- ============================================================
CREATE TABLE IF NOT EXISTS services (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(100) NOT NULL,
  description TEXT,
  active      TINYINT(1) NOT NULL DEFAULT 1,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: doctors
-- Doctors and their schedules.
-- ============================================================
CREATE TABLE IF NOT EXISTS doctors (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(100) NOT NULL,
  specialty   VARCHAR(100),
  schedule    VARCHAR(100),               -- e.g. "Mon-Wed-Fri"
  available   TINYINT(1) NOT NULL DEFAULT 1,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: users
-- Login credentials for patient accounts.
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  username    VARCHAR(50) NOT NULL UNIQUE,
  password    VARCHAR(255) NOT NULL,      -- bcrypt hash (password_hash)
  role        ENUM('patient') NOT NULL DEFAULT 'patient',
  status      ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: patients
-- Patient profile details (1-to-1 with users).
-- ============================================================
CREATE TABLE IF NOT EXISTS patients (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  user_id       INT NOT NULL UNIQUE,      -- FK → users.id
  patient_no    VARCHAR(10) NOT NULL UNIQUE,  -- e.g. P-001
  full_name     VARCHAR(100) NOT NULL,
  email         VARCHAR(100),
  phone         VARCHAR(20),
  address       TEXT,
  birthdate     DATE,
  gender        ENUM('Male','Female','Other'),
  blood_type    VARCHAR(5),
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_patients_user FOREIGN KEY (user_id)
    REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: appointments
-- Appointment records.
-- ============================================================
CREATE TABLE IF NOT EXISTS appointments (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  appt_no     VARCHAR(10) NOT NULL UNIQUE, -- e.g. APT-001
  patient_id  INT NOT NULL,                -- FK → patients.id
  doctor_id   INT,                         -- FK → doctors.id (nullable)
  service     VARCHAR(100),
  date        DATE NOT NULL,
  time        TIME NOT NULL,
  reason      TEXT,
  admin_note  TEXT,                       -- set by admin on Cancelled/Rejected
  status      ENUM('Pending','Approved','Rejected','Completed','Cancelled')
              NOT NULL DEFAULT 'Pending',
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_appointments_patient FOREIGN KEY (patient_id)
    REFERENCES patients(id) ON DELETE CASCADE,
  CONSTRAINT fk_appointments_doctor FOREIGN KEY (doctor_id)
    REFERENCES doctors(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: appointment_logs
-- Audit trail of every status change on an appointment.
-- ============================================================
CREATE TABLE IF NOT EXISTS appointment_logs (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  appointment_id  INT NOT NULL,             -- FK → appointments.id
  changed_by      VARCHAR(100),             -- username of actor
  old_status      VARCHAR(20),
  new_status      VARCHAR(20),
  note            TEXT,
  changed_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_logs_appointment FOREIGN KEY (appointment_id)
    REFERENCES appointments(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: admin_users
-- Admin login accounts (separate from patient users).
-- ============================================================
CREATE TABLE IF NOT EXISTS admin_users (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  username    VARCHAR(50) NOT NULL UNIQUE,
  password    VARCHAR(255) NOT NULL,       -- bcrypt hash
  full_name   VARCHAR(100),
  email       VARCHAR(100),
  phone       VARCHAR(20),
  role        VARCHAR(50) NOT NULL DEFAULT 'System Administrator',
  status      ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: holidays
-- Closed/holiday dates (replaces RHU_DATA.closedDates in JS).
-- ============================================================
CREATE TABLE IF NOT EXISTS holidays (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  date        DATE NOT NULL UNIQUE,
  name        VARCHAR(100),
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- INDEXES for common query patterns
-- ============================================================
CREATE INDEX idx_appointments_patient  ON appointments(patient_id);
CREATE INDEX idx_appointments_doctor   ON appointments(doctor_id);
CREATE INDEX idx_appointments_date     ON appointments(date);
CREATE INDEX idx_appointments_status   ON appointments(status);
CREATE INDEX idx_patients_patient_no   ON patients(patient_no);
CREATE INDEX idx_appointment_logs_appt ON appointment_logs(appointment_id);
