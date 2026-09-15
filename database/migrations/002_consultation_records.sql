-- ============================================================
-- Migration 002: Consultation Records for Completed Appointments
-- RHU Rizal Online Medical Appointment System
-- ============================================================

USE rhu_rizal;

CREATE TABLE IF NOT EXISTS consultation_records (
  id                  INT AUTO_INCREMENT PRIMARY KEY,
  appointment_id      INT NOT NULL UNIQUE,
  patient_id          INT NOT NULL,
  doctor_id           INT NULL,

  -- Patient Demographics Snapshot
  patient_name        VARCHAR(100) NOT NULL,
  dob                 DATE NULL,
  age                 INT NULL,
  gender              VARCHAR(20) NULL,
  address             TEXT NULL,
  folder_no           VARCHAR(50) NULL,
  tin_no              VARCHAR(50) NULL,

  -- Consultation Details
  mode_of_transaction VARCHAR(50) NOT NULL DEFAULT 'Online appointment',
  consultation_date   DATE NOT NULL,
  consultation_time   TIME NOT NULL,
  nature_of_visit     VARCHAR(255) NULL,

  -- Vitals & Physical Measurements
  chief_complaints    TEXT NULL,
  height              VARCHAR(50) NULL,
  weight              VARCHAR(50) NULL,
  bp                  VARCHAR(50) NULL,
  rr                  VARCHAR(50) NULL,
  pr                  VARCHAR(50) NULL,
  temperature         VARCHAR(50) NULL,

  -- Clinical Assessment & Management
  history_of_illness  TEXT NULL,
  past_medical_history TEXT NULL,
  pertinent_pe        TEXT NULL,
  diagnosis           TEXT NULL,
  treatment           TEXT NULL,
  lab_findings        TEXT NULL,

  created_by          VARCHAR(100) NULL,
  created_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  CONSTRAINT fk_consultation_appointment FOREIGN KEY (appointment_id)
    REFERENCES appointments(id) ON DELETE CASCADE,
  CONSTRAINT fk_consultation_patient FOREIGN KEY (patient_id)
    REFERENCES patients(id) ON DELETE CASCADE,
  CONSTRAINT fk_consultation_doctor FOREIGN KEY (doctor_id)
    REFERENCES doctors(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE INDEX idx_consultation_patient ON consultation_records(patient_id);
CREATE INDEX idx_consultation_date ON consultation_records(consultation_date);
