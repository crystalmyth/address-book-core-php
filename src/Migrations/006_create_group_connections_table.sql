CREATE TABLE IF NOT EXISTS group_connections (
    parent_group_id INT,
    child_group_id INT,
    FOREIGN KEY (parent_group_id) REFERENCES groups(id),
    FOREIGN KEY (child_group_id) REFERENCES groups(id),
    PRIMARY KEY (parent_group_id, child_group_id) 
)