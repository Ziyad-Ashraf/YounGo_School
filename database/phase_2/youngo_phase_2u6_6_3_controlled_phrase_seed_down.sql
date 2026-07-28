-- Phase 2U.6.6.3 controlled frontend phrase seed rollback.
-- Remove only approved phrase rows whose values still match the seed.
-- Do not remove rows changed later and do not touch deferred keys.

SET NAMES utf8mb4;

DELETE FROM `language`
WHERE `phrase` = 'primary_navigation'
  AND `english` = 'Primary navigation'
  AND `arabic` = 'التنقل الرئيسي';

DELETE FROM `language`
WHERE `phrase` = 'language_switcher'
  AND `english` = 'Language switcher'
  AND `arabic` = 'مبدّل اللغة';

DELETE FROM `language`
WHERE `phrase` = 'footer_navigation'
  AND `english` = 'Footer navigation'
  AND `arabic` = 'روابط التذييل';

DELETE FROM `language`
WHERE `phrase` = 'showing_results'
  AND `english` = 'Showing results'
  AND `arabic` = 'عرض النتائج';
