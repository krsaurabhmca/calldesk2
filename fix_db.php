<?php
// fix_db.php
require_once 'config/db.php';

echo "<h1>Database Optimization & Fix</h1>";

// 1. Projects Table
echo "<p>Checking Projects table...</p>";
mysqli_query($conn, "ALTER TABLE projects MODIFY status TINYINT(1) DEFAULT 1");
$res = mysqli_query($conn, "UPDATE projects SET status = 1 WHERE status = 0 OR status IS NULL");
echo "Projects updated: " . mysqli_affected_rows($conn) . " rows.<br>";

// 2. Lead Sources Table
echo "<p>Checking Lead Sources table...</p>";
mysqli_query($conn, "ALTER TABLE lead_sources ADD COLUMN status TINYINT(1) DEFAULT 1");
mysqli_query($conn, "ALTER TABLE lead_sources MODIFY status TINYINT(1) DEFAULT 1");
$res = mysqli_query($conn, "UPDATE lead_sources SET status = 1 WHERE status = 0 OR status IS NULL");
echo "Lead Sources updated: " . mysqli_affected_rows($conn) . " rows.<br>";

echo "<h3>Fix complete! Please refresh your app now.</h3>";
?>
