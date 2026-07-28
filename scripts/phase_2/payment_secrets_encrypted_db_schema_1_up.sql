-- PAYMENT.SECRETS.ENCRYPTED.DB.SCHEMA.1
-- LOCAL ENCRYPTED PAYMOB CREDENTIAL STORAGE SCHEMA
-- REVIEWED AND EXECUTED LOCALLY ONLY AFTER BACKUP
-- No real Paymob values are inserted by this script.
-- Do not use legacy payment_gateways for YounGo Paymob credentials.

CREATE TABLE IF NOT EXISTS `youngo_payment_provider_secret_configs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `provider` VARCHAR(50) NOT NULL DEFAULT 'paymob',
  `mode` VARCHAR(20) NOT NULL DEFAULT 'sandbox',
  `encrypted_api_key` LONGTEXT DEFAULT NULL,
  `encrypted_public_key` LONGTEXT DEFAULT NULL,
  `encrypted_secret_key` LONGTEXT DEFAULT NULL,
  `encrypted_hmac_secret` LONGTEXT DEFAULT NULL,
  `has_api_key` TINYINT(1) NOT NULL DEFAULT 0,
  `has_public_key` TINYINT(1) NOT NULL DEFAULT 0,
  `has_secret_key` TINYINT(1) NOT NULL DEFAULT 0,
  `has_hmac_secret` TINYINT(1) NOT NULL DEFAULT 0,
  `encryption_version` VARCHAR(50) NOT NULL DEFAULT 'ci3_encryption_v1',
  `key_fingerprint` VARCHAR(128) DEFAULT NULL,
  `storage_status` VARCHAR(80) NOT NULL DEFAULT 'schema_ready_no_values',
  `last_rotated_at` INT(11) UNSIGNED DEFAULT NULL,
  `updated_by_user_id` BIGINT UNSIGNED DEFAULT NULL,
  `created_at` INT(11) UNSIGNED DEFAULT NULL,
  `updated_at` INT(11) UNSIGNED DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_yppsc_provider_mode` (`provider`, `mode`),
  KEY `idx_yppsc_provider_mode_status` (`provider`, `mode`, `storage_status`),
  KEY `idx_yppsc_updated_by` (`updated_by_user_id`),
  KEY `idx_yppsc_updated_at` (`updated_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
