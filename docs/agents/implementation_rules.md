# YounGo Implementation Rules

## 1. Purpose

This document defines implementation rules for developers and AI/code agents working on YounGo.

It explains how changes should be made safely inside the existing CodeIgniter CMS.

This file is not a project overview, design system, phase plan, database schema, or prompt file.

Use this file together with:

```text
YOUNGO_PROJECT_CONTEXT.md
AGENTS.md
docs/design/youngo_style_direction.md
docs/planning/youngo_master_plan_v2.md
docs/planning/youngo_phase_2_hybrid_access_subscriptions_roles_architecture.md
docs/planning/
docs/reference/
````

---

## 2. Core Rule

YounGo must be built on top of the existing Academy LMS / CodeIgniter CMS.

Do not rewrite the system from scratch.

Do not replace the existing CMS dashboard.

Do not convert the project to another framework.

---

## 3. Framework Rules

This project is a PHP CodeIgniter MVC application.

It is not Laravel.

Do not use:

```text
php artisan
Laravel migrations
Blade templates
Eloquent models
Laravel routing assumptions
```

Work inside the existing project structure:

```text
application/controllers/
application/models/
application/views/
application/config/
assets/
uploads/
```

Follow existing CodeIgniter-style patterns unless a planning document explicitly approves another approach.

---

## 4. Documentation Separation Rules

Keep documentation files clean by purpose.

Use:

```text
YOUNGO_PROJECT_CONTEXT.md = high-level project context
AGENTS.md = short root-level agent instructions
docs/design/ = visual identity and UX direction
docs/reference/ = old CMS docs, findings, screenshots, exports
docs/planning/ = delivery phases and implementation plans
docs/agents/ = agent rules, prompts, testing notes
```

Do not mix:

```text
Phase plans inside design files
Database schemas inside style files
Prompt text inside project context
Visual style rules inside phase plans unless needed for acceptance
Old CMS references as active implementation plans
```

When adding documentation, place it in the folder that matches its purpose.

---

## 5. Source of Truth Rules

Before implementing, read the current master plan:

[`youngo_master_plan_v2.md`](../planning/youngo_master_plan_v2.md)

This plan is the primary reference for project priorities, architecture, Academy LMS reuse decisions, deployment preparation, and implementation sequencing.

Then read any relevant supporting planning document in:

```text
docs/planning/
```

For work touching access, subscriptions, course purchase, coupons, discounts, manual grants, checkout, Paymob/payment flow, roles, permissions, or instructor assignment, also read:

[`youngo_phase_2_hybrid_access_subscriptions_roles_architecture.md`](../planning/youngo_phase_2_hybrid_access_subscriptions_roles_architecture.md)

If a task is not clearly aligned with the master plan, stop and ask for clarification before changing code.

When documents conflict, use this priority:

```text
1. Latest explicit user instruction
2. docs/planning/youngo_master_plan_v2.md
3. YOUNGO_PROJECT_CONTEXT.md
4. docs/planning/
5. docs/agents/implementation_rules.md
6. docs/design/youngo_style_direction.md
7. docs/reference/
8. Existing code behavior
```

---

## 6. Git Safety Rules

Before editing files, always check:

```bash
git branch --show-current
git status
```

Preferred working behavior:

```text
Make small, reviewable changes.
Avoid broad unrelated edits.
Do not commit unless explicitly asked.
Do not push unless explicitly asked.
Do not delete existing CMS functionality without approval.
```

If the working tree is not clean, inspect the changes before editing.

Do not overwrite user changes.

---

## 7. Sensitive File Rules

Do not expose, print, or commit secrets.

Treat these as sensitive:

```text
application/config/database.php
.env files if present
API keys
SMTP credentials
payment credentials
private local config files
```

Do not modify database credentials unless explicitly asked.

---

## 8. Dependency Rules

Do not change dependencies unless the task requires it and the user approves.

Avoid running:

```bash
composer install
composer update
npm install
npm update
```

without explicit approval.

This CMS may contain old CodeIgniter/Academy LMS dependency assumptions. Dependency changes can break compatibility.

Prefer working with the existing project structure and existing assets first.

---

## 9. Frontend Theme Rules

The YounGo frontend should be implemented as its own frontend theme.

Preferred target structure:

```text
application/views/frontend/youngo/
assets/frontend/youngo/
```

Do not directly rewrite the existing frontend theme unless a planning document explicitly says so.

The old frontend theme may be used as a reference for:

```text
Existing view loading patterns
Existing helper usage
Existing course/category/blog rendering
Existing login/signup links
Existing asset loading conventions
```

The YounGo theme should have its own clean CSS, JavaScript, images, and view partials where practical.

---

## 10. Design Implementation Rules

Use the approved design direction from:

```text
docs/design/youngo_style_direction.md
```

Do not invent a new design identity.

The YounGo visual direction is:

```text
Modern
Premium
Soft
Purple-led
Kids-learning focused
Parent-trust focused
Rounded
Friendly
Clean
```

The public website may feel more playful.

The admin dashboard should be calmer, simpler, and more operational.

---

## 11. Stitch Output Rules

Stitch output is a design reference, not production code.

Do not paste Stitch HTML directly into the CMS as final implementation.

Stitch output may include:

```text
Tailwind CDN
Temporary hosted images
Static placeholder content
Non-CodeIgniter HTML structure
Prototype-only interactions
```

Convert Stitch designs into:

```text
Maintainable CodeIgniter PHP views
Local CSS
Local JavaScript
CMS-driven content
Reusable view partials where useful
Existing LMS data where possible
Local or CMS-uploaded images
```

Use Stitch for visual direction, spacing, hierarchy, and component intent.

---

## 12. CMS Content Rules

The YounGo website should have fixed visual styling and editable content.

Admins should manage content, not design styling.

CMS-managed content may include:

```text
Homepage text
Images
CTA labels and links
Featured categories
Featured courses
Benefits
About content
Testimonials
FAQs
Blog/news previews
Contact information
Menu links
SEO fields
Section visibility
Section ordering
Draft/published state
```

Do not add styling controls such as arbitrary colors, font sizes, spacing controls, layout controls, or theme switching unless a planning document explicitly requires them.

---

## 13. Existing CMS Reuse Rules

Reuse existing LMS/CMS data where practical.

The master plan makes this a required decision rule: Academy LMS is the core system, and existing working LMS functionality must not be rebuilt unless reuse has been checked and proven impractical.

Likely reusable areas:

```text
Courses
Categories
Lessons
Instructors
Users
Blogs
Custom pages
Frontend settings
Contact information
FAQs
Reviews
Uploaded media
SEO-related fields
```

Do not duplicate existing data structures unnecessarily.

Create YounGo-specific structures only when existing CMS structures are too limited for the approved product direction.

The decision to create new tables, fields, or admin screens should be documented in `docs/planning/`.

---

## 13A. Phase 2 Access, Subscription, Coupon, And Role Rules

The active Phase 2 architecture reference is:

[`youngo_phase_2_hybrid_access_subscriptions_roles_architecture.md`](../planning/youngo_phase_2_hybrid_access_subscriptions_roles_architecture.md)

Use it before touching:

```text
course access
lesson access
subscriptions
course purchase
coupons
discounts
manual grants
checkout
payment providers
Paymob
invoices
roles
permissions
instructor assignment
```

Phase 2 must be additive and compatibility-first.

Do not:

```text
Remove individual course purchase while adding subscriptions.
Remove existing LMS enrolment/payment logic until a safe compatibility layer exists.
Remove or abruptly replace enrol table logic.
Remove or abruptly replace existing lesson access helpers such as enroll_status().
Remove cart/session code while adding direct checkout UX.
Treat coupon/discount as direct access.
Treat public free-course classification as the target YounGo business model.
Enable real payments before implementation and QA are explicitly approved.
Change Paymob method decisions before current implementation research.
Abruptly replace role_id, permissions, or is_instructor behavior.
Implement single-role assumptions in new Phase 2 work.
Let normal admins modify or downgrade Root Admin.
Mark subscriptions, manual grants, expanded coupons, Paymob payments, or multi-role capability as implemented before they are built and tested.
```

Current Subscription Plan Management status:

```text
Phase 2L Subscription Plan Management is implemented and QA-tested locally.
The local Phase 2L schema is applied: subscription plan archive columns and subscription plan audit log table/indexes exist.
The feature manages plan definitions only. It does not issue subscriptions, create checkout orders, process payment, apply coupons, grant manual access, create enrolments, or write access rows.
YounGo commercial currency is EGP. The global system currency is currently EGP.
The seeded Monthly, 3 Months, and Yearly subscription placeholders now store EGP and remain inactive/non-purchasable.
Their current local prices are temporary placeholders only: Monthly 100.00 EGP, 3 Months 250.00 EGP, and Yearly 900.00 EGP.
The Phase 2L diagnostic now reports zero non-EGP subscription plans; currency readiness is clean for subscription plan definitions.
Subscriptions are still not production-ready: final prices require owner approval, and checkout/payment/subscription issuance are not implemented.
Do not correct real/seeded plan currency through manual row-level SQL unless the project owner explicitly approves it.
Do not create, activate, or make real commercial subscription plans purchasable until final prices are approved and the owner explicitly approves activation.
Gateway currency alignment is deferred to a later payment/Paymob phase.
```

Current Shared Entitlement Write Service status:

```text
Phase 2M Shared Entitlement Write Service foundation is implemented, schema-applied, committed, and controlled service-QA tested locally.
Use application/models/Youngo_entitlement_write_model.php for future manual course grants, manual subscription grants, course/subscription revocation, duplicate prevention, linked manual-grant revocation, and transactional entitlement writes.
The diagnostic is scripts/phase_2/youngo_phase_2m_entitlement_write_diagnostic.php.
The local Phase 2M schema adds revoke actor/note fields and indexes to youngo_course_access and youngo_user_subscriptions, with no hard foreign keys.
Controlled service QA passed, temporary QA rows were removed by DB restore, and final diagnostics pass.
Phase 2N Manual Grants dashboard/UI is implemented, committed, and authenticated UI-QA tested locally.
Manual Grants UI uses application/controllers/Youngo_manual_grants.php plus backend admin list/create/detail views, routes under /admin/youngo/manual-grants, and navigation under YounGo gated by grant_manual_access.
The UI supports manual course grants, manual subscription grants, detail view, duplicate rejection through the write service, and course/subscription revocation.
All Manual Grants UI writes use Youngo_entitlement_write_model; do not bypass it with manual SQL or controller-local grant/revoke logic.
Authenticated QA verified Root Admin login, navigation/list/create pages, course and subscription grant/revoke flows, read-layer recognition/denial, cleanup restore from D:\Work\YounGo\backups\youngo_school (14).sql, and final diagnostics.
Phase 2O learner-facing entitlement visibility/enforcement alignment is implemented, committed, and controlled browser-QA tested locally.
Course card CTAs/status, course detail CTAs/status, manual grant/subscription access labels, checkout-not-ready messaging for paid subscription-only courses, Home::play_lesson(), Home::pdf_canvas(), go_course_playing_page(), lesson_mobile_web_view_get(), offline_video_for_mobile_app(), and course review visibility/submission gates are aligned with the YounGo entitlement read layer where scoped.
Paid subscription-only courses without active access must not expose legacy Buy Now/Add to cart while YounGo checkout is not implemented.
Phase 2O QA used temporary manual grants through the Manual Grants UI and restored from D:\Work\YounGo\backups\youngo_school (15).sql; final diagnostics passed and entitlement tables are empty again.
Phase 2P My Courses / My Access learner visibility is implemented, committed, and controlled-QA tested locally.
Youngo_entitlement_model now provides read-only learner access composition methods: get_learner_course_access_items($user_id), get_learner_subscription_summary($user_id), and get_learner_access_counts($user_id).
Home::my_access(), application/views/frontend/youngo/my_access.php, application/views/frontend/youngo/reload_my_courses.php, the My Access profile-menu item, and scripts/phase_2/youngo_phase_2p_learner_access_visibility_diagnostic.php are implemented.
My Courses now uses prepared learner access items and includes active legacy enrolments plus active direct YounGo/manual course access rows. It must not auto-list every subscription-eligible course just because a subscription is active.
My Access is visibility-only. It may show subscription/access summaries and browsing links, but must not expose checkout, renewal, payment, coupon, Subscribe, Pay, or Renew actions.
Phase 2P QA used temporary manual grants through the Manual Grants UI and restored from D:\Work\YounGo\backups\youngo_school (16).sql; final diagnostics passed and entitlement tables are empty again.
Phase 2Q curated QA learner baseline setup is completed locally.
Pre-setup backup was D:\Work\YounGo\backups\youngo_school (17).sql.
The curated post-setup backup is D:\Work\YounGo\backups\youngo_school_qa_baseline_after_phase_2q2.sql and should be used as the local baseline for future learner-authenticated QA.
Dedicated QA learner user 8 exists as qa.learner@youngo.local, name YounGo QA Learner, role_id 2, active, and is_instructor 0.
Do not document, print, commit, store, or reuse the QA learner password. The project owner must provide it when learner-authenticated QA is needed.
Current QA fixtures: user 8 clean no-access learner; user 2/course 6 legacy enrol compatibility; course 1 manual-grant / lesson-safe QA; course 9 paid subscription-only checkout-not-ready CTA; plan 1 Monthly EGP manual subscription QA.
Phase 2Q setup did not modify Root Admin or existing users 2/5/6/7, preserved user 2/course 6 enrolment, and created no grants/subscriptions/course access/checkout/payment/order/coupon/enrol/progress rows.
Phase 2Q.3 learner-authenticated browser QA is completed locally for the implemented Phase 2O/2P learner surfaces using QA learner user 8.
Phase 2Q.3 verified QA learner login, My Courses no-access baseline, My Access no-subscription baseline, course 1 no-access state, course 9 checkout-not-ready state, temporary manual course grant visibility, My Courses and My Access course-access visibility, course listing/detail active grant state, normal learner lesson access with active manual grant, review-area visibility without review submission, duplicate course grant rejection, course grant revocation, temporary manual subscription visibility, My Access active subscription summary, My Courses no subscription catalog flooding, course listing/detail subscription state, duplicate subscription rejection, subscription revocation, and GET-only cart boundary.
The curated baseline backup D:\Work\YounGo\backups\youngo_school_qa_baseline_after_phase_2q2.sql was restored after QA. Temporary rows before restore were watch_histories = 1, youngo_course_access = 1, youngo_user_subscriptions = 1, and youngo_manual_grants = 2. After restore, user 8 remained, entitlement tables were empty, watch/progress returned to baseline, and diagnostics passed.
Phase 2R read-only admin/user entitlement summaries are implemented, committed, and authenticated admin-QA tested locally.
Admin user edit and course edit contexts show read-only YounGo entitlement summary cards when the current admin has grant_manual_access. Summary cards are hidden safely if the capability helper is unavailable or capability is denied.
The Phase 2R summary cards separate legacy enrolment from active YounGo course access, do not treat subscription eligibility as enrolment, show active user subscription state where applicable, show recent manual grants safely, and link to Manual Grants filters by user_id/course_id.
Manual Grants UI remains the authoritative grant/revoke surface; no grant/revoke controls belong in admin summary cards.
The Phase 2R diagnostic is scripts/phase_2/youngo_phase_2r_admin_entitlement_summary_diagnostic.php and should pass locally.
Phase 2R authenticated QA used temporary manual course/subscription grants through Manual Grants UI, verified summary count/status updates, duplicate rejection, revocation, filtered links, GET-only cart boundary, cleanup restore from D:\Work\YounGo\backups\youngo_school_qa_baseline_after_phase_2q2.sql, and final clean diagnostics.
The Phase 2R QA-blocking fix made user_edit.php safely default missing/empty social/payment key arrays without exposing sensitive values.
Phase 2S YounGo CTA boundary fixes are implemented, committed, reviewed, and manually validated locally.
Home::get_enrolled_to_free_course() blocks legacy free-enrol creation for YounGo-managed course modes subscription_only, subscription_and_purchase, and purchase_only, redirects safely back to course detail, and preserves legacy free-course enrol for non-YounGo-controlled courses.
Course detail, course cards, my_wishlist.php, and wishlist_items.php no longer expose legacy Enroll Now, Add to cart, or Buy Now CTAs for YounGo-managed no-access courses. Wishlist empty-state copy no longer mentions free-course enrolment or cart shortcuts.
The Phase 2S diagnostic is scripts/phase_2/youngo_phase_2s_route_cta_boundary_diagnostic.php and should pass locally.
Phase 2S manual validation used QA learner user 8. Course 1 showed access-managed messaging, no legacy CTAs, and /home/get_enrolled_to_free_course/1 redirected safely without creating an enrol row for user 8. Course 9 remained checkout-not-ready without Buy Now/Add to cart. Post-validation counts kept user 8 enrol rows at 0 and entitlement/checkout/payment/coupon tables empty.
Phase 2U.3 localization schema foundation is implemented, reviewed, committed, and locally applied after backup D:\Work\YounGo\backups\youngo_school_before_phase_2u3_localization_schema_2026_07_16.sql.
Phase 2U.3 added four YounGo-specific translation tables: youngo_course_translations, youngo_category_translations, youngo_section_translations, and youngo_lesson_translations. Canonical course/category/section/lesson IDs remain the stable references for access, grants, subscriptions, future checkout/orders, lesson progress, and diagnostics.
Duplicate courses per language are not the default localization model. Translations should be attached to canonical records.
The English seed script scripts/phase_2/youngo_phase_2u3_seed_english_translations.php is idempotent and seeded English rows from existing canonical content without modifying canonical rows. No Arabic rows, language phrase table changes, settings changes, frontend rendering, dashboard form changes, or course rebuild happened in Phase 2U.3.
The Phase 2U.3 diagnostic is scripts/phase_2/youngo_phase_2u3_localization_schema_diagnostic.php and should pass locally.
Phase 2U.4 canonical Arabic UI phrase support is implemented, reviewed, committed, and locally applied after backup D:\Work\YounGo\backups\youngo_school_before_phase_2u4_arabic_phrase_support_2026_07_16.sql.
Phase 2U.4 added language.arabic, created application/language/arabic.json, and added database/phase_2/youngo_phase_2u4_arabic_phrase_support_up.sql, database/phase_2/youngo_phase_2u4_arabic_phrase_support_down.sql, scripts/phase_2/youngo_phase_2u4_seed_arabic_phrases.php, and scripts/phase_2/youngo_phase_2u4_arabic_phrase_diagnostic.php.
The canonical Arabic language code is arabic; arabic_translated and application/language/arabic_translated.json are deprecated placeholders and must not be treated as source of truth.
English remains the default language. settings.language_dirs includes arabic: rtl, but Phase 2U.4 itself did not implement frontend Arabic rendering, /ar routes, dashboard bilingual forms, or Arabic course/content translation rows.
Arabic phrase values were generated from current project phrase keys and English values in simple Modern Standard Arabic. High-priority YounGo UI phrases have direct translations; many non-priority phrases use safe Arabic fallback wording and require phrase-polish QA before user-facing Arabic launch.
The Phase 2U.4 diagnostic is scripts/phase_2/youngo_phase_2u4_arabic_phrase_diagnostic.php and should pass locally.
Phase 2U.5.2 translation model/helper foundation is implemented, reviewed, committed, and locally diagnostic-tested.
Phase 2U.5.2 added application/models/Youngo_translation_model.php and scripts/phase_2/youngo_phase_2u5_translation_model_diagnostic.php.
Youngo_translation_model is the central helper/model for YounGo bilingual content. It works with the Phase 2U.3 translation tables while preserving canonical course/category/section/lesson IDs. It does not replace canonical LMS tables and does not handle entitlement, subscription, grant, checkout, coupon, or payment logic.
Canonical language codes are english and arabic. Compatibility inputs map en to english, ar to arabic, and deprecated arabic_translated to arabic, but arabic_translated must not be used as canonical.
The model supports direct translation reads, fallback reads, future dashboard upsert methods, slug generation/availability checks, has_translation(), and missing-translation summaries. Fallback order is requested language translation, English translation, canonical LMS table data, then null; fallback metadata includes requested_language, resolved_language, is_fallback, missing_translation, and translation_source.
Phase 2U.5.2 did not modify dashboard forms, frontend rendering, /ar routes, language phrases, settings, canonical course/category/section/lesson data, checkout/order/coupon/Paymob/payment, or entitlement rows. No Arabic course/content translation rows were created, and upsert methods were reviewed but not executed or wired to forms.
The Phase 2U.5 diagnostic is scripts/phase_2/youngo_phase_2u5_translation_model_diagnostic.php and should pass locally.
Phase 2U.5.3 Category/Subcategory Bilingual Form Support is implemented, reviewed, committed, and runtime-QA tested locally.
Phase 2U.5.3 updated application/models/Crud_model.php, application/views/backend/admin/category_add.php, application/views/backend/admin/category_edit.php, application/views/backend/admin/sub_category_add.php, application/views/backend/admin/sub_category_edit.php, and added scripts/phase_2/youngo_phase_2u5_category_bilingual_forms_diagnostic.php.
Category and subcategory add/edit forms now include english_name, english_slug, arabic_name, and arabic_slug. Arabic fields are optional and use RTL direction. Shared fields such as code, parent/category selection, icon, thumbnail, and category image remain canonical/shared outside bilingual content fields.
Crud_model::add_category() and Crud_model::edit_category() sync canonical category.name and category.slug from English fields, keep the legacy name fallback for compatibility, upsert English category translations after canonical create/update, and upsert Arabic category translations only when Arabic name or Arabic slug is non-empty. Empty Arabic rows are not created, and arabic_translated is not used.
Root Admin authenticated dashboard QA verified temporary category/subcategory add/edit with English and Arabic values, edited translation updates, English-only category behavior with no Arabic row, DB restore from D:\Work\YounGo\backups\youngo_school_before_phase_2u5_3_category_bilingual_form_qa_2026_07_16_190255.sql, temporary QA data removal, and restored baseline counts: category = 12, youngo_category_translations english = 12, youngo_category_translations arabic = 0, course = 7, section = 18, lesson = 36, enrol = 1, and protected checkout/payment/coupon/entitlement/progress rows clean.
Phase 2U.5.3 did not modify Admin.php, course forms, section forms, lesson forms, frontend rendering, /ar routes, language phrases, settings, checkout/order/coupon/Paymob/payment, or entitlement logic.
The Phase 2U.5 category bilingual forms diagnostic is scripts/phase_2/youngo_phase_2u5_category_bilingual_forms_diagnostic.php and should pass locally.
Phase 2U.5.4 Course Add/Edit Bilingual Form Support and Course Form UX Alignment is implemented, runtime-QA tested, reviewed, and committed in commit 50ae714 Add YounGo course bilingual form support.
Phase 2U.5.4 updated application/helpers/common_helper.php, application/models/Crud_model.php, application/views/backend/admin/course_add.php, application/views/backend/admin/course_add_shortcut.php, application/views/backend/admin/course_edit.php, scripts/phase_2/youngo_phase_2u4_arabic_phrase_diagnostic.php, scripts/phase_2/youngo_phase_2u5_category_bilingual_forms_diagnostic.php, scripts/phase_2/youngo_phase_2u5_translation_model_diagnostic.php, and scripts/phase_2/youngo_phase_2u5_course_bilingual_forms_diagnostic.php.
Course add/edit forms now collect English and optional RTL Arabic course fields for title, slug, short_description, description, outcomes, requirements, faqs, seo_title, meta_keywords, and meta_description. English fields are canonical and sync to canonical course fields for Academy LMS compatibility. English course translation rows are upserted after canonical create/update. Arabic course translation rows are upserted only when Arabic content is non-empty; English-only courses do not create Arabic translation rows.
Course shortcut remains English-oriented and creates canonical English course data plus an English translation row. Arabic completion is deferred to full course edit.
Course language_made_in is course-content metadata only. Valid marker values are english, arabic, and arabic_translated. The arabic_translated marker is valid only for course content/video/material metadata and must not be stored in youngo_course_translations.language_code. Translation table language codes remain only english and arabic; site language and /ar routing are unaffected.
Runtime QA found and fixed a course edit HTTP 500 / partial blank page. course_edit.php now loads Youngo_translation_model safely via the CodeIgniter instance, English fallback values load safely, Arabic arrays default safely without Arabic translations, and malformed HTML in reviewed course edit sections was fixed.
Pricing/access UX now hides/disables one-time price and discount fields for subscription_only courses with helper copy; purchase_only and subscription_and_purchase keep price/discount fields available. Crud_model::update_course() preserves existing price/discount values when disabled fields are not posted. Add/shortcut paths default absent price fields safely. No checkout/payment/Paymob/order/coupon behavior was implemented.
EGP display was normalized in common_helper.php for readability while respecting configured currency position. Observed local settings were system_currency = EGP and currency_position = left, with readable display such as EGP 500. DB currency values, prices, checkout, payment, and Paymob logic were not changed. Arabic EGP-symbol display may be revisited later when Arabic UI context is fully implemented.
Runtime QA used a temporary admin account, not Root Admin. Backup before QA was D:\Work\YounGo\backups\youngo_school_before_phase_2u5_4_temp_admin_runtime_qa_2026_07_17_002027.phpdbdump. QA verified course edit/add rendering, bilingual fields, language markers, access/pricing behavior, EGP display, temporary bilingual course add/edit, redirect to non-blank edit page, English-only no-Arabic-row behavior, and no duplicate translation rows. After restore, temporary QA data from the final QA turn was removed; existing manual course QA data from before the backup remained intentionally present: course = 8, english course translations = 8, arabic course translations = 1, and protected payment/watch/progress/entitlement/checkout/coupon rows = 0.
Phase 2U.5.5 Section/Lesson Bilingual Form Support is implemented, focused-reviewed, runtime-QA tested, restored, and committed in commit f3368fc Add YounGo section lesson bilingual form support.
Phase 2U.5.5 updated application/models/Crud_model.php, application/views/backend/admin/section_add.php, application/views/backend/admin/section_edit.php, application/views/backend/admin/lesson_add.php, application/views/backend/admin/lesson_edit.php, application/views/backend/admin/text_type_lesson_add.php, application/views/backend/admin/text_type_lesson_edit.php, scripts/phase_2/youngo_phase_2u5_course_bilingual_forms_diagnostic.php, and scripts/phase_2/youngo_phase_2u5_section_lesson_bilingual_forms_diagnostic.php.
Section add/edit forms now collect required or primary english_title and optional RTL arabic_title. Canonical section.title syncs from English. English section translations are upserted after canonical create/update. Arabic section translations are upserted only when Arabic title is non-empty. Blank Arabic fields do not create empty Arabic rows and do not delete existing Arabic rows in this phase.
Lesson add/edit forms now collect required or primary english_title, optional english_summary, optional RTL arabic_title, and optional RTL arabic_summary. Canonical lesson.title and lesson.summary sync from English. English lesson translations are upserted after canonical create/update. Arabic lesson translations are upserted only when at least one Arabic translatable field is non-empty. Blank Arabic fields do not create empty Arabic rows and do not delete existing Arabic rows in this phase.
Text lesson add/edit partials now collect english_text_content and optional RTL arabic_text_content. Canonical text lesson body/content syncs from English. Arabic text content is stored only in youngo_lesson_translations. Non-text lessons are not forced to provide text content; media/video/PDF/file/shared fields remain shared and are not translated.
Crud_model::sync_legacy_lesson_post_fields() preserves legacy media handler compatibility by copying English bilingual lesson fields into legacy POST keys such as title, summary, and text_description before legacy handlers run. This protects Academy Cloud/video/media behavior from blank titles after the bilingual field rename.
Youngo_translation_model is used for youngo_section_translations and youngo_lesson_translations. Translation table language codes remain english and arabic only; arabic_translated must not be used as a translation-table language code.
Phase 2U.5.5 runtime QA used backup D:\Work\YounGo\backups\youngo_school_before_phase_2u5_5_section_lesson_bilingual_qa_2026_07_18_194034.sql. Temporary admin login succeeded. A temporary Course Manager role plus legacy course/category permissions were used only under backup and removed by restore. QA verified bilingual section add/edit, bilingual text lesson add/edit, English-only section and text lesson no-Arabic-row behavior, minimal YouTube lesson media/title compatibility, canonical English sync, English upserts, conditional Arabic upserts, no duplicate translation rows, clean Arabic UTF-8, temporary data removal, and protected rows clean.
After Phase 2U.5.5 restore, pre/post counts matched: course = 8, section = 18, lesson = 36, youngo_section_translations total = 18, section English = 18, section Arabic = 0, youngo_lesson_translations total = 36, lesson English = 36, lesson Arabic = 0, youngo_user_roles = 0, permissions = 2, enrol = 1, and payment/watch/progress/entitlement/checkout/coupon protected rows = 0.
The Phase 2U.5.5 diagnostic is scripts/phase_2/youngo_phase_2u5_section_lesson_bilingual_forms_diagnostic.php. It checks expected files and bilingual fields, Arabic RTL markers, shared media/type/course/section fields, Youngo_translation_model save-path usage, English upserts, conditional Arabic upserts, translation tables and English coverage, Arabic section/lesson rows staying 0 after restore, protected rows, and role/course/category/translation diagnostic compatibility.
Runtime QA found the temporary admin lacked the legacy course permission. It was adjusted only inside QA and restored afterward. Phase 2V.0 audited the two permission systems, and Phase 2V.1 Role Assignment Management is implemented, runtime-QA tested, reviewed, and committed in commit 21aecd7 Add YounGo role assignment management.
Phase 2V.1 adds /admin/youngo/role-assignments and /admin/youngo/role-assignments/update under YounGo -> Role Assignments, guarded by manage_roles. The page lists users with Root Admin, Admin, Content, Course, and Instructor toggles.
The approved role authority model is: Root Admin = developer/system owner/highest authority; Admin = client/operational owner; Content, Course, and Instructor = scoped operational roles. Root Admin can manage everything and remains protected from everyone else. Admin can manage non-root roles but cannot modify Root Admin. Content/Course/Instructor cannot manage roles unless explicitly granted manage_roles. Admin is mutually exclusive with Content/Course/Instructor, while Content, Course, and Instructor can combine freely; Content + Course is the practical Content & Course Manager state.
Root Admin is visible but read-only in the Role Assignments UI. Root Admin controls are disabled, tampered Root Admin updates are rejected server-side with protected_root_admin, Root Admin user/credential fields are not touched, and Root Admin keeps supreme authority through protected bypass/full authority.
Phase 2V.1 intentionally bridges legacy admin permissions and YounGo capabilities. Legacy compatibility still uses users.role_id, users.is_instructor, permissions.permissions, and check_permission(); YounGo roles use youngo_roles, youngo_capabilities, youngo_role_capabilities, youngo_user_roles, and the capability helper. This bridge is required while legacy course/category pages still depend on check_permission('course') and check_permission('category').
Admin maps to manage_roles through reversible seed files database/phase_2/youngo_phase_2v1_admin_manage_roles_up.sql and database/phase_2/youngo_phase_2v1_admin_manage_roles_down.sql. The up script maps only YounGo admin to manage_roles and does not grant manage_roles to content_manager/course_manager/instructor. The down script removes only admin -> manage_roles and does not delete role/capability rows or unrelated mappings.
Role assignment updates must keep a permissions row for non-root users to avoid the legacy no-permissions-row full-access behavior. Admin receives controlled operational legacy permissions. Course grants legacy course and category. Content grants legacy category. Instructor assigns the YounGo instructor role and current LMS instructor state without granting course/admin access unless combined with Course.
Phase 2V.1 runtime QA used the temporary admin account and did not modify Root Admin. Backup before QA was D:\Work\YounGo\backups\youngo_school_before_phase_2v1_admin_manage_roles_alignment_2026_07_17_061234.sql. QA verified Admin access to Role Assignments, non-root role updates, Root Admin read-only UI, protected_root_admin rejection for tampered Root Admin update, Course-only denial from Role Assignments, scoped roles without manage_roles, Admin mutual exclusion, restored temporary QA assignments, reapplied required admin -> manage_roles mapping, and clean protected payment/checkout/entitlement/manual-grant/coupon rows.
The Phase 2V.1 diagnostic is scripts/phase_2/youngo_phase_2v_role_assignment_diagnostic.php. It checks role assignment files/routes/guards, Root Admin protection markers, Admin mutual exclusion, admin -> manage_roles, scoped roles without manage_roles, SQL seed files, legacy bridge markers, and payment/checkout scope boundaries.
Phase 2U.6.2 Frontend Language Context and URL Mapping Helpers is implemented, focused-reviewed, committed, and diagnostics-tested in commit 25e0544 Add YounGo frontend language context helpers. It added application/helpers/youngo_frontend_language_helper.php and scripts/phase_2/youngo_phase_2u6_frontend_language_context_diagnostic.php. The helper normalizes frontend language aliases to english/arabic only, treats arabic_translated as an input alias to arabic only, detects ar-prefixed frontend URIs, maps equivalent English and Arabic URLs while preserving query strings, excludes admin/payment/checkout/cart/coupon/write/API/cron paths, and provides future html lang/dir helpers. It is not autoloaded and did not add routes, frontend rendering, content translation shaping, session/cookie/settings writes, or checkout/payment changes.
Phase 2U.6.3 Arabic Public Route Aliases is implemented, focused-reviewed, diagnostics-tested, committed, and pushed in commit 4b773b2 Add YounGo Arabic public route aliases. English canonical frontend URLs remain unprefixed. Arabic canonical public frontend URLs use /ar/..., and /en must not become canonical. No /en routes were added and no English routes were redirected to /en. Admin/dashboard and checkout/payment/cart/coupon/write/API/cron routes remain unlocalized. arabic_translated is not a route language, UI language, or translation-table language; course language_made_in remains separate course-content metadata.
Arabic public aliases added in Phase 2U.6.3 are /ar -> home/index, /ar/courses -> home/courses, /ar/courses/{page} -> home/courses, /ar/course/{slug}/{id} -> home/course/$1/$2, /ar/search -> home/search, /ar/search/{query} -> home/search/$1, /ar/my-courses -> home/my_courses, /ar/my-access -> home/my_access, /ar/wishlist -> home/my_wishlist, /ar/login -> login/index, and /ar/sign-up -> sign_up/index. English unprefixed routes remain unchanged. Course detail preserves slug then id argument order, search preserves the query argument, and course pagination remains compatible with Home::courses() using URI segment 3. /ar/login and /ar/sign-up target the existing public auth controllers.
Phase 2U.6.3 did not add aliases for admin, addons, api, cron, home/payment, home/paypal, home/stripe, home/paymob, home/razorpay, home/paystack, home/flutterwave, home/course_payment, home/shopping_cart, home/update_cart, home/apply_coupon, home/remove_coupon, home/checkout, home/confirm_payment, home/webhook, coupon write routes, cart write routes, or payment callback routes. Lesson/player/PDF aliases remain deferred, including Home::lesson, Home::pdf_canvas, Home::play_lesson, mobile lesson helpers, and offline video helpers, because those are gated playback/progress/session surfaces that need separate staged QA before Arabic aliases are added.
Phase 2U.6.3 did not implement frontend translated content rendering, frontend RTL shell rendering, a language switcher UI, course/category/section/lesson translation shaping, session language writes, cookie writes, settings writes, phrase writes, checkout/payment/cart/coupon localization, or admin/dashboard localization. Arabic route aliases may still render existing canonical frontend content until later translation-aware rendering phases.
The Phase 2U.6.3 diagnostic is scripts/phase_2/youngo_phase_2u6_arabic_route_alias_diagnostic.php. It checks /ar public aliases, preserved English routes, no /en routes, no admin/payment/checkout/cart/coupon/API/cron Arabic aliases, argument mapping, route order, Phase 2U.6.2 helper compatibility, no arabic_translated route/UI language usage, no frontend rendering/content translation changes, and Phase 2S/2P/2R compatibility. Focused review also tightened existing diagnostics so routes.php changes are tolerated only when the diff is Arabic-alias-only. PHP lint and compatibility diagnostics passed. HTTP GET smoke was intentionally skipped; static route diagnostics were used instead.
Phase 2U.6.4 Translation-aware Frontend Content Shaping is implemented, focused-reviewed, runtime-QA tested, restored, committed, and pushed in commit 12fcdae Add YounGo frontend content translation shaping. It added application/helpers/youngo_frontend_content_helper.php and scripts/phase_2/youngo_phase_2u6_frontend_content_translation_diagnostic.php, and updated Home.php, common_helper.php, YounGo course listing/detail/wishlist views, and Phase 2U.6 diagnostics.
The Phase 2U.6.4 helper uses Youngo_translation_model and the Phase 2U.6.2 frontend language context helper. It shapes only whitelisted display fields for home, courses, search, course detail, my courses, my access, and wishlist initial page data; preserves canonical IDs and operational fields; stores original canonical values in safe youngo_canonical_* metadata; never emits/stores arabic_translated; and does not write DB/session/cookie/settings or call unknown get_phrase() keys.
Fallback order is Arabic route = arabic translation -> english translation -> canonical LMS field, and English route = english translation -> canonical LMS field. Course shaped fields are title, short_description, description, outcomes, requirements, faqs, seo_title, meta_keywords, and meta_description. Category display name can be shaped while canonical category ID/slug remain filter and URL identity. Section title and lesson title/summary can be shaped outside deferred player/PDF routes, with helper support for text lesson body on non-player display paths. IDs, slugs/link identity, price, discount, currency, access modes, media, instructor, level, progress, entitlement/access fields, CTA state, wishlist state, checkout/payment fields, lesson type, duration, attachment, video URL, and player/PDF routes remain unchanged.
Phase 2U.6.4 runtime QA used backup D:\Work\YounGo\backups\youngo_school_before_phase_2u6_4_frontend_content_translation_qa_2026_07_18_215217.sql. Temporary Arabic rows were inserted only into YounGo translation tables for course 1, category 7, section 1, and lesson 1; no canonical rows were changed. Restore removed temporary rows and matched the pre-QA counts, including course 8, category 12, section 18, lesson 36, course translations total 9 / English 8 / Arabic 1, category Arabic 0, section Arabic 0, lesson Arabic 0, ci_sessions 636, youngo_user_roles 0, permissions 2, enrol 1, and protected payment/watch/progress/entitlement/checkout/coupon rows 0.
QA confirmed / and /home/courses remain English/canonical, /ar and /ar/courses display Arabic shaped values where temporary translations exist, /home/course/scratch-coding-for-young-creators/1 remains English/canonical, /ar/course/scratch-coding-for-young-creators/1 displays Arabic shaped course/section/lesson values where available, search Arabic aliases resolve, and /ar/wishlist, /ar/my-courses, and /ar/my-access resolve without fatal/404 in unauthenticated safe GET checks. Excluded /ar/admin, /ar/home/payment, /ar/home/checkout, /ar/home/shopping_cart, /ar/home/apply_coupon, /ar/api, and /ar/cron render the app 404 page. No write endpoints were submitted and no /en links were introduced.
Current working English routes for course/list/search/detail surfaces remain /home/...; direct /courses, /course/..., /search, and /wishlist are not current English routes. Arabic public route aliases use /ar/... and English canonical URL strategy remains unprefixed.
The Phase 2U.6.4 diagnostic is scripts/phase_2/youngo_phase_2u6_frontend_content_translation_diagnostic.php. It checks helper functions, Home.php integration points, /ar aliases, no /en routes, no new route changes, no payment/checkout/cart/coupon localization, no arabic_translated table-language usage, canonical IDs/access/media/progress preservation in shaping logic, read-only sample shaping, and route/language/section-lesson/role/course-category/translation/access diagnostic compatibility.
Phase 2U.6.5 Language Switcher and RTL Shell Rendering is implemented, focused-reviewed, runtime-QA tested, restored, committed, and pushed in commit 8321e91 Add YounGo language switcher and RTL shell.
Phase 2U.6.5 updated application/helpers/youngo_frontend_language_helper.php, application/views/frontend/youngo/header.php, application/views/frontend/youngo/index.php, assets/frontend/youngo/css/youngo.css, scripts/phase_2/youngo_phase_2u5_section_lesson_bilingual_forms_diagnostic.php, scripts/phase_2/youngo_phase_2u6_arabic_route_alias_diagnostic.php, scripts/phase_2/youngo_phase_2u6_frontend_content_translation_diagnostic.php, scripts/phase_2/youngo_phase_2u6_frontend_language_context_diagnostic.php, and scripts/phase_2/youngo_phase_2u6_language_switcher_rtl_diagnostic.php.
The YounGo frontend shell now uses route-derived language helper values. English routes render html lang="en" dir="ltr"; Arabic /ar routes render html lang="ar" dir="rtl". The frontend body adds helper-derived and escaped youngo-lang-english/youngo-lang-arabic, youngo-dir-ltr/youngo-dir-rtl, data-youngo-language, and data-youngo-dir metadata while preserving existing body classes. The admin/backend shell remains unaffected.
The YounGo frontend header now includes an EN | عربي language switcher with active state and aria-current. Switcher URLs are helper-generated, preserve query strings, never generate /en, never show/link arabic_translated, and map working English /home/... route reality to Arabic aliases.
Focused review fixed a subdirectory URL risk: youngo_frontend_current_uri_string_with_query() now prefers CodeIgniter's $CI->uri->uri_string() over raw REQUEST_URI and uses REQUEST_URI only as fallback, because raw REQUEST_URI can include the app base path on subdirectory installs and generate wrong switcher URLs.
Minimal YounGo-scoped RTL CSS was added in assets/frontend/youngo/css/youngo.css for header/nav shell behavior. The language switcher remains readable/LTR, and full visual RTL polish remains deferred.
Phase 2U.6.5 runtime QA used backup D:\Work\YounGo\backups\youngo_school_before_phase_2u6_5_language_switcher_rtl_qa_2026_07_18_224356.sql. No temporary Arabic rows were needed. Runtime QA verified correct html lang/dir and body metadata on / and /ar, switcher active states, / <-> /ar, /home/courses <-> /ar/courses including query preservation, /home/course/scratch-coding-for-young-creators/1 <-> /ar/course/scratch-coding-for-young-creators/1 with slug/id preservation, /home/search?query=Scratch <-> /ar/search?query=Scratch, /ar/login and /ar/sign-up using the YounGo shell, /ar/wishlist, normal unauthenticated redirect behavior for /ar/my-courses and /ar/my-access, and app 404 behavior for excluded /ar/admin, /ar/home/payment, /ar/home/checkout, /ar/home/shopping_cart, /ar/home/apply_coupon, /ar/api, and /ar/cron. No write endpoints were called. Restore succeeded and pre/post counts matched exactly, including ci_sessions 636, course 8, category 12, section 18, lesson 36, course translations total 9 / English 8 / Arabic 1, category Arabic 0, section Arabic 0, lesson Arabic 0, enrol 1, and protected YounGo entitlement/payment/checkout/coupon/progress tables 0.
The Phase 2U.6.5 diagnostic is scripts/phase_2/youngo_phase_2u6_language_switcher_rtl_diagnostic.php. It checks shell html lang/dir helper usage, English en/ltr and Arabic ar/rtl mapping, body metadata, switcher existence, helper-generated URLs, query preservation, no /en links/routes, no route additions, no session/cookie/settings language writes, no DB/phrase writes, no checkout/payment/cart/coupon localization, admin shell boundaries, and content/route diagnostic compatibility.
PDF-specific browser QA was skipped to avoid broadening scope; broader API/mobile entitlement UX remains future work.
After QA cleanup, entitlement write tables are empty locally: youngo_course_access = 0, youngo_user_subscriptions = 0, youngo_manual_grants = 0, and youngo_checkout_orders = 0.
Checkout issuance methods are safe non-writing stubs returning checkout_issuance_not_implemented; checkout/order completion is not implemented.
Do not create real grants, access rows, or subscriptions through manual row-level SQL.
Future grant/access controllers must enforce grant_manual_access before calling the write service.
Learner access data must use centralized read methods in Youngo_entitlement_model rather than duplicating entitlement queries in views.
Future temporary grant/subscription QA must use the Manual Grants UI and restore from backup after temporary rows unless the phase explicitly creates a durable baseline.
Revocation must preserve progress, enrolment, payment, watch history, subscription plan, and checkout/order rows.
Do not use legacy free enrol, legacy coupons, legacy cart, or legacy checkout as shortcuts for YounGo-managed access. Coupons must later go through formal checkout/order/access issuance.
Frontend phrase conversion/polish, My Courses/Wishlist AJAX language propagation, full RTL visual polish, performance batching for translation reads, hreflang/canonical SEO, lesson/player/PDF Arabic aliases, mobile/API entitlement alignment, course data rebuild, and checkout/Paymob/subscription purchase flow remain future work. Recommended next roadmap: Phase 2U.6.6 frontend phrase conversion/inventory, Phase 2U.6.7 controlled frontend localization QA and diagnostics, Phase 2U.7 bilingual QA plus phrase polish QA, Phase 2T course data rebuild, then checkout/Paymob/subscription purchase flow. Phase 2V.2 role-assignment docs/UX polish may be handled only if needed and is not blocking localization.
```

Target direction:

```text
Subscriptions are added beside existing course purchase.
Coupons/discounts go through checkout/payment flow and result in formal access records.
Manual grants are admin-created and auditable.
Direct checkout is the target YounGo UX, while cart remains for compatibility.
Learner/User capability should be automatic for all accounts.
Accounts should support multiple roles/capabilities.
    Root Admin is protected and not a normal toggle.
```

### Local demo data policy

The local YounGo database contains no real production, client, learner, payment, enrolment, or operational data.

Root Admin is the only local identity protected by default. Do not accidentally delete, disable, downgrade, lock, or strip Root Admin of essential access. Future reset or baseline scripts must explicitly preserve Root Admin unless the project owner gives a separate instruction. Do not assume all `role_id = 1` users are protected Root Admins.

Other local users and all local courses, categories, subcategories, sections, lessons, media, enrolments, access records, subscriptions, and progress rows are demo/development/test data. They may be edited, deleted, rebuilt, reassigned, completed, archived, or replaced when that improves correct product flows, role and capability behavior, course ownership, instructor assignments, entitlement behavior, subscription behavior, manual grants, checkout/orders, payments, QA coverage, or overall consistency.

Do not preserve incomplete demo data merely because it already exists. Existing demo records must not dictate system architecture.

Approved database-write phases still require a user-created phpMyAdmin backup before apply. Data changes must remain scoped, documented, and reversible where practical. Temporary QA data and fixtures must not be included in production deployment packages.

### Phase 2 dependency rules

New Phase 2 admin pages must use minimal YounGo capability enforcement before they are introduced. Full role-management UI is not required before every feature, but new pages must not rely only on broad `role_id == 1` checks.

Course access settings must be manageable before course purchase checkout is built. Subscription plans must be manageable before subscription checkout. Manual grants and checkout must use the shared entitlement write service, and access issuance must be auditable and idempotent.

Coupons must operate through formal orders and must never directly grant access. Paymob must be added only after the internal order lifecycle and access issuance are stable.

Before any localization schema, subscription, manual grant, or entitlement write implementation/QA, run:

```text
php scripts/phase_2/youngo_phase_2u5_course_bilingual_forms_diagnostic.php
php scripts/phase_2/youngo_phase_2u5_section_lesson_bilingual_forms_diagnostic.php
php scripts/phase_2/youngo_phase_2u5_category_bilingual_forms_diagnostic.php
php scripts/phase_2/youngo_phase_2u5_translation_model_diagnostic.php
php scripts/phase_2/youngo_phase_2u4_arabic_phrase_diagnostic.php
php scripts/phase_2/youngo_phase_2u3_localization_schema_diagnostic.php
php scripts/phase_2/youngo_phase_2j_capability_diagnostic.php
php scripts/phase_2/youngo_phase_2l_subscription_plan_diagnostic.php
php scripts/phase_2/youngo_phase_2m_entitlement_write_diagnostic.php
php scripts/phase_2/youngo_phase_2p_learner_access_visibility_diagnostic.php
php scripts/phase_2/youngo_phase_2r_admin_entitlement_summary_diagnostic.php
php scripts/phase_2/youngo_phase_2s_route_cta_boundary_diagnostic.php
```

The Phase 2L diagnostic reports schema state, expected currency EGP, current system currency, non-EGP subscription plan count/list, capability checks, and commercial readiness blocked/unblocked. A structural PASS does not mean subscriptions are production-ready if readiness is still blocked.
After the seeded placeholder correction, the expected local diagnostic state is `settings.system_currency = EGP`, `non_egp_subscription_plan_count = 0`, and no currency blocker. This still does not make checkout, payment, or subscription issuance ready.
The Phase 2M diagnostic should report PASS, Phase 2M schema applied, no active duplicates, no orphan issues, currency readiness clean, `grant_manual_access` exists, Root Admin has it, and user 7 does not.
The Phase 2P diagnostic should report PASS, required learner access files/methods present, My Access/profile-menu wiring present, Phase 2M schema readiness present, entitlement tables empty after QA cleanup, and EGP currency readiness clean.
The Phase 2R diagnostic should report PASS, required admin summary files/methods present, user/course edit integration present, Manual Grants route present, Phase 2M schema readiness present, entitlement tables empty after QA cleanup, and EGP currency readiness clean.
The Phase 2S diagnostic should report PASS, the free-enrol route guard present, CTA-boundary source checks present, Course 1 in the subscription-only fixture state, entitlement tables empty after QA cleanup, and checkout/payment/coupon/order counts clean.
The Phase 2U.3 diagnostic should report PASS, translation tables present, expected columns/indexes present, English translation coverage matching canonical counts, no duplicate entity/language pairs, only expected language codes, and entitlement tables empty.
The Phase 2U.4 diagnostic should report PASS, language.arabic present, English still default, settings.language_dirs containing arabic: rtl, 100% Arabic phrase coverage for non-empty English phrases, no mojibake, valid translation language-code boundaries, guarded non-course Arabic translation rows, and entitlement/payment rows clean.
The Phase 2U.5 translation model diagnostic should report PASS, Youngo_translation_model present/loadable, expected methods present, language normalization canonical, direct read/fallback methods working through SELECT-only checks, course Arabic translation rows tolerated where created by scoped course forms, non-course Arabic content rows guarded, language.arabic present, settings.language still english, and protected rows clean.
The Phase 2U.5 category bilingual forms diagnostic should report PASS, category/subcategory bilingual fields present, shared fields preserved, save path wired to Youngo_translation_model, English category translation coverage present, Arabic category translation rows unchanged after restore, no course/section/lesson form changes, and protected rows clean.
The Phase 2U.5 course bilingual forms diagnostic should report PASS, course add/edit bilingual fields present, shortcut safe and English-oriented, language marker values english/arabic/arabic_translated present, translation-table language codes limited to english/arabic, category/section/lesson Arabic rows guarded appropriately, EGP display source checks present, checkout/payment source boundaries clean, and protected rows clean.
The Phase 2U.5 section/lesson bilingual forms diagnostic should report PASS, section/lesson/text lesson bilingual fields present, Arabic RTL markers present, shared media/type/course/section fields preserved, save paths wired to Youngo_translation_model, English section/lesson translation coverage present, Arabic section/lesson rows unchanged at 0 after restore, no empty Arabic row logic, role/course/category/translation diagnostics compatible, and protected rows clean.

No broad learner or commercial rollout should be considered ready before the remaining web gates are reviewed and authenticated QA is completed. The curated demo/system baseline should be rebuilt after the management flows exist, using actual system flows wherever practical.

---

## 14. Admin Dashboard Rules

The existing CMS dashboard should remain the foundation.

YounGo-specific admin screens should be added carefully and consistently with existing admin patterns.

Admin implementation should prioritize:

```text
Clear page titles
Simple forms
Content-focused cards
Visibility controls
Save/publish clarity
Preview where practical
Non-technical usability
```

Avoid:

```text
Complex generic page builders
Overly visual drag-and-drop systems unless planned
Theme marketplace logic
Styling controls for admins
Breaking existing admin navigation
```

---

## 15. Controller and Model Rules

Follow existing CodeIgniter patterns.

Before adding new methods:

```text
Inspect the existing controller/model style.
Reuse existing helpers where safe.
Keep method names clear.
Avoid large unrelated controller rewrites.
Avoid placing complex business logic directly in views.
```

Prefer placing reusable data access logic in models.

Avoid duplicating similar queries across multiple views.

---

## 16. View Rules

Views should be maintainable and readable.

Use view partials where they improve clarity.

Avoid very large single-page views when a page can be cleanly split into sections.

Recommended approach for complex frontend pages:

```text
Main page view
Shared header/footer
Section partials
Small reusable components where useful
```

Do not mix large business logic into view files.

Light formatting and display logic is acceptable if consistent with the existing CMS style.

---

## 17. Asset Rules

YounGo assets should live under:

```text
assets/frontend/youngo/
```

Suggested structure:

```text
assets/frontend/youngo/css/
assets/frontend/youngo/js/
assets/frontend/youngo/images/
```

Avoid editing global assets unless necessary.

Avoid relying on external prototype CDNs for production UI.

If external fonts or icons are used, document the choice and keep fallbacks.

---

## 18. Database Change Rules

Do not make database changes casually.

Any database change must be justified by planning documents.

Before creating new tables or fields, check whether the existing CMS already has a suitable structure.

Database changes should be:

```text
Minimal
Named clearly
Related to YounGo content needs
Backward-compatible where possible
Documented in docs/planning/
Easy to recreate on another environment
```

Do not remove or rename existing LMS tables/columns unless explicitly approved.

---

## 19. Local Runtime Rules

Do not assume the application runs locally until the database is configured/imported.

Before runtime testing, confirm:

```text
Database config exists locally
Database has been imported
Base URL is configured
Required uploads/assets exist
Server/PHP version is compatible enough to run the CMS
```

If runtime setup is missing, perform static analysis and implementation planning only.

---

## 20. Testing Rules

Use browser testing where available.

Playwright MCP can be used to validate:

```text
Homepage rendering
Course details rendering
Navigation links
Responsive behavior
Admin screen rendering
CMS edit-to-frontend flow
Broken layout checks
Console errors where visible
```

Testing expectations should follow the relevant planning document.

Do not claim a feature works unless it has been tested or the limitation is clearly stated.

---

## 21. Accessibility Rules

Minimum accessibility expectations:

```text
Meaningful button labels
Visible focus states
Readable contrast
Alt text for important images
Form labels
Status text in addition to color
Comfortable mobile tap targets
Keyboard-friendly basic interactions
```

Do not sacrifice readability for visual decoration.

---

## 22. Error Handling Rules

When adding forms or admin actions, handle:

```text
Missing input
Invalid IDs
Missing records
Permission/session checks consistent with existing admin patterns
Upload failures
Database update failures
```

Use existing project conventions for flash messages, redirects, and admin authentication.

---

## 23. Coding Style Rules

Keep code simple and project-consistent.

Prefer:

```text
Clear names
Small focused methods
Readable PHP
Readable CSS
Minimal JavaScript
Comments only when useful
Existing conventions over new patterns
```

Avoid:

```text
Clever abstractions
Large unrelated refactors
Dead code
Commented-out blocks
Mixing multiple unrelated tasks in one change
```

---

## 24. Approval Rules

Ask for approval before:

```text
Changing database structure
Changing authentication/session behavior
Changing access, entitlement, subscription, checkout, payment, coupon, invoice, role, permission, or instructor-assignment behavior
Deleting files
Replacing existing frontend/admin flows
Installing dependencies
Running destructive commands
Committing or pushing
Large refactors
```

Small additive changes inside approved scope may proceed only after the user has approved the relevant plan.

---

## 25. Final Implementation Principle

Build YounGo carefully as a focused layer on top of the existing LMS/CMS.

Protect the existing system.

Add only what is needed.

Keep the website polished.

Keep the CMS manageable.

Keep documentation separated by purpose.

````
