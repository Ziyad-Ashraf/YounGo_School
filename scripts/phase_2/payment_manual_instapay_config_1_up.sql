-- PAYMENT.MANUAL.INSTAPAY.CONFIG.1
-- Additive manual Instapay target configuration fields on the existing
-- YounGo payment provider config table. No checkout/upload/admin-review
-- behavior is enabled by this schema.

ALTER TABLE `youngo_payment_provider_configs`
  ADD COLUMN IF NOT EXISTS `instapay_enabled_for_checkout` TINYINT(1) NOT NULL DEFAULT 0 AFTER `transaction_inquiry_enabled`,
  ADD COLUMN IF NOT EXISTS `instapay_target_label` VARCHAR(255) DEFAULT NULL AFTER `instapay_enabled_for_checkout`,
  ADD COLUMN IF NOT EXISTS `instapay_target_address` VARCHAR(255) DEFAULT NULL AFTER `instapay_target_label`,
  ADD COLUMN IF NOT EXISTS `instapay_target_link` VARCHAR(500) DEFAULT NULL AFTER `instapay_target_address`,
  ADD COLUMN IF NOT EXISTS `instapay_instructions_ar` TEXT DEFAULT NULL AFTER `instapay_target_link`,
  ADD COLUMN IF NOT EXISTS `instapay_instructions_en` TEXT DEFAULT NULL AFTER `instapay_instructions_ar`,
  ADD COLUMN IF NOT EXISTS `instapay_max_upload_mb` DECIMAL(5,2) NOT NULL DEFAULT 5.00 AFTER `instapay_instructions_en`,
  ADD COLUMN IF NOT EXISTS `instapay_allowed_mimes` VARCHAR(255) DEFAULT 'image/jpeg,image/png,image/webp' AFTER `instapay_max_upload_mb`;
