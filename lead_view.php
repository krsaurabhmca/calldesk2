<?php
// lead_view.php
require_once 'config/db.php';
require_once 'includes/auth.php';
require_once 'includes/custom_fields_helper.php';
checkAuth();

$lead_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

$org_id = getOrgId();
$sql = "SELECT l.*, u.name as executive_name, s.source_name, p.name as project_name 
        FROM leads l 
        LEFT JOIN users u ON l.assigned_to = u.id 
        LEFT JOIN lead_sources s ON l.source_id = s.id
        LEFT JOIN projects p ON l.project_id = p.id
        WHERE l.id = $lead_id AND l.organization_id = $org_id";
if ($role !== 'admin') {
    $sql .= " AND l.assigned_to = $user_id";
}
$result = mysqli_query($conn, $sql);
$lead = mysqli_fetch_assoc($result);

if (!$lead) {
    die("Lead not found or access denied.");
}

// Handle New Follow-up
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_followup'])) {
    $remark = mysqli_real_escape_string($conn, $_POST['remark']);
    $next_date = mysqli_real_escape_string($conn, $_POST['next_follow_up_date']);
    $new_status = mysqli_real_escape_string($conn, $_POST['status']);

    // Insert follow-up
    mysqli_query($conn, "INSERT INTO follow_ups (organization_id, lead_id, executive_id, remark, next_follow_up_date) VALUES ($org_id, $lead_id, $user_id, '$remark', '$next_date')");
    
    // Update lead status
    mysqli_query($conn, "UPDATE leads SET status = '$new_status', remarks = '$remark' WHERE id = $lead_id");
    
    header("Location: lead_view.php?id=$lead_id&success=1");
    exit();
}

// Fetch Follow-up History
$history_res = mysqli_query($conn, "SELECT f.*, u.name as executive_name FROM follow_ups f JOIN users u ON f.executive_id = u.id JOIN leads l ON f.lead_id = l.id WHERE f.lead_id = $lead_id AND l.organization_id = $org_id ORDER BY f.created_at DESC");

// Fetch Custom Fields Data
$custom_fields = getCustomFields($conn, $org_id);
$custom_data = getLeadCustomData($conn, $lead_id);

// Fetch Call Logs for this lead's number
$lead_mobile = $lead['mobile'];
$calls_res = mysqli_query($conn, "SELECT * FROM call_logs WHERE (mobile = '$lead_mobile' OR lead_id = $lead_id) AND executive_id IN (SELECT id FROM users WHERE organization_id = $org_id) ORDER BY call_time DESC LIMIT 50");

include 'includes/header.php';
?>

<div style="max-width: 1100px; margin: 0 auto; padding-bottom: 3rem;">
    <!-- Profile Header -->
    <div class="card" style="padding: 1.5rem; border-radius: 20px; border: none; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05); margin-bottom: 2rem; background: linear-gradient(to right, #ffffff, #fcfdff);">
        <div style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 1.5rem;">
            <div style="display: flex; align-items: center; gap: 1.25rem;">
                <div style="width: 64px; height: 64px; border-radius: 18px; background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%); color: white; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: 800; box-shadow: 0 8px 16px rgba(99, 102, 241, 0.25);">
                    <?php echo strtoupper(substr($lead['name'], 0, 1)); ?>
                </div>
                <div>
                    <h1 style="font-size: 1.5rem; font-weight: 800; color: var(--text-main); margin: 0; letter-spacing: -0.02em;"><?php echo htmlspecialchars($lead['name']); ?></h1>
                    <div style="display: flex; align-items: center; gap: 0.75rem; margin-top: 0.25rem;">
                        <span style="font-size: 0.875rem; font-weight: 600; color: var(--primary);"><i class="fas fa-phone-alt"></i> <?php echo $lead['mobile']; ?></span>
                        <span style="width: 4px; height: 4px; border-radius: 50%; background: #cbd5e1;"></span>
                        <span style="font-size: 0.75rem; color: var(--text-muted); font-weight: 500;"><i class="fas fa-calendar"></i> Added <?php echo date('d M, Y', strtotime($lead['created_at'])); ?></span>
                    </div>
                </div>
            </div>
            
            <div style="display: flex; gap: 0.75rem;">
                <a href="tel:<?php echo $lead['mobile']; ?>" class="action-pill call" title="Call Now">
                    <i class="fas fa-phone"></i> <span>Call</span>
                </a>
                <a href="https://wa.me/<?php echo $lead['mobile']; ?>" target="_blank" class="action-pill whatsapp" title="WhatsApp Message">
                    <i class="fab fa-whatsapp"></i> <span>WhatsApp</span>
                </a>
                <a href="lead_edit.php?id=<?php echo $lead_id; ?>" class="action-pill edit" title="Edit Lead">
                    <i class="fas fa-edit"></i> <span>Edit</span>
                </a>
            </div>
        </div>
        
        <!-- Quick Status Tracker -->
        <div style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid #f1f5f9; display: flex; align-items: center; gap: 1rem;">
            <span style="font-size: 0.7rem; font-weight: 800; text-transform: uppercase; color: var(--text-muted); letter-spacing: 0.05em;">Current Status:</span>
            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                <?php 
                $statuses = ['New', 'Follow-up', 'Interested', 'Converted', 'Lost'];
                foreach($statuses as $s): 
                    $active = ($lead['status'] == $s);
                ?>
                <span class="status-indicator <?php echo $active ? 'active' : ''; ?> <?php echo strtolower($s); ?>">
                    <?php if ($active): ?><i class="fas fa-check-circle"></i><?php endif; ?>
                    <?php echo $s; ?>
                </span>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 340px; gap: 2rem;">
        <!-- Left: Details Tabs -->
        <div>
            <div class="tab-header">
                <button class="tab-btn active" onclick="openTab(event, 'details')"><i class="fas fa-info-circle"></i> Basic Details</button>
                <button class="tab-btn" onclick="openTab(event, 'timeline')"><i class="fas fa-history"></i> Timeline</button>
                <button class="tab-btn" onclick="openTab(event, 'calls')"><i class="fas fa-phone-volume"></i> Calls & Records</button>
            </div>

            <div id="details" class="tab-content active">
                <div class="card" style="margin-top: 0; border-top-left-radius: 0; border-top-right-radius: 0;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                        <div>
                            <h4 class="section-title">Lead Information</h4>
                            <div class="info-grid">
                                <div class="info-item">
                                    <label>Source</label>
                                    <p><?php echo $lead['source_name'] ?: 'Direct / Unknown'; ?></p>
                                </div>
                                <div class="info-item">
                                    <label>Project / Category</label>
                                    <p><?php echo $lead['project_name'] ?: 'General'; ?></p>
                                </div>
                                <div class="info-item">
                                    <label>Assigned To</label>
                                    <p><?php echo $lead['executive_name'] ?: 'Unassigned'; ?></p>
                                </div>
                                <div class="info-item">
                                    <label>Last Updated</label>
                                    <p><?php echo date('d M, h:i A', strtotime($lead['updated_at'])); ?></p>
                                </div>
                            </div>
                        </div>

                        <?php if (!empty($custom_fields)): ?>
                        <div>
                            <h4 class="section-title">Additional Info</h4>
                            <div class="info-grid">
                                <?php foreach($custom_fields as $cf): 
                                    $val = $custom_data[$cf['id']] ?? '—';
                                ?>
                                <div class="info-item">
                                    <label><?php echo $cf['field_label']; ?></label>
                                    <p><?php echo htmlspecialchars($val); ?></p>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div style="margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid #f1f5f9;">
                        <h4 class="section-title">Latest Remarks</h4>
                        <p style="font-size: 0.875rem; color: var(--text-main); line-height: 1.6; background: #f8fafc; padding: 1rem; border-radius: 12px; border: 1px solid #f1f5f9;">
                            <?php echo nl2br(htmlspecialchars($lead['remarks'] ?: 'No remarks added yet.')); ?>
                        </p>
                    </div>
                </div>
            </div>

            <div id="timeline" class="tab-content">
                <div class="card" style="margin-top: 0; border-top-left-radius: 0; border-top-right-radius: 0;">
                    <div class="timeline">
                        <?php while ($h = mysqli_fetch_assoc($history_res)): ?>
                        <div class="timeline-item">
                            <div class="timeline-marker"></div>
                            <div class="timeline-content">
                                <div style="display: flex; justify-content: space-between; margin-bottom: 0.4rem;">
                                    <span style="font-weight: 700; font-size: 0.875rem;"><?php echo $h['executive_name']; ?></span>
                                    <span style="font-size: 0.75rem; color: var(--text-muted);"><?php echo date('d M, h:i A', strtotime($h['created_at'])); ?></span>
                                </div>
                                <p style="font-size: 0.875rem; margin: 0; color: var(--text-main);"><?php echo nl2br(htmlspecialchars($h['remark'])); ?></p>
                                <div style="margin-top: 0.6rem; font-size: 0.75rem; font-weight: 700; color: var(--primary);">
                                    <i class="far fa-calendar-alt"></i> Next: <?php echo date('d M Y', strtotime($h['next_follow_up_date'])); ?>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                        <?php if (mysqli_num_rows($history_res) === 0): ?>
                            <div style="text-align: center; color: var(--text-muted); padding: 3rem;">No follow-up history yet.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div id="calls" class="tab-content">
                <div class="card" style="margin-top: 0; border-top-left-radius: 0; border-top-right-radius: 0; padding: 0; overflow: hidden;">
                    <table class="modern-table">
                        <thead>
                            <tr>
                                <th>Type / Time</th>
                                <th>Duration</th>
                                <th>Recording</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            mysqli_data_seek($calls_res, 0); 
                            while ($call = mysqli_fetch_assoc($calls_res)): 
                            ?>
                            <tr>
                                <td>
                                    <div style="font-weight: 700; font-size: 0.8125rem; color: <?php echo $call['type'] == 'Incoming' ? '#10b981' : ($call['type'] == 'Missed' ? '#ef4444' : 'var(--primary)'); ?>;">
                                        <i class="fas <?php echo $call['type'] == 'Incoming' ? 'fa-arrow-down' : 'fa-arrow-up'; ?>"></i> <?php echo strtoupper($call['type']); ?>
                                    </div>
                                    <div style="font-size: 0.7rem; color: var(--text-muted);"><?php echo date('d M, h:i A', strtotime($call['call_time'])); ?></div>
                                </td>
                                <td style="font-weight: 600; font-size: 0.8125rem;"><?php echo floor($call['duration']/60).'m '.($call['duration']%60).'s'; ?></td>
                                <td>
                                    <?php if ($call['recording_path']): ?>
                                        <button class="btn btn-primary" style="padding: 0.25rem 0.6rem; font-size: 0.65rem;" onclick="playRecord('<?php echo $call['recording_path']; ?>')">
                                            <i class="fas fa-play"></i> Play
                                        </button>
                                    <?php else: ?>
                                        <span style="font-size: 0.65rem; color: #cbd5e1;">N/A</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            <?php if (mysqli_num_rows($calls_res) === 0): ?>
                            <tr><td colspan="3" style="text-align: center; padding: 3rem; color: var(--text-muted);">No records found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Right: Action Side -->
        <div style="position: sticky; top: 2rem; height: fit-content;">
            <div class="card" style="background: white; border: 1px solid #eef2f6; box-shadow: 0 4px 20px rgba(0,0,0,0.03);">
                <h3 style="font-size: 0.9rem; font-weight: 800; margin-bottom: 1.25rem; color: var(--text-main);">Log Follow-up</h3>
                <form action="" method="POST">
                    <input type="hidden" name="add_followup" value="1">
                    <div class="form-group">
                        <label class="form-label" style="font-size: 0.7rem;">Update Status</label>
                        <select name="status" class="form-control" style="height: 40px; font-size: 0.85rem;" required>
                            <?php foreach($statuses as $s): ?>
                            <option value="<?php echo $s; ?>" <?php echo $lead['status'] == $s ? 'selected' : ''; ?>><?php echo $s; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" style="font-size: 0.7rem;">Next Follow-up Date</label>
                        <input type="date" name="next_follow_up_date" class="form-control" style="height: 40px; font-size: 0.85rem;" required min="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label" style="font-size: 0.7rem;">Remarks / Notes</label>
                        <textarea name="remark" class="form-control" rows="4" style="font-size: 0.85rem;" placeholder="Write brief notes..." required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width: 100%; padding: 0.75rem; border-radius: 12px; font-weight: 700; margin-top: 0.5rem;">
                        Save Follow-up
                    </button>
                </form>
            </div>
            
            <div style="margin-top: 1.5rem; text-align: center;">
                <p style="font-size: 0.7rem; color: var(--text-muted); font-weight: 500;">
                    <i class="fas fa-lock"></i> Secured Lead Information
                </p>
            </div>
        </div>
    </div>
</div>

<style>
.action-pill {
    padding: 0.6rem 1.25rem;
    border-radius: 12px;
    font-size: 0.875rem;
    font-weight: 700;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.2s;
}
.action-pill.call { background: #eff6ff; color: #2563eb; }
.action-pill.whatsapp { background: #f0fdf4; color: #16a34a; }
.action-pill.edit { background: #f8fafc; color: #475569; }
.action-pill:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.05); }

.status-indicator {
    padding: 0.35rem 0.875rem;
    border-radius: 50px;
    font-size: 0.75rem;
    font-weight: 700;
    color: var(--text-muted);
    background: #f1f5f9;
    display: flex;
    align-items: center;
    gap: 0.4rem;
}
.status-indicator.active { color: white; }
.status-indicator.active.new { background: #3b82f6; }
.status-indicator.active.follow-up { background: #f59e0b; }
.status-indicator.active.interested { background: #8b5cf6; }
.status-indicator.active.converted { background: #10b981; }
.status-indicator.active.lost { background: #ef4444; }

.tab-header {
    display: flex;
    gap: 0.5rem;
}
.tab-btn {
    padding: 0.75rem 1.5rem;
    border: none;
    background: #f1f5f9;
    border-radius: 12px 12px 0 0;
    font-size: 0.8125rem;
    font-weight: 700;
    color: var(--text-muted);
    cursor: pointer;
    transition: all 0.2s;
}
.tab-btn.active {
    background: white;
    color: var(--primary);
}

.tab-content { display: none; }
.tab-content.active { display: block; animation: fadeIn 0.3s forwards; }

.section-title {
    font-size: 0.7rem;
    font-weight: 800;
    text-transform: uppercase;
    color: var(--text-muted);
    letter-spacing: 0.05em;
    margin-bottom: 1rem;
}

.info-grid { display: grid; gap: 0.875rem; }
.info-item label { display: block; font-size: 0.7rem; color: var(--text-muted); margin-bottom: 0.15rem; }
.info-item p { font-size: 0.875rem; font-weight: 700; color: var(--text-main); margin: 0; }

.timeline { position: relative; padding-left: 1.5rem; margin-top: 1rem; }
.timeline::before {
    content: '';
    position: absolute;
    left: 4px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #f1f5f9;
}
.timeline-item { position: relative; margin-bottom: 2rem; }
.timeline-marker {
    position: absolute;
    left: -1.5rem;
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: #cbd5e1;
    border: 2px solid white;
    z-index: 1;
}
.timeline-item:first-child .timeline-marker { background: var(--primary); outline: 4px solid #eff6ff; }
.timeline-content {
    background: #ffffff;
    border: 1px solid #f1f5f9;
    padding: 1rem;
    border-radius: 12px;
}

.modern-table { width: 100%; border-collapse: collapse; }
.modern-table th { background: #f8fafc; padding: 0.75rem 1rem; text-align: left; font-size: 0.7rem; color: var(--text-muted); font-weight: 800; text-transform: uppercase; }
.modern-table td { padding: 1rem; border-bottom: 1px solid #f1f5f9; }

@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
</style>

<script>
function openTab(evt, tabName) {
    var i, tabcontent, tablinks;
    tabcontent = document.getElementsByClassName("tab-content");
    for (i = 0; i < tabcontent.length; i++) {
        tabcontent[i].style.display = "none";
        tabcontent[i].classList.remove("active");
    }
    tablinks = document.getElementsByClassName("tab-btn");
    for (i = 0; i < tablinks.length; i++) {
        tablinks[i].className = tablinks[i].className.replace(" active", "");
    }
    document.getElementById(tabName).style.display = "block";
    document.getElementById(tabName).classList.add("active");
    evt.currentTarget.className += " active";
}
</script>

<?php include 'includes/footer.php'; ?>
