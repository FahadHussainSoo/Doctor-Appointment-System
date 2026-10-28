<?php
require_once '../includes/config.php';
requireAdmin();

$message = '';

// Handle doctor actions (unchanged from Phase 1)
if (isset($_POST['add_doctor'])) {
    $user_id = $_POST['user_id'];
    $specialty_id = $_POST['specialty_id'];
    $qualification = $_POST['qualification'];
    $experience_years = $_POST['experience_years'];
    $consultation_fee = $_POST['consultation_fee'];
    $bio = $_POST['bio'];
    $available_days = $_POST['available_days'];
    $available_time_start = $_POST['available_time_start'];
    $available_time_end = $_POST['available_time_end'];

    $stmt = $pdo->prepare("INSERT INTO doctors (user_id, specialty_id, qualification, experience_years, consultation_fee, bio, available_days, available_time_start, available_time_end) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    if ($stmt->execute([$user_id, $specialty_id, $qualification, $experience_years, $consultation_fee, $bio, $available_days, $available_time_start, $available_time_end])) {
        $message = "Doctor added successfully!";
    } else {
        $message = "Error adding doctor.";
    }
}

if (isset($_POST['update_doctor'])) {
    $id = $_POST['id'];
    $specialty_id = $_POST['specialty_id'];
    $qualification = $_POST['qualification'];
    $experience_years = $_POST['experience_years'];
    $consultation_fee = $_POST['consultation_fee'];
    $bio = $_POST['bio'];
    $available_days = $_POST['available_days'];
    $available_time_start = $_POST['available_time_start'];
    $available_time_end = $_POST['available_time_end'];
    $status = $_POST['status'];

    $stmt = $pdo->prepare("UPDATE doctors SET specialty_id = ?, qualification = ?, experience_years = ?, consultation_fee = ?, bio = ?, available_days = ?, available_time_start = ?, available_time_end = ?, status = ? WHERE id = ?");
    if ($stmt->execute([$specialty_id, $qualification, $experience_years, $consultation_fee, $bio, $available_days, $available_time_start, $available_time_end, $status, $id])) {
        $message = "Doctor updated successfully!";
    } else {
        $message = "Error updating doctor.";
    }
}

if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM doctors WHERE id = ?");
    if ($stmt->execute([$id])) {
        $message = "Doctor deleted successfully!";
    } else {
        $message = "Error deleting doctor.";
    }
}

// Get all doctors
$doctors = $pdo->query("
    SELECT d.*, u.first_name, u.last_name, u.email, s.name as specialty_name
    FROM doctors d
    JOIN users u ON d.user_id = u.id
    JOIN specialties s ON d.specialty_id = s.id
    ORDER BY d.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Get all users for dropdown
$users = $pdo->query("SELECT id, first_name, last_name, email FROM users WHERE role = 'patient'")->fetchAll(PDO::FETCH_ASSOC);

// Get all specialties
$specialties = $pdo->query("SELECT * FROM specialties ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
?>
<?php
$pageTitle = 'Manage Doctors';
$dashRole = 'admin';
$activeMenu = 'doctors';
$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => 'index.php'],
    ['label' => 'Doctors'],
];
require '../includes/dash_header.php';
?>
            <div class="crud-toolbar">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800">Manage Doctors</h1>
                    <p class="text-gray-500" style="margin-top:.25rem;"><?php echo count($doctors); ?> doctor<?php echo count($doctors) == 1 ? '' : 's'; ?> on record</p>
                </div>
                <button type="button" class="crud-add-btn" data-bs-toggle="modal" data-bs-target="#addDoctorModal">
                    <i class="fas fa-plus"></i> Add New Doctor
                </button>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-success mb-4" data-auto-dismiss><i class="fas fa-circle-check me-1"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <!-- Doctors Table -->
            <div class="crud-table-card">
                <div class="crud-table-header">
                    <h2><i class="fas fa-user-md"></i> All Doctors</h2>
                </div>
                <div class="crud-table-scroll">
                    <table class="mb-datatable table align-middle w-100">
                        <thead>
                            <tr>
                                <th>Doctor</th>
                                <th>Specialty</th>
                                <th>Experience</th>
                                <th>Fee</th>
                                <th>Status</th>
                                <th class="no-sort">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($doctors as $doctor): ?>
                            <tr>
                                <td>
                                    <div class="font-medium">Dr. <?php echo htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name']); ?></div>
                                    <div class="text-gray-500 text-sm"><?php echo htmlspecialchars($doctor['email']); ?></div>
                                </td>
                                <td><?php echo htmlspecialchars($doctor['specialty_name']); ?></td>
                                <td><?php echo (int)$doctor['experience_years']; ?> years</td>
                                <td>$<?php echo htmlspecialchars($doctor['consultation_fee']); ?></td>
                                <td>
                                    <span class="crud-badge crud-badge-<?php echo $doctor['status'] == 'active' ? 'active' : 'inactive'; ?>">
                                        <?php echo ucfirst($doctor['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="crud-actions">
                                        <button type="button" class="crud-act-btn crud-act-edit"
                                                data-edit-trigger="#editDoctorModal"
                                                data-field-id="<?php echo $doctor['id']; ?>"
                                                data-field-specialty_id="<?php echo $doctor['specialty_id']; ?>"
                                                data-field-qualification="<?php echo htmlspecialchars($doctor['qualification'], ENT_QUOTES); ?>"
                                                data-field-experience_years="<?php echo $doctor['experience_years']; ?>"
                                                data-field-consultation_fee="<?php echo $doctor['consultation_fee']; ?>"
                                                data-field-bio="<?php echo htmlspecialchars($doctor['bio'], ENT_QUOTES); ?>"
                                                data-field-available_days="<?php echo htmlspecialchars($doctor['available_days'], ENT_QUOTES); ?>"
                                                data-field-available_time_start="<?php echo htmlspecialchars($doctor['available_time_start']); ?>"
                                                data-field-available_time_end="<?php echo htmlspecialchars($doctor['available_time_end']); ?>"
                                                data-field-status="<?php echo $doctor['status']; ?>">
                                            <i class="fas fa-pen"></i> Edit
                                        </button>
                                        <button type="button" class="crud-act-btn crud-act-delete"
                                                data-delete-trigger="#deleteDoctorModal"
                                                data-delete-href="?delete=<?php echo $doctor['id']; ?>"
                                                data-delete-label="Dr. <?php echo htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name'], ENT_QUOTES); ?>">
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

            <!-- Add Doctor Modal -->
            <div class="modal fade" id="addDoctorModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content">
                        <form method="POST" class="crud-form">
                            <div class="modal-header">
                                <h5 class="modal-title"><i class="fas fa-user-md"></i> Add New Doctor</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Select User</label>
                                        <select name="user_id" required class="form-select">
                                            <option value="">Select User</option>
                                            <?php foreach ($users as $user): ?>
                                                <option value="<?php echo $user['id']; ?>">
                                                    <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name'] . ' (' . $user['email'] . ')'); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Specialty</label>
                                        <select name="specialty_id" required class="form-select">
                                            <option value="">Select Specialty</option>
                                            <?php foreach ($specialties as $specialty): ?>
                                                <option value="<?php echo $specialty['id']; ?>"><?php echo htmlspecialchars($specialty['name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Qualification</label>
                                        <input type="text" name="qualification" required class="form-control">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Experience (Years)</label>
                                        <input type="number" name="experience_years" required class="form-control">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Consultation Fee</label>
                                        <input type="number" step="0.01" name="consultation_fee" required class="form-control">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Available Days</label>
                                        <input type="text" name="available_days" placeholder="e.g., Mon,Wed,Fri" class="form-control">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Start Time</label>
                                        <input type="time" name="available_time_start" class="form-control">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">End Time</label>
                                        <input type="time" name="available_time_end" class="form-control">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Bio</label>
                                        <textarea name="bio" rows="3" class="form-control"></textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" name="add_doctor" class="btn btn-primary"><i class="fas fa-check"></i> Add Doctor</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Edit Doctor Modal -->
            <div class="modal fade" id="editDoctorModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content">
                        <form method="POST" class="crud-form">
                            <div class="modal-header">
                                <h5 class="modal-title"><i class="fas fa-pen"></i> Edit Doctor</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <input type="hidden" name="id">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Specialty</label>
                                        <select name="specialty_id" required class="form-select">
                                            <?php foreach ($specialties as $specialty): ?>
                                                <option value="<?php echo $specialty['id']; ?>"><?php echo htmlspecialchars($specialty['name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Status</label>
                                        <select name="status" required class="form-select">
                                            <option value="active">Active</option>
                                            <option value="inactive">Inactive</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Qualification</label>
                                        <input type="text" name="qualification" required class="form-control">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Experience (Years)</label>
                                        <input type="number" name="experience_years" required class="form-control">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Consultation Fee</label>
                                        <input type="number" step="0.01" name="consultation_fee" required class="form-control">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Available Days</label>
                                        <input type="text" name="available_days" placeholder="e.g., Mon,Wed,Fri" class="form-control">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Start Time</label>
                                        <input type="time" name="available_time_start" class="form-control">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">End Time</label>
                                        <input type="time" name="available_time_end" class="form-control">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Bio</label>
                                        <textarea name="bio" rows="3" class="form-control"></textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" name="update_doctor" class="btn btn-primary"><i class="fas fa-check"></i> Save Changes</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Delete Confirm Modal -->
            <div class="modal fade" id="deleteDoctorModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header" style="background: linear-gradient(135deg,#EF4444,#F43F5E);">
                            <h5 class="modal-title"><i class="fas fa-triangle-exclamation"></i> Delete Doctor</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p>Are you sure you want to delete <strong data-delete-name>this doctor</strong>? This action cannot be undone.</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <a href="#" data-delete-confirm class="btn btn-danger"><i class="fas fa-trash"></i> Delete</a>
                        </div>
                    </div>
                </div>
            </div>
<?php require '../includes/dash_footer.php'; ?>
