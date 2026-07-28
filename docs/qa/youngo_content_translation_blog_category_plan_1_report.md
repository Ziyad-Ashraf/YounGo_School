# CONTENT.TRANSLATION.BLOG.CATEGORY.PLAN.1 Report

Phase: `CONTENT.TRANSLATION.BLOG.CATEGORY.PLAN.1 - Plan Blog Category Localization Reuse`

Date: 2026-07-26

Scope: planning only. No deploy, push, DB write, schema creation, Blog content edit, Blog category content edit, route/slug change, payment/Paymob change, checkout CTA exposure, Arabic pack import, or Root Admin change was performed.

## A. Current Branch/Status

Starting branch:

```text
analysis/cms-audit
```

Starting worktree:

```text
clean
```

Latest commit at start:

```text
2ae7372 Wire YounGo blog detail translations
```

## B. Files Inspected

Reports read:

- `docs/qa/youngo_content_translation_reuse_audit_1_report.md`
- `docs/qa/youngo_content_translation_blog_detail_wire_1_report.md`

Source inspected:

- `application/controllers/Blog.php`
- `application/controllers/Admin.php`
- `application/controllers/Sitemap.php`
- `application/models/Crud_model.php`
- `application/views/frontend/youngo/blogs.php`
- `application/views/frontend/youngo/blog_details.php`
- `application/views/backend/admin/blog.php`
- `application/views/backend/admin/blog_category.php`
- `application/views/backend/admin/blog_category_add.php`
- `application/views/backend/admin/blog_category_edit.php`
- `application/views/backend/admin/blog_add.php`
- `application/views/backend/admin/blog_edit.php`
- `application/config/routes.php`
- `scripts/phase_2/`

Note: `application/views/frontend/youngo/blog.php` was requested but does not exist. The active YounGo public Blog list/category view is `application/views/frontend/youngo/blogs.php`.

## C. Current Blog Category Storage/Rendering

DB storage:

```text
blog_category
```

Current columns:

```text
blog_category_id
title
subtitle
slug
added_date
```

Current local counts:

```text
blog_category rows: 3
blogs rows: 4
blogs rows with category: 4
```

Controller/model methods:

- `Admin::add_blog_category()` opens the add modal.
- `Admin::edit_blog_category($blog_category_id)` opens the edit modal.
- `Admin::blog_category('add')` calls `Crud_model::add_blog_category()`.
- `Admin::blog_category('update')` calls `Crud_model::update_blog_category($id)`.
- `Admin::blog_category('delete')` calls `Crud_model::delete_blog_category($id)`.
- `Crud_model::get_blog_categories($id)` reads categories.
- `Crud_model::get_blog_category_by_slug($slug)` resolves the filter slug.
- `Crud_model::get_blogs_by_category_id($id)` counts/list-supports category-linked Blog posts.

Admin forms:

- `blog_category_add.php` posts `title` and `subtitle` to `admin/blog_category/add`.
- `blog_category_edit.php` posts `title` and `subtitle` to `admin/blog_category/update/{id}`.
- Existing create/edit methods regenerate canonical `slug` from canonical `title`.
- Blog add/edit post forms select categories by `blog_category_id` and display canonical `title`.

Frontend usage:

- Blog list/card category label calls `youngo_blog_category_title()` in `blogs.php`.
- Arabic list/card category labels currently use a limited view-local map for `Parent Guides`, `Learning Tips`, and `Future Skills`.
- Blog categories page in `blogs.php` renders raw `title` and `subtitle`.
- Blog detail eyebrow in `blog_details.php` calls `youngo_blog_detail_category_title()` and returns raw `blog_category.title`.
- Category filters use `?category={canonical_slug}` and resolve through `Crud_model::get_blog_category_by_slug()`.
- Sitemap Blog category URLs use canonical `blog_category.slug`.

Search/filter:

- Category filtering uses canonical category slug only.
- Blog search queries canonical `blogs.title` and `blogs.description`; it does not search Blog translation rows or Blog category labels.

## D. Existing Translation Support Finding

No reusable Blog category translation support exists today.

Diagnostic findings:

```text
youngo_blog_category_translations: missing
blog_category_translations: missing
blog_categories_translations: missing
admin bilingual Blog category inputs: missing
Crud_model Blog category translation methods: missing
frontend reusable Blog category lookup: missing
view-local Arabic category map: present
```

The view-local map is useful only for the three current demo names. It is not reusable content infrastructure and should not be treated as the final localization model.

## E. Recommended Localization Approach

Recommended approach: **A. Add an additive `youngo_blog_category_translations` table in the next implementation phase.**

Comparison:

| Option | Assessment |
|---|---|
| A. Additive Blog category translation table | Recommended. Matches existing YounGo translation-table pattern, preserves canonical Blog category IDs/slugs, avoids route changes, and keeps dynamic CMS content out of phrase rows. |
| B. Add bilingual fields directly to `blog_category` | Not recommended. It mutates the legacy table, scales poorly if fields/languages expand, and does not match the existing YounGo additive localization strategy. |
| C. Suppress category labels on Arabic pages until storage exists | Safe as a temporary fallback only, but it lowers visible content quality and does not solve Blog category pages or filters. |
| D. Treat category labels as static phrases | Not recommended. Blog categories are CMS content, not fixed UI labels; using phrase rows would mix dynamic content with language-pack labels and repeat past phrase-table problems. |

Recommended future table shape:

```text
youngo_blog_category_translations
id
blog_category_id
language_code
title
subtitle
slug
created_at
updated_at
```

Recommended indexes:

- Unique key on `(blog_category_id, language_code)`.
- Index on `language_code`.
- Optional index on translated `slug` for future display/SEO use only.

Important boundary: canonical filter URLs should keep using `blog_category.slug`. Translated slugs can be stored for future display/SEO, but should not change current routes in the first implementation.

## F. Fallback Behavior

Recommended behavior:

- Arabic/default: Arabic category translation if present, else canonical `blog_category.title` / `subtitle`.
- `/ar`: same as Arabic/default.
- `/en`: English category translation if present, else canonical `blog_category.title` / `subtitle`.
- Slugs and IDs remain canonical for filtering, sitemap, Blog post relationships, and admin selection.
- `arabic_translated` must never be accepted or stored as a Blog category UI/content language code.

This fallback differs from Blog post Arabic listing behavior, which currently skips untranslated Arabic posts. For categories, canonical fallback is safer because categories are navigational labels and hiding them can remove filter/category affordances.

## G. Admin UI Plan

Recommended admin surfaces:

- `application/views/backend/admin/blog_category_add.php`
- `application/views/backend/admin/blog_category_edit.php`
- Optionally `application/views/backend/admin/blog_category.php` for displaying canonical plus translation coverage badges later.

Recommended fields:

- English title, required.
- English subtitle, optional, max 80 to preserve current UI constraint.
- English slug, optional or auto-generated.
- Arabic title, optional, RTL.
- Arabic subtitle, optional, RTL, max 80.
- Arabic slug, optional or auto-generated for future display only.

Validation/preservation:

- Preserve canonical `blog_category.title` and `blog_category.slug` from English/canonical values for legacy compatibility.
- Keep `blog_category_id` as the relationship key from Blog posts.
- Keep existing duplicate canonical slug rejection.
- Create/update English translation rows alongside canonical saves.
- Create/update Arabic translation rows only when Arabic title/subtitle/slug is non-empty.
- Do not delete existing Arabic rows when a field is submitted blank unless a later phase explicitly defines translation deletion behavior.
- Do not change Blog post translation save paths or Blog post admin forms except category labels in selects if needed.

Recommended model path:

- Add narrow Blog category translation methods to `Crud_model` or a small reusable Blog content helper only if preferred.
- Reuse the existing project pattern from category/course/Blog post translation methods rather than creating a large new Blog subsystem.

## H. Frontend Wiring Plan

Recommended frontend changes:

- Add a reusable Blog category translation resolver, for example `youngo_blog_category_translation_row()` or a controller/model helper method.
- Replace the view-local Arabic map in `blogs.php` with the resolver.
- Blog list/card labels should display localized category title with canonical fallback.
- Blog categories page should display localized category title and subtitle with canonical fallback.
- Blog detail eyebrow should display localized category title with canonical fallback.
- Category links should continue using canonical `?category={blog_category.slug}`.
- Existing `/en` and `/ar` route behavior should not change.

Recommended source touch points for implementation:

- `application/models/Crud_model.php`
- `application/views/backend/admin/blog_category_add.php`
- `application/views/backend/admin/blog_category_edit.php`
- `application/views/frontend/youngo/blogs.php`
- `application/views/frontend/youngo/blog_details.php`
- A new read-only/write-safe diagnostic under `scripts/phase_2/`

## I. Search/Filter Notes

Category filter:

- Keep `?category={canonical_slug}`.
- Continue resolving through `Crud_model::get_blog_category_by_slug()`.
- Do not introduce translated slugs into filtering in the first implementation.

Search:

- Current Blog search queries canonical `blogs.title` and `blogs.description`.
- Blog category localization does not need to change search.
- Translation-aware Blog search can be planned separately if Arabic users need search over `youngo_blog_translations` or future Blog category translations.

Sitemap:

- Keep canonical Blog category URLs using existing `blog_category.slug`.
- Do not add language-prefixed or translated-category sitemap URLs in this phase.

## J. Risks/Blockers

- Blog detail category eyebrow can still show English canonical category text until category localization is implemented.
- Blog categories page currently renders raw `title` and `subtitle`.
- The view-local Arabic map only covers current demo category names and will fail for new categories.
- Directly changing canonical slugs would break existing category filter URLs; the plan avoids that.
- Adding a translation table requires a future schema phase with backup/restore discipline; this planning phase intentionally did not create it.

## K. Recommended Next Phase

Recommended next phase:

```text
CONTENT.TRANSLATION.BLOG.CATEGORY.SCHEMA.ADMIN.WIRE.1
```

Suggested scope:

- Add additive `youngo_blog_category_translations` schema.
- Add admin bilingual Blog category fields.
- Add model save/read helpers.
- Wire Blog list/category/detail category labels to translated values.
- Preserve canonical Blog category IDs/slugs and existing Blog post translation behavior.

## L. Diagnostic Result

Added diagnostic:

```text
scripts/phase_2/youngo_content_translation_blog_category_plan_1_diagnostic.php
```

Result:

```text
php -l scripts/phase_2/youngo_content_translation_blog_category_plan_1_diagnostic.php
No syntax errors detected

php scripts/phase_2/youngo_content_translation_blog_category_plan_1_diagnostic.php
PASS: Blog category localization planning diagnostic completed read-only.
```

The diagnostic verified:

- Current `blog_category` columns and row count.
- Current `blogs.blog_category_id` usage.
- Absence of Blog category translation tables.
- Absence of Blog category bilingual admin fields.
- Absence of Blog category translation model/helper methods.
- Current frontend usage points in Blog list/cards, Blog category page, Blog detail eyebrow, filter, and sitemap.
- Existing view-local Arabic category map.
- No DB writes.
- No payment/Paymob/checkout/coupon/cart changes.
- No `arabic_translated` usage in Blog category sources or routes.

## M. Git Status

Expected final git status for this planning phase:

```text
?? docs/qa/youngo_content_translation_blog_category_plan_1_report.md
?? scripts/phase_2/youngo_content_translation_blog_category_plan_1_diagnostic.php
```
