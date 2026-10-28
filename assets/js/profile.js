/* ==========================================================================
   MediBook — profile.js (Phase 4)
   Profile & Settings redesign: tab switching, avatar upload preview +
   drag/drop, password strength meter, and the dark/light theme toggle.
   Pure presentation / client-side UX — no calls back to the server except
   the normal <form> submits already wired up in the PHP pages.
   ========================================================================== */

/* ---------- Theme toggle: applied ASAP so there's no flash of the wrong
   theme. Safe to run before DOMContentLoaded since it only touches <html>. ---------- */
(function () {
  try {
    var saved = localStorage.getItem('mb-theme');
    if (saved === 'dark') {
      document.documentElement.setAttribute('data-theme', 'dark');
    }
  } catch (e) { /* localStorage unavailable — default to light */ }
})();

document.addEventListener('DOMContentLoaded', function () {

  /* ---------- Theme toggle button(s) ---------- */
  document.querySelectorAll('[data-mb-theme-toggle]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var isDark = document.documentElement.getAttribute('data-theme') === 'dark';
      if (isDark) {
        document.documentElement.removeAttribute('data-theme');
        try { localStorage.setItem('mb-theme', 'light'); } catch (e) {}
      } else {
        document.documentElement.setAttribute('data-theme', 'dark');
        try { localStorage.setItem('mb-theme', 'dark'); } catch (e) {}
      }
    });
  });

  /* ---------- Tabs ---------- */
  document.querySelectorAll('[data-mb-tabs]').forEach(function (group) {
    var groupId = group.getAttribute('data-mb-tabs');
    var buttons = group.querySelectorAll('.mb-tab-btn');
    var panels = document.querySelectorAll('[data-mb-tab-panel-group="' + groupId + '"]');

    buttons.forEach(function (btn) {
      btn.addEventListener('click', function () {
        var target = btn.getAttribute('data-tab-target');

        buttons.forEach(function (b) { b.classList.remove('active'); });
        btn.classList.add('active');

        panels.forEach(function (p) {
          p.classList.toggle('active', p.getAttribute('data-mb-tab-panel') === target);
        });

        // Remember the last-open tab per group across reloads (nice on Settings).
        try { sessionStorage.setItem('mb-tab-' + groupId, target); } catch (e) {}
      });
    });

    // Restore last-open tab, or honor a #hash on first load (e.g. profile.php#security).
    var restore = null;
    try { restore = sessionStorage.getItem('mb-tab-' + groupId); } catch (e) {}
    var hashTarget = window.location.hash ? window.location.hash.slice(1) : null;
    var wanted = hashTarget || restore;
    if (wanted) {
      var match = group.querySelector('.mb-tab-btn[data-tab-target="' + wanted + '"]');
      if (match) match.click();
    }
  });

  /* ---------- Avatar / file upload dropzones ---------- */
  document.querySelectorAll('[data-mb-dropzone]').forEach(function (zone) {
    var input = zone.querySelector('input[type="file"]');
    var previewImg = zone.parentElement.querySelector('[data-mb-avatar-preview]');
    var filenameEl = zone.querySelector('[data-mb-filename]');

    if (!input) return;

    zone.addEventListener('click', function () { input.click(); });

    ['dragenter', 'dragover'].forEach(function (evt) {
      zone.addEventListener(evt, function (e) {
        e.preventDefault(); e.stopPropagation();
        zone.classList.add('is-dragover');
      });
    });
    ['dragleave', 'drop'].forEach(function (evt) {
      zone.addEventListener(evt, function (e) {
        e.preventDefault(); e.stopPropagation();
        zone.classList.remove('is-dragover');
      });
    });
    zone.addEventListener('drop', function (e) {
      var files = e.dataTransfer.files;
      if (files && files.length) {
        input.files = files;
        handleFile(files[0]);
      }
    });
    input.addEventListener('change', function () {
      if (input.files && input.files[0]) handleFile(input.files[0]);
    });

    function handleFile(file) {
      if (filenameEl) filenameEl.textContent = file.name;
      if (previewImg && file.type.startsWith('image/')) {
        var reader = new FileReader();
        reader.onload = function (e) {
          if (previewImg.tagName === 'IMG') {
            previewImg.src = e.target.result;
          } else {
            // Fallback: replace an initials-avatar <div> with a live preview <img>.
            var img = document.createElement('img');
            img.src = e.target.result;
            img.className = previewImg.className;
            img.setAttribute('data-mb-avatar-preview', '');
            previewImg.replaceWith(img);
            previewImg = img;
          }
        };
        reader.readAsDataURL(file);
      }
    }
  });

  /* ---------- Password strength meter ---------- */
  document.querySelectorAll('[data-mb-pw-strength]').forEach(function (input) {
    var fill = document.querySelector(input.getAttribute('data-mb-pw-strength'));
    var label = fill ? fill.parentElement.querySelector('.pw-strength-label') : null;
    if (!fill) return;

    input.addEventListener('input', function () {
      var val = input.value;
      var score = 0;
      if (val.length >= 8) score++;
      if (val.length >= 12) score++;
      if (/[A-Z]/.test(val) && /[a-z]/.test(val)) score++;
      if (/\d/.test(val)) score++;
      if (/[^A-Za-z0-9]/.test(val)) score++;

      var pct = val.length === 0 ? 0 : Math.min(100, (score / 5) * 100);
      var colors = ['#EF4444', '#EF4444', '#F59E0B', '#F59E0B', '#10B981', '#10B981'];
      var labels = ['Too short', 'Weak', 'Fair', 'Good', 'Strong', 'Strong'];

      fill.style.width = pct + '%';
      fill.style.background = colors[score];
      if (label) label.textContent = val.length === 0 ? 'Enter a new password' : labels[score];
    });
  });

  /* ---------- Confirm-password live match check ---------- */
  document.querySelectorAll('[data-mb-confirm-target]').forEach(function (confirmInput) {
    var target = document.querySelector(confirmInput.getAttribute('data-mb-confirm-target'));
    if (!target) return;
    function check() {
      if (!confirmInput.value) { confirmInput.setCustomValidity(''); return; }
      confirmInput.setCustomValidity(confirmInput.value === target.value ? '' : "Passwords don't match");
    }
    confirmInput.addEventListener('input', check);
    target.addEventListener('input', check);
  });
});
