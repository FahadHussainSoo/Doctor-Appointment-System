<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

$auth = new Auth($pdo);
$error = '';

if ($_POST) {
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    if ($auth->login($email, $password)) {
        if (isAdmin()) {
            header("Location: admin/index.php");
        } elseif (isDoctor()) {
            header("Location: doctor/index.php");
        } else {
            header("Location: dashboard.php");
        }
        exit();
    }
}
?>
<?php
$pageTitle = 'Login';
$breadcrumbs = [
    ['label' => 'Home', 'url' => 'index.php'],
    ['label' => 'Login'],
];
require 'includes/header.php';
?>
    <section class="mb-auth-section">
        <span class="mb-auth-blob b1"></span>
        <span class="mb-auth-blob b2"></span>
        <span class="mb-auth-blob b3"></span>

        <div class="mb-auth-card">
            <!-- Illustration side -->
            <div class="mb-auth-illustration">
                <svg class="mb-auth-illustration-art" width="180" height="140" viewBox="0 0 180 140" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <ellipse cx="90" cy="122" rx="70" ry="10" fill="rgba(255,255,255,.14)"/>
                    <rect x="34" y="34" width="112" height="76" rx="14" fill="rgba(255,255,255,.16)"/>
                    <rect x="50" y="50" width="80" height="44" rx="8" fill="rgba(255,255,255,.22)"/>
                    <circle cx="90" cy="72" r="20" fill="#fff" fill-opacity=".9"/>
                    <path d="M82 72a8 8 0 0 1 16 0v3a8 8 0 0 1-16 0z" fill="var(--color-primary)"/>
                    <path d="M90 60v-6M84 57h12" stroke="var(--color-primary)" stroke-width="2.5" stroke-linecap="round"/>
                    <circle cx="140" cy="40" r="14" fill="rgba(255,255,255,.25)"/>
                    <circle cx="34" cy="96" r="10" fill="rgba(255,255,255,.2)"/>
                </svg>
                <h3>Your health, one click away</h3>
                <p>Sign in to manage appointments, message your care team, and stay on top of every visit — securely and in one place.</p>
                <div class="mb-auth-illustration-badges">
                    <span><i class="fas fa-lock"></i> Secure login</span>
                    <span><i class="fas fa-user-md"></i> Verified doctors</span>
                    <span><i class="fas fa-calendar-check"></i> Instant booking</span>
                </div>
            </div>

            <!-- Form side -->
            <div class="mb-auth-form-col">
                <div class="mb-auth-header">
                    <div class="mb-auth-brand">
                        <span class="mb-brand-icon"><i class="fas fa-stethoscope"></i></span>
                        <span>MediBook</span>
                    </div>
                    <h1 class="mb-auth-title">Welcome back</h1>
                    <p class="mb-auth-subtitle">Sign in to continue to your account</p>
                </div>

                <?php if ($error): ?>
                    <div class="mb-auth-alert error"><i class="fas fa-circle-exclamation"></i> <span><?php echo $error; ?></span></div>
                <?php endif; ?>

                <?php if (isset($_GET['success'])): ?>
                    <div class="mb-auth-alert success"><i class="fas fa-circle-check"></i> <span>Registration successful! You can now sign in.</span></div>
                <?php endif; ?>

                <form method="POST" class="mb-auth-form" autocomplete="on">
                    <div class="mb-field">
                        <i class="fas fa-envelope mb-field-icon"></i>
                        <input type="email" name="email" id="email" class="mb-input" placeholder=" " required autocomplete="email">
                        <label for="email" class="mb-floating-label">Email Address</label>
                    </div>

                    <div class="mb-field has-toggle">
                        <i class="fas fa-lock mb-field-icon"></i>
                        <input type="password" name="password" id="password" class="mb-input" placeholder=" " required autocomplete="current-password">
                        <label for="password" class="mb-floating-label">Password</label>
                        <button type="button" class="mb-pass-toggle" data-mb-pass-toggle="password" aria-label="Show password">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>

                    <div class="mb-auth-meta-row">
                        <label class="mb-auth-remember">
                            <input type="checkbox" name="remember"> Remember me
                        </label>
                        <a href="forgot_password.php" class="mb-auth-link">Forgot password?</a>
                    </div>

                    <button type="submit" class="mb-auth-btn">
                        Sign In <i class="fas fa-arrow-right"></i>
                    </button>
                </form>

                <p class="mb-auth-footer-text">
                    Don't have an account? <a href="register.php" class="mb-auth-link">Create one</a>
                </p>
            </div>
        </div>
    </section>
<?php require 'includes/footer.php'; ?>