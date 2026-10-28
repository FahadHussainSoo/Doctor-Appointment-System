<?php
require_once 'includes/config.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$appointment_id = $_GET['id'] ?? '';

if (empty($appointment_id)) {
    header("Location: my_appointments.php");
    exit();
}

// Get appointment details
$stmt = $pdo->prepare("
    SELECT a.*, 
           u.first_name as doctor_first, u.last_name as doctor_last, u.email as doctor_email,
           s.name as specialty_name, d.qualification, d.experience_years, d.consultation_fee,
           d.available_days, d.available_time_start, d.available_time_end
    FROM appointments a
    JOIN doctors d ON a.doctor_id = d.id
    JOIN users u ON d.user_id = u.id
    JOIN specialties s ON d.specialty_id = s.id
    WHERE a.id = ? AND a.patient_id = ?
");
$stmt->execute([$appointment_id, $_SESSION['user_id']]);
$appointment = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$appointment) {
    header("Location: my_appointments.php");
    exit();
}
?>
<?php
$pageTitle = 'Appointment Details';
$activeNav = 'appointments';
$breadcrumbs = [
    ['label' => 'Home', 'url' => 'index.php'],
    ['label' => 'My Appointments', 'url' => 'my_appointments.php'],
    ['label' => 'Appointment Details'],
];
require 'includes/header.php';
?>

    <div class="max-w-4xl mx-auto px-4 py-8">
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
                <!-- Appointment Information -->
                <div class="grid md:grid-cols-2 gap-8 mb-8">
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
                                <span class="text-gray-600">Consultation Fee:</span>
                                <span class="font-semibold">$<?php echo htmlspecialchars($appointment['consultation_fee']); ?></span>
                            </div>
                            <div class="flex justify-between border-b pb-2">
                                <span class="text-gray-600">Booked On:</span>
                                <span class="font-semibold"><?php echo date('F j, Y g:i A', strtotime($appointment['created_at'])); ?></span>
                            </div>
                        </div>
                    </div>

                    <div>
                        <h2 class="text-xl font-semibold text-gray-800 mb-4">Doctor Information</h2>
                        <div class="flex items-center space-x-4 mb-4">
                            <div class="avatar-circle-lg" style="background:var(--gradient-cool); display:flex; align-items:center; justify-content:center;">
                                <i class="fas fa-user-md" style="color:#fff;"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold">
                                    Dr. <?php echo htmlspecialchars($appointment['doctor_first'] . ' ' . $appointment['doctor_last']); ?>
                                </h3>
                                <p style="color:var(--color-primary);"><?php echo htmlspecialchars($appointment['specialty_name']); ?></p>
                                <p class="text-gray-600 text-sm"><?php echo htmlspecialchars($appointment['doctor_email']); ?></p>
                            </div>
                        </div>
                        <div class="space-y-2 text-sm">
                            <div class="flex items-center text-gray-600">
                                <i class="fas fa-graduation-cap mr-2 w-5"></i>
                                <span><?php echo htmlspecialchars($appointment['qualification']); ?></span>
                            </div>
                            <div class="flex items-center text-gray-600">
                                <i class="fas fa-briefcase mr-2 w-5"></i>
                                <span><?php echo htmlspecialchars($appointment['experience_years']); ?> years experience</span>
                            </div>
                            <div class="flex items-center text-gray-600">
                                <i class="fas fa-calendar mr-2 w-5"></i>
                                <span>Available: <?php echo htmlspecialchars($appointment['available_days']); ?></span>
                            </div>
                            <div class="flex items-center text-gray-600">
                                <i class="fas fa-clock mr-2 w-5"></i>
                                <span>
                                    <?php echo date('g:i A', strtotime($appointment['available_time_start'])); ?> -
                                    <?php echo date('g:i A', strtotime($appointment['available_time_end'])); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Symptoms and Notes -->
                <div class="grid md:grid-cols-2 gap-8 mb-8">
                    <div>
                        <h2 class="text-xl font-semibold text-gray-800 mb-4">Symptoms</h2>
                        <div class="surface-muted p-4">
                            <p class="text-gray-700" style="margin:0;"><?php echo nl2br(htmlspecialchars($appointment['symptoms'])); ?></p>
                        </div>
                    </div>

                    <?php if (!empty($appointment['notes'])): ?>
                    <div>
                        <h2 class="text-xl font-semibold text-gray-800 mb-4">Additional Notes</h2>
                        <div class="surface-muted p-4">
                            <p class="text-gray-700" style="margin:0;"><?php echo nl2br(htmlspecialchars($appointment['notes'])); ?></p>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Actions -->
                <div class="border-t pt-6" style="display:flex; gap:1rem; flex-wrap:wrap;">
                    <a href="my_appointments.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Appointments
                    </a>
                    <?php if ($appointment['status'] == 'scheduled' || $appointment['status'] == 'confirmed'): ?>
                        <a href="cancel_appointmet.php?id=<?php echo $appointment['id']; ?>"
                           class="btn btn-danger"
                           onclick="return confirm('Are you sure you want to cancel this appointment?')">
                            <i class="fas fa-ban"></i> Cancel Appointment
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
<?php require 'includes/footer.php'; ?>