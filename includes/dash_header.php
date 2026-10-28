<?php
/**
 * Phase 1B — Shared dashboard shell for admin/*.php and doctor/*.php.
 * Include AFTER ../includes/config.php and the requireAdmin()/requireDoctor() guard.
 * Variables to set before including:
 *   $pageTitle    (string)  Browser tab title.
 *   $dashRole     (string)  'admin' | 'doctor'
 *   $activeMenu   (string)  Matches a sidebar item key (see includes/sidebar.php).
 *   $breadcrumbs  (array)   [['label' => 'Dashboard', 'url' => 'index.php'], ['label' => 'Current']]
 *
 * Pairs with includes/dash_footer.php, which closes the wrappers opened here.
 */
$pageTitle  = $pageTitle ?? 'Dashboard';
$dashRole   = $dashRole ?? 'admin';
$activeMenu = $activeMenu ?? '';
$basePath   = '../';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - MediBook <?php echo $dashRole === 'admin' ? 'Admin' : 'Doctor'; ?></title>
    <!-- Phase 4: apply saved theme before first paint to avoid a light/dark flash -->
    <script>(function(){try{if(localStorage.getItem('mb-theme')==='dark'){document.documentElement.setAttribute('data-theme','dark');}}catch(e){}})();</script>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <!-- Bootstrap 5 (Phase 1A: added alongside existing Tailwind, see project notes) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- MediBook Design System (Phase 1A) -->
    <link href="../assets/css/variables.css" rel="stylesheet">
    <link href="../assets/css/custom.css" rel="stylesheet">
    <link href="../assets/css/animations.css" rel="stylesheet">
    <!-- MediBook Layout System (Phase 1B) -->
    <link href="../assets/css/layout.css" rel="stylesheet">
    <!-- MediBook Dashboard Widgets (Phase 2B) -->
    <link href="../assets/css/dashboard.css" rel="stylesheet">
    <!-- DataTables (Bootstrap 5 build) + MediBook CRUD styling (Phase 3A) -->
    <link href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="../assets/css/crud.css" rel="stylesheet">
    <!-- MediBook Profile & Settings (Phase 4) -->
    <link href="../assets/css/profile.css" rel="stylesheet">
</head>
<body class="bg-gray-100 mb-dash-body">
    <!-- Phase 5: page loader (hidden by layout.js once the page finishes loading) -->
    <div class="mb-page-loader" data-mb-page-loader aria-hidden="true"><div class="spinner-brand"></div></div>

<div class="mb-dash-shell">
    <?php require __DIR__ . '/sidebar.php'; ?>

    <div class="mb-dash-main-wrap" style="flex:1; min-width:0; display:flex; flex-direction:column;">
        <!-- ============ Dashboard Top Bar ============ -->
        <header class="mb-dash-topbar glass">
            <div class="mb-dash-topbar-inner">
                <div style="display:flex; align-items:center; gap:.9rem;">
                    <button type="button" class="mb-sidebar-toggle" data-mb-sidebar-toggle aria-label="Open menu">
                        <i class="fas fa-bars"></i>
                    </button>
                    <span class="mb-dash-title"><?php echo htmlspecialchars($pageTitle); ?></span>
                </div>
                <div style="display:flex; align-items:center; gap:.6rem;">
                    <button type="button" class="mb-theme-toggle" data-mb-theme-toggle aria-label="Toggle dark mode" title="Toggle dark / light mode">
                        <i class="fas fa-moon"></i><i class="fas fa-sun"></i>
                    </button>
                    <div class="mb-dropdown">
                        <div class="mb-user-chip mb-dropdown-toggle">
                            <span class="mb-user-avatar"><?php echo strtoupper(substr($_SESSION['first_name'] ?? 'U', 0, 1)); ?></span>
                            <span class="mb-hide-mobile"><?php echo htmlspecialchars($_SESSION['first_name'] ?? ''); ?></span>
                            <i class="fas fa-chevron-down mb-dropdown-caret mb-hide-mobile"></i>
                        </div>
                        <div class="mb-dropdown-menu glass" style="right:0; left:auto;">
                            <a href="profile.php"><i class="fas fa-user-circle"></i> My Profile</a>
                            <?php if ($dashRole === 'admin'): ?>
                                <a href="settings.php"><i class="fas fa-gear"></i> Settings</a>
                            <?php endif; ?>
                            <a href="<?php echo $dashRole === 'admin' ? 'logout.php' : '../logout.php'; ?>"><i class="fas fa-right-from-bracket"></i> Logout</a>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <main class="mb-dash-main">
            <?php if (!empty($breadcrumbs)): ?>
            <div class="mb-breadcrumb-bar mb-dash glass">
                <ul class="mb-breadcrumb">
                    <?php $bcCount = count($breadcrumbs); foreach ($breadcrumbs as $i => $bc): ?>
                        <li>
                            <?php if (!empty($bc['url']) && $i < $bcCount - 1): ?>
                                <a href="<?php echo htmlspecialchars($bc['url']); ?>"><?php echo $i === 0 ? '<i class="fas fa-gauge-high"></i> ' : ''; ?><?php echo htmlspecialchars($bc['label']); ?></a>
                            <?php else: ?>
                                <span class="current"><?php echo htmlspecialchars($bc['label']); ?></span>
                            <?php endif; ?>
                        </li>
                        <?php if ($i < $bcCount - 1): ?><li class="sep"><i class="fas fa-chevron-right"></i></li><?php endif; ?>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
