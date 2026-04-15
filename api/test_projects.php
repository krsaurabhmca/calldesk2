<?php
require_once 'auth_check.php';
echo json_encode(['success' => true, 'message' => 'API is reachable', 'user' => $auth_user]);
?>
