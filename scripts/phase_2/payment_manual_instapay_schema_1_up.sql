-- PAYMENT.MANUAL.INSTAPAY.SCHEMA.1
-- Additive manual Instapay submission schema only.
-- Review statuses for this table are limited to: pending_review, approved, rejected.
-- No upload UI, admin review UI, approval behavior, Paymob activation, or access issuance.

CREATE TABLE IF NOT EXISTS `youngo_instapay_payment_submissions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` BIGINT UNSIGNED NOT NULL,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `status` VARCHAR(50) NOT NULL DEFAULT 'pending_review' COMMENT 'Manual Instapay review status: pending_review, approved, rejected only',
  `expected_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `submitted_amount` DECIMAL(10,2) DEFAULT NULL,
  `currency` VARCHAR(10) NOT NULL DEFAULT 'EGP',
  `instapay_target_label` VARCHAR(255) DEFAULT NULL,
  `instapay_target_address` VARCHAR(255) DEFAULT NULL,
  `instapay_target_link` VARCHAR(500) DEFAULT NULL,
  `screenshot_path` VARCHAR(500) DEFAULT NULL,
  `screenshot_original_name` VARCHAR(255) DEFAULT NULL,
  `screenshot_mime` VARCHAR(100) DEFAULT NULL,
  `screenshot_size` INT(11) UNSIGNED DEFAULT NULL,
  `transaction_reference` VARCHAR(255) DEFAULT NULL,
  `user_note` TEXT DEFAULT NULL,
  `admin_note` TEXT DEFAULT NULL,
  `reviewed_by_user_id` INT(11) UNSIGNED DEFAULT NULL,
  `reviewed_at` INT(11) UNSIGNED DEFAULT NULL,
  `approved_at` INT(11) UNSIGNED DEFAULT NULL,
  `rejected_at` INT(11) UNSIGNED DEFAULT NULL,
  `access_issued` TINYINT(1) NOT NULL DEFAULT 0,
  `access_issued_at` INT(11) UNSIGNED DEFAULT NULL,
  `access_reference_type` VARCHAR(50) DEFAULT NULL,
  `access_reference_id` BIGINT UNSIGNED DEFAULT NULL,
  `snapshot_json` LONGTEXT DEFAULT NULL,
  `created_at` INT(11) UNSIGNED DEFAULT NULL,
  `updated_at` INT(11) UNSIGNED DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_yips_order_id` (`order_id`),
  KEY `idx_yips_user_id` (`user_id`),
  KEY `idx_yips_status` (`status`),
  KEY `idx_yips_reviewed_by` (`reviewed_by_user_id`),
  KEY `idx_yips_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
