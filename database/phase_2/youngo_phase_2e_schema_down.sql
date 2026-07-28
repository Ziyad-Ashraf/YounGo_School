-- YounGo Phase 2E.1 rollback artifact.
-- WARNING: Do not run without a verified database backup and explicit approval.
-- WARNING: This rollback is intended only for Phase 2E.1 artifacts before production use.
-- WARNING: Export all youngo_ tables first if any Phase 2 test data exists.
-- This rollback must not touch enrol, payment, users, permissions, role, watch_histories, or watched_duration.

DROP TABLE IF EXISTS `youngo_coupon_courses`;
DROP TABLE IF EXISTS `youngo_coupon_subscription_plans`;
DROP TABLE IF EXISTS `youngo_coupon_usages`;
DROP TABLE IF EXISTS `youngo_course_access`;
DROP TABLE IF EXISTS `youngo_user_subscriptions`;
DROP TABLE IF EXISTS `youngo_manual_grants`;
DROP TABLE IF EXISTS `youngo_checkout_orders`;
DROP TABLE IF EXISTS `youngo_user_roles`;
DROP TABLE IF EXISTS `youngo_role_capabilities`;
DROP TABLE IF EXISTS `youngo_capabilities`;
DROP TABLE IF EXISTS `youngo_roles`;
DROP TABLE IF EXISTS `youngo_subscription_plans`;

ALTER TABLE `course`
  DROP COLUMN IF EXISTS `youngo_subscription_excluded`;

ALTER TABLE `course`
  DROP COLUMN IF EXISTS `youngo_purchase_duration_days`;

ALTER TABLE `course`
  DROP COLUMN IF EXISTS `youngo_purchase_access_type`;

ALTER TABLE `course`
  DROP COLUMN IF EXISTS `youngo_allow_individual_purchase`;

ALTER TABLE `course`
  DROP COLUMN IF EXISTS `youngo_access_mode`;

ALTER TABLE `coupons`
  DROP COLUMN IF EXISTS `updated_at`;

ALTER TABLE `coupons`
  DROP COLUMN IF EXISTS `status`;

ALTER TABLE `coupons`
  DROP COLUMN IF EXISTS `max_usage_count`;

ALTER TABLE `coupons`
  DROP COLUMN IF EXISTS `scope`;

ALTER TABLE `coupons`
  DROP COLUMN IF EXISTS `discount_value`;

ALTER TABLE `coupons`
  DROP COLUMN IF EXISTS `discount_type`;
