<?php
// projects.php
require_once 'includes/header.php';

if ($role !== 'admin') {
    echo "<script>window.location.href='index.php';</script>";
    exit();
}

$org_id = $_SESSION['organization_id'];
$success = '';
$error = '';

// Handle Add Project
if (isset($_POST['add_project'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    if (!empty($name)) {
        $sql = "INSERT INTO projects (organization_id, name) VALUES ($org_id, '$name')";
        if (mysqli_query($conn, $sql)) {
            $success = "Project category added successfully!";
        } else {
            if (mysqli_errno($conn) == 1062) {
                $error = "This project category already exists.";
            } else {
                $error = "Failed to add project.";
            }
        }
    }
}

// Handle Delete Project
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $sql = "DELETE FROM projects WHERE id = $id AND organization_id = $org_id";
    if (mysqli_query($conn, $sql)) {
        $success = "Project deleted successfully!";
    } else {
        $error = "Failed to delete project.";
    }
}

// Fetch Projects
$sql = "SELECT p.*, (SELECT COUNT(*) FROM leads l WHERE l.project_id = p.id) as lead_count 
        FROM projects p WHERE p.organization_id = $org_id ORDER BY name ASC";
$result = mysqli_query($conn, $sql);
$projects = [];
while ($row = mysqli_fetch_assoc($result)) {
    $projects[] = $row;
}
?>

<div class="page-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
    <div>
        <h1 style="font-size: 1.5rem; font-weight: 800; color: var(--text-main); margin-bottom: 0.25rem;">Project Categories</h1>
        <p style="font-size: 0.875rem; color: var(--text-muted);">Manage global projects and categories for lead assignment.</p>
    </div>
    <button onclick="document.getElementById('addModal').style.display='flex'" class="btn btn-primary" style="display: flex; align-items: center; gap: 0.5rem; padding: 0.625rem 1.25rem;">
        <i class="fas fa-plus"></i> Add Category
    </button>
</div>

<?php if ($success): ?>
    <div style="background: #f0fdf4; border: 1px solid #bbfcce; color: #166534; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;">
        <i class="fas fa-check-circle" style="margin-right: 0.5rem;"></i> <?php echo $success; ?>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div style="background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;">
        <i class="fas fa-exclamation-circle" style="margin-right: 0.5rem;"></i> <?php echo $error; ?>
    </div>
<?php endif; ?>

<div class="card" style="padding: 0; overflow: hidden; border-radius: 12px;">
    <table class="table" style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="background: #f8fafc; border-bottom: 1px solid var(--border);">
                <th style="padding: 1.25rem; text-align: left; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em;">Category Name</th>
                <th style="padding: 1.25rem; text-align: left; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em;">Leads Linked</th>
                <th style="padding: 1.25rem; text-align: right; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($projects)): ?>
                <tr>
                    <td colspan="3" style="padding: 3rem; text-align: center; color: var(--text-muted);">
                        <i class="fas fa-folder-open" style="font-size: 2rem; display: block; margin-bottom: 1rem; opacity: 0.3;"></i>
                        No categories found. Add your first project category to get started.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($projects as $proj): ?>
                    <tr style="border-bottom: 1px solid var(--border);">
                        <td style="padding: 1.25rem;">
                            <div style="display: flex; align-items: center; gap: 0.75rem;">
                                <div style="width: 36px; height: 36px; border-radius: 10px; background: #f5f3ff; color: var(--primary); display: flex; align-items: center; justify-content: center;">
                                    <i class="fas fa-briefcase"></i>
                                </div>
                                <span style="font-weight: 600; color: var(--text-main);"><?php echo $proj['name']; ?></span>
                            </div>
                        </td>
                        <td style="padding: 1.25rem;">
                            <span style="background: #eef2ff; color: var(--primary); padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.75rem; font-weight: 700;">
                                <?php echo $proj['lead_count']; ?> Leads
                            </span>
                        </td>
                        <td style="padding: 1.25rem; text-align: right;">
                            <a href="?delete=<?php echo $proj['id']; ?>" onclick="return confirm('Are you sure you want to delete this category?')" style="color: var(--danger); font-size: 0.875rem; padding: 0.5rem; border-radius: 6px; transition: all 0.2s;" onmouseover="this.style.background='#fef2f2'" onmouseout="this.style.background='transparent'">
                                <i class="fas fa-trash-alt"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Add Modal -->
<div id="addModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.7); z-index: 1000; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
    <div style="background: white; border-radius: 16px; width: 100%; max-width: 450px; padding: 2rem; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h2 style="font-size: 1.25rem; font-weight: 800; color: var(--text-main);">Add New Category</h2>
            <button onclick="document.getElementById('addModal').style.display='none'" style="background: none; border: none; color: var(--text-muted); cursor: pointer;"><i class="fas fa-times"></i></button>
        </div>
        <form method="POST">
            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; font-size: 0.875rem; font-weight: 600; color: var(--text-main); margin-bottom: 0.5rem;">Category Name</label>
                <input type="text" name="name" required placeholder="e.g., Luxury Apartments" style="width: 100%; padding: 0.75rem 1rem; border: 1px solid var(--border); border-radius: 8px; font-size: 0.875rem;">
            </div>
            <button type="submit" name="add_project" class="btn btn-primary" style="width: 100%; padding: 0.75rem; font-weight: 700;">Create Category</button>
        </form>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
