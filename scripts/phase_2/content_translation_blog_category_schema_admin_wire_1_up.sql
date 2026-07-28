CREATE TABLE IF NOT EXISTS `youngo_blog_category_translations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `blog_category_id` int(11) NOT NULL,
  `language_code` varchar(20) NOT NULL,
  `title` varchar(255) NOT NULL,
  `subtitle` varchar(500) DEFAULT NULL,
  `display_slug` varchar(255) DEFAULT NULL,
  `created_by_user_id` int(11) DEFAULT NULL,
  `updated_by_user_id` int(11) DEFAULT NULL,
  `created_at` int(11) DEFAULT NULL,
  `updated_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_ybct_category_language` (`blog_category_id`, `language_code`),
  KEY `idx_ybct_language` (`language_code`),
  KEY `idx_ybct_display_slug` (`display_slug`),
  CONSTRAINT `chk_ybct_language_code` CHECK (`language_code` IN ('english', 'arabic'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
