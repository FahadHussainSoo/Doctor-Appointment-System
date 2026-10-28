<?php
/**
 * Phase 1B — Shared footer + bottom scripts.
 * Include near the end of every page, right before </body></html>.
 *
 * Set $basePath before including from a subfolder (admin/, doctor/) so
 * links and asset paths resolve correctly, e.g. $basePath = '../';
 * Root-level pages can omit it (defaults to '').
 */
$basePath = $basePath ?? '';
?>
    <!-- ============ Footer ============ -->
    <footer class="mb-footer">
        <div class="mb-footer-inner">
            <div>
                <div class="mb-footer-brand">
                    <span class="mb-brand-icon"><i class="fas fa-stethoscope"></i></span>
                    <span>MediBook</span>
                </div>
                <p class="mb-footer-about">
                    Easy, fast, and secure platform to book medical appointments with qualified doctors —
                    the healthcare you need, whenever you need it.
                </p>
                <div class="mb-footer-social">
                    <a href="https://facebook.com" target="_blank" rel="noopener" class="mb-social-icon facebook" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                    <a href="https://instagram.com" target="_blank" rel="noopener" class="mb-social-icon instagram" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                    <a href="https://linkedin.com" target="_blank" rel="noopener" class="mb-social-icon linkedin" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                    <a href="https://github.com" target="_blank" rel="noopener" class="mb-social-icon github" aria-label="GitHub"><i class="fab fa-github"></i></a>
                </div>
            </div>

            <div>
                <h4>Quick Links</h4>
                <ul>
                    <li><a href="<?php echo $basePath; ?>index.php"><i class="fas fa-chevron-right fa-xs"></i> Home</a></li>
                    <li><a href="<?php echo $basePath; ?>doctors.php"><i class="fas fa-chevron-right fa-xs"></i> Find Doctors</a></li>
                    <li><a href="<?php echo $basePath; ?>login.php"><i class="fas fa-chevron-right fa-xs"></i> Login</a></li>
                    <li><a href="<?php echo $basePath; ?>register.php"><i class="fas fa-chevron-right fa-xs"></i> Sign Up</a></li>
                </ul>
            </div>

            <div>
                <h4>Patients</h4>
                <ul>
                    <li><a href="<?php echo $basePath; ?>my_appointments.php"><i class="fas fa-chevron-right fa-xs"></i> My Appointments</a></li>
                    <li><a href="<?php echo $basePath; ?>doctors.php"><i class="fas fa-chevron-right fa-xs"></i> Book Appointment</a></li>
                    <li><a href="<?php echo $basePath; ?>register.php"><i class="fas fa-chevron-right fa-xs"></i> Create Account</a></li>
                </ul>
            </div>

            <div>
                <h4>Contact</h4>
                <ul>
                    <li><i class="fas fa-phone-alt fa-xs"></i> +1 (800) 555-0192</li>
                    <li><i class="fas fa-envelope fa-xs"></i> support@medibook.com</li>
                    <li><i class="fas fa-location-dot fa-xs"></i> Mon - Sat: 8:00am - 8:00pm</li>
                </ul>
            </div>
        </div>
        <div class="mb-footer-bottom">
            &copy; 2026 All Rights Reserved | Developed By: <a href="#">Fahad Hussain</a>
        </div>
    </footer>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
    <!-- SweetAlert2 (Phase 5 — toast notifications) -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="<?php echo $basePath; ?>assets/js/layout.js"></script>
    <script src="<?php echo $basePath; ?>assets/js/auth.js"></script>
    <script src="<?php echo $basePath; ?>assets/js/dashboard.js"></script>
    <script src="<?php echo $basePath; ?>assets/js/crud.js"></script>
    <!-- MediBook Profile & Settings (Phase 4) -->
    <script src="<?php echo $basePath; ?>assets/js/profile.js"></script>
</body>
</html>
