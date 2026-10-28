<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/uploads.php';
requireDoctor();

$user_id = $_SESSION['user_id'];
$errors = [];
$success = null;

// ---------------------------------------------------------------
// Handle form submissions — same pattern as profile.php: only the
// `users` table fields, avatar, and password. Professional fields
// (specialization, fee, bio, available hours) already have an editor
// in admin/doctors.php, so they aren't duplicated here (kept read-only
// below) to avoid two code paths writing the same columns.
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

    if ($action === 'change_password') {
        $current = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $hash = $stmt->fetchColumn();

        if (!$hash || !password_verify($current, $hash)) {
            $errors[] = 'Current password is incorrect.';
        } elseif (strlen($new) < 8) {
            $errors[] = 'New password must be at least 8 characters.';
        } elseif ($new !== $confirm) {
            $errors[] = 'New password and confirmation do not match.';
        } else {
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([password_hash($new, PASSWORD_DEFAULT), $user_id]);
            $success = 'Password changed successfully.';
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

$doc_stmt = $pdo->prepare("
    SELECT d.*, s.name as specialty_name
    FROM doctors d
    LEFT JOIN specialties s ON d.specialty_id = s.id
    WHERE d.user_id = ?
");
$doc_stmt->execute([$user_id]);
$doctor = $doc_stmt->fetch(PDO::FETCH_ASSOC);
$doctor_id = $doctor['id'];

$total_stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE doctor_id = ?");
$total_stmt->execute([$doctor_id]);
$total_count = (int) $total_stmt->fetchColumn();

$completed_stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE doctor_id = ? AND status = 'completed'");
$completed_stmt->execute([$doctor_id]);
$completed_count = (int) $completed_stmt->fetchColumn();

$patients_stmt = $pdo->prepare("SELECT COUNT(DISTINCT patient_id) FROM appointments WHERE doctor_id = ?");
$patients_stmt->execute([$doctor_id]);
$patients_count = (int) $patients_stmt->fetchColumn();

$today_stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE doctor_id = ? AND appointment_date = CURDATE() AND status IN ('scheduled','confirmed')");
$today_stmt->execute([$doctor_id]);
$today_count = (int) $today_stmt->fetchColumn();

$rating_stmt = $pdo->prepare("SELECT ROUND(AVG(rating), 1) as avg_rating, COUNT(*) as review_count FROM reviews WHERE doctor_id = ?");
$rating_stmt->execute([$doctor_id]);
$rating = $rating_stmt->fetch(PDO::FETCH_ASSOC);

$timeline_stmt = $pdo->prepare("
    SELECT a.*, u.first_name as patient_first, u.last_name as patient_last
    FROM appointments a
    JOIN users u ON a.patient_id = u.id
    WHERE a.doctor_id = ?
    ORDER BY a.appointment_date DESC, a.appointment_time DESC
    LIMIT 10
");
$timeline_stmt->execute([$doctor_id]);
$timeline = $timeline_stmt->fetchAll(PDO::FETCH_ASSOC);

$status_icon = ['scheduled' => 'fa-calendar-day', 'confirmed' => 'fa-calendar-check', 'completed' => 'fa-check', 'cancelled' => 'fa-xmark'];
$status_color = ['scheduled' => 'linear-gradient(135deg,#F59E0B,#F97316)', 'confirmed' => 'linear-gradient(135deg,#10B981,#06B6D4)', 'completed' => 'linear-gradient(135deg,#6366F1,#8B5CF6)', 'cancelled' => 'linear-gradient(135deg,#EF4444,#F43F5E)'];

$week_order = ['Sun' => 'Sunday', 'Mon' => 'Monday', 'Tue' => 'Tuesday', 'Wed' => 'Wednesday', 'Thu' => 'Thursday', 'Fri' => 'Friday', 'Sat' => 'Saturday'];
$available_days_arr = array_filter(array_map('trim', explode(',', $doctor['available_days'] ?? '')));

$pageTitle = 'My Profile';
$dashRole = 'doctor';
$activeMenu = 'profile';
$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => 'index.php'],
    ['label' => 'My Profile'],
];
require '../includes/dash_header.php';
?>

<?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show" data-auto-dismiss><?php echo htmlspecialchars($success); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
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
            <h1 class="profile-hero-name">Dr. <?php echo htmlspecialchars($profile['first_name'] . ' ' . $profile['last_name']); ?></h1>
            <span class="profile-hero-role"><i class="fas fa-user-md"></i> <?php echo htmlspecialchars($doctor['specialty_name'] ?? 'Doctor'); ?></span>
            <div class="profile-hero-meta">
                <span><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($profile['email']); ?></span>
                <?php if (!empty($profile['phone'])): ?><span><i class="fas fa-phone"></i> <?php echo htmlspecialchars($profile['phone']); ?></span><?php endif; ?>
                <span><i class="fas fa-briefcase-medical"></i> <?php echo (int) ($doctor['experience_years'] ?? 0); ?> yrs experience</span>
                <?php if ($rating['review_count'] > 0): ?>
                    <span><i class="fas fa-star text-warning"></i> <?php echo $rating['avg_rating']; ?> (<?php echo $rating['review_count']; ?> reviews)</span>
                <?php endif; ?>
            </div>
        </div>
        <div class="profile-hero-actions">
            <span class="badge <?php echo $doctor['status'] === 'active' ? 'badge-soft-success' : 'badge-soft-secondary'; ?>">
                <span class="status-dot <?php echo $doctor['status'] === 'active' ? 'status-dot-success' : 'status-dot-muted'; ?>"></span><?php echo ucfirst($doctor['status']); ?>
            </span>
        </div>
    </div>
</div>

<!-- ============ Tabs ============ -->
<div class="mb-tabs" data-mb-tabs="doctor-profile">
    <button type="button" class="mb-tab-btn active" data-tab-target="overview"><i class="fas fa-gauge"></i> Overview</button>
    <button type="button" class="mb-tab-btn" data-tab-target="professional"><i class="fas fa-stethoscope"></i> Professional Info</button>
    <button type="button" class="mb-tab-btn" data-tab-target="personal"><i class="fas fa-id-card"></i> Personal Info</button>
    <button type="button" class="mb-tab-btn" data-tab-target="security"><i class="fas fa-lock"></i> Security</button>
</div>

<!-- ============ Overview ============ -->
<div class="mb-tab-panel active" data-mb-tab-panel-group="doctor-profile" data-mb-tab-panel="overview">
    <div class="db-grid" style="grid-template-columns: repeat(4, 1fr); gap: 1.1rem; margin-bottom: 1.75rem;">
        <div class="mini-stat"><span class="mini-stat-icon" style="background: var(--gradient-primary);"><i class="fas fa-calendar-alt"></i></span><div><div class="mini-stat-value"><?php echo $total_count; ?></div><div class="mini-stat-label">Total Appointments</div></div></div>
        <div class="mini-stat"><span class="mini-stat-icon" style="background: linear-gradient(135deg,#F59E0B,#F97316);"><i class="fas fa-calendar-day"></i></span><div><div class="mini-stat-value"><?php echo $today_count; ?></div><div class="mini-stat-label">Today</div></div></div>
        <div class="mini-stat"><span class="mini-stat-icon" style="background: linear-gradient(135deg,#6366F1,#8B5CF6);"><i class="fas fa-check-double"></i></span><div><div class="mini-stat-value"><?php echo $completed_count; ?></div><div class="mini-stat-label">Completed</div></div></div>
        <div class="mini-stat"><span class="mini-stat-icon" style="background: linear-gradient(135deg,#10B981,#06B6D4);"><i class="fas fa-users"></i></span><div><div class="mini-stat-value"><?php echo $patients_count; ?></div><div class="mini-stat-label">Patients Seen</div></div></div>
    </div>

    <div class="db-panel">
        <div class="db-panel-header">
            <span class="db-panel-title"><i class="fas fa-timeline"></i> Recent Appointment Timeline</span>
            <a href="appointments.php" class="db-panel-link">View all</a>
        </div>
        <?php if (empty($timeline)): ?>
            <div class="timeline-empty"><i class="fas fa-calendar-times me-1"></i> No appointments yet.</div>
        <?php else: ?>
            <ul class="timeline">
                <?php foreach ($timeline as $t): $st = $t['status']; ?>
                    <li class="timeline-item">
                        <span class="timeline-dot" style="background: <?php echo $status_color[$st] ?? '#9CA3AF'; ?>;"><i class="fas <?php echo $status_icon[$st] ?? 'fa-calendar'; ?>"></i></span>
                        <div class="timeline-content">
                            <div class="timeline-title">
                                <?php echo htmlspecialchars($t['patient_first'] . ' ' . $t['patient_last']); ?>
                                <span class="db-activity-badge db-badge-<?php echo $st; ?> ms-2"><?php echo ucfirst($st); ?></span>
                            </div>
                            <div class="timeline-meta"><?php echo date('l, M j, Y', strtotime($t['appointment_date'])); ?> at <?php echo date('g:i A', strtotime($t['appointment_time'])); ?></div>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>

<!-- ============ Professional Info (read-only — edited via admin/doctors.php) ============ -->
<div class="mb-tab-panel" data-mb-tab-panel-group="doctor-profile" data-mb-tab-panel="professional">
    <div class="alert alert-info"><i class="fas fa-circle-info me-1"></i> Professional details are managed by your hospital admin. Contact them to update your specialization, fee, or availability.</div>
    <div class="row g-4">
        <div class="col-lg-6">
            <div class="db-panel">
                <div class="db-panel-header"><span class="db-panel-title"><i class="fas fa-stethoscope"></i> Specialization</span></div>
                <dl class="info-list">
                    <div class="info-row"><dt>Specialty</dt><dd><?php echo htmlspecialchars($doctor['specialty_name'] ?? '—'); ?></dd></div>
                    <div class="info-row"><dt>Qualification</dt><dd><?php echo htmlspecialchars($doctor['qualification'] ?? '—'); ?></dd></div>
                    <div class="info-row"><dt>Experience</dt><dd><?php echo (int) ($doctor['experience_years'] ?? 0); ?> years</dd></div>
                    <div class="info-row"><dt>Consultation Fee</dt><dd>$<?php echo number_format((float) ($doctor['consultation_fee'] ?? 0), 2); ?></dd></div>
                    <div class="info-row"><dt>Status</dt><dd><?php echo ucfirst($doctor['status']); ?></dd></div>
                </dl>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="db-panel">
                <div class="db-panel-header"><span class="db-panel-title"><i class="fas fa-calendar-week"></i> Availability</span></div>
                <dl class="info-list">
                    <div class="info-row"><dt>Working Days</dt><dd><?php echo !empty($available_days_arr) ? implode(', ', array_map(fn($d) => $week_order[$d] ?? $d, $available_days_arr)) : '—'; ?></dd></div>
                    <div class="info-row"><dt>Hours</dt><dd><?php echo !empty($doctor['available_time_start']) ? date('g:i A', strtotime($doctor['available_time_start'])) . ' – ' . date('g:i A', strtotime($doctor['available_time_end'])) : '—'; ?></dd></div>
                </dl>
                <a href="schedule.php" class="btn btn-outline-secondary btn-sm mt-2"><i class="fas fa-calendar-week me-1"></i> View My Schedule</a>
            </div>
            <?php if (!empty($doctor['bio'])): ?>
            <div class="db-panel mt-3">
                <div class="db-panel-header"><span class="db-panel-title"><i class="fas fa-file-lines"></i> Bio</span></div>
                <p class="mb-0 text-muted"><?php echo nl2br(htmlspecialchars($doctor['bio'])); ?></p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ============ Personal Info ============ -->
<div class="mb-tab-panel" data-mb-tab-panel-group="doctor-profile" data-mb-tab-panel="personal">
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
            </div>
        </div>
    </div>
</div>

<!-- ============ Security ============ -->
<div class="mb-tab-panel" data-mb-tab-panel-group="doctor-profile" data-mb-tab-panel="security">
    <div class="row g-4">
        <div class="col-lg-6">
            <div class="db-panel">
                <div class="db-panel-header"><span class="db-panel-title"><i class="fas fa-lock"></i> Change Password</span></div>
                <form method="POST">
                    <input type="hidden" name="action" value="change_password">
                    <div class="mb-3">
                        <label class="form-label">Current password</label>
                        <input type="password" name="current_password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">New password</label>
                        <input type="password" name="new_password" id="newPasswordDoc" class="form-control" minlength="8" data-mb-pw-strength="#pwFillDoc" required>
                        <div class="pw-strength-track"><div class="pw-strength-fill" id="pwFillDoc"></div></div>
                        <div class="pw-strength-label">Enter a new password</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirm new password</label>
                        <input type="password" name="confirm_password" class="form-control" minlength="8" data-mb-confirm-target="#newPasswordDoc" required>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-key me-1"></i> Update Password</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require '../includes/dash_footer.php'; ?>
