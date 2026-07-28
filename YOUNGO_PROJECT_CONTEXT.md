# YounGo Project Context

## 1. Purpose of This File

This file provides the high-level project context for YounGo.

It explains what the project is, what the current direction is, what existing system it is built on, and where supporting documentation lives.

This file is not a phase plan, implementation guide, database design, controller map, or agent prompt.

Detailed planning belongs in:

```text
docs/planning/
````

The current master planning reference is:

[`youngo_master_plan_v2.md`](./docs/planning/youngo_master_plan_v2.md)

It is the primary reference for project priorities, Academy LMS reuse decisions, deployment preparation, and implementation sequencing.

Agent/development rules belong in:

```text
docs/agents/
```

Design and UI/UX direction belongs in:

```text
docs/design/
```

Old CMS documentation and source references belong in:

```text
docs/reference/
```

---

## 2. Project Name

Project name:

```text
YounGo
```

YounGo is a kids learning platform built on top of an existing Academy LMS / CodeIgniter CMS.

---

## 3. Project Summary

YounGo aims to deliver a polished, modern, client-visible website for a kids learning platform while keeping important website content manageable from the existing CMS dashboard.

The project is not a full rewrite of the LMS.

The existing CMS remains the backend foundation.

The main work is to create a custom YounGo frontend experience and add focused CMS content-management capabilities where the existing CMS is too limited.

---

## 4. Target Product Direction

The approved product direction is:

```text
Build a custom YounGo frontend/theme.
Keep the visual style fixed in the theme.
Make website content editable from the CMS.
Reuse existing LMS features and data where practical.
Add YounGo-specific content-management features only where the existing CMS is too limited.
Avoid building a complex generic page builder.
Avoid focusing on old prebuilt themes as a product feature.
Prioritize a polished website the client can see quickly.
```

---

## 5. Target Audience

YounGo is focused on teaching kids.

The public website should serve:

* Parents
* Kids
* Teachers
* Platform administrators

Parents should feel that the platform is safe, trustworthy, structured, and valuable.

Kids should feel that the platform is friendly, fun, inspiring, and easy to explore.

Teachers/admins should feel that the system is practical and manageable.

---

## 6. Existing System

The existing system is an Academy LMS built with PHP CodeIgniter MVC.

Important existing structure:

```text
application/
  config/
  controllers/
  models/
  views/

assets/
  backend/
  frontend/

uploads/
```

Important existing areas include:

```text
Courses
Categories
Lessons
Users
Instructors
Blogs
Custom pages
Frontend settings
Website settings
Contact information
FAQs
Logos and images
Reviews
SEO-related settings
```

The current system already contains useful LMS and CMS functionality. The YounGo work should build on this foundation instead of replacing it unnecessarily.

---

## 6A. Local Demo Data Policy

The local YounGo system currently contains no real production, client, learner, payment, enrolment, or operational data.

The local users, courses, categories, subcategories, sections, lessons, media, enrolments, entitlement/access records, subscriptions, and progress rows are demo, seed, development, incomplete, or testing data.

Root Admin is the only local identity protected by default.

All other local demo records may be edited, deleted, rebuilt, reassigned, completed, archived, or replaced when doing so improves system flow, form compatibility, role/capability behavior, instructor assignment, entitlement behavior, subscriptions, manual grants, checkout/orders, payments, QA coverage, or overall consistency.

Do not preserve incomplete demo data merely because it already exists. Existing demo records, including courses `1-6` and course `9`, are not protected assets or architectural dependencies and must not dictate system architecture.

Database-write phases still require a user-created phpMyAdmin backup before apply. Data changes must remain scoped, documented, and reversible where practical. Temporary QA data and fixtures must not be included in production deployment packages.

---

## 7. Existing Frontend Direction

The current system supports frontend themes.

The safest direction is to create a new YounGo frontend theme instead of directly rewriting the existing frontend theme.

Target frontend theme location:

```text
application/views/frontend/youngo/
assets/frontend/youngo/
```

The existing frontend theme can be used as a reference, but the YounGo frontend should have its own clean structure and assets.

---

## 8. CMS Content Direction

The YounGo website should have fixed visual styling, but editable content.

CMS-managed content may include:

```text
Hero content
Homepage images
CTA labels and links
Featured categories
Featured courses
About content
Benefits / why choose YounGo
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

The exact implementation details for these capabilities should be documented in `docs/planning/`, not in this file.

---

## 9. Admin Dashboard Direction

The CMS dashboard should remain the main place for managing website content.

YounGo may need a focused website content-management area inside the existing admin dashboard.

The dashboard experience should be simple, content-focused, and practical for non-technical admins.

Admins should manage content, not visual styling.

Detailed admin implementation decisions belong in:

```text
docs/planning/
docs/agents/implementation_rules.md
```

---

## 10. Design Direction

The approved design direction is documented in:

```text
docs/design/youngo_style_direction.md
```

Design summary:

```text
A modern, premium, soft, purple-led kids learning platform.
Friendly enough for children.
Trustworthy enough for parents.
Clean enough for teachers and admins.
Simple enough to implement inside the existing CMS.
```

The design direction is based on:

```text
YounGo logo
Stitch-generated homepage concept
Stitch-generated course details concept
Stitch-generated admin content manager concept
```

Design files should describe visual identity, UI style, UX principles, colors, typography, spacing, components, and interaction direction.

Design files should not contain database plans, controller methods, phase sequencing, or agent instructions.

---

## 11. Reference Documentation

Old CMS documentation, source notes, extracted findings, screenshots, and related reference material should live in:

```text
docs/reference/
```

This folder is for understanding the existing CMS.

Examples of reference materials:

```text
Original CMS documentation
Developer manual notes
Admin guide notes
Existing database/schema notes
Existing controller/model/view findings
Existing CMS screenshots
Stitch exported HTML/design references
Approved visual screenshots
```

Reference files are supporting evidence.

They should not be treated as active implementation plans unless a planning document explicitly uses them.

---

## 12. Planning Documentation

Project sequencing and delivery decisions should live in:

```text
docs/planning/
```

This folder should contain the actual phased implementation plans.

Planning documents may define:

```text
Delivery phases
Scope per phase
Required features
Database changes
Controller/model/view changes
Acceptance criteria
Testing scope
Risks and dependencies
```

The planning folder contains delivery plans, but the current master plan is the primary source of truth for delivery order and implementation priorities:

[`youngo_master_plan_v2.md`](./docs/planning/youngo_master_plan_v2.md)

Older planning documents remain useful historical and supporting references, but new work must be checked against the master plan before implementation.

This root context file should not define specific phase steps.

---

## 13. Agent Documentation

AI/code-agent instructions should live in:

```text
docs/agents/
```

Agent files may include:

```text
Implementation rules
Safety rules
Repository rules
Codex prompts
Antigravity prompts
Playwright MCP testing instructions
Analysis-only prompts
```

Agent files should tell agents how to behave.

They should not replace planning documents or design documents.

---

## 14. Root Agent File

The root agent guidance file should be:

```text
AGENTS.md
```

`AGENTS.md` should give short, high-priority instructions that agents can read immediately from the repository root.

It should point agents to the deeper documentation folders instead of duplicating everything.

---

## 15. Documentation Structure

Recommended documentation structure:

```text
docs/
  design/
    youngo_style_direction.md

  reference/
    README.md
    cms_documentation/
    cms_findings/
    stitch_outputs/
    screenshots/

  planning/
    README.md
    phase documents go here

  agents/
    implementation_rules.md
    codex_analysis_prompt.md
    antigravity_analysis_prompt.md
    playwright_mcp_notes.md

AGENTS.md
YOUNGO_PROJECT_CONTEXT.md
```

Each folder has a separate purpose:

```text
docs/design/     = visual and UX direction
docs/reference/  = old CMS docs, evidence, exports, screenshots, findings
docs/planning/   = delivery plans and implementation phases
docs/agents/     = agent behavior rules and prompts
```

---

## 15A. Phase 2 Business Model Direction

The active Phase 2 architecture reference is:

```text
docs/planning/youngo_phase_2_hybrid_access_subscriptions_roles_architecture.md
```

Phase 2 should add hybrid access and role architecture on top of Academy LMS, not replace the LMS core.

Approved Phase 2 direction:

```text
Student/user account remains the primary account.
Parents may pay or operate the account, but parent multi-child accounts are deferred.
YounGo is B2C for now.
Subscriptions are added beside individual course purchase.
Manual grants are supported and must be auditable.
Coupons/discounts go through checkout and are not direct access.
Direct checkout is the target YounGo UX, while legacy cart logic remains for compatibility.
Paymob is the intended payment provider, but real payments are not active until implementation and QA approval.
Accounts should support multiple roles/capabilities.
Learner/user capability should remain automatic for compatibility.
Root Admin must remain protected.
```

Do not claim subscriptions, manual grants, expanded coupons, Paymob payments, or the multi-role capability model are implemented until implementation and validation have been completed.

Current Phase 2L subscription/currency state:

```text
Subscription Plan Management is implemented and QA-tested locally.
The dashboard uses a dedicated YounGo subscription plans controller, model, views, routes, navigation item, manage_subscriptions capability gate, and diagnostic script.
The Phase 2L archive/audit schema is applied locally, including archived_at, archived_by_user_id, idx_ysp_archived_at, youngo_subscription_plan_audit_log, idx_yspal_plan_created, and idx_yspal_actor_created.
QA verified Root Admin dashboard access, seeded plan listing, temporary QA plan create/edit/status/archive/restore, audit events, invalid-form atomicity, and cleanup.
No checkout, payment, Paymob, coupon, manual grant, subscription issuance, enrolment, or access rows/features were added by Phase 2L.
YounGo commercial currency is EGP.
The global system currency is now EGP after a controlled dashboard Payment Settings update.
Seeded Monthly, 3 Months, and Yearly subscription placeholders now store EGP and remain inactive/non-purchasable.
Their current local prices are temporary placeholders only: Monthly 100.00 EGP, 3 Months 250.00 EGP, and Yearly 900.00 EGP.
The Phase 2L diagnostic now reports non-EGP subscription plan count 0, so currency readiness is clean for subscription plan definitions.
This does not mean subscriptions are production-ready: final commercial prices still require owner approval, and checkout/payment/subscription issuance are not implemented.
Gateway currencies remain mixed and are deferred to the payment/Paymob phase.
```

Current Phase 2M entitlement write-service state:

```text
The Shared Entitlement Write Service foundation is implemented and QA-tested locally.
The committed service is application/models/Youngo_entitlement_write_model.php.
The committed diagnostic is scripts/phase_2/youngo_phase_2m_entitlement_write_diagnostic.php.
The Phase 2M schema is applied locally: revoke actor/note fields and indexes exist on youngo_course_access and youngo_user_subscriptions, with no hard foreign keys.
Controlled service QA verified manual course grant, course revocation, manual subscription grant, subscription revocation, duplicate active grant rejection, linked manual-grant revocation, read-layer recognition/denial, checkout stubs, cleanup restore, and no checkout/payment/order/coupon/enrol/progress side effects.
Phase 2N Manual Grants dashboard/UI is implemented and authenticated QA-tested locally.
The committed dashboard includes application/controllers/Youngo_manual_grants.php, backend admin list/create/detail views, routes under /admin/youngo/manual-grants, and YounGo navigation gated by grant_manual_access.
Manual Grants UI supports listing/filtering manual grants, creating manual course grants, creating manual subscription grants, viewing grant details, and revoking course/subscription grants.
All Manual Grants UI writes go through Youngo_entitlement_write_model; the controller enforces admin session and grant_manual_access, does not authorize by role_id alone, has no delete path, and uses POST-only revocation.
Authenticated QA verified Root Admin login, navigation/list/create access, course grant/read-layer recognition/duplicate rejection/revocation/read-layer denial, subscription grant/read-layer recognition/duplicate rejection/revocation/read-layer denial, cleanup restore from D:\Work\YounGo\backups\youngo_school (14).sql, and final clean diagnostics.
Phase 2O learner-facing entitlement visibility/enforcement alignment is implemented and controlled browser-QA tested locally.
Phase 2O aligned course card CTA/status display, course detail CTA/status display, manual grant and subscription access labels, checkout-not-ready messaging for paid subscription-only courses, `Home::play_lesson()`, `Home::pdf_canvas()`, `go_course_playing_page()`, `lesson_mobile_web_view_get()`, `offline_video_for_mobile_app()`, and course review visibility/submission gates with the YounGo entitlement read layer.
Phase 2O prevents paid subscription-only checkout-not-ready states from exposing legacy Buy Now/Add to cart shortcuts.
Phase 2O QA verified public/logged-out course listing/detail checks, paid subscription-only checkout-not-ready display, temporary course and subscription grants through the Manual Grants UI, duplicate rejection, revocation, protected-table stability, cleanup restore from D:\Work\YounGo\backups\youngo_school (15).sql, and final clean diagnostics.
Phase 2P My Courses / My Access learner visibility is implemented and controlled-QA tested locally.
Phase 2P added read-only learner access composition methods to Youngo_entitlement_model, added Home::my_access(), updated My Courses to use prepared learner access items, added a visibility-only My Access page, added a reload_my_courses view aligned with prepared access items, added My Access to the profile menu, and added the read-only Phase 2P diagnostic.
My Courses now includes active legacy enrolments and active direct YounGo/manual course access rows. It intentionally does not auto-list every subscription-eligible course merely because a subscription is active.
My Access shows learner-safe subscription/access summaries and links only to course browsing. It does not implement checkout, renewal, payment, coupons, or subscription purchase.
Phase 2P QA used temporary manual course and subscription grants through the Manual Grants UI, verified model/read visibility, duplicate rejection, revocation, GET-only cart boundary, cleanup restore from D:\Work\YounGo\backups\youngo_school (16).sql, and final clean diagnostics.
Phase 2Q curated QA learner baseline setup is completed locally.
Pre-setup backup was D:\Work\YounGo\backups\youngo_school (17).sql.
The curated post-setup backup is D:\Work\YounGo\backups\youngo_school_qa_baseline_after_phase_2q2.sql and should be used as the local baseline for future QA requiring learner login.
Dedicated QA learner user 8 exists as qa.learner@youngo.local, name YounGo QA Learner, role_id 2, active, and is_instructor 0.
Do not document, print, commit, store, or reuse the QA learner password. The project owner must provide it when learner-authenticated QA is needed.
Current QA fixtures: QA learner user 8 is the clean no-access learner; user 2/course 6 is the legacy enrol compatibility fixture; course 1 is the manual-grant / lesson-safe QA candidate; course 9 is the paid subscription-only checkout-not-ready CTA candidate; plan 1 Monthly EGP is the manual subscription QA candidate.
After Phase 2Q setup, users increased from 5 to 6, Root Admin was not modified, users 2/5/6/7 were not modified, user 2/course 6 enrol remains, entitlement tables remain empty, and no checkout/payment/order/coupon/enrol/progress rows were created.
Phase 2Q.3 learner-authenticated browser QA is completed locally for the implemented Phase 2O/2P learner surfaces using QA learner user 8.
Phase 2Q.3 verified normal QA learner login, baseline no-access My Courses, baseline no-subscription My Access, course 1 no-access state, course 9 checkout-not-ready state without Buy Now/Add to cart, temporary manual course grant visibility, course listing/detail active grant access, normal learner lesson access with active manual grant, review-area visibility without review submission, duplicate course grant rejection, course grant revocation, temporary manual subscription visibility, My Access active subscription summary, My Courses not auto-populating all subscription-eligible courses, course listing/detail subscription access, duplicate subscription rejection, subscription revocation, and GET-only cart boundary.
The curated baseline backup D:\Work\YounGo\backups\youngo_school_qa_baseline_after_phase_2q2.sql was restored after Phase 2Q.3. Temporary rows before restore were watch_histories = 1, youngo_course_access = 1, youngo_user_subscriptions = 1, and youngo_manual_grants = 2. After restore, QA learner user 8 remained, entitlement tables were empty, watch/progress rows returned to baseline, checkout/payment/order/coupon/enrol rows remained unchanged, and Phase 2J/2L/2M/2P diagnostics passed.
Phase 2R read-only admin/user entitlement summaries are implemented and authenticated-QA tested locally.
Phase 2R added read-only admin summary methods to Youngo_entitlement_model, user/course edit summary cards, Manual Grants filtered summary links, and scripts/phase_2/youngo_phase_2r_admin_entitlement_summary_diagnostic.php.
User and course admin summary cards require grant_manual_access, are hidden safely if the capability helper is unavailable or denied, separate legacy enrolments from YounGo course access, do not treat subscription eligibility as enrolment, show active user subscription state where applicable, and keep Manual Grants as the only grant/revoke surface.
Phase 2R QA verified Root Admin authenticated rendering, summary updates for temporary manual course and subscription grants, duplicate rejection, revocation state, filtered Manual Grants links, GET-only cart boundary, cleanup restore from D:\Work\YounGo\backups\youngo_school_qa_baseline_after_phase_2q2.sql, and final Phase 2J/2L/2M/2P/2R diagnostics.
Phase 2R also fixed user edit rendering for missing/empty social/payment key arrays without documenting or exposing sensitive values.
Phase 2S YounGo CTA boundary fixes are implemented, reviewed, and manually validated locally.
Phase 2S added a guard in Home::get_enrolled_to_free_course() so YounGo-managed course modes subscription_only, subscription_and_purchase, and purchase_only cannot create legacy enrol rows through the legacy free-enrol route.
Course detail, course cards, and wishlist views no longer expose legacy Enroll Now, Add to cart, or Buy Now CTAs for YounGo-managed no-access courses. Wishlist empty-state copy was updated to remove legacy free-enrol/cart wording.
Course 1, Scratch Coding for Young Creators, is the subscription_only legacy-free-risk fixture: manual QA confirmed access-managed messaging, no legacy Enroll Now/Add to cart/Buy Now, and direct /home/get_enrolled_to_free_course/1 redirecting safely back to course detail without creating an enrol row for QA learner user 8. Global enrol remained 1 and user 8 enrol rows remained 0.
Course 9 remains the paid subscription-only checkout-not-ready fixture and manual QA confirmed checkout-not-ready messaging with no Buy Now/Add to cart. Courses listing showed Course 1 access-managed and Course 9 subscription-not-ready.
The Phase 2S diagnostic is scripts/phase_2/youngo_phase_2s_route_cta_boundary_diagnostic.php.
Phase 2U.3 localization schema foundation is implemented, reviewed, committed, and locally applied after backup D:\Work\YounGo\backups\youngo_school_before_phase_2u3_localization_schema_2026_07_16.sql.
Phase 2U.3 created database/phase_2/youngo_phase_2u3_localization_schema_up.sql, database/phase_2/youngo_phase_2u3_localization_schema_down.sql, scripts/phase_2/youngo_phase_2u3_localization_schema_diagnostic.php, and scripts/phase_2/youngo_phase_2u3_seed_english_translations.php.
The schema adds YounGo-specific translation tables for canonical content: youngo_course_translations, youngo_category_translations, youngo_section_translations, and youngo_lesson_translations.
Canonical course/category/section/lesson rows remain stable and continue to be used by access checks, manual grants, subscriptions, future checkout/orders, lesson progress, and diagnostics. Duplicate courses per language are not the chosen default model.
Unique entity/language keys exist: uniq_yct_course_language, uniq_ycat_category_language, uniq_yst_section_language, and uniq_ylt_lesson_language. Language/slug indexes exist where applicable, and no hard foreign keys were added.
The English seed script is idempotent and seeded English translation rows from canonical content without modifying canonical rows: course translations = 7, category translations = 12, section translations = 18, and lesson translations = 36. Canonical counts stayed course = 7, category = 12, section = 18, lesson = 36.
No Arabic rows were created in Phase 2U.3. The language phrase table, Arabic phrase JSON, language settings, language_dirs, frontend rendering, dashboard bilingual forms, and course data were not changed.
Course translation slug values are currently NULL because canonical course rows have no slug column; translated slug generation is deferred to later dashboard/frontend localization work.
Phase 2U.4 canonical Arabic UI phrase support is implemented, reviewed, committed, and locally applied after backup D:\Work\YounGo\backups\youngo_school_before_phase_2u4_arabic_phrase_support_2026_07_16.sql.
Phase 2U.4 added the canonical language.arabic phrase column and created application/language/arabic.json from current project phrase keys and English values. English remains the default language, and settings.language_dirs already includes arabic: rtl.
The canonical Arabic language code going forward is arabic. application/language/arabic_translated.json remains present only as a deprecated placeholder and is not source of truth for new work.
Phase 2U.4 generated Modern Standard Arabic phrase values with simple parent/child-friendly wording. High-priority navigation, access, wishlist, My Courses, My Access, and checkout-not-ready phrases have direct translations; many non-priority phrases use safe Arabic fallback wording and require Arabic phrase polish QA before any public Arabic launch.
The Phase 2U.4 diagnostic is scripts/phase_2/youngo_phase_2u4_arabic_phrase_diagnostic.php and should pass locally. It verifies language.arabic, English default, arabic:rtl direction, phrase coverage, mojibake checks, translation language-code boundaries, guarded non-course Arabic translation rows, and protected entitlement/payment counts.
Phase 2U.4 did not modify frontend rendering, did not implement /ar routes, did not add dashboard bilingual content forms, did not create Arabic course/content translation rows, did not change default language, and did not touch course/category/section/lesson canonical data, checkout/order/coupon/Paymob/payment, or entitlement rows.
Phase 2U.5.2 translation model/helper foundation is implemented, reviewed, committed, and locally diagnostic-tested.
Phase 2U.5.2 created application/models/Youngo_translation_model.php and scripts/phase_2/youngo_phase_2u5_translation_model_diagnostic.php.
Youngo_translation_model is the central helper/model for YounGo bilingual content. It uses the Phase 2U.3 translation tables, keeps canonical course/category/section/lesson IDs stable, does not replace canonical LMS content tables, and does not handle entitlement, subscription, manual grant, checkout, coupon, or payment logic.
Supported canonical content language codes are english and arabic. Compatibility inputs map en to english, ar to arabic, and deprecated arabic_translated to arabic only as an input alias; arabic_translated is not canonical.
The model supports direct translation reads, fallback reads, future dashboard upserts, slug generation/availability checks, has_translation(), and get_missing_translation_summary(). Fallback order is requested language translation, English translation, canonical LMS data, then null for missing entities, with requested_language, resolved_language, is_fallback, missing_translation, and translation_source metadata.
Phase 2U.5.2 did not change dashboard forms, frontend rendering, /ar routes, language phrases, settings, canonical course/category/section/lesson data, checkout/order/coupon/Paymob/payment, or entitlement rows. No Arabic content translation rows were created, and upsert methods were not executed or wired to forms.
The Phase 2U.5 diagnostic is scripts/phase_2/youngo_phase_2u5_translation_model_diagnostic.php and should pass locally.
Phase 2U.5.3 Category/Subcategory Bilingual Form Support is implemented, reviewed, committed, and runtime-QA tested locally.
Phase 2U.5.3 updated application/models/Crud_model.php, category/subcategory add/edit views, and added scripts/phase_2/youngo_phase_2u5_category_bilingual_forms_diagnostic.php.
Category and subcategory add/edit forms now include english_name, english_slug, arabic_name, and arabic_slug. Arabic fields are optional and RTL. Shared metadata including code, parent/category selection, icon, thumbnail, and category image remains canonical/shared outside bilingual fields.
Crud_model::add_category() and Crud_model::edit_category() sync canonical category.name and category.slug from English fields while keeping the legacy name fallback. English category translations are upserted after create/update; Arabic category translations are upserted only when Arabic name or slug is provided. Empty Arabic rows are not created, and arabic_translated is not used.
Runtime QA used backup D:\Work\YounGo\backups\youngo_school_before_phase_2u5_3_category_bilingual_form_qa_2026_07_16_190255.sql, verified temporary category/subcategory add/edit flows plus English-only no-Arabic-row behavior, restored the DB, removed temporary QA data, and returned counts to category = 12, category English translations = 12, category Arabic translations = 0, with protected rows clean.
Phase 2U.5.3 did not modify Admin.php, course forms, section forms, lesson forms, frontend rendering, /ar routes, language phrases, settings, checkout/order/coupon/Paymob/payment, or entitlement logic.
The Phase 2U.5 category diagnostic is scripts/phase_2/youngo_phase_2u5_category_bilingual_forms_diagnostic.php and should pass locally.
Phase 2U.5.4 Course Add/Edit Bilingual Form Support and Course Form UX Alignment is implemented, runtime-QA tested, reviewed, and committed in commit 50ae714 Add YounGo course bilingual form support.
Phase 2U.5.4 updated application/helpers/common_helper.php, application/models/Crud_model.php, application/views/backend/admin/course_add.php, application/views/backend/admin/course_add_shortcut.php, application/views/backend/admin/course_edit.php, scripts/phase_2/youngo_phase_2u4_arabic_phrase_diagnostic.php, scripts/phase_2/youngo_phase_2u5_category_bilingual_forms_diagnostic.php, scripts/phase_2/youngo_phase_2u5_translation_model_diagnostic.php, and scripts/phase_2/youngo_phase_2u5_course_bilingual_forms_diagnostic.php.
Course add/edit forms now support English and Arabic bilingual fields for title, slug, short_description, description, outcomes, requirements, faqs, seo_title, meta_keywords, and meta_description. English fields are canonical and sync back to canonical course fields for existing Academy LMS compatibility. Arabic fields are optional, and Arabic course translation rows are created only when Arabic content is non-empty. English-only courses do not create Arabic translation rows.
Course shortcut remains simple and English-oriented: it creates canonical English course data and an English translation row, with Arabic completion deferred to full course edit.
Course language_made_in is course-content metadata only. Valid marker values are english, arabic, and arabic_translated. The arabic_translated marker is valid for course content/video/material metadata only and must not be stored in youngo_course_translations.language_code. Translation tables continue to use only english and arabic, and this marker does not control system UI language, frontend site language, or /ar routing.
Phase 2U.5.4 fixed a runtime-QA course edit HTTP 500 / partial blank page by safely loading Youngo_translation_model through the CodeIgniter instance, using English fallback values, safely defaulting Arabic arrays when Arabic translation is absent, and fixing malformed HTML in the reviewed course edit sections.
Pricing/access UX now hides/disables one-time price and discount fields for subscription_only courses with helper copy; purchase_only and subscription_and_purchase keep price/discount fields available. Crud_model::update_course() preserves existing price/discount values when disabled fields are not posted, and add/shortcut paths default absent price fields safely. No checkout/payment/Paymob/order/coupon behavior was implemented.
EGP display was normalized in common_helper.php for readability while respecting configured currency position. Observed local settings were system_currency = EGP and currency_position = left, producing readable examples such as EGP 500. DB currency values, DB prices, checkout, payment, and Paymob logic were not changed; Arabic EGP-symbol display may be revisited later with full Arabic UI context.
Phase 2U.5.4 runtime QA used a temporary admin account, not Root Admin. Backup before QA was D:\Work\YounGo\backups\youngo_school_before_phase_2u5_4_temp_admin_runtime_qa_2026_07_17_002027.phpdbdump. QA verified course edit/add rendering, bilingual fields, language marker options, subscription-only and purchase-mode pricing behavior, readable EGP display, temporary bilingual course add/edit, redirect to non-blank edit page, English-only no-Arabic-row behavior, and no duplicate translation rows.
After restore from the Phase 2U.5.4 QA turn, temporary QA data from the final QA turn was removed. Existing manual course QA data from before that backup remained intentionally present: course = 8, youngo_course_translations total = 9, english rows = 8, arabic rows = 1, category = 12, section = 18, lesson = 36, enrol = 1, and payment/watch/progress/entitlement/checkout/coupon protected rows = 0.
Phase 2U.5.5 Section/Lesson Bilingual Form Support is implemented, focused-reviewed, runtime-QA tested, restored, and committed in commit f3368fc Add YounGo section lesson bilingual form support.
Phase 2U.5.5 updated application/models/Crud_model.php, application/views/backend/admin/section_add.php, application/views/backend/admin/section_edit.php, application/views/backend/admin/lesson_add.php, application/views/backend/admin/lesson_edit.php, application/views/backend/admin/text_type_lesson_add.php, application/views/backend/admin/text_type_lesson_edit.php, scripts/phase_2/youngo_phase_2u5_course_bilingual_forms_diagnostic.php, and scripts/phase_2/youngo_phase_2u5_section_lesson_bilingual_forms_diagnostic.php.
Section add/edit forms now support required or primary english_title and optional RTL arabic_title. Canonical section.title syncs from English. English section translations are upserted, and Arabic section translations are upserted only when Arabic title is non-empty. Blank Arabic section fields do not create empty Arabic rows and do not delete existing Arabic rows in this phase.
Lesson add/edit forms now support required or primary english_title, optional english_summary, optional RTL arabic_title, and optional RTL arabic_summary. Canonical lesson.title and lesson.summary sync from English. English lesson translations are upserted, and Arabic lesson translations are upserted only when at least one Arabic translatable field is non-empty. Blank Arabic lesson fields do not create empty Arabic rows and do not delete existing Arabic rows in this phase.
Text lesson add/edit partials now support english_text_content and optional RTL arabic_text_content. Canonical text lesson body/content syncs from English. Arabic text content is stored only in youngo_lesson_translations. Non-text lessons are not forced to provide text content, and media/video/PDF/file/shared fields remain shared rather than translated.
Phase 2U.5.5 added Crud_model::sync_legacy_lesson_post_fields() because existing legacy media handlers still expected POST fields such as title, summary, and text_description. The bridge copies English lesson fields into those legacy keys before legacy handlers run, protecting Academy Cloud/video/media behavior from blank titles after the bilingual field rename.
Youngo_translation_model is used for youngo_section_translations and youngo_lesson_translations. Translation table language codes remain english and arabic only; arabic_translated is not a translation-table language code.
Runtime QA used backup D:\Work\YounGo\backups\youngo_school_before_phase_2u5_5_section_lesson_bilingual_qa_2026_07_18_194034.sql. Temporary admin login succeeded, temporary Course Manager role plus legacy course/category permissions were used only under backup, restore removed that setup, and pre/post counts matched: course = 8, section = 18, lesson = 36, section translations total = 18, section English = 18, section Arabic = 0, lesson translations total = 36, lesson English = 36, lesson Arabic = 0, youngo_user_roles = 0, permissions = 2, enrol = 1, and protected payment/watch/progress/entitlement/checkout/coupon rows = 0.
QA verified bilingual section add/edit, bilingual text lesson add/edit, English-only section and text lesson no-Arabic-row behavior, minimal YouTube lesson media/title compatibility, canonical English sync, English translation upserts, conditional Arabic translation upserts, no duplicate translation rows, Arabic UTF-8 cleanliness, temporary data removal after restore, and protected rows clean.
The Phase 2U.5.5 diagnostic is scripts/phase_2/youngo_phase_2u5_section_lesson_bilingual_forms_diagnostic.php. It checks expected section/lesson/text lesson files, bilingual fields, Arabic RTL markers, shared media/type/course/section fields, Youngo_translation_model save-path usage, English upserts, conditional Arabic upserts, translation table existence and English coverage, Arabic section/lesson rows staying 0 after restore, protected payment/access/checkout/coupon rows, and role/course/category/translation diagnostic compatibility.
Runtime QA found the temporary admin lacked the legacy course permission. That was temporarily adjusted only inside QA and restored afterward. Phase 2V.0 audited the roles/permissions gap, and Phase 2V.1 Role Assignment Management is implemented, runtime-QA tested, reviewed, and committed in commit 21aecd7 Add YounGo role assignment management.
Phase 2V.1 adds YounGo -> Role Assignments at /admin/youngo/role-assignments, guarded by manage_roles, with Root Admin, Admin, Content, Course, and Instructor toggles. The authority model is: Root Admin = developer/system owner/highest authority; Admin = client/operational owner; Content/Course/Instructor = scoped operational roles. Root Admin remains read-only and protected from everyone else; Admin can manage non-root user roles but cannot modify Root Admin; Content/Course/Instructor cannot manage roles unless explicitly granted manage_roles; Admin is mutually exclusive with Content/Course/Instructor; Content, Course, and Instructor can combine, with Content + Course acting as the practical Content & Course Manager state.
Phase 2V.1 bridges legacy users.role_id / users.is_instructor / permissions.permissions / check_permission() behavior with YounGo roles and capabilities in youngo_roles, youngo_capabilities, youngo_role_capabilities, and youngo_user_roles. This bridge is intentional because legacy course/category pages still rely on check_permission('course') and check_permission('category'). The implementation avoids the legacy no-permissions-row full-access behavior for non-root users.
The required Admin authority mapping is represented by reversible seed files database/phase_2/youngo_phase_2v1_admin_manage_roles_up.sql and database/phase_2/youngo_phase_2v1_admin_manage_roles_down.sql. The up script maps only YounGo admin to manage_roles; scoped roles do not receive manage_roles. The down script removes only admin -> manage_roles without deleting role/capability rows or unrelated mappings.
Phase 2V.1 runtime QA used the temporary admin account, not Root Admin. Backup before QA was D:\Work\YounGo\backups\youngo_school_before_phase_2v1_admin_manage_roles_alignment_2026_07_17_061234.sql. QA verified Admin access to Role Assignments, non-root role updates, Root Admin read-only UI, protected_root_admin rejection for tampered Root Admin update, Course-only denial from Role Assignments, scoped roles without manage_roles, Admin mutual exclusion, restored temporary QA assignments, reapplied required admin -> manage_roles mapping, and clean protected payment/checkout/entitlement/manual-grant/coupon rows.
The Phase 2V.1 diagnostic is scripts/phase_2/youngo_phase_2v_role_assignment_diagnostic.php. It checks role assignment files/routes/guards, Root Admin protection markers, Admin mutual exclusion, admin -> manage_roles, scoped roles without manage_roles, SQL seed files, legacy bridge markers, and payment/checkout scope boundaries.
Phase 2U.6.2 Frontend Language Context and URL Mapping Helpers is implemented, focused-reviewed, committed, and diagnostics-tested in commit 25e0544 Add YounGo frontend language context helpers. It added application/helpers/youngo_frontend_language_helper.php and scripts/phase_2/youngo_phase_2u6_frontend_language_context_diagnostic.php. The helper normalizes frontend language aliases to english/arabic only, treats arabic_translated as an input alias to arabic only, detects ar-prefixed frontend URIs, maps English and Arabic URLs while preserving query strings, excludes admin/payment/checkout/cart/coupon/write/API/cron paths, and provides future html lang/dir helpers. It did not add routes, frontend rendering, content translation shaping, session/cookie/settings writes, or checkout/payment changes.
Phase 2U.6.3 Arabic Public Route Aliases is implemented, focused-reviewed, diagnostics-tested, committed, and pushed in commit 4b773b2 Add YounGo Arabic public route aliases. English canonical frontend URLs remain unprefixed. Arabic canonical public frontend URLs use /ar/..., and /en must not become canonical. No /en routes were added and no English routes were redirected to /en. Admin/dashboard and checkout/payment/cart/coupon/write/API/cron routes remain unlocalized. arabic_translated is not a route language, UI language, or translation-table language; course language_made_in remains separate course-content metadata.
Arabic public aliases added in Phase 2U.6.3 are /ar -> home/index, /ar/courses -> home/courses, /ar/courses/{page} -> home/courses, /ar/course/{slug}/{id} -> home/course/$1/$2, /ar/search -> home/search, /ar/search/{query} -> home/search/$1, /ar/my-courses -> home/my_courses, /ar/my-access -> home/my_access, /ar/wishlist -> home/my_wishlist, /ar/login -> login/index, and /ar/sign-up -> sign_up/index. English unprefixed routes remain unchanged. Course detail preserves slug then id argument order, search preserves the query argument, and course pagination remains compatible with Home::courses() using URI segment 3. /ar/login and /ar/sign-up target the existing public auth controllers.
Phase 2U.6.3 did not add aliases for admin, addons, api, cron, home/payment, home/paypal, home/stripe, home/paymob, home/razorpay, home/paystack, home/flutterwave, home/course_payment, home/shopping_cart, home/update_cart, home/apply_coupon, home/remove_coupon, home/checkout, home/confirm_payment, home/webhook, coupon write routes, cart write routes, or payment callback routes. Lesson/player/PDF aliases remain deferred, including Home::lesson, Home::pdf_canvas, Home::play_lesson, mobile lesson helpers, and offline video helpers, because those are gated playback/progress/session surfaces that need separate staged QA before Arabic aliases are added.
Phase 2U.6.3 did not implement frontend translated content rendering, frontend RTL shell rendering, a language switcher UI, course/category/section/lesson translation shaping, session language writes, cookie writes, settings writes, phrase writes, checkout/payment/cart/coupon localization, or admin/dashboard localization. Arabic route aliases may still render existing canonical frontend content until later translation-aware rendering phases.
The Phase 2U.6.3 diagnostic is scripts/phase_2/youngo_phase_2u6_arabic_route_alias_diagnostic.php. It checks /ar public aliases, preserved English routes, no /en routes, no admin/payment/checkout/cart/coupon/API/cron Arabic aliases, argument mapping, route order, Phase 2U.6.2 helper compatibility, no arabic_translated route/UI language usage, no frontend rendering/content translation changes, and Phase 2S/2P/2R compatibility. Focused review also tightened existing diagnostics so routes.php changes are tolerated only when the diff is Arabic-alias-only. PHP lint and compatibility diagnostics passed. HTTP GET smoke was intentionally skipped; static route diagnostics were used instead.
Phase 2U.6.4 Translation-aware Frontend Content Shaping is implemented, focused-reviewed, runtime-QA tested, restored, committed, and pushed in commit 12fcdae Add YounGo frontend content translation shaping. It added application/helpers/youngo_frontend_content_helper.php and scripts/phase_2/youngo_phase_2u6_frontend_content_translation_diagnostic.php, and updated Home.php, common_helper.php, YounGo course listing/detail/wishlist views, and Phase 2U.6 diagnostics.
The Phase 2U.6.4 helper uses Youngo_translation_model and the Phase 2U.6.2 frontend language context helper to shape home, courses, search, course detail, my courses, my access, and wishlist initial page data. It overlays only whitelisted display fields, preserves canonical IDs and operational fields, stores original canonical values in safe youngo_canonical_* metadata, never emits/stores arabic_translated, and does not write DB/session/cookie/settings or call unknown get_phrase() keys.
Fallback order is Arabic route = arabic translation -> english translation -> canonical LMS field, and English route = english translation -> canonical LMS field. Course shaped fields are title, short_description, description, outcomes, requirements, faqs, seo_title, meta_keywords, and meta_description. Category display names can be shaped while canonical category ID/slug remain filter and URL identity. Section title and lesson title/summary can be shaped outside deferred player/PDF routes, with helper support for text lesson body on non-player display paths. IDs, slugs/link identity, price, discount, currency, access modes, media, instructor, level, progress, entitlement/access fields, CTA state, wishlist state, checkout/payment fields, lesson type, duration, attachment, video URL, and player/PDF routes remain unchanged.
Phase 2U.6.4 runtime QA used backup D:\Work\YounGo\backups\youngo_school_before_phase_2u6_4_frontend_content_translation_qa_2026_07_18_215217.sql. Temporary Arabic rows were inserted only into YounGo translation tables for course 1, category 7, section 1, and lesson 1; no canonical rows were changed. Restore removed temporary rows and pre/post counts matched exactly: course 8, category 12, section 18, lesson 36, course translations total 9 / English 8 / Arabic 1, category translations total 12 / English 12 / Arabic 0, section translations total 18 / English 18 / Arabic 0, lesson translations total 36 / English 36 / Arabic 0, ci_sessions 636, youngo_user_roles 0, permissions 2, enrol 1, and protected payment/watch/progress/entitlement/checkout/coupon rows 0.
QA confirmed / and /home/courses remain English/canonical, /ar and /ar/courses display Arabic shaped values where temporary translations exist, /home/course/scratch-coding-for-young-creators/1 remains English/canonical, /ar/course/scratch-coding-for-young-creators/1 displays Arabic shaped course/section/lesson values where available, search Arabic aliases resolve, and /ar/wishlist, /ar/my-courses, and /ar/my-access resolve without fatal/404 in unauthenticated safe GET checks. /ar/admin, /ar/home/payment, /ar/home/checkout, /ar/home/shopping_cart, /ar/home/apply_coupon, /ar/api, and /ar/cron render the app 404 page. No write endpoints were submitted and no /en links were introduced.
Current working English routes for course/list/search/detail surfaces remain /home/...; direct /courses, /course/..., /search, and /wishlist are not current English routes. Arabic public route aliases use /ar/... and English canonical URL strategy remains unprefixed.
The Phase 2U.6.4 diagnostic is scripts/phase_2/youngo_phase_2u6_frontend_content_translation_diagnostic.php. It checks helper functions, Home.php integration points, /ar aliases, no /en routes, no new route changes, no payment/checkout/cart/coupon localization, no arabic_translated table-language usage, canonical IDs/access/media/progress preservation in shaping logic, read-only sample shaping, and route/language/section-lesson/role/course-category/translation/access diagnostic compatibility.
Phase 2U.6.5 Language Switcher and RTL Shell Rendering is implemented, focused-reviewed, runtime-QA tested, restored, committed, and pushed in commit 8321e91 Add YounGo language switcher and RTL shell.
Phase 2U.6.5 updated application/helpers/youngo_frontend_language_helper.php, application/views/frontend/youngo/header.php, application/views/frontend/youngo/index.php, assets/frontend/youngo/css/youngo.css, scripts/phase_2/youngo_phase_2u5_section_lesson_bilingual_forms_diagnostic.php, scripts/phase_2/youngo_phase_2u6_arabic_route_alias_diagnostic.php, scripts/phase_2/youngo_phase_2u6_frontend_content_translation_diagnostic.php, scripts/phase_2/youngo_phase_2u6_frontend_language_context_diagnostic.php, and scripts/phase_2/youngo_phase_2u6_language_switcher_rtl_diagnostic.php.
The YounGo frontend shell now uses route-derived language helper values. English routes render html lang="en" dir="ltr"; Arabic /ar routes render html lang="ar" dir="rtl". The frontend body adds helper-derived and escaped youngo-lang-english/youngo-lang-arabic, youngo-dir-ltr/youngo-dir-rtl, data-youngo-language, and data-youngo-dir metadata while preserving existing body classes. The admin/backend shell remains unaffected.
The YounGo frontend header now includes an EN | عربي language switcher with active state and aria-current. URLs are helper-generated, preserve query strings, never generate /en, never show/link arabic_translated, and map the current English route reality to Arabic aliases, including / -> /ar, /home/courses -> /ar/courses, /ar/courses -> /home/courses, /home/course/{slug}/{id} -> /ar/course/{slug}/{id}, and /ar/course/{slug}/{id} -> /home/course/{slug}/{id}.
Focused review fixed a subdirectory URL risk: youngo_frontend_current_uri_string_with_query() now prefers CodeIgniter's $CI->uri->uri_string() over raw REQUEST_URI and uses REQUEST_URI only as fallback, because raw REQUEST_URI can include the app base path on subdirectory installs and generate wrong switcher URLs.
Minimal YounGo-scoped RTL CSS was added in assets/frontend/youngo/css/youngo.css. The RTL CSS is header/nav focused, keeps the language switcher readable/LTR, and does not attempt full visual RTL polish.
Phase 2U.6.5 runtime QA used backup D:\Work\YounGo\backups\youngo_school_before_phase_2u6_5_language_switcher_rtl_qa_2026_07_18_224356.sql. No temporary Arabic rows were needed. QA confirmed / resolved 200 with lang="en" and dir="ltr"; /ar resolved 200 with lang="ar" and dir="rtl"; body metadata was correct; switcher active state worked on English and Arabic pages; / switched to /ar and /ar switched to /; /home/courses and /ar/courses resolved 200; /home/courses?page=2 switched to /ar/courses?page=2 and back; /home/course/scratch-coding-for-young-creators/1 and /ar/course/scratch-coding-for-young-creators/1 resolved 200 with slug/id preserved; /home/search?query=Scratch and /ar/search?query=Scratch resolved 200 with query preservation; /ar/login and /ar/sign-up resolved 200 using the YounGo shell with Arabic RTL metadata and switcher; /ar/wishlist resolved 200; and /ar/my-courses plus /ar/my-access kept normal unauthenticated refresh redirect behavior. Excluded /ar/admin, /ar/home/payment, /ar/home/checkout, /ar/home/shopping_cart, /ar/home/apply_coupon, /ar/api, and /ar/cron rendered the app 404 page. No write endpoints were called.
The Phase 2U.6.5 DB restore succeeded and pre/post counts matched exactly: ci_sessions 636, course 8, category 12, section 18, lesson 36, youngo_course_translations total 9 / English 8 / Arabic 1, youngo_category_translations total 12 / English 12 / Arabic 0, youngo_section_translations total 18 / English 18 / Arabic 0, youngo_lesson_translations total 36 / English 36 / Arabic 0, enrol 1, and protected YounGo entitlement/payment/checkout/coupon/progress tables 0.
The Phase 2U.6.5 diagnostic is scripts/phase_2/youngo_phase_2u6_language_switcher_rtl_diagnostic.php. It checks shell html lang/dir helper usage, English en/ltr and Arabic ar/rtl mapping, body language/dir metadata, language switcher existence, helper-generated URLs, query preservation, no /en links/routes, no route additions, no session/cookie/settings language writes, no DB/phrase writes, no checkout/payment/cart/coupon localization, admin shell boundaries, and compatibility with content translation and Arabic route diagnostics.
PDF-specific browser QA was skipped to avoid broadening scope. Broader API/mobile entitlement UX remains future work.
Final local entitlement write tables are empty after QA cleanup: youngo_course_access = 0, youngo_user_subscriptions = 0, youngo_manual_grants = 0, youngo_checkout_orders = 0.
Checkout/order issuance remains intentionally stubbed with checkout_issuance_not_implemented.
Paymob/payment/coupons are not implemented.
Do not create real grants/access/subscriptions through manual SQL. Root Admin may act as authorized grant/revoke actor but must not be modified.
Do not route YounGo-managed courses through legacy free enrol, checkout/cart/coupon behavior as a shortcut; checkout, coupons, and Paymob remain later phases.
Learner access/account visibility must use the centralized read methods in Youngo_entitlement_model; grant/access writes must continue to use Youngo_entitlement_write_model.
Frontend phrase conversion/polish, My Courses/Wishlist AJAX language propagation, full RTL visual polish, performance batching for translation reads, hreflang/canonical SEO, lesson/player/PDF Arabic aliases, mobile/API entitlement alignment, course data rebuild, and checkout/Paymob/subscription purchase flow remain future work. Recommended next roadmap: Phase 2U.6.6 frontend phrase conversion/inventory, Phase 2U.6.7 controlled frontend localization QA and diagnostics, Phase 2U.7 bilingual QA plus phrase polish QA, Phase 2T course data rebuild, then checkout/Paymob/subscription purchase flow. Phase 2V.2 role-assignment docs/UX polish may be handled only if needed and is not blocking localization.
```

---

## 16. Current Development State

The project has been initialized as a Git repository.

The current safe working branch is:

```text
analysis/cms-audit
```

The local database may not yet be fully configured.

Do not assume the application runs locally until database setup/import is confirmed.

Sensitive local configuration files such as database credentials should not be committed.

Phase 2I starts from the clean, pushed local baseline commit:

```text
ca30def
```

This commit is recorded only as the clean starting point for Phase 2I documentation alignment, not as a permanent current baseline for later phases.

---

## 17. Current Approved Direction

The current approved direction is:

```text
Use the existing CodeIgniter CMS as the foundation.
Create a new custom YounGo frontend/theme.
Keep the YounGo design fixed and consistent.
Make website content manageable from the CMS.
Reuse existing LMS data where useful.
Add new YounGo-specific CMS structures only where needed.
Keep design, reference, planning, and agent documentation separated by purpose.
```

The current master plan further clarifies that Academy LMS is the core engine for authentication, roles, courses, lessons, enrollment, cart, wishlist, payments, reviews, progress, and related LMS workflows. Existing working LMS functionality must be reused whenever possible and must not be rebuilt unless reuse is proven impractical.

````
