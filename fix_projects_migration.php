<?php
include 'config/db.php';

echo "Starting migration fix...\n";

// 1. Fixing Projects Table
echo "Adding organization_id to projects...\n";
$res1 = mysqli_query($conn, "ALTER TABLE projects ADD COLUMN organization_id INT NULL AFTER id");
if ($res1) {
    echo "Column added successfully.\n";
    mysqli_query($conn, "UPDATE projects SET organization_id = 1 WHERE organization_id IS NULL");
    mysqli_query($conn, "ALTER TABLE projects MODIFY organization_id INT NOT NULL");
    mysqli_query($conn, "ALTER TABLE projects ADD INDEX (organization_id)");
    echo "Projects table updated.\n";
} else {
    echo "Projects table column might already exist or error: " . mysqli_error($conn) . "\n";
}

// 2. Fixing Lead Sources (Just in case)
echo "Checking lead_sources index...\n";
mysqli_query($conn, "ALTER TABLE lead_sources DROP INDEX source_name"); // Might fail if already dropped
mysqli_query($conn, "ALTER TABLE lead_sources ADD UNIQUE KEY unique_source_per_org (organization_id, source_name)");

echo "Migration fix complete.\n";
?>
