<?php
include 'config/db.php';
$res = mysqli_query($conn, 'SHOW COLUMNS FROM projects');
if ($res) {
    while($row = mysqli_fetch_assoc($res)) {
        echo $row['Field'] . "\n";
    }
} else {
    echo "Error: " . mysqli_error($conn) . "\n";
}
?>
