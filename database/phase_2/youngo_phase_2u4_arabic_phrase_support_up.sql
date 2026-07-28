-- Phase 2U.4: Canonical Arabic UI phrase support.
--
-- Scope:
-- - Add canonical `arabic` phrase column to the existing Academy LMS language table.
-- - Keep English as the default language.
-- - Keep `arabic_translated` deprecated and non-canonical.
-- - Do not touch course/category/section/lesson content or YounGo translation tables.

ALTER TABLE `language`
  ADD COLUMN IF NOT EXISTS `arabic` longtext DEFAULT NULL AFTER `english`;

-- `settings.language_dirs` already contains {"arabic":"rtl"} in the current local DB.
-- This update is intentionally conditional for portability if the migration is replayed
-- against a compatible DB that is missing the Arabic direction mapping.
UPDATE `settings`
SET `value` = JSON_SET(`value`, '$.arabic', 'rtl')
WHERE `key` = 'language_dirs'
  AND JSON_VALID(`value`)
  AND JSON_EXTRACT(`value`, '$.arabic') IS NULL;
