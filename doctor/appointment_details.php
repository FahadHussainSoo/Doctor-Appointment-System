<?php
require_once '../includes/config.php';
requireDoctor();

$appointment_id = $_GET['id'] ?? '';

if (empty($appointment_id)) {
    header("Location: appointments.php");
    exit();
}

// Get doctor ID
$stmt = $pdo->prepare("SELECT id FROM doctors WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$doctor = $stmt->fetch(PDO::FETCH_ASSOC);
$doctor_id = $doctor['id'];

// Get appointment details
$stmt = $pdo->prepare("
    SELECT a.*, 
           u.first_name, u.last_name, u.email, u.phone, u.date_of_birth, u.address
    FROM appointments a
    JOIN users u ON a.patient_id = u.id
    WHERE a.id = ? AND a.doctor_id = ?
");
$stmt->execute([$appointment_id, $doctor_id]);
$appointment = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$appointment) {
    header("Location: appointments.php");
    exit();
}

// Handle status update
if (isset($_POST['update_status'])) {
    $status = $_POST['status'];
    $notes = $_POST['doctor_notes'];
    
    $update_stmt = $pdo->prepare("UPDATE appointments SET status = ?, notes = ? WHERE id = ?");
    $update_stmt->execute([$status, $notes, $appointment_id]);
    
    header("Location: appointment_details.php?id=" . $appointment_id . "&updated=1");
    exit();
}

// Calculate patient age
$age = '';
if ($appointment['date_of_birth']) {
    $birthDate = new DateTime($appointment['date_of_birth']);
    $today = new DateTime();
    $age = $today->diff($birthDate)->y;
}
?>
<?php
$pageTitle = 'Appointment Details';
$dashRole = 'doctor';
$activeMenu = 'appointments';
$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => 'index.php'],
    ['label' => 'My Appointments', 'url' => 'appointments.php'],
    ['label' => 'Appointment Details'],
];
require '../includes/dash_header.php';
?>
    <div class="mx-auto px-0 py-0">
        <div class="card card-hover-lift fade-in-up" style="border-radius:var(--radius-xl); overflow:hidden;">
            <!-- Header -->
            <div class="card-header card-header-gradient" style="padding:1.25rem 1.5rem;">
                <div class="flex justify-between items-center">
                    <h1 class="text-2xl font-bold" style="margin:0;">Appointment Details</h1>
                    <span class="crud-badge crud-badge-<?php echo htmlspecialchars($appointment['status']); ?>" style="background:rgba(255,255,255,.92);">
                        <?php echo ucfirst($appointment['status']); ?>
                    </span>
                </div>
            </div>

            <div class="p-6" style="padding:1.75rem;">
                <?php if (isset($_GET['updated'])): ?>
                    <div class="alert alert-success mb-6">Appointment updated successfully!</div>
                <?php endif; ?>

                <div class="grid md:grid-cols-2 gap-8 mb-8">
                    <!-- Patient Information -->
                    <div>
                        <h2 class="text-xl font-semibold text-gray-800 mb-4">Patient Information</h2>
                        <div class="space-y-3">
                            <div class="flex justify-between border-b pb-2">
                                <span class="text-gray-600">Name:</span>
                                <span class="font-semibold"><?php echo htmlspecialchars($appointment['first_name'] . ' ' . $appointment['last_name']); ?></span>
                            </div>
                            <?php if ($age): ?>
                            <div class="flex justify-between border-b pb-2">
                                <span class="text-gray-600">Age:</span>
                                <span class="font-semibold"><?php echo $age; ?> years</span>
                            </div>
                            <?php endif; ?>
                            <div class="flex justify-between border-b pb-2">
                                <span class="text-gray-600">Email:</span>
                                <span class="font-semibold"><?php echo htmlspecialchars($appointment['email']); ?></span>
                            </div>
                            <div class="flex justify-between border-b pb-2">
                                <span class="text-gray-600">Phone:</span>
                                <span class="font-semibold"><?php echo htmlspecialchars($appointment['phone'] ?: 'Not provided'); ?></span>
                            </div>
                            <?php if ($appointment['address']): ?>
                            <div class="flex justify-between border-b pb-2">
                                <span class="text-gray-600">Address:</span>
                                <span class="font-semibold text-right"><?php echo nl2br(htmlspecialchars($appointment['address'])); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Appointment Information -->
                    <div>
                        <h2 class="text-xl font-semibold text-gray-800 mb-4">Appointment Information</h2>
                        <div class="space-y-3">
                            <div class="flex justify-between border-b pb-2">
                                <span class="text-gray-600">Date:</span>
                                <span class="font-semibold"><?php echo date('F j, Y', strtotime($appointment['appointment_date'])); ?></span>
                            </div>
                            <div class="flex justify-between border-b pb-2">
                                <span class="text-gray-600">Time:</span>
                                <span class="font-semibold"><?php echo date('g:i A', strtotime($appointment['appointment_time'])); ?></span>
                            </div>
                            <div class="flex justify-between border-b pb-2">
                                <span class="text-gray-600">Status:</span>
                                <span class="crud-badge crud-badge-<?php echo htmlspecialchars($appointment['status']); ?>"><?php echo ucfirst($appointment['status']); ?></span>
                            </div>
                            <div class="flex justify-between border-b pb-2">
                                <span class="text-gray-600">Booked On:</span>
                                <span class="font-semibold"><?php echo date('F j, Y g:i A', strtotime($appointment['created_at'])); ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Symptoms, Notes, and Status update all live in ONE form (fixes the
                     source markup, which opened <form> mid-page and only closed it
                     two sections later — same fields/names, now validly nested). -->
                <form method="POST" class="crud-form">
                    <div class="grid md:grid-cols-2 gap-8 mb-8">
                        <div>
                            <h2 class="text-xl font-semibold text-gray-800 mb-4">Patient Symptoms</h2>
                            <div class="surface-muted p-4">
                                <p class="text-gray-700" style="margin:0;"><?php echo nl2br(htmlspecialchars($appointment['symptoms'])); ?></p>
                            </div>
                        </div>

                        <div>
                            <h2 class="text-xl font-semibold text-gray-800 mb-4">Doctor's Notes</h2>
                            <textarea name="doctor_notes" rows="6"
                                      placeholder="Enter your clinical notes, diagnosis, or treatment plan..."
                                      class="form-control"><?php echo htmlspecialchars($appointment['notes'] ?? ''); ?></textarea>
                        </div>
                    </div>

                    <!-- Status Update -->
                    <div class="surface-panel" style="background:var(--color-primary-tint); border:none; padding:1.5rem; margin-bottom:2rem;">
                        <h2 class="text-xl font-semibold text-gray-800 mb-4">Update Appointment Status</h2>
                        <div class="flex items-center space-x-4" style="display:flex; align-items:center; gap:1rem; flex-wrap:wrap;">
                            <select name="status" class="form-select" style="max-width:220px;">
                                <option value="scheduled" <?php echo $appointment['status'] == 'scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                                <option value="confirmed" <?php echo $appointment['status'] == 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                <option value="completed" <?php echo $appointment['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                <option value="cancelled" <?php echo $appointment['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                            <button type="submit" name="update_status" class="btn btn-primary">
                                <i class="fas fa-check"></i> Update Status &amp; Notes
                            </button>
                        </div>
                    </div>
                </form>

                <!-- Actions -->
                <div class="border-t pt-6">
                    <a href="appointments.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Appointments
                    </a>
                </div>
            </div>
        </div>
    </div>
<?php require '../includes/dash_footer.php'; ?>
