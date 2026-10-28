<?php
require_once 'includes/config.php';

// Get all active doctors
$doctors = $pdo->query("
    SELECT d.*, u.first_name, u.last_name, u.email, s.name as specialty_name
    FROM doctors d
    JOIN users u ON d.user_id = u.id
    JOIN specialties s ON d.specialty_id = s.id
    WHERE d.status = 'active'
    ORDER BY u.first_name, u.last_name
")->fetchAll(PDO::FETCH_ASSOC);
?>
<?php
$pageTitle = 'Find Doctors';
$activeNav = 'doctors';
$breadcrumbs = [
    ['label' => 'Home', 'url' => 'index.php'],
    ['label' => 'Find Doctors'],
];
require 'includes/header.php';
?>

    <div class="max-w-7xl mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-2">Our Doctors</h1>
        <p class="text-muted-soft mb-4">Find and book the right specialist for your needs</p>

        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($doctors as $i => $doctor): ?>
                <div class="card card-hover-lift mb-doctor-card p-4" style="animation-delay: <?php echo min($i, 5) * 0.06; ?>s;">
                    <div class="card-body">
                        <div class="flex items-center space-x-4 mb-4">
                            <div class="avatar-circle-lg" style="background: var(--gradient-cool); color:#fff; display:flex; align-items:center; justify-content:center;">
                                <i class="fas fa-user-md fa-lg"></i>
                            </div>
                            <div>
                                <h3 class="text-xl font-semibold">
                                    Dr. <?php echo htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name']); ?>
                                </h3>
                                <p style="color: var(--color-primary); font-weight:600;"><?php echo htmlspecialchars($doctor['specialty_name']); ?></p>
                            </div>
                        </div>

                        <div class="space-y-2 mb-4">
                            <div class="flex items-center text-sm text-gray-600">
                                <i class="fas fa-graduation-cap mr-2"></i>
                                <span><?php echo htmlspecialchars($doctor['qualification']); ?></span>
                            </div>
                            <div class="flex items-center text-sm text-gray-600">
                                <i class="fas fa-briefcase mr-2"></i>
                                <span><?php echo htmlspecialchars($doctor['experience_years']); ?> years experience</span>
                            </div>
                            <div class="flex items-center text-sm text-gray-600">
                                <i class="fas fa-calendar mr-2"></i>
                                <span>Available: <?php echo htmlspecialchars($doctor['available_days']); ?></span>
                            </div>
                        </div>

                        <p class="text-gray-600 text-sm mb-4">
                            <?php echo htmlspecialchars($doctor['bio']); ?>
                        </p>

                        <div class="mb-doctor-fee">
                            <div>
                                <span class="text-2xl font-bold text-gray-800">$<?php echo htmlspecialchars($doctor['consultation_fee']); ?></span>
                                <span class="text-gray-600 text-sm">/consultation</span>
                            </div>

                        <?php if(isLoggedIn() && isPatient()): ?>
                            <a href="book_appointment.php?doctor_id=<?php echo $doctor['id']; ?>"
                               class="btn btn-primary btn-sm">
                                Book Appointment
                            </a>
                        <?php elseif(!isLoggedIn()): ?>
                            <a href="login.php"
                               class="btn btn-outline-primary btn-sm">
                                Login to Book
                            </a>
                        <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php if (empty($doctors)): ?>
                <div class="empty-state" style="grid-column: 1 / -1;">
                    <i class="fas fa-user-doctor"></i>
                    <p>No doctors available at the moment.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php require 'includes/footer.php'; ?>