-- YounGo Phase 2E.1 read-only validation queries.
-- These queries are intended for manual review after a separately approved local migration.
-- Do not edit data from this file.

SELECT DATABASE() AS current_database;
SELECT VERSION() AS server_version;

SHOW TABLES LIKE 'youngo\_%';

SELECT 'youngo_subscription_plans' AS table_name, COUNT(*) AS row_count FROM `youngo_subscription_plans`;
SELECT 'youngo_roles' AS table_name, COUNT(*) AS row_count FROM `youngo_roles`;
SELECT 'youngo_capabilities' AS table_name, COUNT(*) AS row_count FROM `youngo_capabilities`;
SELECT 'youngo_role_capabilities' AS table_name, COUNT(*) AS row_count FROM `youngo_role_capabilities`;
SELECT 'youngo_user_roles' AS table_name, COUNT(*) AS row_count FROM `youngo_user_roles`;

SELECT `slug`, `name`, `duration_days`, `price`, `currency`, `is_active`, `is_purchasable`, `is_featured`
FROM `youngo_subscription_plans`
ORDER BY `sort_order`, `id`;

SELECT `role_key`, `label`, `is_assignable`
FROM `youngo_roles`
ORDER BY `role_key`;

SELECT `capability_key`, `label`
FROM `youngo_capabilities`
ORDER BY `capability_key`;

SELECT r.`role_key`, COUNT(rc.`capability_id`) AS capability_count
FROM `youngo_roles` r
LEFT JOIN `youngo_role_capabilities` rc ON rc.`role_id` = r.`id`
GROUP BY r.`role_key`
ORDER BY r.`role_key`;

SHOW COLUMNS FROM `course` LIKE 'youngo_access_mode';
SHOW COLUMNS FROM `course` LIKE 'youngo_allow_individual_purchase';
SHOW COLUMNS FROM `course` LIKE 'youngo_purchase_access_type';
SHOW COLUMNS FROM `course` LIKE 'youngo_purchase_duration_days';
SHOW COLUMNS FROM `course` LIKE 'youngo_subscription_excluded';

SHOW COLUMNS FROM `coupons` LIKE 'discount_type';
SHOW COLUMNS FROM `coupons` LIKE 'discount_value';
SHOW COLUMNS FROM `coupons` LIKE 'scope';
SHOW COLUMNS FROM `coupons` LIKE 'max_usage_count';
SHOW COLUMNS FROM `coupons` LIKE 'status';
SHOW COLUMNS FROM `coupons` LIKE 'updated_at';

SELECT COUNT(*) AS users_count FROM `users`;
SELECT COUNT(*) AS admin_role_users_count FROM `users` WHERE `role_id` = 1;
SELECT COUNT(*) AS learner_role_users_count FROM `users` WHERE `role_id` = 2;
SELECT COUNT(*) AS instructor_flag_users_count FROM `users` WHERE `is_instructor` = 1;
SELECT COUNT(*) AS permissions_count FROM `permissions`;

SELECT
  u.`id`,
  u.`email`,
  u.`role_id`,
  u.`is_instructor`,
  CASE WHEN p.`id` IS NULL THEN 'root_admin_by_legacy_missing_permissions_row' ELSE 'restricted_admin_by_permissions_row' END AS legacy_admin_state,
  p.`permissions`
FROM `users` u
LEFT JOIN `permissions` p ON p.`admin_id` = u.`id`
WHERE u.`role_id` = 1
ORDER BY u.`id`;

SELECT COUNT(*) AS inferred_root_admin_count
FROM `users` u
LEFT JOIN `permissions` p ON p.`admin_id` = u.`id`
WHERE u.`role_id` = 1 AND p.`id` IS NULL;

SELECT COUNT(*) AS user_role_backfill_count
FROM `youngo_user_roles`;

SELECT
  u.`id`,
  u.`email`,
  u.`role_id`,
  u.`is_instructor`,
  u.`status`,
  p.`permissions`
FROM `users` u
LEFT JOIN `permissions` p ON p.`admin_id` = u.`id`
WHERE u.`id` = 7 OR u.`email` = 'client@gmail.com';

SELECT COUNT(*) AS enrol_count FROM `enrol`;
SELECT COUNT(*) AS payment_count FROM `payment`;
SELECT COUNT(*) AS watch_histories_count FROM `watch_histories`;
SELECT COUNT(*) AS watched_duration_count FROM `watched_duration`;
SELECT COUNT(*) AS courses_count FROM `course`;
SELECT COUNT(*) AS coupons_count FROM `coupons`;
