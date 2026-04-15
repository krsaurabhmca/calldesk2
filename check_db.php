<?php
require 'config/db.php';
$res = mysqli_query($conn, "SELECT id, name, status FROM projects");
while($row = mysqli_fetch_assoc($res)) print_r($row);
$res = mysqli_query($conn, "SELECT id, source_name, status FROM lead_sources");
while($row = mysqli_fetch_assoc($res)) print_r($row);
