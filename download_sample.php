<?php
// download_sample.php
require_once 'config/db.php';
require_once 'includes/auth.php';
require_once 'includes/custom_fields_helper.php';
checkAuth();

$org_id = getOrgId();
$custom_fields = getCustomFields($conn, $org_id);

// Define headers
$headers = ['Name', 'Mobile', 'Status', 'Remarks'];
foreach ($custom_fields as $cf) {
    $headers[] = $cf['field_label'];
}

// Example data
$example = ['John Doe', '9876543210', 'New', 'Interested in the project'];
foreach ($custom_fields as $cf) {
    if ($cf['field_type'] == 'number') {
        $example[] = '1000';
    } elseif ($cf['field_type'] == 'date') {
        $example[] = date('Y-m-d');
    } else {
        $example[] = $cf['field_options'] ? explode(',', $cf['field_options'])[0] : 'Sample Data';
    }
}

// Set headers for download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=leads_sample_' . date('Ymd') . '.csv');

$output = fopen('php://output', 'w');
fputcsv($output, $headers);
fputcsv($output, $example);
fclose($output);
exit();
?>
