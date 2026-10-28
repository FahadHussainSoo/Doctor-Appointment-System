<?php
require_once '../includes/config.php';
requireDoctor();

// ---- Existing Phase-1 lookups & stats (unchanged) ----
$stmt = $pdo->prepare("SELECT id FROM doctors WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$doctor = $stmt->fetch(PDO::FETCH_ASSOC);
$doctor_id = $doctor['id'];

$total_appointments = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE doctor_id = ?");
$total_appointments->execute([$doctor_id]);
$total_count = $total_appointments->fetchColumn();

$today_appointments = $pdo->prepare("
    SELECT COUNT(*) FROM appointments 
    WHERE doctor_id = ? AND appointment_date = CURDATE() AND status IN ('scheduled', 'confirmed')
");
$today_appointments->execute([$doctor_id]);
$today_count = $today_appointments->fetchColumn();

$upcoming_appointments = $pdo->prepare("
    SELECT COUNT(*) FROM appointments 
    WHERE doctor_id = ? AND appointment_date >= CURDATE() AND status IN ('scheduled', 'confirmed')
");
$upcoming_appointments->execute([$doctor_id]);
$upcoming_count = $upcoming_appointments->fetchColumn();

$recent_stmt = $pdo->prepare("
    SELECT a.*, u.first_name, u.last_name, u.email, u.phone
    FROM appointments a
    JOIN users u ON a.patient_id = u.id
    WHERE a.doctor_id = ?
    ORDER BY a.appointment_date DESC, a.appointment_time DESC
    LIMIT 5
");
$recent_stmt->execute([$doctor_id]);
$recent_appointments = $recent_stmt->fetchAll(PDO::FETCH_ASSOC);

$today_stmt = $pdo->prepare("
    SELECT a.*, u.first_name, u.last_name
    FROM appointments a
    JOIN users u ON a.patient_id = u.id
    WHERE a.doctor_id = ? AND a.appointment_date = CURDATE() AND a.status IN ('scheduled', 'confirmed')
    ORDER BY a.appointment_time
");
$today_stmt->execute([$doctor_id]);
$today_schedule = $today_stmt->fetchAll(PDO::FETCH_ASSOC);

// ---- Phase 2B: additional read-only stats for the new widgets ----
$status_counts = ['scheduled' => 0, 'confirmed' => 0, 'completed' => 0, 'cancelled' => 0];
$status_stmt = $pdo->prepare("SELECT status, COUNT(*) as c FROM appointments WHERE doctor_id = ? GROUP BY status");
$status_stmt->execute([$doctor_id]);
foreach ($status_stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    if (isset($status_counts[$row['status']])) $status_counts[$row['status']] = (int)$row['c'];
}
$status_total = max(1, array_sum($status_counts));

$trend_stmt = $pdo->prepare("
    SELECT appointment_date as d, COUNT(*) as c
    FROM appointments
    WHERE doctor_id = ? AND appointment_date BETWEEN DATE_SUB(CURDATE(), INTERVAL 6 DAY) AND CURDATE()
    GROUP BY appointment_date
");
$trend_stmt->execute([$doctor_id]);
$trend_raw = [];
foreach ($trend_stmt->fetchAll(PDO::FETCH_ASSOC) as $row) { $trend_raw[$row['d']] = (int)$row['c']; }
$trend = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i day"));
    $trend[] = ['label' => date('D', strtotime($d)), 'date' => $d, 'count' => $trend_raw[$d] ?? 0];
}
$trend_max = max(1, max(array_column($trend, 'count')));

$cal_stmt = $pdo->prepare("
    SELECT DISTINCT appointment_date FROM appointments
    WHERE doctor_id = ? AND MONTH(appointment_date) = MONTH(CURDATE()) AND YEAR(appointment_date) = YEAR(CURDATE())
");
$cal_stmt->execute([$doctor_id]);
$cal_dates = array_map(function ($r) { return $r['appointment_date']; }, $cal_stmt->fetchAll(PDO::FETCH_ASSOC));

$confirmation_rate = round(($status_counts['confirmed'] + $status_counts['completed']) / $status_total * 100);
$completion_rate = round($status_counts['completed'] / $status_total * 100);
?>
<?php
$pageTitle = 'Doctor Dashboard';
$dashRole = 'doctor';
$activeMenu = 'dashboard';
$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => 'index.php'],
    ['label' => 'Overview'],
];
require '../includes/dash_header.php';
?>
    <div class="mx-auto px-0 py-0">
        <h1 class="text-3xl font-bold text-gray-800">Doctor Dashboard</h1>
        <p class="text-gray-500" style="margin-top:.25rem; margin-bottom: 1.75rem;">Welcome back, Dr. <?php echo htmlspecialchars($_SESSION['first_name']); ?> — here's your day at a glance.</p>

        <!-- Statistics Cards (gradient) -->
        <div class="db-grid db-stats-row" style="grid-template-columns: repeat(3, 1fr);">
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
                    <span class="db-stat-icon"><i class="fas fa-clock"></i></span>
                    <span class="db-stat-trend"><i class="fas fa-bolt"></i> Today</span>
                </div>
                <div class="db-stat-value"><?php echo (int)$today_count; ?></div>
                <div class="db-stat-label">Today's Appointments</div>
            </div>

            <div class="db-stat-card db-grad-3 db-fade-in d3">
                <div class="db-stat-top">
                    <span class="db-stat-icon"><i class="fas fa-calendar-check"></i></span>
                    <span class="db-stat-trend"><i class="fas fa-arrow-trend-up"></i> Ahead</span>
                </div>
                <div class="db-stat-value"><?php echo (int)$upcoming_count; ?></div>
                <div class="db-stat-label">Upcoming Appointments</div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="db-panel db-fade-in" style="margin-bottom: 1.75rem;">
            <div class="db-panel-header">
                <span class="db-panel-title"><i class="fas fa-bolt"></i> Quick Actions</span>
            </div>
            <div class="db-grid db-quick-actions">
                <a href="appointments.php" class="db-qa-btn">
                    <span class="db-qa-icon db-grad-1"><i class="fas fa-calendar-alt"></i></span>
                    <span class="db-qa-label">View All Appointments</span>
                    <small>Full appointment list</small>
                </a>
                <a href="schedule.php" class="db-qa-btn">
                    <span class="db-qa-icon db-grad-5"><i class="fas fa-clock"></i></span>
                    <span class="db-qa-label">Manage Schedule</span>
                    <small>Set your availability</small>
                </a>
                <a href="#todays-schedule" class="db-qa-btn">
                    <span class="db-qa-icon db-grad-3"><i class="fas fa-list-check"></i></span>
                    <span class="db-qa-label">Today's Schedule</span>
                    <small><?php echo (int)$today_count; ?> patient<?php echo $today_count == 1 ? '' : 's'; ?> today</small>
                </a>
            </div>
        </div>

        <div class="db-grid db-main-grid">
            <!-- Left column -->
            <div class="db-grid" style="gap: 1.75rem;">

                <!-- 7-day trend chart -->
                <div class="db-panel db-fade-in d1">
                    <div class="db-panel-header">
                        <span class="db-panel-title"><i class="fas fa-chart-column"></i> Your Bookings — Last 7 Days</span>
                    </div>
                    <div class="db-bar-chart">
                        <?php foreach ($trend as $t): ?>
                            <div class="db-bar-col">
                                <span class="day-val"><?php echo $t['count']; ?></span>
                                <div class="db-bar-track">
                                    <div class="db-bar-fill" data-height="<?php echo round($t['count'] / $trend_max * 100); ?>"></div>
                                </div>
                                <span class="day-lbl"><?php echo $t['label']; ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Recent Activity (existing recent-appointments data, restyled) -->
                <div class="db-panel db-fade-in d2">
                    <div class="db-panel-header">
                        <span class="db-panel-title"><i class="fas fa-list-check"></i> Recent Activity</span>
                        <a href="appointments.php" class="db-panel-link">View all</a>
                    </div>
                    <ul class="db-activity-list">
                        <?php if (empty($recent_appointments)): ?>
                            <div class="db-empty"><i class="fas fa-calendar-times"></i>No appointments yet.</div>
                        <?php endif; ?>
                        <?php foreach ($recent_appointments as $appointment): ?>
                            <?php $st = $appointment['status']; ?>
                            <li class="db-activity-item">
                                <span class="db-activity-dot <?php echo $st; ?>">
                                    <i class="fas <?php echo $st === 'cancelled' ? 'fa-xmark' : ($st === 'completed' ? 'fa-check' : 'fa-calendar-day'); ?>"></i>
                                </span>
                                <div class="db-activity-body">
                                    <div class="db-activity-title"><?php echo htmlspecialchars($appointment['first_name'] . ' ' . $appointment['last_name']); ?></div>
                                    <div class="db-activity-meta"><?php echo date('M j, Y', strtotime($appointment['appointment_date'])); ?> at <?php echo date('g:i A', strtotime($appointment['appointment_time'])); ?></div>
                                </div>
                                <span class="db-activity-badge db-badge-<?php echo $st; ?>"><?php echo ucfirst($st); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <!-- Today's Schedule (existing functionality, restyled) -->
                <div class="db-panel db-fade-in d3" id="todays-schedule">
                    <div class="db-panel-header">
                        <span class="db-panel-title"><i class="fas fa-hospital-user"></i> Today's Schedule</span>
                    </div>
                    <?php if (!empty($today_schedule)): ?>
                        <ul class="db-activity-list">
                            <?php foreach ($today_schedule as $appointment): ?>
                                <li class="db-activity-item">
                                    <span class="db-activity-dot <?php echo $appointment['status']; ?>"><i class="fas fa-user"></i></span>
                                    <div class="db-activity-body">
                                        <div class="db-activity-title"><?php echo htmlspecialchars($appointment['first_name'] . ' ' . $appointment['last_name']); ?></div>
                                        <div class="db-activity-meta"><?php echo date('g:i A', strtotime($appointment['appointment_time'])); ?></div>
                                    </div>
                                    <span class="db-activity-badge db-badge-<?php echo $appointment['status']; ?>"><?php echo ucfirst($appointment['status']); ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <div class="db-empty"><i class="fas fa-mug-hot"></i>No appointments today — enjoy the breather.</div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Right column -->
            <div class="db-grid" style="gap: 1.75rem;">

                <!-- Donut chart -->
                <div class="db-panel db-fade-in d1">
                    <div class="db-panel-header">
                        <span class="db-panel-title"><i class="fas fa-chart-pie"></i> Status Breakdown</span>
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

                <!-- Progress bars -->
                <div class="db-panel db-fade-in d2">
                    <div class="db-panel-header">
                        <span class="db-panel-title"><i class="fas fa-gauge-high"></i> Performance</span>
                    </div>
                    <div class="db-progress-item">
                        <div class="db-progress-labels"><span class="name">Confirmation Rate</span><span class="pct"><?php echo $confirmation_rate; ?>%</span></div>
                        <div class="db-progress-track"><div class="db-progress-fill" data-value="<?php echo $confirmation_rate; ?>"></div></div>
                    </div>
                    <div class="db-progress-item">
                        <div class="db-progress-labels"><span class="name">Completion Rate</span><span class="pct"><?php echo $completion_rate; ?>%</span></div>
                        <div class="db-progress-track"><div class="db-progress-fill grad-cool" data-value="<?php echo $completion_rate; ?>"></div></div>
                    </div>
                </div>

                <!-- Notifications -->
                <div class="db-panel db-fade-in d3">
                    <div class="db-panel-header">
                        <span class="db-panel-title"><i class="fas fa-bell"></i> Notifications</span>
                    </div>
                    <ul class="db-notif-list">
                        <li class="db-notif-item unread">
                            <span class="db-notif-icon"><i class="fas fa-calendar-day"></i></span>
                            <div>
                                <div class="db-notif-text">You have <strong><?php echo (int)$today_count; ?></strong> appointment<?php echo $today_count == 1 ? '' : 's'; ?> today.</div>
                                <div class="db-notif-time">Just now</div>
                            </div>
                        </li>
                        <?php if ($status_counts['scheduled'] > 0): ?>
                        <li class="db-notif-item warn">
                            <span class="db-notif-icon"><i class="fas fa-hourglass-half"></i></span>
                            <div>
                                <div class="db-notif-text"><strong><?php echo (int)$status_counts['scheduled']; ?></strong> appointment<?php echo $status_counts['scheduled'] == 1 ? '' : 's'; ?> awaiting your confirmation.</div>
                                <div class="db-notif-time">Review in Appointments</div>
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
<?php require '../includes/dash_footer.php'; ?>
