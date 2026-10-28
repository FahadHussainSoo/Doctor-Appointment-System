<?php
require_once '../includes/config.php';
requireAdmin();
require_once '../includes/auth.php';

$auth = new Auth($pdo);
$message = '';

// Handle user actions (unchanged from Phase 1)
if (isset($_POST['add_user'])) {
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $phone = $_POST['phone'];
    $date_of_birth = $_POST['date_of_birth'];
    $address = $_POST['address'];
    $role = $_POST['role'];

    if ($auth->register($first_name, $last_name, $email, $password, $phone, $date_of_birth, $address)) {
        if ($role !== 'patient') {
            $user_id = $pdo->lastInsertId();
            $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
            $stmt->execute([$role, $user_id]);
        }
        $message = "User added successfully!";
    } else {
        $message = "Error adding user. Email may already exist.";
    }
}

if (isset($_POST['update_user'])) {
    $id = $_POST['id'];
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $date_of_birth = $_POST['date_of_birth'];
    $address = $_POST['address'];
    $role = $_POST['role'];

    if ($auth->updateUser($id, $first_name, $last_name, $email, $phone, $date_of_birth, $address, $role)) {
        $message = "User updated successfully!";
    } else {
        $message = "Error updating user.";
    }
}

if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    if ($auth->deleteUser($id)) {
        $message = "User deleted successfully!";
    } else {
        $message = "Error deleting user.";
    }
}

$users = $auth->getAllUsers();
?>
<?php
$pageTitle = 'Manage Users';
$dashRole = 'admin';
$activeMenu = 'users';
$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => 'index.php'],
    ['label' => 'Users'],
];
require '../includes/dash_header.php';
?>
            <div class="crud-toolbar">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800">Manage Users</h1>
                    <p class="text-gray-500" style="margin-top:.25rem;"><?php echo count($users); ?> account<?php echo count($users) == 1 ? '' : 's'; ?> registered</p>
                </div>
                <button type="button" class="crud-add-btn" data-bs-toggle="modal" data-bs-target="#addUserModal">
                    <i class="fas fa-user-plus"></i> Add New User
                </button>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-success mb-4" data-auto-dismiss><i class="fas fa-circle-check me-1"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <!-- Users Table -->
            <div class="crud-table-card">
                <div class="crud-table-header">
                    <h2><i class="fas fa-users"></i> All Users</h2>
                </div>
                <div class="crud-table-scroll">
                    <table class="mb-datatable table align-middle w-100">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Role</th>
                                <th>Joined</th>
                                <th class="no-sort">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td class="font-medium"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo htmlspecialchars($user['phone'] ?: 'N/A'); ?></td>
                                <td>
                                    <span class="crud-badge crud-badge-<?php echo $user['role']; ?>">
                                        <?php echo ucfirst($user['role']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <div class="crud-actions">
                                        <button type="button" class="crud-act-btn crud-act-edit"
                                                data-edit-trigger="#editUserModal"
                                                data-field-id="<?php echo $user['id']; ?>"
                                                data-field-first_name="<?php echo htmlspecialchars($user['first_name'], ENT_QUOTES); ?>"
                                                data-field-last_name="<?php echo htmlspecialchars($user['last_name'], ENT_QUOTES); ?>"
                                                data-field-email="<?php echo htmlspecialchars($user['email'], ENT_QUOTES); ?>"
                                                data-field-phone="<?php echo htmlspecialchars($user['phone'], ENT_QUOTES); ?>"
                                                data-field-date_of_birth="<?php echo htmlspecialchars($user['date_of_birth']); ?>"
                                                data-field-address="<?php echo htmlspecialchars($user['address'], ENT_QUOTES); ?>"
                                                data-field-role="<?php echo $user['role']; ?>">
                                            <i class="fas fa-pen"></i> Edit
                                        </button>
                                        <button type="button" class="crud-act-btn crud-act-delete"
                                                data-delete-trigger="#deleteUserModal"
                                                data-delete-href="?delete=<?php echo $user['id']; ?>"
                                                data-delete-label="<?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name'], ENT_QUOTES); ?>">
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

            <!-- Add User Modal -->
            <div class="modal fade" id="addUserModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content">
                        <form method="POST" class="crud-form">
                            <div class="modal-header">
                                <h5 class="modal-title"><i class="fas fa-user-plus"></i> Add New User</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">First Name</label>
                                        <input type="text" name="first_name" required class="form-control">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Last Name</label>
                                        <input type="text" name="last_name" required class="form-control">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Email</label>
                                        <input type="email" name="email" required class="form-control">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Password</label>
                                        <input type="password" name="password" required class="form-control">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Phone</label>
                                        <input type="text" name="phone" class="form-control">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Role</label>
                                        <select name="role" class="form-select">
                                            <option value="patient">Patient</option>
                                            <option value="admin">Admin</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Date of Birth</label>
                                        <input type="date" name="date_of_birth" class="form-control">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Address</label>
                                        <textarea name="address" rows="2" class="form-control"></textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" name="add_user" class="btn btn-primary"><i class="fas fa-check"></i> Add User</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Edit User Modal -->
            <div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content">
                        <form method="POST" class="crud-form">
                            <div class="modal-header">
                                <h5 class="modal-title"><i class="fas fa-pen"></i> Edit User</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <input type="hidden" name="id">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">First Name</label>
                                        <input type="text" name="first_name" required class="form-control">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Last Name</label>
                                        <input type="text" name="last_name" required class="form-control">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Email</label>
                                        <input type="email" name="email" required class="form-control">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Phone</label>
                                        <input type="text" name="phone" class="form-control">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Role</label>
                                        <select name="role" class="form-select">
                                            <option value="patient">Patient</option>
                                            <option value="admin">Admin</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Date of Birth</label>
                                        <input type="date" name="date_of_birth" class="form-control">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Address</label>
                                        <textarea name="address" rows="2" class="form-control"></textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" name="update_user" class="btn btn-primary"><i class="fas fa-check"></i> Save Changes</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Delete Confirm Modal -->
            <div class="modal fade" id="deleteUserModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header" style="background: linear-gradient(135deg,#EF4444,#F43F5E);">
                            <h5 class="modal-title"><i class="fas fa-triangle-exclamation"></i> Delete User</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p>Are you sure you want to delete <strong data-delete-name>this user</strong>? This action cannot be undone.</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <a href="#" data-delete-confirm class="btn btn-danger"><i class="fas fa-trash"></i> Delete</a>
                        </div>
                    </div>
                </div>
            </div>
<?php require '../includes/dash_footer.php'; ?>
