-- ROLLBACK SQL FOR LOCAL PAYMENT.PAYMOB.CONFIG.DASHBOARD.AUDIT.SCHEMA.1 ONLY
-- REVIEW BEFORE EXECUTION
-- NOT EXECUTED IN THIS PHASE UNLESS MANUAL ROLLBACK IS REQUIRED
--
-- Primary rollback remains restoring the fresh local DB backup created before
-- PAYMENT.PAYMOB.CONFIG.DASHBOARD.AUDIT.SCHEMA.1 was applied.
--
-- This reverses only the dedicated YounGo Paymob dashboard config audit table.
-- It does not touch inherited Academy payment_gateways, payment, enrol, Root
-- Admin, checkout orders, payment transactions, config rows, or entitlement/access tables.

DROP TABLE IF EXISTS `youngo_payment_config_audit_logs`;
