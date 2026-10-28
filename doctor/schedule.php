<?php
require_once '../includes/config.php';
requireDoctor();

// ---- Doctor lookup (same pattern as index.php / appointments.php) ----
$stmt = $pdo->prepare("
    SELECT d.*, s.name as specialty_name
    FROM doctors d
    LEFT JOIN specialties s ON d.specialty_id = s.id
    WHERE d.user_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$doctor = $stmt->fetch(PDO::FETCH_ASSOC);
$doctor_id = $doctor['id'];

// ---- Read-only date params (validated, defaulted — no writes on this page) ----
$selected_date = $_GET['date'] ?? date('Y-m-d');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $selected_date) || !strtotime($selected_date)) {
    $selected_date = date('Y-m-d');
}
$ym = $_GET['ym'] ?? date('Y-m', strtotime($selected_date));
if (!preg_match('/^\d{4}-\d{2}$/', $ym)) {
    $ym = date('Y-m', strtotime($selected_date));
}
list($cal_year, $cal_month) = array_map('intval', explode('-', $ym));

// ---- Dates with appointments in the viewed month (for calendar dots) ----
$cal_stmt = $pdo->prepare("
    SELECT DISTINCT appointment_date FROM appointments
    WHERE doctor_id = ? AND MONTH(appointment_date) = ? AND YEAR(appointment_date) = ?
");
$cal_stmt->execute([$doctor_id, $cal_month, $cal_year]);
$cal_dates_set = array_flip(array_map(function ($r) { return $r['appointment_date']; }, $cal_stmt->fetchAll(PDO::FETCH_ASSOC)));

// ---- Appointments booked on the selected day (for the time-slot view) ----
$day_stmt = $pdo->prepare("
    SELECT a.*, u.first_name, u.last_name
    FROM appointments a
    JOIN users u ON a.patient_id = u.id
    WHERE a.doctor_id = ? AND a.appointment_date = ? AND a.status IN ('scheduled','confirmed','completed')
    ORDER BY a.appointment_time
");
$day_stmt->execute([$doctor_id, $selected_date]);
$day_by_time = [];
foreach ($day_stmt->fetchAll(PDO::FETCH_ASSOC) as $a) {
    $day_by_time[$a['appointment_time']] = $a;
}

// ---- Weekly availability + this day's time slots (same 30-min slot logic
//      used for booking, reused here read-only — no INSERT/UPDATE happens
//      on this page) ----
$week_order = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
$available_days_arr = array_filter(array_map('trim', explode(',', $doctor['available_days'] ?? '')));
$selected_dow = date('D', strtotime($selected_date));
$is_working_day = in_array($selected_dow, $available_days_arr, true);

$slots = [];
if ($is_working_day && !empty($doctor['available_time_start']) && !empty($doctor['available_time_end'])) {
    $start = strtotime($doctor['available_time_start']);
    $end = strtotime($doctor['available_time_end']);
    while ($start < $end) {
        $slots[] = date('H:i:s', $start);
        $start += 1800; // 30 minutes — matches book_appointment.php
    }
}
$booked_count = 0;
foreach ($slots as $s) { if (isset($day_by_time[$s])) $booked_count++; }
$free_count = count($slots) - $booked_count;

// ---- Calendar month grid (server-rendered, so days can be real links) ----
$first_ts = mktime(0, 0, 0, $cal_month, 1, $cal_year);
$first_dow = (int)date('w', $first_ts);
$days_in_month = (int)date('t', $first_ts);
$days_in_prev_month = (int)date('t', mktime(0, 0, 0, $cal_month - 1, 1, $cal_year));
$today_str = date('Y-m-d');
$prev_ym = date('Y-m', strtotime("$cal_year-$cal_month-01 -1 month"));
$next_ym = date('Y-m', strtotime("$cal_year-$cal_month-01 +1 month"));
$month_label = date('F Y', $first_ts);

$pageTitle = 'My Schedule';
$dashRole = 'doctor';
$activeMenu = 'schedule';
$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => 'index.php'],
    ['label' => 'My Schedule'],
];
require '../includes/dash_header.php';
?>
    <div class="mx-auto px-0 py-0">
        <h1 class="text-3xl font-bold text-gray-800">My Schedule</h1>
        <p class="text-gray-500" style="margin-top:.25rem; margin-bottom: 1.75rem;">Your weekly availability, calendar, and time slots at a glance.</p>

        <!-- Weekly availability strip -->
        <div class="db-panel db-fade-in mb-6" style="margin-bottom:1.75rem;">
            <div class="db-panel-header">
                <span class="db-panel-title"><i class="fas fa-calendar-week"></i> Weekly Availability</span>
                <span class="text-gray-500 text-sm">
                    <?php if (!empty($doctor['available_time_start']) && !empty($doctor['available_time_end'])): ?>
                        <i class="fas fa-clock"></i> <?php echo date('g:i A', strtotime($doctor['available_time_start'])); ?> – <?php echo date('g:i A', strtotime($doctor['available_time_end'])); ?>
                    <?php endif; ?>
                </span>
            </div>
            <div class="weekday-strip">
                <?php foreach ($week_order as $dow): ?>
                    <span class="weekday-chip <?php echo in_array($dow, $available_days_arr, true) ? 'on' : ''; ?>" title="<?php echo $dow; ?>"><?php echo $dow; ?></span>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="db-grid db-main-grid" style="gap: 1.75rem;">
            <!-- Left: selected day's time slots -->
            <div class="db-grid" style="gap: 1.75rem;">
                <div class="db-panel db-fade-in">
                    <div class="db-panel-header">
                        <span class="db-panel-title"><i class="fas fa-list-check"></i> <?php echo date('l, F j, Y', strtotime($selected_date)); ?></span>
                        <?php if ($selected_date !== $today_str): ?>
                            <a href="schedule.php?date=<?php echo urlencode($today_str); ?>&ym=<?php echo urlencode(date('Y-m')); ?>" class="db-panel-link">Jump to today</a>
                        <?php endif; ?>
                    </div>

                    <?php if (!$is_working_day): ?>
                        <div class="db-empty"><i class="fas fa-mug-hot"></i>Not a working day — no availability configured for <?php echo htmlspecialchars($selected_dow); ?>.</div>
                    <?php elseif (empty($slots)): ?>
                        <div class="db-empty"><i class="fas fa-clock"></i>No time slots configured for this doctor.</div>
                    <?php else: ?>
                        <div class="slot-legend" style="margin-bottom:1rem;">
                            <span><span class="dot" style="background:#10B981;"></span><?php echo $free_count; ?> free</span>
                            <span><span class="dot" style="background:#EF4444;"></span><?php echo $booked_count; ?> booked</span>
                        </div>
                        <div class="slot-grid">
                            <?php foreach ($slots as $slot): ?>
                                <?php if (isset($day_by_time[$slot])): $a = $day_by_time[$slot]; ?>
                                    <div class="slot-static slot-booked" title="<?php echo htmlspecialchars($a['first_name'] . ' ' . $a['last_name']); ?>">
                                        <span><?php echo date('g:i A', strtotime($slot)); ?></span>
                                        <small><?php echo htmlspecialchars($a['first_name'] . ' ' . substr($a['last_name'], 0, 1) . '.'); ?></small>
                                    </div>
                                <?php else: ?>
                                    <div class="slot-static slot-free">
                                        <?php echo date('g:i A', strtotime($slot)); ?>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if (!empty($day_by_time)): ?>
                <!-- Booked appointments list for the day, with quick links -->
                <div class="db-panel db-fade-in d1">
                    <div class="db-panel-header">
                        <span class="db-panel-title"><i class="fas fa-hospital-user"></i> Booked This Day</span>
                    </div>
                    <ul class="db-activity-list">
                        <?php foreach ($day_by_time as $a): ?>
                            <li class="db-activity-item">
                                <span class="db-activity-dot <?php echo htmlspecialchars($a['status']); ?>"><i class="fas fa-user"></i></span>
                                <div class="db-activity-body">
                                    <div class="db-activity-title"><?php echo htmlspecialchars($a['first_name'] . ' ' . $a['last_name']); ?></div>
                                    <div class="db-activity-meta"><?php echo date('g:i A', strtotime($a['appointment_time'])); ?></div>
                                </div>
                                <span class="db-activity-badge db-badge-<?php echo htmlspecialchars($a['status']); ?>"><?php echo ucfirst($a['status']); ?></span>
                                <a href="appointment_details.php?id=<?php echo (int)$a['id']; ?>" class="crud-act-btn crud-act-view" style="margin-left:.5rem;">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
            </div>

            <!-- Right: month calendar navigator -->
            <div class="db-grid" style="gap: 1.75rem;">
                <div class="db-panel db-fade-in d1">
                    <div class="db-calendar-head">
                        <span class="month"><?php echo htmlspecialchars($month_label); ?></span>
                        <div class="db-calendar-nav">
                            <a href="schedule.php?ym=<?php echo urlencode($prev_ym); ?>&date=<?php echo urlencode($selected_date); ?>" aria-label="Previous month"><i class="fas fa-chevron-left"></i></a>
                            <a href="schedule.php?ym=<?php echo urlencode($next_ym); ?>&date=<?php echo urlencode($selected_date); ?>" aria-label="Next month"><i class="fas fa-chevron-right"></i></a>
                        </div>
                    </div>
                    <div class="db-calendar-grid">
                        <?php foreach (['S', 'M', 'T', 'W', 'T', 'F', 'S'] as $dowLbl): ?>
                            <div class="dow"><?php echo $dowLbl; ?></div>
                        <?php endforeach; ?>

                        <?php for ($i = $first_dow - 1; $i >= 0; $i--): ?>
                            <div class="db-cal-day other-month"><?php echo $days_in_prev_month - $i; ?></div>
                        <?php endfor; ?>

                        <?php for ($d = 1; $d <= $days_in_month; $d++):
                            $dateStr = sprintf('%04d-%02d-%02d', $cal_year, $cal_month, $d);
                            $classes = ['db-cal-day'];
                            if ($dateStr === $today_str) $classes[] = 'today';
                            if ($dateStr === $selected_date) $classes[] = 'selected';
                            if (isset($cal_dates_set[$dateStr])) $classes[] = 'has-appt';
                        ?>
                            <a href="schedule.php?date=<?php echo urlencode($dateStr); ?>&ym=<?php echo urlencode($ym); ?>" class="<?php echo implode(' ', $classes); ?>"><?php echo $d; ?></a>
                        <?php endfor; ?>

                        <?php
                        $total_cells = $first_dow + $days_in_month;
                        $trailing = (7 - ($total_cells % 7)) % 7;
                        for ($n = 1; $n <= $trailing; $n++):
                        ?>
                            <div class="db-cal-day other-month"><?php echo $n; ?></div>
                        <?php endfor; ?>
                    </div>
                    <div class="db-calendar-legend"><span class="dot"></span> Has appointments</div>
                </div>

                <div class="db-panel db-fade-in d2">
                    <div class="db-panel-header">
                        <span class="db-panel-title"><i class="fas fa-bolt"></i> Quick Actions</span>
                    </div>
                    <div class="db-grid db-quick-actions">
                        <a href="appointments.php" class="db-qa-btn">
                            <span class="db-qa-icon db-grad-1"><i class="fas fa-calendar-alt"></i></span>
                            <span class="db-qa-label">All Appointments</span>
                            <small>Full history &amp; filters</small>
                        </a>
                        <a href="index.php" class="db-qa-btn">
                            <span class="db-qa-icon db-grad-5"><i class="fas fa-gauge-high"></i></span>
                            <span class="db-qa-label">Dashboard</span>
                            <small>Overview &amp; stats</small>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php require '../includes/dash_footer.php'; ?>
