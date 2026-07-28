-- YounGo Phase 2E.1 schema migration artifact.
-- Review-only until explicitly approved for local execution.
-- Do not run on server without a separate backup, preflight, validation, and approval.
-- This migration is additive and compatibility-first.
-- It does not replace enrol, payment, permissions, users.role_id, users.is_instructor, cart, invoice, or lesson access behavior.

CREATE TABLE IF NOT EXISTS `youngo_subscription_plans` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(100) NOT NULL,
  `duration_days` INT(11) NOT NULL,
  `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `currency` VARCHAR(10) NOT NULL DEFAULT 'USD',
  `is_active` TINYINT(1) NOT NULL DEFAULT 0,
  `is_purchasable` TINYINT(1) NOT NULL DEFAULT 0,
  `is_featured` TINYINT(1) NOT NULL DEFAULT 0,
  `sort_order` INT(11) NOT NULL DEFAULT 0,
  `created_at` INT(11) UNSIGNED DEFAULT NULL,
  `updated_at` INT(11) UNSIGNED DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_ysp_slug` (`slug`),
  KEY `idx_ysp_active` (`is_active`),
  KEY `idx_ysp_purchasable` (`is_purchasable`),
  KEY `idx_ysp_sort` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `youngo_roles` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `role_key` VARCHAR(100) NOT NULL,
  `label` VARCHAR(255) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `is_system` TINYINT(1) NOT NULL DEFAULT 1,
  `is_assignable` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` INT(11) UNSIGNED DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_yr_role_key` (`role_key`),
  KEY `idx_yr_assignable` (`is_assignable`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `youngo_capabilities` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `capability_key` VARCHAR(150) NOT NULL,
  `label` VARCHAR(255) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `is_system` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` INT(11) UNSIGNED DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_yc_capability_key` (`capability_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `youngo_role_capabilities` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `role_id` BIGINT UNSIGNED NOT NULL,
  `capability_id` BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_yrc_role_capability` (`role_id`, `capability_id`),
  KEY `idx_yrc_capability_id` (`capability_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `youngo_user_roles` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `role_id` BIGINT UNSIGNED NOT NULL,
  `assigned_by_user_id` INT(11) UNSIGNED DEFAULT NULL,
  `created_at` INT(11) UNSIGNED DEFAULT NULL,
  `revoked_at` INT(11) UNSIGNED DEFAULT NULL,
  `status` VARCHAR(50) NOT NULL DEFAULT 'active',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_yur_user_role_status` (`user_id`, `role_id`, `status`),
  KEY `idx_yur_user_status` (`user_id`, `status`),
  KEY `idx_yur_role_status` (`role_id`, `status`),
  KEY `idx_yur_assigned_by` (`assigned_by_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `youngo_checkout_orders` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `order_type` VARCHAR(50) NOT NULL,
  `status` VARCHAR(50) NOT NULL DEFAULT 'pending',
  `course_id` INT(11) UNSIGNED DEFAULT NULL,
  `plan_id` BIGINT UNSIGNED DEFAULT NULL,
  `subtotal_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `discount_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `tax_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `total_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `currency` VARCHAR(10) NOT NULL DEFAULT 'USD',
  `coupon_id` INT(11) UNSIGNED DEFAULT NULL,
  `coupon_code` VARCHAR(255) DEFAULT NULL,
  `payment_gateway` VARCHAR(100) DEFAULT NULL,
  `provider_intent_id` VARCHAR(255) DEFAULT NULL,
  `provider_transaction_id` VARCHAR(255) DEFAULT NULL,
  `payment_id` INT(11) UNSIGNED DEFAULT NULL,
  `metadata` LONGTEXT DEFAULT NULL,
  `created_at` INT(11) UNSIGNED DEFAULT NULL,
  `updated_at` INT(11) UNSIGNED DEFAULT NULL,
  `completed_at` INT(11) UNSIGNED DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_yco_user_status` (`user_id`, `status`),
  KEY `idx_yco_order_status` (`order_type`, `status`),
  KEY `idx_yco_course_id` (`course_id`),
  KEY `idx_yco_plan_id` (`plan_id`),
  KEY `idx_yco_coupon_id` (`coupon_id`),
  KEY `idx_yco_provider_intent` (`provider_intent_id`),
  KEY `idx_yco_payment_id` (`payment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `youngo_manual_grants` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `granted_by_user_id` INT(11) UNSIGNED NOT NULL,
  `granted_to_user_id` INT(11) UNSIGNED NOT NULL,
  `grant_type` VARCHAR(50) NOT NULL,
  `course_id` INT(11) UNSIGNED DEFAULT NULL,
  `plan_id` BIGINT UNSIGNED DEFAULT NULL,
  `custom_duration_days` INT(11) DEFAULT NULL,
  `start_date` INT(11) UNSIGNED DEFAULT NULL,
  `expiry_date` INT(11) UNSIGNED DEFAULT NULL,
  `is_lifetime` TINYINT(1) NOT NULL DEFAULT 0,
  `status` VARCHAR(50) NOT NULL DEFAULT 'active',
  `note` TEXT DEFAULT NULL,
  `created_at` INT(11) UNSIGNED DEFAULT NULL,
  `revoked_at` INT(11) UNSIGNED DEFAULT NULL,
  `revoked_by_user_id` INT(11) UNSIGNED DEFAULT NULL,
  `revoke_note` TEXT DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ymg_granted_to` (`granted_to_user_id`),
  KEY `idx_ymg_granted_by` (`granted_by_user_id`),
  KEY `idx_ymg_type_status` (`grant_type`, `status`),
  KEY `idx_ymg_course_id` (`course_id`),
  KEY `idx_ymg_plan_id` (`plan_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `youngo_user_subscriptions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `plan_id` BIGINT UNSIGNED NOT NULL,
  `source` VARCHAR(50) NOT NULL DEFAULT 'checkout',
  `status` VARCHAR(50) NOT NULL DEFAULT 'active',
  `start_date` INT(11) UNSIGNED DEFAULT NULL,
  `expiry_date` INT(11) UNSIGNED DEFAULT NULL,
  `duration_days` INT(11) DEFAULT NULL,
  `price_paid` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `currency` VARCHAR(10) NOT NULL DEFAULT 'USD',
  `checkout_order_id` BIGINT UNSIGNED DEFAULT NULL,
  `payment_id` INT(11) UNSIGNED DEFAULT NULL,
  `manual_grant_id` BIGINT UNSIGNED DEFAULT NULL,
  `created_at` INT(11) UNSIGNED DEFAULT NULL,
  `updated_at` INT(11) UNSIGNED DEFAULT NULL,
  `revoked_at` INT(11) UNSIGNED DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_yus_user_status_expiry` (`user_id`, `status`, `expiry_date`),
  KEY `idx_yus_plan_id` (`plan_id`),
  KEY `idx_yus_checkout_order_id` (`checkout_order_id`),
  KEY `idx_yus_payment_id` (`payment_id`),
  KEY `idx_yus_manual_grant_id` (`manual_grant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `youngo_course_access` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `course_id` INT(11) UNSIGNED NOT NULL,
  `access_source` VARCHAR(50) NOT NULL,
  `status` VARCHAR(50) NOT NULL DEFAULT 'active',
  `start_date` INT(11) UNSIGNED DEFAULT NULL,
  `expiry_date` INT(11) UNSIGNED DEFAULT NULL,
  `is_lifetime` TINYINT(1) NOT NULL DEFAULT 1,
  `checkout_order_id` BIGINT UNSIGNED DEFAULT NULL,
  `payment_id` INT(11) UNSIGNED DEFAULT NULL,
  `enrol_id` INT(11) UNSIGNED DEFAULT NULL,
  `manual_grant_id` BIGINT UNSIGNED DEFAULT NULL,
  `created_at` INT(11) UNSIGNED DEFAULT NULL,
  `updated_at` INT(11) UNSIGNED DEFAULT NULL,
  `revoked_at` INT(11) UNSIGNED DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_yca_user_course_status` (`user_id`, `course_id`, `status`),
  KEY `idx_yca_course_status` (`course_id`, `status`),
  KEY `idx_yca_expiry_date` (`expiry_date`),
  KEY `idx_yca_payment_id` (`payment_id`),
  KEY `idx_yca_enrol_id` (`enrol_id`),
  KEY `idx_yca_manual_grant_id` (`manual_grant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `youngo_coupon_usages` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `coupon_id` INT(11) UNSIGNED DEFAULT NULL,
  `coupon_code` VARCHAR(255) DEFAULT NULL,
  `user_id` INT(11) UNSIGNED DEFAULT NULL,
  `checkout_order_id` BIGINT UNSIGNED DEFAULT NULL,
  `payment_id` INT(11) UNSIGNED DEFAULT NULL,
  `discount_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `used_at` INT(11) UNSIGNED DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ycu_coupon_id` (`coupon_id`),
  KEY `idx_ycu_coupon_code` (`coupon_code`),
  KEY `idx_ycu_user_id` (`user_id`),
  KEY `idx_ycu_checkout_order_id` (`checkout_order_id`),
  KEY `idx_ycu_payment_id` (`payment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `youngo_coupon_subscription_plans` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `coupon_id` INT(11) UNSIGNED NOT NULL,
  `plan_id` BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_ycsp_coupon_plan` (`coupon_id`, `plan_id`),
  KEY `idx_ycsp_plan_id` (`plan_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `youngo_coupon_courses` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `coupon_id` INT(11) UNSIGNED NOT NULL,
  `course_id` INT(11) UNSIGNED NOT NULL,
  `rule_type` VARCHAR(50) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_ycc_coupon_course_rule` (`coupon_id`, `course_id`, `rule_type`),
  KEY `idx_ycc_course_id` (`course_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `course`
  ADD COLUMN IF NOT EXISTS `youngo_access_mode` VARCHAR(50) NOT NULL DEFAULT 'subscription_only' COMMENT 'YounGo access: subscription_only, subscription_and_purchase, purchase_only';

ALTER TABLE `course`
  ADD COLUMN IF NOT EXISTS `youngo_allow_individual_purchase` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'YounGo individual purchase availability';

ALTER TABLE `course`
  ADD COLUMN IF NOT EXISTS `youngo_purchase_access_type` VARCHAR(50) NOT NULL DEFAULT 'lifetime' COMMENT 'YounGo purchase access: lifetime or time_limited';

ALTER TABLE `course`
  ADD COLUMN IF NOT EXISTS `youngo_purchase_duration_days` INT(11) DEFAULT NULL COMMENT 'YounGo time-limited purchase duration in days';

ALTER TABLE `course`
  ADD COLUMN IF NOT EXISTS `youngo_subscription_excluded` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'YounGo subscription exclusion flag';

ALTER TABLE `coupons`
  ADD COLUMN IF NOT EXISTS `discount_type` VARCHAR(50) NOT NULL DEFAULT 'percentage' COMMENT 'YounGo coupon type: percentage or fixed';

ALTER TABLE `coupons`
  ADD COLUMN IF NOT EXISTS `discount_value` DECIMAL(10,2) DEFAULT NULL COMMENT 'YounGo coupon value; legacy discount_percentage remains compatible';

ALTER TABLE `coupons`
  ADD COLUMN IF NOT EXISTS `scope` VARCHAR(50) NOT NULL DEFAULT 'both' COMMENT 'YounGo coupon scope: subscription, course_purchase, both';

ALTER TABLE `coupons`
  ADD COLUMN IF NOT EXISTS `max_usage_count` INT(11) DEFAULT NULL COMMENT 'YounGo maximum coupon usage count';

ALTER TABLE `coupons`
  ADD COLUMN IF NOT EXISTS `status` VARCHAR(50) NOT NULL DEFAULT 'active' COMMENT 'YounGo coupon status';

ALTER TABLE `coupons`
  ADD COLUMN IF NOT EXISTS `updated_at` INT(11) UNSIGNED DEFAULT NULL COMMENT 'YounGo coupon updated timestamp';
