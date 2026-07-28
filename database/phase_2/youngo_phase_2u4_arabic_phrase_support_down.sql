-- Phase 2U.4 rollback: remove canonical Arabic UI phrase column.
--
-- This intentionally does not remove `arabic` from settings.language_dirs because
-- the current local DB already had that direction mapping before Phase 2U.4.
-- Prefer full DB backup restore for rollback after seeding phrase data.

ALTER TABLE `language`
  DROP COLUMN IF EXISTS `arabic`;
