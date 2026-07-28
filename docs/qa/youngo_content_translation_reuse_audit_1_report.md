# CONTENT.TRANSLATION.REUSE.AUDIT.1 Report

Phase: `CONTENT.TRANSLATION.REUSE.AUDIT.1 - Audit Existing Bilingual Content Translation Infrastructure`

Date: 2026-07-26

Scope: audit/planning only. No deployment, push, DB write, content edit, schema creation, phrase seed/import, route change, payment/Paymob change, checkout CTA exposure, Root Admin change, or Arabic pack import was performed.

## A. Current Branch/Status

Starting branch:

```text
analysis/cms-audit
```

Starting worktree was clean.

Recent expected commits were present:

```text
b02ced1 QA Arabic public auth copy
ad62df3 Wire Arabic public auth copy
b1d4064 Audit Arabic public frontend localization
283b9f1 QA localized subscription plan public pages
bfc5223 QA bilingual subscription plan copy entry
74c9b8d Add admin bilingual subscription plan translation fields
97d74c1 Wire localized subscription plan public model
61238b4 Add subscription plan translation schema foundation
```

## B. Files/Reports Inspected

Required reports inspected:

- `docs/qa/youngo_language_frontend_dynamic_content_ar_copy_plan_1_report.md`
- `docs/qa/youngo_dynamic_content_arabic_public_localization_qa_1_report.md`
- `docs/qa/youngo_dynamic_content_arabic_subscriptions_public_qa_1_report.md`
- `docs/qa/youngo_language_frontend_auth_copy_qa_1_report.md`

Additional prior reports inspected:

- `docs/qa/youngo_demo_content_1_blog_contact_report.md`
- `docs/qa/youngo_demo_fix_2_blog_i18n_report.md`
- `docs/qa/youngo_client_upload_preflight_review.md`

Core source files inspected:

- `application/models/Youngo_translation_model.php`
- `application/models/Youngo_subscription_model.php`
- `application/models/Crud_model.php`
- `application/controllers/Home.php`
- `application/controllers/Blog.php`
- `application/controllers/Admin.php`
- `application/controllers/Youngo_subscription_plans.php`
- `application/helpers/youngo_frontend_content_helper.php`
- `application/helpers/youngo_frontend_language_helper.php`
- `application/helpers/common_helper.php`
- `application/views/backend/admin/course_add.php`
- `application/views/backend/admin/course_edit.php`
- `application/views/backend/admin/course_add_shortcut.php`
- `application/views/backend/admin/category_add.php`
- `application/views/backend/admin/category_edit.php`
- `application/views/backend/admin/sub_category_add.php`
- `application/views/backend/admin/sub_category_edit.php`
- `application/views/backend/admin/section_add.php`
- `application/views/backend/admin/section_edit.php`
- `application/views/backend/admin/lesson_add.php`
- `application/views/backend/admin/lesson_edit.php`
- `application/views/backend/admin/text_type_lesson_add.php`
- `application/views/backend/admin/text_type_lesson_edit.php`
- `application/views/backend/admin/youngo_subscription_plan_form.php`
- `application/views/backend/admin/blog_add.php`
- `application/views/backend/admin/blog_edit.php`
- `application/views/backend/admin/blog_category_add.php`
- `application/views/backend/admin/blog_category_edit.php`
- `application/views/backend/admin/youngo_homepage.php`
- `application/views/frontend/youngo/home.php`
- `application/views/frontend/youngo/courses_page.php`
- `application/views/frontend/youngo/course_page.php`
- `application/views/frontend/youngo/course_listing/course_card.php`
- `application/views/frontend/youngo/course_listing/filter_panel.php`
- `application/views/frontend/youngo/blogs.php`
- `application/views/frontend/youngo/blog_details.php`
- `application/views/frontend/youngo/subscriptions.php`
- `application/views/frontend/youngo/contact_us.php`

## C. Static Phrases vs Dynamic Content Distinction

Static UI/system labels belong to the language pack / Edit Phrase system. Examples: navigation labels, auth form labels, button text, empty-state labels, and repeated interface wording.

Dynamic content belongs in bilingual content tables/models/forms. Examples: course titles/descriptions, category names, section titles, lesson summaries/body text, subscription plan names/descriptions, blog post titles/body, homepage CMS blocks, and contact/business content.

The existing project already follows this distinction for courses, categories, sections, lessons, subscription plans, and part of Blog. Those surfaces should be reused and extended, not rebuilt through phrase rows.

## D. Existing Translation Infrastructure Inventory

| Area | DB storage | Model/helper | Admin entry | Frontend wiring | Classification |
|---|---|---|---|---|---|
| Courses | `course`, `youngo_course_translations` | `Youngo_translation_model`, `youngo_frontend_content_helper` | Add/edit/shortcut bilingual pattern | Course listing, cards, detail, homepage featured courses, wishlist/access surfaces | Implemented and frontend-wired |
| Categories/subcategories | `category`, `youngo_category_translations` | `Youngo_translation_model`, `youngo_frontend_content_helper` | Category and subcategory add/edit bilingual forms | Course filters/cards/homepage category surfaces | Implemented and frontend-wired |
| Sections | `section`, `youngo_section_translations` | `Youngo_translation_model`, frontend helper | Section add/edit bilingual forms | Course detail curriculum | Implemented and partially coverage-complete |
| Lessons | `lesson`, `youngo_lesson_translations` | `Youngo_translation_model`, frontend helper | Lesson/text lesson add/edit bilingual forms | Course detail curriculum | Implemented and partially coverage-complete |
| Subscription plans | `youngo_subscription_plans`, `youngo_subscription_plan_translations` | `Youngo_subscription_model` | YounGo subscription plan form | Public subscriptions page | Implemented and frontend-wired |
| Blog posts | `blogs`, `youngo_blog_translations` | `Crud_model` Blog translation methods | Blog add/edit partial bilingual fields | Blog listing/cards only | Implemented but detail frontend not wired |
| Blog categories | `blog_category` only | Legacy `Crud_model` | Shared title/subtitle only | Listing uses legacy title plus a view-local Arabic map for three demo names | Genuinely missing reusable bilingual infrastructure |
| Homepage CMS blocks | `frontend_settings.youngo_homepage_content` JSON | `common_helper`, language helper local text map | Shared homepage manager fields | Homepage uses local text-map transformation plus translated auto course/category rows | Implemented as shared JSON with partial localization; no bilingual content entry |
| Contact/settings content | `frontend_settings.contact_info`, `settings` | Shared frontend settings helpers | Shared settings/dashboard path | Contact view uses shared email/phone and local Arabic fallback for address/hours | Mostly shared/non-translated; business-copy enhancement needed |
| Learner dynamic labels/values | Mixed LMS/course fields | Phrase helpers and display helpers | Mostly shared metadata | Public views translate labels but not every DB-backed value | Mixed; many values should remain shared or become taxonomy mappings |

## E. Courses Translation Status

Courses are implemented and frontend-wired.

Current DB inventory from the diagnostic:

```text
course rows: 8
youngo_course_translations rows: 16
english rows: 8
arabic rows: 8
arabic_translated rows: 0
```

Infrastructure:

- `youngo_course_translations` stores `course_id`, `language_code`, `title`, `slug`, `short_description`, `description`, `outcomes`, `requirements`, `faqs`, and SEO fields.
- `Youngo_translation_model` provides course translation reads, fallback reads, future upserts, slug helpers, and missing-translation summaries.
- Admin course add/edit forms collect English canonical fields and optional Arabic fields.
- English values sync back to canonical `course` fields for Academy LMS compatibility.
- Public YounGo course listing/detail/card/homepage/wishlist/access surfaces use `youngo_frontend_translate_course_row(s)`.

Fallback behavior:

- Arabic/default: Arabic translation, then English translation, then canonical LMS field.
- English: English translation, then canonical LMS field.
- IDs, access mode, price, media, and operational fields are preserved.

Classification: implemented and frontend-wired. Do not rebuild.

## F. Categories/Subcategories Translation Status

Categories and subcategories are implemented and frontend-wired.

Current DB inventory:

```text
category rows: 12
top-level rows: 6
subcategory rows: 6
youngo_category_translations rows: 24
english rows: 12
arabic rows: 12
arabic_translated rows: 0
```

Infrastructure:

- `youngo_category_translations` stores `category_id`, `language_code`, `name`, `slug`, and `description`.
- `Youngo_translation_model` handles category translation and fallback.
- Category and subcategory add/edit forms collect `english_name`, `english_slug`, `arabic_name`, and `arabic_slug`.
- Course filters, course cards, homepage featured categories, and course-related category display paths use the frontend content helper.

Fallback behavior matches the central translation model.

Classification: implemented and frontend-wired. Do not rebuild.

## G. Sections/Lessons Translation Status

Sections and lessons are implemented, but current local Arabic coverage is partial.

Current DB inventory:

```text
section rows: 20
youngo_section_translations rows: 34
english rows: 20
arabic rows: 14
arabic_translated rows: 0

lesson rows: 40
youngo_lesson_translations rows: 68
english rows: 40
arabic rows: 28
arabic_translated rows: 0
```

Infrastructure:

- `youngo_section_translations` stores section titles.
- `youngo_lesson_translations` stores lesson title, summary, and text lesson content.
- Section add/edit forms collect English and optional Arabic titles.
- Lesson add/edit forms collect English and optional Arabic title/summary.
- Text lesson add/edit partials collect English and optional Arabic text content.
- Course detail curriculum calls `youngo_frontend_translate_section_rows()` and `youngo_frontend_translate_lesson_rows()`.

Fallback behavior is safe. Missing Arabic rows fall back to English translation and then canonical LMS data.

Classification: implemented and frontend-wired, with data coverage incomplete. Do not rebuild; fill/QA missing Arabic rows through existing forms or controlled content process.

## H. Subscription Plan Translation Status

Subscription plans are implemented and frontend-wired.

Current DB inventory:

```text
youngo_subscription_plans rows: 3
public purchasable rows: 3
youngo_subscription_plan_translations rows: 6
english rows: 3
arabic rows: 3
arabic_translated rows: 0
```

Infrastructure:

- `youngo_subscription_plan_translations` stores `plan_id`, `language_code`, `name`, `short_description`, `description`, and `badge_label`.
- `Youngo_subscription_model` owns translation reads/saves and public plan overlays.
- Admin plan form uses `translations[english]` and `translations[arabic]` fields, with RTL Arabic entry.
- `Home::subscriptions()` passes frontend language into `get_public_subscription_plans()`.
- Public subscriptions view displays translated plan copy while preserving price, duration, currency, active/purchasable state, archive state, and checkout-not-ready safety.

Classification: implemented and frontend-wired. Do not rebuild.

## I. Blog/Content Translation Status

Blog posts have a minimal bilingual implementation, but it is not consistently wired.

Current DB inventory:

```text
blogs rows: 4
youngo_blog_translations rows: 8
english rows: 4
arabic rows: 4
arabic_translated rows: 0
blog_category rows: 3
youngo_blog_category_translations table: missing
```

Implemented:

- `youngo_blog_translations` stores `blog_id`, `language_code`, `title`, `excerpt`, `description`, and `slug`.
- `Crud_model` includes `youngo_get_blog_translation()`, `youngo_save_blog_translation()`, and `youngo_save_blog_translations_from_post()`.
- Blog add/edit forms collect English excerpt and Arabic title/excerpt/description.
- Blog listing/card view reads `youngo_blog_translations`.
- Arabic listing skips DB posts without Arabic translated title rather than showing English-only DB rows.

Not fully wired:

- `Blog.php` does not shape Blog detail data through translation helpers.
- `application/views/frontend/youngo/blog_details.php` does not read `youngo_blog_translations`.
- Blog categories have no translation table or bilingual admin fields.
- Blog category Arabic output currently uses a view-local map for the three demo category names, not reusable CMS infrastructure.

Classification: Blog posts are implemented but detail frontend is not wired; Blog categories are genuinely missing reusable bilingual infrastructure.

## J. Homepage/Contact Translation Status

Homepage content is CMS-backed but not stored as true bilingual content.

Current DB inventory:

```text
frontend_settings rows: 50
frontend_settings.youngo_homepage_content rows: 1
frontend_settings.contact_info rows: 1
youngo_homepage_translations table: missing
youngo_contact_translations table: missing
```

Homepage:

- Admin homepage manager edits shared `frontend_settings.youngo_homepage_content` JSON.
- Frontend `home.php` loads `youngo_get_homepage_content()`.
- Manual homepage strings pass through `youngo_frontend_homepage_localize_content()`, which uses local text mapping from the language helper.
- Auto-sourced featured course/category rows reuse the real translation helper/model.

This is useful for demo fallback but not a scalable bilingual homepage CMS model.

Contact:

- Contact page reads shared `frontend_settings.contact_info` and `settings` values.
- Email and phone are correctly shared values.
- Arabic page uses local Arabic fallback for address and working hours.
- Contact remains display-only; no public form was enabled.

Classification: homepage/contact are partially localized through shared JSON plus local fallback mapping. Business-editable bilingual content storage/admin entry is missing or incomplete. Email/phone/social URLs should remain shared/non-translated.

## K. Admin Forms Reuse Findings

Reusable admin bilingual form patterns already exist:

- English primary/canonical fields plus optional Arabic fields.
- Arabic fields use RTL direction.
- English saves sync canonical Academy LMS fields.
- Arabic rows are created only when Arabic values are non-empty.
- Shared operational fields remain outside bilingual content fields.

This pattern is already present for courses, categories, subcategories, sections, lessons, text lessons, subscription plans, and Blog posts. Reuse this pattern for Blog categories and homepage/contact content instead of inventing a separate editing model.

## L. Frontend Wiring Findings

Strong wiring exists for:

- Course listing and detail.
- Course cards and filters.
- Homepage featured course/category auto-sourced rows.
- Subscription plans.
- Blog listing/cards.

Partial or missing wiring exists for:

- Blog detail page.
- Blog category display.
- Homepage manual CMS blocks.
- Contact address/hours.
- Some learner-facing DB-backed metadata values such as level/access labels where values are stored as internal/shared strings.

## M. What Is Already Done and Should Not Be Rebuilt

Do not rebuild:

- `Youngo_translation_model`.
- `youngo_course_translations`, `youngo_category_translations`, `youngo_section_translations`, and `youngo_lesson_translations`.
- Existing course/category/subcategory/section/lesson bilingual admin forms.
- `application/helpers/youngo_frontend_content_helper.php`.
- Existing public course/category/section/lesson frontend shaping.
- `Youngo_subscription_model` translation overlay.
- `youngo_subscription_plan_translations`.
- Existing subscription plan bilingual admin form and public display wiring.
- Blog post translation table and Blog add/edit translation save path.

Enhance those surfaces in place where needed.

## N. What Needs Enhancement

Priority enhancements:

- Add a small reusable Blog translation read helper/model path rather than keeping Blog translation reads in the listing view only.
- Wire Blog detail display to `youngo_blog_translations` with the same fallback rules as the listing.
- Add Blog category bilingual storage and admin fields if Blog categories remain public.
- Replace homepage manual-string local text-map dependence with explicit bilingual CMS content fields or a structured bilingual JSON model.
- Add contact bilingual fields only for translatable business copy such as address display text and working hours; keep email, phone, social URLs, and system identifiers shared.
- Add per-content-type coverage diagnostics for missing Arabic rows.
- Normalize fallback reporting metadata across Blog/subscription/homepage surfaces where practical.

## O. What Is Genuinely Missing

Genuine missing reusable infrastructure:

- Blog category translation table/model/admin fields/frontend lookup.
- Blog detail translation wiring.
- Dedicated bilingual CMS storage for homepage manual blocks.
- Dedicated bilingual CMS storage for contact display copy, if admin-editable Arabic contact copy is required.
- Unified dynamic content coverage report for all content types.

Not missing:

- Course/category/section/lesson translation model.
- Course/category/section/lesson translation tables.
- Course/category/subcategory/section/lesson admin bilingual entry forms.
- Subscription plan translation table/model/form/public rendering.
- Blog post translation rows and basic admin entry path.

## P. Recommended Roadmap

Demo-critical fixes:

1. Wire Blog detail page to existing `youngo_blog_translations`.
2. Replace view-local Blog category Arabic mapping with reusable Blog category translation infrastructure or hide category labels where no translation exists.
3. Fill missing Arabic section/lesson rows for demo-visible courses using existing admin/content paths.
4. Add a read-only coverage diagnostic that lists exact missing Arabic rows by entity.

Data-entry/admin efficiency:

1. Reuse the existing English-primary/Arabic-optional admin pattern for Blog categories.
2. Add homepage bilingual content entry without changing homepage visual/layout controls.
3. Add contact bilingual display fields only for address/hours/business copy.

Frontend wiring fixes:

1. Centralize Blog translation selection outside the frontend view.
2. Keep all frontend dynamic content selection on canonical IDs and existing slugs/routes.
3. Preserve fallback order and metadata consistently.

Long-term cleanup:

1. Reduce view-local translation maps once CMS-backed bilingual content exists.
2. Keep static labels in phrase helpers/language pack and dynamic content in content translation tables.
3. Avoid dedicated duplicate course/category translation models unless `Youngo_translation_model` becomes insufficient.
4. Keep `arabic_translated` out of UI language, routes, and translation-table `language_code` values.

## Q. Risks/Blockers

- Blog detail pages can still show canonical English content on Arabic routes until detail translation wiring is added.
- Blog categories are public but not reusable-bilingual; current Arabic display depends on a limited local map.
- Homepage Arabic content relies partly on local text mapping, which can drift from CMS-managed English content.
- Contact Arabic address/hours are local fallback strings, not editable bilingual CMS content.
- Sections and lessons have incomplete Arabic coverage: 14/20 sections and 28/40 lessons.
- Existing legacy phrase-table corruption/warnings remain outside this phase.

## R. Diagnostic Result

Added read-only diagnostic:

```text
scripts/phase_2/youngo_content_translation_reuse_audit_1_diagnostic.php
```

Validation result:

```text
php -l scripts/phase_2/youngo_content_translation_reuse_audit_1_diagnostic.php
No syntax errors detected

php scripts/phase_2/youngo_content_translation_reuse_audit_1_diagnostic.php
PASS: content translation reuse audit diagnostic completed read-only.
```

Diagnostic verified:

- Detected translation models/helpers/forms/views.
- Detected translation tables and columns.
- Current coverage counts for courses, categories/subcategories, sections, lessons, subscription plans, Blogs, Blog categories, homepage, and contact settings.
- No `arabic_translated` rows in translation tables.
- Course content marker form field `language_made_in` is present and saves into the existing `course.language` marker column.
- Protected payment/access table counts stayed at 0.
- No changed payment/Paymob/checkout/coupon/cart files were detected.
- No DB writes were executed.

## S. Git Status

Final git status after this audit:

```text
?? docs/qa/youngo_content_translation_reuse_audit_1_report.md
?? scripts/phase_2/youngo_content_translation_reuse_audit_1_diagnostic.php
```

Only the audit report and read-only diagnostic were created.
