<?php
require_once 'config/db.php';

// 1. Create projects table with organization_id
$res1 = mysqli_query($conn, "CREATE TABLE IF NOT EXISTS projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    organization_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY (organization_id, name)
)");

// 2. Create user_projects mapping table
$res2 = mysqli_query($conn, "CREATE TABLE IF NOT EXISTS user_projects (
    user_id INT NOT NULL,
    project_id INT NOT NULL,
    PRIMARY KEY (user_id, project_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
)");

// 3. Add project_id to leads
$check = mysqli_query($conn, "SHOW COLUMNS FROM leads LIKE 'project_id'");
if (mysqli_num_rows($check) == 0) {
    mysqli_query($conn, "ALTER TABLE leads ADD COLUMN project_id INT NULL");
    mysqli_query($conn, "ALTER TABLE leads ADD FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL");
}

if ($res1 && $res2) {
    echo "Database updated successfully with Projects and User Mapping.";
} else {
    echo "Error updating database: " . mysqli_error($conn);
}
?>
