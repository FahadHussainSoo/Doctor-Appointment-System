<?php
/**
 * Phase 3A — Department Management.
 *
 * New page. There was no admin UI for "departments" before this phase —
 * the app's closest existing concept is the `specialties` table (already
 * used everywhere as a doctor's department/specialty). This page adds
 * CRUD for that existing table; it does not create any new table and does
 * not modify any other page's queries or handlers.
 */
require_once '../includes/config.php';
requireAdmin();

$message = '';
$errorMsg = '';

if (isset($_POST['add_department'])) {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $icon = trim($_POST['icon']);

    if ($name === '') {
        $errorMsg = "Department name is required.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO specialties (name, description, icon) VALUES (?, ?, ?)");
        if ($stmt->execute([$name, $description, $icon])) {
            $message = "Department added successfully!";
        } else {
            $errorMsg = "Error adding department.";
        }
    }
}

if (isset($_POST['update_department'])) {
    $id = $_POST['id'];
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $icon = trim($_POST['icon']);

    if ($name === '') {
        $errorMsg = "Department name is required.";
    } else {
        $stmt = $pdo->prepare("UPDATE specialties SET name = ?, description = ?, icon = ? WHERE id = ?");
        if ($stmt->execute([$name, $description, $icon, $id])) {
            $message = "Department updated successfully!";
        } else {
            $errorMsg = "Error updating department.";
        }
    }
}

if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    try {
        $stmt = $pdo->prepare("DELETE FROM specialties WHERE id = ?");
        $stmt->execute([$id]);
        $message = "Department deleted successfully!";
    } catch (PDOException $e) {
        // Specialties are referenced by doctors.specialty_id (FK) — block delete gracefully.
        $errorMsg = "Cannot delete this department — one or more doctors are still assigned to it.";
    }
}

// Departments with a live doctor count (read-only aggregate, no schema change)
$departments = $pdo->query("
    SELECT s.*, COUNT(d.id) as doctor_count
    FROM specialties s
    LEFT JOIN doctors d ON d.specialty_id = s.id
    GROUP BY s.id
    ORDER BY s.name
")->fetchAll(PDO::FETCH_ASSOC);
?>
<?php
$pageTitle = 'Manage Departments';
$dashRole = 'admin';
$activeMenu = 'departments';
$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => 'index.php'],
    ['label' => 'Departments'],
];
require '../includes/dash_header.php';
?>
            <div class="crud-toolbar">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800">Manage Departments</h1>
                    <p class="text-gray-500" style="margin-top:.25rem;"><?php echo count($departments); ?> department<?php echo count($departments) == 1 ? '' : 's'; ?> configured</p>
                </div>
                <button type="button" class="crud-add-btn" data-bs-toggle="modal" data-bs-target="#addDepartmentModal">
                    <i class="fas fa-plus"></i> Add New Department
                </button>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-success mb-4" data-auto-dismiss><i class="fas fa-circle-check me-1"></i> <?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            <?php if ($errorMsg): ?>
                <div class="alert alert-danger mb-4"><i class="fas fa-circle-exclamation me-1"></i> <?php echo htmlspecialchars($errorMsg); ?></div>
            <?php endif; ?>

            <!-- Departments Table -->
            <div class="crud-table-card">
                <div class="crud-table-header">
                    <h2><i class="fas fa-hospital"></i> All Departments</h2>
                </div>
                <div class="crud-table-scroll">
                    <table class="mb-datatable table align-middle w-100">
                        <thead>
                            <tr>
                                <th>Department</th>
                                <th>Description</th>
                                <th>Doctors</th>
                                <th class="no-sort">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($departments as $dept): ?>
                            <tr>
                                <td>
                                    <div class="font-medium"><i class="fas fa-<?php echo htmlspecialchars($dept['icon'] ?: 'notes-medical'); ?>" style="color:var(--color-primary); margin-right:.4rem;"></i><?php echo htmlspecialchars($dept['name']); ?></div>
                                </td>
                                <td><div class="max-w-xs truncate"><?php echo htmlspecialchars($dept['description'] ?: '—'); ?></div></td>
                                <td>
                                    <span class="crud-badge <?php echo $dept['doctor_count'] > 0 ? 'crud-badge-active' : 'crud-badge-inactive'; ?>">
                                        <?php echo (int)$dept['doctor_count']; ?> doctor<?php echo $dept['doctor_count'] == 1 ? '' : 's'; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="crud-actions">
                                        <button type="button" class="crud-act-btn crud-act-edit"
                                                data-edit-trigger="#editDepartmentModal"
                                                data-field-id="<?php echo $dept['id']; ?>"
                                                data-field-name="<?php echo htmlspecialchars($dept['name'], ENT_QUOTES); ?>"
                                                data-field-description="<?php echo htmlspecialchars($dept['description'], ENT_QUOTES); ?>"
                                                data-field-icon="<?php echo htmlspecialchars($dept['icon'], ENT_QUOTES); ?>">
                                            <i class="fas fa-pen"></i> Edit
                                        </button>
                                        <button type="button" class="crud-act-btn crud-act-delete"
                                                data-delete-trigger="#deleteDepartmentModal"
                                                data-delete-href="?delete=<?php echo $dept['id']; ?>"
                                                data-delete-label="<?php echo htmlspecialchars($dept['name'], ENT_QUOTES); ?>">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Add Department Modal -->
            <div class="modal fade" id="addDepartmentModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <form method="POST" class="crud-form">
                            <div class="modal-header">
                                <h5 class="modal-title"><i class="fas fa-hospital"></i> Add New Department</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label class="form-label">Name</label>
                                    <input type="text" name="name" required class="form-control" placeholder="e.g., Cardiology">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Description</label>
                                    <textarea name="description" rows="3" class="form-control"></textarea>
                                </div>
                                <div class="mb-1">
                                    <label class="form-label">Icon <span class="form-text">(Font Awesome name, e.g. "heart")</span></label>
                                    <input type="text" name="icon" class="form-control" placeholder="heart">
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" name="add_department" class="btn btn-primary"><i class="fas fa-check"></i> Add Department</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Edit Department Modal -->
            <div class="modal fade" id="editDepartmentModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <form method="POST" class="crud-form">
                            <div class="modal-header">
                                <h5 class="modal-title"><i class="fas fa-pen"></i> Edit Department</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <input type="hidden" name="id">
                                <div class="mb-3">
                                    <label class="form-label">Name</label>
                                    <input type="text" name="name" required class="form-control">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Description</label>
                                    <textarea name="description" rows="3" class="form-control"></textarea>
                                </div>
                                <div class="mb-1">
                                    <label class="form-label">Icon <span class="form-text">(Font Awesome name)</span></label>
                                    <input type="text" name="icon" class="form-control">
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" name="update_department" class="btn btn-primary"><i class="fas fa-check"></i> Save Changes</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Delete Confirm Modal -->
            <div class="modal fade" id="deleteDepartmentModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header" style="background: linear-gradient(135deg,#EF4444,#F43F5E);">
                            <h5 class="modal-title"><i class="fas fa-triangle-exclamation"></i> Delete Department</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p>Are you sure you want to delete <strong data-delete-name>this department</strong>? Departments with doctors assigned cannot be deleted.</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <a href="#" data-delete-confirm class="btn btn-danger"><i class="fas fa-trash"></i> Delete</a>
                        </div>
                    </div>
                </div>
            </div>
<?php require '../includes/dash_footer.php'; ?>
