-- YounGo coupon lifecycle extension. Apply after a verified database backup.
-- Adds the coupon start-date required by subscription checkout validation.
ALTER TABLE `coupons`
  ADD COLUMN IF NOT EXISTS `start_date` INT(11) UNSIGNED DEFAULT NULL COMMENT 'Coupon becomes valid at this timestamp';

ALTER TABLE `coupons`
  ADD INDEX IF NOT EXISTS `idx_coupons_status_start_expiry` (`status`, `start_date`, `expiry_date`);
