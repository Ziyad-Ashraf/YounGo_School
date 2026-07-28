-- YounGo Phase 2M entitlement write service rollback artifact.
-- Reverses only Phase 2M additions. Do not drop base YounGo entitlement tables.
-- Do not run without explicit backup, review, and approval.

ALTER TABLE `youngo_course_access`
  DROP INDEX IF EXISTS `idx_yca_checkout_order_id`,
  DROP INDEX IF EXISTS `idx_yca_revoked_by_user_id`;

ALTER TABLE `youngo_course_access`
  DROP COLUMN IF EXISTS `revoke_note`,
  DROP COLUMN IF EXISTS `revoked_by_user_id`;

ALTER TABLE `youngo_user_subscriptions`
  DROP INDEX IF EXISTS `idx_yus_revoked_by_user_id`;

ALTER TABLE `youngo_user_subscriptions`
  DROP COLUMN IF EXISTS `revoke_note`,
  DROP COLUMN IF EXISTS `revoked_by_user_id`;
