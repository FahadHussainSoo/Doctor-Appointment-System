/* ==========================================================================
   MediBook — auth.js (Phase 2A)
   Password visibility toggle for the Login / Register / Forgot Password
   split-screen cards. Pure UI behaviour, no backend calls.
   ========================================================================== */
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('[data-mb-pass-toggle]').forEach(function (btn) {
    var targetId = btn.getAttribute('data-mb-pass-toggle');
    var input = document.getElementById(targetId);
    if (!input) return;

    btn.addEventListener('click', function () {
      var isHidden = input.getAttribute('type') === 'password';
      input.setAttribute('type', isHidden ? 'text' : 'password');
      var icon = btn.querySelector('i');
      if (icon) {
        icon.classList.toggle('fa-eye', !isHidden);
        icon.classList.toggle('fa-eye-slash', isHidden);
      }
      btn.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
    });
  });
});
