-- PAYMENT.PAYMOB.CONFIG.DASHBOARD.AUDIT.SCHEMA.1 local schema artifact.
-- LOCAL ONLY. REVIEW BEFORE EXECUTION.
-- Applied only in the approved local PAYMENT.PAYMOB.CONFIG.DASHBOARD.AUDIT.SCHEMA.1 phase.
--
-- Purpose:
-- - Add dedicated YounGo Paymob dashboard configuration audit logging.
-- - Store redacted non-private config summaries and secret presence transitions only.
-- - Never store raw private Paymob values.
-- - Keep audit logging separate from inherited Academy payment_gateways.
-- - Do not enable payment, network, webhook, or checkout CTA behavior.

CREATE TABLE IF NOT EXISTS `youngo_payment_config_audit_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `provider` VARCHAR(50) NOT NULL DEFAULT 'paymob',
  `mode` VARCHAR(20) NOT NULL DEFAULT 'sandbox',
  `action` VARCHAR(80) NOT NULL,
  `actor_user_id` BIGINT UNSIGNED DEFAULT NULL,
  `actor_role` VARCHAR(80) DEFAULT NULL,
  `actor_type` VARCHAR(80) DEFAULT NULL,
  `changed_fields_json` LONGTEXT DEFAULT NULL,
  `before_summary_json` LONGTEXT DEFAULT NULL,
  `after_summary_json` LONGTEXT DEFAULT NULL,
  `secret_presence_changes_json` LONGTEXT DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` VARCHAR(255) DEFAULT NULL,
  `created_at` INT(11) UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ypcal_provider_mode_created` (`provider`, `mode`, `created_at`),
  KEY `idx_ypcal_actor_created` (`actor_user_id`, `created_at`),
  KEY `idx_ypcal_action_created` (`action`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
