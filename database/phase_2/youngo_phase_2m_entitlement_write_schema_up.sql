-- YounGo Phase 2M entitlement write service schema artifact.
-- Additive only. Do not apply without explicit backup, review, and approval.
-- Adds revoke actor/note support and lookup indexes needed by the shared write service.
-- No hard foreign keys. No row rewrites. No plan/user/order/coupon/enrol/payment data changes.

ALTER TABLE `youngo_course_access`
  ADD COLUMN IF NOT EXISTS `revoked_by_user_id` INT UNSIGNED NULL AFTER `revoked_at`,
  ADD COLUMN IF NOT EXISTS `revoke_note` TEXT NULL AFTER `revoked_by_user_id`;

ALTER TABLE `youngo_course_access`
  ADD INDEX IF NOT EXISTS `idx_yca_checkout_order_id` (`checkout_order_id`),
  ADD INDEX IF NOT EXISTS `idx_yca_revoked_by_user_id` (`revoked_by_user_id`);

ALTER TABLE `youngo_user_subscriptions`
  ADD COLUMN IF NOT EXISTS `revoked_by_user_id` INT UNSIGNED NULL AFTER `revoked_at`,
  ADD COLUMN IF NOT EXISTS `revoke_note` TEXT NULL AFTER `revoked_by_user_id`;

ALTER TABLE `youngo_user_subscriptions`
  ADD INDEX IF NOT EXISTS `idx_yus_revoked_by_user_id` (`revoked_by_user_id`);
