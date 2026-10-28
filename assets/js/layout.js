/* ==========================================================================
   MediBook — layout.js (Phase 1B)
   Handles: premium navbar scroll state, dropdowns, mobile off-canvas menu,
   dark sidebar toggle (mobile), and active-link highlighting.
   Pure UI/UX behaviour — no backend calls, no data changes.
   ========================================================================== */
document.addEventListener('DOMContentLoaded', function () {

  /* ---------- Navbar scroll shadow ---------- */
  var navbar = document.querySelector('.mb-navbar');
  if (navbar) {
    var onScroll = function () {
      navbar.classList.toggle('scrolled', window.scrollY > 8);
    };
    window.addEventListener('scroll', onScroll);
    onScroll();
  }

  /* ---------- Dropdowns (click + hover-friendly on touch) ---------- */
  document.querySelectorAll('.mb-dropdown').forEach(function (dd) {
    var toggle = dd.querySelector('.mb-dropdown-toggle');
    if (!toggle) return;
    toggle.addEventListener('click', function (e) {
      e.preventDefault();
      var wasOpen = dd.classList.contains('open');
      document.querySelectorAll('.mb-dropdown.open').forEach(function (o) { o.classList.remove('open'); });
      if (!wasOpen) dd.classList.add('open');
    });
  });
  document.addEventListener('click', function (e) {
    if (!e.target.closest('.mb-dropdown')) {
      document.querySelectorAll('.mb-dropdown.open').forEach(function (o) { o.classList.remove('open'); });
    }
  });

  /* ---------- Mobile off-canvas menu (public navbar) ---------- */
  var mobileToggle = document.querySelector('[data-mb-mobile-toggle]');
  var mobileMenu = document.querySelector('.mb-mobile-menu');
  var overlay = document.querySelector('.mb-overlay');
  function closeMobileMenu() {
    if (mobileMenu) mobileMenu.classList.remove('open');
    if (overlay) overlay.classList.remove('open');
    document.body.style.overflow = '';
  }
  if (mobileToggle && mobileMenu) {
    mobileToggle.addEventListener('click', function () {
      mobileMenu.classList.add('open');
      if (overlay) overlay.classList.add('open');
      document.body.style.overflow = 'hidden';
    });
  }
  document.querySelectorAll('[data-mb-mobile-close]').forEach(function (btn) {
    btn.addEventListener('click', closeMobileMenu);
  });
  if (overlay) overlay.addEventListener('click', closeMobileMenu);

  /* ---------- Dark sidebar toggle (mobile, admin/doctor dashboards) ---------- */
  var sidebarToggle = document.querySelector('[data-mb-sidebar-toggle]');
  var sidebar = document.querySelector('.mb-sidebar');
  var sidebarOverlay = document.querySelector('.mb-sidebar-overlay');
  function closeSidebar() {
    if (sidebar) sidebar.classList.remove('open');
    if (sidebarOverlay) sidebarOverlay.classList.remove('open');
    document.body.style.overflow = '';
  }
  if (sidebarToggle && sidebar) {
    sidebarToggle.addEventListener('click', function () {
      sidebar.classList.add('open');
      if (sidebarOverlay) sidebarOverlay.classList.add('open');
      document.body.style.overflow = 'hidden';
    });
  }
  if (sidebarOverlay) sidebarOverlay.addEventListener('click', closeSidebar);

  /* ---------- Close menus with Escape ---------- */
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      closeMobileMenu();
      closeSidebar();
      document.querySelectorAll('.mb-dropdown.open').forEach(function (o) { o.classList.remove('open'); });
    }
  });

  /* ---------- Phase 5: alert banners → auto-dismiss + matching toast ----------
     Purely presentational: reads the .alert already rendered by PHP (its
     text and success/danger/warning/info class), never touches why it was
     shown. data-auto-dismiss was already used in the markup (e.g.
     profile.php) but had no JS behind it until now. */
  var ALERT_ICON = { success: 'success', danger: 'error', warning: 'warning', info: 'info' };
  document.querySelectorAll('.alert').forEach(function (box) {
    var type = ['success', 'danger', 'warning', 'info'].find(function (t) {
      return box.classList.contains('alert-' + t);
    }) || 'info';

    if (window.MediBookToast) {
      var items = box.querySelectorAll('li');
      var msg = items.length
        ? Array.prototype.map.call(items, function (li) { return li.textContent.trim(); }).join(' • ')
        : (box.textContent || '').trim().replace(/\s+/g, ' ');
      if (msg) window.MediBookToast.fire(ALERT_ICON[type], msg.slice(0, 220));
    }

    if (box.hasAttribute('data-auto-dismiss')) {
      setTimeout(function () {
        box.style.transition = 'opacity .35s ease, transform .35s ease, margin .35s ease, padding .35s ease, max-height .35s ease';
        box.style.maxHeight = box.offsetHeight + 'px';
        requestAnimationFrame(function () {
          box.style.opacity = '0';
          box.style.transform = 'translateY(-6px)';
          box.style.maxHeight = '0';
          box.style.marginTop = '0';
          box.style.marginBottom = '0';
          box.style.paddingTop = '0';
          box.style.paddingBottom = '0';
          box.style.overflow = 'hidden';
        });
        setTimeout(function () { box.remove(); }, 400);
      }, 4000);
    }
  });

  /* ---------- Phase 5: lightweight loading state on form submit ----------
     Cosmetic only — disables + spins the submit button so double-clicks
     and slow requests feel handled. The form still submits normally; a
     safety timeout re-enables the button so nothing can get stuck if a
     page keeps the same DOM around (e.g. client-side validation stops it). */
  document.querySelectorAll('form').forEach(function (form) {
    form.addEventListener('submit', function () {
      var btn = form.querySelector('button[type="submit"]:not([disabled])');
      if (!btn) return;
      btn.classList.add('is-loading');
      btn.disabled = true;
      setTimeout(function () {
        btn.classList.remove('is-loading');
        btn.disabled = false;
      }, 8000);
    });
  });
});

/* ---------- Phase 5: global page loader ---------- */
window.addEventListener('load', function () {
  var loader = document.querySelector('[data-mb-page-loader]');
  if (!loader) return;
  loader.classList.add('mb-loader-hide');
  setTimeout(function () { loader.remove(); }, 500);
});

/* ---------- Phase 5: toast helper (SweetAlert2) ----------
   Safe to call even if SweetAlert2 hasn't loaded (e.g. offline CDN) —
   silently no-ops instead of throwing. */
window.MediBookToast = {
  fire: function (icon, message) {
    if (typeof window.Swal === 'undefined') return;
    window.Swal.fire({
      toast: true,
      position: 'top-end',
      icon: icon || 'info',
      title: message,
      showConfirmButton: false,
      timer: 3500,
      timerProgressBar: true,
    });
  },
};
