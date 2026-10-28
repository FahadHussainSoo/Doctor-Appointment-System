-- ==========================================================================
-- MediBook — Phase 4 database patch
-- Additive only: adds a single nullable column so profile avatars can be
-- stored. Nothing existing is altered, renamed, or dropped, and every
-- Phase 1–3 table, query, and page keeps working unchanged whether or not
-- you run this (avatar upload just falls back to initials until you do).
--
-- Run once against your existing `doctor_booking_system` database, e.g.:
--   mysql -u root -p doctor_booking_system < db_patch_phase4.sql
-- ==========================================================================

ALTER TABLE `users`
  ADD COLUMN `avatar` VARCHAR(255) DEFAULT NULL AFTER `address`;
