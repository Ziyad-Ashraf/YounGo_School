-- YounGo Phase 2G.6 controlled QA dataset artifact.
-- REVIEW ONLY. DO NOT APPLY unless a later phase explicitly approves local DB fixture writes.
-- LOCAL ONLY. DO NOT RUN ON PRODUCTION OR SERVER.
-- This script creates temporary QA rows marked with YOUNGO_QA_2G_LOCAL_ONLY.
-- It does not create users, modify passwords, create sessions, or modify demo courses 1-6 or 9.
-- Run the matching down script before re-running this up script.

START TRANSACTION;

SET NAMES utf8 COLLATE utf8_unicode_ci;

-- Keep marker comparisons collation-safe across legacy utf8 LMS tables and
-- YounGo utf8mb4 entitlement tables.
SET @qa_marker_utf8 := _utf8'YOUNGO_QA_2G_LOCAL_ONLY' COLLATE utf8_unicode_ci;
SET @qa_marker_utf8mb4 := _utf8mb4'YOUNGO_QA_2G_LOCAL_ONLY' COLLATE utf8mb4_unicode_ci;
SET @qa_marker_like_utf8 := _utf8'YOUNGO\_QA\_2G\_LOCAL\_ONLY%' COLLATE utf8_unicode_ci;
SET @qa_marker := @qa_marker_utf8;
SET @qa_now := UNIX_TIMESTAMP();
SET @qa_past := UNIX_TIMESTAMP() - 86400;
SET @qa_future := UNIX_TIMESTAMP() + 2592000;
SET @qa_admin_user_id := 1;
SET @qa_no_access_user_id := 2;
SET @qa_instructor_user_id := 5;
SET @qa_learner_user_id := 6;
SET @qa_plan_id := (SELECT `id` FROM `youngo_subscription_plans` WHERE `slug` = _utf8mb4'3-months' COLLATE utf8mb4_unicode_ci LIMIT 1);

-- Preflight review queries. Expected missing_user_count = 0 and @qa_plan_id is not null.
SELECT COUNT(*) AS missing_user_count
FROM (
  SELECT @qa_admin_user_id AS user_id
  UNION ALL SELECT @qa_no_access_user_id
  UNION ALL SELECT @qa_instructor_user_id
  UNION ALL SELECT @qa_learner_user_id
) qa_users
LEFT JOIN `users` u ON u.`id` = qa_users.user_id
WHERE u.`id` IS NULL;

SELECT @qa_plan_id AS selected_subscription_plan_id;

-- QA courses are independent by test case to avoid overlapping active access sources.
-- Subscription-eligible courses use youngo_access_mode = subscription_only and youngo_subscription_excluded = 0.
-- Purchase-only courses use youngo_access_mode = purchase_only and youngo_subscription_excluded = 1.
INSERT INTO `course`
  (`title`, `short_description`, `description`, `outcomes`, `faqs`, `language`, `category_id`, `sub_category_id`, `section`, `requirements`, `price`, `discount_flag`, `discounted_price`, `level`, `user_id`, `thumbnail`, `video_url`, `date_added`, `last_modified`, `course_type`, `is_top_course`, `is_admin`, `status`, `course_overview_provider`, `meta_keywords`, `meta_description`, `is_free_course`, `multi_instructor`, `enable_drip_content`, `creator`, `expiry_period`, `publish_date`, `youngo_access_mode`, `youngo_allow_individual_purchase`, `youngo_purchase_access_type`, `youngo_purchase_duration_days`, `youngo_subscription_excluded`)
VALUES
  ('YOUNGO_QA_SUBSCRIPTION_COURSE__TC-INSTRUCTOR-ACCESS__YOUNGO_QA_2G_LOCAL_ONLY', 'YOUNGO_QA_2G_LOCAL_ONLY instructor access fixture.', '<p>YOUNGO_QA_2G_LOCAL_ONLY instructor access fixture.</p>', '[]', '{}', 'english', 1, 0, '[]', '[]', 0, 0, 0, 'QA', CAST(@qa_instructor_user_id AS CHAR), '', '', @qa_now, @qa_now, 'general', 0, 1, 'active', '', @qa_marker, 'Local-only QA fixture.', 0, 0, 0, @qa_instructor_user_id, NULL, '', 'subscription_only', 0, 'lifetime', NULL, 0),
  ('YOUNGO_QA_PURCHASE_ONLY_COURSE__TC-LEGACY-VALID__YOUNGO_QA_2G_LOCAL_ONLY', 'YOUNGO_QA_2G_LOCAL_ONLY valid legacy enrol fixture.', '<p>YOUNGO_QA_2G_LOCAL_ONLY valid legacy enrol fixture.</p>', '[]', '{}', 'english', 1, 0, '[]', '[]', 0, 0, 0, 'QA', CAST(@qa_admin_user_id AS CHAR), '', '', @qa_now, @qa_now, 'general', 0, 1, 'active', '', @qa_marker, 'Local-only QA fixture.', 0, 0, 0, @qa_admin_user_id, NULL, '', 'purchase_only', 1, 'lifetime', NULL, 1),
  ('YOUNGO_QA_PURCHASE_ONLY_COURSE__TC-LEGACY-EXPIRED__YOUNGO_QA_2G_LOCAL_ONLY', 'YOUNGO_QA_2G_LOCAL_ONLY expired legacy enrol fixture.', '<p>YOUNGO_QA_2G_LOCAL_ONLY expired legacy enrol fixture.</p>', '[]', '{}', 'english', 1, 0, '[]', '[]', 0, 0, 0, 'QA', CAST(@qa_admin_user_id AS CHAR), '', '', @qa_now, @qa_now, 'general', 0, 1, 'active', '', @qa_marker, 'Local-only QA fixture.', 0, 0, 0, @qa_admin_user_id, NULL, '', 'purchase_only', 1, 'lifetime', NULL, 1),
  ('YOUNGO_QA_SUBSCRIPTION_COURSE__TC-COURSE-ACCESS-ACTIVE__YOUNGO_QA_2G_LOCAL_ONLY', 'YOUNGO_QA_2G_LOCAL_ONLY active course access fixture.', '<p>YOUNGO_QA_2G_LOCAL_ONLY active course access fixture.</p>', '[]', '{}', 'english', 1, 0, '[]', '[]', 0, 0, 0, 'QA', CAST(@qa_admin_user_id AS CHAR), '', '', @qa_now, @qa_now, 'general', 0, 1, 'active', '', @qa_marker, 'Local-only QA fixture.', 0, 0, 0, @qa_admin_user_id, NULL, '', 'subscription_only', 0, 'lifetime', NULL, 0),
  ('YOUNGO_QA_SUBSCRIPTION_COURSE__TC-COURSE-ACCESS-EXPIRED__YOUNGO_QA_2G_LOCAL_ONLY', 'YOUNGO_QA_2G_LOCAL_ONLY expired course access fixture.', '<p>YOUNGO_QA_2G_LOCAL_ONLY expired course access fixture.</p>', '[]', '{}', 'english', 1, 0, '[]', '[]', 0, 0, 0, 'QA', CAST(@qa_admin_user_id AS CHAR), '', '', @qa_now, @qa_now, 'general', 0, 1, 'active', '', @qa_marker, 'Local-only QA fixture.', 0, 0, 0, @qa_admin_user_id, NULL, '', 'subscription_only', 0, 'lifetime', NULL, 0),
  ('YOUNGO_QA_PURCHASE_ONLY_COURSE__TC-COURSE-ACCESS-REVOKED__YOUNGO_QA_2G_LOCAL_ONLY', 'YOUNGO_QA_2G_LOCAL_ONLY revoked course access fixture.', '<p>YOUNGO_QA_2G_LOCAL_ONLY revoked course access fixture.</p>', '[]', '{}', 'english', 1, 0, '[]', '[]', 0, 0, 0, 'QA', CAST(@qa_admin_user_id AS CHAR), '', '', @qa_now, @qa_now, 'general', 0, 1, 'active', '', @qa_marker, 'Local-only QA fixture.', 0, 0, 0, @qa_admin_user_id, NULL, '', 'purchase_only', 1, 'lifetime', NULL, 1),
  ('YOUNGO_QA_SUBSCRIPTION_COURSE__TC-SUBSCRIPTION-ACTIVE__YOUNGO_QA_2G_LOCAL_ONLY', 'YOUNGO_QA_2G_LOCAL_ONLY active subscription fixture.', '<p>YOUNGO_QA_2G_LOCAL_ONLY active subscription fixture.</p>', '[]', '{}', 'english', 1, 0, '[]', '[]', 0, 0, 0, 'QA', CAST(@qa_admin_user_id AS CHAR), '', '', @qa_now, @qa_now, 'general', 0, 1, 'active', '', @qa_marker, 'Local-only QA fixture.', 0, 0, 0, @qa_admin_user_id, NULL, '', 'subscription_only', 0, 'lifetime', NULL, 0),
  ('YOUNGO_QA_SUBSCRIPTION_COURSE__TC-SUBSCRIPTION-EXPIRED__YOUNGO_QA_2G_LOCAL_ONLY', 'YOUNGO_QA_2G_LOCAL_ONLY expired subscription fixture.', '<p>YOUNGO_QA_2G_LOCAL_ONLY expired subscription fixture.</p>', '[]', '{}', 'english', 1, 0, '[]', '[]', 0, 0, 0, 'QA', CAST(@qa_admin_user_id AS CHAR), '', '', @qa_now, @qa_now, 'general', 0, 1, 'active', '', @qa_marker, 'Local-only QA fixture.', 0, 0, 0, @qa_admin_user_id, NULL, '', 'subscription_only', 0, 'lifetime', NULL, 0),
  ('YOUNGO_QA_SUBSCRIPTION_COURSE__TC-SUBSCRIPTION-REVOKED__YOUNGO_QA_2G_LOCAL_ONLY', 'YOUNGO_QA_2G_LOCAL_ONLY revoked subscription fixture.', '<p>YOUNGO_QA_2G_LOCAL_ONLY revoked subscription fixture.</p>', '[]', '{}', 'english', 1, 0, '[]', '[]', 0, 0, 0, 'QA', CAST(@qa_admin_user_id AS CHAR), '', '', @qa_now, @qa_now, 'general', 0, 1, 'active', '', @qa_marker, 'Local-only QA fixture.', 0, 0, 0, @qa_admin_user_id, NULL, '', 'subscription_only', 0, 'lifetime', NULL, 0),
  ('YOUNGO_QA_PURCHASE_ONLY_COURSE__TC-PURCHASE-ONLY-DENIAL__YOUNGO_QA_2G_LOCAL_ONLY', 'YOUNGO_QA_2G_LOCAL_ONLY purchase-only subscription denial fixture.', '<p>YOUNGO_QA_2G_LOCAL_ONLY purchase-only subscription denial fixture.</p>', '[]', '{}', 'english', 1, 0, '[]', '[]', 0, 0, 0, 'QA', CAST(@qa_admin_user_id AS CHAR), '', '', @qa_now, @qa_now, 'general', 0, 1, 'active', '', @qa_marker, 'Local-only QA fixture.', 0, 0, 0, @qa_admin_user_id, NULL, '', 'purchase_only', 1, 'lifetime', NULL, 1),
  ('YOUNGO_QA_SUBSCRIPTION_COURSE__TC-MEDIA-STREAMING__YOUNGO_QA_2G_LOCAL_ONLY', 'YOUNGO_QA_2G_LOCAL_ONLY media streaming fixture.', '<p>YOUNGO_QA_2G_LOCAL_ONLY media streaming fixture.</p>', '[]', '{}', 'english', 1, 0, '[]', '[]', 0, 0, 0, 'QA', CAST(@qa_admin_user_id AS CHAR), '', '', @qa_now, @qa_now, 'general', 0, 1, 'active', '', @qa_marker, 'Local-only QA fixture.', 0, 0, 0, @qa_admin_user_id, NULL, '', 'subscription_only', 0, 'lifetime', NULL, 0);

SET @c_instructor := (SELECT `id` FROM `course` WHERE `title` = 'YOUNGO_QA_SUBSCRIPTION_COURSE__TC-INSTRUCTOR-ACCESS__YOUNGO_QA_2G_LOCAL_ONLY' ORDER BY `id` DESC LIMIT 1);
SET @c_legacy_valid := (SELECT `id` FROM `course` WHERE `title` = 'YOUNGO_QA_PURCHASE_ONLY_COURSE__TC-LEGACY-VALID__YOUNGO_QA_2G_LOCAL_ONLY' ORDER BY `id` DESC LIMIT 1);
SET @c_legacy_expired := (SELECT `id` FROM `course` WHERE `title` = 'YOUNGO_QA_PURCHASE_ONLY_COURSE__TC-LEGACY-EXPIRED__YOUNGO_QA_2G_LOCAL_ONLY' ORDER BY `id` DESC LIMIT 1);
SET @c_access_active := (SELECT `id` FROM `course` WHERE `title` = 'YOUNGO_QA_SUBSCRIPTION_COURSE__TC-COURSE-ACCESS-ACTIVE__YOUNGO_QA_2G_LOCAL_ONLY' ORDER BY `id` DESC LIMIT 1);
SET @c_access_expired := (SELECT `id` FROM `course` WHERE `title` = 'YOUNGO_QA_SUBSCRIPTION_COURSE__TC-COURSE-ACCESS-EXPIRED__YOUNGO_QA_2G_LOCAL_ONLY' ORDER BY `id` DESC LIMIT 1);
SET @c_access_revoked := (SELECT `id` FROM `course` WHERE `title` = 'YOUNGO_QA_PURCHASE_ONLY_COURSE__TC-COURSE-ACCESS-REVOKED__YOUNGO_QA_2G_LOCAL_ONLY' ORDER BY `id` DESC LIMIT 1);
SET @c_sub_active := (SELECT `id` FROM `course` WHERE `title` = 'YOUNGO_QA_SUBSCRIPTION_COURSE__TC-SUBSCRIPTION-ACTIVE__YOUNGO_QA_2G_LOCAL_ONLY' ORDER BY `id` DESC LIMIT 1);
SET @c_sub_expired := (SELECT `id` FROM `course` WHERE `title` = 'YOUNGO_QA_SUBSCRIPTION_COURSE__TC-SUBSCRIPTION-EXPIRED__YOUNGO_QA_2G_LOCAL_ONLY' ORDER BY `id` DESC LIMIT 1);
SET @c_sub_revoked := (SELECT `id` FROM `course` WHERE `title` = 'YOUNGO_QA_SUBSCRIPTION_COURSE__TC-SUBSCRIPTION-REVOKED__YOUNGO_QA_2G_LOCAL_ONLY' ORDER BY `id` DESC LIMIT 1);
SET @c_purchase_only := (SELECT `id` FROM `course` WHERE `title` = 'YOUNGO_QA_PURCHASE_ONLY_COURSE__TC-PURCHASE-ONLY-DENIAL__YOUNGO_QA_2G_LOCAL_ONLY' ORDER BY `id` DESC LIMIT 1);
SET @c_media := (SELECT `id` FROM `course` WHERE `title` = 'YOUNGO_QA_SUBSCRIPTION_COURSE__TC-MEDIA-STREAMING__YOUNGO_QA_2G_LOCAL_ONLY' ORDER BY `id` DESC LIMIT 1);

INSERT INTO `section` (`title`, `course_id`, `start_date`, `end_date`, `restricted_by`, `order`)
VALUES
  (CONCAT(@qa_marker_utf8, _utf8'__TC-INSTRUCTOR-ACCESS__SECTION' COLLATE utf8_unicode_ci), @c_instructor, NULL, NULL, NULL, 1),
  (CONCAT(@qa_marker_utf8, _utf8'__TC-LEGACY-VALID__SECTION' COLLATE utf8_unicode_ci), @c_legacy_valid, NULL, NULL, NULL, 1),
  (CONCAT(@qa_marker_utf8, _utf8'__TC-LEGACY-EXPIRED__SECTION' COLLATE utf8_unicode_ci), @c_legacy_expired, NULL, NULL, NULL, 1),
  (CONCAT(@qa_marker_utf8, _utf8'__TC-COURSE-ACCESS-ACTIVE__SECTION' COLLATE utf8_unicode_ci), @c_access_active, NULL, NULL, NULL, 1),
  (CONCAT(@qa_marker_utf8, _utf8'__TC-COURSE-ACCESS-EXPIRED__SECTION' COLLATE utf8_unicode_ci), @c_access_expired, NULL, NULL, NULL, 1),
  (CONCAT(@qa_marker_utf8, _utf8'__TC-COURSE-ACCESS-REVOKED__SECTION' COLLATE utf8_unicode_ci), @c_access_revoked, NULL, NULL, NULL, 1),
  (CONCAT(@qa_marker_utf8, _utf8'__TC-SUBSCRIPTION-ACTIVE__SECTION' COLLATE utf8_unicode_ci), @c_sub_active, NULL, NULL, NULL, 1),
  (CONCAT(@qa_marker_utf8, _utf8'__TC-SUBSCRIPTION-EXPIRED__SECTION' COLLATE utf8_unicode_ci), @c_sub_expired, NULL, NULL, NULL, 1),
  (CONCAT(@qa_marker_utf8, _utf8'__TC-SUBSCRIPTION-REVOKED__SECTION' COLLATE utf8_unicode_ci), @c_sub_revoked, NULL, NULL, NULL, 1),
  (CONCAT(@qa_marker_utf8, _utf8'__TC-PURCHASE-ONLY-DENIAL__SECTION' COLLATE utf8_unicode_ci), @c_purchase_only, NULL, NULL, NULL, 1),
  (CONCAT(@qa_marker_utf8, _utf8'__TC-MEDIA-STREAMING__SECTION' COLLATE utf8_unicode_ci), @c_media, NULL, NULL, NULL, 1);

SET @s_instructor := (SELECT `id` FROM `section` WHERE `course_id` = @c_instructor AND `title` = CONCAT(@qa_marker_utf8, _utf8'__TC-INSTRUCTOR-ACCESS__SECTION' COLLATE utf8_unicode_ci) ORDER BY `id` DESC LIMIT 1);
SET @s_legacy_valid := (SELECT `id` FROM `section` WHERE `course_id` = @c_legacy_valid AND `title` = CONCAT(@qa_marker_utf8, _utf8'__TC-LEGACY-VALID__SECTION' COLLATE utf8_unicode_ci) ORDER BY `id` DESC LIMIT 1);
SET @s_legacy_expired := (SELECT `id` FROM `section` WHERE `course_id` = @c_legacy_expired AND `title` = CONCAT(@qa_marker_utf8, _utf8'__TC-LEGACY-EXPIRED__SECTION' COLLATE utf8_unicode_ci) ORDER BY `id` DESC LIMIT 1);
SET @s_access_active := (SELECT `id` FROM `section` WHERE `course_id` = @c_access_active AND `title` = CONCAT(@qa_marker_utf8, _utf8'__TC-COURSE-ACCESS-ACTIVE__SECTION' COLLATE utf8_unicode_ci) ORDER BY `id` DESC LIMIT 1);
SET @s_access_expired := (SELECT `id` FROM `section` WHERE `course_id` = @c_access_expired AND `title` = CONCAT(@qa_marker_utf8, _utf8'__TC-COURSE-ACCESS-EXPIRED__SECTION' COLLATE utf8_unicode_ci) ORDER BY `id` DESC LIMIT 1);
SET @s_access_revoked := (SELECT `id` FROM `section` WHERE `course_id` = @c_access_revoked AND `title` = CONCAT(@qa_marker_utf8, _utf8'__TC-COURSE-ACCESS-REVOKED__SECTION' COLLATE utf8_unicode_ci) ORDER BY `id` DESC LIMIT 1);
SET @s_sub_active := (SELECT `id` FROM `section` WHERE `course_id` = @c_sub_active AND `title` = CONCAT(@qa_marker_utf8, _utf8'__TC-SUBSCRIPTION-ACTIVE__SECTION' COLLATE utf8_unicode_ci) ORDER BY `id` DESC LIMIT 1);
SET @s_sub_expired := (SELECT `id` FROM `section` WHERE `course_id` = @c_sub_expired AND `title` = CONCAT(@qa_marker_utf8, _utf8'__TC-SUBSCRIPTION-EXPIRED__SECTION' COLLATE utf8_unicode_ci) ORDER BY `id` DESC LIMIT 1);
SET @s_sub_revoked := (SELECT `id` FROM `section` WHERE `course_id` = @c_sub_revoked AND `title` = CONCAT(@qa_marker_utf8, _utf8'__TC-SUBSCRIPTION-REVOKED__SECTION' COLLATE utf8_unicode_ci) ORDER BY `id` DESC LIMIT 1);
SET @s_purchase_only := (SELECT `id` FROM `section` WHERE `course_id` = @c_purchase_only AND `title` = CONCAT(@qa_marker_utf8, _utf8'__TC-PURCHASE-ONLY-DENIAL__SECTION' COLLATE utf8_unicode_ci) ORDER BY `id` DESC LIMIT 1);
SET @s_media := (SELECT `id` FROM `section` WHERE `course_id` = @c_media AND `title` = CONCAT(@qa_marker_utf8, _utf8'__TC-MEDIA-STREAMING__SECTION' COLLATE utf8_unicode_ci) ORDER BY `id` DESC LIMIT 1);

-- Each course gets a text lesson for Home::lesson() checks and a PDF lesson for Files::index() checks.
-- The PDF/MP4 binary files are not created by this artifact.
INSERT INTO `lesson`
  (`title`, `duration`, `course_id`, `section_id`, `video_type`, `cloud_video_id`, `video_url`, `audio_url`, `date_added`, `last_modified`, `lesson_type`, `attachment`, `attachment_type`, `caption`, `summary`, `is_free`, `order`, `quiz_attempt`, `video_type_for_mobile_application`, `video_url_for_mobile_application`, `duration_for_mobile_application`)
VALUES
  (CONCAT(@qa_marker_utf8, _utf8'__TC-INSTRUCTOR-ACCESS__TEXT' COLLATE utf8_unicode_ci), '00:01:00', @c_instructor, @s_instructor, NULL, NULL, NULL, NULL, @qa_now, @qa_now, 'text', '<p>YOUNGO_QA_2G_LOCAL_ONLY text lesson.</p>', 'description', NULL, 'QA text lesson.', 0, 1, 0, NULL, NULL, NULL),
  (CONCAT(@qa_marker_utf8, _utf8'__TC-INSTRUCTOR-ACCESS__PDF' COLLATE utf8_unicode_ci), '00:01:00', @c_instructor, @s_instructor, NULL, NULL, NULL, NULL, @qa_now, @qa_now, 'other', 'youngo_qa_2g_local_only_sample.pdf', 'pdf', NULL, 'QA PDF lesson.', 0, 2, 0, NULL, NULL, NULL),
  (CONCAT(@qa_marker_utf8, _utf8'__TC-LEGACY-VALID__TEXT' COLLATE utf8_unicode_ci), '00:01:00', @c_legacy_valid, @s_legacy_valid, NULL, NULL, NULL, NULL, @qa_now, @qa_now, 'text', '<p>YOUNGO_QA_2G_LOCAL_ONLY text lesson.</p>', 'description', NULL, 'QA text lesson.', 0, 1, 0, NULL, NULL, NULL),
  (CONCAT(@qa_marker_utf8, _utf8'__TC-LEGACY-VALID__PDF' COLLATE utf8_unicode_ci), '00:01:00', @c_legacy_valid, @s_legacy_valid, NULL, NULL, NULL, NULL, @qa_now, @qa_now, 'other', 'youngo_qa_2g_local_only_sample.pdf', 'pdf', NULL, 'QA PDF lesson.', 0, 2, 0, NULL, NULL, NULL),
  (CONCAT(@qa_marker_utf8, _utf8'__TC-LEGACY-EXPIRED__TEXT' COLLATE utf8_unicode_ci), '00:01:00', @c_legacy_expired, @s_legacy_expired, NULL, NULL, NULL, NULL, @qa_now, @qa_now, 'text', '<p>YOUNGO_QA_2G_LOCAL_ONLY text lesson.</p>', 'description', NULL, 'QA text lesson.', 0, 1, 0, NULL, NULL, NULL),
  (CONCAT(@qa_marker_utf8, _utf8'__TC-LEGACY-EXPIRED__PDF' COLLATE utf8_unicode_ci), '00:01:00', @c_legacy_expired, @s_legacy_expired, NULL, NULL, NULL, NULL, @qa_now, @qa_now, 'other', 'youngo_qa_2g_local_only_sample.pdf', 'pdf', NULL, 'QA PDF lesson.', 0, 2, 0, NULL, NULL, NULL),
  (CONCAT(@qa_marker_utf8, _utf8'__TC-COURSE-ACCESS-ACTIVE__TEXT' COLLATE utf8_unicode_ci), '00:01:00', @c_access_active, @s_access_active, NULL, NULL, NULL, NULL, @qa_now, @qa_now, 'text', '<p>YOUNGO_QA_2G_LOCAL_ONLY text lesson.</p>', 'description', NULL, 'QA text lesson.', 0, 1, 0, NULL, NULL, NULL),
  (CONCAT(@qa_marker_utf8, _utf8'__TC-COURSE-ACCESS-ACTIVE__PDF' COLLATE utf8_unicode_ci), '00:01:00', @c_access_active, @s_access_active, NULL, NULL, NULL, NULL, @qa_now, @qa_now, 'other', 'youngo_qa_2g_local_only_sample.pdf', 'pdf', NULL, 'QA PDF lesson.', 0, 2, 0, NULL, NULL, NULL),
  (CONCAT(@qa_marker_utf8, _utf8'__TC-COURSE-ACCESS-EXPIRED__TEXT' COLLATE utf8_unicode_ci), '00:01:00', @c_access_expired, @s_access_expired, NULL, NULL, NULL, NULL, @qa_now, @qa_now, 'text', '<p>YOUNGO_QA_2G_LOCAL_ONLY text lesson.</p>', 'description', NULL, 'QA text lesson.', 0, 1, 0, NULL, NULL, NULL),
  (CONCAT(@qa_marker_utf8, _utf8'__TC-COURSE-ACCESS-EXPIRED__PDF' COLLATE utf8_unicode_ci), '00:01:00', @c_access_expired, @s_access_expired, NULL, NULL, NULL, NULL, @qa_now, @qa_now, 'other', 'youngo_qa_2g_local_only_sample.pdf', 'pdf', NULL, 'QA PDF lesson.', 0, 2, 0, NULL, NULL, NULL),
  (CONCAT(@qa_marker_utf8, _utf8'__TC-COURSE-ACCESS-REVOKED__TEXT' COLLATE utf8_unicode_ci), '00:01:00', @c_access_revoked, @s_access_revoked, NULL, NULL, NULL, NULL, @qa_now, @qa_now, 'text', '<p>YOUNGO_QA_2G_LOCAL_ONLY text lesson.</p>', 'description', NULL, 'QA text lesson.', 0, 1, 0, NULL, NULL, NULL),
  (CONCAT(@qa_marker_utf8, _utf8'__TC-COURSE-ACCESS-REVOKED__PDF' COLLATE utf8_unicode_ci), '00:01:00', @c_access_revoked, @s_access_revoked, NULL, NULL, NULL, NULL, @qa_now, @qa_now, 'other', 'youngo_qa_2g_local_only_sample.pdf', 'pdf', NULL, 'QA PDF lesson.', 0, 2, 0, NULL, NULL, NULL),
  (CONCAT(@qa_marker_utf8, _utf8'__TC-SUBSCRIPTION-ACTIVE__TEXT' COLLATE utf8_unicode_ci), '00:01:00', @c_sub_active, @s_sub_active, NULL, NULL, NULL, NULL, @qa_now, @qa_now, 'text', '<p>YOUNGO_QA_2G_LOCAL_ONLY text lesson.</p>', 'description', NULL, 'QA text lesson.', 0, 1, 0, NULL, NULL, NULL),
  (CONCAT(@qa_marker_utf8, _utf8'__TC-SUBSCRIPTION-ACTIVE__PDF' COLLATE utf8_unicode_ci), '00:01:00', @c_sub_active, @s_sub_active, NULL, NULL, NULL, NULL, @qa_now, @qa_now, 'other', 'youngo_qa_2g_local_only_sample.pdf', 'pdf', NULL, 'QA PDF lesson.', 0, 2, 0, NULL, NULL, NULL),
  (CONCAT(@qa_marker_utf8, _utf8'__TC-SUBSCRIPTION-EXPIRED__TEXT' COLLATE utf8_unicode_ci), '00:01:00', @c_sub_expired, @s_sub_expired, NULL, NULL, NULL, NULL, @qa_now, @qa_now, 'text', '<p>YOUNGO_QA_2G_LOCAL_ONLY text lesson.</p>', 'description', NULL, 'QA text lesson.', 0, 1, 0, NULL, NULL, NULL),
  (CONCAT(@qa_marker_utf8, _utf8'__TC-SUBSCRIPTION-EXPIRED__PDF' COLLATE utf8_unicode_ci), '00:01:00', @c_sub_expired, @s_sub_expired, NULL, NULL, NULL, NULL, @qa_now, @qa_now, 'other', 'youngo_qa_2g_local_only_sample.pdf', 'pdf', NULL, 'QA PDF lesson.', 0, 2, 0, NULL, NULL, NULL),
  (CONCAT(@qa_marker_utf8, _utf8'__TC-SUBSCRIPTION-REVOKED__TEXT' COLLATE utf8_unicode_ci), '00:01:00', @c_sub_revoked, @s_sub_revoked, NULL, NULL, NULL, NULL, @qa_now, @qa_now, 'text', '<p>YOUNGO_QA_2G_LOCAL_ONLY text lesson.</p>', 'description', NULL, 'QA text lesson.', 0, 1, 0, NULL, NULL, NULL),
  (CONCAT(@qa_marker_utf8, _utf8'__TC-SUBSCRIPTION-REVOKED__PDF' COLLATE utf8_unicode_ci), '00:01:00', @c_sub_revoked, @s_sub_revoked, NULL, NULL, NULL, NULL, @qa_now, @qa_now, 'other', 'youngo_qa_2g_local_only_sample.pdf', 'pdf', NULL, 'QA PDF lesson.', 0, 2, 0, NULL, NULL, NULL),
  (CONCAT(@qa_marker_utf8, _utf8'__TC-PURCHASE-ONLY-DENIAL__TEXT' COLLATE utf8_unicode_ci), '00:01:00', @c_purchase_only, @s_purchase_only, NULL, NULL, NULL, NULL, @qa_now, @qa_now, 'text', '<p>YOUNGO_QA_2G_LOCAL_ONLY text lesson.</p>', 'description', NULL, 'QA text lesson.', 0, 1, 0, NULL, NULL, NULL),
  (CONCAT(@qa_marker_utf8, _utf8'__TC-PURCHASE-ONLY-DENIAL__PDF' COLLATE utf8_unicode_ci), '00:01:00', @c_purchase_only, @s_purchase_only, NULL, NULL, NULL, NULL, @qa_now, @qa_now, 'other', 'youngo_qa_2g_local_only_sample.pdf', 'pdf', NULL, 'QA PDF lesson.', 0, 2, 0, NULL, NULL, NULL),
  (CONCAT(@qa_marker_utf8, _utf8'__TC-MEDIA-STREAMING__TEXT' COLLATE utf8_unicode_ci), '00:01:00', @c_media, @s_media, NULL, NULL, NULL, NULL, @qa_now, @qa_now, 'text', '<p>YOUNGO_QA_2G_LOCAL_ONLY text lesson.</p>', 'description', NULL, 'QA text lesson.', 0, 1, 0, NULL, NULL, NULL),
  (CONCAT(@qa_marker_utf8, _utf8'__TC-MEDIA-STREAMING__PDF' COLLATE utf8_unicode_ci), '00:01:00', @c_media, @s_media, NULL, NULL, NULL, NULL, @qa_now, @qa_now, 'other', 'youngo_qa_2g_local_only_sample.pdf', 'pdf', NULL, 'QA PDF lesson.', 0, 2, 0, NULL, NULL, NULL),
  (CONCAT(@qa_marker_utf8, _utf8'__TC-MEDIA-STREAMING__MP4' COLLATE utf8_unicode_ci), '00:01:00', @c_media, @s_media, 'system', NULL, 'uploads/lesson_files/youngo_qa_2g_local_only_sample.mp4', NULL, @qa_now, @qa_now, 'video', NULL, NULL, NULL, 'QA MP4 lesson.', 0, 3, 0, NULL, NULL, NULL);

-- TC-LEGACY-VALID: user 6 has valid legacy enrolment on its own purchase-only QA course.
INSERT INTO `enrol` (`user_id`, `course_id`, `gifted_by`, `expiry_date`, `date_added`, `last_modified`)
VALUES (@qa_learner_user_id, @c_legacy_valid, 0, @qa_future, @qa_now, @qa_now);

-- TC-LEGACY-EXPIRED: user 6 has expired legacy enrolment on a different purchase-only QA course.
INSERT INTO `enrol` (`user_id`, `course_id`, `gifted_by`, `expiry_date`, `date_added`, `last_modified`)
VALUES (@qa_learner_user_id, @c_legacy_expired, 0, @qa_past, @qa_past - 86400, @qa_now);

-- TC-COURSE-ACCESS-ACTIVE: user 6 has active YounGo course access on its own subscription-eligible QA course.
INSERT INTO `youngo_course_access`
  (`user_id`, `course_id`, `access_source`, `status`, `start_date`, `expiry_date`, `is_lifetime`, `created_at`, `updated_at`, `revoked_at`)
VALUES
  (@qa_learner_user_id, @c_access_active, 'course_purchase', 'active', @qa_now, NULL, 1, @qa_now, @qa_now, NULL);

-- TC-COURSE-ACCESS-EXPIRED: user 6 has expired YounGo course access on a separate QA course.
INSERT INTO `youngo_course_access`
  (`user_id`, `course_id`, `access_source`, `status`, `start_date`, `expiry_date`, `is_lifetime`, `created_at`, `updated_at`, `revoked_at`)
VALUES
  (@qa_learner_user_id, @c_access_expired, 'course_purchase', 'active', @qa_past - 86400, @qa_past, 0, @qa_now, @qa_now, NULL);

-- TC-COURSE-ACCESS-REVOKED: user 6 has revoked YounGo course access on a separate purchase-only QA course.
-- Purchase-only configuration prevents user-level subscription state from masking the revoked course_access state.
INSERT INTO `youngo_course_access`
  (`user_id`, `course_id`, `access_source`, `status`, `start_date`, `expiry_date`, `is_lifetime`, `created_at`, `updated_at`, `revoked_at`)
VALUES
  (@qa_learner_user_id, @c_access_revoked, 'course_purchase', 'revoked', @qa_now, NULL, 1, @qa_now, @qa_now, @qa_now);

-- TC-MEDIA-STREAMING: user 6 has active YounGo course access on the media QA course.
INSERT INTO `youngo_course_access`
  (`user_id`, `course_id`, `access_source`, `status`, `start_date`, `expiry_date`, `is_lifetime`, `created_at`, `updated_at`, `revoked_at`)
VALUES
  (@qa_learner_user_id, @c_media, 'course_purchase', 'active', @qa_now, NULL, 1, @qa_now, @qa_now, NULL);

-- TC-SUBSCRIPTION-ACTIVE and TC-PURCHASE-ONLY-DENIAL: user 2 has one active subscription.
-- This subscription should allow subscription-eligible QA courses and deny purchase-only QA courses.
INSERT INTO `youngo_user_subscriptions`
  (`user_id`, `plan_id`, `source`, `status`, `start_date`, `expiry_date`, `duration_days`, `price_paid`, `currency`, `created_at`, `updated_at`, `revoked_at`)
VALUES
  (@qa_no_access_user_id, @qa_plan_id, @qa_marker_utf8mb4, 'active', @qa_now, @qa_future, 30, 0.00, 'USD', @qa_now, @qa_now, NULL);

-- TC-SUBSCRIPTION-EXPIRED: user 6 has an expired subscription. It is tested on a course with no direct user 6 access.
INSERT INTO `youngo_user_subscriptions`
  (`user_id`, `plan_id`, `source`, `status`, `start_date`, `expiry_date`, `duration_days`, `price_paid`, `currency`, `created_at`, `updated_at`, `revoked_at`)
VALUES
  (@qa_learner_user_id, @qa_plan_id, @qa_marker_utf8mb4, 'active', @qa_past - 86400, @qa_past, 30, 0.00, 'USD', @qa_now, @qa_now, NULL);

-- TC-SUBSCRIPTION-REVOKED: user 5 has a revoked subscription. It is tested on a course where user 5 is not instructor.
INSERT INTO `youngo_user_subscriptions`
  (`user_id`, `plan_id`, `source`, `status`, `start_date`, `expiry_date`, `duration_days`, `price_paid`, `currency`, `created_at`, `updated_at`, `revoked_at`)
VALUES
  (@qa_instructor_user_id, @qa_plan_id, @qa_marker_utf8mb4, 'revoked', @qa_now, @qa_future, 30, 0.00, 'USD', @qa_now, @qa_now, @qa_now);

-- Review counts after apply.
SELECT 'YOUNGO_QA_2G_LOCAL_ONLY courses' AS label, COUNT(*) AS count_value FROM `course` WHERE `meta_keywords` = @qa_marker_utf8;
SELECT 'YOUNGO_QA_2G_LOCAL_ONLY lessons' AS label, COUNT(*) AS count_value FROM `lesson` WHERE `title` LIKE @qa_marker_like_utf8 ESCAPE '\\';
SELECT 'YOUNGO_QA_2G_LOCAL_ONLY enrol' AS label, COUNT(*) AS count_value FROM `enrol` WHERE `course_id` IN (SELECT `id` FROM `course` WHERE `meta_keywords` = @qa_marker_utf8);
SELECT 'YOUNGO_QA_2G_LOCAL_ONLY course_access' AS label, COUNT(*) AS count_value FROM `youngo_course_access` WHERE `course_id` IN (SELECT `id` FROM `course` WHERE `meta_keywords` = @qa_marker_utf8);
SELECT 'YOUNGO_QA_2G_LOCAL_ONLY subscriptions' AS label, COUNT(*) AS count_value FROM `youngo_user_subscriptions` WHERE `source` = @qa_marker_utf8mb4;

COMMIT;
