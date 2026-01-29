-- Migration: Add new columns to lab_test_types table
-- Run this if you already have the database created

ALTER TABLE `lab_test_types` 
ADD COLUMN IF NOT EXISTS `category` varchar(100) DEFAULT NULL AFTER `price`,
ADD COLUMN IF NOT EXISTS `sample_type` varchar(100) DEFAULT NULL AFTER `category`,
ADD COLUMN IF NOT EXISTS `turnaround_hours` int DEFAULT 24 AFTER `sample_type`,
ADD COLUMN IF NOT EXISTS `fasting_required` tinyint(1) DEFAULT 0 AFTER `turnaround_hours`;

-- Note: MySQL 8.0.16+ supports IF NOT EXISTS for ADD COLUMN
-- For older versions, you may need to run each ALTER separately and ignore errors for existing columns
