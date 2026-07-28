-- Phase 2U.6.6.3 controlled frontend phrase seed.
-- Insert only missing approved phrase keys.
-- Do not seed deferred keys.

SET NAMES utf8mb4;

INSERT INTO `language` (`phrase`, `english`, `arabic`)
SELECT 'primary_navigation', 'Primary navigation', 'التنقل الرئيسي'
WHERE NOT EXISTS (
    SELECT 1 FROM `language` WHERE `phrase` = 'primary_navigation'
);

INSERT INTO `language` (`phrase`, `english`, `arabic`)
SELECT 'language_switcher', 'Language switcher', 'مبدّل اللغة'
WHERE NOT EXISTS (
    SELECT 1 FROM `language` WHERE `phrase` = 'language_switcher'
);

INSERT INTO `language` (`phrase`, `english`, `arabic`)
SELECT 'footer_navigation', 'Footer navigation', 'روابط التذييل'
WHERE NOT EXISTS (
    SELECT 1 FROM `language` WHERE `phrase` = 'footer_navigation'
);

INSERT INTO `language` (`phrase`, `english`, `arabic`)
SELECT 'showing_results', 'Showing results', 'عرض النتائج'
WHERE NOT EXISTS (
    SELECT 1 FROM `language` WHERE `phrase` = 'showing_results'
);
