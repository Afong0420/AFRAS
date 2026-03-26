-- ============================================================
-- Migration: Per-day schedule times for units
-- Run this against your attendance_db database
-- ============================================================

-- New table: stores individual start/end times per day per unit
CREATE TABLE IF NOT EXISTS `unit_schedule` (
  `Id`        int(10) NOT NULL AUTO_INCREMENT,
  `unitId`    int(10) NOT NULL,
  `day`       enum('Mon','Tue','Wed','Thu','Fri') NOT NULL,
  `startTime` time NOT NULL,
  `endTime`   time NOT NULL,
  PRIMARY KEY (`Id`),
  UNIQUE KEY `unit_day` (`unitId`, `day`),
  CONSTRAINT `fk_unit_schedule_unit` FOREIGN KEY (`unitId`) REFERENCES `unit`(`Id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Keep old startTime/endTime columns on unit as a fallback (do NOT drop them)
-- scheduleDays column is still used to know which days are active
