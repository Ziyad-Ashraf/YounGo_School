-- Phase 2U.3 rollback: remove YounGo localization translation tables.
--
-- This intentionally affects only Phase 2U.3 translation tables.
-- Prefer full DB backup restore for QA rollback when any data seeding was performed.

DROP TABLE IF EXISTS `youngo_lesson_translations`;
DROP TABLE IF EXISTS `youngo_section_translations`;
DROP TABLE IF EXISTS `youngo_category_translations`;
DROP TABLE IF EXISTS `youngo_course_translations`;
