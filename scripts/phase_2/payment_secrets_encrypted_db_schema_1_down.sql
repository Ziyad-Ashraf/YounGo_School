-- ROLLBACK SQL FOR LOCAL PAYMENT.SECRETS.ENCRYPTED.DB.SCHEMA.1 ONLY
-- REVIEW BEFORE EXECUTION
-- NOT EXECUTED IN THIS PHASE UNLESS MANUAL ROLLBACK IS REQUIRED
-- This removes the YounGo encrypted Paymob credential storage table only.
-- It does not touch legacy payment_gateways, payment, enrol, Root Admin, or Paymob config rows.

DROP TABLE IF EXISTS `youngo_payment_provider_secret_configs`;
