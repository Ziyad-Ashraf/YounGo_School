-- YounGo Phase 2H Demo Data Alignment - UP
--
-- LOCAL/DEMO ONLY.
-- Do not apply until explicitly approved.
--
-- Goal:
-- - Create missing demo subcategories under parent categories 2, 3, 4, and 6.
-- - Align existing demo courses 1-6 so each has a valid sub_category_id under
--   its existing parent category.
--
-- Safety boundaries:
-- - Marker: YOUNGO_DEMO_ALIGNMENT_2H
-- - Does not create or modify users.
-- - Does not alter passwords.
-- - Does not create sessions.
-- - Does not touch Phase 2G QA marker data.
-- - Does not modify course 9.
-- - Does not change is_free_course, price, discounted_price, or YounGo access fields.

SET NAMES utf8 COLLATE utf8_unicode_ci;
START TRANSACTION;

SET @demo_marker := _utf8'YOUNGO_DEMO_ALIGNMENT_2H' COLLATE utf8_unicode_ci;
SET @now := UNIX_TIMESTAMP();

SET @code_science := _utf8'YOUNGO_DEMO_ALIGNMENT_2H__SCIENCE_FOUNDATIONS' COLLATE utf8_unicode_ci;
SET @code_creative := _utf8'YOUNGO_DEMO_ALIGNMENT_2H__CREATIVE_FOUNDATIONS' COLLATE utf8_unicode_ci;
SET @code_reading := _utf8'YOUNGO_DEMO_ALIGNMENT_2H__READING_FOUNDATIONS' COLLATE utf8_unicode_ci;
SET @code_life := _utf8'YOUNGO_DEMO_ALIGNMENT_2H__LIFE_SKILLS_FOUNDATIONS' COLLATE utf8_unicode_ci;

SET @slug_science := _utf8'youngo-demo-alignment-2h-science-foundations' COLLATE utf8_unicode_ci;
SET @slug_creative := _utf8'youngo-demo-alignment-2h-creative-foundations' COLLATE utf8_unicode_ci;
SET @slug_reading := _utf8'youngo-demo-alignment-2h-reading-foundations' COLLATE utf8_unicode_ci;
SET @slug_life := _utf8'youngo-demo-alignment-2h-life-skills-foundations' COLLATE utf8_unicode_ci;

-- Preflight: expected parent categories and existing demo courses.
SELECT id, name, parent, slug, code
FROM category
WHERE id IN (1, 2, 3, 4, 5, 6, 7, 8)
ORDER BY id;

SELECT id, title, category_id, sub_category_id, is_free_course, price, discounted_price,
       youngo_access_mode, youngo_allow_individual_purchase, youngo_subscription_excluded
FROM course
WHERE id IN (1, 2, 3, 4, 5, 6, 9)
ORDER BY id;

-- Create one marked child subcategory for each parent category currently missing one.
-- Existing child categories 7 and 8 are reused for courses under parent categories 1 and 5.
INSERT INTO category
    (code, name, parent, slug, date_added, last_modified, font_awesome_class, thumbnail, sub_category_thumbnail)
SELECT
    @code_science,
    _utf8'Science Foundations' COLLATE utf8_unicode_ci,
    2,
    @slug_science,
    @now,
    @now,
    _utf8'fas fa-flask' COLLATE utf8_unicode_ci,
    NULL,
    NULL
WHERE EXISTS (SELECT 1 FROM category WHERE id = 2 AND parent = 0)
  AND NOT EXISTS (
      SELECT 1
      FROM category
      WHERE parent = 2
        AND (code = @code_science OR slug = @slug_science)
  );

INSERT INTO category
    (code, name, parent, slug, date_added, last_modified, font_awesome_class, thumbnail, sub_category_thumbnail)
SELECT
    @code_creative,
    _utf8'Creative Foundations' COLLATE utf8_unicode_ci,
    3,
    @slug_creative,
    @now,
    @now,
    _utf8'fas fa-palette' COLLATE utf8_unicode_ci,
    NULL,
    NULL
WHERE EXISTS (SELECT 1 FROM category WHERE id = 3 AND parent = 0)
  AND NOT EXISTS (
      SELECT 1
      FROM category
      WHERE parent = 3
        AND (code = @code_creative OR slug = @slug_creative)
  );

INSERT INTO category
    (code, name, parent, slug, date_added, last_modified, font_awesome_class, thumbnail, sub_category_thumbnail)
SELECT
    @code_reading,
    _utf8'Reading Foundations' COLLATE utf8_unicode_ci,
    4,
    @slug_reading,
    @now,
    @now,
    _utf8'fas fa-book-open' COLLATE utf8_unicode_ci,
    NULL,
    NULL
WHERE EXISTS (SELECT 1 FROM category WHERE id = 4 AND parent = 0)
  AND NOT EXISTS (
      SELECT 1
      FROM category
      WHERE parent = 4
        AND (code = @code_reading OR slug = @slug_reading)
  );

INSERT INTO category
    (code, name, parent, slug, date_added, last_modified, font_awesome_class, thumbnail, sub_category_thumbnail)
SELECT
    @code_life,
    _utf8'Life Skills Foundations' COLLATE utf8_unicode_ci,
    6,
    @slug_life,
    @now,
    @now,
    _utf8'fas fa-seedling' COLLATE utf8_unicode_ci,
    NULL,
    NULL
WHERE EXISTS (SELECT 1 FROM category WHERE id = 6 AND parent = 0)
  AND NOT EXISTS (
      SELECT 1
      FROM category
      WHERE parent = 6
        AND (code = @code_life OR slug = @slug_life)
  );

SET @sub_coding := (SELECT id FROM category WHERE id = 7 AND parent = 1 LIMIT 1);
SET @sub_math := (SELECT id FROM category WHERE id = 8 AND parent = 5 LIMIT 1);
SET @sub_science := (SELECT id FROM category WHERE parent = 2 AND code = @code_science ORDER BY id DESC LIMIT 1);
SET @sub_creative := (SELECT id FROM category WHERE parent = 3 AND code = @code_creative ORDER BY id DESC LIMIT 1);
SET @sub_reading := (SELECT id FROM category WHERE parent = 4 AND code = @code_reading ORDER BY id DESC LIMIT 1);
SET @sub_life := (SELECT id FROM category WHERE parent = 6 AND code = @code_life ORDER BY id DESC LIMIT 1);

-- Align courses 1-6 with valid subcategories under their existing parent categories.
-- These updates are intentionally guarded by course id, current category_id, and sub_category_id=0.
UPDATE course
SET sub_category_id = @sub_coding
WHERE id = 1
  AND category_id = 1
  AND sub_category_id = 0
  AND @sub_coding IS NOT NULL;

UPDATE course
SET sub_category_id = @sub_science
WHERE id = 2
  AND category_id = 2
  AND sub_category_id = 0
  AND @sub_science IS NOT NULL;

UPDATE course
SET sub_category_id = @sub_creative
WHERE id = 3
  AND category_id = 3
  AND sub_category_id = 0
  AND @sub_creative IS NOT NULL;

UPDATE course
SET sub_category_id = @sub_math
WHERE id = 4
  AND category_id = 5
  AND sub_category_id = 0
  AND @sub_math IS NOT NULL;

UPDATE course
SET sub_category_id = @sub_reading
WHERE id = 5
  AND category_id = 4
  AND sub_category_id = 0
  AND @sub_reading IS NOT NULL;

UPDATE course
SET sub_category_id = @sub_life
WHERE id = 6
  AND category_id = 6
  AND sub_category_id = 0
  AND @sub_life IS NOT NULL;

-- Validation: each aligned course should now point to a subcategory whose parent
-- equals the course parent category.
SELECT c.id, c.title, c.category_id, c.sub_category_id, sc.name AS sub_category_name,
       sc.parent AS sub_category_parent,
       CASE WHEN sc.parent = c.category_id THEN 'ok' ELSE 'check' END AS relation_status
FROM course c
LEFT JOIN category sc ON sc.id = c.sub_category_id
WHERE c.id IN (1, 2, 3, 4, 5, 6, 9)
ORDER BY c.id;

-- Deliberately deferred:
-- - Course 9 remains untouched. Future options: archive/deactivate it, complete it
--   with sections/lessons/thumbnail, or keep it as a manual local test artifact.
-- - Courses 1-6 keep existing is_free_course/pricing values. Free-course versus
--   YounGo subscription-mode alignment needs a separate access/business decision.

COMMIT;
