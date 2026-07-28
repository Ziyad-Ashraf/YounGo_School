-- PAYMENT.COUPON.CHECKOUT.SNAPSHOT.SCHEMA.1 rollback
-- Removes only nullable columns introduced by this phase.

ALTER TABLE `youngo_checkout_orders`
  DROP COLUMN IF EXISTS `checkout_snapshot_json`,
  DROP COLUMN IF EXISTS `item_title_snapshot`,
  DROP COLUMN IF EXISTS `selected_payment_method`,
  DROP COLUMN IF EXISTS `coupon_discount_value`,
  DROP COLUMN IF EXISTS `coupon_discount_type`;
