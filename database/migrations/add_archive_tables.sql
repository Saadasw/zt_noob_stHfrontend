-- Archive Tables Migration
-- Creates archive versions of appointments, bills, payments, and lab_tests tables
-- Archive tables store historical records that have been moved from active tables
-- They have NO foreign key constraints (data is self-contained after archiving)

-- 1. Appointments Archive
CREATE TABLE IF NOT EXISTS `appointments_archive` (
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
  `status` varchar(50) DEFAULT 'scheduled',
  `reminder_sent` tinyint(1) DEFAULT 0,
  `cancelled_at` datetime DEFAULT NULL,
  `cancellation_reason` text,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `archived_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `archived_by` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `appointment_no` (`appointment_no`),
  KEY `patient_id` (`patient_id`),
  KEY `appointment_date` (`appointment_date`),
  KEY `archived_at` (`archived_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 2. Bills Archive
CREATE TABLE IF NOT EXISTS `bills_archive` (
  `id` varchar(255) NOT NULL,
  `bill_no` varchar(100) NOT NULL,
  `patient_id` varchar(255) NOT NULL,
  `branch_id` varchar(255) DEFAULT NULL,
  `appointment_id` varchar(255) DEFAULT NULL,
  `total_amount` float NOT NULL DEFAULT 0,
  `paid_amount` float DEFAULT 0,
  `due_amount` float DEFAULT 0,
  `payment_status` varchar(50) DEFAULT 'pending',
  `due_date` date DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `archived_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `archived_by` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `bill_no` (`bill_no`),
  KEY `patient_id` (`patient_id`),
  KEY `archived_at` (`archived_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 3. Payments Archive
CREATE TABLE IF NOT EXISTS `payments_archive` (
  `id` varchar(255) NOT NULL,
  `bill_id` varchar(255) NOT NULL,
  `receipt_no` varchar(100) NOT NULL,
  `amount` float NOT NULL,
  `method` varchar(50) DEFAULT 'cash',
  `transaction_ref` varchar(255) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'completed',
  `paid_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `archived_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `archived_by` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `bill_id` (`bill_id`),
  KEY `receipt_no` (`receipt_no`),
  KEY `archived_at` (`archived_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 4. Lab Tests Archive
CREATE TABLE IF NOT EXISTS `lab_tests_archive` (
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
  `ordered_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `archived_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `archived_by` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `test_no` (`test_no`),
  KEY `patient_id` (`patient_id`),
  KEY `archived_at` (`archived_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
