-- ============================================================
-- RHU Rizal Online Medical Appointment System
-- Seed Data (converted from mockData.js)
-- Version: 1.0 | Created: May 6, 2026
-- ============================================================
-- Run AFTER schema.sql.
-- Usage: mysql -u root rhu_rizal < seed.sql
-- ============================================================

USE rhu_rizal;

-- ============================================================
-- SERVICES
-- ============================================================
INSERT INTO services (id, name) VALUES
  (1,  'General Consultation'),
  (2,  'Prenatal Care'),
  (3,  'Pediatrics'),
  (4,  'Dental Services'),
  (5,  'Family Planning'),
  (6,  'Immunization'),
  (7,  'Laboratory Services'),
  (8,  'TB-DOTS Program'),
  (9,  'Nutrition Counseling'),
  (10, 'Eye Care');

-- ============================================================
-- DOCTORS
-- ============================================================
INSERT INTO doctors (id, name, specialty, schedule, available) VALUES
  (1, 'Dr. Maria Santos',    'General Medicine', 'Mon-Wed-Fri', 1),
  (2, 'Dr. Jose Reyes',      'Pediatrics',       'Tue-Thu',     1),
  (3, 'Dr. Ana Dela Cruz',   'OB-Gynecology',    'Mon-Thu',     1),
  (4, 'Dr. Carlos Mendoza',  'Dentistry',        'Wed-Fri',     1),
  (5, 'Dr. Rosa Flores',     'Internal Medicine','Tue-Fri',     0),
  (6, 'Dr. Eduardo Bautista','Ophthalmology',    'Mon-Wed',     1);

-- ============================================================
-- ADMIN USERS
-- Passwords hashed using password_hash('admin123', PASSWORD_BCRYPT).
-- Regenerate in PHP if needed:  echo password_hash('admin123', PASSWORD_BCRYPT);
-- ============================================================
INSERT INTO admin_users (id, username, password, full_name, email, phone, role) VALUES
  (1, 'admin',
   '$2y$10$nUuq4kD2nHTy1.bX8vbqrOwI7K6JJ0XU6zA5/zwYoMzVJncbBmMS6',
   'Admin User',
   'admin@rhurjzal.gov.ph',
   '+63 917 000 0000',
   'System Administrator');

-- ============================================================
-- USERS  (patient login accounts)
-- Passwords hashed: password_hash('patient123', PASSWORD_BCRYPT)
-- ============================================================
INSERT INTO users (id, username, password, role, status, created_at) VALUES
  (1, 'juandc',      '$2y$10$qb4LFN9.oHT/Oxjk2PramOmTI648gwaionD7uZrlNJQ1KGG4iHt2W',  'patient', 'Active',   '2025-01-10 08:00:00'),
  (2, 'mcsantos',    '$2y$10$qb4LFN9.oHT/Oxjk2PramOmTI648gwaionD7uZrlNJQ1KGG4iHt2W',  'patient', 'Active',   '2025-02-14 09:00:00'),
  (3, 'rmangubat',   '$2y$10$qb4LFN9.oHT/Oxjk2PramOmTI648gwaionD7uZrlNJQ1KGG4iHt2W',  'patient', 'Inactive', '2025-03-01 10:00:00'),
  (4, 'lfernandez',  '$2y$10$qb4LFN9.oHT/Oxjk2PramOmTI648gwaionD7uZrlNJQ1KGG4iHt2W',  'patient', 'Active',   '2025-03-20 11:00:00');

-- ============================================================
-- PATIENTS  (profile details, linked to users)
-- ============================================================
INSERT INTO patients (id, user_id, patient_no, full_name, email, phone, address, birthdate, gender, blood_type, created_at) VALUES
  (1, 1, 'P-001', 'Juan dela Cruz',       'juan@example.com',    '09171234567', 'Brgy. Rizal, Rizal',       '1990-05-15', 'Male',   'O+', '2025-01-10 08:00:00'),
  (2, 2, 'P-002', 'Maria Clara Santos',   'maria@example.com',   '09187654321', 'Brgy. Poblacion, Rizal',   '1985-09-22', 'Female', 'A+', '2025-02-14 09:00:00'),
  (3, 3, 'P-003', 'Roberto Mangubat',     'roberto@example.com', '09201112233', 'Brgy. San Jose, Rizal',    '1978-03-08', 'Male',   'B+', '2025-03-01 10:00:00'),
  (4, 4, 'P-004', 'Liza Fernandez',       'liza@example.com',    '09334455667', 'Brgy. Kalinawan, Rizal',   '1995-11-30', 'Female', 'AB+','2025-03-20 11:00:00');

-- ============================================================
-- APPOINTMENTS
-- doctor_id FKs match the doctors table above.
-- ============================================================
INSERT INTO appointments (id, appt_no, patient_id, doctor_id, service, date, time, reason, status, created_at) VALUES
  (1, 'APT-001', 1, 1, 'General Consultation', '2026-04-15', '09:00:00', 'Routine check-up and blood pressure monitoring',          'Pending',   '2026-04-10 08:00:00'),
  (2, 'APT-002', 2, 3, 'Prenatal Care',         '2026-04-16', '10:30:00', 'Monthly prenatal check-up, 6 months pregnant',            'Approved',  '2026-04-11 09:00:00'),
  (3, 'APT-003', 3, 4, 'Dental Services',        '2026-04-14', '14:00:00', 'Tooth extraction',                                        'Completed', '2026-04-08 07:00:00'),
  (4, 'APT-004', 4, 3, 'Family Planning',        '2026-04-17', '11:00:00', 'Family planning consultation',                            'Pending',   '2026-04-12 10:00:00'),
  (5, 'APT-005', 1, 1, 'Laboratory Services',    '2026-03-20', '08:00:00', 'Complete blood count and urinalysis',                     'Completed', '2026-03-15 08:00:00'),
  (6, 'APT-006', 2, 2, 'Immunization',           '2026-04-18', '09:30:00', 'Child immunization schedule',                             'Approved',  '2026-04-13 09:00:00'),
  (7, 'APT-007', 3, 1, 'TB-DOTS Program',        '2026-04-20', '08:30:00', 'TB medication refill and follow-up',                      'Pending',   '2026-04-13 10:00:00'),
  (8, 'APT-008', 4, 6, 'Eye Care',               '2026-03-10', '13:00:00', 'Blurry vision and eye strain',                            'Completed', '2026-03-05 08:00:00');

-- ============================================================
-- APPOINTMENT LOGS  (seed a few sample audit entries)
-- ============================================================
INSERT INTO appointment_logs (appointment_id, changed_by, old_status, new_status, note, changed_at) VALUES
  (2, 'admin', 'Pending',  'Approved',  'Approved by admin.',              '2026-04-11 14:00:00'),
  (3, 'admin', 'Pending',  'Approved',  'Approved by admin.',              '2026-04-08 10:00:00'),
  (3, 'admin', 'Approved', 'Completed', 'Patient attended — marked done.', '2026-04-14 16:00:00'),
  (5, 'admin', 'Pending',  'Approved',  'Approved by admin.',              '2026-03-15 11:00:00'),
  (5, 'admin', 'Approved', 'Completed', 'Patient attended — marked done.', '2026-03-20 12:00:00'),
  (6, 'admin', 'Pending',  'Approved',  'Approved by admin.',              '2026-04-13 15:00:00'),
  (8, 'admin', 'Pending',  'Approved',  'Approved by admin.',              '2026-03-05 09:00:00'),
  (8, 'admin', 'Approved', 'Completed', 'Patient attended — marked done.', '2026-03-10 15:00:00');

-- ============================================================
-- HOLIDAYS  (from RHU_DATA.closedDates)
-- ============================================================
INSERT INTO holidays (date, name) VALUES
  ('2026-04-09', 'Araw ng Kagitingan'),
  ('2026-04-01', 'Good Friday');

-- ============================================================
-- Reset AUTO_INCREMENT counters to continue after seed data
-- ============================================================
ALTER TABLE services        AUTO_INCREMENT = 11;
ALTER TABLE doctors         AUTO_INCREMENT = 7;
ALTER TABLE admin_users     AUTO_INCREMENT = 2;
ALTER TABLE users           AUTO_INCREMENT = 5;
ALTER TABLE patients        AUTO_INCREMENT = 5;
ALTER TABLE appointments    AUTO_INCREMENT = 9;
ALTER TABLE appointment_logs AUTO_INCREMENT = 9;
ALTER TABLE holidays        AUTO_INCREMENT = 3;
