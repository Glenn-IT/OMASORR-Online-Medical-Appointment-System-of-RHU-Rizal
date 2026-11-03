-- ============================================================
-- Migration 003: Profile Pictures, Announcements, and Sample Doctors
-- ============================================================

-- 1. Profile photos for patients and admin_users
ALTER TABLE patients ADD COLUMN profile_photo VARCHAR(255) NULL AFTER blood_type;
ALTER TABLE admin_users ADD COLUMN profile_photo VARCHAR(255) NULL AFTER phone;

-- 2. Announcements table for service/doctor unavailability and general notices
CREATE TABLE IF NOT EXISTS announcements (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(150) NOT NULL,
  message TEXT NOT NULL,
  type ENUM('doctor_unavailable', 'service_unavailable', 'general_advisory') NOT NULL DEFAULT 'general_advisory',
  doctor_id INT NULL,
  service_id INT NULL,
  start_date DATE NULL,
  end_date DATE NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_by INT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE SET NULL,
  FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 3. Insert diverse sample doctors matching RHU services
-- Check if doctors exist before inserting or insert new ones
INSERT INTO doctors (name, specialty, schedule, available) VALUES
('Dr. Roberto Cruz', 'Eye Care / Ophthalmology', 'Mon-Wed-Fri', 1),
('Dr. Elena Reyes', 'Pediatrics', 'Mon-Tue-Thu-Fri', 1),
('Dr. Jose Dizon', 'Dental Services', 'Tue-Thu-Fri', 1),
('Dr. Carmela Mendoza', 'Prenatal & OB-GYN', 'Mon-Wed-Thu', 1),
('Dr. Antonio Ramos', 'TB-DOTS & Internal Medicine', 'Mon-Tue-Wed-Thu-Fri', 1),
('Dr. Patricia Gomez', 'Nutrition & Family Planning', 'Mon-Wed-Fri', 1);
