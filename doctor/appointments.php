<?php
require_once '../includes/config.php';
requireDoctor();

// Get doctor ID (unchanged)
$stmt = $pdo->prepare("SELECT id FROM doctors WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$doctor = $stmt->fetch(PDO::FETCH_ASSOC);
$doctor_id = $doctor['id'];

// Handle status updates (unchanged)
if (isset($_POST['update_status'])) {
    $appointment_id = $_POST['appointment_id'];
    $status = $_POST['status'];

    $stmt = $pdo->prepare("UPDATE appointments SET status = ? WHERE id = ? AND doctor_id = ?");
    $stmt->execute([$status, $appointment_id, $doctor_id]);

    header("Location: appointments.php?updated=1");
    exit();
}

// Get filter parameters (unchanged)
$filter = $_GET['filter'] ?? 'all';
$date_filter = $_GET['date'] ?? '';

// Build query (unchanged)
$query = "
    SELECT a.*, u.first_name, u.last_name, u.email, u.phone, u.date_of_birth
    FROM appointments a
    JOIN users u ON a.patient_id = u.id
    WHERE a.doctor_id = ?
";

$params = [$doctor_id];

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
$pageTitle = 'My Appointments';
$dashRole = 'doctor';
$activeMenu = 'appointments';
$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => 'index.php'],
    ['label' => 'My Appointments'],
];
require '../includes/dash_header.php';
?>
    <div class="mx-auto px-0 py-0">
        <div class="crud-toolbar">
            <div>
                <h1 class="text-3xl font-bold text-gray-800">My Appointments</h1>
                <p class="text-gray-500" style="margin-top:.25rem;"><?php echo count($appointments); ?> appointment<?php echo count($appointments) == 1 ? '' : 's'; ?> matching your filters</p>
            </div>
        </div>

        <?php if (isset($_GET['updated'])): ?>
            <div class="alert alert-success mb-6">Appointment status updated successfully!</div>
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

        <!-- Appointments Table -->
        <div class="crud-table-card">
            <div class="crud-table-header">
                <h2><i class="fas fa-calendar-alt"></i> Appointments</h2>
            </div>
            <div class="crud-table-scroll">
                <table class="mb-datatable table align-middle w-100">
                    <thead>
                        <tr>
                            <th>Patient</th>
                            <th>Contact</th>
                            <th>Date &amp; Time</th>
                            <th>Symptoms</th>
                            <th>Status</th>
                            <th class="no-sort">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($appointments as $appointment): ?>
                        <tr>
                            <td>
                                <div class="font-medium"><?php echo htmlspecialchars($appointment['first_name'] . ' ' . $appointment['last_name']); ?></div>
                                <div class="text-gray-500 text-sm">
                                    <?php
                                    if ($appointment['date_of_birth']) {
                                        $birthDate = new DateTime($appointment['date_of_birth']);
                                        $today = new DateTime();
                                        $age = $today->diff($birthDate)->y;
                                        echo $age . ' years';
                                    }
                                    ?>
                                </div>
                            </td>
                            <td>
                                <div class="text-sm"><?php echo htmlspecialchars($appointment['email']); ?></div>
                                <div class="text-gray-500 text-sm"><?php echo htmlspecialchars($appointment['phone'] ?: 'No phone'); ?></div>
                            </td>
                            <td>
                                <div class="font-medium"><?php echo date('M j, Y', strtotime($appointment['appointment_date'])); ?></div>
                                <div class="text-gray-500 text-sm"><?php echo date('g:i A', strtotime($appointment['appointment_time'])); ?></div>
                            </td>
                            <td><div class="max-w-xs"><?php echo nl2br(htmlspecialchars($appointment['symptoms'])); ?></div></td>
                            <td>
                                <span class="crud-badge crud-badge-<?php echo $appointment['status']; ?>">
                                    <?php echo ucfirst($appointment['status']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="crud-actions">
                                    <button type="button" class="crud-act-btn crud-act-edit"
                                            data-edit-trigger="#updateStatusModal"
                                            data-field-appointment_id="<?php echo $appointment['id']; ?>"
                                            data-field-status="<?php echo $appointment['status']; ?>"
                                            data-status-label="<?php echo htmlspecialchars($appointment['first_name'] . ' ' . $appointment['last_name'], ENT_QUOTES); ?>">
                                        <i class="fas fa-rotate"></i> Status
                                    </button>
                                    <a href="appointment_details.php?id=<?php echo $appointment['id']; ?>" class="crud-act-btn crud-act-view">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <?php if (empty($appointments)): ?>
                    <div class="db-empty" style="padding: 3rem 1rem;">
                        <i class="fas fa-calendar-times"></i>
                        <h3 class="text-xl font-semibold text-gray-600 mb-1">No Appointments Found</h3>
                        <p>Try adjusting your filters.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Update Status Modal -->
        <div class="modal fade" id="updateStatusModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form method="POST" class="crud-form">
                        <div class="modal-header">
                            <h5 class="modal-title"><i class="fas fa-rotate"></i> Update Appointment Status</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="appointment_id">
                            <p class="text-gray-600 mb-3" id="updateStatusLabel"></p>
                            <label class="form-label">Status</label>
                            <select name="status" required class="form-select">
                                <option value="scheduled">Scheduled</option>
                                <option value="confirmed">Confirmed</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="update_status" class="btn btn-primary"><i class="fas fa-check"></i> Update</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <script>
        document.querySelectorAll('[data-edit-trigger="#updateStatusModal"]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var label = document.getElementById('updateStatusLabel');
                if (label) label.textContent = btn.getAttribute('data-status-label') || '';
            });
        });
        </script>
    </div>
<?php require '../includes/dash_footer.php'; ?>
