-- ============================================================
-- Migration 001: login lockout + OTP password reset
-- Run against the existing rhu_rizal database.
-- ============================================================

USE rhu_rizal;

ALTER TABLE users
  ADD COLUMN failed_attempts INT NOT NULL DEFAULT 0,
  ADD COLUMN locked_until DATETIME NULL;

ALTER TABLE admin_users
  ADD COLUMN failed_attempts INT NOT NULL DEFAULT 0,
  ADD COLUMN locked_until DATETIME NULL;

CREATE TABLE IF NOT EXISTS password_resets (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  account_type ENUM('patient','admin') NOT NULL,
  account_id   INT NOT NULL,
  otp_hash     VARCHAR(255) NOT NULL,
  attempts     INT NOT NULL DEFAULT 0,
  expires_at   DATETIME NOT NULL,
  verified_at  DATETIME NULL,
  created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
