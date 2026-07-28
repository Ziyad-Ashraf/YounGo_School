-- DYNAMIC.CONTENT.ARABIC.SUBSCRIPTIONS.SCHEMA.1
-- Additive subscription plan translation schema.
--
-- Scope:
-- - Add Arabic/English content translations for YounGo subscription plans.
-- - Preserve canonical subscription plan IDs and operational slugs.
-- - Do not duplicate price, duration, currency, slug, status, archive, checkout, payment, or access fields.
-- - Do not change public subscription behavior in this phase.

CREATE TABLE IF NOT EXISTS `youngo_subscription_plan_translations` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `plan_id` INT(11) UNSIGNED NOT NULL,
  `language_code` VARCHAR(20) NOT NULL,
  `name` VARCHAR(255) DEFAULT NULL,
  `short_description` VARCHAR(500) DEFAULT NULL,
  `description` LONGTEXT DEFAULT NULL,
  `badge_label` VARCHAR(100) DEFAULT NULL,
  `created_by_user_id` INT(11) UNSIGNED DEFAULT NULL,
  `updated_by_user_id` INT(11) UNSIGNED DEFAULT NULL,
  `created_at` INT(11) UNSIGNED DEFAULT NULL,
  `updated_at` INT(11) UNSIGNED DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_yspt_plan_language` (`plan_id`, `language_code`),
  KEY `idx_yspt_plan_id` (`plan_id`),
  KEY `idx_yspt_language_code` (`language_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
