-- Migration: Add billing links to lab_tests and prescriptions
-- Run this after existing migrations

-- Add bill_item_id to lab_tests (links completed test to bill item)
ALTER TABLE lab_tests ADD COLUMN bill_item_id VARCHAR(255) DEFAULT NULL;

-- Add bill_item_id to prescriptions (links dispensed prescription to bill item)
ALTER TABLE prescriptions ADD COLUMN bill_item_id VARCHAR(255) DEFAULT NULL;

-- Note: These columns track whether the item has been billed
-- NULL = not yet billed
-- Value = ID of the bill_item that was created
