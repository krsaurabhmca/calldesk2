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

// 5. Add updated_at to leads table if not exists
$sql_check_leads = "SHOW COLUMNS FROM leads LIKE 'updated_at'";
$res_leads = mysqli_query($conn, $sql_check_leads);
if (mysqli_num_rows($res_leads) == 0) {
    mysqli_query($conn, "ALTER TABLE leads ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at");
    echo "Added updated_at column to leads table.\n";
}

echo "All tables (Projects, Lead Sources, Users, Leads) table schema updated.\n";

echo "Migration complete.\n";
?>
