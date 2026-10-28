<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/uploads.php';
requireAdmin();

$user_id = $_SESSION['user_id'];
$errors = [];
$success = null;

// ---------------------------------------------------------------
// Handle form submissions — personal info + avatar only. Password
// change lives on the Settings page (admin/settings.php) to avoid
// two separate "change password" forms doing the same thing.
// ---------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $date_of_birth = trim($_POST['date_of_birth'] ?? '') ?: null;
        $address = trim($_POST['address'] ?? '');

        if ($first_name === '' || $last_name === '') {
            $errors[] = 'First and last name are required.';
        } else {
            $auth = new Auth($pdo);
            $auth->updateProfile($user_id, $first_name, $last_name, $phone, $date_of_birth, $address);
            $_SESSION['first_name'] = $first_name;
            $_SESSION['last_name'] = $last_name;
            $success = 'Your profile has been updated.';
        }
    }

    if ($action === 'upload_avatar' && isset($_FILES['avatar'])) {
        $result = mb_save_avatar($pdo, $user_id, $_FILES['avatar'], '../');
        if ($result['success']) {
            $success = $result['message'];
        } else {
            $errors[] = $result['message'];
        }
    }
}

// ---------------------------------------------------------------
// Read-only data
// ---------------------------------------------------------------
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$profile = $stmt->fetch(PDO::FETCH_ASSOC);
$avatar_url = mb_get_avatar($pdo, $user_id, '../');

$users_count = (int) $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$doctors_count = (int) $pdo->query("SELECT COUNT(*) FROM doctors")->fetchColumn();
$patients_count = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'patient'")->fetchColumn();
$appointments_count = (int) $pdo->query("SELECT COUNT(*) FROM appointments")->fetchColumn();
$departments_count = (int) $pdo->query("SELECT COUNT(*) FROM specialties")->fetchColumn();

$revenue_stmt = $pdo->query("
    SELECT COALESCE(SUM(d.consultation_fee), 0) as total
    FROM appointments a
    JOIN doctors d ON a.doctor_id = d.id
    WHERE a.status = 'completed'
");
$total_revenue = (float) $revenue_stmt->fetchColumn();

// "Recent system activity" — a read-only merge of the two most meaningful
// event streams already in the schema (new signups, new bookings). There's
// no dedicated audit-log table yet, so this is presented as recent activity
// rather than a personal action log.
$recent_users_stmt = $pdo->query("SELECT id, first_name, last_name, role, created_at FROM users ORDER BY created_at DESC LIMIT 6");
$recent_users = $recent_users_stmt->fetchAll(PDO::FETCH_ASSOC);

$recent_appts_stmt = $pdo->query("
    SELECT a.id, a.created_at, a.status, u.first_name as patient_first, u.last_name as patient_last,
           ud.first_name as doctor_first, ud.last_name as doctor_last
    FROM appointments a
    JOIN users u ON a.patient_id = u.id
    JOIN doctors d ON a.doctor_id = d.id
    JOIN users ud ON d.user_id = ud.id
    ORDER BY a.created_at DESC LIMIT 6
");
$recent_appts = $recent_appts_stmt->fetchAll(PDO::FETCH_ASSOC);

$activity = [];
foreach ($recent_users as $u) {
    $activity[] = ['time' => $u['created_at'], 'icon' => 'fa-user-plus', 'color' => 'linear-gradient(135deg,#10B981,#06B6D4)',
        'text' => '<strong>' . htmlspecialchars($u['first_name'] . ' ' . $u['last_name']) . '</strong> registered as ' . htmlspecialchars($u['role'])];
}
foreach ($recent_appts as $a) {
    $activity[] = ['time' => $a['created_at'], 'icon' => 'fa-calendar-plus', 'color' => 'linear-gradient(135deg,#4F46E5,#8B5CF6)',
        'text' => '<strong>' . htmlspecialchars($a['patient_first'] . ' ' . $a['patient_last']) . '</strong> booked with Dr. ' . htmlspecialchars($a['doctor_first'] . ' ' . $a['doctor_last'])];
}
usort($activity, fn($x, $y) => strtotime($y['time']) <=> strtotime($x['time']));
$activity = array_slice($activity, 0, 8);

$pageTitle = 'My Profile';
$dashRole = 'admin';
$activeMenu = 'profile';
$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => 'index.php'],
    ['label' => 'My Profile'],
];
require '../includes/dash_header.php';
?>

<?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show"><?php echo htmlspecialchars($success); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>
<?php if (!empty($errors)): ?>
    <div class="alert alert-danger"><ul class="mb-0 ps-3"><?php foreach ($errors as $e) echo '<li>' . htmlspecialchars($e) . '</li>'; ?></ul></div>
<?php endif; ?>

<!-- ============ Profile hero ============ -->
<div class="profile-hero">
    <div class="profile-hero-cover"></div>
    <div class="profile-hero-avatar-wrap">
        <?php if ($avatar_url): ?>
            <img src="<?php echo htmlspecialchars($avatar_url); ?>" alt="" class="profile-avatar">
        <?php else: ?>
            <div class="profile-avatar"><?php echo strtoupper(substr($profile['first_name'], 0, 1) . substr($profile['last_name'], 0, 1)); ?></div>
        <?php endif; ?>
        <a href="#personal" class="profile-avatar-edit" onclick="document.querySelector('[data-tab-target=personal]').click();" title="Change photo">
            <i class="fas fa-camera"></i>
        </a>
    </div>
    <div class="profile-hero-body">
        <div class="profile-hero-identity">
            <h1 class="profile-hero-name"><?php echo htmlspecialchars($profile['first_name'] . ' ' . $profile['last_name']); ?></h1>
            <span class="profile-hero-role"><i class="fas fa-shield-halved"></i> System Administrator</span>
            <div class="profile-hero-meta">
                <span><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($profile['email']); ?></span>
                <?php if (!empty($profile['phone'])): ?><span><i class="fas fa-phone"></i> <?php echo htmlspecialchars($profile['phone']); ?></span><?php endif; ?>
                <span><i class="fas fa-calendar"></i> Admin since <?php echo date('M Y', strtotime($profile['created_at'])); ?></span>
            </div>
        </div>
        <div class="profile-hero-actions">
            <a href="settings.php" class="mb-btn mb-btn-primary"><i class="fas fa-gear"></i> Settings</a>
        </div>
    </div>
</div>

<!-- ============ Tabs ============ -->
<div class="mb-tabs" data-mb-tabs="admin-profile">
    <button type="button" class="mb-tab-btn active" data-tab-target="overview"><i class="fas fa-gauge"></i> Overview</button>
    <button type="button" class="mb-tab-btn" data-tab-target="personal"><i class="fas fa-id-card"></i> Personal Info</button>
</div>

<!-- ============ Overview ============ -->
<div class="mb-tab-panel active" data-mb-tab-panel-group="admin-profile" data-mb-tab-panel="overview">
    <div class="db-grid" style="grid-template-columns: repeat(5, 1fr); gap: 1rem; margin-bottom: 1.75rem;">
        <div class="mini-stat"><span class="mini-stat-icon" style="background: var(--gradient-primary);"><i class="fas fa-users"></i></span><div><div class="mini-stat-value"><?php echo $users_count; ?></div><div class="mini-stat-label">Users</div></div></div>
        <div class="mini-stat"><span class="mini-stat-icon" style="background: linear-gradient(135deg,#06B6D4,#3B82F6);"><i class="fas fa-user-md"></i></span><div><div class="mini-stat-value"><?php echo $doctors_count; ?></div><div class="mini-stat-label">Doctors</div></div></div>
        <div class="mini-stat"><span class="mini-stat-icon" style="background: linear-gradient(135deg,#10B981,#06B6D4);"><i class="fas fa-hospital-user"></i></span><div><div class="mini-stat-value"><?php echo $patients_count; ?></div><div class="mini-stat-label">Patients</div></div></div>
        <div class="mini-stat"><span class="mini-stat-icon" style="background: linear-gradient(135deg,#8B5CF6,#EC4899);"><i class="fas fa-calendar-alt"></i></span><div><div class="mini-stat-value"><?php echo $appointments_count; ?></div><div class="mini-stat-label">Appointments</div></div></div>
        <div class="mini-stat"><span class="mini-stat-icon" style="background: linear-gradient(135deg,#F59E0B,#EF4444);"><i class="fas fa-sack-dollar"></i></span><div><div class="mini-stat-value">$<?php echo number_format($total_revenue, 0); ?></div><div class="mini-stat-label">Revenue</div></div></div>
    </div>

    <div class="db-panel">
        <div class="db-panel-header">
            <span class="db-panel-title"><i class="fas fa-bolt"></i> Recent System Activity</span>
        </div>
        <?php if (empty($activity)): ?>
            <div class="timeline-empty">Nothing to show yet.</div>
        <?php else: ?>
            <?php foreach ($activity as $a): ?>
                <div class="activity-item">
                    <span class="activity-icon" style="background: <?php echo $a['color']; ?>;"><i class="fas <?php echo $a['icon']; ?>"></i></span>
                    <div>
                        <div class="activity-text"><?php echo $a['text']; ?></div>
                        <div class="activity-time"><?php echo date('M j, Y g:i A', strtotime($a['time'])); ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- ============ Personal Info ============ -->
<div class="mb-tab-panel" data-mb-tab-panel-group="admin-profile" data-mb-tab-panel="personal">
    <div class="row g-4">
        <div class="col-lg-4">
            <div class="db-panel">
                <div class="db-panel-header"><span class="db-panel-title"><i class="fas fa-image"></i> Profile Photo</span></div>
                <form method="POST" enctype="multipart/form-data" id="avatarForm">
                    <input type="hidden" name="action" value="upload_avatar">
                    <label class="upload-dropzone" data-mb-dropzone>
                        <input type="file" name="avatar" accept="image/jpeg,image/png,image/webp" onchange="document.getElementById('avatarForm').submit();">
                        <i class="fas fa-cloud-arrow-up"></i>
                        Click or drag a photo here<br>
                        <small>JPG, PNG or WEBP · up to 2MB</small>
                        <div class="upload-filename" data-mb-filename></div>
                    </label>
                </form>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="db-panel">
                <div class="db-panel-header"><span class="db-panel-title"><i class="fas fa-id-card"></i> Personal Details</span></div>
                <form method="POST">
                    <input type="hidden" name="action" value="update_profile">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">First name</label>
                            <input type="text" name="first_name" class="form-control" value="<?php echo htmlspecialchars($profile['first_name']); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Last name</label>
                            <input type="text" name="last_name" class="form-control" value="<?php echo htmlspecialchars($profile['last_name']); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" value="<?php echo htmlspecialchars($profile['email']); ?>" disabled>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($profile['phone'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Date of birth</label>
                            <input type="date" name="date_of_birth" class="form-control" value="<?php echo htmlspecialchars($profile['date_of_birth'] ?? ''); ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Address</label>
                            <textarea name="address" rows="2" class="form-control"><?php echo htmlspecialchars($profile['address'] ?? ''); ?></textarea>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary mt-3"><i class="fas fa-floppy-disk me-1"></i> Save Changes</button>
                </form>
                <hr>
                <p class="text-muted small mb-0"><i class="fas fa-lock me-1"></i> Password &amp; preferences have moved to <a href="settings.php">Settings</a>.</p>
            </div>
        </div>
    </div>
</div>

<?php require '../includes/dash_footer.php'; ?>
