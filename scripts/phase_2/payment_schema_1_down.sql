-- ROLLBACK SQL FOR LOCAL PAYMENT.SCHEMA.1 ONLY
-- REVIEW BEFORE EXECUTION
-- NOT EXECUTED IN THIS PHASE UNLESS MANUAL ROLLBACK IS REQUIRED
--
-- Purpose:
-- Reverse the additive YounGo payment schema changes applied by
-- scripts/phase_2/payment_config_2_youngo_payment_schema_proposed.sql.
--
-- Primary rollback remains restoring the fresh local DB backup created before
-- PAYMENT.SCHEMA.1, because MySQL/MariaDB DDL auto-commits. Use this file only
-- after review when a targeted rollback is safer than full DB restore.
--
-- Assumptions:
-- - The local baseline already had youngo_checkout_orders from Phase 2E.
-- - The Phase 2E checkout defaults were currency = USD and status = pending.
-- - No production/live/cPanel database should ever run this rollback file.

-- -------------------------------------------------------------------------
-- 1. Drop transaction/event table introduced for YounGo payment processing.
-- -------------------------------------------------------------------------

DROP TABLE IF EXISTS `youngo_payment_transactions`;

-- -------------------------------------------------------------------------
-- 2. Remove checkout-order indexes introduced by PAYMENT.SCHEMA.1.
-- -------------------------------------------------------------------------

ALTER TABLE `youngo_checkout_orders`
  DROP INDEX IF EXISTS `uniq_yco_order_reference`,
  DROP INDEX IF EXISTS `uniq_yco_idempotency_key`,
  DROP INDEX IF EXISTS `idx_yco_provider_order`,
  DROP INDEX IF EXISTS `idx_yco_provider_transaction`,
  DROP INDEX IF EXISTS `idx_yco_gateway_env_status`,
  DROP INDEX IF EXISTS `idx_yco_entitlement_status`;

-- -------------------------------------------------------------------------
-- 3. Remove checkout-order columns introduced by PAYMENT.SCHEMA.1.
-- -------------------------------------------------------------------------

ALTER TABLE `youngo_checkout_orders`
  DROP COLUMN IF EXISTS `order_reference`,
  DROP COLUMN IF EXISTS `total_amount_cents`,
  DROP COLUMN IF EXISTS `gateway_environment`,
  DROP COLUMN IF EXISTS `provider_order_id`,
  DROP COLUMN IF EXISTS `idempotency_key`,
  DROP COLUMN IF EXISTS `last_hmac_verified`,
  DROP COLUMN IF EXISTS `entitlement_issued`,
  DROP COLUMN IF EXISTS `entitlement_issuance_status`,
  DROP COLUMN IF EXISTS `entitlement_course_access_id`,
  DROP COLUMN IF EXISTS `entitlement_subscription_id`,
  DROP COLUMN IF EXISTS `entitlement_issued_at`,
  DROP COLUMN IF EXISTS `entitlement_issuance_error`,
  DROP COLUMN IF EXISTS `failure_code`,
  DROP COLUMN IF EXISTS `failure_message`,
  DROP COLUMN IF EXISTS `payment_started_at`,
  DROP COLUMN IF EXISTS `return_seen_at`,
  DROP COLUMN IF EXISTS `last_webhook_at`,
  DROP COLUMN IF EXISTS `paid_at`,
  DROP COLUMN IF EXISTS `failed_at`,
  DROP COLUMN IF EXISTS `cancelled_at`,
  DROP COLUMN IF EXISTS `expired_at`;

-- Restore Phase 2E defaults. Review current data before execution.
ALTER TABLE `youngo_checkout_orders`
  MODIFY COLUMN `currency` VARCHAR(10) NOT NULL DEFAULT 'USD',
  MODIFY COLUMN `status` VARCHAR(50) NOT NULL DEFAULT 'pending';

-- -------------------------------------------------------------------------
-- 4. Remove entitlement/coupon indexes introduced by PAYMENT.SCHEMA.1.
-- -------------------------------------------------------------------------

ALTER TABLE `youngo_course_access`
  DROP INDEX IF EXISTS `uniq_yca_checkout_order`,
  DROP INDEX IF EXISTS `idx_yca_checkout_payment`,
  DROP INDEX IF EXISTS `idx_yca_user_course_source_status`;

ALTER TABLE `youngo_user_subscriptions`
  DROP INDEX IF EXISTS `uniq_yus_checkout_order`,
  DROP INDEX IF EXISTS `idx_yus_checkout_payment`,
  DROP INDEX IF EXISTS `idx_yus_user_source_status`;

ALTER TABLE `youngo_coupon_usages`
  DROP INDEX IF EXISTS `uniq_ycu_checkout_order`,
  DROP INDEX IF EXISTS `idx_ycu_user_coupon_order`;

-- End of rollback proposal. Do not execute without owner approval and backup.
