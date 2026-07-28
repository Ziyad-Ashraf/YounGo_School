-- LANGUAGE.PHRASE.OVERRIDE.METADATA.SCHEMA.1
-- Additive metadata tables for safe language pack imports and manual phrase override tracking.
-- This migration does not import phrase values and does not modify the legacy `language` table.

CREATE TABLE IF NOT EXISTS `youngo_language_import_batches` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `language_code` VARCHAR(32) NOT NULL,
  `source_file` VARCHAR(255) NOT NULL,
  `source_sha256` CHAR(64) DEFAULT NULL,
  `import_mode` VARCHAR(64) NOT NULL,
  `total_keys` INT(11) UNSIGNED NOT NULL DEFAULT 0,
  `inserted_count` INT(11) UNSIGNED NOT NULL DEFAULT 0,
  `updated_count` INT(11) UNSIGNED NOT NULL DEFAULT 0,
  `skipped_count` INT(11) UNSIGNED NOT NULL DEFAULT 0,
  `manual_preserved_count` INT(11) UNSIGNED NOT NULL DEFAULT 0,
  `invalid_count` INT(11) UNSIGNED NOT NULL DEFAULT 0,
  `status` VARCHAR(32) NOT NULL DEFAULT 'preview',
  `summary_json` LONGTEXT DEFAULT NULL,
  `created_by_user_id` INT(11) UNSIGNED DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ylib_language_status_created` (`language_code`, `status`, `created_at`),
  KEY `idx_ylib_created_by_user` (`created_by_user_id`),
  KEY `idx_ylib_source_sha256` (`source_sha256`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `youngo_language_phrase_meta` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `phrase_id` INT(11) UNSIGNED DEFAULT NULL,
  `phrase_key` VARCHAR(255) NOT NULL,
  `language_code` VARCHAR(32) NOT NULL,
  `source` VARCHAR(32) NOT NULL DEFAULT 'legacy_existing',
  `last_import_batch_id` INT(11) UNSIGNED DEFAULT NULL,
  `last_imported_value_hash` CHAR(64) DEFAULT NULL,
  `current_value_hash` CHAR(64) DEFAULT NULL,
  `manually_overridden_at` DATETIME DEFAULT NULL,
  `manually_overridden_by_user_id` INT(11) UNSIGNED DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_ylpm_phrase_language` (`phrase_key`, `language_code`),
  KEY `idx_ylpm_phrase_id` (`phrase_id`),
  KEY `idx_ylpm_language_source` (`language_code`, `source`),
  KEY `idx_ylpm_last_import_batch` (`last_import_batch_id`),
  KEY `idx_ylpm_manual_actor` (`manually_overridden_by_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
