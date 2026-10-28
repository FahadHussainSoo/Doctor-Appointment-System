<?php
/**
 * Phase 1B — Dark responsive sidebar for admin/ and doctor/ dashboards.
 * Included by includes/dash_header.php. Expects (set by the caller):
 *   $dashRole    'admin' | 'doctor'
 *   $activeMenu  string key matching one of the menu items below
 * Links are written relative to the current script (admin/*.php or
 * doctor/*.php), matching the original Phase 1A routing exactly.
 */
$dashRole = $dashRole ?? 'admin';

if ($dashRole === 'admin') {
    $sidebarMenu = [
        ['key' => 'dashboard',    'label' => 'Dashboard',    'url' => 'index.php',        'icon' => 'fa-gauge-high'],
        ['key' => 'appointments', 'label' => 'Appointments', 'url' => 'appointments.php',  'icon' => 'fa-calendar-alt'],
        ['key' => 'users',        'label' => 'Users',        'url' => 'users.php',         'icon' => 'fa-users'],
        ['key' => 'doctors',      'label' => 'Doctors',      'url' => 'doctors.php',       'icon' => 'fa-user-md'],
        ['key' => 'departments',  'label' => 'Departments',  'url' => 'departments.php',   'icon' => 'fa-hospital'],
        ['key' => 'profile',      'label' => 'My Profile',   'url' => 'profile.php',       'icon' => 'fa-user-circle'],
        ['key' => 'settings',     'label' => 'Settings',     'url' => 'settings.php',      'icon' => 'fa-gear'],
    ];
    $roleLabel = 'Admin Panel';
    $logoutUrl = 'logout.php';
} else {
    $sidebarMenu = [
        ['key' => 'dashboard',    'label' => 'Dashboard',    'url' => 'index.php',        'icon' => 'fa-gauge-high'],
        ['key' => 'appointments', 'label' => 'My Appointments', 'url' => 'appointments.php', 'icon' => 'fa-calendar-alt'],
        ['key' => 'schedule',     'label' => 'My Schedule',  'url' => 'schedule.php',     'icon' => 'fa-calendar-week'],
        ['key' => 'profile',      'label' => 'My Profile',   'url' => 'profile.php',      'icon' => 'fa-user-circle'],
    ];
    $roleLabel = 'Doctor Panel';
    $logoutUrl = '../logout.php';
}
?>
<div class="mb-sidebar-overlay mb-overlay"></div>
<aside class="mb-sidebar glass-dark" id="mbSidebar">
    <div class="mb-sidebar-brand">
        <div class="mb-sidebar-brand-row">
            <span class="mb-brand-icon"><i class="fas fa-stethoscope"></i></span>
            <span class="mb-brand-text" style="color:#fff;">Medi<span style="color:#A5B4FC;">Book</span></span>
        </div>
        <span class="mb-sidebar-role"><?php echo htmlspecialchars($roleLabel); ?></span>
        <div class="mb-sidebar-user">
            <?php echo ($dashRole === 'doctor' ? 'Dr. ' : '') . htmlspecialchars($_SESSION['first_name'] ?? ''); ?>
        </div>
    </div>

    <nav class="mb-sidebar-nav">
        <div class="mb-sidebar-section-title">Menu</div>
        <?php foreach ($sidebarMenu as $item): ?>
            <a href="<?php echo $item['url']; ?>" class="mb-sidebar-link <?php echo ($activeMenu ?? '') === $item['key'] ? 'active' : ''; ?>">
                <i class="fas <?php echo $item['icon']; ?>"></i> <?php echo htmlspecialchars($item['label']); ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <div class="mb-sidebar-footer">
        <a href="<?php echo $logoutUrl; ?>" class="mb-sidebar-link logout">
            <i class="fas fa-right-from-bracket"></i> Logout
        </a>
    </div>
</aside>
