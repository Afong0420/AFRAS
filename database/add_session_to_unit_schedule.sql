-- Migration: Support morning (lecture) + afternoon (laboratory) sessions per day
-- Run this after the previous migrations

-- Drop old unique constraint that only allowed one slot per day
ALTER TABLE `unit_schedule` DROP INDEX `unit_day`;

-- Add session column
ALTER TABLE `unit_schedule`
  ADD COLUMN `session` enum('morning','afternoon') NOT NULL DEFAULT 'morning' AFTER `day`;

-- New unique key: one morning + one afternoon slot per day per unit
ALTER TABLE `unit_schedule`
  ADD UNIQUE KEY `unit_day_session` (`unitId`, `day`, `session`);
