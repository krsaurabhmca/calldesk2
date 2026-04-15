<?php
// lead_import.php
require_once 'config/db.php';
require_once 'includes/auth.php';
checkAuth();

$message = '';
$error = '';
$org_id = getOrgId();
$user_id = $_SESSION['user_id'];

// Step 1: Upload & Initial Parse
$csv_headers = [];
$sample_row = [];
$temp_file = '';

if (isset($_POST['upload_csv'])) {
    if ($_FILES['csv_file']['error'] == 0) {
        $filename = $_FILES['csv_file']['tmp_name'];
        $handle = fopen($filename, "r");
        $csv_headers = fgetcsv($handle, 1000, ",");
        $sample_row = fgetcsv($handle, 1000, ",");
        fclose($handle);

        // Save file to a temporary location for Step 2
        $upload_dir = __DIR__ . '/scratch/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        $temp_file = 'import_' . $user_id . '_' . time() . '.csv';
        move_uploaded_file($_FILES['csv_file']['tmp_name'], $upload_dir . $temp_file);
    } else {
        $error = "File upload failed.";
    }
}

// Step 3: Final Import
if (isset($_POST['import_data'])) {
    $mapping = $_POST['map'];
    $temp_filename = $_POST['temp_file'];
    $filepath = __DIR__ . '/scratch/' . $temp_filename;
    
    if (file_exists($filepath)) {
        $handle = fopen($filepath, "r");
        $headers = fgetcsv($handle, 1000, ",");
        $success_count = 0;
        $skip_count = 0;

        // Fetch custom fields for this org to identify mapping
        $cf_res = mysqli_query($conn, "SELECT id, field_name FROM lead_custom_fields WHERE organization_id = $org_id");
        $custom_fields = [];
        while($cf = mysqli_fetch_assoc($cf_res)) {
            $custom_fields[$cf['field_name']] = $cf['id'];
        }

        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            $lead_data = [];
            foreach ($mapping as $db_field => $csv_index) {
                if ($csv_index !== "") {
                    $lead_data[$db_field] = mysqli_real_escape_string($conn, $data[$csv_index]);
                }
            }

            if (empty($lead_data['name']) || empty($lead_data['mobile'])) {
                $skip_count++;
                continue;
            }

            // Insert into leads table
            $name = $lead_data['name'] ?? '';
            $mobile = $lead_data['mobile'] ?? '';
            $status = $lead_data['status'] ?? 'New';
            $remarks = $lead_data['remarks'] ?? 'Imported lead';
            $assigned_to = $user_id; // Default to self

            $sql = "INSERT INTO leads (organization_id, name, mobile, status, assigned_to, remarks) 
                    VALUES ($org_id, '$name', '$mobile', '$status', $assigned_to, '$remarks')";
            
            if (mysqli_query($conn, $sql)) {
                $new_lead_id = mysqli_insert_id($conn);
                $success_count++;

                // Handle Custom Fields Mapping
                foreach ($lead_data as $field_key => $val) {
                    if (isset($custom_fields[$field_key])) {
                        $field_id = $custom_fields[$field_key];
                        mysqli_query($conn, "INSERT INTO lead_custom_data (lead_id, field_id, field_value) VALUES ($new_lead_id, $field_id, '$val')");
                    }
                }
            } else {
                $skip_count++;
            }
        }
        fclose($handle);
        unlink($filepath); // Delete temp file
        $message = "Import complete! $success_count leads imported, $skip_count skipped.";
    }
}

include 'includes/header.php';
?>

<div style="max-width: 900px; margin: 0 auto;">
    <div style="margin-bottom: 2rem;">
        <h2 style="font-size: 1.5rem; font-weight: 800; color: var(--text-main); letter-spacing: -0.02em;">Bulk Lead Import</h2>
        <p style="color: var(--text-muted); font-size: 0.875rem;">Upload a CSV file to import multiple leads at once. You can map your CSV columns to standard and custom lead fields.</p>
    </div>

    <?php if ($message): ?>
    <div style="background: #ecfdf5; color: #065f46; padding: 1rem; border-radius: 12px; border: 1px solid #d1fae5; margin-bottom: 2rem;">
        <i class="fas fa-check-circle" style="margin-right: 0.5rem;"></i> <?php echo $message; ?>
        <a href="leads.php" style="margin-left: 1rem; color: var(--primary); font-weight: 700; text-decoration: none;">View Leads →</a>
    </div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div style="background: #fef2f2; color: #991b1b; padding: 1rem; border-radius: 12px; border: 1px solid #fee2e2; margin-bottom: 2rem;">
        <i class="fas fa-exclamation-circle" style="margin-right: 0.5rem;"></i> <?php echo $error; ?>
    </div>
    <?php endif; ?>

    <?php if (empty($csv_headers)): ?>
    <!-- Step 1: File Upload -->
    <div class="card" style="padding: 3rem; text-align: center; border: 2px dashed var(--border);">
        <form action="" method="POST" enctype="multipart/form-data">
            <i class="fas fa-file-csv" style="font-size: 3rem; color: var(--primary); margin-bottom: 1.5rem; opacity: 0.5;"></i>
            <h3 style="font-size: 1.125rem; font-weight: 700; margin-bottom: 0.5rem;">Select CSV File</h3>
            <p style="font-size: 0.875rem; color: var(--text-muted); margin-bottom: 2rem;">Ensure your file is in .csv format and contains a header row.</p>
            
            <input type="file" name="csv_file" id="csv_file" style="display: none;" accept=".csv" onchange="this.form.submit()" required>
            <label for="csv_file" class="btn btn-primary" style="cursor: pointer; padding: 0.75rem 2.5rem;">
                <i class="fas fa-upload"></i> Choose File & Continue
            </label>
            <input type="hidden" name="upload_csv" value="1">
            
            <div style="margin-top: 1.5rem; border-top: 1px solid #f1f5f9; pt: 1rem; padding-top: 1.5rem;">
                <a href="download_sample.php" style="color: var(--primary); font-size: 0.8125rem; font-weight: 700; text-decoration: none;">
                    <i class="fas fa-download"></i> Download Sample CSV File
                </a>
                <p style="font-size: 0.7rem; color: var(--text-muted); margin-top: 0.4rem;">Includes your current custom fields for easy mapping.</p>
            </div>
        </form>
    </div>
    <?php else: ?>
    <!-- Step 2: Mapping -->
    <div class="card">
        <h3 style="font-size: 1.125rem; font-weight: 700; margin-bottom: 1.5rem;">Map CSV Columns</h3>
        <form action="" method="POST">
            <input type="hidden" name="import_data" value="1">
            <input type="hidden" name="temp_file" value="<?php echo htmlspecialchars($temp_file); ?>">
            
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th style="padding: 1rem;">Database Field</th>
                            <th style="padding: 1rem;">Maps to CSV Column</th>
                            <th style="padding: 1rem;">Sample Data</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Standard Fields -->
                        <tr>
                            <td style="padding: 1rem; font-weight: 600;">Full Name <span style="color: var(--danger);">*</span></td>
                            <td style="padding: 1rem;">
                                <select name="map[name]" class="form-control" required>
                                    <option value="">-- Select Column --</option>
                                    <?php foreach($csv_headers as $i => $h): ?>
                                        <option value="<?php echo $i; ?>"><?php echo htmlspecialchars($h); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td style="padding: 1rem; font-size: 0.75rem; color: var(--text-muted);" id="sample_name"></td>
                        </tr>
                        <tr>
                            <td style="padding: 1rem; font-weight: 600;">Mobile Number <span style="color: var(--danger);">*</span></td>
                            <td style="padding: 1rem;">
                                <select name="map[mobile]" class="form-control" required>
                                    <option value="">-- Select Column --</option>
                                    <?php foreach($csv_headers as $i => $h): ?>
                                        <option value="<?php echo $i; ?>"><?php echo htmlspecialchars($h); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td style="padding: 1rem; font-size: 0.75rem; color: var(--text-muted);" id="sample_mobile"></td>
                        </tr>
                        <tr>
                            <td style="padding: 1rem; font-weight: 600;">Lead Status</td>
                            <td style="padding: 1rem;">
                                <select name="map[status]" class="form-control">
                                    <option value="">-- Select Column (Optional) --</option>
                                    <?php foreach($csv_headers as $i => $h): ?>
                                        <option value="<?php echo $i; ?>"><?php echo htmlspecialchars($h); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td style="padding: 1rem; font-size: 0.75rem; color: var(--text-muted);"></td>
                        </tr>
                        <tr>
                            <td style="padding: 1rem; font-weight: 600;">Remarks</td>
                            <td style="padding: 1rem;">
                                <select name="map[remarks]" class="form-control">
                                    <option value="">-- Select Column (Optional) --</option>
                                    <?php foreach($csv_headers as $i => $h): ?>
                                        <option value="<?php echo $i; ?>"><?php echo htmlspecialchars($h); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td style="padding: 1rem; font-size: 0.75rem; color: var(--text-muted);"></td>
                        </tr>

                        <!-- Custom Fields -->
                        <?php 
                        $cf_res = mysqli_query($conn, "SELECT * FROM lead_custom_fields WHERE organization_id = $org_id");
                        if (mysqli_num_rows($cf_res) > 0): 
                        ?>
                        <tr>
                            <td colspan="3" style="background: #f8fafc; padding: 0.75rem 1rem; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; color: var(--text-muted); letter-spacing: 0.05em;">Custom Dynamic Fields</td>
                        </tr>
                        <?php while($cf = mysqli_fetch_assoc($cf_res)): ?>
                        <tr>
                            <td style="padding: 1rem; font-weight: 600;"><?php echo $cf['field_label']; ?></td>
                            <td style="padding: 1rem;">
                                <select name="map[<?php echo $cf['field_name']; ?>]" class="form-control">
                                    <option value="">-- Skip Field --</option>
                                    <?php foreach($csv_headers as $i => $h): ?>
                                        <option value="<?php echo $i; ?>"><?php echo htmlspecialchars($h); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td style="padding: 1rem; font-size: 0.75rem; color: var(--text-muted);"></td>
                        </tr>
                        <?php endwhile; endif; ?>
                    </tbody>
                </table>
            </div>

            <div style="margin-top: 2rem; display: flex; justify-content: flex-end; gap: 1rem;">
                <a href="lead_import.php" class="btn" style="background: #f1f5f9; color: var(--text-main);">Cancel</a>
                <button type="submit" class="btn btn-primary" style="padding: 0.75rem 2rem;">Start Import Now</button>
            </div>
        </form>
    </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
