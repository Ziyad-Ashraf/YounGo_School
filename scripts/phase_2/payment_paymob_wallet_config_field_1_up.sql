-- PAYMENT.PAYMOB.WALLET.CONFIG.FIELD.1
-- LOCAL PAYMOB WALLET INTEGRATION ID CONFIG FIELD
-- REVIEWED AND EXECUTED LOCALLY ONLY AFTER BACKUP
-- No Paymob credentials or activation flags are inserted by this script.
-- Do not use legacy payment_gateways for YounGo Paymob configuration.

ALTER TABLE `youngo_payment_provider_configs`
  ADD COLUMN IF NOT EXISTS `wallet_integration_id_egp` VARCHAR(100) DEFAULT NULL
  AFTER `card_integration_id_egp`;
