-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 24, 2026 at 02:23 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `rhu_rizal`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_users`
--

CREATE TABLE `admin_users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role` varchar(50) NOT NULL DEFAULT 'System Administrator',
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `failed_attempts` int(11) NOT NULL DEFAULT 0,
  `locked_until` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admin_users`
--

INSERT INTO `admin_users` (`id`, `username`, `password`, `full_name`, `email`, `phone`, `role`, `status`, `created_at`, `failed_attempts`, `locked_until`) VALUES
(1, 'admin', '$2y$10$KBU2yGJKATs6fgnsCt0zxuRN3b9.wbYrmVAlfeGrg.EFeXVG34SeW', 'Admin User', 'pascualmarina674@gmail.com', '09557997409', 'System Administrator', 'Active', '2026-05-06 17:15:13', 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `id` int(11) NOT NULL,
  `appt_no` varchar(10) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `doctor_id` int(11) DEFAULT NULL,
  `service` varchar(100) DEFAULT NULL,
  `date` date NOT NULL,
  `time` time NOT NULL,
  `reason` text DEFAULT NULL,
  `status` enum('Pending','Approved','Rejected','Completed','Cancelled') NOT NULL DEFAULT 'Pending',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `admin_note` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `appointment_logs`
--

CREATE TABLE `appointment_logs` (
  `id` int(11) NOT NULL,
  `appointment_id` int(11) NOT NULL,
  `changed_by` varchar(100) DEFAULT NULL,
  `old_status` varchar(20) DEFAULT NULL,
  `new_status` varchar(20) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `changed_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `doctors`
--

CREATE TABLE `doctors` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `specialty` varchar(100) DEFAULT NULL,
  `schedule` varchar(100) DEFAULT NULL,
  `available` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `doctors`
--

INSERT INTO `doctors` (`id`, `name`, `specialty`, `schedule`, `available`, `created_at`) VALUES
(1, 'Dr. Maria Santos', 'General Medicine', 'Mon-Wed-Fri', 1, '2026-05-06 17:15:13');

-- --------------------------------------------------------

--
-- Table structure for table `holidays`
--

CREATE TABLE `holidays` (
  `id` int(11) NOT NULL,
  `date` date NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `holidays`
--

INSERT INTO `holidays` (`id`, `date`, `name`, `created_at`) VALUES
(1, '2026-04-09', 'Araw ng Kagitingan', '2026-05-06 17:15:14'),
(2, '2026-04-01', 'Good Friday', '2026-05-06 17:15:14');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `account_type` enum('patient','admin') NOT NULL,
  `account_id` int(11) NOT NULL,
  `otp_hash` varchar(255) NOT NULL,
  `attempts` int(11) NOT NULL DEFAULT 0,
  `expires_at` datetime NOT NULL,
  `verified_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `password_resets`
--

INSERT INTO `password_resets` (`id`, `account_type`, `account_id`, `otp_hash`, `attempts`, `expires_at`, `verified_at`, `created_at`) VALUES
(3, 'patient', 11, '$2y$10$jygwlAS2fu1YzGxKgkTTKODslc3.dJIouqUHKijUUIJzTuOR638nm', 0, '2026-07-12 22:23:30', '2026-07-12 22:13:47', '2026-07-12 22:13:30'),
(4, 'admin', 1, '$2y$10$3HaR0E0Ht5DNKI3gk8XVc.MgqEWMyen/lU69p7u5w7EpsaK.ggRsa', 0, '2026-07-12 22:26:05', '2026-07-12 22:17:41', '2026-07-12 22:16:05'),
(5, 'patient', 11, '$2y$10$nYc4wU3hWEckJepljClThe2fLnd0hMQZyMPzcoitJ0UQLPOJ8cV72', 0, '2026-07-16 01:33:11', NULL, '2026-07-16 01:23:11');

-- --------------------------------------------------------

--
-- Table structure for table `patients`
--

CREATE TABLE `patients` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `patient_no` varchar(10) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `birthdate` date DEFAULT NULL,
  `gender` enum('Male','Female','Other') DEFAULT NULL,
  `blood_type` varchar(5) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `patients`
--

INSERT INTO `patients` (`id`, `user_id`, `patient_no`, `full_name`, `email`, `phone`, `address`, `birthdate`, `gender`, `blood_type`, `created_at`) VALUES
(9, 9, 'P-001', 'Glensn Pagurayan', 'glesnn@gmail.com', '09557997921', 'Samples', '2000-08-23', 'Male', 'A-', '2026-07-07 19:43:48'),
(10, 10, 'P-002', 'Samuel Ilac', 'sam@gmail.com', '09559746545', 'Sample', '2000-08-23', 'Male', 'A+', '2026-07-09 15:25:05'),
(11, 11, 'P-003', 'Glenard Pagurayan', 'glenard2308@gmail.com', '09557997409', 'Sample', '2000-08-23', 'Male', 'Unkno', '2026-07-12 21:46:19'),
(15, 15, 'P-004', 'Lea Pagurayan', 'lbariuangasmen@gmail.com', '09579974092', 'sample', '2000-08-23', 'Male', 'A+', '2026-07-16 01:25:30');

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

CREATE TABLE `services` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `services`
--

INSERT INTO `services` (`id`, `name`, `description`, `active`, `created_at`) VALUES
(1, 'General Consultation', NULL, 1, '2026-05-06 17:15:13'),
(2, 'Prenatal Care', NULL, 1, '2026-05-06 17:15:13'),
(3, 'Pediatrics', NULL, 1, '2026-05-06 17:15:13'),
(4, 'Dental Services', NULL, 1, '2026-05-06 17:15:13'),
(5, 'Family Planning', NULL, 1, '2026-05-06 17:15:13'),
(6, 'Immunization', NULL, 1, '2026-05-06 17:15:13'),
(7, 'Laboratory Services', NULL, 1, '2026-05-06 17:15:13'),
(8, 'TB-DOTS Program', NULL, 1, '2026-05-06 17:15:13'),
(9, 'Nutrition Counseling', NULL, 1, '2026-05-06 17:15:13'),
(10, 'Eye Care', NULL, 1, '2026-05-06 17:15:13');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('patient') NOT NULL DEFAULT 'patient',
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `failed_attempts` int(11) NOT NULL DEFAULT 0,
  `locked_until` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`, `status`, `created_at`, `failed_attempts`, `locked_until`) VALUES
(9, 'glenn', '$2y$10$hldJeTTWmP34XyQpGmKCZ.fWhzxsaeBj3CpDgH628efzyQr9Fnwfq', 'patient', 'Active', '2026-07-07 19:43:48', 0, NULL),
(10, 'sams', '$2y$10$mZDvnLci8f4S4HMoEQ4HyOoWtPPQwT.gIVIG91rzzzMvG.ezxuYce', 'patient', 'Active', '2026-07-09 15:25:05', 0, NULL),
(11, 'glenard', '$2y$10$CirmL6X3fDtKvTrx4Pgphe8nMpciw5wbogmiH8pDHpzG0O5FhOG/i', 'patient', 'Active', '2026-07-12 21:46:19', 0, '2026-07-16 01:25:08'),
(15, 'lhei', '$2y$10$10aMttyOjN7gmFcgzptd7eOje0OEKTyoTAigbUBtH1NgUD902J2BW', 'patient', 'Active', '2026-07-16 01:25:30', 0, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_users`
--
ALTER TABLE `admin_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `appt_no` (`appt_no`),
  ADD KEY `idx_appointments_patient` (`patient_id`),
  ADD KEY `idx_appointments_doctor` (`doctor_id`),
  ADD KEY `idx_appointments_date` (`date`),
  ADD KEY `idx_appointments_status` (`status`);

--
-- Indexes for table `appointment_logs`
--
ALTER TABLE `appointment_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_appointment_logs_appt` (`appointment_id`);

--
-- Indexes for table `doctors`
--
ALTER TABLE `doctors`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `holidays`
--
ALTER TABLE `holidays`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `date` (`date`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `patients`
--
ALTER TABLE `patients`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD UNIQUE KEY `patient_no` (`patient_no`),
  ADD KEY `idx_patients_patient_no` (`patient_no`);

--
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_users`
--
ALTER TABLE `admin_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `appointment_logs`
--
ALTER TABLE `appointment_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `doctors`
--
ALTER TABLE `doctors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `holidays`
--
ALTER TABLE `holidays`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `patients`
--
ALTER TABLE `patients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `fk_appointments_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_appointments_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `appointment_logs`
--
ALTER TABLE `appointment_logs`
  ADD CONSTRAINT `fk_logs_appointment` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `patients`
--
ALTER TABLE `patients`
  ADD CONSTRAINT `fk_patients_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
