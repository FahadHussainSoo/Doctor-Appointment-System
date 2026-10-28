<?php
require_once '../includes/config.php';
requireAdmin();

// Handle status updates (unchanged from Phase 1)
if (isset($_POST['update_status'])) {
    $appointment_id = $_POST['appointment_id'];
    $status = $_POST['status'];

    $stmt = $pdo->prepare("UPDATE appointments SET status = ? WHERE id = ?");
    $stmt->execute([$status, $appointment_id]);

    header("Location: appointments.php?updated=1");
    exit();
}

// Get all appointments
$stmt = $pdo->query("
    SELECT a.*, 
           up.first_name as patient_first, up.last_name as patient_last, up.email as patient_email,
           ud.first_name as doctor_first, ud.last_name as doctor_last, s.name as specialty
    FROM appointments a
    JOIN users up ON a.patient_id = up.id
    JOIN doctors d ON a.doctor_id = d.id
    JOIN users ud ON d.user_id = ud.id
    JOIN specialties s ON d.specialty_id = s.id
    ORDER BY a.appointment_date DESC, a.appointment_time DESC
");
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<?php
$pageTitle = 'Manage Appointments';
$dashRole = 'admin';
$activeMenu = 'appointments';
$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => 'index.php'],
    ['label' => 'Appointments'],
];
require '../includes/dash_header.php';
?>
            <div class="crud-toolbar">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800">Manage Appointments</h1>
                    <p class="text-gray-500" style="margin-top:.25rem;"><?php echo count($appointments); ?> appointment<?php echo count($appointments) == 1 ? '' : 's'; ?> total</p>
                </div>
            </div>

            <?php if (isset($_GET['updated'])): ?>
                <div class="alert alert-success mb-4" data-auto-dismiss><i class="fas fa-circle-check me-1"></i>
                    Appointment status updated successfully!
                </div>
            <?php endif; ?>

            <div class="crud-table-card">
                <div class="crud-table-header">
                    <h2><i class="fas fa-calendar-alt"></i> All Appointments</h2>
                </div>
                <div class="crud-table-scroll">
                    <table class="mb-datatable table align-middle w-100">
                        <thead>
                            <tr>
                                <th>Patient</th>
                                <th>Doctor &amp; Specialty</th>
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
                                    <div class="font-medium"><?php echo htmlspecialchars($appointment['patient_first'] . ' ' . $appointment['patient_last']); ?></div>
                                    <div class="text-gray-500 text-sm"><?php echo htmlspecialchars($appointment['patient_email']); ?></div>
                                </td>
                                <td>
                                    <div class="font-medium">Dr. <?php echo htmlspecialchars($appointment['doctor_first'] . ' ' . $appointment['doctor_last']); ?></div>
                                    <div class="text-gray-500 text-sm"><?php echo htmlspecialchars($appointment['specialty']); ?></div>
                                </td>
                                <td>
                                    <div class="font-medium"><?php echo date('M j, Y', strtotime($appointment['appointment_date'])); ?></div>
                                    <div class="text-gray-500 text-sm"><?php echo date('g:i A', strtotime($appointment['appointment_time'])); ?></div>
                                </td>
                                <td><div class="max-w-xs truncate"><?php echo htmlspecialchars($appointment['symptoms']); ?></div></td>
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
                                                data-status-label="<?php echo htmlspecialchars($appointment['patient_first'] . ' ' . $appointment['patient_last'] . ' — Dr. ' . $appointment['doctor_first'] . ' ' . $appointment['doctor_last'], ENT_QUOTES); ?>">
                                            <i class="fas fa-rotate"></i> Update Status
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
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
            // Small page-specific helper: show which appointment is being updated
            // (uses the same data-edit-trigger flow from crud.js, just adds a label).
            document.querySelectorAll('[data-edit-trigger="#updateStatusModal"]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var label = document.getElementById('updateStatusLabel');
                    if (label) label.textContent = btn.getAttribute('data-status-label') || '';
                });
            });
            </script>
<?php require '../includes/dash_footer.php'; ?>
