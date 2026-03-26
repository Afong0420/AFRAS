-- Migration: Add lectureID column to unit table
ALTER TABLE `unit` ADD COLUMN `lectureID` int(10) DEFAULT NULL AFTER `venueID`;
ALTER TABLE `unit` ADD CONSTRAINT `fk_unit_lecture` FOREIGN KEY (`lectureID`) REFERENCES `lecture` (`Id`) ON DELETE SET NULL ON UPDATE CASCADE;
