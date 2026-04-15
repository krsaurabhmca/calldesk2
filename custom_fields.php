<?php
// custom_fields.php
require_once 'config/db.php';
require_once 'includes/auth.php';
checkAdmin();

$message = '';
$error = '';
$org_id = getOrgId();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_field'])) {
        $name = mysqli_real_escape_string($conn, trim($_POST['field_name']));
        $label = mysqli_real_escape_string($conn, trim($_POST['field_label']));
        $type = mysqli_real_escape_string($conn, $_POST['field_type']);
        $options = mysqli_real_escape_string($conn, trim($_POST['field_options']));
        $required = isset($_POST['is_required']) ? 1 : 0;

        $sql = "INSERT INTO lead_custom_fields (organization_id, field_name, field_label, field_type, field_options, is_required) 
                VALUES ($org_id, '$name', '$label', '$type', '$options', $required)";
        if (mysqli_query($conn, $sql)) {
            $message = "Custom field added successfully!";
        } else {
            $error = "Failed to add field: " . mysqli_error($conn);
        }
    }

    if (isset($_POST['delete_field'])) {
        $id = (int)$_POST['field_id'];
        if (mysqli_query($conn, "DELETE FROM lead_custom_fields WHERE id = $id AND organization_id = $org_id")) {
            $message = "Field deleted successfully!";
        } else {
            $error = "Failed to delete field.";
        }
    }
}

$fields_res = mysqli_query($conn, "SELECT * FROM lead_custom_fields WHERE organization_id = $org_id ORDER BY id ASC");

include 'includes/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
    <div>
        <h2 style="font-size: 1.125rem; font-weight: 800; color: var(--text-main); letter-spacing: -0.01em;">Custom Lead Fields</h2>
        <p style="font-size: 0.75rem; color: var(--text-muted);">Define additional information you want to collect for each lead.</p>
    </div>
</div>

<?php if ($message): ?>
<div style="background: #ecfdf5; color: #065f46; padding: 0.75rem 1rem; border-radius: 6px; border: 1px solid #d1fae5; margin-bottom: 1rem; font-size: 0.8125rem; font-weight: 600;">
    <i class="fas fa-check-circle" style="margin-right: 0.5rem;"></i> <?php echo $message; ?>
</div>
<?php endif; ?>

<?php if ($error): ?>
<div style="background: #fef2f2; color: #991b1b; padding: 0.75rem 1rem; border-radius: 6px; border: 1px solid #fee2e2; margin-bottom: 1rem; font-size: 0.8125rem; font-weight: 600;">
    <i class="fas fa-exclamation-circle" style="margin-right: 0.5rem;"></i> <?php echo $error; ?>
</div>
<?php endif; ?>

<div style="display: grid; grid-template-columns: 1fr 2fr; gap: 1.5rem;">
    <!-- Add Field Form -->
    <div class="card">
        <h3 style="font-size: 0.875rem; font-weight: 800; margin-bottom: 1rem;">Create New Field</h3>
        <form action="" method="POST">
            <input type="hidden" name="add_field" value="1">
            <div class="form-group">
                <label class="form-label" style="font-size: 0.75rem;">Field Label (Visible to users)</label>
                <input type="text" name="field_label" id="field_label" class="form-control" placeholder="e.g. Budget Range" required>
            </div>
            <div class="form-group">
                <label class="form-label" style="font-size: 0.75rem;">Internal Name (A-Z, no spaces)</label>
                <input type="text" name="field_name" id="field_name" class="form-control" placeholder="e.g. budget_range" pattern="[a-zA-Z0-9_]+" required>
            </div>
            <div class="form-group">
                <label class="form-label" style="font-size: 0.75rem;">Field Type</label>
                <select name="field_type" class="form-control" id="field_type" onchange="toggleOptions()">
                    <option value="text">Text Input</option>
                    <option value="number">Number</option>
                    <option value="date">Date</option>
                    <option value="select">Dropdown (Select)</option>
                </select>
            </div>
            <div class="form-group" id="options_group" style="display: none;">
                <label class="form-label" style="font-size: 0.75rem;">Dropdown Options (Comma separated)</label>
                <textarea name="field_options" class="form-control" placeholder="Option 1, Option 2, Option 3"></textarea>
            </div>
            <div class="form-group" style="display: flex; align-items: center; gap: 0.5rem; margin-top: 0.5rem;">
                <input type="checkbox" name="is_required" id="is_required">
                <label for="is_required" style="font-size: 0.75rem; font-weight: 600;">Required Field</label>
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">Create Field</button>
        </form>
    </div>

    <!-- Field List -->
    <div class="card" style="padding: 0; overflow-x: auto;">
        <table>
            <thead>
                <tr>
                    <th style="padding: 1rem;">Label</th>
                    <th style="padding: 1rem;">Internal Name</th>
                    <th style="padding: 1rem;">Type</th>
                    <th style="padding: 1rem; text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = mysqli_fetch_assoc($fields_res)): ?>
                <tr>
                    <td style="padding: 1rem;">
                        <span style="font-weight: 700;"><?php echo $row['field_label']; ?></span>
                        <?php if ($row['is_required']): ?>
                            <span style="color: var(--danger);">*</span>
                        <?php endif; ?>
                    </td>
                    <td style="padding: 1rem; font-family: monospace; font-size: 0.75rem; color: var(--text-muted);"><?php echo $row['field_name']; ?></td>
                    <td style="padding: 1rem; text-transform: uppercase; font-size: 0.7rem; font-weight: 700; color: var(--primary);"><?php echo $row['field_type']; ?></td>
                    <td style="padding: 1rem; text-align: right;">
                        <form action="" method="POST" onsubmit="return confirm('Are you sure? This will delete all data stored in this field for all leads.')">
                            <input type="hidden" name="field_id" value="<?php echo $row['id']; ?>">
                            <input type="hidden" name="delete_field" value="1">
                            <button type="submit" class="btn" style="background: #fee2e2; color: #b91c1c; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
                <?php if (mysqli_num_rows($fields_res) == 0): ?>
                <tr>
                    <td colspan="4" style="padding: 3rem; text-align: center; color: var(--text-muted);">No custom fields created yet.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function toggleOptions() {
    var type = document.getElementById('field_type').value;
    var group = document.getElementById('options_group');
    group.style.display = (type === 'select') ? 'block' : 'none';
}

document.getElementById('field_label').addEventListener('input', function() {
    var label = this.value;
    var nameField = document.getElementById('field_name');
    
    // Generate slug: lowercase, replace non-alphanumeric with underscore, remove duplicate underscores
    var slug = label.toLowerCase()
                    .replace(/[^a-z0-9]/g, '_')
                    .replace(/_+/g, '_')
                    .replace(/^_|_$/g, '');
    
    nameField.value = slug;
});
</script>

<?php include 'includes/footer.php'; ?>
