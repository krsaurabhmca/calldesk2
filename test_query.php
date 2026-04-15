<?php
include 'config/db.php';
$org_id = 1;
$sql = "SELECT p.name, COUNT(l.id) as count 
         FROM projects p 
         LEFT JOIN leads l ON p.id = l.project_id AND l.organization_id = $org_id
         WHERE p.organization_id = $org_id 
         GROUP BY p.id ORDER BY count DESC LIMIT 5";
$r = mysqli_query($conn, $sql);
if ($r) {
    echo "Query successful!\n";
} else {
    echo "Query failed: " . mysqli_error($conn) . "\n";
}
?>
