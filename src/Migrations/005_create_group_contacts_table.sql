CREATE TABLE IF NOT EXISTS group_contacts (
    group_id INT,
    contact_id INT,
    inherited BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (group_id) REFERENCES groups(id),
    FOREIGN KEY (contact_id) REFERENCES contacts(id),
    PRIMARY KEY (group_id, contact_id) 
)