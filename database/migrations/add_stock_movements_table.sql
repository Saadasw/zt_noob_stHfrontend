-- Add stock_movements table for inventory audit trail
CREATE TABLE IF NOT EXISTS `stock_movements` (
  `id` varchar(255) NOT NULL,
  `inventory_id` varchar(255) NOT NULL,
  `medicine_id` varchar(255) NOT NULL,
  `branch_id` varchar(255) NOT NULL,
  `movement_type` ENUM('add', 'dispense', 'adjust', 'expired', 'return') NOT NULL,
  `quantity_change` int NOT NULL,  -- positive for add, negative for dispense
  `quantity_before` int NOT NULL,
  `quantity_after` int NOT NULL,
  `batch_number` varchar(100) DEFAULT NULL,
  `reference_id` varchar(255) DEFAULT NULL,  -- prescription_id for dispense, or other ref
  `reason` varchar(255) DEFAULT NULL,
  `performed_by` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `inventory_id` (`inventory_id`),
  KEY `medicine_id` (`medicine_id`),
  KEY `branch_id` (`branch_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
