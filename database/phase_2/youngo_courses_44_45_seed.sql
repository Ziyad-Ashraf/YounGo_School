-- Seed the two bilingual demo courses requested for YounGo instructor profiles.
-- Canonical course rows are preserved if either ID already exists.
START TRANSACTION;

INSERT INTO `course` (
  `id`, `title`, `short_description`, `description`, `outcomes`, `faqs`, `language`,
  `category_id`, `sub_category_id`, `section`, `requirements`, `price`, `discount_flag`,
  `discounted_price`, `level`, `user_id`, `thumbnail`, `video_url`, `date_added`,
  `last_modified`, `course_type`, `is_top_course`, `is_admin`, `status`,
  `course_overview_provider`, `meta_keywords`, `meta_description`, `is_free_course`,
  `multi_instructor`, `enable_drip_content`, `creator`, `expiry_period`,
  `upcoming_image_thumbnail`, `publish_date`, `youngo_access_mode`,
  `youngo_allow_individual_purchase`, `youngo_purchase_access_type`,
  `youngo_purchase_duration_days`, `youngo_subscription_excluded`
) VALUES
  (44, 'Cartoon Dubbing - الدوبلاج الكرتوني', '[]', '[]', '[]', '[]', 'arabic', 16, 17, '[56,57,58]', '[]', 0, 0, 0, 'beginner', 7, NULL, 'https://youtu.be/T-g0yvxndJI?si=MWZPIgYnLnMA_8qQ', 1784779200, 1785056371, 'general', 0, 0, 'active', 'youtube', NULL, NULL, 0, 0, 0, 7, NULL, NULL, NULL, 'subscription_only', 0, 'lifetime', NULL, 0)
ON DUPLICATE KEY UPDATE `title` = VALUES(`title`), `language` = VALUES(`language`), `video_url` = VALUES(`video_url`), `status` = VALUES(`status`), `creator` = VALUES(`creator`), `youngo_access_mode` = VALUES(`youngo_access_mode`);

INSERT INTO `course` (
  `id`, `title`, `short_description`, `description`, `outcomes`, `faqs`, `language`,
  `category_id`, `sub_category_id`, `section`, `requirements`, `price`, `discount_flag`,
  `discounted_price`, `level`, `user_id`, `thumbnail`, `video_url`, `date_added`,
  `last_modified`, `course_type`, `is_top_course`, `is_admin`, `status`,
  `course_overview_provider`, `meta_keywords`, `meta_description`, `is_free_course`,
  `multi_instructor`, `enable_drip_content`, `creator`, `expiry_period`,
  `upcoming_image_thumbnail`, `publish_date`, `youngo_access_mode`,
  `youngo_allow_individual_purchase`, `youngo_purchase_access_type`,
  `youngo_purchase_duration_days`, `youngo_subscription_excluded`
) VALUES
  (45, 'Basic Drawing Course', '[]', '[]', '[]', '[]', 'english', 18, 19, '[59,60,61,62]', '[]', 0, 0, 0, 'beginner', 7, NULL, 'https://www.youtube.com/watch?v=-zP-9NE1U7E', 1785038400, NULL, 'general', 0, 0, 'active', 'youtube', NULL, NULL, 0, 0, 0, 7, NULL, NULL, NULL, 'subscription_only', 0, 'lifetime', NULL, 0)
ON DUPLICATE KEY UPDATE `title` = VALUES(`title`), `language` = VALUES(`language`), `video_url` = VALUES(`video_url`), `status` = VALUES(`status`), `creator` = VALUES(`creator`), `youngo_access_mode` = VALUES(`youngo_access_mode`);

INSERT INTO `youngo_course_translations` (`course_id`, `language_code`, `title`, `short_description`, `created_at`, `updated_at`)
VALUES
  (44, 'arabic', 'الدوبلاج الكرتوني', 'دورة عربية لتعلم أساسيات الدوبلاج الكرتوني.', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
  (45, 'english', 'Basic Drawing Course', 'A beginner-friendly course for learning the basics of drawing.', UNIX_TIMESTAMP(), UNIX_TIMESTAMP())
ON DUPLICATE KEY UPDATE `title` = VALUES(`title`), `short_description` = VALUES(`short_description`), `updated_at` = VALUES(`updated_at`);

COMMIT;
