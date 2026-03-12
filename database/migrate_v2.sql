-- Migration v2: remove rooms dependency, update residents fields
-- IMPORTANT: Review before running on production.

-- 1) Backup (recommended)
-- CREATE TABLE residents_backup AS SELECT * FROM residents;

-- 2) Drop foreign key to rooms if present
ALTER TABLE residents DROP FOREIGN KEY fk_residents_room;

-- 3) Add new columns
ALTER TABLE residents
    ADD COLUMN date_of_birth DATE NULL AFTER full_name,
    ADD COLUMN room_number VARCHAR(20) NULL AFTER gender,
    ADD COLUMN bed_number VARCHAR(20) NULL AFTER room_number,
    ADD COLUMN guardian_name VARCHAR(150) NULL AFTER bed_number,
    ADD COLUMN guardian_phone VARCHAR(30) NULL AFTER guardian_name,
    ADD COLUMN alternate_contact_number VARCHAR(30) NULL AFTER guardian_phone,
    ADD COLUMN monthly_fee DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER alternate_contact_number,
    ADD COLUMN joining_date DATE NOT NULL DEFAULT (CURRENT_DATE) AFTER monthly_fee,
    MODIFY COLUMN status ENUM('Active', 'Deceased', 'Discharged') NOT NULL DEFAULT 'Active';

-- 4) (Optional) Migrate admission_date -> joining_date
UPDATE residents SET joining_date = admission_date WHERE admission_date IS NOT NULL;

-- 5) (Optional) Migrate contact_number/emergency_contact to new fields
UPDATE residents
SET guardian_phone = emergency_contact
WHERE guardian_phone IS NULL AND emergency_contact IS NOT NULL;

UPDATE residents
SET alternate_contact_number = contact_number
WHERE alternate_contact_number IS NULL AND contact_number IS NOT NULL;

-- 6) Remove old columns
ALTER TABLE residents
    DROP COLUMN resident_identifier,
    DROP COLUMN age,
    DROP COLUMN contact_number,
    DROP COLUMN emergency_contact,
    DROP COLUMN address,
    DROP COLUMN admission_date,
    DROP COLUMN room_id;

-- 7) Drop rooms table (only after confirming nothing else needs it)
DROP TABLE rooms;

