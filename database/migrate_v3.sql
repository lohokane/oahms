-- Migration v3: add photo and document fields for residents

ALTER TABLE residents
    ADD COLUMN photo_path VARCHAR(255) NULL AFTER status,
    ADD COLUMN document_path VARCHAR(255) NULL AFTER photo_path,
    ADD COLUMN document_name VARCHAR(255) NULL AFTER document_path;

