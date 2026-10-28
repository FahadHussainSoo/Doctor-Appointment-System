# Phase 4 — Profile & Settings Redesign

Scope, per the brief: redesign Doctor/Patient/Admin profiles + Settings;
add profile cards, timeline, stats, avatar, activity; improve forms, tabs,
cards, uploads. Backend logic from Phases 1–3 is unchanged.

## New pages
- `profile.php` — patient profile (hero, stats, timeline, edit info, security)
- `doctor/profile.php` — doctor profile (+ read-only professional info tab)
- `admin/profile.php` — admin profile (+ system-wide stats & activity feed)
- `admin/settings.php` — security / preferences (dark mode) / system info

## New shared assets
- `assets/css/profile.css` — hero, tabs, timeline, activity feed, upload
  dropzone, mini stat cards, info-list rows, and dark-mode token overrides
- `assets/js/profile.js` — tab switching, avatar dropzone + live preview,
  password strength meter, confirm-password check, dark/light toggle
- `includes/uploads.php` — new, isolated avatar-upload helper (auth.php,
  config.php, and every other Phase 1–3 backend file are untouched)

## One additive DB change
`db_patch_phase4.sql` adds a single nullable `users.avatar` column — needed
so an uploaded photo has somewhere to persist. Nothing existing is altered
or dropped. Run it once:
```
mysql -u root -p doctor_booking_system < db_patch_phase4.sql
```
If you skip it, every page still works — avatar upload just shows a clear
"run the patch" message instead of a broken query, and profiles fall back
to initials avatars.

## What's genuinely functional vs. cosmetic
- Avatar upload, profile edits, and password changes are real (validated,
  persisted, tested against a live MySQL instance).
- Dark/light mode is real — it's a client-side toggle (localStorage) that
  re-points the app's existing CSS custom properties, so it reaches
  cards/tables/forms/sidebars app-wide, not just the new pages.
- Doctor "Professional Info" (specialty, fee, bio, hours) is shown
  read-only with a note pointing to admin/doctors.php, which already had
  the only existing editor for those columns — not duplicated here.
- Settings → Preferences has one disabled placeholder switch (email
  alerts) clearly labeled as not wired to a mail sender yet, so it isn't
  mistaken for working.

## Tested
Every new/modified PHP file passes `php -l`. Full end-to-end pass against
a live MariaDB instance: login as patient/doctor/admin, load all four new
pages, submit every form (profile edit, password change, avatar upload)
and confirm the database actually changed. Regression-tested all 11
pre-existing pages after editing the shared header/sidebar/footer includes
— zero errors, zero broken links.
