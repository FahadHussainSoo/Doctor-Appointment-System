<?php
require_once 'includes/config.php';

if (!isLoggedIn() || isAdmin()) {
    header("Location: login.php");
    exit();
}

$doctor_id = $_GET['doctor_id'] ?? '';
$error = '';
$success = '';

// Get doctor details
if (empty($doctor_id)) {
    header("Location: doctors.php");
    exit();
}

$stmt = $pdo->prepare("
    SELECT d.*, u.first_name, u.last_name, u.email, s.name as specialty_name
    FROM doctors d
    JOIN users u ON d.user_id = u.id
    JOIN specialties s ON d.specialty_id = s.id
    WHERE d.id = ? AND d.status = 'active'
");
$stmt->execute([$doctor_id]);
$doctor = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$doctor) {
    header("Location: doctors.php");
    exit();
}

// Handle appointment booking
if ($_POST) {
    $appointment_date = $_POST['appointment_date'];
    $appointment_time = $_POST['appointment_time'];
    $symptoms = $_POST['symptoms'];
    $notes = $_POST['notes'];
    
    // Check if the time slot is available
    $check_stmt = $pdo->prepare("
        SELECT id FROM appointments 
        WHERE doctor_id = ? AND appointment_date = ? AND appointment_time = ? 
        AND status IN ('scheduled', 'confirmed')
    ");
    $check_stmt->execute([$doctor_id, $appointment_date, $appointment_time]);
    
    if ($check_stmt->rowCount() > 0) {
        $error = "This time slot is already booked. Please choose another time.";
    } else {
        // Create appointment
        $stmt = $pdo->prepare("
            INSERT INTO appointments (patient_id, doctor_id, appointment_date, appointment_time, symptoms, notes) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        if ($stmt->execute([$_SESSION['user_id'], $doctor_id, $appointment_date, $appointment_time, $symptoms, $notes])) {
            $success = "Appointment booked successfully!";
            header("Location: my_appointments.php?success=1");
            exit();
        } else {
            $error = "Failed to book appointment. Please try again.";
        }
    }
}

// Generate available time slots
$available_times = [];
$start = strtotime($doctor['available_time_start']);
$end = strtotime($doctor['available_time_end']);

while ($start < $end) {
    $available_times[] = date('H:i:s', $start);
    $start += 1800; // 30 minutes interval
}
?>
<?php
$pageTitle = 'Book Appointment';
$activeNav = 'doctors';
$breadcrumbs = [
    ['label' => 'Home', 'url' => 'index.php'],
    ['label' => 'Find Doctors', 'url' => 'doctors.php'],
    ['label' => 'Book Appointment'],
];
require 'includes/header.php';
?>

    <div class="max-w-4xl mx-auto px-4 py-8">
        <div class="card card-hover-lift fade-in-up" style="border-radius:var(--radius-xl); padding:2rem;">
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Book Appointment</h1>
            <p class="text-gray-600 mb-4">Schedule your consultation with the doctor</p>

            <!-- Doctor Info -->
            <div class="surface-panel" style="background:var(--color-primary-tint); border:none; padding:1.5rem; margin-bottom:1.75rem;">
                <div class="flex items-center space-x-4">
                    <div class="avatar-circle-lg" style="background:var(--gradient-primary); display:flex; align-items:center; justify-content:center;">
                        <i class="fas fa-user-md" style="color:#fff; font-size:1.4rem;"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-semibold text-gray-800">
                            Dr. <?php echo htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name']); ?>
                        </h3>
                        <p class="font-medium" style="color:var(--color-primary);"><?php echo htmlspecialchars($doctor['specialty_name']); ?></p>
                        <p class="text-gray-600">Consultation Fee: $<?php echo htmlspecialchars($doctor['consultation_fee']); ?></p>
                        <p class="text-gray-600 text-sm">Available: <?php echo htmlspecialchars($doctor['available_days']); ?> from
                            <?php echo date('g:i A', strtotime($doctor['available_time_start'])); ?> to
                            <?php echo date('g:i A', strtotime($doctor['available_time_end'])); ?>
                        </p>
                    </div>
                </div>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger mb-4"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-4">
                    <label class="form-label">Appointment Date</label>
                    <input type="date" name="appointment_date" required
                           min="<?php echo date('Y-m-d'); ?>"
                           class="form-control" style="max-width:280px;">
                </div>

                <div class="mb-4">
                    <label class="form-label">Appointment Time <span class="text-gray-500" style="font-weight:400;">— pick a slot</span></label>
                    <?php if (empty($available_times)): ?>
                        <div class="empty-state" style="padding:1.5rem;"><i class="fas fa-clock"></i><p class="mb-0">No time slots configured for this doctor.</p></div>
                    <?php else: ?>
                        <div class="slot-grid" role="radiogroup" aria-label="Appointment time">
                            <?php foreach ($available_times as $i => $time): ?>
                                <label class="slot-pill">
                                    <input type="radio" name="appointment_time" value="<?php echo htmlspecialchars($time); ?>" required <?php echo isset($_POST['appointment_time']) && $_POST['appointment_time'] === $time ? 'checked' : ''; ?>>
                                    <span><i class="fas fa-clock fa-xs"></i> <?php echo date('g:i A', strtotime($time)); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="mb-4">
                    <label class="form-label">Symptoms / Reason for Visit</label>
                    <textarea name="symptoms" rows="4" required
                              placeholder="Please describe your symptoms or reason for the appointment..."
                              class="form-control"></textarea>
                </div>

                <div class="mb-4">
                    <label class="form-label">Additional Notes (Optional)</label>
                    <textarea name="notes" rows="3"
                              placeholder="Any additional information you'd like to share..."
                              class="form-control"></textarea>
                </div>

                <div class="alert alert-warning mb-4" style="border-left-color: var(--color-warning);">
                    <div class="flex items-start space-x-3">
                        <i class="fas fa-info-circle mt-1"></i>
                        <div>
                            <h4 class="font-semibold" style="margin-bottom:.25rem;">Important Information</h4>
                            <p class="text-sm mb-0">
                                • Please arrive 15 minutes before your scheduled appointment time<br>
                                • Bring your ID and insurance card if applicable<br>
                                • Cancellations must be made at least 24 hours in advance<br>
                                • Consultation fee: $<?php echo htmlspecialchars($doctor['consultation_fee']); ?>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="flex space-x-4 pt-2" style="display:flex; gap:1rem; flex-wrap:wrap;">
                    <a href="doctors.php" class="btn btn-outline-primary" style="flex:1; min-width:140px; text-align:center;">
                        Cancel
                    </a>
                    <button type="submit" class="btn btn-primary" style="flex:1; min-width:140px;">
                        <i class="fas fa-calendar-check"></i> Confirm Booking
                    </button>
                </div>
            </form>
        </div>
    </div>
<?php require 'includes/footer.php'; ?>