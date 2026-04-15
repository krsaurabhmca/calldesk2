<?php
// lead_add.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'config/db.php';
require_once 'includes/auth.php';
checkAuth();

$message = '';
$error = '';

$prefill_mobile = isset($_GET['mobile']) ? $_GET['mobile'] : '';
$call_id = isset($_GET['call_id']) ? (int)$_GET['call_id'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $org_id = getOrgId();
    if (!$org_id) {
        $error = "Session expired or invalid organization. Please login again.";
    } else {
        $name = mysqli_real_escape_string($conn, $_POST['name']);
        $mobile = mysqli_real_escape_string($conn, $_POST['mobile']);
        $source_id = !empty($_POST['source_id']) ? (int)$_POST['source_id'] : "NULL";
        $status = mysqli_real_escape_string($conn, $_POST['status']);
        $assigned_to = !empty($_POST['assigned_to']) ? (int)$_POST['assigned_to'] : "NULL";
        $project_id = !empty($_POST['project_id']) ? (int)$_POST['project_id'] : "NULL";
        $remarks = mysqli_real_escape_string($conn, $_POST['remarks']);

        $sql = "INSERT INTO leads (organization_id, name, mobile, source_id, project_id, status, assigned_to, remarks) 
                VALUES ($org_id, '$name', '$mobile', $source_id, $project_id, '$status', $assigned_to, '$remarks')";
    }
    
    try {
        if (mysqli_query($conn, $sql)) {
            $new_lead_id = mysqli_insert_id($conn);
            
            // If coming from call log, link it
            if ($call_id > 0) {
                mysqli_query($conn, "UPDATE call_logs SET lead_id = $new_lead_id, is_converted = 1 WHERE id = $call_id AND organization_id = $org_id");
            }
            
            header("Location: leads.php?success=1");
            exit();
        } else {
            $error = "Failed to create lead: " . mysqli_error($conn);
        }
    } catch (Throwable $e) {
        if (method_exists($e, 'getCode') && $e->getCode() == 1062) {
            $error = "A lead with this mobile number already exists in your organization.";
        } else {
            $error = "Error: " . $e->getMessage();
        }
    }
}

$org_id = getOrgId();
if ($org_id) {
    $users_result = mysqli_query($conn, "SELECT id, name FROM users WHERE organization_id = $org_id AND status = 1 ORDER BY name ASC");
    $sources_result = mysqli_query($conn, "SELECT id, source_name FROM lead_sources WHERE organization_id = $org_id AND status = 1 ORDER BY source_name ASC");
    $projects_result = mysqli_query($conn, "SELECT id, name FROM projects WHERE organization_id = $org_id AND status = 1 ORDER BY name ASC");
} else {
    $users_result = $sources_result = $projects_result = false;
    if (!$error) $error = "Invalid session. Please login again.";
}

include 'includes/header.php';
?>

<div style="max-width: 600px; margin: 0 auto; padding: 1rem 0;">
    <div style="display: flex; align-items: center; margin-bottom: 1rem;">
        <a href="leads.php" style="margin-right: 0.75rem; color: var(--text-muted);"><i class="fas fa-arrow-left"></i></a>
        <h2 style="font-size: 1.125rem; font-weight: 800; color: var(--text-main); margin: 0;">Add New Lead</h2>
    </div>

    <?php if ($error): ?>
    <div style="background: #fef2f2; color: #991b1b; padding: 0.6rem 0.8rem; border-radius: 8px; border: 1px solid #fee2e2; margin-bottom: 1rem; font-size: 0.75rem; font-weight: 600;">
        <i class="fas fa-exclamation-circle" style="margin-right: 0.4rem;"></i> <?php echo $error; ?>
    </div>
    <?php endif; ?>

    <div class="card" style="padding: 1.25rem; border-radius: 16px;">
        <form action="" method="POST">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.875rem;">
                <div class="form-group">
                    <label class="form-label" style="font-size: 0.7rem; margin-bottom: 0.25rem;">Full Name</label>
                    <div style="position: relative;">
                        <i class="fas fa-user" style="position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 0.7rem;"></i>
                        <input type="text" name="name" class="form-control" style="padding-left: 2rem; height: 38px; font-size: 0.85rem;" placeholder="Lead Name" required autofocus>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label" style="font-size: 0.7rem; margin-bottom: 0.25rem;">Mobile Number</label>
                    <div style="position: relative;">
                        <i class="fas fa-phone" style="position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 0.7rem;"></i>
                        <input type="text" name="mobile" class="form-control" style="padding-left: 2rem; height: 38px; font-size: 0.85rem;" placeholder="Mobile" value="<?php echo htmlspecialchars($prefill_mobile); ?>" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label" style="font-size: 0.7rem; margin-bottom: 0.25rem;">Lead Source</label>
                    <select name="source_id" class="form-control" style="height: 38px; font-size: 0.85rem;" required>
                        <option value="">-- Select --</option>
                        <?php if ($sources_result): ?>
                            <?php while ($s = mysqli_fetch_assoc($sources_result)): ?>
                                <option value="<?php echo $s['id']; ?>"><?php echo $s['source_name']; ?></option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label" style="font-size: 0.7rem; margin-bottom: 0.25rem;">Project / Category</label>
                    <select name="project_id" class="form-control" style="height: 38px; font-size: 0.85rem;">
                        <option value="">-- Select --</option>
                        <?php 
                        if ($projects_result):
                            mysqli_data_seek($projects_result, 0);
                            while ($p = mysqli_fetch_assoc($projects_result)): 
                        ?>
                            <option value="<?php echo $p['id']; ?>"><?php echo $p['name']; ?></option>
                        <?php 
                            endwhile; 
                        endif;
                        ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" style="font-size: 0.7rem; margin-bottom: 0.25rem;">Current Status</label>
                    <select name="status" class="form-control" style="height: 38px; font-size: 0.85rem;" required>
                        <option value="New">New</option>
                        <option value="Follow-up">Follow-up</option>
                        <option value="Interested">Interested</option>
                        <option value="Converted">Converted</option>
                        <option value="Lost">Lost</option>
                    </select>
                </div>

                <?php if (isAdmin()): ?>
                <div class="form-group">
                    <label class="form-label" style="font-size: 0.7rem; margin-bottom: 0.25rem;">Assign To</label>
                    <select name="assigned_to" class="form-control" style="height: 38px; font-size: 0.85rem;">
                        <option value="">-- Select --</option>
                        <?php if ($users_result): ?>
                            <?php while ($u = mysqli_fetch_assoc($users_result)): ?>
                                <option value="<?php echo $u['id']; ?>"><?php echo $u['name']; ?></option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <?php else: ?>
                    <input type="hidden" name="assigned_to" value="<?php echo $_SESSION['user_id']; ?>">
                    <div style="display:flex; align-items:center; padding-top:1.25rem;">
                        <span style="font-size: 0.75rem; color: var(--text-muted); font-weight: 500;">
                            <i class="fas fa-user-check" style="margin-right: 0.25rem; color: var(--primary);"></i> Self Assigned
                        </span>
                    </div>
                <?php endif; ?>

                <div class="form-group" style="grid-column: span 2;">
                    <label class="form-label" style="font-size: 0.7rem; margin-bottom: 0.25rem;">Initial Remarks</label>
                    <textarea name="remarks" class="form-control" rows="2" style="font-size: 0.85rem; padding: 0.6rem 0.75rem;" placeholder="Notes about this lead..."></textarea>
                </div>
            </div>
            
            <div style="display: flex; gap: 0.75rem; margin-top: 1.25rem; border-top: 1px solid #f1f5f9; pt: 1.25rem; padding-top: 1.25rem;">
                <button type="submit" class="btn btn-primary" style="width: auto; padding: 0.625rem 2rem; font-size: 0.875rem;">Create Lead</button>
                <a href="leads.php" class="btn" style="width: auto; padding: 0.625rem 2rem; background: #f8fafc; color: var(--text-main); text-decoration: none; font-size: 0.875rem; border: 1px solid var(--border);">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
