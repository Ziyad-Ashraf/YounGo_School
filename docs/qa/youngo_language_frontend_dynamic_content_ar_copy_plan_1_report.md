# LANGUAGE.FRONTEND.DYNAMIC.CONTENT.AR_COPY.PLAN.1 Report

## A. Current Branch/Status

- Branch: `analysis/cms-audit`
- Start status: clean.
- Latest commit at start: `b99dedf Polish Arabic public frontend visual copy`
- This was a planning/audit-only phase.
- No deploy, push, DB write, content edit, Arabic pack import, phrase seed, route change, Paymob/payment change, checkout CTA exposure, Root Admin change, or credential printing was performed.

## B. Files Inspected

- `docs/qa/youngo_language_frontend_arabic_visual_copy_polish_1_report.md`
- `docs/qa/youngo_language_frontend_phrase_wire_remaining_1_report.md`
- `docs/qa/youngo_subscriptions_page_dynamic_ui_1_report.md`
- `docs/qa/youngo_localization_ar_default_links_1_report.md`
- `application/models/Youngo_subscription_model.php`
- `application/models/Youngo_translation_model.php`
- `application/models/Crud_model.php`
- `application/controllers/Home.php`
- `application/controllers/Blog.php`
- `application/helpers/youngo_frontend_language_helper.php`
- `application/helpers/youngo_frontend_content_helper.php`
- `application/views/frontend/youngo/`
- `application/views/backend/admin/`
- `scripts/phase_2/`

Notes:

- `application/models/Youngo_course_translation_model.php` was requested for inspection but does not exist locally.
- `application/models/Youngo_category_translation_model.php` was requested for inspection but does not exist locally.
- The reusable translation model present locally is `application/models/Youngo_translation_model.php`.

## C. Dynamic Content Source Map

| Frontend area | Current source | Current Arabic behavior |
| --- | --- | --- |
| Subscription plans | `youngo_subscription_plans` through `Youngo_subscription_model::get_public_subscription_plans()` | Falls back to canonical `name` because no subscription bilingual fields exist locally. |
| Course cards/listing | `course` plus `youngo_course_translations` | Already wired through `youngo_frontend_translate_course_rows()`. Falls back if Arabic row/field is missing. |
| Course detail | `course`, `section`, `lesson`, users/reviews | Course, section, lesson display fields are translation-aware where integrated; instructor/user names remain personal data. |
| Categories/subcategories | `category` plus `youngo_category_translations` | Already wired in course/home category display paths. |
| Sections/lessons | `section`, `lesson` plus translation tables | Course-detail curriculum display is translation-aware. Player/PDF/mobile lesson routes remain deferred. |
| Blog list/detail | `blogs`, `blog_category`, optional `youngo_blog_translations` | Blog list probes `youngo_blog_translations`; blog detail still reads raw `blog_details` fields. |
| Blog categories | `blog_category` | No `youngo_blog_category_translations` table detected. Current view has limited hardcoded category-title mapping. |
| Contact | `frontend_settings.contact_info`, `settings` fallback | Static labels are phrase-backed; contact content is not modeled as bilingual content. |
| Homepage CMS sections | `frontend_settings.youngo_homepage_content` JSON plus auto course/category/blog sources | Some manual JSON copy is localized by a static text map; auto course/category sources reuse translation shaping. |
| Instructor/author/client names | `users` | Should normally remain unchanged unless a field is marketing/display copy rather than a personal name. |

## D. Subscription Plan Localization Finding

Diagnostic table fields for `youngo_subscription_plans`:

- `id`
- `name`
- `slug`
- `duration_days`
- `price`
- `currency`
- `is_active`
- `is_purchasable`
- `is_featured`
- `sort_order`
- `created_at`
- `updated_at`
- `archived_at`
- `archived_by_user_id`

No bilingual subscription fields were detected, such as `english_name`, `arabic_name`, `name_en`, `name_ar`, or bilingual description fields.

The public model is already language-aware in shape, because it checks possible bilingual column names before falling back to the canonical field. The database schema/admin form does not currently provide those fields, so plan names such as Monthly/Yearly still come from raw plan records.

Recommendation:

- Add an additive subscription-plan translation table rather than adding many columns directly to `youngo_subscription_plans`.
- Suggested table: `youngo_subscription_plan_translations`.
- Suggested fields: `id`, `plan_id`, `language_code`, `name`, `short_description`, `description`, `created_at`, `updated_at`.
- Unique key: `plan_id + language_code`.
- Supported language codes: `english`, `arabic` only.
- Keep `youngo_subscription_plans.slug` as the operational identity for now; do not add translated public slugs until checkout/order dependencies are reviewed.
- Add bilingual fields to the admin plan form only after schema approval.

## E. Course Localization Finding

Reusable foundation exists:

- `youngo_course_translations`
- `Youngo_translation_model`
- `youngo_frontend_translate_course_row()`
- `youngo_frontend_translate_course_rows()`
- Course add/edit bilingual form support in `Crud_model`

Diagnostic counts:

- `course`: `8`
- `youngo_course_translations`: `8` English rows, `8` Arabic rows
- `arabic_translated` translation rows: `0`

Recommendation:

- Wire/QA coverage rather than add schema.
- Keep canonical course IDs and existing route IDs stable.
- Continue using canonical slug/ID links for now; translated slugs should be SEO cleanup after public route stability.
- Do not treat `course.language_made_in = arabic_translated` as UI language. It is content/media metadata only.

## F. Category/Subcategory Localization Finding

Reusable foundation exists:

- `youngo_category_translations`
- `Youngo_translation_model`
- `youngo_frontend_translate_category_row()`
- `youngo_frontend_translate_category_rows()`
- Category/subcategory admin bilingual form support in `Crud_model`

Diagnostic counts:

- `category`: `12`
- `youngo_category_translations`: `12` English rows, `12` Arabic rows
- `arabic_translated` translation rows: `0`

Recommendation:

- No schema is needed for category/subcategory public Arabic copy.
- Focus next work on route/filter QA and avoiding translated slugs as filter identity until a dedicated slug phase.

## G. Lesson/Section Localization Finding

Reusable foundation exists:

- `youngo_section_translations`
- `youngo_lesson_translations`
- `Youngo_translation_model`
- `youngo_frontend_translate_section_rows()`
- `youngo_frontend_translate_lesson_rows()`
- Section/lesson admin bilingual form support in `Crud_model`

Diagnostic counts:

- `section`: `20`
- `youngo_section_translations`: `20` English rows, `14` Arabic rows
- `lesson`: `40`
- `youngo_lesson_translations`: `40` English rows, `28` Arabic rows
- `arabic_translated` translation rows: `0`

Recommendation:

- Use the existing translation tables and admin forms.
- Fill missing Arabic section/lesson rows through approved content editing or a controlled content seed phase.
- Keep lesson/player/PDF/mobile route localization deferred because those routes are access/progress-sensitive.

## H. Blog/Content Localization Finding

Blog status:

- `blogs`: `4`
- `blog_category`: `3`
- `youngo_blog_translations` exists with `4` English rows and `4` Arabic rows.
- `youngo_blog_category_translations` does not exist.
- `blogs.php` has view-local helper logic that probes `youngo_blog_translations`.
- `blog_details.php` still renders raw `blog_details['title']`, `description`, keywords, and category title.
- Admin blog forms appear legacy-oriented and are not clearly wired to bilingual blog translation saves.

Recommendation:

- Add a dedicated `Youngo_blog_translation_model` or extend a general content translation model for blog/blog-category entities.
- Wire `Blog.php` to prepare translated rows centrally before views render.
- Update blog detail and category display to use translated data.
- Add bilingual admin fields for blog title, excerpt, description, slug, and category display labels in a later implementation phase.

Contact/homepage status:

- Contact content comes from `frontend_settings.contact_info` and `settings`; labels are phrase-backed but content itself is not bilingual.
- Homepage content comes from `frontend_settings.youngo_homepage_content` JSON; current Arabic localization depends partly on a static text map and partly on course/category shaping.

Recommendation:

- Treat homepage/contact content as CMS copy needing a bilingual content-storage plan.
- Avoid using the UI phrase table for large homepage/contact content bodies except short labels.

## I. Fields/Models Already Reusable

Reusable now:

- `Youngo_translation_model` for `course`, `category`, `section`, and `lesson`
- `youngo_frontend_content_helper.php` frontend shaping helpers
- Course/category/section/lesson admin bilingual save paths in `Crud_model`
- `Youngo_subscription_model::get_public_subscription_plans($language = null)` as a future public read adapter
- Existing `youngo_blog_translations` table for blog rows, pending proper model/controller/admin wiring

Missing or incomplete:

- No `Youngo_course_translation_model.php` wrapper exists; use `Youngo_translation_model`.
- No `Youngo_category_translation_model.php` wrapper exists; use `Youngo_translation_model`.
- No subscription-plan translation table/model/admin UI exists.
- No central blog translation model was found.
- No blog-category translation table was found.
- Contact/homepage CMS JSON is not structured as bilingual content.

## J. Missing Schema/Admin Needs

Required later schema/admin work:

- Subscription-plan translation schema and admin fields.
- Blog translation model/controller wiring and admin bilingual fields.
- Blog-category translation support if blog categories remain visible publicly.
- Homepage/contact bilingual content plan, likely via structured bilingual JSON or companion translation rows.

No schema needed for:

- Course names/descriptions.
- Category/subcategory names.
- Section titles.
- Lesson titles/summaries/text content.

Those already have translation tables and admin edit foundations.

## K. Recommended Implementation Order

1. `DYNAMIC.CONTENT.ARABIC.SUBSCRIPTIONS.SCHEMA.PLAN.1`
2. `DYNAMIC.CONTENT.ARABIC.SUBSCRIPTIONS.SCHEMA.MODEL.1`
3. `DYNAMIC.CONTENT.ARABIC.SUBSCRIPTIONS.ADMIN.UI.1`
4. `DYNAMIC.CONTENT.ARABIC.SUBSCRIPTIONS.PUBLIC.WIRE.1`
5. `DYNAMIC.CONTENT.ARABIC.COURSES.COVERAGE.QA.1`
6. `DYNAMIC.CONTENT.ARABIC.SECTIONS.LESSONS.COVERAGE.QA.1`
7. `DYNAMIC.CONTENT.ARABIC.BLOG.MODEL.ADMIN.PLAN.1`
8. `DYNAMIC.CONTENT.ARABIC.BLOG.PUBLIC.WIRE.1`
9. `DYNAMIC.CONTENT.ARABIC.HOMEPAGE.CONTACT.PLAN.1`
10. `DYNAMIC.CONTENT.ARABIC.PUBLIC.QA.1`

## L. Risks/Blockers

- Subscription plan names are public DB content and cannot be fixed through phrase labels without making plans system-static.
- Current active/purchasable subscription plan count is `3`; public cards may show English plan names until subscription translation support is implemented.
- Section and lesson Arabic rows are partial locally, so Arabic course detail can still fall back to English for missing records.
- Blog list and blog detail have inconsistent localization paths; detail page is not centrally translation-wired.
- Blog category localization is missing as schema/model support.
- Homepage/contact content has mixed CMS JSON/settings behavior and needs a storage decision before broad editing.
- Instructor and author names should generally remain unchanged; biography/title may need owner decision before translation.
- Translated slugs should be deferred until route/canonical/SEO behavior is planned.

## M. Diagnostic Result

Passed:

```text
php scripts/phase_2/youngo_language_frontend_dynamic_content_ar_copy_plan_1_diagnostic.php
```

Diagnostic highlights:

- Dynamic sources identified.
- Translation model presence detected.
- Subscription bilingual field status detected: none present.
- Course/category/section/lesson translation table status detected.
- Blog translation and blog-category translation status detected.
- `arabic_translated` rows in YounGo translation tables: `0`.
- Payment/checkout-related changed files: `0`.
- No DB writes were executed.

## N. Git Status

Final expected dirty status for this phase:

```text
?? docs/qa/youngo_language_frontend_dynamic_content_ar_copy_plan_1_report.md
?? scripts/phase_2/youngo_language_frontend_dynamic_content_ar_copy_plan_1_diagnostic.php
```

No deploy or push was performed.
