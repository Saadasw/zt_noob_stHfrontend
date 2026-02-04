-- Branch Admin Migration
-- Run this to add branch_admin role and create branch_admin_profiles table

-- Step 1: Add branch_admin to users.role ENUM
ALTER TABLE `users` MODIFY `role` ENUM('admin','branch_admin','doctor','staff','patient') NOT NULL;

-- Step 2: Create branch_admin_profiles table
CREATE TABLE IF NOT EXISTS `branch_admin_profiles` (
  `id` varchar(255) NOT NULL,
  `user_id` varchar(255) NOT NULL,
  `branch_id` varchar(255) NOT NULL,
  `employee_id` varchar(100) NOT NULL,
  `designation` varchar(100) DEFAULT 'Branch Administrator',
  `deleted_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  UNIQUE KEY `employee_id` (`employee_id`),
  KEY `branch_id` (`branch_id`),
  CONSTRAINT `fk_branch_admin_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_branch_admin_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Step 3: Insert a sample branch admin (password: admin123)
-- You can run this to create a test branch admin
-- INSERT INTO users (id, email, password, name, role, phone, is_active) VALUES 
-- ('BA-001', 'branchadmin@stgeorge.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Melbourne Branch Admin', 'branch_admin', '03-9876-5432', 1);
-- INSERT INTO branch_admin_profiles (id, user_id, branch_id, employee_id, designation) VALUES 
-- ('BAP-001', 'BA-001', 'BR-MEL-01', 'EMP-BA-001', 'Branch Administrator');
