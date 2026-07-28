-- YounGo Phase 2L subscription plan management rollback artifact.
-- Review-only until explicitly approved for execution.
-- Reverses only Phase 2L archive/audit additions.
-- Does not drop the original youngo_subscription_plans table.

DROP TABLE IF EXISTS `youngo_subscription_plan_audit_log`;

ALTER TABLE `youngo_subscription_plans`
  DROP INDEX IF EXISTS `idx_ysp_archived_at`;

ALTER TABLE `youngo_subscription_plans`
  DROP COLUMN IF EXISTS `archived_by_user_id`,
  DROP COLUMN IF EXISTS `archived_at`;
