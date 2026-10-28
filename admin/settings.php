<?php
require_once '../includes/config.php';
requireAdmin();

$user_id = $_SESSION['user_id'];
$errors = [];
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'change_password') {
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

// ---- Read-only system info (no writes) ----
$db_version = $pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
$php_version = PHP_VERSION;
$table_counts = [];
foreach (['users', 'doctors', 'appointments', 'specialties', 'reviews'] as $t) {
    try {
        $table_counts[$t] = (int) $pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
    } catch (PDOException $e) {
        $table_counts[$t] = null;
    }
}
$has_avatar_column = false;
try {
    $pdo->query("SELECT avatar FROM users LIMIT 1");
    $has_avatar_column = true;
} catch (PDOException $e) {
    $has_avatar_column = false;
}

$pageTitle = 'Settings';
$dashRole = 'admin';
$activeMenu = 'settings';
$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => 'index.php'],
    ['label' => 'Settings'],
];
require '../includes/dash_header.php';
?>

<?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show"><?php echo htmlspecialchars($success); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>
<?php if (!empty($errors)): ?>
    <div class="alert alert-danger"><ul class="mb-0 ps-3"><?php foreach ($errors as $e) echo '<li>' . htmlspecialchars($e) . '</li>'; ?></ul></div>
<?php endif; ?>

<?php if (!$has_avatar_column): ?>
    <div class="alert alert-warning"><i class="fas fa-triangle-exclamation me-1"></i> Avatar uploads are disabled until <code>db_patch_phase4.sql</code> is run against this database (adds one nullable column to <code>users</code>). Everything else on this page works either way.</div>
<?php endif; ?>

<div class="mb-tabs" data-mb-tabs="admin-settings">
    <button type="button" class="mb-tab-btn active" data-tab-target="security"><i class="fas fa-lock"></i> Security</button>
    <button type="button" class="mb-tab-btn" data-tab-target="preferences"><i class="fas fa-sliders"></i> Preferences</button>
    <button type="button" class="mb-tab-btn" data-tab-target="system"><i class="fas fa-server"></i> System Info</button>
</div>

<!-- ============ Security ============ -->
<div class="mb-tab-panel active" data-mb-tab-panel-group="admin-settings" data-mb-tab-panel="security">
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
                        <input type="password" name="new_password" id="newPasswordAdmin" class="form-control" minlength="8" data-mb-pw-strength="#pwFillAdmin" required>
                        <div class="pw-strength-track"><div class="pw-strength-fill" id="pwFillAdmin"></div></div>
                        <div class="pw-strength-label">Enter a new password</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirm new password</label>
                        <input type="password" name="confirm_password" class="form-control" minlength="8" data-mb-confirm-target="#newPasswordAdmin" required>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-key me-1"></i> Update Password</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- ============ Preferences ============ -->
<div class="mb-tab-panel" data-mb-tab-panel-group="admin-settings" data-mb-tab-panel="preferences">
    <div class="row g-4">
        <div class="col-lg-7">
            <div class="db-panel">
                <div class="db-panel-header"><span class="db-panel-title"><i class="fas fa-sliders"></i> Display</span></div>
                <div class="pref-row">
                    <div>
                        <div class="pref-row-label">Dark mode</div>
                        <div class="pref-row-desc">Switch the whole app to a dark theme. Saved on this device.</div>
                    </div>
                    <button type="button" class="mb-theme-toggle" data-mb-theme-toggle style="border-color: var(--color-border); background: var(--color-bg); color: var(--color-text);">
                        <i class="fas fa-moon"></i><i class="fas fa-sun"></i>
                    </button>
                </div>
                <div class="pref-row">
                    <div>
                        <div class="pref-row-label">Email notification alerts</div>
                        <div class="pref-row-desc">Get a preview toggle for future new-appointment / cancellation emails. Not yet wired to a mail sender.</div>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" disabled checked>
                    </div>
                </div>
                <p class="text-muted small mt-3 mb-0"><i class="fas fa-circle-info me-1"></i> Broader configuration (SMTP, SMS gateway, multi-language) is planned for a later phase and isn't wired up yet — this section only covers what's genuinely functional today.</p>
            </div>
        </div>
    </div>
</div>

<!-- ============ System Info ============ -->
<div class="mb-tab-panel" data-mb-tab-panel-group="admin-settings" data-mb-tab-panel="system">
    <div class="row g-4">
        <div class="col-lg-6">
            <div class="db-panel">
                <div class="db-panel-header"><span class="db-panel-title"><i class="fas fa-server"></i> Environment</span></div>
                <dl class="info-list">
                    <div class="info-row"><dt>PHP Version</dt><dd><?php echo htmlspecialchars($php_version); ?></dd></div>
                    <div class="info-row"><dt>MySQL / MariaDB Version</dt><dd><?php echo htmlspecialchars($db_version); ?></dd></div>
                    <div class="info-row"><dt>Avatar Storage</dt><dd><?php echo $has_avatar_column ? '<span class="badge badge-soft-success">Enabled</span>' : '<span class="badge badge-soft-warning">Patch not applied</span>'; ?></dd></div>
                </dl>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="db-panel">
                <div class="db-panel-header"><span class="db-panel-title"><i class="fas fa-database"></i> Database Records</span></div>
                <dl class="info-list">
                    <?php foreach ($table_counts as $table => $count): ?>
                        <div class="info-row"><dt><?php echo ucfirst($table); ?></dt><dd><?php echo $count === null ? '—' : number_format($count); ?></dd></div>
                    <?php endforeach; ?>
                </dl>
            </div>
        </div>
    </div>
</div>

<?php require '../includes/dash_footer.php'; ?>
