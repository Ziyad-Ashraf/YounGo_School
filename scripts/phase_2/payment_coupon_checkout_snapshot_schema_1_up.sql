-- PAYMENT.COUPON.CHECKOUT.SNAPSHOT.SCHEMA.1
-- Additive checkout snapshot support only. No data mutation, payment activation, or access behavior change.

ALTER TABLE `youngo_checkout_orders`
  ADD COLUMN IF NOT EXISTS `coupon_discount_type` VARCHAR(50) DEFAULT NULL AFTER `coupon_code`,
  ADD COLUMN IF NOT EXISTS `coupon_discount_value` DECIMAL(10,2) DEFAULT NULL AFTER `coupon_discount_type`,
  ADD COLUMN IF NOT EXISTS `selected_payment_method` VARCHAR(50) DEFAULT NULL AFTER `coupon_discount_value`,
  ADD COLUMN IF NOT EXISTS `item_title_snapshot` VARCHAR(255) DEFAULT NULL AFTER `selected_payment_method`,
  ADD COLUMN IF NOT EXISTS `checkout_snapshot_json` LONGTEXT DEFAULT NULL AFTER `item_title_snapshot`;
