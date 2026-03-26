-- Migration: Add session column to attendance table
-- Run this against your attendance_db database

ALTER TABLE `attendance`
  ADD COLUMN `session` enum('morning','afternoon','') NOT NULL DEFAULT ''
  AFTER `unit`;
