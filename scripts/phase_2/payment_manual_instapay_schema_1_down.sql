-- PAYMENT.MANUAL.INSTAPAY.SCHEMA.1 rollback.
-- Drops only the additive manual Instapay submission table from this phase.

DROP TABLE IF EXISTS `youngo_instapay_payment_submissions`;
