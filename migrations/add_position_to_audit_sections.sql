-- Add position column to audit_sections table
ALTER TABLE audit_sections
ADD COLUMN position INT NOT NULL DEFAULT 0 AFTER weight; 