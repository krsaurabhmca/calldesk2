<?php
// add_sim_column.php
require_once 'config/db.php';

$sql = "ALTER TABLE call_logs ADD COLUMN sim_slot VARCHAR(20) DEFAULT NULL AFTER type";

if (mysqli_query($conn, $sql)) {
    echo "Column 'sim_slot' added successfully to call_logs table.\n";
} else {
    echo "Error adding column: " . mysqli_error($conn) . "\n";
}
?>
