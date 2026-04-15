<?php
require_once 'auth_check.php';

$method = $_SERVER['REQUEST_METHOD'];
$executive_id = $auth_user['id'];
$org_id = $auth_user['organization_id'];
$role = $auth_user['role'];

if ($method === 'GET') {
    $action = $_GET['action'] ?? 'list';

    if ($action === 'list') {
        // Fetch projects
        if ($role === 'admin') {
            $sql = "SELECT * FROM projects WHERE organization_id = $org_id ORDER BY name ASC";
        } else {
            // Non-admins only see projects assigned to them
            $sql = "SELECT p.* FROM projects p 
                    JOIN user_projects up ON p.id = up.project_id 
                    WHERE up.user_id = $executive_id AND p.organization_id = $org_id 
                    ORDER BY p.name ASC";
        }
        $result = mysqli_query($conn, $sql);
        $projects = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $projects[] = $row;
        }
        sendResponse(true, 'Projects fetched successfully', $projects);

    } elseif ($action === 'user_assignments') {
        // Admin only: Fetch all users and their assigned projects
        if ($role !== 'admin') {
            sendResponse(false, 'Unauthorized', null, 403);
        }

        $sql = "SELECT u.id, u.name, GROUP_CONCAT(p.name SEPARATOR ', ') as project_names, 
                       GROUP_CONCAT(p.id SEPARATOR ',') as project_ids
                FROM users u
                LEFT JOIN user_projects up ON u.id = up.user_id
                LEFT JOIN projects p ON up.project_id = p.id
                WHERE u.organization_id = $org_id AND u.status = 1
                GROUP BY u.id ORDER BY u.name ASC";
        $result = mysqli_query($conn, $sql);
        $assignments = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $assignments[] = $row;
        }
        sendResponse(true, 'User assignments fetched', $assignments);
    }

} elseif ($method === 'POST') {
    $action = $_POST['action'] ?? 'add';

    if ($action === 'add') {
        if ($role !== 'admin') {
            sendResponse(false, 'Unauthorized', null, 403);
        }

        $name = mysqli_real_escape_string($conn, $_POST['name'] ?? '');
        if (empty($name)) {
            sendResponse(false, 'Project name is required', null, 400);
        }

        $sql = "INSERT INTO projects (organization_id, name) VALUES ($org_id, '$name')";
        if (mysqli_query($conn, $sql)) {
            sendResponse(true, 'Project added successfully', ['id' => mysqli_insert_id($conn)]);
        } else {
            sendResponse(false, 'Error adding project: ' . mysqli_error($conn), null, 500);
        }

    } elseif ($action === 'assign') {
        if ($role !== 'admin') {
            sendResponse(false, 'Unauthorized', null, 403);
        }

        $target_user_id = (int)($_POST['user_id'] ?? 0);
        $project_ids_str = $_POST['project_ids'] ?? ''; // Comma-separated IDs

        if ($target_user_id <= 0) {
            sendResponse(false, 'User ID is required', null, 400);
        }

        // Transactions would be better but simple loops for now
        mysqli_query($conn, "DELETE FROM user_projects WHERE user_id = $target_user_id");

        if (!empty($project_ids_str)) {
            $ids = explode(',', $project_ids_str);
            foreach ($ids as $pid) {
                $pid = (int)$pid;
                mysqli_query($conn, "INSERT INTO user_projects (user_id, project_id) VALUES ($target_user_id, $pid)");
            }
        }

        sendResponse(true, 'Projects assigned successfully');
    }

} elseif ($method === 'DELETE') {
    // Delete project
    if ($role !== 'admin') {
        sendResponse(false, 'Unauthorized', null, 403);
    }

    $project_id = (int)($_GET['id'] ?? 0);
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
