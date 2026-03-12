-- Migration v4: support multiple documents per resident

CREATE TABLE IF NOT EXISTS residents_documents (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    resident_id INT UNSIGNED NOT NULL,
    document_path VARCHAR(255) NOT NULL,
    document_name VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_res_docs_resident FOREIGN KEY (resident_id) REFERENCES residents(id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Optional one-time migration: move existing single document into the new table
INSERT INTO residents_documents (resident_id, document_path, document_name)
SELECT id, document_path, COALESCE(document_name, document_path)
FROM residents
WHERE document_path IS NOT NULL AND document_path <> '';

