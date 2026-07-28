-- YounGo Phase 2L subscription plan management schema artifact.
-- Review-only until explicitly approved for execution.
-- Do not run on server without a separate backup, preflight, validation, and approval.
-- This migration is additive and compatibility-first.
-- It does not modify checkout, payment, enrolment, coupons, user subscription issuance, roles, or Root Admin data.

ALTER TABLE `youngo_subscription_plans`
  ADD COLUMN IF NOT EXISTS `archived_at` INT(11) UNSIGNED DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `archived_by_user_id` INT(11) UNSIGNED DEFAULT NULL;

ALTER TABLE `youngo_subscription_plans`
  ADD INDEX IF NOT EXISTS `idx_ysp_archived_at` (`archived_at`);

CREATE TABLE IF NOT EXISTS `youngo_subscription_plan_audit_log` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `plan_id` BIGINT UNSIGNED NOT NULL,
  `actor_user_id` BIGINT UNSIGNED DEFAULT NULL,
  `action` VARCHAR(100) NOT NULL,
  `before_data` LONGTEXT DEFAULT NULL,
  `after_data` LONGTEXT DEFAULT NULL,
  `created_at` INT(11) UNSIGNED DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_yspal_plan_created` (`plan_id`, `created_at`),
  KEY `idx_yspal_actor_created` (`actor_user_id`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
