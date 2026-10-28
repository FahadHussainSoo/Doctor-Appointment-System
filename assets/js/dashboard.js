/* ==========================================================================
   MediBook — dashboard.js (Phase 2B)
   Animates progress bars and bar-chart columns into view, and renders the
   mini calendar widget from a data-appointments JSON attribute. Pure
   presentation / rendering — reads data already output by PHP, no calls
   back to the server.
   ========================================================================== */
document.addEventListener('DOMContentLoaded', function () {

  /* ---------- Animate progress bars to their target width ---------- */
  document.querySelectorAll('.db-progress-fill[data-value]').forEach(function (bar) {
    var val = Math.max(0, Math.min(100, parseFloat(bar.getAttribute('data-value')) || 0));
    requestAnimationFrame(function () {
      setTimeout(function () { bar.style.width = val + '%'; }, 120);
    });
  });

  /* ---------- Animate bar-chart columns to their target height ---------- */
  document.querySelectorAll('.db-bar-fill[data-height]').forEach(function (bar) {
    var val = Math.max(0, Math.min(100, parseFloat(bar.getAttribute('data-height')) || 0));
    requestAnimationFrame(function () {
      setTimeout(function () { bar.style.height = val + '%'; }, 120);
    });
  });

  /* ---------- Mini calendar widget ---------- */
  document.querySelectorAll('[data-mb-calendar]').forEach(function (el) {
    var appts = {};
    try { (JSON.parse(el.getAttribute('data-appointments') || '[]')).forEach(function (d) { appts[d] = true; }); }
    catch (e) { /* ignore malformed data */ }

    var now = new Date();
    var year = now.getFullYear(), month = now.getMonth();
    var todayStr = now.toISOString().slice(0, 10);

    var monthLabel = el.querySelector('[data-cal-month]');
    if (monthLabel) {
      monthLabel.textContent = now.toLocaleString(undefined, { month: 'long', year: 'numeric' });
    }

    var grid = el.querySelector('[data-cal-grid]');
    if (!grid) return;

    var dows = ['S', 'M', 'T', 'W', 'T', 'F', 'S'];
    var html = dows.map(function (d) { return '<div class="dow">' + d + '</div>'; }).join('');

    var firstDay = new Date(year, month, 1).getDay();
    var daysInMonth = new Date(year, month + 1, 0).getDate();
    var daysInPrevMonth = new Date(year, month, 0).getDate();

    for (var i = firstDay - 1; i >= 0; i--) {
      html += '<div class="db-cal-day other-month">' + (daysInPrevMonth - i) + '</div>';
    }
    for (var d = 1; d <= daysInMonth; d++) {
      var dateStr = year + '-' + String(month + 1).padStart(2, '0') + '-' + String(d).padStart(2, '0');
      var classes = ['db-cal-day'];
      if (dateStr === todayStr) classes.push('today');
      if (appts[dateStr]) classes.push('has-appt');
      html += '<div class="' + classes.join(' ') + '">' + d + '</div>';
    }
    var totalCells = firstDay + daysInMonth;
    var trailing = (7 - (totalCells % 7)) % 7;
    for (var n = 1; n <= trailing; n++) {
      html += '<div class="db-cal-day other-month">' + n + '</div>';
    }

    grid.innerHTML = html;
  });
});
