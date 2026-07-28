# AGENTS.md

## 1. Purpose

This file gives high-priority instructions for AI/code agents working on the YounGo repository.

It is intentionally short.

Do not treat this file as the full project plan, full design system, or full implementation guide.

Before making changes, agents must read the supporting documentation listed below.

The current master planning reference is:

```text
docs/planning/youngo_master_plan_v2.md
```

---

## 2. Required Reading Order

Before modifying files, read these documents in order:

```text
YOUNGO_PROJECT_CONTEXT.md
docs/design/youngo_style_direction.md
docs/planning/
docs/planning/youngo_phase_2_hybrid_access_subscriptions_roles_architecture.md when working on access, subscriptions, coupons, manual grants, checkout, payments, roles, permissions, or instructor assignment
docs/agents/implementation_rules.md
docs/reference/
````

Use each location for its intended purpose:

```text
YOUNGO_PROJECT_CONTEXT.md        = high-level project context
docs/design/                     = visual identity and UI/UX direction
docs/reference/                  = existing CMS documentation, findings, exports, screenshots
docs/planning/                   = delivery phases and implementation plans
docs/agents/                     = detailed agent/development rules and prompts
```

If a required document or folder is missing, stop and report what is missing before making assumptions.

---

## 2A. Master Plan Reference

The current source of truth for project priorities, architecture, reuse decisions, deployment preparation, and implementation sequencing is:

[`youngo_master_plan_v2.md`](./docs/planning/youngo_master_plan_v2.md)

All implementation work must be reviewed against this plan before modifying or rebuilding existing Academy LMS functionality.

Academy LMS must be reused as the core system whenever possible. Existing working LMS functionality must not be rebuilt unless reuse has been checked against the master plan and proven impractical.

The current Phase 2 architecture reference is:

[`youngo_phase_2_hybrid_access_subscriptions_roles_architecture.md`](./docs/planning/youngo_phase_2_hybrid_access_subscriptions_roles_architecture.md)

Read it before modifying access, subscriptions, course purchase, coupons, manual grants, checkout, payments, roles, permissions, or instructor assignment.

---

## 3. Project Summary

YounGo is a kids learning platform built on top of an existing Academy LMS / PHP CodeIgniter CMS.

The goal is to create a custom YounGo frontend and focused CMS content-management experience while keeping the existing LMS/CMS as the backend foundation.

Do not rewrite the system from scratch.

Do not replace the existing CMS dashboard unless explicitly instructed.

---

## 4. Technology Stack

This project is a PHP CodeIgniter MVC application.

Important rule:

```text
This is not Laravel.
```

Do not use Laravel commands such as:

```text
php artisan
```

Do not assume Laravel structure, migrations, routes, Blade, or Eloquent.

Work within the existing CodeIgniter-style structure:

```text
application/controllers/
application/models/
application/views/
application/config/
assets/
uploads/
```

---

## 5. Product Direction

The approved direction is:

```text
Create a new custom YounGo frontend/theme.
Keep the visual design fixed and consistent.
Make website content manageable from the CMS.
Reuse existing LMS data where useful.
Add YounGo-specific CMS structures only where the existing CMS is too limited.
Avoid complex generic page builders.
Avoid focusing on old prebuilt themes as a product feature.
```

The platform is for teaching kids.

The design must feel safe, friendly, modern, premium, and trustworthy for parents.

---

## 6. Design Rules

The approved visual direction is documented in:

```text
docs/design/youngo_style_direction.md
```

Do not invent a new visual identity unless explicitly asked.

Use the approved YounGo direction:

```text
Soft purple-led identity
Rounded cards
Friendly kids-learning feel
Parent-trust focused messaging
Clean SaaS-style admin UI
Fixed theme styling
CMS-managed content
```

Stitch-generated screens and exports are design references, not production-ready code.

Do not paste Stitch HTML directly into production without converting it into maintainable CodeIgniter views, local assets, CMS data, and project CSS/JS.

---

## 7. CMS and Content Rules

The CMS should manage content, not visual styling.

Admins may manage website content such as:

```text
Hero content
Images
CTA labels and links
Featured categories
Featured courses
About content
Benefits
Testimonials
FAQs
Blog/news previews
Contact information
Menu links
SEO content
Section visibility
Section ordering
Draft/published state
```

Do not add unnecessary visual styling controls unless a planning document explicitly requires them.

---

## 7A. Phase 2 Access, Payment, And Role Guardrails

Phase 2 adds hybrid access, subscriptions, manual grants, coupon scope improvements, direct checkout, Paymob planning, and multi-role/capability architecture beside the existing Academy LMS.

Do not:

```text
Remove existing course purchase.
Remove existing enrolment/payment logic before a compatibility layer exists.
Remove existing cart code while adding direct checkout UX.
Treat coupon/discount as direct access.
Treat public free-course classification as the target YounGo business model.
Enable real payments before implementation and QA approval.
Abruptly replace role_id, permissions, is_instructor, enrol, payment, invoice, or lesson access behavior.
Downgrade or casually modify Root Admin.
    Claim subscriptions, manual grants, expanded coupons, Paymob payments, or multi-role capability are implemented before they are built and tested.
```

Current subscription/currency status:

```text
Subscription Plan Management is implemented and QA-tested locally, including dashboard plan CRUD/actions, archive/restore support, and audit logging.
The Phase 2L archive/audit schema is applied locally.
YounGo commercial currency is EGP, and the global system currency has been changed to EGP through the dashboard.
The seeded Monthly, 3 Months, and Yearly subscription placeholders now store EGP and remain inactive/non-purchasable.
Their current local prices are temporary placeholders only: Monthly 100.00 EGP, 3 Months 250.00 EGP, and Yearly 900.00 EGP.
Currency readiness is clean for subscription plan definitions, but subscriptions are not production-ready.
Do not create, activate, or make real commercial subscription plans purchasable until final prices are approved and the owner explicitly approves activation.
Do not use manual row-level SQL to correct real/seeded plan currency unless explicitly approved.
Gateway currency alignment is deferred to the payment/Paymob phase and is not a Subscription Plan Management blocker.
Subscription Plan Management does not issue subscriptions, checkout orders, payments, coupons, enrolments, or access.
```

Current entitlement write-service status:

```text
Phase 2M Shared Entitlement Write Service foundation is implemented, schema-applied, committed, and controlled service-QA tested locally.
The service is application/models/Youngo_entitlement_write_model.php.
The diagnostic is scripts/phase_2/youngo_phase_2m_entitlement_write_diagnostic.php.
The Phase 2M schema adds revoke actor/note fields and indexes to youngo_course_access and youngo_user_subscriptions, with no hard foreign keys.
The service supports manual course grants, manual subscription grants, course/subscription revocation, linked manual-grant revocation, duplicate active entitlement prevention, transactions/rollback, and non-writing checkout issuance stubs.
Phase 2N Manual Grants dashboard/UI is implemented, committed, and authenticated UI-QA tested locally.
The dashboard is application/controllers/Youngo_manual_grants.php with admin views for list, create, and detail/revoke flows.
Routes exist under /admin/youngo/manual-grants and navigation appears under the YounGo group.
Manual Grants UI enforces grant_manual_access, uses Youngo_entitlement_write_model for all grant/revoke writes, has no delete path, and uses POST-only revocation.
Authenticated QA verified Root Admin login, menu/list/create access, manual course grant/revoke, manual subscription grant/revoke, duplicate rejection, read-layer recognition/denial, cleanup restore from D:\Work\YounGo\backups\youngo_school (14).sql, and final clean diagnostics.
Phase 2O learner-facing entitlement visibility/enforcement alignment is implemented, committed, and browser-QA tested locally.
The alignment updates application/controllers/Home.php, application/views/frontend/youngo/course_listing/course_card.php, application/views/frontend/youngo/course_page.php, and application/views/frontend/youngo/course_page_reviews.php.
Course cards, course detail CTAs/status, Home::play_lesson(), Home::pdf_canvas(), go_course_playing_page(), lesson_mobile_web_view_get(), offline_video_for_mobile_app(), and course review visibility/submission now use the YounGo entitlement read layer where scoped.
Paid subscription-only courses without active access show checkout-not-ready messaging and must not be routed into legacy Buy Now/Add to cart as a shortcut while YounGo checkout is not implemented.
Phase 2O QA verified public/logged-out listing/detail checks, paid subscription-only checkout-not-ready display, temporary course/subscription manual grants through the Manual Grants UI, duplicate rejection, revocation, protected-table stability, cleanup restore from D:\Work\YounGo\backups\youngo_school (15).sql, and final clean diagnostics.
Phase 2P My Courses / My Access learner visibility is implemented, committed, and controlled-QA tested locally.
The learner visibility work added read-only learner access methods to application/models/Youngo_entitlement_model.php, added Home::my_access(), updated My Courses to use prepared learner access items, added application/views/frontend/youngo/my_access.php, added application/views/frontend/youngo/reload_my_courses.php, added My Access to the profile menu, and added scripts/phase_2/youngo_phase_2p_learner_access_visibility_diagnostic.php.
My Courses now lists active legacy enrolments and active direct YounGo/manual course access rows, but intentionally does not list every subscription-eligible course just because a subscription is active.
My Access is visibility-only: it shows subscription/access summaries and links to course browsing only. It does not implement checkout, renewal, payment, coupons, or subscription purchase.
Phase 2P QA used temporary manual course and subscription grants through the Manual Grants UI, verified My Courses/My Access model visibility, duplicate rejection, revocation, GET-only cart boundary, cleanup restore from D:\Work\YounGo\backups\youngo_school (16).sql, and final clean diagnostics.
Phase 2Q curated QA learner baseline setup is completed locally.
Pre-setup backup was D:\Work\YounGo\backups\youngo_school (17).sql.
The curated post-setup backup is D:\Work\YounGo\backups\youngo_school_qa_baseline_after_phase_2q2.sql and should be used as the local baseline for future learner-authenticated QA.
Dedicated QA learner user 8 exists as qa.learner@youngo.local, name YounGo QA Learner, role_id 2, active, and is_instructor 0.
Do not document, print, commit, store, or reuse the QA learner password. The project owner must provide it when learner-authenticated QA is needed.
QA learner user 8 is the clean no-access learner fixture. User 2 plus course 6 remains the legacy enrol compatibility fixture. Course 1 is the manual-grant / lesson-safe QA candidate. Course 9 is the paid subscription-only checkout-not-ready CTA candidate. Plan 1 Monthly EGP is the manual subscription QA candidate.
After Phase 2Q setup, entitlement write tables remain empty locally and checkout/payment/order/coupon/enrol/progress rows were not created.
Phase 2Q.3 learner-authenticated browser QA is completed locally using QA learner user 8.
Phase 2Q.3 verified QA learner login, baseline no-access My Courses, baseline no-subscription My Access, course 1 no-access state, course 9 checkout-not-ready state, temporary manual course grant visibility, My Courses course-access visibility, My Access course-access summary, course listing/detail active grant state, normal learner lesson access with active manual grant, review-area visibility without submitting a review, duplicate grant rejection, course grant revocation, temporary manual subscription visibility, My Access active subscription summary, My Courses no subscription-catalog flooding, course listing/detail subscription access state, duplicate subscription rejection, subscription revocation, and GET-only cart boundary.
Phase 2Q.3 used and restored the curated baseline backup D:\Work\YounGo\backups\youngo_school_qa_baseline_after_phase_2q2.sql. Temporary rows before restore were watch_histories = 1, youngo_course_access = 1, youngo_user_subscriptions = 1, and youngo_manual_grants = 2. After restore, QA learner user 8 remained, entitlement tables were empty, watch/progress rows returned to baseline, and diagnostics passed.
Phase 2R read-only admin/user entitlement summaries are implemented, committed, and authenticated admin-QA tested locally.
The admin summary work added read-only admin summary methods to application/models/Youngo_entitlement_model.php, added user/course summary cards in backend admin edit contexts, and added scripts/phase_2/youngo_phase_2r_admin_entitlement_summary_diagnostic.php.
User edit and course edit show read-only YounGo entitlement summaries only when the current admin has grant_manual_access. If the capability helper is unavailable or the capability is denied, summary cards are hidden safely.
Summary cards separate legacy enrolment counts from active YounGo course access, do not treat subscription eligibility as enrolment, show active subscription status/count for the user where applicable, show recent manual grants safely, and link to Manual Grants filters by user_id/course_id.
Manual Grants UI remains the authoritative grant/revoke surface. No grant/revoke controls were added to summary cards.
Phase 2R QA verified Root Admin authenticated rendering, baseline user 8 and course 1 summaries, temporary manual course grant summary updates, duplicate course grant rejection, course grant revocation summary updates, temporary manual subscription summary updates, duplicate subscription rejection, subscription revocation summary updates, Manual Grants filtered links, GET-only cart boundary, cleanup restore from D:\Work\YounGo\backups\youngo_school_qa_baseline_after_phase_2q2.sql, and final clean diagnostics.
The Phase 2R QA-blocking fix made user_edit.php safely default missing/empty social/payment key arrays without documenting or exposing sensitive values.
Phase 2S YounGo CTA boundary fixes are implemented, committed, reviewed, and manually validated locally.
The Phase 2S work updated application/controllers/Home.php, application/views/frontend/youngo/course_listing/course_card.php, application/views/frontend/youngo/course_page.php, application/views/frontend/youngo/my_wishlist.php, and application/views/frontend/youngo/wishlist_items.php, and added scripts/phase_2/youngo_phase_2s_route_cta_boundary_diagnostic.php.
Home::get_enrolled_to_free_course() now blocks legacy free-enrol creation for YounGo-managed course modes subscription_only, subscription_and_purchase, and purchase_only, redirects safely back to course detail, and preserves legacy free-course enrol behaviour for non-YounGo-controlled courses.
Course detail, course cards, and wishlist views no longer expose legacy Enroll Now, Add to cart, or Buy Now CTAs for YounGo-managed no-access courses. Wishlist empty-state copy no longer tells learners to enrol in free courses or add paid courses to cart.
Phase 2S manual validation used QA learner user 8. Course 1, Scratch Coding for Young Creators, is the subscription_only legacy-free-risk fixture and showed access-managed messaging with no Enroll Now/Add to cart/Buy Now. The direct legacy free-enrol route /home/get_enrolled_to_free_course/1 redirected safely to course detail and did not create an enrol row for user 8; global enrol stayed 1 and user 8 enrol rows stayed 0.
Course 9 remains the paid subscription-only checkout-not-ready fixture and showed subscription checkout-not-ready messaging with no Buy Now/Add to cart. Courses listing showed Course 1 access-managed and Course 9 subscription-not-ready. My Courses/My Access baseline remained zero active access/subscriptions. Wishlist loaded empty.
After Phase 2S validation, payment, youngo_checkout_orders, youngo_coupon_usages, youngo_coupon_subscription_plans, youngo_coupon_courses, youngo_course_access, youngo_user_subscriptions, and youngo_manual_grants remained 0.
Phase 2U.3 localization schema foundation is implemented, reviewed, committed, and locally applied after backup D:\Work\YounGo\backups\youngo_school_before_phase_2u3_localization_schema_2026_07_16.sql.
Phase 2U.3 added additive YounGo translation tables only: youngo_course_translations, youngo_category_translations, youngo_section_translations, and youngo_lesson_translations.
Canonical course, category, section, and lesson rows remain stable and continue to be the IDs used by access, manual grants, subscriptions, future checkout/orders, lesson progress, and diagnostics. Duplicate courses per language are not the default localization model.
The Phase 2U.3 schema includes unique entity/language keys uniq_yct_course_language, uniq_ycat_category_language, uniq_yst_section_language, and uniq_ylt_lesson_language, plus language/slug indexes where applicable. No hard foreign keys were added.
The idempotent English seed script created English translation rows from existing canonical content: youngo_course_translations = 7, youngo_category_translations = 12, youngo_section_translations = 18, and youngo_lesson_translations = 36. Canonical counts stayed course = 7, category = 12, section = 18, lesson = 36.
No Arabic translation rows were created in Phase 2U.3. The language phrase table, language settings, language_dirs, frontend rendering, dashboard bilingual forms, Arabic phrase JSON repair/import, course rebuild, checkout/order/coupon/Paymob/payment, and access/payment/enrol/progress data were not changed.
Course translation slug values are currently NULL because the canonical course table has no slug column; translated slug generation is deferred to dashboard/frontend localization phases.
The Phase 2U.3 diagnostic is scripts/phase_2/youngo_phase_2u3_localization_schema_diagnostic.php and should pass locally.
Phase 2U.4 canonical Arabic UI phrase support is implemented, reviewed, committed, and locally applied after backup D:\Work\YounGo\backups\youngo_school_before_phase_2u4_arabic_phrase_support_2026_07_16.sql.
Phase 2U.4 added the canonical language.arabic phrase column, created application/language/arabic.json, and added database/phase_2/youngo_phase_2u4_arabic_phrase_support_up.sql, database/phase_2/youngo_phase_2u4_arabic_phrase_support_down.sql, scripts/phase_2/youngo_phase_2u4_seed_arabic_phrases.php, and scripts/phase_2/youngo_phase_2u4_arabic_phrase_diagnostic.php.
The canonical Arabic language code is arabic. application/language/arabic_translated.json remains present only as a deprecated placeholder and must not be used as source of truth for new work.
English remains the default language. settings.language_dirs already includes arabic: rtl, so Arabic UI phrase support exists at phrase-data level only.
Arabic phrases were generated from current project phrase keys and English values using Modern Standard Arabic with simple parent/child-friendly wording. High-priority YounGo/navigation/access phrases have direct translations; many non-priority phrases currently use safe Arabic fallback wording and require phrase-polish QA before public Arabic launch.
Phase 2U.4 did not create Arabic rows in the Phase 2U.3 course/category/section/lesson translation tables, did not modify canonical course/category/section/lesson content, did not add frontend translation rendering, did not implement /ar routes, did not add dashboard bilingual content forms, did not change default language, and did not touch checkout/order/coupon/Paymob/payment or entitlement rows.
The Phase 2U.4 diagnostic is scripts/phase_2/youngo_phase_2u4_arabic_phrase_diagnostic.php and should pass locally.
Phase 2U.5.2 translation model/helper foundation is implemented, reviewed, committed, and locally diagnostic-tested.
Phase 2U.5.2 added application/models/Youngo_translation_model.php and scripts/phase_2/youngo_phase_2u5_translation_model_diagnostic.php.
Youngo_translation_model is the central helper/model for YounGo bilingual content. It works with the Phase 2U.3 translation tables while preserving canonical course/category/section/lesson IDs, and it does not replace canonical LMS tables or handle entitlement, subscription, manual grant, checkout, coupon, or payment logic.
Supported canonical content language codes are english and arabic. Compatibility inputs map en to english, ar to arabic, and deprecated arabic_translated to arabic, but arabic_translated must never be returned or stored as canonical.
The model supports direct translation reads, fallback reads, future dashboard upsert methods, slug generation/availability checks, has_translation(), and missing-translation summaries. Fallback order is requested language translation, English translation, canonical LMS table data, then null for missing entities. Fallback metadata includes requested_language, resolved_language, is_fallback, missing_translation, and translation_source.
Phase 2U.5.2 did not change dashboard forms, frontend rendering, /ar routes, language phrases, settings, canonical course/category/section/lesson data, checkout/order/coupon/Paymob/payment, or entitlement rows. No Arabic course/content translation rows were created, and upsert methods were reviewed but not executed or wired to forms.
The Phase 2U.5 diagnostic is scripts/phase_2/youngo_phase_2u5_translation_model_diagnostic.php and should pass locally.
Phase 2U.5.3 Category/Subcategory Bilingual Form Support is implemented, reviewed, committed, and runtime-QA tested locally.
Phase 2U.5.3 updated application/models/Crud_model.php, application/views/backend/admin/category_add.php, application/views/backend/admin/category_edit.php, application/views/backend/admin/sub_category_add.php, application/views/backend/admin/sub_category_edit.php, and added scripts/phase_2/youngo_phase_2u5_category_bilingual_forms_diagnostic.php.
Category and subcategory add/edit forms now collect english_name, english_slug, arabic_name, and arabic_slug. Arabic fields are optional and use RTL direction. Shared fields such as code, parent/category selection, icon, thumbnail, and category image remain canonical/shared outside bilingual content fields.
Crud_model::add_category() and Crud_model::edit_category() sync canonical category.name and category.slug from English fields, with the legacy name fallback retained for compatibility. English category translations are upserted after canonical create/update. Arabic category translations are upserted only when Arabic name or Arabic slug is non-empty; empty Arabic rows are not created and arabic_translated is not used.
Admin.php, course forms, section forms, lesson forms, frontend rendering, /ar routes, language phrases, settings, checkout/order/coupon/Paymob/payment, and entitlement logic were not changed in Phase 2U.5.3.
Phase 2U.5.3 runtime QA used backup D:\Work\YounGo\backups\youngo_school_before_phase_2u5_3_category_bilingual_form_qa_2026_07_16_190255.sql. Root Admin authenticated dashboard QA verified temporary category/subcategory add/edit with English and Arabic values, English-only category behavior with no Arabic row, restore cleanup, and post-restore counts category = 12, youngo_category_translations english = 12, youngo_category_translations arabic = 0, and protected rows clean.
The Phase 2U.5 category bilingual forms diagnostic is scripts/phase_2/youngo_phase_2u5_category_bilingual_forms_diagnostic.php and should pass locally.
Phase 2U.5.4 Course Add/Edit Bilingual Form Support and Course Form UX Alignment is implemented, runtime-QA tested, reviewed, and committed.
The implementation commit is Add YounGo course bilingual form support.
Phase 2U.5.4 updated application/helpers/common_helper.php, application/models/Crud_model.php, application/views/backend/admin/course_add.php, application/views/backend/admin/course_add_shortcut.php, application/views/backend/admin/course_edit.php, scripts/phase_2/youngo_phase_2u4_arabic_phrase_diagnostic.php, scripts/phase_2/youngo_phase_2u5_category_bilingual_forms_diagnostic.php, scripts/phase_2/youngo_phase_2u5_translation_model_diagnostic.php, and scripts/phase_2/youngo_phase_2u5_course_bilingual_forms_diagnostic.php.
Course add/edit forms now collect bilingual course fields for title, slug, short_description, description, outcomes, requirements, faqs, seo_title, meta_keywords, and meta_description. English fields are the canonical source and sync back to canonical course fields for Academy LMS compatibility. Arabic fields are optional.
English course translation rows are upserted after canonical create/update. Arabic course translation rows are upserted only when Arabic content is non-empty; English-only courses do not create Arabic translation rows.
Course shortcut remains English-oriented: it creates canonical English course data and an English translation row, with Arabic completion deferred to full course edit.
Course language_made_in is course-content metadata only. Valid marker values are english, arabic, and arabic_translated. The arabic_translated marker means the course content/video/material is Arabic-translated; it is not a system UI language, frontend site language, /ar route, or translation-table language.
YounGo translation table language codes remain only english and arabic. arabic_translated must not be stored in youngo_course_translations.language_code.
Runtime QA found and fixed a course edit HTTP 500 / partial blank page. course_edit.php now loads Youngo_translation_model safely through the CodeIgniter instance, English fallback values load safely, Arabic arrays default safely when Arabic translation is absent, and malformed HTML in reviewed course edit sections was fixed.
Pricing/access-mode UX was aligned: subscription_only hides/disables one-time price and discount fields with helper copy; purchase_only and subscription_and_purchase keep price/discount fields available. Crud_model::update_course() preserves existing price/discount values when disabled price fields are not posted. Add/shortcut paths default absent price fields safely. No checkout/payment/Paymob/order/coupon work was implemented.
EGP display was normalized in common_helper.php for readability while respecting configured currency position. The observed local settings were system_currency = EGP and currency_position = left, producing readable examples such as EGP 500. DB currency values, DB prices, checkout, payment, and Paymob logic were not changed. Arabic EGP-symbol display may be revisited later when Arabic UI context is fully implemented.
Phase 2U.5.4 runtime QA used a temporary admin account, not Root Admin. Backup before QA was D:\Work\YounGo\backups\youngo_school_before_phase_2u5_4_temp_admin_runtime_qa_2026_07_17_002027.phpdbdump. QA verified course edit/add rendering, English and Arabic fields, language marker options, subscription-only and purchase-mode pricing behavior, readable EGP display, temporary bilingual course add/edit, redirect to non-blank edit page, English-only no-Arabic-row behavior, and no duplicate translation rows.
After restore from the Phase 2U.5.4 QA turn, temporary QA data from the final QA turn was removed. Existing manual course QA data from before that backup remained intentionally present: course = 8, youngo_course_translations total = 9, english rows = 8, arabic rows = 1, category = 12, section = 18, lesson = 36, enrol = 1, payment/watch/progress/entitlement/checkout/coupon protected rows = 0.
Phase 2U.5.5 Section/Lesson Bilingual Form Support is implemented, focused-reviewed, runtime-QA tested, restored, and committed in commit f3368fc Add YounGo section lesson bilingual form support.
Phase 2U.5.5 updated application/models/Crud_model.php, application/views/backend/admin/section_add.php, application/views/backend/admin/section_edit.php, application/views/backend/admin/lesson_add.php, application/views/backend/admin/lesson_edit.php, application/views/backend/admin/text_type_lesson_add.php, application/views/backend/admin/text_type_lesson_edit.php, scripts/phase_2/youngo_phase_2u5_course_bilingual_forms_diagnostic.php, and scripts/phase_2/youngo_phase_2u5_section_lesson_bilingual_forms_diagnostic.php.
Section add/edit forms now collect required or primary english_title and optional RTL arabic_title. Canonical section.title syncs from English. English section translations are upserted after canonical create/update. Arabic section translations are upserted only when Arabic title is non-empty. Blank Arabic fields do not create empty Arabic rows and do not delete existing Arabic rows in this phase.
Lesson add/edit forms now collect required or primary english_title, optional english_summary, optional RTL arabic_title, and optional RTL arabic_summary. Canonical lesson.title and lesson.summary sync from English. English lesson translations are upserted after canonical create/update. Arabic lesson translations are upserted only when at least one Arabic translatable field is non-empty. Blank Arabic fields do not create empty Arabic rows and do not delete existing Arabic rows in this phase.
Text lesson add/edit partials now collect english_text_content and optional RTL arabic_text_content. Canonical text lesson body/content syncs from English. Arabic text content is stored only in youngo_lesson_translations. Non-text lessons are not forced to provide text content, and media/video/PDF/file/shared lesson fields remain shared rather than translated.
Phase 2U.5.5 added Crud_model::sync_legacy_lesson_post_fields() because legacy media handlers still expect POST keys such as title, summary, and text_description. The bridge copies English bilingual fields into those legacy keys before legacy handlers run, protecting Academy Cloud/video/media update behavior from blank titles after the bilingual field rename.
Youngo_translation_model is used for youngo_section_translations and youngo_lesson_translations. Translation table language codes remain english and arabic only; arabic_translated must not be used as a translation-table language code.
Phase 2U.5.5 runtime QA used backup D:\Work\YounGo\backups\youngo_school_before_phase_2u5_5_section_lesson_bilingual_qa_2026_07_18_194034.sql. Temporary admin login succeeded. A temporary Course Manager role plus legacy course/category permissions were used only under backup and were removed by restore. QA verified bilingual section add/edit, bilingual text lesson add/edit, English-only section and text lesson no-Arabic-row behavior, a minimal YouTube lesson media/title compatibility smoke, canonical English sync, English upserts, conditional Arabic upserts, no duplicate translation rows, clean Arabic UTF-8, and no protected row drift.
After Phase 2U.5.5 restore, temporary sections, lessons, and translation rows were removed. Pre/post counts matched: course = 8, section = 18, lesson = 36, youngo_section_translations total = 18, section English = 18, section Arabic = 0, youngo_lesson_translations total = 36, lesson English = 36, lesson Arabic = 0, youngo_user_roles = 0, permissions = 2, enrol = 1, and payment/watch/progress/entitlement/checkout/coupon protected rows = 0.
The Phase 2U.5.5 diagnostic is scripts/phase_2/youngo_phase_2u5_section_lesson_bilingual_forms_diagnostic.php. It checks section/lesson/text lesson files and fields, Arabic RTL markers, shared media/type/course/section fields, Youngo_translation_model save-path usage, English upserts, conditional Arabic upserts, translation tables and English coverage, Arabic section/lesson rows staying 0 after restore, protected payment/access/checkout/coupon rows, and role/course/category/translation diagnostic compatibility.
Runtime QA found the temporary admin lacked the legacy course permission. That was adjusted only inside QA and restored afterward. Phase 2V.0 then audited the two permission systems, and Phase 2V.1 Role Assignment Management is implemented, runtime-QA tested, reviewed, and committed in commit 21aecd7 Add YounGo role assignment management.
Phase 2V.1 added /admin/youngo/role-assignments and /admin/youngo/role-assignments/update under YounGo -> Role Assignments, guarded by manage_roles. The page lists users with Root Admin, Admin, Content, Course, and Instructor toggles.
The approved authority model is: Root Admin = developer/system owner/highest authority; Admin = client/operational owner; Content, Course, and Instructor = scoped operational roles. Root Admin can manage everything and remains protected from everyone else. Admin can manage roles for non-root users but cannot modify Root Admin. Content/Course/Instructor cannot manage roles unless explicitly granted manage_roles. Admin is mutually exclusive with Content/Course/Instructor, while Content, Course, and Instructor can combine freely; Content + Course is the practical Content & Course Manager state.
Root Admin is visible but read-only in the role assignment UI. Root Admin controls are disabled, tampered Root Admin updates are rejected server-side with protected_root_admin, Root Admin user/credential fields are not touched, and Root Admin keeps supreme authority through protected bypass/full authority.
Phase 2V.1 bridges legacy admin permissions and YounGo capabilities. Legacy compatibility still uses users.role_id, users.is_instructor, permissions.permissions, and check_permission(); YounGo role state uses youngo_roles, youngo_capabilities, youngo_role_capabilities, youngo_user_roles, and the capability helper. This bridge is intentional because current course/category pages still depend on legacy check_permission('course') and check_permission('category').
Admin maps to manage_roles through reversible seed files database/phase_2/youngo_phase_2v1_admin_manage_roles_up.sql and database/phase_2/youngo_phase_2v1_admin_manage_roles_down.sql. The up script maps only YounGo admin to manage_roles, does not grant manage_roles to content_manager/course_manager/instructor, and is safely re-runnable. The down script removes only admin -> manage_roles and does not delete role/capability rows or unrelated mappings.
Admin receives controlled operational legacy permissions while avoiding the legacy no-permissions-row full-access behavior for non-root users. Course grants legacy course and category. Content grants legacy category. Instructor assigns the YounGo instructor role and current LMS instructor state, and does not grant course/admin access unless combined with Course.
Phase 2V.1 runtime QA used the temporary admin account and did not modify Root Admin. Backup before QA was D:\Work\YounGo\backups\youngo_school_before_phase_2v1_admin_manage_roles_alignment_2026_07_17_061234.sql. QA verified Admin access to Role Assignments, non-root role updates, Root Admin read-only UI, protected_root_admin rejection for tampered Root Admin update, Course-only denial from Role Assignments, scoped roles without manage_roles, Admin mutual exclusion, restored temporary QA assignments, reapplied required admin -> manage_roles mapping, and clean protected payment/checkout/entitlement/manual-grant/coupon rows.
The Phase 2V.1 diagnostic is scripts/phase_2/youngo_phase_2v_role_assignment_diagnostic.php. It checks role assignment files/routes/guards, Root Admin protection markers, Admin mutual exclusion, admin -> manage_roles, scoped roles without manage_roles, SQL seed files, legacy bridge markers, and payment/checkout scope boundaries.
Phase 2U.6.2 Frontend Language Context and URL Mapping Helpers is implemented, focused-reviewed, committed, and diagnostic-tested in commit 25e0544 Add YounGo frontend language context helpers. The helper is application/helpers/youngo_frontend_language_helper.php, and the diagnostic is scripts/phase_2/youngo_phase_2u6_frontend_language_context_diagnostic.php. It normalizes en/eng/english to english and ar/ara/arabic/arabic_translated to arabic as an input alias only, keeps emitted frontend language codes limited to english and arabic, detects ar-prefixed frontend URIs, maps equivalent English/Arabic URLs while preserving query strings, excludes admin/payment/checkout/cart/coupon/write/API/cron paths, and returns html lang/dir values for future shell work. It is not autoloaded and did not add routes, frontend rendering, content translation shaping, session/cookie/settings writes, or checkout/payment changes.
Phase 2U.6.3 Arabic Public Route Aliases is implemented, focused-reviewed, diagnostics-tested, committed, and pushed in commit 4b773b2 Add YounGo Arabic public route aliases. English canonical frontend URLs remain unprefixed. Arabic canonical public frontend URLs use /ar/..., and /en must not become canonical. No /en routes were added and no English routes were redirected to /en. Admin/dashboard, checkout/payment/cart/coupon/write/API/cron routes remain unlocalized. arabic_translated is not a route language, UI language, or translation-table language; course language_made_in remains separate course-content metadata.
Phase 2U.6.3 added Arabic public aliases only: /ar -> home/index, /ar/courses -> home/courses, /ar/courses/{page} -> home/courses, /ar/course/{slug}/{id} -> home/course/$1/$2, /ar/search -> home/search, /ar/search/{query} -> home/search/$1, /ar/my-courses -> home/my_courses, /ar/my-access -> home/my_access, /ar/wishlist -> home/my_wishlist, /ar/login -> login/index, and /ar/sign-up -> sign_up/index. English unprefixed routes remain unchanged. Course detail preserves slug then id argument order, search preserves the query argument, and course pagination remains compatible with Home::courses() using URI segment 3. /ar/login and /ar/sign-up target the existing public auth controllers.
Phase 2U.6.3 did not add aliases for admin, addons, api, cron, home/payment, home/paypal, home/stripe, home/paymob, home/razorpay, home/paystack, home/flutterwave, home/course_payment, home/shopping_cart, home/update_cart, home/apply_coupon, home/remove_coupon, home/checkout, home/confirm_payment, home/webhook, coupon write routes, cart write routes, or payment callback routes. Lesson/player/PDF aliases remain deferred, including Home::lesson, Home::pdf_canvas, Home::play_lesson, mobile lesson helpers, and offline video helpers, because those are gated playback/progress/session surfaces that need separate staged QA before Arabic aliases are added.
Phase 2U.6.3 did not implement frontend translated content rendering, frontend RTL shell rendering, a language switcher UI, course/category/section/lesson translation shaping, session language writes, cookie writes, settings writes, phrase writes, checkout/payment/cart/coupon localization, or admin/dashboard localization. Arabic route aliases may still render existing canonical frontend content until later translation-aware rendering phases.
The Phase 2U.6.3 diagnostic is scripts/phase_2/youngo_phase_2u6_arabic_route_alias_diagnostic.php. It checks /ar public aliases, preserved English routes, no /en routes, no admin/payment/checkout/cart/coupon/API/cron Arabic aliases, argument mapping, route order, Phase 2U.6.2 helper compatibility, no arabic_translated route/UI language usage, no frontend rendering/content translation changes, and Phase 2S/2P/2R compatibility. Focused review also tightened existing diagnostics so routes.php changes are tolerated only when the diff is Arabic-alias-only, preventing broad route-change tolerance in future phases. PHP lint and compatibility diagnostics passed. HTTP GET smoke was intentionally skipped; static route diagnostics were used instead.
Phase 2U.6.4 Translation-aware Frontend Content Shaping is implemented, focused-reviewed, runtime-QA tested, restored, committed, and pushed in commit 12fcdae Add YounGo frontend content translation shaping. It added application/helpers/youngo_frontend_content_helper.php and scripts/phase_2/youngo_phase_2u6_frontend_content_translation_diagnostic.php, and updated Home.php, common_helper.php, YounGo course listing/detail/wishlist views, and Phase 2U.6 diagnostics.
Phase 2U.6.4 shapes safe public frontend content for home, courses, search, course detail, my courses, my access, and wishlist initial page data. It uses Youngo_translation_model plus the Phase 2U.6.2 frontend language context helper, overlays only whitelisted display fields, preserves canonical IDs and operational fields, stores original canonical values in safe youngo_canonical_* metadata, never emits/stores arabic_translated, and does not write DB/session/cookie/settings or call unknown get_phrase() keys.
Frontend content fallback is: Arabic route = arabic translation -> english translation -> canonical LMS field; English route = english translation -> canonical LMS field. Course display fields shaped are title, short_description, description, outcomes, requirements, faqs, seo_title, meta_keywords, and meta_description. Category display name can be shaped while canonical category ID/slug remain filter and URL identity. Section title plus lesson title/summary can be shaped on course detail and similar non-player display paths; helper support exists for text lesson body outside deferred player/PDF routes. Course IDs, slug/link identity, price, discount, currency, access mode, media, instructor, category, level, progress, entitlement/access fields, CTA state, wishlist state, checkout/payment fields, lesson IDs, lesson type, duration, attachment, video URL, access, and player/PDF routes remain unchanged.
Phase 2U.6.4 runtime QA used backup D:\Work\YounGo\backups\youngo_school_before_phase_2u6_4_frontend_content_translation_qa_2026_07_18_215217.sql. Temporary Arabic rows were inserted only in YounGo translation tables for course 1, category 7, section 1, and lesson 1; no canonical rows were changed. Restore removed the temporary rows and pre/post counts matched: course = 8, category = 12, section = 18, lesson = 36, course translations total = 9, course English = 8, course Arabic = 1, category translations total = 12, category English = 12, category Arabic = 0, section translations total = 18, section English = 18, section Arabic = 0, lesson translations total = 36, lesson English = 36, lesson Arabic = 0, ci_sessions = 636, youngo_user_roles = 0, permissions = 2, enrol = 1, and payment/watch/progress/entitlement/checkout/coupon protected rows = 0.
Phase 2U.6.4 QA confirmed / and /home/courses remain English/canonical, /ar and /ar/courses display Arabic shaped values where temporary translations exist, /home/course/scratch-coding-for-young-creators/1 remains English/canonical, /ar/course/scratch-coding-for-young-creators/1 displays Arabic shaped course/section/lesson values where available, search Arabic aliases resolve, and /ar/wishlist, /ar/my-courses, and /ar/my-access resolve without fatal/404 in unauthenticated safe GET checks. /ar/admin, /ar/home/payment, /ar/home/checkout, /ar/home/shopping_cart, /ar/home/apply_coupon, /ar/api, and /ar/cron render the app 404 page. No write endpoints were submitted and no /en links were introduced.
Current working English routes for course/list/search/detail surfaces remain /home/...; direct /courses, /course/..., /search, and /wishlist are not current English routes. Arabic public route aliases use /ar/... and English canonical URL strategy remains unprefixed.
The Phase 2U.6.4 diagnostic is scripts/phase_2/youngo_phase_2u6_frontend_content_translation_diagnostic.php. It checks helper functions, Home.php integration points, /ar aliases, no /en routes, no new route changes, no payment/checkout/cart/coupon localization, no arabic_translated table-language usage, canonical IDs/access/media/progress preservation in shaping logic, read-only sample shaping, and route/language/section-lesson/role/course-category/translation/access diagnostic compatibility.
Phase 2U.6.5 Language Switcher and RTL Shell Rendering is implemented, focused-reviewed, runtime-QA tested, restored, committed, and pushed in commit 8321e91 Add YounGo language switcher and RTL shell.
Phase 2U.6.5 updated application/helpers/youngo_frontend_language_helper.php, application/views/frontend/youngo/header.php, application/views/frontend/youngo/index.php, assets/frontend/youngo/css/youngo.css, scripts/phase_2/youngo_phase_2u5_section_lesson_bilingual_forms_diagnostic.php, scripts/phase_2/youngo_phase_2u6_arabic_route_alias_diagnostic.php, scripts/phase_2/youngo_phase_2u6_frontend_content_translation_diagnostic.php, scripts/phase_2/youngo_phase_2u6_frontend_language_context_diagnostic.php, and scripts/phase_2/youngo_phase_2u6_language_switcher_rtl_diagnostic.php.
The YounGo frontend shell now derives active language from the Phase 2U.6.2 route helper. English routes render html lang="en" dir="ltr"; Arabic /ar routes render html lang="ar" dir="rtl". The body adds helper-derived and escaped youngo-lang-english/youngo-lang-arabic, youngo-dir-ltr/youngo-dir-rtl, data-youngo-language, and data-youngo-dir metadata while preserving existing body classes. The admin/backend shell is unaffected.
The YounGo frontend header now includes a simple EN | عربي language switcher with active state and aria-current. Switcher URLs are helper-generated, preserve query strings, map working English /home/... route reality to Arabic aliases such as /home/courses <-> /ar/courses and /home/course/{slug}/{id} <-> /ar/course/{slug}/{id}, never generate /en, and never show or link arabic_translated. The review fix made youngo_frontend_current_uri_string_with_query() prefer CodeIgniter $CI->uri->uri_string() over raw REQUEST_URI, using REQUEST_URI only as fallback, because raw REQUEST_URI can include the app base path on subdirectory installs and generate wrong switcher URLs.
Minimal YounGo-scoped RTL CSS was added in assets/frontend/youngo/css/youngo.css for header/nav shell behavior, with the language switcher kept readable/LTR. Full visual RTL polish was not attempted.
Phase 2U.6.5 runtime QA used backup D:\Work\YounGo\backups\youngo_school_before_phase_2u6_5_language_switcher_rtl_qa_2026_07_18_224356.sql. No temporary Arabic rows were needed. Runtime QA confirmed / and /ar resolve 200 with correct html lang/dir and body metadata, the switcher appears with EN active on English pages and Arabic active on Arabic pages, / maps to /ar and /ar maps to /, /home/courses and /ar/courses resolve 200, query strings are preserved for /home/courses?page=2 <-> /ar/courses?page=2, /home/course/scratch-coding-for-young-creators/1 and /ar/course/scratch-coding-for-young-creators/1 resolve 200 with slug/id preserved, /home/search?query=Scratch and /ar/search?query=Scratch resolve 200 with query preservation, /ar/login and /ar/sign-up resolve 200 using the YounGo shell, /ar/wishlist resolves 200, and /ar/my-courses plus /ar/my-access keep normal unauthenticated refresh redirect behavior. Excluded /ar/admin, /ar/home/payment, /ar/home/checkout, /ar/home/shopping_cart, /ar/home/apply_coupon, /ar/api, and /ar/cron render the app 404 page. Restore succeeded and pre/post counts matched exactly, including ci_sessions = 636, course = 8, category = 12, section = 18, lesson = 36, course translations total = 9 / English = 8 / Arabic = 1, category Arabic = 0, section Arabic = 0, lesson Arabic = 0, enrol = 1, and protected YounGo entitlement/payment/checkout/coupon/progress tables = 0.
The Phase 2U.6.5 diagnostic is scripts/phase_2/youngo_phase_2u6_language_switcher_rtl_diagnostic.php. It checks shell html lang/dir helper usage, English en/ltr and Arabic ar/rtl mapping, body language/dir metadata, language switcher existence, helper-generated URLs, query preservation, no /en links/routes, no route additions, no session/cookie/settings language writes, no DB/phrase writes, no checkout/payment/cart/coupon localization, admin shell boundaries, and compatibility with content translation and Arabic route diagnostics.
PDF-specific browser QA remains a limitation because it was skipped to avoid broadening the QA surface; broader mobile/API entitlement UX remains future work.
After QA cleanup, entitlement write tables are empty locally: youngo_course_access = 0, youngo_user_subscriptions = 0, youngo_manual_grants = 0, and youngo_checkout_orders = 0.
Checkout/order issuance, Paymob/payment, coupons, and real subscription purchase flow remain not implemented.
All future manual grant writes must use Youngo_entitlement_write_model and enforce grant_manual_access before calling it.
Learner access/account visibility must use the centralized read methods in Youngo_entitlement_model rather than duplicating entitlement queries in views.
Do not create real grants or access rows through manual SQL.
Future grant/subscription QA must use the Manual Grants UI and restore from backup after temporary rows unless the phase explicitly creates a durable baseline.
Revocation must preserve progress, enrolment, payment, watch history, subscription plan, user, and checkout/order rows.
Do not use legacy coupons, cart, Buy Now, or direct SQL as shortcuts to issue YounGo access; coupons must later flow through formal checkout/order/access issuance.
Root Admin may be used as grant/revoke actor when authorized, but Root Admin must not be modified.
Frontend phrase conversion/polish, My Courses/Wishlist AJAX language propagation, full RTL visual polish, performance batching for translation reads, hreflang/canonical SEO, lesson/player/PDF Arabic aliases, mobile/API entitlement alignment, course data rebuild, and checkout/Paymob/subscription purchase flow remain future work. Recommended next roadmap: Phase 2U.6.6 frontend phrase conversion/inventory, Phase 2U.6.7 controlled frontend localization QA and diagnostics, Phase 2U.7 bilingual QA plus phrase polish QA, Phase 2T course data rebuild, then checkout/Paymob/subscription purchase flow. Phase 2V.2 role-assignment docs/UX polish may be handled only if needed and is not blocking localization.
Run Phase 2U.6 language switcher/RTL, frontend content translation, Arabic route alias, frontend language context, Phase 2U.5 section/lesson bilingual forms, Phase 2U.5 course bilingual forms, Phase 2U.5 category bilingual forms, Phase 2U.5 translation model, Phase 2U.4, Phase 2U.3, Phase 2J, Phase 2L, Phase 2M, Phase 2P, Phase 2R, and Phase 2S diagnostics before localization model/phrase/schema/form/route/rendering, grant, entitlement, learner-access visibility, admin entitlement summary, or YounGo CTA-boundary work.
```

---

## 7B. Local Demo Data Policy

The local YounGo database contains demo, seed, development, incomplete, and testing data only. It does not contain real production, client, learner, payment, enrolment, or operational data.

Root Admin is the only local identity protected by default.

Other local users and all local demo courses, categories, sections, lessons, media, enrolments, access records, subscriptions, and progress rows may be deliberately edited, deleted, rebuilt, reassigned, completed, archived, or replaced when doing so improves the system.

Do not preserve incomplete demo data for its own sake. Existing demo records, including courses 1-6 and course 9, are not protected assets and must not dictate system architecture.

Database-write phases still require a user-created phpMyAdmin backup before apply. Data changes must be scoped, documented, and reversible where practical. Temporary QA data and fixtures must not be deployed as production data.

Future reset/baseline scripts must preserve Root Admin unless the project owner gives a separate explicit instruction. Do not assume every `role_id = 1` user is a protected Root Admin.

---

## 8. Repository Safety Rules

Before editing files:

```text
Check the current branch.
Check git status.
Understand the relevant existing files.
Make the smallest safe change needed.
```

Do not commit changes unless explicitly asked.

Do not push changes unless explicitly asked.

Do not delete existing CMS functionality unless explicitly approved.

Do not make broad refactors without approval.

Do not modify sensitive local configuration files unless explicitly requested.

Sensitive files include:

```text
application/config/database.php
```

Do not expose or print database credentials.

---

## 9. Dependency and Environment Rules

Do not run dependency-changing commands unless explicitly approved.

Avoid running:

```text
composer install
composer update
npm install
npm update
```

unless the task specifically requires it and the user approves.

The project may have old CodeIgniter/Academy LMS dependencies. Modern dependency tools may introduce compatibility issues.

Do not assume the local app runs until database setup/import is confirmed.

---

## 10. Implementation Approach

Prefer additive changes over destructive changes.

Recommended approach:

```text
Create new YounGo-specific frontend/theme files.
Reuse existing LMS models/controllers where safe.
Add YounGo-specific CMS features where needed.
Keep existing default frontend/theme files available as reference.
Avoid breaking current admin/course/user functionality.
```

When unsure, analyze first and ask for approval before editing.

---

## 11. Agent Behavior Rules

Agents should:

```text
Analyze before modifying.
Explain intended changes before large edits.
Use existing project conventions.
Keep files organized by purpose.
Avoid mixing design, planning, reference, and implementation concerns.
Prefer clear, maintainable PHP/CSS/JS over clever abstractions.
```

Agents should not:

```text
Invent missing requirements.
Move unrelated files.
Rewrite core LMS logic without approval.
Mix planning notes into design files.
Mix design rules into phase plans.
Treat reference files as active plans unless planning docs say so.
```

---

## 12. Playwright / Browser Testing

When browser testing is available, use Playwright MCP or browser inspection to validate visual and functional changes.

Useful checks include:

```text
Homepage loads
Course details page loads
Navigation works
Responsive layout is acceptable
Admin content screens render correctly
CMS content changes appear on frontend
No obvious layout breakage
```

Testing scope should follow the relevant planning document.

---

## 13. Source of Truth Priority

When documents conflict, use this priority order:

```text
1. User's latest explicit instruction
2. docs/planning/youngo_master_plan_v2.md
3. YOUNGO_PROJECT_CONTEXT.md
4. docs/planning/
5. docs/agents/implementation_rules.md
6. docs/design/youngo_style_direction.md
7. docs/reference/
8. Existing codebase behavior
```

If there is still conflict, stop and ask for clarification.

---

## 14. Final Reminder

This repository is a real CMS project.

Be conservative.

Protect the existing LMS.

Build YounGo as a focused custom frontend and CMS content-management layer on top of the existing system.

````
