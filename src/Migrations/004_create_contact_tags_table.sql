CREATE TABLE IF NOT EXISTS contact_tags (
    name VARCHAR(255) NOT NULL,
    contact_id INT NOT NULL,
    FOREIGN KEY (contact_id) REFERENCES contacts(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP On Update CURRENT_TIMESTAMP
)