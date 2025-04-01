-- Add weight column to audit_sections table
ALTER TABLE audit_sections
ADD COLUMN weight DECIMAL(5,2) NOT NULL DEFAULT 1.00 AFTER description; 