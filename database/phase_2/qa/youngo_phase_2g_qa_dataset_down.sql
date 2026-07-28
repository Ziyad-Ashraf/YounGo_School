-- YounGo Phase 2G.6 controlled QA dataset cleanup artifact.
-- REVIEW ONLY. DO NOT APPLY unless a later phase explicitly approves local DB fixture cleanup.
-- LOCAL ONLY. DO NOT RUN ON PRODUCTION OR SERVER.
-- Removes only rows scoped by YOUNGO_QA_2G_LOCAL_ONLY marker or QA course IDs.

START TRANSACTION;

SET NAMES utf8 COLLATE utf8_unicode_ci;

-- Keep marker comparisons collation-safe across legacy utf8 LMS tables and
-- YounGo utf8mb4 entitlement tables.
SET @qa_marker_utf8 := _utf8'YOUNGO_QA_2G_LOCAL_ONLY' COLLATE utf8_unicode_ci;
SET @qa_marker_utf8mb4 := _utf8mb4'YOUNGO_QA_2G_LOCAL_ONLY' COLLATE utf8mb4_unicode_ci;
SET @qa_marker_like_utf8 := _utf8'YOUNGO\_QA\_2G\_LOCAL\_ONLY%' COLLATE utf8_unicode_ci;
SET @qa_subscription_course_like_utf8 := _utf8'YOUNGO\_QA\_SUBSCRIPTION\_COURSE\_\_%' COLLATE utf8_unicode_ci;
SET @qa_purchase_course_like_utf8 := _utf8'YOUNGO\_QA\_PURCHASE\_ONLY\_COURSE\_\_%' COLLATE utf8_unicode_ci;

-- Review the rows that will be removed.
SELECT `id`, `title` FROM `course` WHERE `meta_keywords` = @qa_marker_utf8 ORDER BY `id`;

-- Cleanup order:
-- 1. watch/progress rows scoped to QA users/courses.
DELETE FROM `watched_duration`
WHERE `watched_course_id` IN (SELECT `id` FROM `course` WHERE `meta_keywords` = @qa_marker_utf8);

DELETE FROM `watch_histories`
WHERE `course_id` IN (SELECT `id` FROM `course` WHERE `meta_keywords` = @qa_marker_utf8);

-- 2. YounGo subscriptions created by this QA marker.
DELETE FROM `youngo_user_subscriptions`
WHERE `source` = @qa_marker_utf8mb4;

-- 3. YounGo course access scoped to QA courses.
DELETE FROM `youngo_course_access`
WHERE `course_id` IN (SELECT `id` FROM `course` WHERE `meta_keywords` = @qa_marker_utf8);

-- 4. Legacy enrol rows scoped to QA courses.
DELETE FROM `enrol`
WHERE `course_id` IN (SELECT `id` FROM `course` WHERE `meta_keywords` = @qa_marker_utf8);

-- 5. Lessons scoped by QA course IDs or QA marker title.
DELETE FROM `lesson`
WHERE `course_id` IN (SELECT `id` FROM `course` WHERE `meta_keywords` = @qa_marker_utf8)
   OR `title` LIKE @qa_marker_like_utf8 ESCAPE '\\';

-- 6. Sections scoped by QA course IDs or QA marker title.
DELETE FROM `section`
WHERE `course_id` IN (SELECT `id` FROM `course` WHERE `meta_keywords` = @qa_marker_utf8)
   OR `title` LIKE @qa_marker_like_utf8 ESCAPE '\\';

-- 7. Courses scoped by QA marker.
DELETE FROM `course`
WHERE `meta_keywords` = @qa_marker_utf8
  AND (`title` LIKE @qa_subscription_course_like_utf8 ESCAPE '\\'
       OR `title` LIKE @qa_purchase_course_like_utf8 ESCAPE '\\');

-- 8. Temporary media files are not removed by SQL.
-- If created during a later approved QA phase, manually delete:
-- uploads/lesson_files/youngo_qa_2g_local_only_sample.pdf
-- uploads/lesson_files/youngo_qa_2g_local_only_sample.mp4

-- Review counts after cleanup. Expected count_value = 0 for each row.
SELECT 'YOUNGO_QA_2G_LOCAL_ONLY courses' AS label, COUNT(*) AS count_value FROM `course` WHERE `meta_keywords` = @qa_marker_utf8;
SELECT 'YOUNGO_QA_2G_LOCAL_ONLY lessons' AS label, COUNT(*) AS count_value FROM `lesson` WHERE `title` LIKE @qa_marker_like_utf8 ESCAPE '\\';
SELECT 'YOUNGO_QA_2G_LOCAL_ONLY subscriptions' AS label, COUNT(*) AS count_value FROM `youngo_user_subscriptions` WHERE `source` = @qa_marker_utf8mb4;

COMMIT;
