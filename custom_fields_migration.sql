-- Migration for Custom Fields
CREATE TABLE IF NOT EXISTS lead_custom_fields (
    id INT AUTO_INCREMENT PRIMARY KEY,
    organization_id INT NOT NULL,
    field_name VARCHAR(100) NOT NULL,
    field_label VARCHAR(100) NOT NULL,
    field_type ENUM('text', 'number', 'date', 'select') DEFAULT 'text',
    field_options TEXT NULL, -- JSON or comma-separated for 'select'
    is_required TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS lead_custom_data (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lead_id INT NOT NULL,
    field_id INT NOT NULL,
    field_value TEXT NULL,
    FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE CASCADE,
    FOREIGN KEY (field_id) REFERENCES lead_custom_fields(id) ON DELETE CASCADE
);
