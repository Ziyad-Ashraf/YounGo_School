-- YounGo Phase 2H Demo Data Alignment - DOWN
--
-- LOCAL/DEMO ONLY.
-- Do not apply until explicitly approved.
--
-- Goal:
-- - Revert only the Phase 2H demo subcategory alignment for courses 1-6.
-- - Remove only marked subcategories created by the Phase 2H alignment artifact,
--   and only after they are no longer referenced by courses.
--
-- Safety boundaries:
-- - Marker: YOUNGO_DEMO_ALIGNMENT_2H
-- - Does not delete courses 1-6.
-- - Does not delete existing parent categories.
-- - Does not delete existing pre-alignment subcategories 7 or 8.
-- - Does not modify users, passwords, sessions, or QA marker data.

SET NAMES utf8 COLLATE utf8_unicode_ci;
START TRANSACTION;

SET @code_science := _utf8'YOUNGO_DEMO_ALIGNMENT_2H__SCIENCE_FOUNDATIONS' COLLATE utf8_unicode_ci;
SET @code_creative := _utf8'YOUNGO_DEMO_ALIGNMENT_2H__CREATIVE_FOUNDATIONS' COLLATE utf8_unicode_ci;
SET @code_reading := _utf8'YOUNGO_DEMO_ALIGNMENT_2H__READING_FOUNDATIONS' COLLATE utf8_unicode_ci;
SET @code_life := _utf8'YOUNGO_DEMO_ALIGNMENT_2H__LIFE_SKILLS_FOUNDATIONS' COLLATE utf8_unicode_ci;

SET @sub_coding := (SELECT id FROM category WHERE id = 7 AND parent = 1 LIMIT 1);
SET @sub_math := (SELECT id FROM category WHERE id = 8 AND parent = 5 LIMIT 1);
SET @sub_science := (SELECT id FROM category WHERE parent = 2 AND code = @code_science ORDER BY id DESC LIMIT 1);
SET @sub_creative := (SELECT id FROM category WHERE parent = 3 AND code = @code_creative ORDER BY id DESC LIMIT 1);
SET @sub_reading := (SELECT id FROM category WHERE parent = 4 AND code = @code_reading ORDER BY id DESC LIMIT 1);
SET @sub_life := (SELECT id FROM category WHERE parent = 6 AND code = @code_life ORDER BY id DESC LIMIT 1);

-- Revert only the known course/subcategory alignments made by the UP artifact.
UPDATE course
SET sub_category_id = 0
WHERE id = 1
  AND category_id = 1
  AND sub_category_id = @sub_coding
  AND @sub_coding IS NOT NULL;

UPDATE course
SET sub_category_id = 0
WHERE id = 2
  AND category_id = 2
  AND sub_category_id = @sub_science
  AND @sub_science IS NOT NULL;

UPDATE course
SET sub_category_id = 0
WHERE id = 3
  AND category_id = 3
  AND sub_category_id = @sub_creative
  AND @sub_creative IS NOT NULL;

UPDATE course
SET sub_category_id = 0
WHERE id = 4
  AND category_id = 5
  AND sub_category_id = @sub_math
  AND @sub_math IS NOT NULL;

UPDATE course
SET sub_category_id = 0
WHERE id = 5
  AND category_id = 4
  AND sub_category_id = @sub_reading
  AND @sub_reading IS NOT NULL;

UPDATE course
SET sub_category_id = 0
WHERE id = 6
  AND category_id = 6
  AND sub_category_id = @sub_life
  AND @sub_life IS NOT NULL;

-- Remove only Phase 2H-created subcategories, never existing category ids 7 or 8.
-- Each delete is guarded so referenced categories are preserved for manual review.
DELETE FROM category
WHERE parent = 2
  AND code = @code_science
  AND NOT EXISTS (SELECT 1 FROM course WHERE sub_category_id = category.id);

DELETE FROM category
WHERE parent = 3
  AND code = @code_creative
  AND NOT EXISTS (SELECT 1 FROM course WHERE sub_category_id = category.id);

DELETE FROM category
WHERE parent = 4
  AND code = @code_reading
  AND NOT EXISTS (SELECT 1 FROM course WHERE sub_category_id = category.id);

DELETE FROM category
WHERE parent = 6
  AND code = @code_life
  AND NOT EXISTS (SELECT 1 FROM course WHERE sub_category_id = category.id);

-- Validation: expected rollback state is sub_category_id=0 for courses 1-6.
SELECT c.id, c.title, c.category_id, c.sub_category_id
FROM course c
WHERE c.id IN (1, 2, 3, 4, 5, 6, 9)
ORDER BY c.id;

-- Validation: expected marker count is zero unless a marked subcategory was
-- intentionally preserved because another course references it.
SELECT id, name, parent, slug, code
FROM category
WHERE code IN (@code_science, @code_creative, @code_reading, @code_life)
ORDER BY id;

COMMIT;
