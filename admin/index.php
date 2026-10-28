<?php
require_once '../includes/config.php';
requireAdmin();

// ---- Existing Phase-1 stats (unchanged) ----
$users_count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$doctors_count = $pdo->query("SELECT COUNT(*) FROM doctors WHERE status = 'active'")->fetchColumn();
$appointments_count = $pdo->query("SELECT COUNT(*) FROM appointments")->fetchColumn();
$today_appointments = $pdo->query("SELECT COUNT(*) FROM appointments WHERE appointment_date = CURDATE()")->fetchColumn();

// ---- Phase 2B: additional read-only stats for the new widgets ----
// Status breakdown (for donut chart + progress bars)
$status_counts = ['scheduled' => 0, 'confirmed' => 0, 'completed' => 0, 'cancelled' => 0];
$status_stmt = $pdo->query("SELECT status, COUNT(*) as c FROM appointments GROUP BY status");
foreach ($status_stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    if (isset($status_counts[$row['status']])) $status_counts[$row['status']] = (int)$row['c'];
}
$status_total = max(1, array_sum($status_counts));

// Last 7 days appointment volume (for bar chart)
$trend_stmt = $pdo->prepare("
    SELECT appointment_date as d, COUNT(*) as c
    FROM appointments
    WHERE appointment_date BETWEEN DATE_SUB(CURDATE(), INTERVAL 6 DAY) AND CURDATE()
    GROUP BY appointment_date
");
$trend_stmt->execute();
$trend_raw = [];
foreach ($trend_stmt->fetchAll(PDO::FETCH_ASSOC) as $row) { $trend_raw[$row['d']] = (int)$row['c']; }
$trend = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i day"));
    $trend[] = ['label' => date('D', strtotime($d)), 'date' => $d, 'count' => $trend_raw[$d] ?? 0];
}
$trend_max = max(1, max(array_column($trend, 'count')));

// Appointment dates in the current month (for calendar widget)
$cal_stmt = $pdo->query("
    SELECT DISTINCT appointment_date FROM appointments
    WHERE MONTH(appointment_date) = MONTH(CURDATE()) AND YEAR(appointment_date) = YEAR(CURDATE())
");
$cal_dates = array_map(function ($r) { return $r['appointment_date']; }, $cal_stmt->fetchAll(PDO::FETCH_ASSOC));

// Pending doctor accounts (inactive) — used for a notification
$inactive_doctors = $pdo->query("SELECT COUNT(*) FROM doctors WHERE status = 'inactive'")->fetchColumn();

// Rates for progress bars
$confirmation_rate = round(($status_counts['confirmed'] + $status_counts['completed']) / $status_total * 100);
$completion_rate = round($status_counts['completed'] / $status_total * 100);
$cancellation_rate = round($status_counts['cancelled'] / $status_total * 100);
?>
<?php
$pageTitle = 'Admin Dashboard';
$dashRole = 'admin';
$activeMenu = 'dashboard';
$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => 'index.php'],
    ['label' => 'Overview'],
];
require '../includes/dash_header.php';
?>
            <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:.75rem; margin-bottom: 1.75rem;">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800">Admin Dashboard</h1>
                    <p class="text-gray-500" style="margin-top:.25rem;">Welcome back, <?php echo htmlspecialchars($_SESSION['first_name']); ?> — here's what's happening today.</p>
                </div>
            </div>

            <!-- Statistics Cards (gradient) -->
            <div class="db-grid db-stats-row">
                <div class="db-stat-card db-grad-1 db-fade-in d1">
                    <div class="db-stat-top">
                        <span class="db-stat-icon"><i class="fas fa-users"></i></span>
                        <span class="db-stat-trend"><i class="fas fa-arrow-trend-up"></i> All time</span>
                    </div>
                    <div class="db-stat-value"><?php echo (int)$users_count; ?></div>
                    <div class="db-stat-label">Total Users</div>
                </div>

                <div class="db-stat-card db-grad-2 db-fade-in d2">
                    <div class="db-stat-top">
                        <span class="db-stat-icon"><i class="fas fa-user-md"></i></span>
                        <span class="db-stat-trend"><i class="fas fa-circle-check"></i> Active</span>
                    </div>
                    <div class="db-stat-value"><?php echo (int)$doctors_count; ?></div>
                    <div class="db-stat-label">Active Doctors</div>
                </div>

                <div class="db-stat-card db-grad-3 db-fade-in d3">
                    <div class="db-stat-top">
                        <span class="db-stat-icon"><i class="fas fa-calendar-alt"></i></span>
                        <span class="db-stat-trend"><i class="fas fa-layer-group"></i> Total</span>
                    </div>
                    <div class="db-stat-value"><?php echo (int)$appointments_count; ?></div>
                    <div class="db-stat-label">Total Appointments</div>
                </div>

                <div class="db-stat-card db-grad-4 db-fade-in d4">
                    <div class="db-stat-top">
                        <span class="db-stat-icon"><i class="fas fa-clock"></i></span>
                        <span class="db-stat-trend"><i class="fas fa-bolt"></i> Today</span>
                    </div>
                    <div class="db-stat-value"><?php echo (int)$today_appointments; ?></div>
                    <div class="db-stat-label">Today's Appointments</div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="db-panel db-fade-in" style="margin-bottom: 1.75rem;">
                <div class="db-panel-header">
                    <span class="db-panel-title"><i class="fas fa-bolt"></i> Quick Actions</span>
                </div>
                <div class="db-grid db-quick-actions">
                    <a href="appointments.php" class="db-qa-btn">
                        <span class="db-qa-icon db-grad-3"><i class="fas fa-calendar-alt"></i></span>
                        <span class="db-qa-label">Manage Appointments</span>
                        <small>View &amp; update all bookings</small>
                    </a>
                    <a href="users.php" class="db-qa-btn">
                        <span class="db-qa-icon db-grad-1"><i class="fas fa-users"></i></span>
                        <span class="db-qa-label">Manage Users</span>
                        <small>Patients &amp; accounts</small>
                    </a>
                    <a href="doctors.php" class="db-qa-btn">
                        <span class="db-qa-icon db-grad-2"><i class="fas fa-user-md"></i></span>
                        <span class="db-qa-label">Manage Doctors</span>
                        <small>Add or edit profiles</small>
                    </a>
                    <a href="appointments.php?filter=scheduled" class="db-qa-btn">
                        <span class="db-qa-icon db-grad-4"><i class="fas fa-hourglass-half"></i></span>
                        <span class="db-qa-label">Pending Review</span>
                        <small><?php echo (int)$status_counts['scheduled']; ?> awaiting confirmation</small>
                    </a>
                </div>
            </div>

            <div class="db-grid db-main-grid">
                <!-- Left column -->
                <div class="db-grid" style="gap: 1.75rem;">

                    <!-- 7-day trend chart -->
                    <div class="db-panel db-fade-in d1">
                        <div class="db-panel-header">
                            <span class="db-panel-title"><i class="fas fa-chart-column"></i> Bookings — Last 7 Days</span>
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

                    <!-- Recent Activity -->
                    <div class="db-panel db-fade-in d2">
                        <div class="db-panel-header">
                            <span class="db-panel-title"><i class="fas fa-list-check"></i> Recent Activity</span>
                            <a href="appointments.php" class="db-panel-link">View all</a>
                        </div>
                        <ul class="db-activity-list">
                            <?php
                            $stmt = $pdo->query("
                                SELECT a.*, 
                                       up.first_name as patient_first, up.last_name as patient_last,
                                       ud.first_name as doctor_first, ud.last_name as doctor_last
                                FROM appointments a
                                JOIN users up ON a.patient_id = up.id
                                JOIN doctors d ON a.doctor_id = d.id
                                JOIN users ud ON d.user_id = ud.id
                                ORDER BY a.created_at DESC LIMIT 6
                            ");
                            $recentRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            ?>
                            <?php if (empty($recentRows)): ?>
                                <div class="db-empty"><i class="fas fa-calendar-times"></i>No activity yet.</div>
                            <?php endif; ?>
                            <?php foreach ($recentRows as $appointment): ?>
                                <?php $st = $appointment['status']; ?>
                                <li class="db-activity-item">
                                    <span class="db-activity-dot <?php echo $st; ?>">
                                        <i class="fas <?php echo $st === 'cancelled' ? 'fa-xmark' : ($st === 'completed' ? 'fa-check' : 'fa-calendar-day'); ?>"></i>
                                    </span>
                                    <div class="db-activity-body">
                                        <div class="db-activity-title"><?php echo htmlspecialchars($appointment['patient_first'] . ' ' . $appointment['patient_last']); ?> &rarr; Dr. <?php echo htmlspecialchars($appointment['doctor_first'] . ' ' . $appointment['doctor_last']); ?></div>
                                        <div class="db-activity-meta"><?php echo date('M j, Y', strtotime($appointment['appointment_date'])); ?> at <?php echo date('g:i A', strtotime($appointment['appointment_time'])); ?></div>
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
                            <span class="db-panel-title"><i class="fas fa-chart-pie"></i> Status Breakdown</span>
                        </div>
                        <div class="db-donut-wrap">
                            <div class="db-donut" style="--p1:<?php echo round($status_counts['completed']/$status_total*100); ?>; --p2:<?php echo round($status_counts['scheduled']/$status_total*100); ?>; --p3:<?php echo round($status_counts['confirmed']/$status_total*100); ?>;">
                                <div class="db-donut-center">
                                    <span class="num"><?php echo (int)$appointments_count; ?></span>
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
                        <div class="db-progress-item">
                            <div class="db-progress-labels"><span class="name">Cancellation Rate</span><span class="pct"><?php echo $cancellation_rate; ?>%</span></div>
                            <div class="db-progress-track"><div class="db-progress-fill grad-warm" data-value="<?php echo $cancellation_rate; ?>"></div></div>
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
                                    <div class="db-notif-text"><strong><?php echo (int)$today_appointments; ?></strong> appointment<?php echo $today_appointments == 1 ? '' : 's'; ?> scheduled for today.</div>
                                    <div class="db-notif-time">Just now</div>
                                </div>
                            </li>
                            <?php if ($status_counts['scheduled'] > 0): ?>
                            <li class="db-notif-item warn">
                                <span class="db-notif-icon"><i class="fas fa-hourglass-half"></i></span>
                                <div>
                                    <div class="db-notif-text"><strong><?php echo (int)$status_counts['scheduled']; ?></strong> appointment<?php echo $status_counts['scheduled'] == 1 ? '' : 's'; ?> awaiting confirmation.</div>
                                    <div class="db-notif-time">Today</div>
                                </div>
                            </li>
                            <?php endif; ?>
                            <?php if ($inactive_doctors > 0): ?>
                            <li class="db-notif-item danger">
                                <span class="db-notif-icon"><i class="fas fa-user-md"></i></span>
                                <div>
                                    <div class="db-notif-text"><strong><?php echo (int)$inactive_doctors; ?></strong> doctor account<?php echo $inactive_doctors == 1 ? '' : 's'; ?> marked inactive.</div>
                                    <div class="db-notif-time">Review in Doctors</div>
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

            <!-- Recent Appointments table (existing functionality, kept) -->
            <div class="db-panel db-fade-in" style="margin-top: 1.75rem;">
                <div class="db-panel-header">
                    <span class="db-panel-title"><i class="fas fa-table-list"></i> All Recent Appointments</span>
                </div>
                <div class="crud-table-scroll">
                    <table class="table align-middle w-100">
                        <thead>
                            <tr>
                                <th>Patient</th>
                                <th>Doctor</th>
                                <th>Date &amp; Time</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentRows as $appointment): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($appointment['patient_first'] . ' ' . $appointment['patient_last']); ?></td>
                                <td>Dr. <?php echo htmlspecialchars($appointment['doctor_first'] . ' ' . $appointment['doctor_last']); ?></td>
                                <td>
                                    <?php echo date('M j, Y', strtotime($appointment['appointment_date'])); ?><br>
                                    <span class="text-gray-500 text-sm"><?php echo date('g:i A', strtotime($appointment['appointment_time'])); ?></span>
                                </td>
                                <td>
                                    <span class="crud-badge crud-badge-<?php echo htmlspecialchars($appointment['status']); ?>">
                                        <?php echo ucfirst($appointment['status']); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
<?php require '../includes/dash_footer.php'; ?>
