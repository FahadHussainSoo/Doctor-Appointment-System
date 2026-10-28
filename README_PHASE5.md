# Phase 5 — Global Polish Pass

Scope, per the brief: add loading/animation/toast infrastructure project-wide,
fix leftover CSS/consistency issues, and unify the footer. No backend logic,
authentication, database structure, or SQL queries were touched — every
change below is CSS, JS, or presentation-only HTML inside existing PHP files.

## Why this pass looked different from earlier phases

The project (through Phase 4) was already unusually disciplined: a real
design-token system (`variables.css`), a component library (`custom.css`,
`crud.css`, `dashboard.css`, `profile.css`), a shared header/footer/sidebar
template, and most pages already used it. So this phase is **not** a
top-to-bottom rewrite — it's:

1. New shared infrastructure (loading spinner, toasts, entrance/hover
   animation, smooth scroll) added once to the shared CSS/JS/include files,
   which every page picks up automatically for free.
2. A handful of pages that had never been migrated off raw Tailwind/inline
   styling, brought up to the same bar as the rest of the app.
3. Small consistency bugs (a table missing the shared badge styling, six
   flash-message boxes using ad-hoc Tailwind instead of the shared `.alert`
   component) found by grepping the whole codebase and fixed one by one.

## What's new (shared, every page gets these automatically)

- **Loading spinner** — `.mb-page-loader` overlay (added to `includes/header.php`
  and `includes/dash_header.php`), hidden by `layout.js` once the page
  finishes loading.
- **Toast notifications** — SweetAlert2 added (`includes/footer.php`).
  `layout.js` now mirrors every `.alert` box already rendered by PHP as a
  toast (top-right, auto-dismissing) — it reads the alert's existing
  success/danger/warning/info class and text, so no PHP changed.
- **Auto-dismiss** — `data-auto-dismiss` was already used in `profile.php`'s
  markup but had no JS behind it. It's wired up now (fades out after 4s).
- **Professional loading states** — every form's submit button now shows a
  spinner and disables itself on submit (`.btn.is-loading`), with an 8s
  safety timeout so nothing can get permanently stuck.
- **Smooth scrolling**, a **custom scrollbar** (was already there from
  Phase 1A), **page transition** (a quick fade-in on every page load).
- **Card/panel hover + entrance animation**, extended to `.db-panel` and
  `.crud-table-card` — guarded with `:not(.db-fade-in)` so pages that
  already had their own staggered dashboard animation, or the login/register
  page's own `mbCardIn` animation, were left alone rather than double-animated.
- **Smooth dropdown animations** — already implemented well pre-Phase 5
  (opacity/transform/visibility transitions in `layout.css`); left as-is.
- Safety nets: `img { max-width:100%; height:auto; }` so an oversized image
  can never cause horizontal overflow; verified no `<img>` tag in the
  project can point at a missing file (the three avatar `<img>` tags in the
  profile pages are all guarded by a PHP `if ($avatar_url)` with an
  initials-avatar fallback).

## Pages brought up to the shared design system

- **`index.php`** (homepage) and **`doctors.php`** were still 100% flat
  Tailwind from before Phase 1A's Bootstrap migration started — the two
  most-visited public pages hadn't been touched yet. Rebuilt with a new
  `.mb-hero` section and `.card`/`.btn`/`.avatar-circle` components (all
  reused from the existing system — two small new CSS components,
  `.mb-hero` and `.mb-doctor-card`, were added to `custom.css` for this).
  Query logic and every `isLoggedIn()`/`isPatient()` conditional are
  untouched.
- **`admin/index.php`**'s "Recent Appointments" table was the one table in
  the project not using the shared `crud-badge` status-color system or
  `crud-table-scroll` wrapper — fixed, and output escaping added
  (`htmlspecialchars`) since it was missing there.
- **Six flash-message boxes** across `admin/departments.php`,
  `admin/doctors.php`, `admin/users.php`, and `admin/appointments.php` were
  raw `bg-green-100 border...` Tailwind instead of the shared `.alert`
  component used everywhere else — fixed, which also means they now pick up
  the new toast + auto-dismiss behavior for free.

## What I checked and found already correct (left alone)

- The footer (`includes/footer.php`) was already a single shared include
  used by every page (via `header.php` and `dash_footer.php`), already had
  the exact required copyright line, all four social icons, and hover
  animations. No rebuild was needed there — just adding the SweetAlert2
  script tag alongside the existing scripts.
- Mobile navigation (off-canvas menu + overlay), the dashboard sidebar
  toggle, and dropdown animations were already solid.
- Every other data table in the project already had a responsive wrapper
  (`crud-table-scroll` / `table-responsive`).
- Bootstrap 5, jQuery, DataTables, and the AJAX/CRUD flows are untouched —
  nothing here changes what any button or form actually *does*.

## Known trade-off, intentionally not touched

Tailwind's CDN build is still loaded alongside Bootstrap 5 on every page
(this predates Phase 5 — see `custom.css`'s own top-of-file comment from
Phase 1A). A lot of page markup still uses Tailwind utility classes for
layout. Removing Tailwind entirely would be a real "optimize CSS/JS" win,
but it would require rewriting the layout markup of most remaining pages —
that's a rebuild, not a polish pass, and risks breaking pages that currently
work. I left it in place rather than gamble with "do not break existing
functionality." If you want that cleanup as its own phase, I'd scope it
page-by-page rather than as a blanket removal.

## Testing instructions

I don't have a PHP interpreter or MySQL in this environment, so — unlike
Phase 4's README, which describes tests actually run against a live
database — everything below is **checked statically** (PHP open/close tag
balance, CSS brace balance, grepped for leftover raw styling, verified
class names referenced in HTML match classes defined in CSS). Please
smoke-test on your XAMPP setup before treating this as final:

1. Copy the project into `htdocs/` as usual; no database changes are needed
   for this phase (Phase 4's `db_patch_phase4.sql` is unrelated and already
   optional-if-skipped).
2. Load `index.php` — you should see the page-load spinner briefly, then a
   gradient hero section (previously a flat blue block).
3. Load `doctors.php` — cards should be hoverable (lift + shadow), buttons
   should be the same blue/gradient primary style as the rest of the site.
4. Trigger any flash message (e.g. log in with a wrong password, add a
   department in `admin/departments.php`, update an appointment status in
   `admin/appointments.php`) — you should see the inline banner **and** a
   toast notification top-right, and on `profile.php`'s success banner
   specifically, watch it fade out on its own after ~4 seconds.
5. Resize the browser down to a phone width on a few pages (`index.php`,
   `admin/index.php`, `doctor/schedule.php`) — check the off-canvas menu /
   sidebar toggle still work and nothing overflows horizontally.
6. Submit any form (login, add doctor, filter appointments) — the submit
   button should briefly show a spinner.
7. If your network blocks the SweetAlert2 CDN, toasts silently won't
   appear — inline `.alert` banners still render normally either way, so
   nothing is broken, just the toast layer is unavailable.
