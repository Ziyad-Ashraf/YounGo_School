-- ROLLBACK SQL FOR LOCAL PAYMENT.PAYMOB.CONFIG.DASHBOARD.SCHEMA.1 ONLY
-- REVIEW BEFORE EXECUTION
-- NOT EXECUTED IN THIS PHASE UNLESS MANUAL ROLLBACK IS REQUIRED
--
-- Primary rollback remains restoring the fresh local DB backup created before
-- PAYMENT.PAYMOB.CONFIG.DASHBOARD.SCHEMA.1 was applied.
--
-- This reverses only the dedicated YounGo Paymob dashboard config storage table.
-- It does not touch inherited Academy payment_gateways, payment, enrol, Root
-- Admin, checkout orders, payment transactions, or entitlement/access tables.

DROP TABLE IF EXISTS `youngo_payment_provider_configs`;
