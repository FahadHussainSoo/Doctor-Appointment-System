<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

$auth = new Auth($pdo);
$error = '';
$success = '';

if ($_POST) {
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $phone = $_POST['phone'];
    $date_of_birth = $_POST['date_of_birth'];
    $address = $_POST['address'];
    
    // Validation
    if ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } else {
        if ($auth->register($first_name, $last_name, $email, $password, $phone, $date_of_birth, $address)) {
            $success = "Registration successful! You can now login.";
            header("Location: login.php?success=1");
            exit();
        } else {
            $error = "Email already exists. Please use a different email.";
        }
    }
}
?>
<?php
$pageTitle = 'Register';
$breadcrumbs = [
    ['label' => 'Home', 'url' => 'index.php'],
    ['label' => 'Register'],
];
require 'includes/header.php';
?>
    <section class="mb-auth-section">
        <span class="mb-auth-blob b1"></span>
        <span class="mb-auth-blob b2"></span>
        <span class="mb-auth-blob b3"></span>

        <div class="mb-auth-card mb-auth-card-wide">
            <!-- Illustration side -->
            <div class="mb-auth-illustration">
                <svg class="mb-auth-illustration-art" width="180" height="140" viewBox="0 0 180 140" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <ellipse cx="90" cy="122" rx="70" ry="10" fill="rgba(255,255,255,.14)"/>
                    <circle cx="66" cy="56" r="22" fill="rgba(255,255,255,.9)"/>
                    <path d="M40 112c2-20 12-32 26-32s24 12 26 32" stroke="rgba(255,255,255,.9)" stroke-width="10" stroke-linecap="round" fill="none"/>
                    <circle cx="132" cy="40" r="16" fill="rgba(255,255,255,.25)"/>
                    <circle cx="146" cy="94" r="10" fill="rgba(255,255,255,.2)"/>
                    <path d="M110 70l8 8 14-16" stroke="#fff" stroke-width="5" stroke-linecap="round" stroke-linejoin="round" fill="none" opacity=".9"/>
                </svg>
                <h3>Join MediBook today</h3>
                <p>Create your free account to book appointments in seconds, track your visit history, and get reminders before every checkup.</p>
                <div class="mb-auth-illustration-badges">
                    <span><i class="fas fa-shield-halved"></i> Private &amp; secure</span>
                    <span><i class="fas fa-bolt"></i> Quick setup</span>
                    <span><i class="fas fa-heart-pulse"></i> Free forever</span>
                </div>
            </div>

            <!-- Form side -->
            <div class="mb-auth-form-col">
                <div class="mb-auth-header">
                    <div class="mb-auth-brand">
                        <span class="mb-brand-icon"><i class="fas fa-stethoscope"></i></span>
                        <span>MediBook</span>
                    </div>
                    <h1 class="mb-auth-title">Create your account</h1>
                    <p class="mb-auth-subtitle">Join thousands of patients using MediBook</p>
                </div>

                <?php if ($error): ?>
                    <div class="mb-auth-alert error"><i class="fas fa-circle-exclamation"></i> <span><?php echo $error; ?></span></div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="mb-auth-alert success"><i class="fas fa-circle-check"></i> <span><?php echo $success; ?></span></div>
                <?php endif; ?>

                <form method="POST" class="mb-auth-form" autocomplete="on">
                    <div class="mb-auth-row">
                        <div class="mb-field">
                            <i class="fas fa-user mb-field-icon"></i>
                            <input type="text" name="first_name" id="first_name" class="mb-input" placeholder=" " required autocomplete="given-name">
                            <label for="first_name" class="mb-floating-label">First Name</label>
                        </div>
                        <div class="mb-field">
                            <i class="fas fa-user mb-field-icon"></i>
                            <input type="text" name="last_name" id="last_name" class="mb-input" placeholder=" " required autocomplete="family-name">
                            <label for="last_name" class="mb-floating-label">Last Name</label>
                        </div>
                    </div>

                    <div class="mb-field">
                        <i class="fas fa-envelope mb-field-icon"></i>
                        <input type="email" name="email" id="reg_email" class="mb-input" placeholder=" " required autocomplete="email">
                        <label for="reg_email" class="mb-floating-label">Email Address</label>
                    </div>

                    <div class="mb-auth-row">
                        <div class="mb-field has-toggle">
                            <i class="fas fa-lock mb-field-icon"></i>
                            <input type="password" name="password" id="reg_password" class="mb-input" placeholder=" " required autocomplete="new-password">
                            <label for="reg_password" class="mb-floating-label">Password</label>
                            <button type="button" class="mb-pass-toggle" data-mb-pass-toggle="reg_password" aria-label="Show password">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="mb-field has-toggle">
                            <i class="fas fa-lock mb-field-icon"></i>
                            <input type="password" name="confirm_password" id="confirm_password" class="mb-input" placeholder=" " required autocomplete="new-password">
                            <label for="confirm_password" class="mb-floating-label">Confirm Password</label>
                            <button type="button" class="mb-pass-toggle" data-mb-pass-toggle="confirm_password" aria-label="Show password">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="mb-field">
                        <i class="fas fa-phone mb-field-icon"></i>
                        <input type="tel" name="phone" id="phone" class="mb-input" placeholder=" " autocomplete="tel">
                        <label for="phone" class="mb-floating-label">Phone Number</label>
                    </div>

                    <div class="mb-field mb-field-static">
                        <i class="fas fa-cake-candles mb-field-icon"></i>
                        <input type="date" name="date_of_birth" id="date_of_birth" class="mb-input">
                        <label for="date_of_birth" class="mb-floating-label">Date of Birth</label>
                    </div>

                    <div class="mb-field">
                        <i class="fas fa-location-dot mb-field-icon" style="top: 1.5rem; transform:none;"></i>
                        <textarea name="address" id="address" rows="2" class="mb-textarea" placeholder=" "></textarea>
                        <label for="address" class="mb-floating-label">Address</label>
                    </div>

                    <button type="submit" class="mb-auth-btn">
                        Create Account <i class="fas fa-arrow-right"></i>
                    </button>
                </form>

                <p class="mb-auth-footer-text">
                    Already have an account? <a href="login.php" class="mb-auth-link">Sign in</a>
                </p>
            </div>
        </div>
    </section>
<?php require 'includes/footer.php'; ?>