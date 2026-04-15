<?php
// api/reports.php
require_once 'auth_check.php';

if ($auth_user['role'] !== 'admin') {
    sendResponse(false, 'Unauthorized: Admin access required', null, 403);
}

$org_id = $auth_user['organization_id'];
$action = $_GET['action'] ?? 'summary';

    // Category/Project Distribution
    $sql = "SELECT p.id, p.name as category_name, COUNT(l.id) as count 
            FROM projects p 
            LEFT JOIN leads l ON p.id = l.project_id AND l.organization_id = $org_id
            WHERE p.organization_id = $org_id
            GROUP BY p.id";
    $proj_res = mysqli_query($conn, $sql);
    $proj_data = [];
    while($row = mysqli_fetch_assoc($proj_res)) {
        $proj_data[] = $row;
    }

    sendResponse(true, 'Reports fetched', [
        'status_distribution' => $status_data,
        'source_distribution' => $source_data,
        'team_performance' => $team_data,
        'category_distribution' => $proj_data
    ]);
} elseif ($action === 'business_calls_report') {
    $sql = "SELECT 
                u.id as executive_id, 
                u.name as executive_name, 
                DATE(c.call_time) as call_date, 
                SUM(c.duration) as total_duration,
                COUNT(c.id) as call_count
            FROM call_logs c
            JOIN leads l ON c.mobile = l.mobile AND c.organization_id = l.organization_id
            JOIN users u ON c.executive_id = u.id
            WHERE c.organization_id = $org_id
            GROUP BY u.id, DATE(c.call_time)
            ORDER BY call_date DESC, u.name ASC";
            
    $res = mysqli_query($conn, $sql);
    $report_data = [];
    while($row = mysqli_fetch_assoc($res)) {
        $report_data[] = $row;
    }
    
    sendResponse(true, 'Business calls report fetched', $report_data);
}
?>
