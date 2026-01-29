-- Migration: Add reviewed_by and reviewed_at columns to lab_tests table
-- Run this in phpMyAdmin or MySQL to add the missing columns

ALTER TABLE `lab_tests` 
ADD COLUMN IF NOT EXISTS `reviewed_by` varchar(255) DEFAULT NULL AFTER `result`,
ADD COLUMN IF NOT EXISTS `reviewed_at` datetime DEFAULT NULL AFTER `reviewed_by`;

-- Add foreign key for reviewed_by (doctor who reviewed)
-- ALTER TABLE `lab_tests` ADD CONSTRAINT `fk_lab_reviewer` FOREIGN KEY (`reviewed_by`) REFERENCES `doctor_profiles` (`id`) ON DELETE SET NULL;
