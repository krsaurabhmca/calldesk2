<?php
include 'config/db.php';

echo "<pre>";
echo "Starting Database Fixes...\n";

function addColumn($conn, $table, $column, $definition) {
    $check = mysqli_query($conn, "SHOW COLUMNS FROM `$table` LIKE '$column'");
    if (mysqli_num_rows($check) == 0) {
        echo "Adding $column to $table... ";
        $res = mysqli_query($conn, "ALTER TABLE `$table` ADD COLUMN $column $definition");
        if ($res) echo "SUCCESS\n";
        else echo "FAILED: " . mysqli_error($conn) . "\n";
    } else {
        echo "$column already exists in $table.\n";
    }
}

// 1. Projects Table Status
addColumn($conn, 'projects', 'status', 'TINYINT(1) DEFAULT 1 AFTER name');

// 2. Lead Sources Status
addColumn($conn, 'lead_sources', 'status', 'TINYINT(1) DEFAULT 1 AFTER source_name');

// 3. Leads Table Project ID
addColumn($conn, 'leads', 'project_id', 'INT NULL');

echo "\nDone fixes.\n";
echo "</pre>";
?>
