-- PAYMENT.PAYMOB.CONFIG.DASHBOARD.SCHEMA.1 local schema artifact.
-- LOCAL ONLY. REVIEW BEFORE EXECUTION.
-- Applied only in the approved local PAYMENT.PAYMOB.CONFIG.DASHBOARD.SCHEMA.1 phase.
--
-- Purpose:
-- - Add dedicated YounGo Paymob dashboard configuration storage.
-- - Keep YounGo Paymob config separate from inherited Academy payment_gateways.
-- - Store public/non-private config and private value presence/status only.
-- - Do not store real private Paymob values while encryption/key-management is not ready.
-- - Keep all payment, network, webhook, and checkout CTA flags disabled by default.

CREATE TABLE IF NOT EXISTS `youngo_payment_provider_configs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `provider` VARCHAR(50) NOT NULL DEFAULT 'paymob',
  `mode` VARCHAR(20) NOT NULL DEFAULT 'sandbox',
  `currency` VARCHAR(10) NOT NULL DEFAULT 'EGP',
  `amount_multiplier` INT(11) UNSIGNED NOT NULL DEFAULT 100,
  `enabled` TINYINT(1) NOT NULL DEFAULT 0,
  `network_enabled` TINYINT(1) NOT NULL DEFAULT 0,
  `sandbox_network_testing_enabled` TINYINT(1) NOT NULL DEFAULT 0,
  `webhook_testing_enabled` TINYINT(1) NOT NULL DEFAULT 0,
  `checkout_routes_enabled` TINYINT(1) NOT NULL DEFAULT 0,
  `checkout_local_testing_enabled` TINYINT(1) NOT NULL DEFAULT 0,
  `checkout_cta_enabled` TINYINT(1) NOT NULL DEFAULT 0,
  `live_mode_allowed` TINYINT(1) NOT NULL DEFAULT 0,
  `public_key` VARCHAR(512) DEFAULT NULL,
  `public_key_present` TINYINT(1) NOT NULL DEFAULT 0,
  `secret_key_present` TINYINT(1) NOT NULL DEFAULT 0,
  `hmac_secret_present` TINYINT(1) NOT NULL DEFAULT 0,
  `api_key_present` TINYINT(1) NOT NULL DEFAULT 0,
  `card_integration_id_egp` VARCHAR(100) DEFAULT NULL,
  `api_base_url` VARCHAR(255) DEFAULT NULL,
  `checkout_base_url` VARCHAR(255) DEFAULT NULL,
  `return_url` VARCHAR(500) DEFAULT NULL,
  `notification_url` VARCHAR(500) DEFAULT NULL,
  `transaction_inquiry_enabled` TINYINT(1) NOT NULL DEFAULT 0,
  `readiness_status` VARCHAR(50) NOT NULL DEFAULT 'not_configured',
  `readiness_errors` LONGTEXT DEFAULT NULL,
  `private_storage_status` VARCHAR(80) NOT NULL DEFAULT 'blocked_encryption_key_missing',
  `last_readiness_checked_at` INT(11) UNSIGNED DEFAULT NULL,
  `last_sandbox_test_at` INT(11) UNSIGNED DEFAULT NULL,
  `last_sandbox_test_status` VARCHAR(50) DEFAULT NULL,
  `updated_by_user_id` BIGINT UNSIGNED DEFAULT NULL,
  `created_at` INT(11) UNSIGNED DEFAULT NULL,
  `updated_at` INT(11) UNSIGNED DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_yppc_provider_mode` (`provider`, `mode`),
  KEY `idx_yppc_provider_currency` (`provider`, `currency`),
  KEY `idx_yppc_readiness_status` (`readiness_status`),
  KEY `idx_yppc_updated_by` (`updated_by_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
