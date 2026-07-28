-- YounGo Phase 2E.1 seed data artifact.
-- Review-only until explicitly approved for local execution.
-- Does not backfill user roles.
-- Does not touch users.role_id, users.is_instructor, permissions, or Root Admin rows.

SET @youngo_now := UNIX_TIMESTAMP();
SET @youngo_seed_currency := COALESCE((SELECT `value` FROM `settings` WHERE `key` = 'system_currency' LIMIT 1), 'USD');

INSERT INTO `youngo_subscription_plans`
  (`name`, `slug`, `duration_days`, `price`, `currency`, `is_active`, `is_purchasable`, `is_featured`, `sort_order`, `created_at`, `updated_at`)
VALUES
  ('Monthly', 'monthly', 30, 0.00, @youngo_seed_currency, 0, 0, 0, 10, @youngo_now, @youngo_now),
  ('3 Months', '3-months', 90, 0.00, @youngo_seed_currency, 0, 0, 1, 20, @youngo_now, @youngo_now),
  ('Yearly', 'yearly', 365, 0.00, @youngo_seed_currency, 0, 0, 0, 30, @youngo_now, @youngo_now)
ON DUPLICATE KEY UPDATE
  `name` = VALUES(`name`),
  `duration_days` = VALUES(`duration_days`),
  `price` = 0.00,
  `currency` = VALUES(`currency`),
  `is_active` = 0,
  `is_purchasable` = 0,
  `is_featured` = VALUES(`is_featured`),
  `sort_order` = VALUES(`sort_order`),
  `updated_at` = @youngo_now;

INSERT INTO `youngo_roles`
  (`role_key`, `label`, `description`, `is_system`, `is_assignable`, `created_at`)
VALUES
  ('admin', 'Admin', 'Broad YounGo operational admin role without Root Admin protection changes.', 1, 1, @youngo_now),
  ('content_manager', 'Content Manager', 'Manages YounGo content and content-related media only.', 1, 1, @youngo_now),
  ('course_manager', 'Course Manager', 'Manages courses, lessons, categories, publishing, and assigning existing instructors.', 1, 1, @youngo_now),
  ('instructor', 'Instructor', 'Manages lessons only for courses assigned through legacy instructor assignment compatibility.', 1, 1, @youngo_now)
ON DUPLICATE KEY UPDATE
  `label` = VALUES(`label`),
  `description` = VALUES(`description`),
  `is_system` = VALUES(`is_system`),
  `is_assignable` = VALUES(`is_assignable`);

INSERT INTO `youngo_capabilities`
  (`capability_key`, `label`, `description`, `is_system`, `created_at`)
VALUES
  ('manage_homepage_content', 'Manage Homepage Content', 'Manage YounGo homepage sections and content.', 1, @youngo_now),
  ('manage_static_content', 'Manage Static Content', 'Manage static pages, blog, FAQ, testimonials, contact, and similar content areas when available.', 1, @youngo_now),
  ('manage_media', 'Manage Media', 'Upload and manage content-related media.', 1, @youngo_now),
  ('manage_courses', 'Manage Courses', 'Create and edit course records.', 1, @youngo_now),
  ('manage_lessons', 'Manage Lessons', 'Manage course sections and lessons.', 1, @youngo_now),
  ('publish_courses', 'Publish Courses', 'Publish or change course publication state.', 1, @youngo_now),
  ('manage_course_categories', 'Manage Course Categories', 'Manage course categories required for course operations.', 1, @youngo_now),
  ('assign_existing_instructors', 'Assign Existing Instructors', 'Assign active existing instructors to courses.', 1, @youngo_now),
  ('manage_assigned_course_lessons', 'Manage Assigned Course Lessons', 'Instructor capability for lessons on assigned courses only.', 1, @youngo_now),
  ('grant_manual_access', 'Grant Manual Access', 'Create auditable manual access grants.', 1, @youngo_now),
  ('view_payments', 'View Payments', 'View payment and revenue records where allowed.', 1, @youngo_now),
  ('manage_users', 'Manage Users', 'Manage ordinary user accounts where allowed.', 1, @youngo_now),
  ('manage_instructors', 'Manage Instructors', 'Manage instructor accounts where allowed.', 1, @youngo_now),
  ('manage_system_settings', 'Manage System Settings', 'Manage core system settings. Root-only by default until separately approved.', 1, @youngo_now),
  ('manage_roles', 'Manage Roles', 'Manage YounGo role assignments. Root-only by default until separately approved.', 1, @youngo_now),
  ('manage_subscriptions', 'Manage Subscriptions', 'Manage subscription plans and user subscriptions.', 1, @youngo_now),
  ('manage_coupons', 'Manage Coupons', 'Manage YounGo coupon scope and targeting.', 1, @youngo_now),
  ('view_reports', 'View Reports', 'View operational reports where allowed.', 1, @youngo_now)
ON DUPLICATE KEY UPDATE
  `label` = VALUES(`label`),
  `description` = VALUES(`description`),
  `is_system` = VALUES(`is_system`);

INSERT IGNORE INTO `youngo_role_capabilities` (`role_id`, `capability_id`)
SELECT r.`id`, c.`id`
FROM `youngo_roles` r
JOIN `youngo_capabilities` c ON c.`capability_key` IN (
  'manage_homepage_content',
  'manage_static_content',
  'manage_media',
  'manage_courses',
  'manage_lessons',
  'publish_courses',
  'manage_course_categories',
  'assign_existing_instructors',
  'grant_manual_access',
  'view_payments',
  'manage_users',
  'manage_instructors',
  'manage_subscriptions',
  'manage_coupons',
  'view_reports'
)
WHERE r.`role_key` = 'admin';

INSERT IGNORE INTO `youngo_role_capabilities` (`role_id`, `capability_id`)
SELECT r.`id`, c.`id`
FROM `youngo_roles` r
JOIN `youngo_capabilities` c ON c.`capability_key` IN (
  'manage_homepage_content',
  'manage_static_content',
  'manage_media'
)
WHERE r.`role_key` = 'content_manager';

INSERT IGNORE INTO `youngo_role_capabilities` (`role_id`, `capability_id`)
SELECT r.`id`, c.`id`
FROM `youngo_roles` r
JOIN `youngo_capabilities` c ON c.`capability_key` IN (
  'manage_courses',
  'manage_lessons',
  'publish_courses',
  'manage_course_categories',
  'assign_existing_instructors'
)
WHERE r.`role_key` = 'course_manager';

INSERT IGNORE INTO `youngo_role_capabilities` (`role_id`, `capability_id`)
SELECT r.`id`, c.`id`
FROM `youngo_roles` r
JOIN `youngo_capabilities` c ON c.`capability_key` IN (
  'manage_assigned_course_lessons'
)
WHERE r.`role_key` = 'instructor';
