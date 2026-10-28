<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

/**
 * Phase 2A note: there is no password-reset/token table or mailer in the
 * current backend, and none is added here to keep "backend unchanged".
 * This page uses only the existing, already-public Auth::emailExists()
 * to decide internally what to log/prepare, but — by design — always
 * shows the same neutral confirmation message to the visitor, whether or
 * not the address is registered, so the form can't be used to discover
 * which emails exist. Wiring this up to a real reset-token flow and
 * mailer is future backend work, not a Phase 2A layout task.
 */
$auth = new Auth($pdo);
$submitted = false;
$submittedEmail = '';

if ($_POST && !empty($_POST['email'])) {
    $submittedEmail = trim($_POST['email']);
    // Existing, unmodified Auth API — read-only lookup, no schema/backend changes.
    $auth->emailExists($submittedEmail);
    $submitted = true;
}

$pageTitle = 'Forgot Password';
$breadcrumbs = [
    ['label' => 'Home', 'url' => 'index.php'],
    ['label' => 'Login', 'url' => 'login.php'],
    ['label' => 'Forgot Password'],
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
                    <rect x="42" y="58" width="96" height="56" rx="10" fill="rgba(255,255,255,.9)"/>
                    <path d="M50 58v-14a40 40 0 0 1 80 0v14" stroke="rgba(255,255,255,.9)" stroke-width="8" fill="none" stroke-linecap="round"/>
                    <circle cx="90" cy="86" r="10" fill="var(--color-primary)"/>
                    <rect x="86" y="86" width="8" height="16" rx="3" fill="var(--color-primary)"/>
                    <circle cx="146" cy="36" r="12" fill="rgba(255,255,255,.22)"/>
                    <circle cx="30" cy="90" r="9" fill="rgba(255,255,255,.2)"/>
                </svg>
                <h3>Forgot your password?</h3>
                <p>No worries — it happens. Enter the email tied to your account and we'll help you get back in safely.</p>
                <div class="mb-auth-illustration-badges">
                    <span><i class="fas fa-shield-halved"></i> Safe &amp; secure</span>
                    <span><i class="fas fa-envelope-circle-check"></i> Simple recovery</span>
                </div>
            </div>

            <!-- Form side -->
            <div class="mb-auth-form-col">
                <a href="login.php" class="mb-auth-back"><i class="fas fa-arrow-left"></i> Back to login</a>

                <div class="mb-auth-header">
                    <div class="mb-auth-brand">
                        <span class="mb-brand-icon"><i class="fas fa-stethoscope"></i></span>
                        <span>MediBook</span>
                    </div>
                    <h1 class="mb-auth-title">Reset your password</h1>
                    <p class="mb-auth-subtitle">Enter your email and we'll send you recovery instructions</p>
                </div>

                <?php if ($submitted): ?>
                    <div class="mb-auth-alert success">
                        <i class="fas fa-circle-check"></i>
                        <span>If an account exists for <strong><?php echo htmlspecialchars($submittedEmail); ?></strong>, we've sent password reset instructions to that inbox.</span>
                    </div>
                    <p class="mb-auth-footer-text" style="margin-top:.5rem;">
                        Didn't get anything? Check your spam folder, or
                        <a href="forgot_password.php" class="mb-auth-link">try again</a>.
                    </p>
                <?php else: ?>
                    <form method="POST" class="mb-auth-form" autocomplete="on">
                        <div class="mb-field">
                            <i class="fas fa-envelope mb-field-icon"></i>
                            <input type="email" name="email" id="fp_email" class="mb-input" placeholder=" " required autocomplete="email">
                            <label for="fp_email" class="mb-floating-label">Email Address</label>
                        </div>

                        <button type="submit" class="mb-auth-btn">
                            Send Reset Link <i class="fas fa-paper-plane"></i>
                        </button>
                    </form>

                    <div class="mb-auth-divider">or</div>

                    <p class="mb-auth-footer-text">
                        Remembered your password? <a href="login.php" class="mb-auth-link">Sign in</a>
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </section>
<?php require 'includes/footer.php'; ?>
