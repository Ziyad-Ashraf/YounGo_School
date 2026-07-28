# YounGo Phase 2C Entitlement / Access Compatibility Layer Plan

## 1. Executive Summary

This document records the approved Phase 2C plan for a YounGo entitlement/access compatibility layer and the current local implementation status through Phase 2U.6.5.

The original Phase 2C content is a planning baseline. The current local implementation now includes the approved read-only entitlement helper/model, presentation-only frontend integrations, controlled web hard gates, Manual Grants dashboard/UI, learner-facing entitlement visibility/enforcement alignment, My Courses / My Access learner account visibility, read-only admin/user entitlement summary visibility, YounGo CTA boundary protection, the Phase 2U.3 localization translation schema foundation, Phase 2U.4 canonical Arabic UI phrase support, Phase 2U.5.2 translation model/helper foundation, Phase 2U.5.3 category/subcategory bilingual form support, Phase 2U.5.4 course add/edit bilingual form support, Phase 2U.5.5 section/lesson bilingual form support, Phase 2U.6.2 frontend language context helpers, Phase 2U.6.3 Arabic public route aliases, Phase 2U.6.4 translation-aware frontend content shaping, Phase 2U.6.5 language switcher/RTL shell rendering, and Phase 2V.1 role assignment management recorded below. It does not implement subscription checkout, Paymob, real payment behavior, checkout/renewal/coupon actions from My Access, frontend phrase polish, full RTL visual polish, broader API/mobile entitlement UX, lesson/player/PDF Arabic aliases, course data rebuild, unrestricted Root Admin editing, broader role/capability system rewrite, or production/server migration.

Phase 2C should introduce a read-only-first YounGo compatibility layer that can answer:

```text
can_user_access_course(user_id, course_id)
```

The layer must preserve Academy LMS behavior. Existing course purchase, enrolment, lesson access, progress, cart/session, payment, invoice, coupon, user, instructor, and permission logic remain in place until a tested compatibility layer is implemented and explicitly approved.

Core direction:

- Add a YounGo entitlement compatibility layer beside existing Academy LMS access checks.
- Do not modify `enroll_status()` initially.
- Do not remove or replace legacy `enrol`, `payment`, cart, invoice, progress, instructor, or lesson access behavior.
- Return structured access state, not only `true` or `false`.
- Prepare the access contract for later subscriptions, manual grants, direct checkout, and Paymob phases without implementing those features in Phase 2C.

### Current Implementation Status

Phase 2F completed the first read-only, presentation-only entitlement integration locally:

- Read-only entitlement helper/model implemented:
  - `application/helpers/youngo_entitlement_helper.php`
  - `application/models/Youngo_entitlement_model.php`
- Read-only diagnostic script implemented and passed:
  - `scripts/phase_2/youngo_phase_2f2_entitlement_diagnostic.php`
- Presentation-only integrations completed:
  - Course Details page status/CTA display
  - My Courses access status display
  - Course Listing card access badge display

Phase 2G completed the first controlled web hard-gate integrations locally:

- `Home::lesson()` now uses the YounGo entitlement hard gate.
- `Files::index()` now uses the YounGo entitlement hard gate.
- `enroll_status()` remains unchanged.
- Existing valid legacy enrolment remains accepted through the entitlement/fallback path.
- Admin/root and assigned instructor access remain accepted.
- Active `youngo_course_access` and eligible active subscription access are accepted by the entitlement path.

Later Phase 2N/2O status:

- Phase 2N Manual Grants dashboard/UI is implemented and authenticated UI-QA tested locally. It creates/revokes manual course grants and manual subscription grants through `Youngo_entitlement_write_model` and enforces `grant_manual_access`.
- Phase 2O learner-facing entitlement visibility/enforcement alignment is implemented and controlled browser-QA tested locally.
- Course card CTA/status display, course detail CTA/status display, manual grant/subscription access labels, paid subscription-only checkout-not-ready messaging, `Home::play_lesson()`, `Home::pdf_canvas()`, `go_course_playing_page()`, `lesson_mobile_web_view_get()`, `offline_video_for_mobile_app()`, and course review visibility/submission gates are aligned with the YounGo entitlement read layer where scoped.
- Paid subscription-only courses without active access no longer expose legacy Buy Now/Add to cart as a shortcut while YounGo checkout is not implemented.
- Phase 2P My Courses / My Access learner visibility is implemented and controlled-QA tested locally.
- My Courses now uses prepared learner access items and lists active legacy enrolments plus active direct YounGo/manual course access rows.
- My Courses intentionally does not auto-list every subscription-eligible course merely because a subscription is active.
- My Access is visibility-only: it shows subscription/access summaries and links to course browsing only. It does not implement checkout, renewal, payment, coupons, or subscription purchase.
- Phase 2P added the read-only diagnostic `scripts/phase_2/youngo_phase_2p_learner_access_visibility_diagnostic.php`, and it passes locally.
- Phase 2Q curated QA learner baseline setup is completed locally.
- QA learner user 8 exists as `qa.learner@youngo.local`, name `YounGo QA Learner`, `role_id = 2`, active, and `is_instructor = 0`.
- The QA learner password must never be documented, printed, committed, stored, or reused from Root Admin. The project owner must provide it when learner-authenticated QA is needed.
- Curated baseline backups: pre-setup `D:\Work\YounGo\backups\youngo_school (17).sql`; post-setup `D:\Work\YounGo\backups\youngo_school_qa_baseline_after_phase_2q2.sql`.
- Current QA fixtures: user 8 clean no-access learner; user 2/course 6 legacy enrol compatibility; course 1 manual-grant / lesson-safe QA; course 9 paid subscription-only checkout-not-ready CTA; plan 1 Monthly EGP manual subscription QA.
- Phase 2Q.3 learner-authenticated browser QA is completed locally for implemented Phase 2O/2P learner surfaces using QA learner user 8.
- Phase 2Q.3 passed for baseline no-access My Courses, baseline no-subscription My Access, course 1 no-access state, course 9 checkout-not-ready state without legacy Buy Now/Add to cart, manual course grant visibility/revocation, manual subscription visibility/revocation, duplicate rejection, course listing/detail access states, normal learner lesson access with active manual grant, review-area visibility without submission, GET-only cart boundary, and restore cleanup.
- The curated baseline backup was restored after QA. Temporary rows before restore were `watch_histories = 1`, `youngo_course_access = 1`, `youngo_user_subscriptions = 1`, and `youngo_manual_grants = 2`. After restore, QA learner user 8 remained, entitlement tables were empty, watch/progress returned to baseline, and Phase 2J/2L/2M/2P diagnostics passed.
- Phase 2R read-only admin/user entitlement summaries are implemented and authenticated-QA tested locally.
- Phase 2R added admin user edit and course edit summary cards guarded by `grant_manual_access`, read-only admin summary methods in `Youngo_entitlement_model`, Manual Grants filtered links by `user_id`/`course_id`, and the read-only diagnostic `scripts/phase_2/youngo_phase_2r_admin_entitlement_summary_diagnostic.php`.
- Admin summaries separate legacy enrolments from active YounGo course access, do not treat subscription eligibility as enrolment, show active user subscription state where applicable, and keep Manual Grants as the only grant/revoke surface.
- Phase 2R QA verified authenticated Root Admin rendering, temporary course/subscription grant summary updates, duplicate rejection, revocation updates, filtered Manual Grants links, cart boundary, restore cleanup from the curated baseline backup, and final Phase 2J/2L/2M/2P/2R diagnostics.
- The Phase 2R QA-blocking fix made user edit rendering handle missing/empty social/payment key arrays safely without exposing sensitive values.
- Phase 2S CTA boundary fixes are implemented, reviewed, and manually validated locally.
- Phase 2S added a guard to `Home::get_enrolled_to_free_course()` so YounGo-managed modes `subscription_only`, `subscription_and_purchase`, and `purchase_only` cannot create legacy `enrol` rows through the legacy free-enrol route.
- Course detail, course cards, `my_wishlist.php`, and `wishlist_items.php` no longer expose legacy Enroll Now, Add to cart, or Buy Now CTAs for YounGo-managed no-access courses. Empty wishlist copy no longer references legacy free enrol/cart shortcuts.
- Phase 2S added the read-only diagnostic `scripts/phase_2/youngo_phase_2s_route_cta_boundary_diagnostic.php`, and it passes locally.
- Manual validation confirmed Course 1, Scratch Coding for Young Creators, shows access-managed messaging with no legacy CTAs, and direct `/home/get_enrolled_to_free_course/1` redirects safely to course detail without creating an enrol row for QA learner user 8. Global `enrol` stayed `1`, user 8 enrol rows stayed `0`, and entitlement/checkout/payment/coupon tables stayed empty.
- Manual validation confirmed Course 9 remains subscription checkout-not-ready with no Buy Now/Add to cart, and courses listing shows Course 1 access-managed and Course 9 subscription-not-ready.
- Phase 2U.3 localization schema foundation is implemented, reviewed, committed, and locally applied after backup `D:\Work\YounGo\backups\youngo_school_before_phase_2u3_localization_schema_2026_07_16.sql`.
- Phase 2U.3 added YounGo-specific translation tables `youngo_course_translations`, `youngo_category_translations`, `youngo_section_translations`, and `youngo_lesson_translations`. Canonical course/category/section/lesson rows remain stable and continue to be used by entitlement access, manual grants, subscriptions, future checkout/orders, lesson progress, and diagnostics.
- Duplicate courses per language are not the default localization model. Translation rows attach to canonical records.
- English rows were seeded idempotently from canonical content: course translations `7`, category translations `12`, section translations `18`, and lesson translations `36`. Canonical counts stayed course `7`, category `12`, section `18`, lesson `36`.
- No Arabic rows were created; no language phrase table, language setting, language_dirs, frontend rendering, dashboard form, course data, checkout/order/coupon/Paymob/payment, enrol/progress, or entitlement rows were changed by Phase 2U.3.
- The Phase 2U.3 diagnostic is `scripts/phase_2/youngo_phase_2u3_localization_schema_diagnostic.php` and passes locally.
- Phase 2U.4 canonical Arabic UI phrase support is implemented, reviewed, committed, and locally applied after backup `D:\Work\YounGo\backups\youngo_school_before_phase_2u4_arabic_phrase_support_2026_07_16.sql`.
- Phase 2U.4 added `language.arabic`, created `application/language/arabic.json`, and added `database/phase_2/youngo_phase_2u4_arabic_phrase_support_up.sql`, `database/phase_2/youngo_phase_2u4_arabic_phrase_support_down.sql`, `scripts/phase_2/youngo_phase_2u4_seed_arabic_phrases.php`, and `scripts/phase_2/youngo_phase_2u4_arabic_phrase_diagnostic.php`.
- The canonical Arabic language code is `arabic`; `arabic_translated` / `application/language/arabic_translated.json` are deprecated placeholders and are not source of truth for new localization work.
- English remains the default language, `settings.language_dirs` includes `arabic: rtl`, and Arabic UI phrase support exists at phrase-data level only.
- Arabic phrases were generated from current phrase keys and English values with simple Modern Standard Arabic wording. High-priority YounGo UI phrases have direct translations; many non-priority phrases use safe fallback wording and require phrase-polish QA before public Arabic launch.
- Phase 2U.4 did not create Arabic rows in `youngo_course_translations`, `youngo_category_translations`, `youngo_section_translations`, or `youngo_lesson_translations`; did not modify canonical course/category/section/lesson content; did not add frontend rendering, `/ar` routes, or dashboard bilingual forms; and did not touch checkout/order/coupon/Paymob/payment or entitlement rows.
- The Phase 2U.4 diagnostic is `scripts/phase_2/youngo_phase_2u4_arabic_phrase_diagnostic.php` and passes locally.
- Phase 2U.5.2 translation model/helper foundation is implemented, reviewed, committed, and locally diagnostic-tested.
- Phase 2U.5.2 added `application/models/Youngo_translation_model.php` and `scripts/phase_2/youngo_phase_2u5_translation_model_diagnostic.php`.
- `Youngo_translation_model` is the central helper/model for YounGo bilingual content. It works with Phase 2U.3 translation tables, preserves canonical course/category/section/lesson IDs, does not replace canonical LMS tables, and does not handle entitlement, subscription, manual grant, checkout, coupon, or payment logic.
- Canonical content language codes are `english` and `arabic`. Compatibility inputs map `en` to `english`, `ar` to `arabic`, and deprecated `arabic_translated` to `arabic` only as an input alias.
- The model supports direct translation reads, fallback reads, future dashboard upsert methods, slug generation/availability checks, `has_translation()`, and missing-translation summaries. Fallback order is requested language translation, English translation, canonical LMS table data, then `null`; fallback metadata includes `requested_language`, `resolved_language`, `is_fallback`, `missing_translation`, and `translation_source`.
- Phase 2U.5.2 did not change dashboard forms, frontend rendering, `/ar` routes, language phrases, settings, canonical course/category/section/lesson data, checkout/order/coupon/Paymob/payment, or entitlement rows. No Arabic content translation rows were created, and upsert methods were reviewed but not executed or wired to forms.
- The Phase 2U.5 diagnostic is `scripts/phase_2/youngo_phase_2u5_translation_model_diagnostic.php` and passes locally.
- Phase 2U.5.3 Category/Subcategory Bilingual Form Support is implemented, reviewed, committed, and runtime-QA tested locally.
- Phase 2U.5.3 updated `application/models/Crud_model.php`, category/subcategory add/edit views, and added `scripts/phase_2/youngo_phase_2u5_category_bilingual_forms_diagnostic.php`.
- Category and subcategory add/edit forms now collect `english_name`, `english_slug`, `arabic_name`, and `arabic_slug`. Arabic fields are optional and RTL. Shared category metadata, including code, parent/category selection, icon, thumbnail, and category image, remains canonical/shared outside bilingual fields.
- `Crud_model::add_category()` and `Crud_model::edit_category()` sync canonical `category.name` and `category.slug` from English fields, keep the legacy `name` fallback, upsert English category translations after create/update, and upsert Arabic category translations only when Arabic name or slug is non-empty. Empty Arabic rows are not created, and `arabic_translated` is not used.
- Phase 2U.5.3 runtime QA used backup `D:\Work\YounGo\backups\youngo_school_before_phase_2u5_3_category_bilingual_form_qa_2026_07_16_190255.sql`, verified temporary category/subcategory add/edit flows with English and Arabic values, verified an English-only category did not create an Arabic row, restored the DB, removed temporary QA data, and returned to category `12`, category English translations `12`, category Arabic translations `0`, and protected rows clean.
- Phase 2U.5.3 did not modify `Admin.php`, course forms, section forms, lesson forms, frontend rendering, `/ar` routes, language phrases, settings, checkout/order/coupon/Paymob/payment, or entitlement logic.
- The Phase 2U.5 category bilingual forms diagnostic is `scripts/phase_2/youngo_phase_2u5_category_bilingual_forms_diagnostic.php` and passes locally.
- Phase 2U.5.4 Course Add/Edit Bilingual Form Support and Course Form UX Alignment is implemented, runtime-QA tested, reviewed, and committed in commit `50ae714` (`Add YounGo course bilingual form support`).
- Phase 2U.5.4 updated `application/helpers/common_helper.php`, `application/models/Crud_model.php`, course add/edit/shortcut views, the Phase 2U.4/2U.5 diagnostics, and added `scripts/phase_2/youngo_phase_2u5_course_bilingual_forms_diagnostic.php`.
- Course add/edit forms now collect English and optional RTL Arabic course fields for `title`, `slug`, `short_description`, `description`, `outcomes`, `requirements`, `faqs`, `seo_title`, `meta_keywords`, and `meta_description`. English fields are canonical and sync to canonical `course` rows for Academy LMS compatibility. English course translation rows are upserted after canonical create/update. Arabic course translation rows are upserted only when Arabic content is non-empty, so English-only courses do not create Arabic rows.
- Course shortcut remains English-oriented: it creates canonical English course data and an English translation row, while Arabic completion is deferred to full course edit.
- Course `language_made_in` is course-content metadata only. Valid marker values are `english`, `arabic`, and `arabic_translated`; `arabic_translated` is valid only as course content/video/material metadata. Translation table language codes remain only `english` and `arabic`, and `arabic_translated` must not be stored in `youngo_course_translations.language_code`.
- Phase 2U.5.4 fixed a runtime-QA course edit HTTP 500 / partial blank page by loading `Youngo_translation_model` safely via the CodeIgniter instance, using English fallback values, safely defaulting Arabic arrays when Arabic translation is absent, and fixing malformed HTML in the reviewed course edit sections.
- Pricing/access UX now hides/disables one-time price and discount fields for `subscription_only` courses with helper copy; `purchase_only` and `subscription_and_purchase` keep price/discount fields available. `Crud_model::update_course()` preserves existing price/discount values when disabled price fields are not posted. Add/shortcut paths default absent price fields safely. No checkout/payment/Paymob/order/coupon behavior was implemented.
- EGP display was normalized in `common_helper.php` for readability while respecting configured currency position. Observed local settings were `system_currency = EGP` and `currency_position = left`, producing readable examples such as `EGP 500`. DB currency values, DB prices, checkout, payment, and Paymob logic were not changed.
- Phase 2U.5.4 runtime QA used a temporary admin account, not Root Admin. Backup before QA was `D:\Work\YounGo\backups\youngo_school_before_phase_2u5_4_temp_admin_runtime_qa_2026_07_17_002027.phpdbdump`. QA verified course edit/add rendering, bilingual fields, language marker options, subscription-only and purchase-mode pricing behavior, readable EGP display, temporary bilingual course add/edit, redirect to non-blank edit page, English-only no-Arabic-row behavior, and no duplicate translation rows.
- After restore from the Phase 2U.5.4 QA turn, temporary QA data from the final QA turn was removed. Existing manual course QA data from before that backup remained intentionally present: course `8`, youngo_course_translations total `9`, English rows `8`, Arabic rows `1`, category `12`, section `18`, lesson `36`, enrol `1`, and protected payment/watch/progress/entitlement/checkout/coupon rows `0`.
- Phase 2U.5.5 Section/Lesson Bilingual Form Support is implemented, focused-reviewed, runtime-QA tested, restored, and committed in commit `f3368fc` (`Add YounGo section lesson bilingual form support`).
- Section add/edit forms now support `english_title` and optional RTL `arabic_title`, with canonical `section.title` syncing from English. English section translations are upserted, Arabic section translations are conditional on non-empty Arabic title, empty Arabic rows are not created, and blank Arabic fields do not delete existing Arabic rows in this phase.
- Lesson add/edit forms now support `english_title`, optional `english_summary`, optional RTL `arabic_title`, optional RTL `arabic_summary`, and text lesson `english_text_content` / optional RTL `arabic_text_content`. Canonical `lesson.title`, `lesson.summary`, and text lesson body/content sync from English. Arabic lesson translations are conditional on non-empty Arabic content, and non-text media/shared fields remain shared.
- `Crud_model::sync_legacy_lesson_post_fields()` bridges English bilingual fields back to legacy POST keys such as `title`, `summary`, and `text_description` before legacy media handlers run, preserving Academy Cloud/video/media compatibility.
- Phase 2U.5.5 runtime QA used backup `D:\Work\YounGo\backups\youngo_school_before_phase_2u5_5_section_lesson_bilingual_qa_2026_07_18_194034.sql`, used temporary Course Manager/legacy course-category access under backup only, restored successfully, removed temporary role/permission setup and temporary section/lesson data, matched pre/post counts, and kept protected rows clean. The diagnostic is `scripts/phase_2/youngo_phase_2u5_section_lesson_bilingual_forms_diagnostic.php`.
- Runtime QA found the temporary admin lacked the legacy `course` permission. That was temporarily adjusted only inside QA and restored afterward. Phase 2V.0 audited the two permission systems, and Phase 2V.1 Role Assignment Management is implemented, runtime-QA tested, reviewed, and committed in commit `21aecd7` (`Add YounGo role assignment management`).
- Phase 2V.1 added `/admin/youngo/role-assignments` and `/admin/youngo/role-assignments/update` under YounGo -> Role Assignments, guarded by `manage_roles`, with Root Admin, Admin, Content, Course, and Instructor toggles.
- The approved authority model is Root Admin = developer/system owner/highest authority, Admin = client/operational owner, and Content/Course/Instructor = scoped operational roles. Root Admin remains protected from everyone else. Admin can manage roles for non-root users but cannot modify Root Admin. Content/Course/Instructor cannot manage roles unless explicitly granted `manage_roles`. Admin is mutually exclusive with Content/Course/Instructor, while Content, Course, and Instructor can combine; Content + Course is the practical Content & Course Manager state.
- Phase 2V.1 intentionally bridges legacy `users.role_id`, `users.is_instructor`, `permissions.permissions`, and `check_permission()` behavior with YounGo `youngo_roles`, `youngo_capabilities`, `youngo_role_capabilities`, `youngo_user_roles`, and the capability helper because legacy course/category pages still depend on `check_permission('course')` and `check_permission('category')`. Non-root role updates keep a permissions row to avoid the legacy no-permissions-row full-access behavior.
- The reversible seed files `database/phase_2/youngo_phase_2v1_admin_manage_roles_up.sql` and `database/phase_2/youngo_phase_2v1_admin_manage_roles_down.sql` map only YounGo `admin` to `manage_roles`; scoped roles do not receive `manage_roles`; down removes only `admin -> manage_roles` without deleting role/capability rows or unrelated mappings.
- Phase 2V.1 runtime QA used a temporary admin account, not Root Admin. Backup before QA was `D:\Work\YounGo\backups\youngo_school_before_phase_2v1_admin_manage_roles_alignment_2026_07_17_061234.sql`. QA verified Admin access to Role Assignments, non-root role updates, Root Admin read-only UI, `protected_root_admin` rejection for tampered Root Admin update, Course-only denial from Role Assignments, scoped roles without `manage_roles`, Admin mutual exclusion, restored temporary QA assignments, reapplied required `admin -> manage_roles`, and clean protected payment/checkout/entitlement/manual-grant/coupon rows.
- The Phase 2V.1 diagnostic is `scripts/phase_2/youngo_phase_2v_role_assignment_diagnostic.php`.
- Phase 2U.6.2 Frontend Language Context and URL Mapping Helpers is implemented, focused-reviewed, committed, and diagnostics-tested in commit `25e0544` (`Add YounGo frontend language context helpers`). It added `application/helpers/youngo_frontend_language_helper.php` and `scripts/phase_2/youngo_phase_2u6_frontend_language_context_diagnostic.php`. The helper normalizes frontend language aliases to `english`/`arabic` only, treats `arabic_translated` as an input alias to `arabic` only, detects `ar`-prefixed frontend URIs, maps equivalent English and Arabic URLs while preserving query strings, excludes admin/payment/checkout/cart/coupon/write/API/cron paths, and provides future html lang/dir helpers. It did not add routes, frontend rendering, content translation shaping, session/cookie/settings writes, or checkout/payment changes.
- Phase 2U.6.3 Arabic Public Route Aliases is implemented, focused-reviewed, diagnostics-tested, committed, and pushed in commit `4b773b2` (`Add YounGo Arabic public route aliases`). English canonical frontend URLs remain unprefixed. Arabic canonical public frontend URLs use `/ar/...`, and `/en` must not become canonical. No `/en` routes were added and no English routes were redirected to `/en`. Admin/dashboard and checkout/payment/cart/coupon/write/API/cron routes remain unlocalized. `arabic_translated` is not a route language, UI language, or translation-table language; course `language_made_in` remains separate course-content metadata.
- Arabic public aliases added in Phase 2U.6.3 are `/ar -> home/index`, `/ar/courses -> home/courses`, `/ar/courses/{page} -> home/courses`, `/ar/course/{slug}/{id} -> home/course/$1/$2`, `/ar/search -> home/search`, `/ar/search/{query} -> home/search/$1`, `/ar/my-courses -> home/my_courses`, `/ar/my-access -> home/my_access`, `/ar/wishlist -> home/my_wishlist`, `/ar/login -> login/index`, and `/ar/sign-up -> sign_up/index`. English unprefixed routes remain unchanged. Course detail preserves slug then id argument order, search preserves the query argument, course pagination remains compatible with `Home::courses()` using URI segment 3, and `/ar/login` plus `/ar/sign-up` target existing public auth controllers.
- Phase 2U.6.3 did not add aliases for admin, addons, api, cron, `home/payment`, `home/paypal`, `home/stripe`, `home/paymob`, `home/razorpay`, `home/paystack`, `home/flutterwave`, `home/course_payment`, `home/shopping_cart`, `home/update_cart`, `home/apply_coupon`, `home/remove_coupon`, `home/checkout`, `home/confirm_payment`, `home/webhook`, coupon write routes, cart write routes, or payment callback routes. Lesson/player/PDF aliases remain deferred, including `Home::lesson`, `Home::pdf_canvas`, `Home::play_lesson`, mobile lesson helpers, and offline video helpers, because those are gated playback/progress/session surfaces.
- Phase 2U.6.3 did not implement frontend translated content rendering, frontend RTL shell rendering, a language switcher UI, course/category/section/lesson translation shaping, session language writes, cookie writes, settings writes, phrase writes, checkout/payment/cart/coupon localization, or admin/dashboard localization. Arabic route aliases may still render existing canonical frontend content until later translation-aware rendering phases.
- The Phase 2U.6.3 diagnostic is `scripts/phase_2/youngo_phase_2u6_arabic_route_alias_diagnostic.php`. It checks `/ar` public aliases, preserved English routes, no `/en` routes, no admin/payment/checkout/cart/coupon/API/cron Arabic aliases, argument mapping, route order, Phase 2U.6.2 helper compatibility, no `arabic_translated` route/UI language usage, no frontend rendering/content translation changes, and Phase 2S/2P/2R compatibility. Focused review tightened existing diagnostics so `routes.php` changes are tolerated only when the diff is Arabic-alias-only. PHP lint and compatibility diagnostics passed; HTTP GET smoke was intentionally skipped in favor of static route diagnostics.
- Phase 2U.6.4 Translation-aware Frontend Content Shaping is implemented, focused-reviewed, runtime-QA tested, restored, committed, and pushed in commit `12fcdae` (`Add YounGo frontend content translation shaping`). It added `application/helpers/youngo_frontend_content_helper.php` and `scripts/phase_2/youngo_phase_2u6_frontend_content_translation_diagnostic.php`, and updated `Home.php`, `common_helper.php`, YounGo course listing/detail/wishlist views, and Phase 2U.6 diagnostics.
- Phase 2U.6.4 uses `Youngo_translation_model` plus the frontend language context helper to shape home, courses, search, course detail, my courses, my access, and wishlist initial page data. Fallback order is Arabic route = Arabic translation -> English translation -> canonical LMS field, and English route = English translation -> canonical LMS field. The helper overlays only whitelisted display fields, preserves canonical IDs and operational fields, stores original canonical values in safe `youngo_canonical_*` metadata, never emits/stores `arabic_translated`, and does not write DB/session/cookie/settings or call unknown `get_phrase()` keys.
- Course fields shaped are `title`, `short_description`, `description`, `outcomes`, `requirements`, `faqs`, `seo_title`, `meta_keywords`, and `meta_description`. Category display names can be shaped while canonical category IDs/slugs remain filter and URL identity. Section title plus lesson title/summary can be shaped outside deferred player/PDF routes, with helper support for text lesson body on non-player display paths. IDs, slugs/link identity, price, discount, currency, access modes, media, instructor, level, progress, entitlement/access fields, CTA state, wishlist state, checkout/payment fields, lesson type, duration, attachment, video URL, and player/PDF routes remain unchanged.
- Phase 2U.6.4 runtime QA used backup `D:\Work\YounGo\backups\youngo_school_before_phase_2u6_4_frontend_content_translation_qa_2026_07_18_215217.sql`. Temporary Arabic rows were inserted only into YounGo translation tables for course `1`, category `7`, section `1`, and lesson `1`; no canonical rows were changed. Restore removed temporary rows and matched pre/post counts, including course `8`, category `12`, section `18`, lesson `36`, course translations total `9` / English `8` / Arabic `1`, category Arabic `0`, section Arabic `0`, lesson Arabic `0`, `ci_sessions = 636`, `youngo_user_roles = 0`, `permissions = 2`, `enrol = 1`, and protected payment/watch/progress/entitlement/checkout/coupon rows `0`.
- QA confirmed `/`, `/home/courses`, and `/home/course/scratch-coding-for-young-creators/1` remain English/canonical; `/ar`, `/ar/courses`, and `/ar/course/scratch-coding-for-young-creators/1` display Arabic shaped values where translations exist and fall back safely; search Arabic aliases resolve; `/ar/wishlist`, `/ar/my-courses`, and `/ar/my-access` resolve without fatal/404 in unauthenticated safe GET checks; and excluded `/ar/admin`, `/ar/home/payment`, `/ar/home/checkout`, `/ar/home/shopping_cart`, `/ar/home/apply_coupon`, `/ar/api`, and `/ar/cron` render the app 404 page. Current working English routes for course/list/search/detail surfaces remain `/home/...`; direct `/courses`, `/course/...`, `/search`, and `/wishlist` are not current English routes.
- The Phase 2U.6.4 diagnostic is `scripts/phase_2/youngo_phase_2u6_frontend_content_translation_diagnostic.php`. It checks helper functions, `Home.php` integration, `/ar` aliases, no `/en` routes, no new route changes, no payment/checkout/cart/coupon localization, no `arabic_translated` table-language usage, canonical IDs/access/media/progress preservation, read-only sample shaping, and route/language/section-lesson/role/course-category/translation/access diagnostic compatibility.
- Phase 2U.6.5 Language Switcher and RTL Shell Rendering is implemented, focused-reviewed, runtime-QA tested, restored, committed, and pushed in commit `8321e91` (`Add YounGo language switcher and RTL shell`).
- Phase 2U.6.5 added route-derived frontend shell language/direction rendering: English routes output `html lang="en" dir="ltr"` and Arabic `/ar` routes output `html lang="ar" dir="rtl"`. The frontend body adds escaped language/direction classes and `data-youngo-language` / `data-youngo-dir` metadata while preserving existing body classes. Admin/backend shell rendering is unaffected.
- The YounGo frontend header now includes an `EN | عربي` language switcher with active state and `aria-current`. Switcher URLs are helper-generated, preserve query strings, map working English `/home/...` route reality to Arabic aliases, never generate `/en`, and never show/link `arabic_translated`.
- Focused review fixed `youngo_frontend_current_uri_string_with_query()` so it prefers CodeIgniter `$CI->uri->uri_string()` over raw `REQUEST_URI`, using `REQUEST_URI` only as fallback to avoid subdirectory base-path switcher URL errors.
- Minimal YounGo-scoped RTL CSS was added for header/nav shell behavior; full visual RTL polish remains deferred.
- Phase 2U.6.5 runtime QA used backup `D:\Work\YounGo\backups\youngo_school_before_phase_2u6_5_language_switcher_rtl_qa_2026_07_18_224356.sql`, needed no temporary Arabic rows, verified `/`, `/ar`, `/home/courses`, `/ar/courses`, query-string switching, course detail slug/id preservation, search query preservation, `/ar/login`, `/ar/sign-up`, `/ar/wishlist`, normal unauthenticated refresh redirects for `/ar/my-courses` and `/ar/my-access`, and app 404 behavior for excluded `/ar/admin`, `/ar/home/payment`, `/ar/home/checkout`, `/ar/home/shopping_cart`, `/ar/home/apply_coupon`, `/ar/api`, and `/ar/cron`. Restore succeeded and pre/post counts matched exactly, including `ci_sessions = 636`, course `8`, category `12`, section `18`, lesson `36`, course translations total `9` / English `8` / Arabic `1`, category Arabic `0`, section Arabic `0`, lesson Arabic `0`, `enrol = 1`, and protected YounGo entitlement/payment/checkout/coupon/progress tables `0`.
- The Phase 2U.6.5 diagnostic is `scripts/phase_2/youngo_phase_2u6_language_switcher_rtl_diagnostic.php`. It checks shell html lang/dir helper usage, English `en/ltr` and Arabic `ar/rtl` mapping, body metadata, switcher existence, helper-generated URLs, query preservation, no `/en` links/routes, no route additions, no session/cookie/settings language writes, no DB/phrase writes, no checkout/payment/cart/coupon localization, admin shell boundaries, and content/route diagnostic compatibility.
- PDF-specific browser QA was skipped to avoid broadening scope. Broader API/mobile entitlement UX remains future work.

Phase 2G safety findings:

- `Home::lesson()` makes the access decision before `update_last_played_lesson()`.
- Denied logged-out lesson access was tested safely.
- `watch_histories` and `watched_duration` did not increase during denied lesson tests.
- `Files::index()` authorization uses the session user id only; request/query `user_id` is not trusted.
- A fake request `user_id` parameter did not stream protected content.
- `Files::index()` validates course existence, lesson existence, and lesson/course match before file source/path resolution.
- Denied direct file access returns a safe empty response.
- Streaming internals were not intentionally changed.
- Denied file tests did not create watch/progress/enrol data.

Phase 2G.8C controlled QA matrix results:

- The fixed local QA dataset state was QA courses `32-42`, QA sections `43-53`, QA lessons `62-84`, enrol rows `8-9`, `youngo_course_access` rows `5-8`, and `youngo_user_subscriptions` rows `4-6`.
- The local PDF fixture existed at `uploads/lesson_files/youngo_qa_2g_local_only_sample.pdf`.
- The MP4 fixture did not exist, so MP4/range QA remained blocked.
- `TC-COURSE-ACCESS-REVOKED` isolation was confirmed fixed: course `37`, `youngo_access_mode=purchase_only`, `youngo_subscription_excluded=1`, user `6`, `has_access=false`, `access_source=course_purchase`, `status=revoked`.
- The entitlement-state matrix passed:
  - `TC-INSTRUCTOR-ACCESS`: allow, `instructor/active`
  - `TC-LEGACY-VALID`: allow, `legacy_enrol/active`
  - `TC-LEGACY-EXPIRED`: deny, `legacy_enrol/expired`
  - `TC-COURSE-ACCESS-ACTIVE`: allow, `course_purchase/active`
  - `TC-COURSE-ACCESS-EXPIRED`: deny, `course_purchase/expired`
  - `TC-COURSE-ACCESS-REVOKED`: deny, `course_purchase/revoked`
  - `TC-SUBSCRIPTION-ACTIVE`: allow, `subscription/active`
  - `TC-SUBSCRIPTION-EXPIRED`: deny, `subscription/expired`
  - `TC-SUBSCRIPTION-REVOKED`: deny, `subscription/revoked`
  - `TC-PURCHASE-ONLY-DENIAL`: deny, `none/none`
  - `TC-MEDIA-STREAMING`: allow, `course_purchase/active`
- Non-authenticated denial checks passed: logged-out `Home::lesson()` did not render protected content, logged-out `Files::index()` did not stream the protected PDF/file, fake request `user_id` did not grant file access, lesson/course mismatch did not stream, and missing lesson did not fatal or stream.
- Denied tests did not change `watch_histories` or `watched_duration`.
- No source code changed during QA. No manual session rows were created. `ci_sessions` increased only due to normal HTTP requests.
- No source fix is needed from Phase 2G.8C.

Boundaries:

- `enroll_status()` has not been modified.
- `Home::play_lesson()` and `Home::pdf_canvas()` are now aligned with the YounGo entitlement read layer by later Phase 2O work.
- Progress AJAX endpoints have not been modified.
- Broader API/mobile gates have not been fully aligned. Only the scoped helper route `lesson_mobile_web_view_get()` and `offline_video_for_mobile_app()` were aligned by Phase 2O.
- `Payment.php`, cart/payment/checkout/invoice behavior remain untouched.
- No subscription checkout, Paymob, real payment behavior, frontend phrase polish, full RTL visual polish, mobile/API rewrite, lesson/player/PDF Arabic aliases, course data rebuild, unrestricted Root Admin editing, or broader role/capability system rewrite has been implemented.
- Full file delivery compatibility is not claimed yet.

Next recommended phase:

- CTA boundary protection is complete locally in Phase 2S. Course add/edit bilingual form support is complete locally in Phase 2U.5.4, section/lesson bilingual form support is complete locally in Phase 2U.5.5, translation-aware frontend content shaping is complete locally in Phase 2U.6.4, and language switcher/RTL shell rendering is complete locally in Phase 2U.6.5. Optional PDF/mobile/API QA can be handled separately if approved before checkout/Paymob.
- Next roadmap focus should move to Phase 2U.6.6 frontend phrase conversion/inventory, Phase 2U.6.7 controlled frontend localization QA and diagnostics, Phase 2U.7 bilingual QA plus Arabic phrase polish QA, then Phase 2T Course Data Rebuild after bilingual infrastructure is ready unless the owner explicitly accepts an English-only interim rebuild. Optional mobile/API entitlement audit, checkout/order/coupon planning, and Paymob remain later, with Paymob only after checkout/order/coupon foundation. Phase 2V.2 role-assignment docs/UX polish may be handled if needed and is not blocking localization.
- Do not claim full learner account AJAX localization, media playback Arabic aliases, full RTL visual polish, course-data production readiness, or API/mobile coverage until their own authenticated/browser/API QA phases are performed.

---

## 2. Current Access Flow Map

### Public Course Details

Current flow:

- `Home::course()` loads public course details.
- `Home::access_denied_courses()` blocks draft and pending courses for unauthorized users.
- The YounGo course page currently builds CTA state from:
  - `is_purchased()`
  - `course.is_free_course`
  - legacy cart routes
  - legacy buy-now routes

Current behavior:

- Public course details are visible for active courses.
- Lesson access is separate from course detail visibility.
- Existing free-course and paid-course UI is still Academy LMS-compatible legacy behavior.

### Lesson Access

Current flow:

- `Home::lesson()` uses `youngo_get_course_access_state()` as the hard access decision when the entitlement helper/model are available.
- It preserves fallback compatibility if the entitlement helper/model are unavailable.
- It allows access for:
  - admin/root users
  - assigned course instructors
  - valid legacy enrolment rows
  - active `youngo_course_access`
  - active subscriptions when the course is subscription-eligible
- It blocks:
  - no access
  - expired enrolments
  - revoked or locked access
  - missing course or lesson
  - lesson/course mismatch

Important implementation result:

- `Home::lesson()` now decides access before calling `Crud_model::update_last_played_lesson()`.
- Denied logged-out lesson access was tested safely.
- `watch_histories` and `watched_duration` did not increase during denied tests.

### Lesson File Delivery

Current flow:

- `Files::index()` uses `youngo_get_course_access_state()` as the hard access decision when the entitlement helper/model are available.
- It preserves fallback compatibility if the entitlement helper/model are unavailable.
- It uses the session user id for authorization and does not trust request/query `user_id`.
- It validates course existence, lesson existence, and lesson/course match before file source/path resolution.
- It allows file access for:
  - admin/root users
  - assigned course instructors
  - valid legacy enrolment rows
  - active `youngo_course_access`
  - active subscriptions when the course is subscription-eligible
- It denies direct file access for missing course, missing lesson, lesson/course mismatch, expired/revoked/locked access, and no access.

Important implementation result:

- Denied direct file access returns the existing safe empty response style.
- A fake request `user_id` parameter did not stream protected content.
- Denied file tests did not create watch/progress/enrol data.
- Streaming internals were not intentionally changed.

### My Courses

Current flow:

- `Home::my_courses()` loads the page.
- Phase 2P `Youngo_entitlement_model::get_learner_course_access_items($user_id)` composes learner-visible course access from legacy `enrol` rows and active direct YounGo/manual course access rows.
- `User_model::my_courses()` remains available for legacy compatibility; My Courses no longer depends on it as the sole source.
- YounGo `my_courses.php` renders prepared learner access items with learner-safe labels.
- Progress is read from `watch_histories`.

Current behavior:

- Existing `enrol` rows still show.
- Active direct YounGo/manual course access rows now show.
- Subscription access does not automatically list every subscription-eligible course.
- Expired enrolments can remain visible as expired/locked.
- Progress is preserved separately from access.

### Free Enrolment Legacy Path

Current flow:

- `Home::get_enrolled_to_free_course()` calls `Crud_model::enrol_to_free_course()`.
- `Crud_model::enrol_to_free_course()` grants access only when `course.is_free_course == 1`.
- Expiry is derived from `course.expiry_period`.

Compatibility rule:

- Keep this path as legacy compatibility.
- Do not treat public free-course classification as the target YounGo business model.

### Paid Purchase Legacy Path

Current flow:

- Cart and buy-now use session `cart_items`.
- `Payment::success_course_payment()` calls:
  - `Crud_model::enrol_student()`
  - `Crud_model::course_purchase()`
- `enrol_student()` creates or updates `enrol`.
- `course_purchase()` creates `payment`.

Compatibility rule:

- Existing course purchase remains supported.
- Phase 2 subscriptions are added beside this flow, not instead of it.

### Admin / Instructor Access

Current flow:

- Admin bypass is session and `role_id` based in lesson routes.
- Root/admin permission behavior is handled separately through current admin permission helpers.
- Instructor access is stored in:
  - `course.creator`
  - comma-separated `course.user_id`
- `Crud_model::is_course_instructor()` checks creator and assigned instructor ids.

Compatibility rule:

- Preserve current admin and assigned instructor behavior.
- Do not normalize or replace instructor assignment in Phase 2C.

### Expired Enrolment Behavior

Current flow:

- `enroll_status()` returns:
  - `valid`
  - `expired`
  - `false`
- `Home::lesson()` blocks expired access and redirects to the course page.
- My Courses may still display expired rows.

Compatibility rule:

- Expired access should lock lessons but preserve progress and history.

---

## 3. Existing Access Decision Points

Current access decision points include:

```text
application/helpers/user_helper.php
application/helpers/common_helper.php
application/controllers/Home.php
application/controllers/Files.php
application/controllers/Admin.php
application/controllers/User.php
application/controllers/Payment.php
application/models/Crud_model.php
application/models/User_model.php
application/models/Payment_model.php
application/views/frontend/youngo/
application/views/lessons/
```

Important functions and methods:

- `is_purchased()`
- `enroll_status()`
- `has_permission()`
- `is_root_admin()`
- `Home::course()`
- `Home::lesson()`
- `Home::play_lesson()`
- `Home::pdf_canvas()`
- `Home::get_enrolled_to_free_course()`
- `Home::coupon_offer_100_percent()`
- `Files::index()`
- `User_model::my_courses()`
- `Crud_model::enrol_student()`
- `Crud_model::enrol_a_student_manually()`
- `Crud_model::shortcut_enrol_a_student_manually()`
- `Crud_model::enrol_to_free_course()`
- `Crud_model::course_purchase()`
- `Crud_model::course_page_accessibility()`
- `Crud_model::is_course_instructor()`
- `Crud_model::update_last_played_lesson()`
- progress and watch-history helpers

Important view assumptions:

- YounGo course pages and cards use `is_purchased()`.
- YounGo My Courses reads from legacy `enrol`.
- Course reviews use `enroll_status()` to decide review eligibility.
- Lesson views assume the controller has already authorized access.

---

## 4. Recommended Entitlement Layer Design

Later implementation should add a YounGo-scoped compatibility layer.

Recommended locations:

```text
application/helpers/youngo_entitlement_helper.php
application/models/Youngo_entitlement_model.php
```

Design principles:

- Read-only first.
- Compatibility-first.
- Additive only.
- No `enroll_status()` modification at first.
- No payment behavior.
- No subscription purchase behavior.
- No manual grant admin behavior.
- No Paymob behavior.
- No database writes.
- No legacy access removal.

The layer should:

- Read current Academy LMS enrolment state.
- Read future `youngo_course_access` rows when schema exists.
- Read future `youngo_user_subscriptions` rows when schema exists.
- Detect assigned instructor access.
- Detect admin/root bypass when context allows.
- Return one structured access state for controllers and views.

Do not scatter new access rules across views.

---

## 5. Recommended Helper and Model Function Names

Recommended helper functions:

```php
youngo_get_course_access_state($user_id, $course_id, $context = array())
youngo_can_access_course($user_id, $course_id, $context = array())
youngo_can_access_lesson($user_id, $lesson_id, $context = array())
youngo_course_is_subscription_eligible($course_id)
youngo_get_course_access_message($access_state)
youngo_is_access_warning_due($start_date, $expiry_date)
```

Recommended model methods:

```php
Youngo_entitlement_model::get_legacy_enrol_state($user_id, $course_id)
Youngo_entitlement_model::get_course_access_state($user_id, $course_id)
Youngo_entitlement_model::get_active_subscription_state($user_id)
Youngo_entitlement_model::course_is_subscription_eligible($course_id)
Youngo_entitlement_model::user_is_assigned_instructor($user_id, $course_id)
Youngo_entitlement_model::get_my_courses_access_rows($user_id)
```

Naming decision:

- Prefer `youngo_can_access_course()` for boolean checks.
- Prefer `youngo_get_course_access_state()` for structured state.
- Avoid replacing `is_purchased()` or `enroll_status()` names during the first compatibility pass.

---

## 6. Access Precedence Order

Recommended active-access precedence:

1. Root/admin bypass where context allows.
2. Assigned instructor bypass for assigned courses.
3. Valid legacy `enrol` row.
4. Active `youngo_course_access` row.
5. Active `youngo_user_subscriptions` row if course is subscription-eligible.
6. No active access.

Manual grants:

- Course manual grants are represented through `youngo_course_access`.
- Subscription manual grants are represented through `youngo_user_subscriptions`.

Expired-state detection:

- The entitlement layer should still detect expired states even when no active access source is found.
- This enables My Courses, course details, and locked-message UI to show accurate state.

---

## 7. Access State Return Structure

The entitlement layer should return a structured associative array.

Recommended shape:

```php
array(
    'has_access' => false,
    'lesson_access_allowed' => false,
    'course_visible_in_my_courses' => false,
    'access_source' => 'none',
    'status' => 'none',
    'is_lifetime' => false,
    'start_date' => null,
    'expiry_date' => null,
    'warning_80_percent' => false,
    'course_access_mode' => null,
    'subscription_eligible' => false,
    'lock_reason' => null,
    'message_key' => 'access_none',
    'source_record_id' => null,
    'legacy_enrol_id' => null
)
```

Allowed `access_source` values:

```text
admin
instructor
legacy_enrol
course_purchase
subscription
manual_grant
none
```

Allowed `status` values:

```text
active
expired
locked
revoked
none
```

Recommended `message_key` values:

```text
access_active
access_admin
access_instructor
access_expired
access_subscription_expired
access_purchase_expired
access_purchase_only_not_in_subscription
access_none
access_login_required
```

---

## 8. Relationship With Existing `enroll_status()`

Safest approach:

```text
Create a new YounGo helper and gradually replace access checks.
```

Do not modify `enroll_status()` initially.

Rules:

- `enroll_status()` remains the legacy Academy LMS access helper.
- `youngo_get_course_access_state()` may call `enroll_status()` or read `enrol` directly to include legacy state.
- New YounGo views and routes should use the new helper only after validation.
- `Home::lesson()` and `Files::index()` have now been migrated in controlled Phase 2G passes.
- Remaining hard gates should be migrated only after a controlled QA dataset/session strategy covers the current authenticated browser gaps.
- Do not make `enroll_status()` call the YounGo layer until legacy side effects, API/mobile paths, file delivery, and old views are fully audited.

Rejected first-pass options:

- Do not modify `enroll_status()` directly.
- Do not replace all `is_purchased()` usage at once.
- Do not change additional lesson/player/file/API gates before validating the current Phase 2G gaps.

---

## 9. My Courses Compatibility Design

My Courses now combines, locally:

- Existing `enrol` rows.
- Active direct `youngo_course_access` rows, including manual course grants.

My Courses does not yet auto-list:

- Every subscription-eligible course during an active subscription.
- Started, saved, or selected subscription-derived courses unless a later owner-approved phase adds that marker.
- Expired/revoked YounGo access as active course cards.

Rules:

- Existing enrolled courses continue to show.
- Expired legacy enrolments remain visible and locked where appropriate.
- Active course access rows show as active.
- Expired course access rows show as locked when prior access or progress exists.
- Subscription access should not automatically list every subscription-eligible course.
- Subscription courses should appear in My Courses only after the user has started, saved, or selected them, unless a later owner decision changes this.
- Progress remains read from `watch_histories`.
- Access expiry must not delete progress.

Phase 2P implemented normalized learner access-read methods in `Youngo_entitlement_model` rather than replacing `User_model::my_courses()` globally.

---

## 10. Lesson Player Compatibility Design

Lesson access should be migrated in careful layers:

1. Add read-only entitlement helper/model. Completed.
2. Validate helper against legacy enrolled, expired, admin, and instructor cases. Partially completed; local legacy enrol and expired cases still lack safe data.
3. Use access state for non-critical YounGo CTA display. Completed.
4. Use access state in `Home::lesson()`. Completed in Phase 2G.2.
5. Use the same access state in `Files::index()`. Completed in Phase 2G.3.
6. Plan a controlled local QA dataset/session strategy for remaining authenticated access cases before touching additional gates.
7. Use access state in `Home::play_lesson()` only after a separate approved plan.
8. Use access state in `Home::pdf_canvas()` only after a separate approved plan.

Important implementation requirement:

- `Home::lesson()` access decision now happens before watch-history mutation for denied users.

Lesson access rules:

- Admin and assigned instructor can access.
- Valid legacy enrolment can access.
- Valid course purchase access can access.
- Active subscription can access subscription-eligible courses.
- Purchase-only courses are not unlocked by subscription.
- Expired/revoked access locks lesson content.
- Progress and watch history must remain preserved.
- Preview/free lesson behavior may remain for legacy compatibility, but it is not the target YounGo business model.

---

## 11. Course Details CTA Design

Course details CTA should be driven by access state plus course access mode.

Course access modes:

```text
subscription_only
subscription_and_purchase
purchase_only
```

User states:

- Logged out.
- Logged in with no access.
- Active subscription.
- Purchased course.
- Expired access.
- Admin.
- Assigned instructor.

Recommended behavior:

- Logged out users see login/join CTA and available purchase/subscription options.
- Logged-in users with no access see subscription CTA, purchase CTA, or both based on course mode.
- Active subscription users see Start/Continue for subscription-eligible courses.
- Purchased users see Start/Continue.
- Expired users see locked state and renewal/repurchase CTA.
- Admin/instructor users see Open, Manage, or Preview access as appropriate.
- Purchase-only courses do not show Start for subscription-only entitlement.

Compatibility fallback before Phase 2 course access fields exist:

- Keep existing `is_free_course`, cart, buy-now, and free-enrol CTA behavior.
- Do not remove cart-first paths yet.
- Do not treat free-course classification as the future model.

---

## 12. Expired Access and 80% Warning Design

Legacy enrolment expiry:

- `enrol.expiry_date` null or empty means lifetime.
- Future `expiry_date` means active.
- Past `expiry_date` means expired.

Course purchase access expiry:

- `is_lifetime=1` means lifetime.
- Time-limited access uses `start_date` and `expiry_date`.

Subscription expiry:

- Active when status is active and `expiry_date >= time()`.
- Expiry blocks lesson access immediately.
- No grace period.

Warning calculation:

```text
elapsed = now - start_date
duration = expiry_date - start_date
warning = duration > 0 && elapsed / duration >= 0.8
```

Rules:

- Apply warning to active subscriptions.
- Apply warning to active time-limited course purchases.
- Do not apply warning to lifetime access.
- Do not apply warning to admin/instructor bypass.
- Expired access shows expired messaging instead of warning messaging.

---

## 13. Manual Grant Compatibility Design

Manual grants should be represented through entitlement records:

- Course manual grant: `youngo_course_access`.
- Subscription manual grant: `youngo_user_subscriptions`.

Access logic:

- The entitlement layer reads the access/subscription record.
- If the record source is manual grant, return `access_source=manual_grant`.
- Active manual grants allow access.
- Expired manual grants lock access.
- Revoked manual grants return locked/revoked state.

Compatibility rule:

- Existing admin manual enrolment remains legacy `enrol`.
- New manual grant implementation should not delete or rewrite existing enrolment rows.
- Manual grants do not require payment records.

---

## 14. Checkout / Payment Future Compatibility Notes

Phase 2C does not implement checkout, payment, Paymob, subscriptions, or manual grant creation.

The entitlement layer should be ready to read records created by later phases:

- Completed direct checkout creates `youngo_checkout_orders`.
- Course purchase checkout creates `youngo_course_access`.
- Subscription checkout creates `youngo_user_subscriptions`.
- Legacy checkout continues to create `payment` and `enrol`.
- Coupon discounts do not grant access until checkout creates formal access records.
- Paymob can later write provider metadata to checkout/payment tables without changing access read order.

Payment rules remain:

- Real payments are not enabled until implementation and QA approval.
- Invoices remain required later for course purchases and subscriptions.
- Phase 2C only prepares the access contract.

---

## 15. Files Likely Touched Later

Helpers/services:

```text
application/helpers/youngo_entitlement_helper.php
application/helpers/user_helper.php
```

Models:

```text
application/models/Youngo_entitlement_model.php
application/models/Crud_model.php
application/models/User_model.php
```

Controllers:

```text
application/controllers/Home.php
application/controllers/Files.php
application/controllers/Admin.php
application/controllers/Payment.php
```

Views:

```text
application/views/frontend/youngo/course_page.php
application/views/frontend/youngo/course_listing/course_card.php
application/views/frontend/youngo/my_courses.php
application/views/frontend/youngo/course_page_reviews.php
application/views/lessons/
```

Validation:

- Prefer manual validation or a local-only diagnostic tool only if explicitly approved.
- Do not add public debug routes by default.

---

## 16. Implementation Sequence Recommendation

Recommended implementation sequence and current state:

1. Add read-only YounGo entitlement helper/model. Completed locally in Phase 2F.
2. Implement legacy-only access state first. Completed locally in Phase 2F:
   - admin
   - assigned instructor
   - legacy enrol active
   - legacy enrol expired
   - no enrol
3. Add safe no-op Phase 2 table checks that return none if Phase 2 tables do not exist. Completed locally in Phase 2F.
4. Validate helper output manually against known legacy access cases. Completed locally through the read-only diagnostic script.
5. Integrate access state into YounGo course cards and course details CTA. Completed locally as presentation-only display.
6. Integrate normalized access state into My Courses. Completed locally as presentation-only display.
7. Regression test legacy free enrolment, paid purchase, manual admin enrolment, invoice, and progress before broader hard gate changes. Pending.
8. Plan controlled hard access gate integration before modifying lesson/file access. Completed for `Home::lesson()` and `Files::index()`.
9. Integrate `Home::lesson()` and move access decision before watch-history mutation for denied users. Completed in Phase 2G.2.
10. Integrate `Files::index()`. Completed in Phase 2G.3.
11. Browser/HTTP QA for logged-out denied `Home::lesson()` and denied direct `Files::index()` access. Completed in Phase 2G.2A and Phase 2G.3A.
12. Plan and apply controlled local QA dataset artifacts for entitlement hard-gate testing. Completed locally.
13. Rerun the controlled entitlement-state matrix after the fixed QA dataset reapply. Completed in Phase 2G.8C, with all read-only entitlement-state cases passing.
14. Clean up the local QA dataset using the reviewed down SQL before planning additional gates. Completed locally.
15. Implement the Shared Entitlement Write Service and Manual Grants dashboard/UI through separate approved phases. Completed locally in Phase 2M/2N.
16. Integrate learner-facing access surfaces for course cards, course detail CTAs, `Home::play_lesson()`, `Home::pdf_canvas()`, selected helper routes, and course review gates. Completed locally in Phase 2O.
17. Implement My Courses / My Access learner account visibility. Completed locally in Phase 2P.
18. Create curated QA learner baseline. Completed locally in Phase 2Q.
19. Complete learner-authenticated browser QA for existing Phase 2O/2P learner access surfaces using QA learner user 8. Completed locally in Phase 2Q.3.
20. Implement and QA read-only admin/user entitlement summaries before checkout/Paymob. Completed locally in Phase 2R.
21. Guard YounGo-managed course CTAs and legacy free-enrol route from legacy enrol/cart/buy shortcuts. Completed locally in Phase 2S.
22. Plan Phase 2T Course Data Rebuild, Arabic/English localization decision and language data support, optional mobile/API entitlement audit, checkout/order issuance, coupons, and Paymob only after separate approved plans.

---

## 17. Regression / Validation Plan

Before implementation:

- Logged-out course details loads.
- Logged-in user with no access can view course details but not lessons.
- Legacy free-enrol route still enrols non-YounGo-controlled legacy free courses, but must not enrol YounGo-managed modes `subscription_only`, `subscription_and_purchase`, or `purchase_only`.
- Legacy paid purchase creates `enrol` and `payment`.
- Existing invoice and purchase history work.
- Existing My Courses shows `enrol` rows.
- Existing valid enrolment opens lesson player.
- Existing expired enrolment is locked.
- Admin can open lessons.
- Assigned instructor can open assigned course lessons.
- Progress/watch history continues to work.

After helper introduction:

- Valid legacy enrol returns active.
- Expired legacy enrol returns expired and visible.
- No enrol returns no access.
- Admin context returns admin access.
- Instructor context returns instructor access.
- Helper output matches current legacy behavior before any hard gates are changed.

After presentation-only view integration:

- Course detail CTA matches access state.
- My Courses shows active and expired courses correctly.
- Course Listing cards show read-only access badges without changing listed courses, filters, sorting, pagination, course links, wishlist hooks, price display, or CTA contracts.
- In Phase 2F, actual lesson/file access gates remained unchanged until the later approved Phase 2G hard-gate passes.

After Phase 2G `Home::lesson()` and `Files::index()` hard access gate integration:

- Logged-out denied `Home::lesson()` access returns safely.
- `Home::lesson()` denied access does not call `update_last_played_lesson()`.
- Denied lesson attempts do not increase `watch_histories` or `watched_duration`.
- Logged-out direct `Files::index()` access returns a safe empty response and does not stream protected content.
- Fake request/query `user_id` does not grant file access.
- Lesson/course mismatch and missing lesson file URLs do not stream protected content.
- Denied file attempts do not increase `watch_histories`, `watched_duration`, or `enrol`.
- Existing cart, purchase, payment, invoice, and coupon flows are not intentionally changed.

Remaining Phase 2G QA gaps after Phase 2G.8C:

- Authenticated admin browser lesson/file playback.
- Assigned instructor browser lesson/file playback.
- Valid legacy enrol student lesson/file playback.
- Authenticated browser file/PDF playback.
- Authorized PDF streaming under a real session.
- MP4/range streaming.
- Payment/checkout behavior remains unchanged and was not part of Phase 2G.8C QA.
- `Home::play_lesson()`, `Home::pdf_canvas()`, progress AJAX endpoints, API/mobile gates, and any additional gates remain future phases.

---

## 18. Risks

Key risks:

- Authenticated browser lesson/file/PDF playback still needs safe browser/session QA.
- MP4/range streaming still needs an approved MP4 fixture and QA; full media playback coverage is not claimed yet.
- Additional gates such as `Home::play_lesson()`, `Home::pdf_canvas()`, progress endpoints, and API/mobile paths remain separate and should not be changed without their own plans.
- Many views call `is_purchased()` directly.
- API/mobile access checks are separate and should be planned after web compatibility.
- `course.user_id` stores comma-separated instructor ids and must remain compatible.
- Local DB has limited real access/payment/progress data, so staging or production verification remains important.
- Listing every subscription-eligible course in My Courses could create a noisy user experience.
- Modifying `enroll_status()` too early could break legacy LMS behavior.

---

## 19. Open Questions / Implementation-Time Decisions

No owner-level business-model questions are open for Phase 2C.

Implementation-time decisions:

- Whether to add a temporary local-only diagnostic route or rely on manual controller/view testing.
- Whether subscription access should show all eligible courses in My Courses or only started/saved/selected courses.
- Exact copy for expired, locked, and warning messages.
- Whether API/mobile entitlement compatibility should be a Phase 2C follow-up or separate mobile/API phase.

Recommended defaults:

- Use manual/local validation first.
- Show only started/saved/selected subscription courses in My Courses.
- Keep API/mobile compatibility as a separate follow-up after web behavior is stable.

---

## 20. Final Recommendation

The read-only entitlement compatibility layer, presentation-only integrations, and controlled `Home::lesson()` / `Files::index()` web hard gates are now implemented locally.

Do not treat this as subscription checkout, manual grant UI, direct checkout, Paymob, real payment, new role behavior, full file delivery compatibility, or production/server migration. Do not modify `enroll_status()` casually. Do not replace legacy enrolment, payment, cart, invoice, progress, instructor, or remaining lesson/player/API access behavior.

The safest path is:

- Keep the YounGo-scoped helper/model access-state readers read-only.
- Continue validating them against current legacy behavior.
- Treat `Home::lesson()` and `Files::index()` as locally integrated and read-only matrix-validated, but not fully QA-complete for authenticated browser playback or MP4/range media cases.
- Do not plan additional gates yet.
- Clean up the local Phase 2G QA dataset before further gate planning.
- Leave later Phase 2 features to consume the access contract after their own implementation approval.
