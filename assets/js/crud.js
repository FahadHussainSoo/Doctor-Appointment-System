/* ==========================================================================
   MediBook — crud.js (Phase 3A)
   Initializes DataTables (search + pagination) on management tables, and
   wires Bootstrap 5 edit/delete modals by reading data-* attributes already
   rendered by PHP — no new endpoints, no change to what gets submitted.
   ========================================================================== */
document.addEventListener('DOMContentLoaded', function () {

  /* ---------- DataTables: search + pagination on every .mb-datatable ---------- */
  if (window.jQuery && jQuery.fn.DataTable) {
    jQuery('.mb-datatable').each(function () {
      var $t = jQuery(this);
      $t.DataTable({
        pageLength: 10,
        lengthMenu: [5, 10, 25, 50],
        order: [],
        language: {
          search: '',
          searchPlaceholder: 'Search...',
          lengthMenu: 'Show _MENU_',
          info: 'Showing _START_–_END_ of _TOTAL_',
          infoEmpty: 'No records found',
          infoFiltered: '(filtered from _MAX_ total)',
          paginate: { previous: '\u2039', next: '\u203A' },
          emptyTable: 'No records to display.'
        },
        columnDefs: [{ targets: 'no-sort', orderable: false }]
      });
    });
  }

  /* ---------- Edit modals: fill a Bootstrap modal's form from a row's data-* ---------- */
  document.querySelectorAll('[data-edit-trigger]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var modalSel = btn.getAttribute('data-edit-trigger');
      var modalEl = document.querySelector(modalSel);
      if (!modalEl) return;

      // Every data-field-* attribute on the trigger maps to a form element
      // named "field" inside the modal (input, select, or textarea).
      Array.from(btn.attributes).forEach(function (attr) {
        if (attr.name.indexOf('data-field-') !== 0) return;
        var field = attr.name.replace('data-field-', '');
        var el = modalEl.querySelector('[name="' + field + '"]');
        if (!el) return;
        if (el.tagName === 'SELECT') {
          el.value = attr.value;
        } else {
          el.value = attr.value;
        }
      });

      if (window.bootstrap) {
        var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();
      }
    });
  });

  /* ---------- Delete confirm modals ---------- */
  document.querySelectorAll('[data-delete-trigger]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var modalSel = btn.getAttribute('data-delete-trigger');
      var modalEl = document.querySelector(modalSel);
      if (!modalEl) return;
      var href = btn.getAttribute('data-delete-href');
      var confirmLink = modalEl.querySelector('[data-delete-confirm]');
      var nameEl = modalEl.querySelector('[data-delete-name]');
      if (confirmLink && href) confirmLink.setAttribute('href', href);
      if (nameEl) nameEl.textContent = btn.getAttribute('data-delete-label') || 'this record';
      if (window.bootstrap) {
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
      }
    });
  });
});
