-- Migration to add file upload support columns to lab_tests table
-- Run via database/run_migrations.php

ALTER TABLE `lab_tests`
ADD COLUMN `file_type` VARCHAR(50) DEFAULT NULL AFTER `report_url`,
ADD COLUMN `file_name` VARCHAR(255) DEFAULT NULL AFTER `file_type`,
ADD COLUMN `file_size` INT DEFAULT NULL AFTER `file_name`;

-- Ensure report_url exists (in case it was missing in some deployments)
-- Note: MySQL 5.7+ doesn't support IF NOT EXISTS in ADD COLUMN easily in one statement without procedure, 
-- but this script might fail safely if column exists or we can use a separate statement.
-- However, since we see it in schema, we assume it's there or this is a fresh add.
-- If it fails because report_url exists, the user can ignore it or we can comment it out.
-- But standard ALTER ADD COLUMN will partial fail/stop if column exists. 
-- Let's just add the NEW columns which we are sure are new.

-- If you deleted the table and re-ran schema.sql, you already have report_url.
-- If you have an OLD database, you might need report_url.
-- Safest involves checking, but for this simple migration script:

-- UNCOMMENT THE LINE BELOW IF YOU GET "Unknown column 'report_url'" error in your app (but don't run it if you have it)
-- ALTER TABLE `lab_tests` ADD COLUMN `report_url` VARCHAR(500) DEFAULT NULL;
