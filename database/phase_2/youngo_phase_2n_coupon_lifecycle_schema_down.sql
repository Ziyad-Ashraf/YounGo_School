-- Rollback for the Phase 2N coupon lifecycle extension.
ALTER TABLE `coupons`
  DROP INDEX IF EXISTS `idx_coupons_status_start_expiry`;

ALTER TABLE `coupons`
  DROP COLUMN IF EXISTS `start_date`;
