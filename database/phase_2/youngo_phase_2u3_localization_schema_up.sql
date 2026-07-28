-- Phase 2U.3: YounGo localization schema foundation.
--
-- Scope:
-- - Add YounGo-specific translation tables for canonical Academy LMS entities.
-- - Preserve canonical course/category/section/lesson IDs.
-- - Do not alter canonical tables, language settings, entitlement tables, checkout, payment, or enrolment data.
-- - No hard foreign keys are added, following the current Phase 2 local migration style.

CREATE TABLE IF NOT EXISTS `youngo_course_translations` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `course_id` int(11) unsigned NOT NULL,
  `language_code` varchar(20) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `slug` varchar(255) DEFAULT NULL,
  `short_description` longtext DEFAULT NULL,
  `description` longtext DEFAULT NULL,
  `outcomes` longtext DEFAULT NULL,
  `requirements` longtext DEFAULT NULL,
  `faqs` longtext DEFAULT NULL,
  `seo_title` varchar(255) DEFAULT NULL,
  `meta_keywords` longtext DEFAULT NULL,
  `meta_description` longtext DEFAULT NULL,
  `created_at` int(11) DEFAULT NULL,
  `updated_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_yct_course_language` (`course_id`, `language_code`),
  KEY `idx_yct_language_code` (`language_code`),
  KEY `idx_yct_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `youngo_category_translations` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `category_id` int(11) unsigned NOT NULL,
  `language_code` varchar(20) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `slug` varchar(255) DEFAULT NULL,
  `description` longtext DEFAULT NULL,
  `created_at` int(11) DEFAULT NULL,
  `updated_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_ycat_category_language` (`category_id`, `language_code`),
  KEY `idx_ycat_language_code` (`language_code`),
  KEY `idx_ycat_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `youngo_section_translations` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `section_id` int(11) NOT NULL,
  `language_code` varchar(20) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `created_at` int(11) DEFAULT NULL,
  `updated_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_yst_section_language` (`section_id`, `language_code`),
  KEY `idx_yst_language_code` (`language_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `youngo_lesson_translations` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `lesson_id` int(11) unsigned NOT NULL,
  `language_code` varchar(20) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `summary` longtext DEFAULT NULL,
  `text_content` longtext DEFAULT NULL,
  `created_at` int(11) DEFAULT NULL,
  `updated_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_ylt_lesson_language` (`lesson_id`, `language_code`),
  KEY `idx_ylt_language_code` (`language_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
