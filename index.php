<?php
require_once 'includes/config.php';

$pageTitle = 'Home';
$activeNav = 'home';
require 'includes/header.php';
?>

    <!-- Hero Section -->
    <section class="mb-hero">
        <div class="mb-hero-inner fade-in-up">
            <h1>Book Doctor Appointments Online</h1>
            <p>
                Easy, fast, and secure platform to book medical appointments with qualified doctors.
                Get the healthcare you need, when you need it.
            </p>
            <div class="mb-hero-actions">
                <a href="doctors.php" class="btn btn-light btn-lg fw-semibold">
                    <i class="fas fa-magnifying-glass"></i> Find Doctors
                </a>
                <?php if(!isLoggedIn()): ?>
                    <a href="register.php" class="btn btn-outline-light btn-lg fw-semibold">
                        Sign Up Free
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Features -->
    <section class="py-5" style="padding-top:5rem !important; padding-bottom:5rem !important;">
        <div class="max-w-7xl mx-auto px-4">
            <div class="text-center mb-5">
                <h2 class="section-title fs-2">Why choose MediBook</h2>
                <p class="text-muted-soft">Everything you need for a smooth healthcare experience</p>
            </div>
            <div class="grid md:grid-cols-3 gap-8">
                <div class="card card-hover-lift text-center p-4 border-0" style="background:transparent; box-shadow:none;">
                    <div class="feature-icon-circle" style="background: var(--gradient-cool);">
                        <i class="fas fa-clock"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-2">24/7 Booking</h3>
                    <p class="text-gray-600">Book appointments anytime from anywhere</p>
                </div>
                <div class="card card-hover-lift text-center p-4 border-0" style="background:transparent; box-shadow:none;">
                    <div class="feature-icon-circle" style="background: linear-gradient(90deg, var(--color-success), #34D399);">
                        <i class="fas fa-user-md"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-2">Qualified Doctors</h3>
                    <p class="text-gray-600">Experienced and certified healthcare professionals</p>
                </div>
                <div class="card card-hover-lift text-center p-4 border-0" style="background:transparent; box-shadow:none;">
                    <div class="feature-icon-circle" style="background: linear-gradient(90deg, var(--color-purple), var(--color-pink));">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-2">Secure &amp; Private</h3>
                    <p class="text-gray-600">Your health information is protected</p>
                </div>
            </div>
        </div>
    </section>

<?php require 'includes/footer.php'; ?>