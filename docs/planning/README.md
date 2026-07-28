# YounGo Planning Folder

## 1. Purpose

This folder contains planning documents for the YounGo project.

Planning documents explain what should be built, in what order, and under what constraints before implementation starts.

This folder is used to keep delivery plans, phase plans, storage/content planning, implementation sequencing, and acceptance criteria separate from project context, design references, agent prompts, and old CMS documentation.

Planning documents should help developers and agents understand the approved work without guessing or drifting into unrelated changes.

---

## Master Plan Reference

The current source of truth for project priorities, architecture, reuse decisions, deployment preparation, and implementation sequencing is:

[`youngo_master_plan_v2.md`](./youngo_master_plan_v2.md)

All implementation work must be reviewed against this plan before modifying or rebuilding existing Academy LMS functionality.

Academy LMS must be reused as the core system whenever possible. Existing working LMS functionality must not be rebuilt unless reuse is proven impractical.

Older planning documents in this folder remain useful historical or supporting references, but they are superseded by the master plan wherever priorities, sequencing, completion status, or reuse decisions differ.

---

## 2. What Belongs in This Folder

Use `docs/planning/` for:

- Project delivery plans
- Phase plans
- Implementation sequence documents
- Storage/content planning documents
- CMS content structure plans
- Acceptance criteria
- Testing and validation plans
- Risk notes related to a planned delivery phase
- Approved planning decisions that affect what will be built

Examples:

```text
docs/planning/phase_1.md
docs/planning/youngo_homepage_content_schema.md
```

---

## 3. What Does Not Belong in This Folder

Do not use `docs/planning/` for:

- Full project overview
- Brand identity or visual design system
- Stitch HTML exports
- Stitch screenshots
- Old CMS/vendor documentation
- Agent prompts
- General agent rules
- Production PHP code
- SQL scripts
- Database migration files
- Controller code
- Model code
- View code
- CSS
- JavaScript
- Temporary research dumps
- Unapproved implementation notes

Planning documents may describe intended implementation direction, but they should not contain production implementation code.

---

## 4. Current Planning Status

The current master planning document is:

```text
docs/planning/youngo_master_plan_v2.md
```

This file is the active planning source of truth.

`phase_1.md` and `youngo_homepage_content_schema.md` are retained as earlier Phase 1 and homepage-content planning references. They should not override the master plan, especially where the master plan documents completed work or revised deployment priorities.

The active Phase 2 supporting architecture document is:

```text
docs/planning/youngo_phase_2_hybrid_access_subscriptions_roles_architecture.md
```

Read it before planning or implementing changes to access, subscriptions, course purchase, coupons, manual grants, checkout, Paymob/payment flow, roles, permissions, or instructor assignment.

The active Phase 2B database schema and migration planning reference is:

```text
docs/planning/youngo_phase_2_database_schema_migration_plan.md
```

It documents the approved additive schema direction and local live-DB verification notes for Phase 2B. It is planning-only and must not be treated as proof that schema changes, migrations, subscriptions, entitlements, manual grants, expanded coupons, direct checkout, Paymob, or multi-role capabilities have been implemented.

The active Phase 2C entitlement/access compatibility planning reference is:

```text
docs/planning/youngo_phase_2_entitlement_access_compatibility_plan.md
```

It documents the approved read-only-first access-layer direction for deciding course and lesson access across legacy enrolment, future course access records, future subscriptions, manual grants, admin bypass, and assigned instructor access. It is planning-only and must not be treated as proof that entitlement helpers, source-code changes, subscriptions, manual grants, direct checkout, Paymob, or new access behavior have been implemented.

The active Phase 2D multi-role/capability compatibility planning reference is:

```text
docs/planning/youngo_phase_2_multi_role_capability_compatibility_plan.md
```

It documents the approved additive role/capability direction for preserving `users.role_id`, `users.is_instructor`, legacy `permissions`, Root Admin protection, restricted admin compatibility, and instructor assignment behavior while preparing YounGo roles and capabilities. It is planning-only and must not be treated as proof that role/capability schema, helpers, account role toggles, permission changes, or new multi-role behavior have been implemented.

The active Phase 2E schema migration execution planning reference is:

```text
docs/planning/youngo_phase_2_schema_migration_execution_plan.md
```

It documents the approved local-first, reviewable migration execution direction for creating Phase 2 schema artifacts later without using `Updater.php`, without modifying `uploads/install.sql`, and without applying schema to the server. It is planning-only and must not be treated as proof that SQL artifacts, migrations, database changes, seed data, or Phase 2 schema implementation have been created or applied.

Before implementation begins, supporting planning documents may still be created where needed to define content structures, storage direction, acceptance criteria, and safe implementation boundaries, but they must be checked against the master plan.

Current Phase 2 local status summary:

- Subscription Plan Management, EGP currency alignment, the Shared Entitlement Write Service foundation, Manual Grants dashboard/UI, and learner-facing entitlement visibility/enforcement alignment are implemented and QA-tested locally.
- Phase 2O aligned course cards, course detail CTAs, selected lesson/PDF/helper access gates, and course review gates with the YounGo entitlement read layer.
- Phase 2P added My Courses / My Access learner visibility: My Courses now uses prepared learner access items for active legacy enrolments and active direct YounGo/manual course access rows, and My Access shows visibility-only subscription/access summaries.
- Subscription access intentionally does not auto-list every eligible course in My Courses.
- Phase 2Q created the curated QA learner baseline locally: user 8, qa.learner@youngo.local, YounGo QA Learner, role_id 2, active, is_instructor 0. Do not document, print, commit, store, or reuse the QA learner password.
- The curated QA learner baseline backup is D:\Work\YounGo\backups\youngo_school_qa_baseline_after_phase_2q2.sql. Use it for future learner-authenticated QA.
- Current QA fixtures are user 8 clean no-access learner, user 2/course 6 legacy enrol compatibility, course 1 manual-grant / lesson-safe QA, course 9 paid subscription-only checkout-not-ready CTA, and plan 1 Monthly EGP manual subscription QA.
- Phase 2Q.3 learner-authenticated browser QA is completed locally for implemented Phase 2O/2P learner surfaces using user 8. It covered My Courses/My Access no-access baselines, manual course grant visibility and revocation, subscription visibility and revocation, course listing/detail access states, normal learner lesson access with active manual grant, review-area visibility without submission, duplicate rejection, GET-only cart boundary, and full restore cleanup.
- The same curated baseline backup was restored after Phase 2Q.3. Temporary rows before restore were `watch_histories = 1`, `youngo_course_access = 1`, `youngo_user_subscriptions = 1`, and `youngo_manual_grants = 2`; after restore, entitlement tables were empty and diagnostics passed.
- Phase 2R read-only admin/user entitlement summaries are implemented and authenticated-QA tested locally. User edit and course edit show read-only YounGo entitlement summaries when the admin has `grant_manual_access`; Manual Grants remains the only grant/revoke surface.
- Phase 2R added `scripts/phase_2/youngo_phase_2r_admin_entitlement_summary_diagnostic.php`, and Phase 2R QA verified summary rendering, temporary grant/subscription summary updates, duplicate rejection, revocation, filtered Manual Grants links, GET-only cart boundary, cleanup restore from the curated QA baseline backup, and final clean diagnostics.
- Phase 2S CTA boundary fixes are implemented, reviewed, and manually validated locally. `Home::get_enrolled_to_free_course()` blocks legacy free enrol for YounGo-managed modes `subscription_only`, `subscription_and_purchase`, and `purchase_only`.
- Phase 2S confirmed Course 1 no longer exposes legacy Enroll Now/Add to cart/Buy Now and the direct free-enrol route redirects safely without creating a user 8 enrol row. Course 9 remains checkout-not-ready with no Buy Now/Add to cart. Wishlist CTAs and empty-state copy no longer point YounGo-managed courses toward legacy enrol/cart/buy shortcuts.
- Phase 2S added `scripts/phase_2/youngo_phase_2s_route_cta_boundary_diagnostic.php`, and the current local diagnostic set through Phase 2S passes.
- Phase 2U.3 localization schema foundation is implemented, reviewed, committed, and locally applied after backup `D:\Work\YounGo\backups\youngo_school_before_phase_2u3_localization_schema_2026_07_16.sql`.
- Phase 2U.3 added translation tables `youngo_course_translations`, `youngo_category_translations`, `youngo_section_translations`, and `youngo_lesson_translations`. Canonical course/category/section/lesson IDs remain stable for access, grants, subscriptions, future checkout/orders, progress, and diagnostics; duplicate courses per language are not the default model.
- English translation rows were seeded idempotently from existing canonical content: course translations `7`, category translations `12`, section translations `18`, and lesson translations `36`. Canonical counts stayed course `7`, category `12`, section `18`, lesson `36`.
- Phase 2U.3 did not create Arabic rows, repair/import Arabic phrases, add an Arabic DB language column, change language settings, change frontend rendering, add dashboard bilingual forms, or rebuild courses. Course translation slug values are currently `NULL` because canonical courses have no slug column.
- Phase 2U.3 added `scripts/phase_2/youngo_phase_2u3_localization_schema_diagnostic.php`, and its diagnostic passes locally.
- Phase 2U.4 canonical Arabic UI phrase support is implemented, reviewed, committed, and locally applied after backup `D:\Work\YounGo\backups\youngo_school_before_phase_2u4_arabic_phrase_support_2026_07_16.sql`.
- Phase 2U.4 added `language.arabic`, created `application/language/arabic.json`, left English as the default language, and uses canonical language code `arabic`. `application/language/arabic_translated.json` remains present but deprecated and non-canonical.
- Arabic phrases were generated from current project phrase keys and English values, with direct translations for high-priority YounGo UI phrases and safe Arabic fallback wording for many non-priority phrases. Arabic phrase polish QA is required before public Arabic launch.
- Phase 2U.4 did not implement frontend Arabic rendering, `/ar` routes, dashboard bilingual forms, Arabic course/content translation rows, course rebuild, checkout/order/coupon/Paymob/payment, or entitlement writes.
- Phase 2U.4 added `scripts/phase_2/youngo_phase_2u4_arabic_phrase_diagnostic.php`, and its diagnostic passes locally.
- Phase 2U.5.2 translation model/helper foundation is implemented, reviewed, committed, and locally diagnostic-tested.
- Phase 2U.5.2 added `application/models/Youngo_translation_model.php` and `scripts/phase_2/youngo_phase_2u5_translation_model_diagnostic.php`.
- `Youngo_translation_model` is the central helper/model for YounGo bilingual content. It uses the Phase 2U.3 translation tables, preserves canonical course/category/section/lesson IDs, and does not handle entitlement, subscription, manual grant, checkout, coupon, or payment logic.
- Supported canonical language codes are `english` and `arabic`; compatibility inputs map `en` to `english`, `ar` to `arabic`, and deprecated `arabic_translated` to `arabic` only as an input alias.
- The model supports direct translation reads, fallback reads, future dashboard upsert methods, slug generation/availability checks, and missing-translation summaries. Fallback order is requested language translation, English translation, canonical LMS data, then `null` for missing entities; fallback metadata includes `requested_language`, `resolved_language`, `is_fallback`, `missing_translation`, and `translation_source`.
- Phase 2U.5.2 did not wire dashboard forms, change frontend rendering, implement `/ar` routes, edit language phrases, create Arabic content translation rows, rebuild courses, or touch checkout/order/coupon/Paymob/payment or entitlement rows. Upsert methods were reviewed but not executed.
- Phase 2U.5.2 added `scripts/phase_2/youngo_phase_2u5_translation_model_diagnostic.php`, and the current local diagnostic set through Phase 2U.5 passes.
- Phase 2U.5.3 Category/Subcategory Bilingual Form Support is implemented, reviewed, runtime-QA tested, restored, and committed.
- Phase 2U.5.3 updated `application/models/Crud_model.php`, category/subcategory add/edit views, and added `scripts/phase_2/youngo_phase_2u5_category_bilingual_forms_diagnostic.php`.
- Category/subcategory add/edit forms now include `english_name`, `english_slug`, `arabic_name`, and `arabic_slug`; Arabic fields are optional and RTL, while shared fields such as code, parent/category selection, icon, thumbnail, and category image remain canonical/shared.
- `Crud_model::add_category()` and `Crud_model::edit_category()` sync canonical `category.name`/`category.slug` from English fields, keep the legacy `name` fallback, upsert English category translations, and conditionally upsert Arabic category translations only when Arabic name or slug is present. Empty Arabic rows are not created and `arabic_translated` is not used.
- Phase 2U.5.3 runtime QA used backup `D:\Work\YounGo\backups\youngo_school_before_phase_2u5_3_category_bilingual_form_qa_2026_07_16_190255.sql`, verified temporary category/subcategory English+Arabic add/edit flows plus English-only no-Arabic-row behavior, restored the DB, removed temporary QA data, and returned to category `12`, category English translations `12`, category Arabic translations `0`, and protected rows clean.
- Phase 2U.5.3 did not change course forms, section forms, lesson forms, frontend rendering, `/ar` routes, language phrases, settings, course rebuild state, checkout/order/coupon/Paymob/payment, or entitlement logic.
- Phase 2U.5.3 added `scripts/phase_2/youngo_phase_2u5_category_bilingual_forms_diagnostic.php`, and that diagnostic continues to pass locally.
- Phase 2U.5.4 Course Add/Edit Bilingual Form Support and Course Form UX Alignment is implemented, runtime-QA tested, reviewed, restored, and committed in commit `50ae714` (`Add YounGo course bilingual form support`).
- Phase 2U.5.4 updated `application/helpers/common_helper.php`, `application/models/Crud_model.php`, course add/edit/shortcut views, the Phase 2U.4/2U.5 diagnostics, and added `scripts/phase_2/youngo_phase_2u5_course_bilingual_forms_diagnostic.php`.
- Course add/edit forms now include English and optional RTL Arabic fields for `title`, `slug`, `short_description`, `description`, `outcomes`, `requirements`, `faqs`, `seo_title`, `meta_keywords`, and `meta_description`. English fields are canonical and sync to the canonical `course` row for Academy LMS compatibility. English course translations are upserted after canonical create/update. Arabic course translations are upserted only when Arabic content is non-empty; English-only courses do not create Arabic rows.
- Course shortcut remains English-oriented: it creates canonical English course data and an English translation row, with Arabic completion deferred to the full course edit form.
- Course `language_made_in` is course-content metadata only. Valid marker values are `english`, `arabic`, and `arabic_translated`; `arabic_translated` means Arabic-translated course content/video/material only. Translation table language codes remain only `english` and `arabic`; `arabic_translated` must not be used in `youngo_course_translations.language_code` and does not control site language or `/ar` routing.
- Phase 2U.5.4 fixed a runtime-QA course edit HTTP 500 / partial blank page by safely loading `Youngo_translation_model` via the CodeIgniter instance, using English fallback values, defaulting Arabic arrays safely when Arabic translations are absent, and fixing malformed HTML in reviewed course edit sections.
- Pricing/access UX now hides/disables one-time price and discount fields for `subscription_only` courses with helper copy; `purchase_only` and `subscription_and_purchase` keep price/discount available. `Crud_model::update_course()` preserves existing price/discount values when disabled price fields are not posted. Add/shortcut paths default absent price fields safely. No checkout/payment/Paymob/order/coupon behavior was implemented.
- EGP display was normalized for readability in `common_helper.php`, respecting configured currency position. Observed local settings were `system_currency = EGP` and `currency_position = left`, with readable output such as `EGP 500`. DB currency values, DB prices, checkout, payment, and Paymob logic were not changed; Arabic EGP-symbol display can be revisited with full Arabic UI context.
- Phase 2U.5.4 runtime QA used a temporary admin account, not Root Admin. Backup before QA was `D:\Work\YounGo\backups\youngo_school_before_phase_2u5_4_temp_admin_runtime_qa_2026_07_17_002027.phpdbdump`. QA verified course edit/add rendering, bilingual fields, language marker options, subscription-only and purchase-mode pricing behavior, readable EGP display, temporary bilingual course add/edit, redirect to non-blank edit page, English-only no-Arabic-row behavior, and no duplicate translation rows.
- After restore from the Phase 2U.5.4 QA turn, temporary QA data from the final QA turn was removed. Existing manual course QA data from before that backup remained intentionally present: course `8`, course translation total `9`, English course translations `8`, Arabic course translations `1`, category `12`, section `18`, lesson `36`, enrol `1`, and protected payment/watch/progress/entitlement/checkout/coupon rows `0`.
- Phase 2U.5.5 Section/Lesson Bilingual Form Support is implemented, focused-reviewed, runtime-QA tested, restored, and committed in commit `f3368fc` (`Add YounGo section lesson bilingual form support`).
- Phase 2U.5.5 updated `application/models/Crud_model.php`, section add/edit views, lesson add/edit views, text lesson add/edit partials, `scripts/phase_2/youngo_phase_2u5_course_bilingual_forms_diagnostic.php`, and added `scripts/phase_2/youngo_phase_2u5_section_lesson_bilingual_forms_diagnostic.php`.
- Section add/edit forms now include required or primary `english_title` and optional RTL `arabic_title`. Canonical `section.title` syncs from English. English section translations are upserted, and Arabic section translations are upserted only when Arabic title is non-empty. Blank Arabic fields do not create empty Arabic rows and do not delete existing Arabic rows in this phase.
- Lesson add/edit forms now include required or primary `english_title`, optional `english_summary`, optional RTL `arabic_title`, and optional RTL `arabic_summary`. Canonical `lesson.title` and `lesson.summary` sync from English. English lesson translations are upserted, and Arabic lesson translations are upserted only when at least one Arabic translatable field is non-empty. Blank Arabic fields do not create empty Arabic rows and do not delete existing Arabic rows in this phase.
- Text lesson add/edit partials now include `english_text_content` and optional RTL `arabic_text_content`. Canonical text lesson body/content syncs from English. Arabic text content is stored only in `youngo_lesson_translations`. Non-text lessons are not forced to provide text content, and media/video/PDF/file/shared fields remain shared.
- `Crud_model::sync_legacy_lesson_post_fields()` copies English bilingual lesson fields into legacy POST keys such as `title`, `summary`, and `text_description` before legacy handlers run. This protects Academy Cloud/video/media behavior from blank titles after the bilingual field rename.
- `Youngo_translation_model` is used for `youngo_section_translations` and `youngo_lesson_translations`; translation-table language codes remain only `english` and `arabic`.
- Phase 2U.5.5 runtime QA used backup `D:\Work\YounGo\backups\youngo_school_before_phase_2u5_5_section_lesson_bilingual_qa_2026_07_18_194034.sql`. A temporary Course Manager role plus legacy course/category permissions were used only under backup and removed by restore. QA verified bilingual section add/edit, bilingual text lesson add/edit, English-only section and text lesson no-Arabic-row behavior, minimal YouTube lesson media/title compatibility, canonical English sync, English upserts, conditional Arabic upserts, no duplicate translation rows, clean Arabic UTF-8, and protected rows clean.
- After Phase 2U.5.5 restore, temporary sections/lessons/translations were removed and pre/post counts matched: course `8`, section `18`, lesson `36`, section translations total `18`, section English `18`, section Arabic `0`, lesson translations total `36`, lesson English `36`, lesson Arabic `0`, `youngo_user_roles = 0`, `permissions = 2`, `enrol = 1`, and payment/watch/progress/entitlement/checkout/coupon protected rows `0`.
- The Phase 2U.5.5 diagnostic is `scripts/phase_2/youngo_phase_2u5_section_lesson_bilingual_forms_diagnostic.php`. It checks expected files and fields, Arabic RTL markers, shared media/type/course/section fields, `Youngo_translation_model` save-path usage, English upserts, conditional Arabic upserts, translation table state and English coverage, Arabic section/lesson rows staying `0` after restore, protected rows, and role/course/category/translation diagnostic compatibility.
- Runtime QA found the temporary admin lacked the legacy `course` permission. That was adjusted only inside QA and restored afterward. Phase 2V.0 audited the two permission systems, and Phase 2V.1 Role Assignment Management is implemented, runtime-QA tested, reviewed, and committed in commit `21aecd7` (`Add YounGo role assignment management`).
- Phase 2V.1 added `/admin/youngo/role-assignments` and `/admin/youngo/role-assignments/update` under YounGo -> Role Assignments, guarded by `manage_roles`. The page lists users with Root Admin, Admin, Content, Course, and Instructor toggles.
- The approved authority model is: Root Admin = developer/system owner/highest authority; Admin = client/operational owner; Content/Course/Instructor = scoped operational roles. Root Admin remains protected from everyone else. Admin can manage roles for non-root users but cannot modify Root Admin. Content/Course/Instructor cannot manage roles. Admin is mutually exclusive with Content/Course/Instructor. Content, Course, and Instructor can combine; Content + Course is the practical Content & Course Manager state.
- Phase 2V.1 bridges legacy `users.role_id`, `users.is_instructor`, `permissions.permissions`, and `check_permission()` with YounGo `youngo_roles`, `youngo_capabilities`, `youngo_role_capabilities`, `youngo_user_roles`, and the capability helper. This bridge is intentional because legacy course/category pages still depend on `check_permission('course')` and `check_permission('category')`. Non-root role updates keep a permissions row to avoid the legacy no-row full-access behavior.
- Phase 2V.1 includes reversible SQL seed files `database/phase_2/youngo_phase_2v1_admin_manage_roles_up.sql` and `database/phase_2/youngo_phase_2v1_admin_manage_roles_down.sql`. The up script maps only YounGo `admin` to `manage_roles`; scoped roles do not receive `manage_roles`. The down script removes only `admin -> manage_roles` and does not delete role/capability rows or unrelated mappings.
- Phase 2V.1 runtime QA used a temporary admin account, not Root Admin. Backup before QA was `D:\Work\YounGo\backups\youngo_school_before_phase_2v1_admin_manage_roles_alignment_2026_07_17_061234.sql`. QA verified Admin access to Role Assignments, non-root role updates, Root Admin read-only UI, `protected_root_admin` rejection for tampered Root Admin update, Course-only denial from Role Assignments, scoped roles without `manage_roles`, Admin mutual exclusion, restored temporary QA assignments, reapplied required `admin -> manage_roles` mapping, and clean protected payment/checkout/entitlement/manual-grant/coupon rows.
- The Phase 2V.1 diagnostic is `scripts/phase_2/youngo_phase_2v_role_assignment_diagnostic.php`. It checks files/routes/guards, Root Admin protection markers, Admin mutual exclusion, `admin -> manage_roles`, scoped roles without `manage_roles`, SQL seed files, legacy bridge markers, and payment/checkout boundaries.
- Phase 2U.6.2 Frontend Language Context and URL Mapping Helpers is implemented, focused-reviewed, committed, and diagnostics-tested in commit `25e0544` (`Add YounGo frontend language context helpers`). It added `application/helpers/youngo_frontend_language_helper.php` and `scripts/phase_2/youngo_phase_2u6_frontend_language_context_diagnostic.php`. The helper normalizes frontend language aliases to `english`/`arabic` only, treats `arabic_translated` as an input alias to `arabic` only, detects `ar`-prefixed frontend URIs, maps equivalent English and Arabic URLs while preserving query strings, excludes admin/payment/checkout/cart/coupon/write/API/cron paths, and provides future html lang/dir helpers. It is not autoloaded and did not add routes, frontend rendering, content translation shaping, session/cookie/settings writes, or checkout/payment changes.
- Phase 2U.6.3 Arabic Public Route Aliases is implemented, focused-reviewed, diagnostics-tested, committed, and pushed in commit `4b773b2` (`Add YounGo Arabic public route aliases`). English canonical frontend URLs remain unprefixed. Arabic canonical public frontend URLs use `/ar/...`, and `/en` must not become canonical. No `/en` routes were added and no English routes were redirected to `/en`. Admin/dashboard and checkout/payment/cart/coupon/write/API/cron routes remain unlocalized. `arabic_translated` is not a route language, UI language, or translation-table language; course `language_made_in` remains separate course-content metadata.
- Arabic public aliases added in Phase 2U.6.3 are `/ar -> home/index`, `/ar/courses -> home/courses`, `/ar/courses/{page} -> home/courses`, `/ar/course/{slug}/{id} -> home/course/$1/$2`, `/ar/search -> home/search`, `/ar/search/{query} -> home/search/$1`, `/ar/my-courses -> home/my_courses`, `/ar/my-access -> home/my_access`, `/ar/wishlist -> home/my_wishlist`, `/ar/login -> login/index`, and `/ar/sign-up -> sign_up/index`. English unprefixed routes remain unchanged. Course detail preserves slug then id argument order, search preserves the query argument, and course pagination remains compatible with `Home::courses()` using URI segment 3. `/ar/login` and `/ar/sign-up` target the existing public auth controllers.
- Phase 2U.6.3 did not add aliases for admin, addons, api, cron, `home/payment`, `home/paypal`, `home/stripe`, `home/paymob`, `home/razorpay`, `home/paystack`, `home/flutterwave`, `home/course_payment`, `home/shopping_cart`, `home/update_cart`, `home/apply_coupon`, `home/remove_coupon`, `home/checkout`, `home/confirm_payment`, `home/webhook`, coupon write routes, cart write routes, or payment callback routes. Lesson/player/PDF aliases remain deferred, including `Home::lesson`, `Home::pdf_canvas`, `Home::play_lesson`, mobile lesson helpers, and offline video helpers, because those are gated playback/progress/session surfaces that need separate staged QA before Arabic aliases are added.
- Phase 2U.6.3 did not implement frontend translated content rendering, frontend RTL shell rendering, a language switcher UI, course/category/section/lesson translation shaping, session language writes, cookie writes, settings writes, phrase writes, checkout/payment/cart/coupon localization, or admin/dashboard localization. Arabic route aliases may still render existing canonical frontend content until later translation-aware rendering phases.
 - The Phase 2U.6.3 diagnostic is `scripts/phase_2/youngo_phase_2u6_arabic_route_alias_diagnostic.php`. It checks `/ar` public aliases, preserved English routes, no `/en` routes, no admin/payment/checkout/cart/coupon/API/cron Arabic aliases, argument mapping, route order, Phase 2U.6.2 helper compatibility, no `arabic_translated` route/UI language usage, no frontend rendering/content translation changes, and Phase 2S/2P/2R compatibility. Focused review also tightened existing diagnostics so `routes.php` changes are tolerated only when the diff is Arabic-alias-only. PHP lint and compatibility diagnostics passed. HTTP GET smoke was intentionally skipped; static route diagnostics were used instead.
 - Phase 2U.6.4 Translation-aware Frontend Content Shaping is implemented, focused-reviewed, runtime-QA tested, restored, committed, and pushed in commit `12fcdae` (`Add YounGo frontend content translation shaping`). It added `application/helpers/youngo_frontend_content_helper.php` and `scripts/phase_2/youngo_phase_2u6_frontend_content_translation_diagnostic.php`, and updated `Home.php`, `common_helper.php`, YounGo course listing/detail/wishlist views, and Phase 2U.6 diagnostics.
 - The Phase 2U.6.4 helper uses `Youngo_translation_model` and the Phase 2U.6.2 frontend language context helper to shape home, courses, search, course detail, my courses, my access, and wishlist initial page data. It overlays only whitelisted display fields, preserves canonical IDs and operational fields, stores original canonical values in safe `youngo_canonical_*` metadata, never emits/stores `arabic_translated`, and does not write DB/session/cookie/settings or call unknown `get_phrase()` keys.
 - Fallback order is Arabic route = Arabic translation -> English translation -> canonical LMS field, and English route = English translation -> canonical LMS field. Course shaped fields are `title`, `short_description`, `description`, `outcomes`, `requirements`, `faqs`, `seo_title`, `meta_keywords`, and `meta_description`. Category display name can be shaped while canonical category ID/slug remain filter and URL identity. Section title and lesson title/summary can be shaped outside deferred player/PDF routes, with helper support for text lesson body on non-player display paths. IDs, slugs/link identity, price, discount, currency, access modes, media, instructor, level, progress, entitlement/access fields, CTA state, wishlist state, checkout/payment fields, lesson type, duration, attachment, video URL, and player/PDF routes remain unchanged.
 - Phase 2U.6.4 runtime QA used backup `D:\Work\YounGo\backups\youngo_school_before_phase_2u6_4_frontend_content_translation_qa_2026_07_18_215217.sql`. Temporary Arabic rows were inserted only into YounGo translation tables for course `1`, category `7`, section `1`, and lesson `1`; no canonical rows were changed. Restore removed temporary rows and pre/post counts matched exactly, including course `8`, category `12`, section `18`, lesson `36`, course translations total `9` / English `8` / Arabic `1`, category Arabic `0`, section Arabic `0`, lesson Arabic `0`, `ci_sessions = 636`, `youngo_user_roles = 0`, `permissions = 2`, `enrol = 1`, and protected payment/watch/progress/entitlement/checkout/coupon rows `0`.
 - QA confirmed `/`, `/home/courses`, and `/home/course/scratch-coding-for-young-creators/1` remain English/canonical; `/ar`, `/ar/courses`, and `/ar/course/scratch-coding-for-young-creators/1` display Arabic shaped values where translations exist and fall back safely; search Arabic aliases resolve; `/ar/wishlist`, `/ar/my-courses`, and `/ar/my-access` resolve without fatal/404 in unauthenticated safe GET checks; and excluded `/ar/admin`, `/ar/home/payment`, `/ar/home/checkout`, `/ar/home/shopping_cart`, `/ar/home/apply_coupon`, `/ar/api`, and `/ar/cron` render the app 404 page. Current working English routes for course/list/search/detail surfaces remain `/home/...`; direct `/courses`, `/course/...`, `/search`, and `/wishlist` are not current English routes.
 - The Phase 2U.6.4 diagnostic is `scripts/phase_2/youngo_phase_2u6_frontend_content_translation_diagnostic.php`. It checks helper functions, `Home.php` integration, `/ar` aliases, no `/en` routes, no new route changes, no payment/checkout/cart/coupon localization, no `arabic_translated` table-language usage, canonical IDs/access/media/progress preservation, read-only sample shaping, and route/language/section-lesson/role/course-category/translation/access diagnostic compatibility.
 - Phase 2U.6.5 Language Switcher and RTL Shell Rendering is implemented, focused-reviewed, runtime-QA tested, restored, committed, and pushed in commit `8321e91` (`Add YounGo language switcher and RTL shell`). It updated `application/helpers/youngo_frontend_language_helper.php`, `application/views/frontend/youngo/header.php`, `application/views/frontend/youngo/index.php`, `assets/frontend/youngo/css/youngo.css`, the Phase 2U.6 diagnostics, and added `scripts/phase_2/youngo_phase_2u6_language_switcher_rtl_diagnostic.php`.
 - The YounGo frontend shell now derives language from route context. English routes render `html lang="en" dir="ltr"` and Arabic `/ar` routes render `html lang="ar" dir="rtl"`. The frontend body adds escaped `youngo-lang-english` / `youngo-lang-arabic`, `youngo-dir-ltr` / `youngo-dir-rtl`, `data-youngo-language`, and `data-youngo-dir` metadata while preserving existing body classes. Admin/backend shell rendering is unaffected.
 - The YounGo frontend header now includes an `EN | عربي` switcher with active state and `aria-current`. URLs are helper-generated, preserve query strings, map working English `/home/...` route reality to Arabic aliases such as `/home/courses <-> /ar/courses` and `/home/course/{slug}/{id} <-> /ar/course/{slug}/{id}`, never generate `/en`, and never show/link `arabic_translated`.
 - Focused review fixed a subdirectory URL risk by making `youngo_frontend_current_uri_string_with_query()` prefer CodeIgniter `$CI->uri->uri_string()` over raw `REQUEST_URI`, with `REQUEST_URI` as fallback only.
 - Minimal YounGo-scoped RTL CSS was added in `assets/frontend/youngo/css/youngo.css` for header/nav shell behavior; the language switcher remains readable/LTR and full RTL visual polish was not attempted.
 - Phase 2U.6.5 runtime QA used backup `D:\Work\YounGo\backups\youngo_school_before_phase_2u6_5_language_switcher_rtl_qa_2026_07_18_224356.sql`. No temporary Arabic rows were needed. QA confirmed `/`, `/ar`, `/home/courses`, `/ar/courses`, `/home/courses?page=2`, `/ar/courses?page=2`, `/home/course/scratch-coding-for-young-creators/1`, `/ar/course/scratch-coding-for-young-creators/1`, `/home/search?query=Scratch`, `/ar/search?query=Scratch`, `/ar/login`, `/ar/sign-up`, and `/ar/wishlist` resolve safely with expected shell/switcher behavior where applicable. `/ar/my-courses` and `/ar/my-access` keep normal unauthenticated refresh redirects. Excluded `/ar/admin`, `/ar/home/payment`, `/ar/home/checkout`, `/ar/home/shopping_cart`, `/ar/home/apply_coupon`, `/ar/api`, and `/ar/cron` render the app 404 page. Restore succeeded and pre/post counts matched exactly, including `ci_sessions = 636`, course `8`, category `12`, section `18`, lesson `36`, course translations total `9` / English `8` / Arabic `1`, category Arabic `0`, section Arabic `0`, lesson Arabic `0`, `enrol = 1`, and protected YounGo entitlement/payment/checkout/coupon/progress tables `0`.
 - The Phase 2U.6.5 diagnostic is `scripts/phase_2/youngo_phase_2u6_language_switcher_rtl_diagnostic.php`. It checks shell html lang/dir helper usage, English `en/ltr` and Arabic `ar/rtl` mapping, body metadata, switcher existence, helper-generated URLs, query preservation, no `/en` links/routes, no route additions, no session/cookie/settings language writes, no DB/phrase writes, no checkout/payment/cart/coupon localization, admin shell boundaries, and compatibility with content translation and Arabic route diagnostics.
 - Known limitations after Phase 2U.6.5: runtime QA used HTTP/HTML response inspection rather than pixel-level browser screenshot QA; My Courses/Wishlist AJAX language propagation remains deferred; phrase polish remains deferred; full RTL visual polish remains deferred; translation shaping still uses per-row fallback reads that may need batching later; hreflang/canonical SEO remains deferred; lesson/player/PDF Arabic aliases remain deferred; and checkout/payment/cart/coupon localization remains deferred.
- PDF-specific browser QA remains a limitation; broader API/mobile entitlement UX remains future work.
- Checkout/order issuance, coupon-based access issuance, Paymob/payment integration, and production subscription purchasing remain not implemented.
- Frontend phrase conversion/polish, My Courses/Wishlist AJAX language propagation, full RTL visual polish, performance batching for translation reads, hreflang/canonical SEO, lesson/player/PDF Arabic aliases, Arabic phrase polish QA, mobile/API entitlement alignment, course data rebuild, and any broader role-permission UX polish beyond Phase 2V.1 remain future work.
- Run Phase 2U.6 frontend content translation, Arabic route alias, frontend language context, Phase 2U.5 section/lesson bilingual forms, Phase 2U.5 course bilingual forms, Phase 2U.5 category bilingual forms, Phase 2U.5 translation model, Phase 2U.4, Phase 2U.3, Phase 2J, Phase 2L, Phase 2M, Phase 2P, Phase 2R, and Phase 2S diagnostics before localization model/phrase/schema/form/route/rendering, access, grant, subscription, entitlement write, learner-access visibility, admin entitlement summary, or CTA-boundary work.
- Current recommended roadmap is Phase 2U.6.6 frontend phrase conversion/inventory, Phase 2U.6.7 controlled frontend localization QA and diagnostics, Phase 2U.7 bilingual QA plus phrase polish QA, Phase 2T course data rebuild after bilingual infrastructure is ready, then checkout/Paymob/subscription purchase flow. Phase 2V.2 role-assignment docs/UX polish may be handled only if needed and is not blocking localization.

---

## 5. Current Roadmap / Divisions

The division list below is historical. Use [`youngo_master_plan_v2.md`](./youngo_master_plan_v2.md) for the current execution order and deployment-readiness priorities.

The YounGo workflow is currently organized into the following divisions:

### Division 1 — Design Lock with Stitch

Status: completed.

Purpose:

- Lock the approved visual direction.
- Confirm the public homepage direction.
- Confirm course details visual direction.
- Confirm admin homepage content manager visual direction.
- Establish the soft purple, premium, kids-learning style.

### Division 2 — Reference and Agent Docs

Status: mostly completed.

Purpose:

- Make the repository understandable for Codex, Antigravity, and future agents.
- Preserve project context, agent rules, design direction, reference material, and initial planning.

### Division 3 — CLI and MCP Readiness

Status: next / parallel.

Purpose:

- Confirm Codex CLI readiness.
- Confirm Antigravity readiness.
- Register Stitch MCP if needed.
- Prepare Playwright MCP for browser testing.

### Division 4 — Agent Analysis and Planning

Status: started.

Purpose:

- Analyze the existing CodeIgniter CMS/LMS safely.
- Produce grounded planning before implementation.
- Keep planning separate from implementation.

### Division 5 — Phase 1 Implementation

Status: not started.

Purpose:

- Create the side-by-side YounGo frontend/theme.
- Create local YounGo assets.
- Implement homepage visual direction.
- Implement course listing/details styling.
- Add the minimum CMS homepage content-management foundation.

### Division 6 — CMS Content Management Expansion

Status: later.

Purpose:

- Expand beyond the minimum homepage manager.
- Improve content, menu, SEO, and admin editing flows.
- Keep admin workflows content-focused rather than style-focused.

### Division 7 — Playwright Testing and Polish

Status: later.

Purpose:

- Check desktop and mobile layouts.
- Check navigation.
- Check course listing and course details.
- Check admin content manager behavior.
- Check CMS edit-to-frontend update behavior.
- Polish visible issues after implementation.

---

## 6. Relationship to Other Documentation

Planning documents must stay focused on delivery and implementation planning.

Use each documentation area for its intended purpose:

```text
YOUNGO_PROJECT_CONTEXT.md
= high-level project context only

AGENTS.md
= short root-level agent instructions only

docs/design/
= visual identity, style, UI/UX direction only

docs/reference/
= old CMS documentation and Stitch outputs only

docs/agents/
= agent behavior rules and prompts only

docs/planning/
= phase plans, storage/content shapes, implementation sequence, and acceptance criteria
```

Do not mix these purposes.

For example:

- Do not put a full design system inside `docs/planning/`.
- Do not put database/storage planning inside `docs/design/`.
- Do not put agent prompts inside `YOUNGO_PROJECT_CONTEXT.md`.
- Do not treat `docs/reference/` as an active implementation plan.
- Do not put production code inside planning documents.

---

## 7. Current Approved Planning Document

The current approved master planning document is:

```text
docs/planning/youngo_master_plan_v2.md
```

This document should be treated as the approved project source of truth.

`phase_1.md` is now an earlier Phase 1 planning reference. It documents the original implementation foundation for:

- YounGo frontend/theme foundation
- Homepage visual implementation
- Minimum CMS homepage content-management foundation
- Course listing/details styling
- Browser and Playwright checks

Do not use `phase_1.md` to override the master plan. Do not rewrite or replace historical planning files unless explicitly approved.

---

## 8. Supporting Planning Documents

The homepage content planning-support document is:

```text
docs/planning/youngo_homepage_content_schema.md
```

This file defines the earlier planned homepage content/storage shape for the CMS homepage manager. Treat it as a supporting reference under the master plan.

It should cover:

- Homepage section keys
- Content fields
- Image fields
- CTA fields
- Visibility fields
- Sort order
- Active/published state
- Safe defaults
- Fallback behavior
- Relationship to existing LMS/CMS data such as courses, categories, blogs, FAQs, and contact information

This document should not contain SQL, PHP, controller code, model code, view code, CSS, or JavaScript.

The Phase 2 hybrid access, subscriptions, and roles architecture document is:

```text
docs/planning/youngo_phase_2_hybrid_access_subscriptions_roles_architecture.md
```

It records owner decisions and architecture direction for:

- Hybrid access / entitlement layer.
- Subscription plans and user subscriptions.
- Course access modes.
- Coupons and discounts.
- Manual grants.
- Direct checkout strategy.
- Paymob payment strategy.
- Multi-role/capability model.
- Instructor assignment rules.

It is the architecture reference for the Phase 2 feature set. Some later Phase 2 pieces have now been implemented locally, but this document itself is still not proof of implementation. Check the master plan current status and diagnostics before assuming any feature is ready.

The Phase 2B database schema and migration plan is:

```text
docs/planning/youngo_phase_2_database_schema_migration_plan.md
```

It records the approved additive database planning direction for subscriptions, user subscriptions, course access records, manual grants, coupon scope, direct checkout orders, and multi-role/capability tables. It also records the local `youngo_school` verification summary. It does not authorize or implement SQL, migrations, source-code changes, database changes, or production payment behavior.

The Phase 2C entitlement/access compatibility plan is:

```text
docs/planning/youngo_phase_2_entitlement_access_compatibility_plan.md
```

It records the approved access-layer planning direction for `can_user_access_course(user_id, course_id)`, structured access state, access precedence, My Courses compatibility, lesson player compatibility, and future subscription/manual-grant/checkout readiness. It does not authorize or implement source-code changes, database changes, entitlement helpers, subscriptions, manual grants, direct checkout, or Paymob behavior.

The Phase 2D multi-role/capability compatibility plan is:

```text
docs/planning/youngo_phase_2_multi_role_capability_compatibility_plan.md
```

It records the approved role/capability planning direction for adding YounGo roles beside existing Academy LMS permissions, protecting Root Admin, preserving restricted admin behavior, keeping learner capability implicit, and keeping instructor assignment compatible with existing course fields. It does not authorize or implement source-code changes, database changes, role/capability helpers, role toggles, migrations, or permission behavior changes.

The Phase 2E schema migration execution plan is:

```text
docs/planning/youngo_phase_2_schema_migration_execution_plan.md
```

It records the approved migration execution planning direction for reviewable SQL artifacts, local-only preflight/apply sequencing, idempotency, rollback, seed safety, Root Admin protection, client-admin protection, and server deployment safeguards. The local Phase 2E base schema and Phase 2L subscription plan archive/audit schema are now applied locally, but server migration still requires separate approval.

Current Subscription Plan Management status:

```text
Phase 2L Subscription Plan Management is implemented and QA-tested locally.
Routes exist under /admin/youngo/subscription-plans.
Access uses the YounGo manage_subscriptions capability.
The local Phase 2L archive/audit schema is applied.
The diagnostic script is scripts/phase_2/youngo_phase_2l_subscription_plan_diagnostic.php.
Subscription Plan Management does not implement checkout, payment, Paymob, coupons, manual grants, subscription issuance, enrolment, or access writes.
```

Current Shared Entitlement Write Service status:

```text
Phase 2M Shared Entitlement Write Service foundation is implemented, schema-applied, committed, and controlled service-QA tested locally.
The service file is application/models/Youngo_entitlement_write_model.php.
The diagnostic script is scripts/phase_2/youngo_phase_2m_entitlement_write_diagnostic.php.
The local Phase 2M schema adds revoked_by_user_id and revoke_note to youngo_course_access and youngo_user_subscriptions, plus indexes on checkout_order_id / revoked_by_user_id as applicable. No hard foreign keys were added.
The service supports manual course grant, manual subscription grant, course access revocation, subscription revocation, duplicate active entitlement prevention, linked manual grant revocation, transactions/rollback, and safe non-writing checkout issuance stubs.
Controlled service QA passed and temporary QA rows were removed by DB restore.
Current local post-cleanup state: youngo_course_access = 0, youngo_user_subscriptions = 0, youngo_manual_grants = 0, youngo_checkout_orders = 0, youngo_coupon_usages = 0, payment = 0, enrol = 1, watch_histories = 0, watched_duration = 0.
Phase 2N Manual Grants dashboard/UI is implemented, committed, and authenticated UI-QA tested locally.
The dashboard routes are under /admin/youngo/manual-grants and the YounGo navigation item is gated by grant_manual_access.
The UI supports list/filter, create manual course grant, create manual subscription grant, detail view, course grant revocation, and subscription grant revocation.
All writes use Youngo_entitlement_write_model; revocation is POST-only and there is no delete path.
Authenticated QA verified Root Admin login, navigation/list/create pages, grant/revoke flows, duplicate rejection, read-layer recognition/denial, cleanup restore from D:\Work\YounGo\backups\youngo_school (14).sql, and final diagnostics.
Phase 2O learner-facing course-card/detail/lesson/PDF/review alignment is implemented, committed, and controlled browser-QA tested locally.
Phase 2P My Courses / My Access learner visibility is implemented, committed, and controlled-QA tested locally.
The Phase 2P files include read-only learner access methods in Youngo_entitlement_model, Home::my_access(), a My Access page, a reload My Courses view, a My Access profile-menu item, and scripts/phase_2/youngo_phase_2p_learner_access_visibility_diagnostic.php.
My Courses includes active legacy enrolments and active direct YounGo/manual course access rows through prepared learner access items. It intentionally does not auto-list every subscription-eligible course just because a subscription is active.
My Access is visibility-only and links to course browsing only; it does not implement checkout, renewal, payment, or coupons.
Phase 2P QA used temporary manual grants through the Manual Grants UI and restored from D:\Work\YounGo\backups\youngo_school (16).sql; final diagnostics passed and entitlement tables are empty again.
Phase 2Q curated QA learner baseline setup is completed locally.
Pre-setup backup was D:\Work\YounGo\backups\youngo_school (17).sql.
The curated post-setup backup is D:\Work\YounGo\backups\youngo_school_qa_baseline_after_phase_2q2.sql.
Dedicated QA learner user 8 exists as qa.learner@youngo.local, name YounGo QA Learner, role_id 2, active, and is_instructor 0. Do not store or print the learner password; the owner must provide it when needed.
Phase 2Q preserved Root Admin, users 2/5/6/7, existing user 2/course 6 enrolment, subscription plans, settings, and empty entitlement tables. No checkout/payment/order/coupon/enrol/progress rows were created.
Phase 2Q.3 learner-authenticated browser QA is completed locally using QA learner user 8 and the curated baseline backup.
Phase 2Q.3 passed for baseline My Courses/My Access no-access states, course 1 no-access state, course 9 checkout-not-ready state, manual course grant visibility/revocation, manual subscription visibility/revocation, duplicate rejection, course listing/detail access states, review-area visibility without submission, GET-only cart boundary, and restore cleanup.
The curated baseline backup was restored after QA; entitlement tables are empty and temporary lesson watch history was removed. PDF-specific browser QA remains a limitation.
After QA cleanup, entitlement write tables are empty locally: youngo_course_access = 0, youngo_user_subscriptions = 0, youngo_manual_grants = 0, and youngo_checkout_orders = 0.
Checkout/order issuance remains stubbed and not implemented.
Paymob/payment/coupons remain later phases.
```

Current currency/readiness status:

```text
YounGo commercial currency is EGP.
settings.system_currency is currently EGP.
Seeded Monthly, 3 Months, and Yearly subscription placeholders now store EGP and remain inactive/non-purchasable.
Their current local prices are temporary placeholders only: Monthly 100.00 EGP, 3 Months 250.00 EGP, and Yearly 900.00 EGP.
The Phase 2L diagnostic now reports zero non-EGP subscription plans, so currency readiness is clean for subscription plan definitions.
Subscriptions are not production-ready: final prices require owner approval, and checkout/payment/subscription issuance are not implemented.
Gateway currencies remain mixed and are deferred to the payment/Paymob phase.
```

---

## 9. Rules Before Implementation

Before implementation starts:

- Read the approved planning document for the relevant phase.
- Confirm the work is inside the approved phase scope.
- Confirm that implementation has been explicitly approved.
- Do not assume Laravel.
- Do not use Laravel commands or concepts.
- Do not overwrite the existing `default-new` theme.
- Do not replace the existing CMS dashboard.
- Do not rewrite course, cart, wishlist, enrollment, authentication, lesson, or player behavior.
- Do not modify access, subscriptions, coupons, manual grants, checkout, payments, roles, permissions, or instructor assignment without reading the Phase 2 architecture document.
- Do not create or apply Phase 2 database schema changes without reading the Phase 2B database schema and migration plan.
- Do not create Phase 2 migration artifacts or execute Phase 2 schema changes without reading the Phase 2E schema migration execution plan.
- Do not modify course or lesson access decisions without reading the Phase 2C entitlement/access compatibility plan.
- Do not modify role, permission, admin, instructor, or account-role behavior without reading the Phase 2D multi-role/capability compatibility plan.
- Do not remove course purchase, cart logic, enrolment logic, payment logic, or existing role/instructor behavior while adding Phase 2 features.
- Do not treat coupon/discount as direct access.
- Do not treat public free-course classification as the target YounGo business model.
- Do not create database changes without approval.
- Do not create SQL before schema/storage review.
- Do not paste Stitch HTML directly into production.
- Do not create, activate, or make real commercial subscription plans purchasable until final prices are approved and the owner explicitly approves activation.
- Do not correct real or seeded subscription plan currency through manual row-level SQL unless explicitly approved; use the Subscription Plans dashboard flow after backup.
- Do not proceed to checkout/Paymob until `scripts/phase_2/youngo_phase_2l_subscription_plan_diagnostic.php` reports subscription currency readiness clean and the remaining order/access issuance phases are approved.
- Do not create real manual grants, subscriptions, or course access rows through manual SQL; Manual Grants UI and future grant/access flows must use `Youngo_entitlement_write_model`.
- Manual Grants UI enforces `grant_manual_access`; future grant/access controllers must do the same before calling the write service.
- Learner access/account visibility must use centralized read methods in `Youngo_entitlement_model`.
- My Access must remain visibility-only until checkout/order/payment/coupon phases are approved.
- Do not treat the checkout issuance stubs as implemented checkout/order completion.
- Do not print, store, commit, or reuse the QA learner password; use the owner-provided credential only through normal login flows.
- Before grant, entitlement write, or learner access visibility work, run Phase 2J, Phase 2L, Phase 2M, and Phase 2P diagnostics.
- Do not switch the active production theme before preview/review approval.
- Keep CMS controls focused on content, not visual styling.

---

## 10. Final Reminder

Planning documents decide what should be built and how the work should be approached.

They do not automatically authorize implementation.

Implementation still requires explicit approval before creating or editing production code, database structures, assets, controllers, models, views, CSS, JavaScript, or SQL.
