<?php
include 'config/db.php';

echo "<pre>";
echo "Running Custom Fields Migration...\n";

$sql = file_get_contents('custom_fields_migration.sql');
$queries = explode(';', $sql);

foreach ($queries as $query) {
    if (trim($query)) {
        echo "Executing: " . substr(trim($query), 0, 50) . "...\n";
        if (mysqli_query($conn, $query)) {
            echo "SUCCESS\n";
        } else {
            echo "FAILED: " . mysqli_error($conn) . "\n";
        }
    }
}

echo "\nMigration complete.\n";
echo "</pre>";
?>
