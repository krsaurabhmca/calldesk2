<?php
// fix_project_status_default.php
require_once 'config/db.php';

echo "Updating projects table schema and existing records...\n";

// 1. Update existing projects to be active (status = 1) if they are currently 0
$sql_update = "UPDATE projects SET status = 1 WHERE status = 0";
if (mysqli_query($conn, $sql_update)) {
    echo "Existing projects updated to status 1.\n";
} else {
    echo "Error updating projects: " . mysqli_error($conn) . "\n";
}

// 2. Change column default to 1
$sql_alter = "ALTER TABLE projects MODIFY status TINYINT(1) DEFAULT 1";
if (mysqli_query($conn, $sql_alter)) {
    echo "Projects table schema updated: default status is now 1.\n";
} else {
    // If column doesn't exist, we might need to add it
    $sql_add = "ALTER TABLE projects ADD COLUMN status TINYINT(1) DEFAULT 1";
    if (mysqli_query($conn, $sql_add)) {
        echo "Column 'status' added to projects table with default 1.\n";
    } else {
        echo "Error altering table: " . mysqli_error($conn) . "\n";
    }
}

echo "Migration complete.\n";
?>
