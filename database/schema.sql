-- Hospital Management System - Complete Schema
-- St. George Hospital
-- Run this file in phpMyAdmin or MySQL to create all tables

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- Create and use database
CREATE DATABASE IF NOT EXISTS `stgeorgehospital`;
USE `stgeorgehospital`;

-- =====================================================
-- 1. USERS SYSTEM
-- =====================================================

CREATE TABLE `users` (
  `id` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `role` enum('admin','doctor','staff','patient') NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `deleted_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `profile_images` (
  `id` varchar(255) NOT NULL,
  `user_id` varchar(255) NOT NULL,
  `image_url` varchar(255) NOT NULL,
  `file_type` varchar(255) DEFAULT NULL,
  `is_current` tinyint(1) DEFAULT 1,
  `uploaded_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `fk_profile_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- 2. ORGANIZATION STRUCTURE
-- =====================================================

CREATE TABLE `branches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(50) NOT NULL,
  `address` varchar(500) NOT NULL,
  `city` varchar(100) NOT NULL,
  `state` varchar(100) NOT NULL,
  `postcode` varchar(20) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `deleted_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- 3. USER PROFILES
-- =====================================================

CREATE TABLE `doctor_profiles` (
  `id` varchar(255) NOT NULL,
  `user_id` varchar(255) NOT NULL,
  `branch_id` varchar(255) NOT NULL,
  `specialization` varchar(255) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `license_number` varchar(100) NOT NULL,
  `consultation_fee` float NOT NULL DEFAULT 0,
  `slot_duration` int DEFAULT 30,
  `is_available` tinyint(1) DEFAULT 1,
  `deleted_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `license_number` (`license_number`),
  KEY `user_id` (`user_id`),
  KEY `branch_id` (`branch_id`),
  CONSTRAINT `fk_doctor_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_doctor_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `staff_profiles` (
  `id` varchar(255) NOT NULL,
  `user_id` varchar(255) NOT NULL,
  `branch_id` varchar(255) NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `employee_id` varchar(100) NOT NULL,
  `designation` varchar(50) NOT NULL,
  `join_date` date DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `employee_id` (`employee_id`),
  KEY `user_id` (`user_id`),
  KEY `branch_id` (`branch_id`),
  CONSTRAINT `fk_staff_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_staff_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `patient_profiles` (
  `id` varchar(255) NOT NULL,
  `user_id` varchar(255) NOT NULL,
  `patient_id` varchar(100) NOT NULL,
  `national_id` varchar(100) DEFAULT NULL,
  `date_of_birth` date NOT NULL,
  `gender` varchar(20) NOT NULL,
  `blood_group` varchar(10) DEFAULT NULL,
  `address` varchar(500) DEFAULT NULL,
  `emergency_contact_name` varchar(255) DEFAULT NULL,
  `emergency_contact_phone` varchar(20) DEFAULT NULL,
  `allergies` text,
  `medical_history` text,
  `preferred_language` varchar(50) DEFAULT 'English',
  `consent_status` tinyint(1) DEFAULT 0,
  `consent_date` date DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `patient_id` (`patient_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `fk_patient_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- 4. SCHEDULING
-- =====================================================

CREATE TABLE `doctor_weekly_schedules` (
  `id` varchar(255) NOT NULL,
  `doctor_id` varchar(255) NOT NULL,
  `branch_id` varchar(255) NOT NULL,
  `day_of_week` tinyint NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `slot_duration` int DEFAULT 30,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `doctor_id` (`doctor_id`),
  KEY `branch_id` (`branch_id`),
  CONSTRAINT `fk_weekly_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `doctor_profiles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_weekly_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `doctor_schedule_overrides` (
  `id` varchar(255) NOT NULL,
  `doctor_id` varchar(255) NOT NULL,
  `branch_id` varchar(255) NOT NULL,
  `date` date NOT NULL,
  `type` varchar(50) NOT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `doctor_id` (`doctor_id`),
  KEY `branch_id` (`branch_id`),
  CONSTRAINT `fk_override_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `doctor_profiles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_override_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- 5. APPOINTMENTS & CLINICAL
-- =====================================================

CREATE TABLE `appointments` (
  `id` varchar(255) NOT NULL,
  `appointment_no` varchar(100) NOT NULL,
  `patient_id` varchar(255) NOT NULL,
  `doctor_id` varchar(255) NOT NULL,
  `branch_id` varchar(255) NOT NULL,
  `schedule_id` varchar(255) DEFAULT NULL,
  `appointment_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `type` varchar(50) DEFAULT 'consultation',
  `reason` text,
  `status` enum('scheduled','confirmed','checked_in','in_progress','completed','cancelled','no_show') DEFAULT 'scheduled',
  `reminder_sent` tinyint(1) DEFAULT 0,
  `cancelled_at` datetime DEFAULT NULL,
  `cancellation_reason` text,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `appointment_no` (`appointment_no`),
  KEY `patient_id` (`patient_id`),
  KEY `doctor_id` (`doctor_id`),
  KEY `branch_id` (`branch_id`),
  CONSTRAINT `fk_appt_patient` FOREIGN KEY (`patient_id`) REFERENCES `patient_profiles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_appt_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `doctor_profiles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_appt_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `medical_records` (
  `id` varchar(255) NOT NULL,
  `record_no` varchar(100) NOT NULL,
  `patient_id` varchar(255) NOT NULL,
  `doctor_id` varchar(255) NOT NULL,
  `appointment_id` varchar(255) DEFAULT NULL,
  `branch_id` varchar(255) DEFAULT NULL,
  `version` int DEFAULT 1,
  `chief_complaint` text,
  `symptoms` text,
  `diagnosis` text,
  `treatment_plan` text,
  `notes` text,
  `status` varchar(50) DEFAULT 'draft',
  `finalized_at` datetime DEFAULT NULL,
  `updated_by` varchar(255) DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `record_no` (`record_no`),
  KEY `patient_id` (`patient_id`),
  KEY `doctor_id` (`doctor_id`),
  CONSTRAINT `fk_record_patient` FOREIGN KEY (`patient_id`) REFERENCES `patient_profiles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_record_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `doctor_profiles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- 6. PHARMACY & INVENTORY
-- =====================================================

CREATE TABLE `medicines` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `generic_name` varchar(255) DEFAULT NULL,
  `code` varchar(100) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `manufacturer` varchar(255) DEFAULT NULL,
  `dosage_form` varchar(100) DEFAULT NULL,
  `strength` varchar(100) DEFAULT NULL,
  `unit_price` float NOT NULL DEFAULT 0,
  `requires_prescription` tinyint(1) DEFAULT 1,
  `is_active` tinyint(1) DEFAULT 1,
  `deleted_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `reservations` (
   `id` varchar(255) NOT NULL PRIMARY KEY
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `inventory` (
  `id` varchar(255) NOT NULL,
  `medicine_id` varchar(255) NOT NULL,
  `branch_id` varchar(255) NOT NULL,
  `quantity` int NOT NULL DEFAULT 0,
  `reorder_level` int DEFAULT 10,
  `batch_number` varchar(100) DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `last_updated` datetime DEFAULT CURRENT_TIMESTAMP,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `medicine_id` (`medicine_id`),
  KEY `branch_id` (`branch_id`),
  CONSTRAINT `fk_inv_medicine` FOREIGN KEY (`medicine_id`) REFERENCES `medicines` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_inv_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- 7. PRESCRIPTIONS
-- =====================================================

CREATE TABLE `prescriptions` (
  `id` varchar(255) NOT NULL,
  `prescription_no` varchar(100) NOT NULL,
  `medical_record_id` varchar(255) DEFAULT NULL,
  `patient_id` varchar(255) NOT NULL,
  `doctor_id` varchar(255) NOT NULL,
  `branch_id` varchar(255) DEFAULT NULL,
  `status` enum('active','partially_dispensed','fully_dispensed','expired','cancelled') DEFAULT 'active',
  `valid_until` date DEFAULT NULL,
  `notes` text,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `prescription_no` (`prescription_no`),
  KEY `patient_id` (`patient_id`),
  KEY `doctor_id` (`doctor_id`),
  CONSTRAINT `fk_rx_patient` FOREIGN KEY (`patient_id`) REFERENCES `patient_profiles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rx_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `doctor_profiles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `prescription_items` (
  `id` varchar(255) NOT NULL,
  `prescription_id` varchar(255) NOT NULL,
  `medicine_id` varchar(255) DEFAULT NULL,
  `medicine_name` varchar(255) NOT NULL,
  `dosage` varchar(100) DEFAULT NULL,
  `frequency` varchar(100) DEFAULT NULL,
  `duration` varchar(100) DEFAULT NULL,
  `quantity` int DEFAULT NULL,
  `instructions` text,
  `dispense_status` enum('pending','partial','complete') DEFAULT 'pending',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `prescription_id` (`prescription_id`),
  CONSTRAINT `fk_rxitem_rx` FOREIGN KEY (`prescription_id`) REFERENCES `prescriptions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- 8. BILLING
-- =====================================================

CREATE TABLE `bills` (
  `id` varchar(255) NOT NULL,
  `bill_no` varchar(100) NOT NULL,
  `patient_id` varchar(255) NOT NULL,
  `branch_id` varchar(255) DEFAULT NULL,
  `appointment_id` varchar(255) DEFAULT NULL,
  `total_amount` float NOT NULL DEFAULT 0,
  `paid_amount` float DEFAULT 0,
  `due_amount` float DEFAULT 0,
  `payment_status` enum('pending','partial','paid','overdue','cancelled','refunded') DEFAULT 'pending',
  `due_date` date DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `bill_no` (`bill_no`),
  KEY `patient_id` (`patient_id`),
  CONSTRAINT `fk_bill_patient` FOREIGN KEY (`patient_id`) REFERENCES `patient_profiles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `bill_items` (
  `id` varchar(255) NOT NULL,
  `bill_id` varchar(255) NOT NULL,
  `description` varchar(255) NOT NULL,
  `item_type` enum('consultation','lab_test','medicine','room','procedure','other') DEFAULT 'other',
  `quantity` int DEFAULT 1,
  `unit_price` float DEFAULT 0,
  `amount` float DEFAULT 0,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `bill_id` (`bill_id`),
  CONSTRAINT `fk_billitem_bill` FOREIGN KEY (`bill_id`) REFERENCES `bills` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `payments` (
  `id` varchar(255) NOT NULL,
  `bill_id` varchar(255) NOT NULL,
  `receipt_no` varchar(100) NOT NULL,
  `amount` float NOT NULL,
  `method` varchar(50) DEFAULT 'cash',
  `transaction_ref` varchar(255) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'completed',
  `paid_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `receipt_no` (`receipt_no`),
  KEY `bill_id` (`bill_id`),
  CONSTRAINT `fk_payment_bill` FOREIGN KEY (`bill_id`) REFERENCES `bills` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- 9. LAB TESTS
-- =====================================================

CREATE TABLE `lab_test_types` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(50) NOT NULL,
  `price` float NOT NULL DEFAULT 0,
  `description` text,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `lab_tests` (
  `id` varchar(255) NOT NULL,
  `test_no` varchar(100) NOT NULL,
  `patient_id` varchar(255) NOT NULL,
  `doctor_id` varchar(255) DEFAULT NULL,
  `test_type_id` varchar(255) NOT NULL,
  `branch_id` varchar(255) DEFAULT NULL,
  `priority` varchar(20) DEFAULT 'routine',
  `status` varchar(50) DEFAULT 'ordered',
  `result` text,
  `report_url` varchar(500) DEFAULT NULL,
  `ordered_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `completed_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `test_no` (`test_no`),
  KEY `patient_id` (`patient_id`),
  KEY `test_type_id` (`test_type_id`),
  CONSTRAINT `fk_test_patient` FOREIGN KEY (`patient_id`) REFERENCES `patient_profiles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_test_type` FOREIGN KEY (`test_type_id`) REFERENCES `lab_test_types` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

COMMIT;
