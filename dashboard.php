<?php
require_once 'includes/config.php';

// Same access rule as the rest of the patient-facing pages: logged-in, non-admin.
if (!isLoggedIn() || isAdmin()) {
    header("Location: login.php");
    exit();
}
// Doctors have their own dashboard.
if (isDoctor()) {
    header("Location: doctor/index.php");
    exit();
}

$patient_id = $_SESSION['user_id'];

// ---- Read-only stats (new; no writes, no schema changes) ----
$total_stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE patient_id = ?");
$total_stmt->execute([$patient_id]);
$total_count = $total_stmt->fetchColumn();

$upcoming_stmt = $pdo->prepare("
    SELECT COUNT(*) FROM appointments
    WHERE patient_id = ? AND appointment_date >= CURDATE() AND status IN ('scheduled','confirmed')
");
$upcoming_stmt->execute([$patient_id]);
$upcoming_count = $upcoming_stmt->fetchColumn();

$status_counts = ['scheduled' => 0, 'confirmed' => 0, 'completed' => 0, 'cancelled' => 0];
$status_stmt = $pdo->prepare("SELECT status, COUNT(*) as c FROM appointments WHERE patient_id = ? GROUP BY status");
$status_stmt->execute([$patient_id]);
foreach ($status_stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    if (isset($status_counts[$row['status']])) $status_counts[$row['status']] = (int)$row['c'];
}
$status_total = max(1, array_sum($status_counts));

$recent_stmt = $pdo->prepare("
    SELECT a.*, ud.first_name as doctor_first, ud.last_name as doctor_last, s.name as specialty_name
    FROM appointments a
    JOIN doctors d ON a.doctor_id = d.id
    JOIN users ud ON d.user_id = ud.id
    LEFT JOIN specialties s ON d.specialty_id = s.id
    WHERE a.patient_id = ?
    ORDER BY a.appointment_date DESC, a.appointment_time DESC
    LIMIT 6
");
$recent_stmt->execute([$patient_id]);
$recent_appointments = $recent_stmt->fetchAll(PDO::FETCH_ASSOC);

$next_stmt = $pdo->prepare("
    SELECT a.*, ud.first_name as doctor_first, ud.last_name as doctor_last, s.name as specialty_name
    FROM appointments a
    JOIN doctors d ON a.doctor_id = d.id
    JOIN users ud ON d.user_id = ud.id
    LEFT JOIN specialties s ON d.specialty_id = s.id
    WHERE a.patient_id = ? AND a.appointment_date >= CURDATE() AND a.status IN ('scheduled','confirmed')
    ORDER BY a.appointment_date ASC, a.appointment_time ASC
    LIMIT 1
");
$next_stmt->execute([$patient_id]);
$next_appointment = $next_stmt->fetch(PDO::FETCH_ASSOC);

$cal_stmt = $pdo->prepare("
    SELECT DISTINCT appointment_date FROM appointments
    WHERE patient_id = ? AND MONTH(appointment_date) = MONTH(CURDATE()) AND YEAR(appointment_date) = YEAR(CURDATE())
");
$cal_stmt->execute([$patient_id]);
$cal_dates = array_map(function ($r) { return $r['appointment_date']; }, $cal_stmt->fetchAll(PDO::FETCH_ASSOC));

// Profile completeness (read-only) — encourages filling phone/DOB/address
$profile_stmt = $pdo->prepare("SELECT phone, date_of_birth, address FROM users WHERE id = ?");
$profile_stmt->execute([$patient_id]);
$profile = $profile_stmt->fetch(PDO::FETCH_ASSOC) ?: [];
$profile_fields = ['phone', 'date_of_birth', 'address'];
$filled = 1; // counts the account itself (name/email) as already complete
foreach ($profile_fields as $f) { if (!empty($profile[$f])) $filled++; }
$profile_completion = round($filled / (count($profile_fields) + 1) * 100);

$days_until_next = null;
if ($next_appointment) {
    $days_until_next = (strtotime($next_appointment['appointment_date']) - strtotime(date('Y-m-d'))) / 86400;
}
?>
<?php
$pageTitle = 'My Dashboard';
$activeNav = 'appointments';
$breadcrumbs = [
    ['label' => 'Home', 'url' => 'index.php'],
    ['label' => 'My Dashboard'],
];
require 'includes/header.php';
?>

    <div class="max-w-7xl mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold text-gray-800">My Dashboard</h1>
        <p class="text-gray-500" style="margin-top:.25rem; margin-bottom: 1.75rem;">Welcome back, <?php echo htmlspecialchars($_SESSION['first_name']); ?> — here's an overview of your care.</p>

        <!-- Statistics Cards (gradient) -->
        <div class="db-grid db-stats-row" style="grid-template-columns: repeat(4, 1fr);">
            <div class="db-stat-card db-grad-1 db-fade-in d1">
                <div class="db-stat-top">
                    <span class="db-stat-icon"><i class="fas fa-calendar-alt"></i></span>
                    <span class="db-stat-trend"><i class="fas fa-layer-group"></i> Total</span>
                </div>
                <div class="db-stat-value"><?php echo (int)$total_count; ?></div>
                <div class="db-stat-label">Total Appointments</div>
            </div>

            <div class="db-stat-card db-grad-5 db-fade-in d2">
                <div class="db-stat-top">
                    <span class="db-stat-icon"><i class="fas fa-calendar-check"></i></span>
                    <span class="db-stat-trend"><i class="fas fa-arrow-trend-up"></i> Ahead</span>
                </div>
                <div class="db-stat-value"><?php echo (int)$upcoming_count; ?></div>
                <div class="db-stat-label">Upcoming</div>
            </div>

            <div class="db-stat-card db-grad-6 db-fade-in d3">
                <div class="db-stat-top">
                    <span class="db-stat-icon"><i class="fas fa-check-double"></i></span>
                    <span class="db-stat-trend"><i class="fas fa-circle-check"></i> Done</span>
                </div>
                <div class="db-stat-value"><?php echo (int)$status_counts['completed']; ?></div>
                <div class="db-stat-label">Completed</div>
            </div>

            <div class="db-stat-card db-grad-4 db-fade-in d4">
                <div class="db-stat-top">
                    <span class="db-stat-icon"><i class="fas fa-xmark"></i></span>
                    <span class="db-stat-trend"><i class="fas fa-ban"></i> Cancelled</span>
                </div>
                <div class="db-stat-value"><?php echo (int)$status_counts['cancelled']; ?></div>
                <div class="db-stat-label">Cancelled</div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="db-panel db-fade-in" style="margin-bottom: 1.75rem;">
            <div class="db-panel-header">
                <span class="db-panel-title"><i class="fas fa-bolt"></i> Quick Actions</span>
            </div>
            <div class="db-grid db-quick-actions">
                <a href="doctors.php" class="db-qa-btn">
                    <span class="db-qa-icon db-grad-1"><i class="fas fa-user-md"></i></span>
                    <span class="db-qa-label">Find a Doctor</span>
                    <small>Browse specialists</small>
                </a>
                <a href="doctors.php" class="db-qa-btn">
                    <span class="db-qa-icon db-grad-5"><i class="fas fa-calendar-plus"></i></span>
                    <span class="db-qa-label">Book Appointment</span>
                    <small>Schedule a visit</small>
                </a>
                <a href="my_appointments.php" class="db-qa-btn">
                    <span class="db-qa-icon db-grad-3"><i class="fas fa-list-check"></i></span>
                    <span class="db-qa-label">My Appointments</span>
                    <small>View booking history</small>
                </a>
            </div>
        </div>

        <div class="db-grid db-main-grid">
            <!-- Left column -->
            <div class="db-grid" style="gap: 1.75rem;">

                <?php if ($next_appointment): ?>
                <!-- Next appointment highlight -->
                <div class="db-panel db-fade-in d1" style="background: var(--gradient-primary); color:#fff; border:none;">
                    <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:1rem;">
                        <div>
                            <div style="font-size:var(--fs-xs); text-transform:uppercase; letter-spacing:.06em; opacity:.85; margin-bottom:.35rem;">
                                <i class="fas fa-star"></i> Your Next Appointment
                            </div>
                            <div style="font-size:var(--fs-lg); font-weight:700;">
                                Dr. <?php echo htmlspecialchars($next_appointment['doctor_first'] . ' ' . $next_appointment['doctor_last']); ?>
                                <?php if (!empty($next_appointment['specialty_name'])): ?>
                                    <span style="font-weight:400; opacity:.9;">— <?php echo htmlspecialchars($next_appointment['specialty_name']); ?></span>
                                <?php endif; ?>
                            </div>
                            <div style="font-size:var(--fs-sm); opacity:.92; margin-top:.25rem;">
                                <i class="fas fa-calendar-day"></i> <?php echo date('l, M j, Y', strtotime($next_appointment['appointment_date'])); ?>
                                &nbsp;·&nbsp; <i class="fas fa-clock"></i> <?php echo date('g:i A', strtotime($next_appointment['appointment_time'])); ?>
                                <?php if ($days_until_next !== null): ?>
                                    &nbsp;·&nbsp; <?php echo $days_until_next == 0 ? 'Today' : ($days_until_next == 1 ? 'Tomorrow' : 'in ' . (int)$days_until_next . ' days'); ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        <a href="appointment_details.php?id=<?php echo $next_appointment['id']; ?>" class="mb-btn" style="background:rgba(255,255,255,.2); color:#fff;">
                            View Details
                        </a>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Recent Activity -->
                <div class="db-panel db-fade-in d2">
                    <div class="db-panel-header">
                        <span class="db-panel-title"><i class="fas fa-list-check"></i> Recent Activity</span>
                        <a href="my_appointments.php" class="db-panel-link">View all</a>
                    </div>
                    <ul class="db-activity-list">
                        <?php if (empty($recent_appointments)): ?>
                            <div class="db-empty"><i class="fas fa-calendar-times"></i>No appointments yet — book your first visit.</div>
                        <?php endif; ?>
                        <?php foreach ($recent_appointments as $appointment): ?>
                            <?php $st = $appointment['status']; ?>
                            <li class="db-activity-item">
                                <span class="db-activity-dot <?php echo $st; ?>">
                                    <i class="fas <?php echo $st === 'cancelled' ? 'fa-xmark' : ($st === 'completed' ? 'fa-check' : 'fa-calendar-day'); ?>"></i>
                                </span>
                                <div class="db-activity-body">
                                    <div class="db-activity-title">Dr. <?php echo htmlspecialchars($appointment['doctor_first'] . ' ' . $appointment['doctor_last']); ?></div>
                                    <div class="db-activity-meta"><?php echo date('M j, Y', strtotime($appointment['appointment_date'])); ?> at <?php echo date('g:i A', strtotime($appointment['appointment_time'])); ?><?php echo !empty($appointment['specialty_name']) ? ' · ' . htmlspecialchars($appointment['specialty_name']) : ''; ?></div>
                                </div>
                                <span class="db-activity-badge db-badge-<?php echo $st; ?>"><?php echo ucfirst($st); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>

            <!-- Right column -->
            <div class="db-grid" style="gap: 1.75rem;">

                <!-- Donut chart -->
                <div class="db-panel db-fade-in d1">
                    <div class="db-panel-header">
                        <span class="db-panel-title"><i class="fas fa-chart-pie"></i> Your Appointments</span>
                    </div>
                    <div class="db-donut-wrap">
                        <div class="db-donut" style="--p1:<?php echo round($status_counts['completed']/$status_total*100); ?>; --p2:<?php echo round($status_counts['scheduled']/$status_total*100); ?>; --p3:<?php echo round($status_counts['confirmed']/$status_total*100); ?>;">
                            <div class="db-donut-center">
                                <span class="num"><?php echo (int)$total_count; ?></span>
                                <span class="lbl">Total</span>
                            </div>
                        </div>
                        <ul class="db-donut-legend">
                            <li><span class="dot" style="background:#10B981;"></span> Completed <span class="val"><?php echo $status_counts['completed']; ?></span></li>
                            <li><span class="dot" style="background:#F59E0B;"></span> Scheduled <span class="val"><?php echo $status_counts['scheduled']; ?></span></li>
                            <li><span class="dot" style="background:#6366F1;"></span> Confirmed <span class="val"><?php echo $status_counts['confirmed']; ?></span></li>
                            <li><span class="dot" style="background:#EF4444;"></span> Cancelled <span class="val"><?php echo $status_counts['cancelled']; ?></span></li>
                        </ul>
                    </div>
                </div>

                <!-- Progress bar -->
                <div class="db-panel db-fade-in d2">
                    <div class="db-panel-header">
                        <span class="db-panel-title"><i class="fas fa-id-card"></i> Profile Completeness</span>
                    </div>
                    <div class="db-progress-item" style="margin-bottom:0;">
                        <div class="db-progress-labels"><span class="name">Your profile</span><span class="pct"><?php echo $profile_completion; ?>%</span></div>
                        <div class="db-progress-track"><div class="db-progress-fill grad-green" data-value="<?php echo $profile_completion; ?>"></div></div>
                    </div>
                    <?php if ($profile_completion < 100): ?>
                        <p style="font-size:var(--fs-xs); color:var(--color-text-soft); margin-top:.75rem;">Add your phone, date of birth, and address so doctors have your full details on file.</p>
                    <?php endif; ?>
                </div>

                <!-- Notifications -->
                <div class="db-panel db-fade-in d3">
                    <div class="db-panel-header">
                        <span class="db-panel-title"><i class="fas fa-bell"></i> Notifications</span>
                    </div>
                    <ul class="db-notif-list">
                        <?php if ($next_appointment && $days_until_next !== null && $days_until_next <= 3): ?>
                        <li class="db-notif-item unread">
                            <span class="db-notif-icon"><i class="fas fa-bell"></i></span>
                            <div>
                                <div class="db-notif-text">Reminder: appointment with Dr. <?php echo htmlspecialchars($next_appointment['doctor_first'] . ' ' . $next_appointment['doctor_last']); ?>
                                    <?php echo $days_until_next == 0 ? 'today' : ($days_until_next == 1 ? 'tomorrow' : 'in ' . (int)$days_until_next . ' days'); ?>.</div>
                                <div class="db-notif-time"><?php echo date('M j, g:i A', strtotime($next_appointment['appointment_date'] . ' ' . $next_appointment['appointment_time'])); ?></div>
                            </div>
                        </li>
                        <?php endif; ?>
                        <?php if ($status_counts['cancelled'] > 0): ?>
                        <li class="db-notif-item danger">
                            <span class="db-notif-icon"><i class="fas fa-ban"></i></span>
                            <div>
                                <div class="db-notif-text">You have <strong><?php echo (int)$status_counts['cancelled']; ?></strong> cancelled appointment<?php echo $status_counts['cancelled'] == 1 ? '' : 's'; ?>.</div>
                                <div class="db-notif-time">View history</div>
                            </div>
                        </li>
                        <?php endif; ?>
                        <?php if ($profile_completion < 100): ?>
                        <li class="db-notif-item warn">
                            <span class="db-notif-icon"><i class="fas fa-id-card"></i></span>
                            <div>
                                <div class="db-notif-text">Your profile is <?php echo $profile_completion; ?>% complete.</div>
                                <div class="db-notif-time">Finish setting it up</div>
                            </div>
                        </li>
                        <?php endif; ?>
                        <?php if (!$next_appointment && $status_counts['cancelled'] == 0 && $profile_completion >= 100): ?>
                        <li class="db-notif-item">
                            <span class="db-notif-icon"><i class="fas fa-check"></i></span>
                            <div>
                                <div class="db-notif-text">You're all caught up — no pending reminders.</div>
                            </div>
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>

                <!-- Calendar widget -->
                <div class="db-panel db-fade-in d4">
                    <div class="db-panel-header">
                        <span class="db-panel-title"><i class="fas fa-calendar"></i> <span data-cal-month>This Month</span></span>
                    </div>
                    <div data-mb-calendar data-appointments='<?php echo htmlspecialchars(json_encode(array_values($cal_dates)), ENT_QUOTES); ?>'>
                        <div class="db-calendar-grid" data-cal-grid></div>
                        <div class="db-calendar-legend"><span class="dot"></span> Has appointments</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php require 'includes/footer.php'; ?>
