-- PAYMENT.CONFIG.2 proposed YounGo payment schema SQL
-- STATUS: NOT EXECUTED.
-- PURPOSE: Future local-only schema proposal for YounGo checkout orders,
-- Paymob sandbox transaction recording, idempotency, HMAC verification, and
-- entitlement issuance tracking.
--
-- DO NOT RUN THIS FILE IN PAYMENT.CONFIG.2.
-- Do not execute without a fresh local DB backup, owner approval, and a
-- reviewed implementation phase. This file is planning/design only.
--
-- Notes:
-- - The current local schema already has youngo_checkout_orders from Phase 2E.
-- - This proposal is additive and keeps hard foreign keys out, matching the
--   existing Phase 2 schema style.
-- - Existing USD-era defaults must be replaced by explicit EGP handling before
--   any real checkout/payment issuance.
-- - Credentials are intentionally not represented in this SQL.

-- -------------------------------------------------------------------------
-- 1. Base checkout order table if missing in a future environment.
-- -------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `youngo_checkout_orders` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `order_reference` VARCHAR(64) DEFAULT NULL,
  `order_type` VARCHAR(50) NOT NULL,
  `status` VARCHAR(50) NOT NULL DEFAULT 'draft',
  `course_id` INT(11) UNSIGNED DEFAULT NULL,
  `plan_id` BIGINT UNSIGNED DEFAULT NULL,
  `subtotal_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `discount_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `tax_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `total_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `total_amount_cents` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `currency` VARCHAR(10) NOT NULL DEFAULT 'EGP',
  `coupon_id` INT(11) UNSIGNED DEFAULT NULL,
  `coupon_code` VARCHAR(255) DEFAULT NULL,
  `payment_gateway` VARCHAR(100) DEFAULT NULL,
  `gateway_environment` VARCHAR(20) NOT NULL DEFAULT 'sandbox',
  `provider_intent_id` VARCHAR(255) DEFAULT NULL,
  `provider_order_id` VARCHAR(255) DEFAULT NULL,
  `provider_transaction_id` VARCHAR(255) DEFAULT NULL,
  `payment_id` INT(11) UNSIGNED DEFAULT NULL,
  `idempotency_key` VARCHAR(128) DEFAULT NULL,
  `last_hmac_verified` TINYINT(1) NOT NULL DEFAULT 0,
  `entitlement_issued` TINYINT(1) NOT NULL DEFAULT 0,
  `entitlement_issuance_status` VARCHAR(50) NOT NULL DEFAULT 'not_started',
  `entitlement_course_access_id` BIGINT UNSIGNED DEFAULT NULL,
  `entitlement_subscription_id` BIGINT UNSIGNED DEFAULT NULL,
  `entitlement_issued_at` INT(11) UNSIGNED DEFAULT NULL,
  `entitlement_issuance_error` TEXT DEFAULT NULL,
  `failure_code` VARCHAR(100) DEFAULT NULL,
  `failure_message` TEXT DEFAULT NULL,
  `metadata` LONGTEXT DEFAULT NULL,
  `created_at` INT(11) UNSIGNED DEFAULT NULL,
  `updated_at` INT(11) UNSIGNED DEFAULT NULL,
  `payment_started_at` INT(11) UNSIGNED DEFAULT NULL,
  `return_seen_at` INT(11) UNSIGNED DEFAULT NULL,
  `last_webhook_at` INT(11) UNSIGNED DEFAULT NULL,
  `completed_at` INT(11) UNSIGNED DEFAULT NULL,
  `paid_at` INT(11) UNSIGNED DEFAULT NULL,
  `failed_at` INT(11) UNSIGNED DEFAULT NULL,
  `cancelled_at` INT(11) UNSIGNED DEFAULT NULL,
  `expired_at` INT(11) UNSIGNED DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_yco_order_reference` (`order_reference`),
  UNIQUE KEY `uniq_yco_idempotency_key` (`idempotency_key`),
  KEY `idx_yco_user_status` (`user_id`, `status`),
  KEY `idx_yco_order_status` (`order_type`, `status`),
  KEY `idx_yco_course_id` (`course_id`),
  KEY `idx_yco_plan_id` (`plan_id`),
  KEY `idx_yco_coupon_id` (`coupon_id`),
  KEY `idx_yco_provider_intent` (`provider_intent_id`),
  KEY `idx_yco_provider_order` (`provider_order_id`),
  KEY `idx_yco_provider_transaction` (`provider_transaction_id`),
  KEY `idx_yco_payment_id` (`payment_id`),
  KEY `idx_yco_gateway_env_status` (`payment_gateway`, `gateway_environment`, `status`),
  KEY `idx_yco_entitlement_status` (`entitlement_issuance_status`, `entitlement_issued`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------------------
-- 2. Additive checkout order columns for existing Phase 2E tables.
-- -------------------------------------------------------------------------

ALTER TABLE `youngo_checkout_orders`
  ADD COLUMN IF NOT EXISTS `order_reference` VARCHAR(64) DEFAULT NULL AFTER `user_id`,
  ADD COLUMN IF NOT EXISTS `total_amount_cents` BIGINT UNSIGNED NOT NULL DEFAULT 0 AFTER `total_amount`,
  ADD COLUMN IF NOT EXISTS `gateway_environment` VARCHAR(20) NOT NULL DEFAULT 'sandbox' AFTER `payment_gateway`,
  ADD COLUMN IF NOT EXISTS `provider_order_id` VARCHAR(255) DEFAULT NULL AFTER `provider_intent_id`,
  ADD COLUMN IF NOT EXISTS `idempotency_key` VARCHAR(128) DEFAULT NULL AFTER `payment_id`,
  ADD COLUMN IF NOT EXISTS `last_hmac_verified` TINYINT(1) NOT NULL DEFAULT 0 AFTER `idempotency_key`,
  ADD COLUMN IF NOT EXISTS `entitlement_issued` TINYINT(1) NOT NULL DEFAULT 0 AFTER `last_hmac_verified`,
  ADD COLUMN IF NOT EXISTS `entitlement_issuance_status` VARCHAR(50) NOT NULL DEFAULT 'not_started' AFTER `entitlement_issued`,
  ADD COLUMN IF NOT EXISTS `entitlement_course_access_id` BIGINT UNSIGNED DEFAULT NULL AFTER `entitlement_issuance_status`,
  ADD COLUMN IF NOT EXISTS `entitlement_subscription_id` BIGINT UNSIGNED DEFAULT NULL AFTER `entitlement_course_access_id`,
  ADD COLUMN IF NOT EXISTS `entitlement_issued_at` INT(11) UNSIGNED DEFAULT NULL AFTER `entitlement_subscription_id`,
  ADD COLUMN IF NOT EXISTS `entitlement_issuance_error` TEXT DEFAULT NULL AFTER `entitlement_issued_at`,
  ADD COLUMN IF NOT EXISTS `failure_code` VARCHAR(100) DEFAULT NULL AFTER `entitlement_issuance_error`,
  ADD COLUMN IF NOT EXISTS `failure_message` TEXT DEFAULT NULL AFTER `failure_code`,
  ADD COLUMN IF NOT EXISTS `payment_started_at` INT(11) UNSIGNED DEFAULT NULL AFTER `updated_at`,
  ADD COLUMN IF NOT EXISTS `return_seen_at` INT(11) UNSIGNED DEFAULT NULL AFTER `payment_started_at`,
  ADD COLUMN IF NOT EXISTS `last_webhook_at` INT(11) UNSIGNED DEFAULT NULL AFTER `return_seen_at`,
  ADD COLUMN IF NOT EXISTS `paid_at` INT(11) UNSIGNED DEFAULT NULL AFTER `completed_at`,
  ADD COLUMN IF NOT EXISTS `failed_at` INT(11) UNSIGNED DEFAULT NULL AFTER `paid_at`,
  ADD COLUMN IF NOT EXISTS `cancelled_at` INT(11) UNSIGNED DEFAULT NULL AFTER `failed_at`,
  ADD COLUMN IF NOT EXISTS `expired_at` INT(11) UNSIGNED DEFAULT NULL AFTER `cancelled_at`;

-- Proposed future default once approved.
-- Review current rows before execution. Current local table is expected empty.
ALTER TABLE `youngo_checkout_orders`
  MODIFY COLUMN `currency` VARCHAR(10) NOT NULL DEFAULT 'EGP',
  MODIFY COLUMN `status` VARCHAR(50) NOT NULL DEFAULT 'draft';

ALTER TABLE `youngo_checkout_orders`
  ADD UNIQUE INDEX IF NOT EXISTS `uniq_yco_order_reference` (`order_reference`),
  ADD UNIQUE INDEX IF NOT EXISTS `uniq_yco_idempotency_key` (`idempotency_key`),
  ADD INDEX IF NOT EXISTS `idx_yco_provider_order` (`provider_order_id`),
  ADD INDEX IF NOT EXISTS `idx_yco_provider_transaction` (`provider_transaction_id`),
  ADD INDEX IF NOT EXISTS `idx_yco_gateway_env_status` (`payment_gateway`, `gateway_environment`, `status`),
  ADD INDEX IF NOT EXISTS `idx_yco_entitlement_status` (`entitlement_issuance_status`, `entitlement_issued`);

-- -------------------------------------------------------------------------
-- 3. Transaction/event recording table.
-- -------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `youngo_payment_transactions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `checkout_order_id` BIGINT UNSIGNED DEFAULT NULL,
  `user_id` INT(11) UNSIGNED DEFAULT NULL,
  `order_reference` VARCHAR(64) DEFAULT NULL,
  `gateway_provider` VARCHAR(100) NOT NULL DEFAULT 'paymob',
  `gateway_environment` VARCHAR(20) NOT NULL DEFAULT 'sandbox',
  `event_type` VARCHAR(50) NOT NULL,
  `status` VARCHAR(50) NOT NULL DEFAULT 'received',
  `gateway_status` VARCHAR(100) DEFAULT NULL,
  `currency` VARCHAR(10) NOT NULL DEFAULT 'EGP',
  `amount_cents` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `amount_decimal` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `provider_intent_id` VARCHAR(255) DEFAULT NULL,
  `provider_order_id` VARCHAR(255) DEFAULT NULL,
  `provider_transaction_id` VARCHAR(255) DEFAULT NULL,
  `provider_integration_id` VARCHAR(255) DEFAULT NULL,
  `merchant_order_reference` VARCHAR(255) DEFAULT NULL,
  `hmac_received` TINYINT(1) NOT NULL DEFAULT 0,
  `hmac_verified` TINYINT(1) NOT NULL DEFAULT 0,
  `verification_source` VARCHAR(50) DEFAULT NULL,
  `idempotency_key` VARCHAR(128) DEFAULT NULL,
  `payload_hash` CHAR(64) DEFAULT NULL,
  `raw_payload_redacted` LONGTEXT DEFAULT NULL,
  `error_code` VARCHAR(100) DEFAULT NULL,
  `error_message` TEXT DEFAULT NULL,
  `received_at` INT(11) UNSIGNED DEFAULT NULL,
  `verified_at` INT(11) UNSIGNED DEFAULT NULL,
  `reconciled_at` INT(11) UNSIGNED DEFAULT NULL,
  `processed_at` INT(11) UNSIGNED DEFAULT NULL,
  `created_at` INT(11) UNSIGNED DEFAULT NULL,
  `updated_at` INT(11) UNSIGNED DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_ypt_idempotency_key` (`idempotency_key`),
  UNIQUE KEY `uniq_ypt_provider_tx_event` (`gateway_provider`, `gateway_environment`, `provider_transaction_id`, `event_type`),
  KEY `idx_ypt_checkout_order_id` (`checkout_order_id`),
  KEY `idx_ypt_user_id` (`user_id`),
  KEY `idx_ypt_order_reference` (`order_reference`),
  KEY `idx_ypt_status` (`status`),
  KEY `idx_ypt_event_status` (`event_type`, `status`),
  KEY `idx_ypt_provider_intent` (`provider_intent_id`),
  KEY `idx_ypt_provider_order` (`provider_order_id`),
  KEY `idx_ypt_provider_transaction` (`provider_transaction_id`),
  KEY `idx_ypt_payload_hash` (`payload_hash`),
  KEY `idx_ypt_received_at` (`received_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------------------
-- 4. Existing entitlement/access table indexes needed for payment issuance.
-- -------------------------------------------------------------------------

ALTER TABLE `youngo_course_access`
  ADD UNIQUE INDEX IF NOT EXISTS `uniq_yca_checkout_order` (`checkout_order_id`),
  ADD INDEX IF NOT EXISTS `idx_yca_checkout_payment` (`checkout_order_id`, `payment_id`),
  ADD INDEX IF NOT EXISTS `idx_yca_user_course_source_status` (`user_id`, `course_id`, `access_source`, `status`);

ALTER TABLE `youngo_user_subscriptions`
  ADD UNIQUE INDEX IF NOT EXISTS `uniq_yus_checkout_order` (`checkout_order_id`),
  ADD INDEX IF NOT EXISTS `idx_yus_checkout_payment` (`checkout_order_id`, `payment_id`),
  ADD INDEX IF NOT EXISTS `idx_yus_user_source_status` (`user_id`, `source`, `status`);

ALTER TABLE `youngo_coupon_usages`
  ADD UNIQUE INDEX IF NOT EXISTS `uniq_ycu_checkout_order` (`checkout_order_id`),
  ADD INDEX IF NOT EXISTS `idx_ycu_user_coupon_order` (`user_id`, `coupon_id`, `checkout_order_id`);

-- -------------------------------------------------------------------------
-- 5. Review queries for a future approved apply phase.
-- -------------------------------------------------------------------------

-- SELECT COUNT(*) AS checkout_order_count FROM `youngo_checkout_orders`;
-- SELECT COUNT(*) AS payment_transaction_count FROM `youngo_payment_transactions`;
-- SELECT `status`, COUNT(*) AS count_value FROM `youngo_checkout_orders` GROUP BY `status`;
-- SELECT `status`, COUNT(*) AS count_value FROM `youngo_payment_transactions` GROUP BY `status`;
-- SELECT `currency`, COUNT(*) AS count_value FROM `youngo_checkout_orders` GROUP BY `currency`;

-- End of proposal. Nothing in this file was executed by PAYMENT.CONFIG.2.
