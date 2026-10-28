<?php
/**
 * Phase 1B — Shared public-site header.
 * Include AFTER includes/config.php.
 * Optional variables to set before including:
 *   $pageTitle  (string)  Browser tab title suffix. Defaults to 'MediBook'.
 *   $activeNav  (string)  One of: home, doctors. Highlights the matching nav link.
 *   $breadcrumbs (array)  [['label' => 'Home', 'url' => 'index.php'], ['label' => 'Current']]
 *                         Last item (no 'url') renders as the current page.
 */
$pageTitle = $pageTitle ?? 'MediBook';
$activeNav = $activeNav ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - MediBook</title>
    <!-- Phase 4: apply saved theme before first paint to avoid a light/dark flash -->
    <script>(function(){try{if(localStorage.getItem('mb-theme')==='dark'){document.documentElement.setAttribute('data-theme','dark');}}catch(e){}})();</script>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <!-- Bootstrap 5 (Phase 1A: added alongside existing Tailwind, see project notes) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- MediBook Design System (Phase 1A) -->
    <link href="assets/css/variables.css" rel="stylesheet">
    <link href="assets/css/custom.css" rel="stylesheet">
    <link href="assets/css/animations.css" rel="stylesheet">
    <!-- MediBook Layout System (Phase 1B) -->
    <link href="assets/css/layout.css" rel="stylesheet">
    <!-- MediBook Auth Pages (Phase 2A) -->
    <link href="assets/css/auth.css" rel="stylesheet">
    <!-- MediBook Dashboard Widgets (Phase 2B) -->
    <link href="assets/css/dashboard.css" rel="stylesheet">
    <!-- DataTables (Bootstrap 5 build) + MediBook CRUD styling (Phase 3A/3B) -->
    <link href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="assets/css/crud.css" rel="stylesheet">
    <!-- MediBook Profile & Settings (Phase 4) -->
    <link href="assets/css/profile.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <!-- Phase 5: page loader (hidden by layout.js once the page finishes loading) -->
    <div class="mb-page-loader" data-mb-page-loader aria-hidden="true"><div class="spinner-brand"></div></div>

    <!-- ============ Modern Header (top utility bar) ============ -->
    <header class="mb-topbar">
        <div class="mb-topbar-inner">
            <div class="mb-topbar-info">
                <span class="mb-hide-mobile"><i class="fas fa-phone-alt"></i> +1 (800) 555-0192</span>
                <span class="mb-hide-mobile"><i class="fas fa-envelope"></i> support@medibook.com</span>
                <span><i class="fas fa-clock"></i> Mon - Sat: 8:00am - 8:00pm</span>
            </div>
            <div class="mb-topbar-social">
                <a href="https://facebook.com" target="_blank" rel="noopener" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                <a href="https://instagram.com" target="_blank" rel="noopener" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                <a href="https://linkedin.com" target="_blank" rel="noopener" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                <a href="https://github.com" target="_blank" rel="noopener" aria-label="GitHub"><i class="fab fa-github"></i></a>
            </div>
        </div>
    </header>

    <!-- ============ Premium Navbar ============ -->
    <nav class="mb-navbar glass">
        <div class="mb-navbar-inner">
            <a href="index.php" class="mb-brand">
                <span class="mb-brand-icon"><i class="fas fa-stethoscope"></i></span>
                <span class="mb-brand-text">Medi<span>Book</span></span>
            </a>

            <ul class="mb-nav-links">
                <li><a href="index.php" class="mb-nav-link <?php echo $activeNav === 'home' ? 'active' : ''; ?>"><i class="fas fa-house fa-sm"></i> Home</a></li>
                <li><a href="doctors.php" class="mb-nav-link <?php echo $activeNav === 'doctors' ? 'active' : ''; ?>"><i class="fas fa-user-md fa-sm"></i> Doctors</a></li>
                <?php if (isLoggedIn() && !isAdmin()): ?>
                <li class="mb-dropdown">
                    <a href="#" class="mb-nav-link mb-dropdown-toggle <?php echo in_array($activeNav, ['appointments']) ? 'active' : ''; ?>">
                        <i class="fas fa-calendar-check fa-sm"></i> My Care <i class="fas fa-chevron-down mb-dropdown-caret"></i>
                    </a>
                    <div class="mb-dropdown-menu glass">
                        <?php if (isDoctor()): ?>
                            <a href="doctor/index.php"><i class="fas fa-gauge"></i> Doctor Dashboard</a>
                            <a href="doctor/appointments.php"><i class="fas fa-calendar-alt"></i> My Appointments</a>
                            <a href="doctor/profile.php"><i class="fas fa-user-circle"></i> My Profile</a>
                        <?php else: ?>
                            <a href="dashboard.php"><i class="fas fa-gauge"></i> My Dashboard</a>
                            <a href="my_appointments.php"><i class="fas fa-calendar-alt"></i> My Appointments</a>
                            <a href="doctors.php"><i class="fas fa-calendar-plus"></i> Book Appointment</a>
                            <a href="profile.php"><i class="fas fa-user-circle"></i> My Profile</a>
                        <?php endif; ?>
                    </div>
                </li>
                <?php endif; ?>
            </ul>

            <div class="mb-nav-actions">
                <button type="button" class="mb-theme-toggle" data-mb-theme-toggle aria-label="Toggle dark mode" title="Toggle dark / light mode">
                    <i class="fas fa-moon"></i><i class="fas fa-sun"></i>
                </button>
                <?php if (isLoggedIn()): ?>
                    <?php if (isAdmin()): ?>
                        <a href="admin/index.php" class="mb-btn mb-btn-primary"><i class="fas fa-shield-halved"></i> <span class="mb-hide-mobile">Admin Panel</span></a>
                    <?php elseif (isDoctor()): ?>
                        <a href="doctor/index.php" class="mb-btn mb-btn-primary"><i class="fas fa-gauge"></i> <span class="mb-hide-mobile">Dashboard</span></a>
                    <?php else: ?>
                        <a href="dashboard.php" class="mb-btn mb-btn-primary"><i class="fas fa-gauge"></i> <span class="mb-hide-mobile">Dashboard</span></a>
                    <?php endif; ?>
                    <div class="mb-dropdown">
                        <div class="mb-user-chip mb-dropdown-toggle">
                            <span class="mb-user-avatar"><?php echo strtoupper(substr($_SESSION['first_name'] ?? 'U', 0, 1)); ?></span>
                            <span class="mb-hide-mobile"><?php echo htmlspecialchars($_SESSION['first_name'] ?? ''); ?></span>
                            <i class="fas fa-chevron-down mb-dropdown-caret mb-hide-mobile"></i>
                        </div>
                        <div class="mb-dropdown-menu glass" style="right:0; left:auto;">
                            <?php if (isAdmin()): ?>
                                <a href="admin/profile.php"><i class="fas fa-user-circle"></i> My Profile</a>
                                <a href="admin/settings.php"><i class="fas fa-gear"></i> Settings</a>
                            <?php elseif (isDoctor()): ?>
                                <a href="doctor/profile.php"><i class="fas fa-user-circle"></i> My Profile</a>
                            <?php else: ?>
                                <a href="profile.php"><i class="fas fa-user-circle"></i> My Profile</a>
                            <?php endif; ?>
                            <a href="logout.php"><i class="fas fa-right-from-bracket"></i> Logout</a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="login.php" class="mb-btn mb-btn-ghost">Login</a>
                    <a href="register.php" class="mb-btn mb-btn-primary"><i class="fas fa-user-plus"></i> Sign Up</a>
                <?php endif; ?>
                <button type="button" class="mb-mobile-toggle" data-mb-mobile-toggle aria-label="Open menu">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </div>
    </nav>

    <!-- ============ Responsive Mobile Menu (off-canvas) ============ -->
    <div class="mb-overlay" data-mb-mobile-close></div>
    <div class="mb-mobile-menu glass">
        <div class="mb-mobile-menu-header">
            <span class="mb-brand-text">Medi<span>Book</span></span>
            <button type="button" class="mb-mobile-menu-close" data-mb-mobile-close aria-label="Close menu"><i class="fas fa-xmark"></i></button>
        </div>
        <a href="index.php" class="mb-nav-link <?php echo $activeNav === 'home' ? 'active' : ''; ?>"><i class="fas fa-house fa-sm"></i> Home</a>
        <a href="doctors.php" class="mb-nav-link <?php echo $activeNav === 'doctors' ? 'active' : ''; ?>"><i class="fas fa-user-md fa-sm"></i> Doctors</a>
        <?php if (isLoggedIn()): ?>
            <?php if (isAdmin()): ?>
                <a href="admin/index.php" class="mb-nav-link"><i class="fas fa-shield-halved"></i> Admin Panel</a>
                <a href="admin/profile.php" class="mb-nav-link"><i class="fas fa-user-circle"></i> My Profile</a>
                <a href="admin/settings.php" class="mb-nav-link"><i class="fas fa-gear"></i> Settings</a>
            <?php elseif (isDoctor()): ?>
                <a href="doctor/index.php" class="mb-nav-link"><i class="fas fa-gauge"></i> Doctor Dashboard</a>
                <a href="doctor/appointments.php" class="mb-nav-link"><i class="fas fa-calendar-alt"></i> My Appointments</a>
                <a href="doctor/profile.php" class="mb-nav-link"><i class="fas fa-user-circle"></i> My Profile</a>
            <?php else: ?>
                <a href="dashboard.php" class="mb-nav-link"><i class="fas fa-gauge"></i> My Dashboard</a>
                <a href="my_appointments.php" class="mb-nav-link"><i class="fas fa-calendar-alt"></i> My Appointments</a>
                <a href="profile.php" class="mb-nav-link"><i class="fas fa-user-circle"></i> My Profile</a>
            <?php endif; ?>
            <a href="logout.php" class="mb-nav-link"><i class="fas fa-right-from-bracket"></i> Logout</a>
        <?php else: ?>
            <a href="login.php" class="mb-nav-link"><i class="fas fa-right-to-bracket"></i> Login</a>
            <a href="register.php" class="mb-nav-link"><i class="fas fa-user-plus"></i> Sign Up</a>
        <?php endif; ?>
    </div>

    <?php if (!empty($breadcrumbs)): ?>
    <div class="mb-breadcrumb-bar">
        <ul class="mb-breadcrumb">
            <?php $bcCount = count($breadcrumbs); foreach ($breadcrumbs as $i => $bc): ?>
                <li>
                    <?php if (!empty($bc['url']) && $i < $bcCount - 1): ?>
                        <a href="<?php echo htmlspecialchars($bc['url']); ?>"><?php echo $i === 0 ? '<i class="fas fa-house"></i> ' : ''; ?><?php echo htmlspecialchars($bc['label']); ?></a>
                    <?php else: ?>
                        <span class="current"><?php echo htmlspecialchars($bc['label']); ?></span>
                    <?php endif; ?>
                </li>
                <?php if ($i < $bcCount - 1): ?><li class="sep"><i class="fas fa-chevron-right"></i></li><?php endif; ?>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>
