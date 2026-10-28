<?php
require_once 'includes/config.php';

if (!isLoggedIn() || isAdmin()) {
    header("Location: login.php");
    exit();
}

// Get filter parameters
$filter = $_GET['filter'] ?? 'all';
$date_filter = $_GET['date'] ?? '';

// Build query
$query = "
    SELECT a.*, u.first_name as doctor_first, u.last_name as doctor_last, s.name as specialty_name
    FROM appointments a
    JOIN doctors d ON a.doctor_id = d.id
    JOIN users u ON d.user_id = u.id
    JOIN specialties s ON d.specialty_id = s.id
    WHERE a.patient_id = ?
";

$params = [$_SESSION['user_id']];

if ($filter !== 'all') {
    $query .= " AND a.status = ?";
    $params[] = $filter;
}

if (!empty($date_filter)) {
    $query .= " AND a.appointment_date = ?";
    $params[] = $date_filter;
}

$query .= " ORDER BY a.appointment_date DESC, a.appointment_time DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<?php
$pageTitle = 'Appointment History';
$activeNav = 'appointments';
$breadcrumbs = [
    ['label' => 'Home', 'url' => 'index.php'],
    ['label' => 'Appointment History'],
];
require 'includes/header.php';
?>

    <div class="max-w-7xl mx-auto px-4 py-8">
        <div class="crud-toolbar">
            <div>
                <h1 class="text-3xl font-bold text-gray-800">Appointment History</h1>
                <p class="text-gray-500" style="margin-top:.25rem;"><?php echo count($appointments); ?> appointment<?php echo count($appointments) == 1 ? '' : 's'; ?> matching your filters</p>
            </div>
            <a href="doctors.php" class="crud-add-btn"><i class="fas fa-calendar-plus"></i> Book New Appointment</a>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success mb-4">Appointment booked successfully!</div>
        <?php endif; ?>

        <?php if (isset($_GET['cancelled'])): ?>
            <div class="alert alert-info mb-4">Appointment cancelled successfully.</div>
        <?php endif; ?>

        <!-- Filters -->
        <div class="db-panel mb-6">
            <div class="db-panel-header">
                <span class="db-panel-title"><i class="fas fa-filter"></i> Filters</span>
            </div>
            <form method="GET" class="row g-3 crud-form align-items-end">
                <div class="col-md-5">
                    <label class="form-label">Status</label>
                    <select name="filter" class="form-select">
                        <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>All Appointments</option>
                        <option value="scheduled" <?php echo $filter === 'scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                        <option value="confirmed" <?php echo $filter === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                        <option value="completed" <?php echo $filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="cancelled" <?php echo $filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Date</label>
                    <input type="date" name="date" value="<?php echo htmlspecialchars($date_filter); ?>" class="form-control">
                </div>
                <div class="col-md-3">
                    <button type="submit" class="crud-add-btn w-100 justify-content-center">
                        <i class="fas fa-filter"></i> Apply Filters
                    </button>
                </div>
            </form>
        </div>

        <!-- Appointments -->
        <div class="crud-table-card">
            <div class="crud-table-header">
                <h2><i class="fas fa-calendar-alt"></i> Your Appointments</h2>
            </div>
            <div class="crud-table-scroll">
                <div class="table-responsive">
                    <table class="table align-middle w-100">
                        <thead>
                            <tr>
                                <th>Doctor</th>
                                <th>Date &amp; Time</th>
                                <th>Symptoms</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($appointments as $appointment): ?>
                            <tr>
                                <td>
                                    <div class="flex items-center gap-2" style="display:flex; align-items:center; gap:.6rem;">
                                        <span class="avatar-circle" style="background:var(--gradient-primary); display:flex; align-items:center; justify-content:center; color:#fff; font-weight:700; font-size:var(--fs-xs);">
                                            <?php echo strtoupper(substr($appointment['doctor_first'], 0, 1) . substr($appointment['doctor_last'], 0, 1)); ?>
                                        </span>
                                        <div>
                                            <div class="font-medium">Dr. <?php echo htmlspecialchars($appointment['doctor_first'] . ' ' . $appointment['doctor_last']); ?></div>
                                            <div class="text-gray-500 text-sm"><?php echo htmlspecialchars($appointment['specialty_name']); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="font-medium"><?php echo date('M j, Y', strtotime($appointment['appointment_date'])); ?></div>
                                    <div class="text-gray-500 text-sm"><?php echo date('g:i A', strtotime($appointment['appointment_time'])); ?></div>
                                </td>
                                <td><div class="max-w-xs" style="max-width:220px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"><?php echo htmlspecialchars($appointment['symptoms']); ?></div></td>
                                <td>
                                    <span class="crud-badge crud-badge-<?php echo htmlspecialchars($appointment['status']); ?>">
                                        <?php echo ucfirst($appointment['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="crud-actions">
                                        <a href="appointment_details.php?id=<?php echo $appointment['id']; ?>" class="crud-act-btn crud-act-view">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                        <?php if ($appointment['status'] == 'scheduled' || $appointment['status'] == 'confirmed'): ?>
                                            <a href="cancel_appointmet.php?id=<?php echo $appointment['id']; ?>"
                                               class="crud-act-btn crud-act-delete"
                                               onclick="return confirm('Are you sure you want to cancel this appointment?')">
                                                <i class="fas fa-ban"></i> Cancel
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if (empty($appointments)): ?>
                    <div class="empty-state">
                        <i class="fas fa-calendar-times"></i>
                        <h3 class="text-xl font-semibold text-gray-600 mb-1">No Appointments Found</h3>
                        <p>Try adjusting your filters, or book your next visit.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php require 'includes/footer.php'; ?>
