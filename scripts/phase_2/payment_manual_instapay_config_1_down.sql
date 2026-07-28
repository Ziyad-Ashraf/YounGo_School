-- PAYMENT.MANUAL.INSTAPAY.CONFIG.1 rollback.
-- Drops only the additive manual Instapay config columns from this phase.

ALTER TABLE `youngo_payment_provider_configs`
  DROP COLUMN IF EXISTS `instapay_allowed_mimes`,
  DROP COLUMN IF EXISTS `instapay_max_upload_mb`,
  DROP COLUMN IF EXISTS `instapay_instructions_en`,
  DROP COLUMN IF EXISTS `instapay_instructions_ar`,
  DROP COLUMN IF EXISTS `instapay_target_link`,
  DROP COLUMN IF EXISTS `instapay_target_address`,
  DROP COLUMN IF EXISTS `instapay_target_label`,
  DROP COLUMN IF EXISTS `instapay_enabled_for_checkout`;
