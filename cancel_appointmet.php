<?php
require_once 'includes/config.php';

if (!isLoggedIn() || isAdmin()) {
    header("Location: login.php");
    exit();
}

$appointment_id = $_GET['id'] ?? '';

if (empty($appointment_id)) {
    header("Location: my_appointments.php");
    exit();
}

// Verify the appointment belongs to the user and can be cancelled
$stmt = $pdo->prepare("
    SELECT id FROM appointments 
    WHERE id = ? AND patient_id = ? AND status IN ('scheduled', 'confirmed')
");
$stmt->execute([$appointment_id, $_SESSION['user_id']]);

if ($stmt->rowCount() > 0) {
    // Cancel the appointment
    $update_stmt = $pdo->prepare("UPDATE appointments SET status = 'cancelled' WHERE id = ?");
    $update_stmt->execute([$appointment_id]);
    
    header("Location: my_appointments.php?cancelled=1");
    exit();
} else {
    header("Location: my_appointments.php");
    exit();
}
?>