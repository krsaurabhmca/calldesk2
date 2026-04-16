<?php
require_once 'auth_check.php';

$method = $_SERVER['REQUEST_METHOD'];
$executive_id = $auth_user['id'];
$org_id = $auth_user['organization_id'];
$role = $auth_user['role'];

if ($method === 'GET') {
    $action = $_GET['action'] ?? 'list';

    if ($action === 'list') {
        $org_id_val = (int) ($org_id ?? 0);
        if ($org_id_val <= 0) {
            sendResponse(true, 'No projects found (invalid org)', []);
        }

        // Fetch projects with lead count
        if ($role === 'admin') {
            $sql = "SELECT p.*, (SELECT COUNT(*) FROM leads l WHERE l.project_id = p.id) as lead_count 
                    FROM projects p WHERE p.organization_id = $org_id_val ORDER BY name ASC";
        } else {
            // Non-admins only see projects assigned to them
            $sql = "SELECT p.*, (SELECT COUNT(*) FROM leads l WHERE l.project_id = p.id) as lead_count
                    FROM projects p 
                    JOIN user_projects up ON p.id = up.project_id 
                    WHERE up.user_id = $executive_id AND p.organization_id = $org_id_val 
                    ORDER BY p.name ASC";
        }
        $result = mysqli_query($conn, $sql);
        if (!$result) {
            sendResponse(false, 'Database Error: ' . mysqli_error($conn), null, 500);
        }
        $projects = [];
        while ($row = mysqli_fetch_assoc($result)) {
            // Robust status check (case-insensitive)
            $statusVal = null;
            if (isset($row['status'])) $statusVal = $row['status'];
            elseif (isset($row['Status'])) $statusVal = $row['Status'];
            elseif (isset($row['STATUS'])) $statusVal = $row['STATUS'];
            
            // If still null or not set, default to 1 (Active)
            $row['status'] = ($statusVal === null || $statusVal === '') ? 1 : (int)$statusVal;
            
            $projects[] = $row;
        }
        
        // Final debug check to see what we are sending
        $sample = count($projects) > 0 ? $projects[0] : 'empty';
        file_put_contents('../scratch/debug_projects.log', "Fetch time: " . date('Y-m-d H:i:s') . "\nCount: " . count($projects) . "\nSample: " . json_encode($sample) . "\n\n", FILE_APPEND);
        
        sendResponse(true, 'Projects fetched successfully', $projects);

    } elseif ($action === 'user_assignments') {
        // Admin only: Fetch all users and their assigned projects
        if ($role !== 'admin') {
            sendResponse(false, 'Unauthorized', null, 403);
        }

        $org_id_val = (int) ($org_id ?? 0);
        $sql = "SELECT u.id, u.name, GROUP_CONCAT(p.name SEPARATOR ', ') as project_names, 
                       GROUP_CONCAT(p.id SEPARATOR ',') as project_ids
                FROM users u
                LEFT JOIN user_projects up ON u.id = up.user_id
                LEFT JOIN projects p ON up.project_id = p.id
                WHERE u.organization_id = $org_id_val AND u.status = 1
                GROUP BY u.id ORDER BY u.name ASC";
        $result = mysqli_query($conn, $sql);
        if (!$result) {
            sendResponse(false, 'Database Error: ' . mysqli_error($conn), null, 500);
        }
        $assignments = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $assignments[] = $row;
        }
        sendResponse(true, 'User assignments fetched', $assignments);
    }

} elseif ($method === 'POST') {
    // Read JSON body if available
    $raw_input = file_get_contents('php://input');
    $input = json_decode($raw_input, true);
    
    if ($input) {
        $action = $input['action'] ?? 'add';
        $post_data = $input;
    } else {
        // Fallback to $_POST and then $_REQUEST
        $post_data = array_merge($_POST, $_REQUEST);
        $action = $post_data['action'] ?? 'add';
    }

    if ($action === 'add') {
        if ($role !== 'admin') {
            sendResponse(false, 'Unauthorized', null, 403);
        }

        $name = mysqli_real_escape_string($conn, $post_data['name'] ?? '');
        if (empty($name)) {
            sendResponse(false, 'Project name is required', null, 400);
        }

        $org_id_val = (int) ($org_id ?? 0);
        if ($org_id_val <= 0) {
            sendResponse(false, 'Organization ID is missing. Please contact support.', null, 400);
        }

        $sql = "INSERT INTO projects (organization_id, name, status) VALUES ($org_id_val, '$name', 1)";
        if (mysqli_query($conn, $sql)) {
            sendResponse(true, 'Project added successfully', ['id' => mysqli_insert_id($conn)]);
        } else {
            $error = mysqli_error($conn);
            if (strpos($error, 'Duplicate entry') !== false) {
                sendResponse(false, 'A project with this name already exists in your organization.', null, 400);
            }
            sendResponse(false, 'Error adding project: ' . $error, null, 500);
        }

    } elseif ($action === 'assign') {
        if ($role !== 'admin') {
            sendResponse(false, 'Unauthorized', null, 403);
        }

        $target_user_id = (int) ($post_data['user_id'] ?? 0);
        $project_ids_str = $post_data['project_ids'] ?? ''; // Comma-separated IDs

        if ($target_user_id <= 0) {
            sendResponse(false, 'User ID is required', null, 400);
        }

        mysqli_query($conn, "DELETE FROM user_projects WHERE user_id = $target_user_id");

        if (!empty($project_ids_str)) {
            $ids = explode(',', $project_ids_str);
            foreach ($ids as $pid) {
                $pid = (int) $pid;
                mysqli_query($conn, "INSERT INTO user_projects (user_id, project_id) VALUES ($target_user_id, $pid)");
            }
        }

        sendResponse(true, 'Projects assigned successfully');
    } elseif ($action === 'toggle_status') {
        if ($role !== 'admin') {
            sendResponse(false, 'Unauthorized: Admin access required', null, 403);
        }
        $id = (int) ($post_data['id'] ?? 0);
        $status = (int) ($post_data['status'] ?? 0); // Default to 0 if not passed, but app sends it
        $org_id_val = (int) $org_id;

        if ($id <= 0) {
            sendResponse(false, 'Invalid Project ID', null, 400);
        }

        $sql = "UPDATE projects SET status = $status WHERE id = $id AND organization_id = $org_id_val";
        if (mysqli_query($conn, $sql)) {
            if (mysqli_affected_rows($conn) > 0) {
                sendResponse(true, 'Project status updated successfully');
            } else {
                // Check if project exists
                $check = mysqli_query($conn, "SELECT id FROM projects WHERE id = $id AND organization_id = $org_id_val");
                if (mysqli_num_rows($check) > 0) {
                    sendResponse(true, 'Status is already set to this value');
                } else {
                    sendResponse(false, 'Project not found or unauthorized', null, 404);
                }
            }
        } else {
            sendResponse(false, 'Database Error: ' . mysqli_error($conn), null, 500);
        }
    } else {
        sendResponse(false, 'Invalid action specified: ' . $action, null, 400);
    }

} elseif ($method === 'DELETE') {
    // Delete project
    if ($role !== 'admin') {
        sendResponse(false, 'Unauthorized', null, 403);
    }

    $project_id = (int) ($_GET['id'] ?? 0);
    if ($project_id <= 0) {
        sendResponse(false, 'Project ID is required', null, 400);
    }

    $sql = "DELETE FROM projects WHERE id = $project_id AND organization_id = $org_id";
    if (mysqli_query($conn, $sql)) {
        sendResponse(true, 'Project deleted successfully');
    } else {
        sendResponse(false, 'Error deleting project: ' . mysqli_error($conn), null, 500);
    }
} else {
    sendResponse(false, 'Method not allowed', null, 405);
}
?>