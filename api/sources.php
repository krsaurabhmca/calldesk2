<?php
// api/sources.php
require_once 'auth_check.php';

$org_id = $auth_user['organization_id'];
$method = $_SERVER['REQUEST_METHOD'];
$role = $auth_user['role'];

if ($method === 'GET') {
    $sql = "SELECT id, source_name FROM lead_sources WHERE organization_id = $org_id ORDER BY source_name ASC";
    $result = mysqli_query($conn, $sql);
    $sources = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $sources[] = $row;
    }
    sendResponse(true, 'Lead sources fetched successfully', $sources);

} elseif ($method === 'POST') {
    if ($role !== 'admin') {
        sendResponse(false, 'Unauthorized: Admin access required', null, 403);
    }

    // Support both Form Data and JSON
    $input = json_decode(file_get_contents('php://input'), true);
    if ($input) {
        $action = $input['action'] ?? 'add';
        $post_data = $input;
    } else {
        $action = $_POST['action'] ?? 'add';
        $post_data = $_POST;
    }

    if ($action === 'add') {
        $name = mysqli_real_escape_string($conn, $post_data['source_name'] ?? '');
        if (empty($name)) {
            sendResponse(false, 'Source name is required', null, 400);
        }

        $sql = "INSERT INTO lead_sources (organization_id, source_name) VALUES ($org_id, '$name')";
        if (mysqli_query($conn, $sql)) {
            sendResponse(true, 'Lead source added successfully', ['id' => mysqli_insert_id($conn)]);
        } else {
            if (mysqli_errno($conn) == 1062) {
                sendResponse(false, 'Lead source already exists', null, 400);
            }
            sendResponse(false, 'Failed to add lead source: ' . mysqli_error($conn), null, 500);
        }

    } elseif ($action === 'delete') {
        $id = (int)($post_data['id'] ?? 0);
        if ($id <= 0) {
            sendResponse(false, 'Invalid Source ID', null, 400);
        }

        if (mysqli_query($conn, "DELETE FROM lead_sources WHERE id = $id AND organization_id = $org_id")) {
            sendResponse(true, 'Lead source deleted');
        } else {
            sendResponse(false, 'Failed to delete lead source', null, 500);
        }
    } elseif ($action === 'toggle_status') {
        $id = (int)($post_data['id'] ?? 0);
        $status = (int)($post_data['status'] ?? 0);
        
        if (mysqli_query($conn, "UPDATE lead_sources SET status = $status WHERE id = $id AND organization_id = $org_id")) {
            sendResponse(true, 'Source status updated');
        } else {
            sendResponse(false, 'Failed to update status', null, 500);
        }
    } else {
        sendResponse(false, 'Invalid action', null, 400);
    }
} elseif ($method === 'DELETE') {
    // Also support native DELETE method
    if ($role !== 'admin') {
        sendResponse(false, 'Unauthorized', null, 403);
    }
    $id = (int)($_GET['id'] ?? 0);
    if (mysqli_query($conn, "DELETE FROM lead_sources WHERE id = $id AND organization_id = $org_id")) {
        sendResponse(true, 'Lead source deleted');
    } else {
        sendResponse(false, 'Failed to delete lead source', null, 500);
    }
} else {
    sendResponse(false, 'Method not allowed', null, 405);
}
?>
