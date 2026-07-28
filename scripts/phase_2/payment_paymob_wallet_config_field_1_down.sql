-- ROLLBACK SQL FOR LOCAL PAYMENT.PAYMOB.WALLET.CONFIG.FIELD.1 ONLY
-- REVIEW BEFORE EXECUTION
-- NOT EXECUTED IN THIS PHASE UNLESS MANUAL ROLLBACK IS REQUIRED
--
-- Primary rollback remains restoring the fresh local DB backup created before
-- PAYMENT.PAYMOB.WALLET.CONFIG.FIELD.1 was applied.
-- This reverses only the additive non-private wallet integration ID field.

ALTER TABLE `youngo_payment_provider_configs`
  DROP COLUMN IF EXISTS `wallet_integration_id_egp`;
