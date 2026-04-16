<?php
// fix_project_status_default.php
require_once 'config/db.php';

echo "Updating projects table schema and existing records...\n";

// 1. Update existing projects to be active (status = 1) if they are currently 0 or NULL
$sql_update = "UPDATE projects SET status = 1 WHERE status = 0 OR status IS NULL";
if (mysqli_query($conn, $sql_update)) {
    echo "Existing projects updated to status 1.\n";
} else {
    echo "Error updating projects: " . mysqli_error($conn) . "\n";
}

// 2. Update existing lead sources
$sql_update_sources = "UPDATE lead_sources SET status = 1 WHERE status = 0 OR status IS NULL";
if (mysqli_query($conn, $sql_update_sources)) {
    echo "Existing lead sources updated to status 1.\n";
} else {
    echo "Error updating lead sources: " . mysqli_error($conn) . "\n";
}

// 3. Update existing users
$sql_update_users = "UPDATE users SET status = 1 WHERE status = 0 OR status IS NULL";
if (mysqli_query($conn, $sql_update_users)) {
    echo "Existing users updated to status 1.\n";
} else {
    echo "Error updating users: " . mysqli_error($conn) . "\n";
}

// 4. Change column defaults to 1
$sql_alter = "ALTER TABLE projects MODIFY status TINYINT(1) DEFAULT 1";
mysqli_query($conn, $sql_alter);

$sql_alter_sources = "ALTER TABLE lead_sources MODIFY status TINYINT(1) DEFAULT 1";
mysqli_query($conn, $sql_alter_sources);

$sql_alter_users = "ALTER TABLE users MODIFY status TINYINT(1) DEFAULT 1";
mysqli_query($conn, $sql_alter_users);

echo "All tables (Projects, Lead Sources, Users) table schema updated: default status is now 1.\n";

echo "Migration complete.\n";
?>
