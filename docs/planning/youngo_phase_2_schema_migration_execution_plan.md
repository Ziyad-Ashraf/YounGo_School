# YounGo Phase 2E Schema Migration Execution Plan

## 1. Executive Summary

This document records the approved Phase 2E schema migration execution plan for the Phase 2 YounGo database changes and the local-only apply/validation result.

This document itself is documentation only. It does not modify source code, modify the database, run SQL, update `uploads/install.sql`, use the Academy updater, commit, or push.

Phase 2E should prepare a safe, reviewable, additive, compatibility-first migration path for the Phase 2B schema. The first migration pass must not destructively modify legacy Academy LMS tables or replace existing access, payment, role, permission, cart, invoice, or lesson behavior.

Core decisions:

- Use additive compatibility-first schema migration.
- Create migration artifacts first, then execute locally only after explicit approval.
- Do not apply anything to the server yet.
- Do not use `Updater.php` for the first Phase 2E migration pass.
- Do not modify `uploads/install.sql` in the first Phase 2E pass.
- Phase 1 DB R2 remains the current deployment baseline.
- Phase 2 schema must not be applied to server until separately approved.

---

## 1A. Local Apply And Validation Result

Phase 2E schema was applied to the local database only after manual backup confirmation.

Local apply context:

- Backup: `D:\Work\YounGo\backups\youngo_school_before_phase_2e_apply_2026_07_14.sql`
- Local DB: `youngo_school`
- Server: MariaDB `10.4.32`
- Server migration: not performed
- Result scope: schema-only local readiness

Local schema result:

- 12 `youngo_%` tables were created locally.
- Approved additive `course` columns were added locally.
- Approved additive `coupons` columns were added locally.
- `users`, `permissions`, `enrol`, and `payment` legacy columns were not altered.

Seed result:

- `youngo_subscription_plans`: 3
- `youngo_roles`: 4
- `youngo_capabilities`: 18
- `youngo_role_capabilities`: 24
- `youngo_user_roles`: 0

Protection checks:

- Root admin user `1` still has no permissions row.
- `client@gmail.com` still has permissions `["course"]`.

Public HTTP smoke tests passed on:

- `/`
- `/home/courses`
- `/home/course/scratch-coding-for-young-creators/1`
- `/login`
- `/sign_up`
- `/home/shopping_cart`
- `/home/my_wishlist`

Important boundary:

- The Phase 2E local schema apply alone did not implement subscriptions, entitlement behavior, expanded coupons, manual grants, direct checkout, Paymob payments, or multi-role behavior.
- Later Phase 2 work has implemented Subscription Plan Management locally, but only for plan definitions. It does not issue subscriptions, create checkout orders, process payments, apply coupons, grant manual access, create enrolments, or write access rows.
- Later Phase 2M work has implemented and QA-tested the Shared Entitlement Write Service foundation locally. It can create/revoke manual course grants and manual subscription grants through `Youngo_entitlement_write_model`.
- Later Phase 2N work has implemented and authenticated UI-QA tested the Manual Grants dashboard locally. It supports list/create/detail/revoke flows for manual course and subscription grants, enforces `grant_manual_access`, and delegates writes to `Youngo_entitlement_write_model`. Checkout/order issuance, payment, Paymob, and coupon flows are still not implemented.
- Later Phase 2O work has implemented and controlled browser-QA tested learner-facing entitlement visibility/enforcement alignment locally. Course cards, course details, selected lesson/PDF/helper access gates, and review gates use the YounGo entitlement read layer where scoped, and paid subscription-only checkout-not-ready states no longer expose legacy Buy Now/Add to cart.
- Later Phase 2P work has implemented and controlled-QA tested My Courses / My Access learner visibility locally. My Courses uses prepared learner access items for active legacy enrolments and active direct YounGo/manual course access rows; My Access is visibility-only and shows subscription/access summaries without checkout, renewal, payment, or coupon actions.
- Later Phase 2Q work has created a curated QA learner baseline locally through the dashboard/admin Add Student flow. QA learner user 8 is `qa.learner@youngo.local`, active learner, no enrol/access/subscription rows, with curated backup `D:\Work\YounGo\backups\youngo_school_qa_baseline_after_phase_2q2.sql`. Do not document, print, commit, store, or reuse its password.
- Later Phase 2Q.3 work completed learner-authenticated browser QA for the implemented Phase 2O/2P learner surfaces using QA learner user 8 and restored the curated baseline backup afterward. My Courses/My Access baselines, manual grant visibility/revocation, subscription visibility/revocation, course listing/detail access states, review-area visibility without submission, duplicate rejection, and GET-only cart boundary passed. PDF-specific browser QA remains a scoped limitation.
- Later Phase 2R work has implemented and authenticated-QA tested read-only admin/user entitlement summaries locally. User edit and course edit summary cards are guarded by `grant_manual_access`, keep Manual Grants as the only grant/revoke surface, and the Phase 2R diagnostic is `scripts/phase_2/youngo_phase_2r_admin_entitlement_summary_diagnostic.php`.
- Checkout/order issuance, coupon issuance, Paymob, and broader API/mobile entitlement UX remain future work.
- Phase 1 DB R2 remains the current deployment baseline until a separate server migration/export plan is approved.

Phase 2L local schema extension:

- `database/phase_2/youngo_phase_2l_subscription_plan_management_schema_up.sql` has been applied locally after backup confirmation.
- It adds `youngo_subscription_plans.archived_at`, `youngo_subscription_plans.archived_by_user_id`, and `idx_ysp_archived_at`.
- It creates `youngo_subscription_plan_audit_log` with `idx_yspal_plan_created` and `idx_yspal_actor_created`.
- It adds no hard foreign keys.
- The Phase 2L diagnostic reports the schema as applied.

Phase 2M local schema extension:

- `database/phase_2/youngo_phase_2m_entitlement_write_schema_up.sql` has been applied locally after backup confirmation.
- It adds `youngo_course_access.revoked_by_user_id` and `youngo_course_access.revoke_note`.
- It adds indexes on `youngo_course_access.checkout_order_id` and `youngo_course_access.revoked_by_user_id`.
- It adds `youngo_user_subscriptions.revoked_by_user_id` and `youngo_user_subscriptions.revoke_note`.
- It adds an index on `youngo_user_subscriptions.revoked_by_user_id`.
- It adds no hard foreign keys.
- The Phase 2M diagnostic reports the schema as applied.

Phase 2U.3 local localization schema extension:

- `database/phase_2/youngo_phase_2u3_localization_schema_up.sql` has been applied locally after backup confirmation.
- Backup: `D:\Work\YounGo\backups\youngo_school_before_phase_2u3_localization_schema_2026_07_16.sql`.
- It creates `youngo_course_translations`, `youngo_category_translations`, `youngo_section_translations`, and `youngo_lesson_translations`.
- It adds unique entity/language keys `uniq_yct_course_language`, `uniq_ycat_category_language`, `uniq_yst_section_language`, and `uniq_ylt_lesson_language`.
- It adds language/slug indexes `idx_yct_language_code`, `idx_yct_slug`, `idx_ycat_language_code`, `idx_ycat_slug`, `idx_yst_language_code`, and `idx_ylt_language_code`.
- It adds no hard foreign keys and does not alter canonical `course`, `category`, `section`, or `lesson` tables.
- `scripts/phase_2/youngo_phase_2u3_seed_english_translations.php` seeded English translation rows idempotently from canonical content: course translations `7`, category translations `12`, section translations `18`, and lesson translations `36`.
- Canonical counts stayed course `7`, category `12`, section `18`, lesson `36`. No Arabic rows were created.
- The Phase 2U.3 diagnostic is `scripts/phase_2/youngo_phase_2u3_localization_schema_diagnostic.php` and reports the schema/seed as applied.
- Phase 2U.3 did not modify language phrase tables, language settings, frontend rendering, dashboard forms, checkout/order/coupon/Paymob/payment, enrol/progress, entitlement rows, or course data.

Phase 2U.4 local Arabic UI phrase extension:

- `database/phase_2/youngo_phase_2u4_arabic_phrase_support_up.sql` has been applied locally after backup confirmation.
- Backup: `D:\Work\YounGo\backups\youngo_school_before_phase_2u4_arabic_phrase_support_2026_07_16.sql`.
- It adds canonical `language.arabic` phrase support and leaves `settings.language = english`.
- `settings.language_dirs` already contained `arabic: rtl`; the migration only preserves/adds that mapping conditionally if missing.
- It creates/uses `application/language/arabic.json` as the canonical Arabic phrase file. `application/language/arabic_translated.json` remains present but deprecated and non-canonical.
- `scripts/phase_2/youngo_phase_2u4_seed_arabic_phrases.php` seeded Arabic UI phrase values from current phrase keys and English values: phrases scanned `1401`, exact translations `141`, safe Arabic fallback phrases `1259`, English fallbacks `0`, updated Arabic values `1400`, placeholder warnings `0`, and mojibake warnings `0`.
- Post-change phrase coverage is English non-empty `1400`, Arabic non-empty `1400`, Arabic empty for English phrases `0`, Arabic coverage `100%`, mojibake count `0`, and placeholder warnings `0`.
- The Phase 2U.4 diagnostic is `scripts/phase_2/youngo_phase_2u4_arabic_phrase_diagnostic.php` and reports the phrase support as applied.
- Phase 2U.4 did not create Arabic rows in the Phase 2U.3 course/category/section/lesson translation tables, did not modify canonical course/category/section/lesson content, did not change frontend rendering, did not implement `/ar` routes, did not add dashboard bilingual forms, and did not touch checkout/order/coupon/Paymob/payment, enrol/progress, or entitlement rows.

Phase 2U.5.2 local translation model/helper foundation:

- `application/models/Youngo_translation_model.php` is implemented, reviewed, and committed as the central helper/model for YounGo bilingual content on top of the Phase 2U.3 translation tables.
- `scripts/phase_2/youngo_phase_2u5_translation_model_diagnostic.php` is implemented and reports the model foundation as applied.
- The model preserves canonical `course`, `category`, `section`, and `lesson` IDs and does not replace canonical LMS tables.
- Canonical language codes are `english` and `arabic`; compatibility inputs map `en` to `english`, `ar` to `arabic`, and deprecated `arabic_translated` to `arabic` only as an input alias.
- The model provides direct translation reads, fallback reads, future dashboard upsert methods, slug generation/availability checks, `has_translation()`, and missing-translation summaries.
- Fallback order is requested language translation, English translation, canonical LMS table data, then `null` for missing entities. Fallback metadata includes `requested_language`, `resolved_language`, `is_fallback`, `missing_translation`, and `translation_source`.
- Phase 2U.5.2 did not apply new schema, change dashboard forms, change frontend rendering, implement `/ar` routes, edit language phrases, create Arabic content translation rows, modify canonical course/category/section/lesson content, execute upsert methods, or touch checkout/order/coupon/Paymob/payment, enrol/progress, or entitlement rows.

Phase 2U.5.3 local category/subcategory bilingual form support:

- `application/models/Crud_model.php`, `application/views/backend/admin/category_add.php`, `application/views/backend/admin/category_edit.php`, `application/views/backend/admin/sub_category_add.php`, and `application/views/backend/admin/sub_category_edit.php` are updated and committed for category/subcategory bilingual form support.
- `scripts/phase_2/youngo_phase_2u5_category_bilingual_forms_diagnostic.php` is implemented and reports the category/subcategory bilingual form support as applied.
- Category and subcategory add/edit forms now include `english_name`, `english_slug`, `arabic_name`, and `arabic_slug`; Arabic fields are optional and RTL.
- Shared category fields, including code, parent/category selection, icon, thumbnail, and category image, remain canonical/shared outside the bilingual content fields.
- `Crud_model::add_category()` and `Crud_model::edit_category()` sync canonical `category.name` and `category.slug` from English fields, retain the legacy `name` fallback, upsert English category translations, and conditionally upsert Arabic category translations only when Arabic name or Arabic slug is non-empty.
- Empty Arabic category translation rows are not created, and `arabic_translated` is not used.
- Runtime QA used backup `D:\Work\YounGo\backups\youngo_school_before_phase_2u5_3_category_bilingual_form_qa_2026_07_16_190255.sql`, verified temporary category/subcategory add/edit flows with English and Arabic values, verified English-only category behavior without an Arabic row, restored the DB, removed temporary QA data, and returned to baseline category `12`, English category translations `12`, Arabic category translations `0`, and protected rows clean.
- Phase 2U.5.3 did not apply schema changes, modify `Admin.php`, modify course/section/lesson forms, change frontend rendering, implement `/ar` routes, edit language phrases, create course/section/lesson Arabic rows, rebuild courses, or touch checkout/order/coupon/Paymob/payment, enrol/progress, or entitlement rows.

Phase 2U.5.4 local course add/edit bilingual form support:

- Phase 2U.5.4 Course Add/Edit Bilingual Form Support and Course Form UX Alignment is implemented, runtime-QA tested, reviewed, and committed in commit `50ae714` (`Add YounGo course bilingual form support`).
- It updated `application/helpers/common_helper.php`, `application/models/Crud_model.php`, `application/views/backend/admin/course_add.php`, `application/views/backend/admin/course_add_shortcut.php`, `application/views/backend/admin/course_edit.php`, `scripts/phase_2/youngo_phase_2u4_arabic_phrase_diagnostic.php`, `scripts/phase_2/youngo_phase_2u5_category_bilingual_forms_diagnostic.php`, `scripts/phase_2/youngo_phase_2u5_translation_model_diagnostic.php`, and `scripts/phase_2/youngo_phase_2u5_course_bilingual_forms_diagnostic.php`.
- No new schema was applied in Phase 2U.5.4. The work uses the existing Phase 2U.3 translation tables.
- Course add/edit forms now support English and optional Arabic fields for `title`, `slug`, `short_description`, `description`, `outcomes`, `requirements`, `faqs`, `seo_title`, `meta_keywords`, and `meta_description`. Canonical course fields sync from English values for backward compatibility.
- English course translation rows are upserted after canonical create/update. Arabic course translation rows are upserted only when Arabic content is non-empty; English-only courses do not create Arabic rows.
- Course shortcut remains English-oriented: it creates canonical English course data and an English translation row, with Arabic completion deferred to full course edit.
- Course `language_made_in` is course-content metadata only. Valid marker values are `english`, `arabic`, and `arabic_translated`; `arabic_translated` is valid only as course content/video/material metadata. Translation table language codes remain only `english` and `arabic`, and `arabic_translated` must not be stored in `youngo_course_translations.language_code`.
- Runtime QA found and fixed a course edit HTTP 500 / partial blank page by safely loading `Youngo_translation_model` through the CodeIgniter instance, using English fallback values, safely defaulting Arabic arrays when Arabic translation is absent, and fixing malformed HTML in reviewed course edit sections.
- Pricing/access UX now hides/disables one-time price and discount fields for `subscription_only` courses with helper copy; `purchase_only` and `subscription_and_purchase` keep price/discount fields available. `Crud_model::update_course()` preserves existing price/discount values when disabled price fields are not posted. Add/shortcut paths default absent price fields safely. No checkout/payment/Paymob/order/coupon behavior was implemented.
- EGP display was normalized in `common_helper.php` while respecting configured currency position. Observed local settings were `system_currency = EGP` and `currency_position = left`, producing readable examples such as `EGP 500`. DB currency values, DB prices, checkout, payment, and Paymob logic were not changed.
- Runtime QA used a temporary admin account, not Root Admin. Backup before QA was `D:\Work\YounGo\backups\youngo_school_before_phase_2u5_4_temp_admin_runtime_qa_2026_07_17_002027.phpdbdump`. QA verified course edit/add rendering, bilingual fields, language marker options, subscription-only and purchase-mode pricing behavior, readable EGP display, temporary bilingual course add/edit, redirect to non-blank edit page, English-only no-Arabic-row behavior, and no duplicate translation rows.
- After restore from the Phase 2U.5.4 QA turn, temporary QA data from the final QA turn was removed. Existing manual course QA data from before that backup remained intentionally present: `course = 8`, `youngo_course_translations total = 9`, `english rows = 8`, `arabic rows = 1`, `category = 12`, `section = 18`, `lesson = 36`, `enrol = 1`, and protected payment/watch/progress/entitlement/checkout/coupon rows `0`.
- Runtime QA found the temporary admin lacked the legacy `course` permission. That was temporarily adjusted only inside QA and restored afterward. Phase 2V.0 audited the two permission systems, and Phase 2V.1 Role Assignment Management is implemented, runtime-QA tested, reviewed, and committed in commit `21aecd7` (`Add YounGo role assignment management`).
- Phase 2V.1 added `/admin/youngo/role-assignments` and `/admin/youngo/role-assignments/update` under YounGo -> Role Assignments, guarded by `manage_roles`, with Root Admin, Admin, Content, Course, and Instructor toggles.
- The approved authority model is Root Admin = developer/system owner/highest authority, Admin = client/operational owner, and Content/Course/Instructor = scoped operational roles. Root Admin remains protected from everyone else. Admin can manage roles for non-root users but cannot modify Root Admin. Content/Course/Instructor cannot manage roles unless explicitly granted `manage_roles`. Admin is mutually exclusive with Content/Course/Instructor, while Content, Course, and Instructor can combine; Content + Course is the practical Content & Course Manager state.
- Phase 2V.1 bridges legacy `users.role_id`, `users.is_instructor`, `permissions.permissions`, and `check_permission()` behavior with YounGo `youngo_roles`, `youngo_capabilities`, `youngo_role_capabilities`, `youngo_user_roles`, and the capability helper because legacy course/category pages still depend on `check_permission('course')` and `check_permission('category')`. Non-root role updates keep a permissions row to avoid legacy no-permissions-row full access.
- The reversible seed files `database/phase_2/youngo_phase_2v1_admin_manage_roles_up.sql` and `database/phase_2/youngo_phase_2v1_admin_manage_roles_down.sql` map only YounGo `admin` to `manage_roles`; scoped roles do not receive `manage_roles`; down removes only `admin -> manage_roles` without deleting role/capability rows or unrelated mappings.
- Phase 2V.1 runtime QA used a temporary admin account, not Root Admin. Backup before QA was `D:\Work\YounGo\backups\youngo_school_before_phase_2v1_admin_manage_roles_alignment_2026_07_17_061234.sql`. QA verified Admin access to Role Assignments, non-root role updates, Root Admin read-only UI, `protected_root_admin` rejection for tampered Root Admin update, Course-only denial from Role Assignments, scoped roles without `manage_roles`, Admin mutual exclusion, restored temporary QA assignments, reapplied required `admin -> manage_roles`, and clean protected payment/checkout/entitlement/manual-grant/coupon rows.
- The Phase 2V.1 diagnostic is `scripts/phase_2/youngo_phase_2v_role_assignment_diagnostic.php`.
- Phase 2U.5.4 did not modify section forms, lesson forms, frontend translation rendering, `/ar` routes, checkout/order/coupon/Paymob/payment, or entitlement write logic.

Phase 2U.5.5 local section/lesson bilingual form support:

- Phase 2U.5.5 Section/Lesson Bilingual Form Support is implemented, focused-reviewed, runtime-QA tested, restored, and committed in commit `f3368fc` (`Add YounGo section lesson bilingual form support`).
- It updated `application/models/Crud_model.php`, `application/views/backend/admin/section_add.php`, `application/views/backend/admin/section_edit.php`, `application/views/backend/admin/lesson_add.php`, `application/views/backend/admin/lesson_edit.php`, `application/views/backend/admin/text_type_lesson_add.php`, `application/views/backend/admin/text_type_lesson_edit.php`, `scripts/phase_2/youngo_phase_2u5_course_bilingual_forms_diagnostic.php`, and `scripts/phase_2/youngo_phase_2u5_section_lesson_bilingual_forms_diagnostic.php`.
- No new schema was applied in Phase 2U.5.5. The work uses existing Phase 2U.3 `youngo_section_translations` and `youngo_lesson_translations`.
- Section add/edit forms now support required or primary `english_title` and optional RTL `arabic_title`. Canonical `section.title` syncs from English, English section translations are upserted, and Arabic section translations are upserted only when Arabic title is non-empty. Blank Arabic section fields do not create empty Arabic rows and do not delete existing Arabic rows in this phase.
- Lesson add/edit forms now support required or primary `english_title`, optional `english_summary`, optional RTL `arabic_title`, optional RTL `arabic_summary`, and text lesson `english_text_content` / optional RTL `arabic_text_content`. Canonical `lesson.title`, `lesson.summary`, and text lesson body/content sync from English. Arabic lesson translations are upserted only when at least one Arabic translatable field is non-empty.
- Non-text lessons are not forced to provide text content. Media/video/PDF/file/shared fields remain shared and are not translated.
- `Crud_model::sync_legacy_lesson_post_fields()` copies English fields into legacy POST keys such as `title`, `summary`, and `text_description` before legacy handlers run, preserving Academy Cloud/video/media compatibility.
- `Youngo_translation_model` is used for section and lesson translations. Translation-table language codes remain only `english` and `arabic`; `arabic_translated` is not a translation-table language code.
- Runtime QA used backup `D:\Work\YounGo\backups\youngo_school_before_phase_2u5_5_section_lesson_bilingual_qa_2026_07_18_194034.sql`, verified bilingual section add/edit, bilingual text lesson add/edit, English-only section and text lesson no-Arabic-row behavior, minimal YouTube lesson media/title compatibility, canonical English sync, English upserts, conditional Arabic upserts, no duplicate translation rows, clean Arabic UTF-8, and protected rows clean.
- A temporary Course Manager role plus legacy course/category permissions were used only under backup and removed by restore. After restore, temporary sections/lessons/translations were removed and pre/post counts matched: `course = 8`, `section = 18`, `lesson = 36`, `youngo_section_translations total = 18`, `section English = 18`, `section Arabic = 0`, `youngo_lesson_translations total = 36`, `lesson English = 36`, `lesson Arabic = 0`, `youngo_user_roles = 0`, `permissions = 2`, `enrol = 1`, and protected payment/watch/progress/entitlement/checkout/coupon rows `0`.
- The Phase 2U.5.5 diagnostic is `scripts/phase_2/youngo_phase_2u5_section_lesson_bilingual_forms_diagnostic.php` and passes locally.

Phase 2U.6.3 local Arabic public route alias support:

- Phase 2U.6.2 Frontend Language Context and URL Mapping Helpers is implemented, focused-reviewed, committed, and diagnostics-tested in commit `25e0544` (`Add YounGo frontend language context helpers`). It added `application/helpers/youngo_frontend_language_helper.php` and `scripts/phase_2/youngo_phase_2u6_frontend_language_context_diagnostic.php`.
- Phase 2U.6.3 Arabic Public Route Aliases is implemented, focused-reviewed, diagnostics-tested, committed, and pushed in commit `4b773b2` (`Add YounGo Arabic public route aliases`).
- No schema, database rows, source data, language phrase values, settings, sessions, cookies, checkout/payment/cart/coupon behavior, role assignments, frontend translated rendering, RTL shell rendering, language switcher UI, or course/category/section/lesson translation shaping were changed in Phase 2U.6.3.
- Owner URL decision: English canonical frontend URLs remain unprefixed; Arabic canonical public frontend URLs use `/ar/...`; `/en` must not become canonical. No `/en` routes were added and no English routes were redirected to `/en`.
- Arabic public aliases added are `/ar -> home/index`, `/ar/courses -> home/courses`, `/ar/courses/{page} -> home/courses`, `/ar/course/{slug}/{id} -> home/course/$1/$2`, `/ar/search -> home/search`, `/ar/search/{query} -> home/search/$1`, `/ar/my-courses -> home/my_courses`, `/ar/my-access -> home/my_access`, `/ar/wishlist -> home/my_wishlist`, `/ar/login -> login/index`, and `/ar/sign-up -> sign_up/index`.
- English unprefixed routes remain unchanged. Course detail preserves slug then id argument order, search preserves the query argument, course pagination remains compatible with `Home::courses()` using URI segment 3, and `/ar/login` plus `/ar/sign-up` target existing public auth controllers.
- Arabic aliases were not added for admin, addons, api, cron, `home/payment`, `home/paypal`, `home/stripe`, `home/paymob`, `home/razorpay`, `home/paystack`, `home/flutterwave`, `home/course_payment`, `home/shopping_cart`, `home/update_cart`, `home/apply_coupon`, `home/remove_coupon`, `home/checkout`, `home/confirm_payment`, `home/webhook`, coupon write routes, cart write routes, or payment callback routes.
- Lesson/player/PDF aliases remain deferred, including `Home::lesson`, `Home::pdf_canvas`, `Home::play_lesson`, mobile lesson helpers, and offline video helpers, because those are gated playback/progress/session surfaces that need separate staged QA before Arabic aliases are added.
- `arabic_translated` is not a route language, UI language, or translation-table language. Course `language_made_in` remains separate course-content metadata.
- The Phase 2U.6.3 diagnostic is `scripts/phase_2/youngo_phase_2u6_arabic_route_alias_diagnostic.php`. It checks `/ar` public aliases, preserved English routes, no `/en` routes, no admin/payment/checkout/cart/coupon/API/cron Arabic aliases, argument mapping, route order, Phase 2U.6.2 helper compatibility, no `arabic_translated` route/UI language usage, no frontend rendering/content translation changes, and Phase 2S/2P/2R compatibility. Focused review tightened existing diagnostics so `routes.php` changes are tolerated only when the diff is Arabic-alias-only. PHP lint and compatibility diagnostics passed; HTTP GET smoke was intentionally skipped in favor of static route diagnostics.
- Phase 2U.6.4 Translation-aware Frontend Content Shaping is implemented, focused-reviewed, runtime-QA tested, restored, committed, and pushed in commit `12fcdae` (`Add YounGo frontend content translation shaping`). It added no schema and performed no durable DB writes. It added `application/helpers/youngo_frontend_content_helper.php` and `scripts/phase_2/youngo_phase_2u6_frontend_content_translation_diagnostic.php`, and updated `Home.php`, `common_helper.php`, YounGo course listing/detail/wishlist views, and Phase 2U.6 diagnostics.
- The Phase 2U.6.4 helper uses `Youngo_translation_model` plus the frontend language context helper to shape home, courses, search, course detail, my courses, my access, and wishlist initial page data. Fallback order is Arabic route = Arabic translation -> English translation -> canonical LMS field, and English route = English translation -> canonical LMS field. It overlays only whitelisted display fields, preserves canonical IDs and operational fields, stores original canonical values in safe `youngo_canonical_*` metadata, never emits/stores `arabic_translated`, and does not write DB/session/cookie/settings or call unknown `get_phrase()` keys.
- Shaped display fields include course `title`, `short_description`, `description`, `outcomes`, `requirements`, `faqs`, `seo_title`, `meta_keywords`, and `meta_description`; category display names; section titles; lesson title/summary; and text lesson body only for non-player display paths. IDs, slugs/link identity, filters, price, discount, currency, access modes, media, instructor, level, progress, entitlement/access fields, CTA state, wishlist state, checkout/payment fields, lesson type, duration, attachment, video URL, and player/PDF routes remain unchanged.
- Phase 2U.6.4 runtime QA used backup `D:\Work\YounGo\backups\youngo_school_before_phase_2u6_4_frontend_content_translation_qa_2026_07_18_215217.sql`. Temporary Arabic rows were inserted only into YounGo translation tables for course `1`, category `7`, section `1`, and lesson `1`; no canonical rows were changed. Restore removed temporary rows and matched pre/post counts: course `8`, category `12`, section `18`, lesson `36`, course translations total `9` / English `8` / Arabic `1`, category translations total `12` / English `12` / Arabic `0`, section translations total `18` / English `18` / Arabic `0`, lesson translations total `36` / English `36` / Arabic `0`, `ci_sessions = 636`, `youngo_user_roles = 0`, `permissions = 2`, `enrol = 1`, and protected payment/watch/progress/entitlement/checkout/coupon rows `0`.
- QA confirmed `/`, `/home/courses`, and `/home/course/scratch-coding-for-young-creators/1` remain English/canonical; `/ar`, `/ar/courses`, and `/ar/course/scratch-coding-for-young-creators/1` display Arabic shaped values where translations exist and fall back safely; search Arabic aliases resolve; `/ar/wishlist`, `/ar/my-courses`, and `/ar/my-access` resolve without fatal/404 in unauthenticated safe GET checks; and excluded `/ar/admin`, `/ar/home/payment`, `/ar/home/checkout`, `/ar/home/shopping_cart`, `/ar/home/apply_coupon`, `/ar/api`, and `/ar/cron` render the app 404 page. Current working English routes for course/list/search/detail surfaces remain `/home/...`; direct `/courses`, `/course/...`, `/search`, and `/wishlist` are not current English routes.
- The Phase 2U.6.4 diagnostic is `scripts/phase_2/youngo_phase_2u6_frontend_content_translation_diagnostic.php`. It checks helper functions, `Home.php` integration, `/ar` aliases, no `/en` routes, no new route changes, no payment/checkout/cart/coupon localization, no `arabic_translated` table-language usage, canonical IDs/access/media/progress preservation, read-only sample shaping, and route/language/section-lesson/role/course-category/translation/access diagnostic compatibility.
- Phase 2U.6.5 Language Switcher and RTL Shell Rendering is implemented, focused-reviewed, runtime-QA tested, restored, committed, and pushed in commit `8321e91` (`Add YounGo language switcher and RTL shell`). It added no schema and performed no durable DB writes.
- Phase 2U.6.5 updated `application/helpers/youngo_frontend_language_helper.php`, `application/views/frontend/youngo/header.php`, `application/views/frontend/youngo/index.php`, `assets/frontend/youngo/css/youngo.css`, Phase 2U.6 diagnostics, and added `scripts/phase_2/youngo_phase_2u6_language_switcher_rtl_diagnostic.php`.
- The YounGo frontend shell now uses route-derived language helper values. English routes render `html lang="en" dir="ltr"` and Arabic `/ar` routes render `html lang="ar" dir="rtl"`. Body metadata includes escaped `youngo-lang-english` / `youngo-lang-arabic`, `youngo-dir-ltr` / `youngo-dir-rtl`, `data-youngo-language`, and `data-youngo-dir`. Admin/backend shell rendering is unaffected.
- The frontend header now includes an `EN | عربي` switcher with active state and `aria-current`. URLs are helper-generated, preserve query strings, never generate `/en`, never show/link `arabic_translated`, and map working English `/home/...` route reality to Arabic aliases. Focused review fixed a subdirectory URL risk by making `youngo_frontend_current_uri_string_with_query()` prefer CodeIgniter `$CI->uri->uri_string()` over raw `REQUEST_URI`, with `REQUEST_URI` as fallback only.
- Minimal YounGo-scoped RTL CSS was added for header/nav shell behavior, with the language switcher kept readable/LTR and full RTL visual polish deferred.
- Phase 2U.6.5 runtime QA used backup `D:\Work\YounGo\backups\youngo_school_before_phase_2u6_5_language_switcher_rtl_qa_2026_07_18_224356.sql`. No temporary Arabic rows were needed. QA verified correct shell/switcher behavior for `/`, `/ar`, `/home/courses`, `/ar/courses`, `/home/courses?page=2`, `/ar/courses?page=2`, `/home/course/scratch-coding-for-young-creators/1`, `/ar/course/scratch-coding-for-young-creators/1`, `/home/search?query=Scratch`, `/ar/search?query=Scratch`, `/ar/login`, `/ar/sign-up`, and `/ar/wishlist`; normal unauthenticated refresh redirects for `/ar/my-courses` and `/ar/my-access`; and app 404 behavior for excluded `/ar/admin`, `/ar/home/payment`, `/ar/home/checkout`, `/ar/home/shopping_cart`, `/ar/home/apply_coupon`, `/ar/api`, and `/ar/cron`. Restore succeeded and pre/post counts matched exactly, including `ci_sessions = 636`, course `8`, category `12`, section `18`, lesson `36`, course translations total `9` / English `8` / Arabic `1`, category Arabic `0`, section Arabic `0`, lesson Arabic `0`, `enrol = 1`, and protected YounGo entitlement/payment/checkout/coupon/progress tables `0`.
- The Phase 2U.6.5 diagnostic is `scripts/phase_2/youngo_phase_2u6_language_switcher_rtl_diagnostic.php`. It checks shell html lang/dir helper usage, English `en/ltr` and Arabic `ar/rtl` mapping, body metadata, switcher existence, helper-generated URLs, query preservation, no `/en` links/routes, no route additions, no session/cookie/settings language writes, no DB/phrase writes, no checkout/payment/cart/coupon localization, admin shell boundaries, and content/route diagnostic compatibility.

---

## 2. Existing Install / Update / Migration Mechanisms Found

Existing mechanisms inspected:

```text
application/controllers/Install.php
application/controllers/Updater.php
uploads/install.sql
update/update_7.1/update_config.json
update/update_7.1/update_script.php
application/config/migration.php
system/libraries/Migration.php
docs/reference/cms_documentation/installation_and_update.md
```

Findings:

- `Install.php` is a fresh-install flow.
- `Install.php::run_blank_sql()` imports `uploads/install.sql`.
- `Install.php` writes `application/config/database.php` and later updates routes during the original installer flow.
- `uploads/install.sql` is the legacy full install dump and contains destructive fresh-install statements such as `DROP TABLE IF EXISTS`.
- `Updater.php` expects an uploaded update ZIP.
- `Updater.php` extracts the update ZIP, copies files listed in `update_config.json`, runs `common_script.php`, checks `require_version`, then runs `update_script.php`.
- The existing `update/update_7.1/update_script.php` uses CodeIgniter DB Forge and updates the application version setting.
- CodeIgniter migration library files exist, but the current Academy LMS project does not appear to use a normal app-owned migrations folder for this YounGo work.

Phase 2E implication:

- The first Phase 2E pass should not use `Install.php`, `Updater.php`, or `uploads/install.sql`.
- The first Phase 2E pass should use separately reviewed SQL/preflight artifacts created later after approval.

---

## 3. Recommended Migration Delivery Approach

Recommended approach:

- Prepare reviewable SQL artifacts in a YounGo-specific migration folder later.
- Split artifacts into preflight, schema, seed, validation, and rollback files.
- Run preflight locally first.
- Apply locally only after explicit approval.
- Validate locally before any server plan is prepared.
- Prepare a separate server migration plan only after local execution and QA pass.

Do not in the first Phase 2E pass:

- Modify `uploads/install.sql`.
- Use `Updater.php`.
- Build an Academy update ZIP.
- Run SQL.
- Apply schema to the server.
- Create destructive changes.
- Replace legacy `enrol`, `payment`, `permissions`, `role_id`, `is_instructor`, cart, invoice, or lesson access behavior.

---

## 4. Proposed Files To Create Later

No migration artifacts are created by this document.

Proposed future artifact set:

```text
database/phase_2e/README.md
database/phase_2e/001_preflight_readonly.sql
database/phase_2e/002_create_phase2_tables.sql
database/phase_2e/003_additive_legacy_table_changes.sql
database/phase_2e/004_seed_reference_data.sql
database/phase_2e/005_post_migration_validation.sql
database/phase_2e/rollback_before_use.sql
database/phase_2e/rollback_after_test_data_export_required.md
```

If a local-only runner is later approved, it should be separate from production code and should not expose database credentials.

Implementation-time correction:

- The final artifact path and filenames should be confirmed before creation.
- Use ASCII filenames.
- Avoid spaces.
- Keep SQL artifacts out of `uploads/install.sql`.

---

## 5. Proposed New Tables Implementation Order

Create new `youngo_` tables before additive changes to legacy tables where possible.

Recommended order:

1. `youngo_subscription_plans`
2. `youngo_roles`
3. `youngo_capabilities`
4. `youngo_role_capabilities`
5. `youngo_user_roles`
6. `youngo_user_subscriptions`
7. `youngo_checkout_orders`
8. `youngo_course_access`
9. `youngo_manual_grants`
10. `youngo_coupon_subscription_plans`
11. `youngo_coupon_courses`
12. `youngo_coupon_usages`

Optional/later tables:

- `youngo_user_capabilities`
- `youngo_entitlement_events`
- `youngo_course_instructor_assignments`

Compatibility rules:

- Prefer indexed integer reference columns instead of hard foreign keys unless live DB constraints are confirmed safe.
- Use explicit indexes for lookup paths needed by entitlement, checkout, coupon, and role helpers.
- Do not backfill historical `enrol` rows into `youngo_course_access` in the first migration.

---

## 6. Proposed Additive Table Changes Order

Recommended additive changes:

1. Add YounGo access-mode fields to `course`.
2. Add YounGo coupon scope/discount fields to `coupons`.
3. Add optional YounGo payment linkage fields to `payment` only when checkout/invoice implementation is approved.

Do not alter initially:

- `users`
- `role`
- `permissions`
- `enrol`
- `watch_histories`
- `watched_duration`
- `lesson`
- `section`
- `settings`
- `frontend_settings`

Do not automatically migrate:

- `course.expiry_period` into new purchase duration fields.
- public `is_free_course` state into the future YounGo business model.
- legacy admin users into broad YounGo Admin power.

---

## 7. Seed Data Implementation Plan

Seed data should be deterministic and idempotent.

Subscription plans:

- Monthly.
- 3 Months.
- Yearly.

Seed rule:

- Seed plans as inactive or non-purchasable until pricing and checkout UI are approved.
- 3 Months may be marked as the intended featured commercial plan, but it must not become purchasable until implementation and QA approve the purchase flow.

YounGo roles:

- `admin`
- `content_manager`
- `course_manager`
- `instructor`

YounGo capabilities:

- `manage_homepage_content`
- `manage_static_content`
- `manage_media`
- `manage_courses`
- `manage_lessons`
- `publish_courses`
- `manage_course_categories`
- `assign_existing_instructors`
- `manage_assigned_course_lessons`
- `grant_manual_access`
- `view_payments`
- `manage_users`
- `manage_instructors`
- `manage_system_settings`
- `manage_roles`
- `manage_subscriptions`
- `manage_coupons`
- `view_reports`

Do not seed:

- Normal stored Learner/User role assignments.
- Broad `youngo_user_roles` backfill for all `role_id = 1` users.
- A `permissions` row for Root Admin.

---

## 8. Root / Admin / Client-Admin Protection Plan

Root Admin protection:

- Do not create a `permissions` row for Root Admin.
- Do not change Root Admin `role_id`.
- Do not change Root Admin `is_instructor`.
- Do not assign or revoke Root Admin through normal role toggles in the first migration.

Admin protection:

- Do not blindly assign broad YounGo Admin power to all `role_id = 1` users.
- Treat existing `permissions` rows as compatibility-sensitive.
- Review each admin permission state before any future `youngo_user_roles` backfill.

Restricted client admin protection:

- Preserve the restricted client admin with `permissions = ["course"]`.
- Keep that account course-limited.
- Do not broaden it to full Admin during migration.

Backfill rule:

- Do not automatically backfill `youngo_user_roles` in the first schema migration.
- If role backfill is later approved, run it as a separate reviewed script with a report-only dry run first.

---

## 9. Idempotency Plan

Future SQL artifacts should be safe to review and safe to re-run where practical.

Table idempotency:

- Check whether each `youngo_` table exists before creating it.
- Use stable table names and explicit indexes.
- Avoid destructive `DROP TABLE` in forward migration artifacts.

Column idempotency:

- Check whether each additive column exists before adding it.
- Do not change existing column types in the first pass.
- Do not rename legacy columns.

Seed idempotency:

- Use stable unique keys such as `slug`, `role_key`, and `capability_key`.
- Use insert-or-update behavior where supported.
- Keep seed values deterministic.

Validation idempotency:

- Validation queries should be read-only.
- Validation should report counts and expected key existence.

---

## 10. Rollback Plan

Before Phase 2 data is used:

- Rollback can remove newly created `youngo_` tables and additive columns only after backup confirmation.
- Prefer restoring from a full database backup if migration partially applies.

After Phase 2 test data exists:

- Export all `youngo_` tables before rollback.
- Export any changed legacy tables if additive columns contain data.
- Disable Phase 2 code paths before removing schema.
- Preserve manual grant, checkout, subscription, coupon usage, role/capability, and audit records unless deletion is explicitly approved.

Never drop or truncate without backup:

- `enrol`
- `payment`
- `permissions`
- `role`
- `users`
- `watch_histories`
- `watched_duration`
- any `youngo_` table containing test or production data

---

## 11. Local Safety Checklist

Before creating migration artifacts later:

- Confirm current Git status.
- Confirm database name and server version.
- Confirm no existing `youngo_%` table conflicts.
- Confirm current local schema still matches Phase 2B assumptions.
- Confirm local backup/export exists.
- Confirm no source-code implementation is mixed into the migration artifact task.

Before any repeat or future local apply:

- Obtain explicit approval to run SQL.
- Run read-only preflight.
- Review preflight output.
- Confirm XAMPP/MySQL target database.
- Confirm backup location.
- Apply migration locally only.
- Run post-migration validation.
- Confirm legacy LMS behavior still loads.

---

## 12. Server Deployment Safety Notes

Phase 1 DB R2 remains the current deployment baseline.

Do not apply Phase 2 schema to the server yet.

Before any future server migration:

- Compare live/server schema against local schema.
- Compare row counts for relevant legacy tables.
- Confirm server MySQL/MariaDB version.
- Confirm backup and restore procedure.
- Confirm maintenance window.
- Confirm rollback plan.
- Confirm no live payment behavior depends on untested Phase 2 tables.
- Run and validate the migration locally first.

Important risk mitigation:

- Risk: server/local DB drift.
- Mitigation: compare live/server schema before any server migration, run and validate migration locally first, and never assume local DB equals server DB.

---

## 13. Validation Plan

Preflight validation:

- Confirm database name.
- Confirm server version.
- Confirm no existing `youngo_%` tables.
- Confirm legacy tables exist.
- Confirm target additive columns do not already exist or match expected shape.
- Confirm root/admin permission-row state.
- Confirm restricted client admin permission state.

Post-migration validation:

- Confirm all required `youngo_` tables exist.
- Confirm expected indexes exist.
- Confirm additive `course` columns exist.
- Confirm additive `coupons` columns exist.
- Confirm subscription plan seed rows exist and are inactive or non-purchasable.
- Confirm role and capability seed rows exist.
- Confirm no `youngo_user_roles` broad backfill occurred.
- Confirm no Root Admin `permissions` row was created.
- Confirm restricted client admin remains course-limited.
- Confirm legacy `enrol`, `payment`, `permissions`, `role`, `users`, progress, cart, invoice, and lesson tables were not destructively changed.

Manual smoke checks after local migration:

- Admin login still works.
- Restricted client admin still sees course-limited behavior.
- Student login still works.
- Existing course listing/details still load.
- Existing My Courses still loads.
- Existing lesson access still follows legacy behavior.

---

## 14. Implementation Phases And Current Status

Phase 2E.1:

- Reviewable local migration artifacts created.

Phase 2E.2:

- Read-only preflight created, linted, fixed for lightweight CodeIgniter config bootstrap, and run successfully.

Phase 2E.3:

- Approved schema artifacts applied locally only after backup confirmation.

Phase 2E.4:

- Local post-migration validation passed, including DB validation and public HTTP smoke tests.

Phase 2E.5:

- Document local apply and validation result.

Phase 2E.6:

- Prepare the next implementation planning step. Phase 2 product behavior remains pending.

Phase 2E.7:

- Future server schema comparison and server migration planning remain pending and require separate approval.

Phase 2E.8:

- Some future Phase 2 implementation work has now been completed locally after Phase 2E, including entitlement read/gate work, capability foundation, Course Add/Edit access settings, Subscription Plan Management, Subscription Plan Management QA, EGP system-currency alignment, and the Phase 2M Shared Entitlement Write Service foundation.
- Seeded Monthly, 3 Months, and Yearly subscription placeholders have now been corrected through the Subscription Plans dashboard to store EGP, remain inactive, and remain non-purchasable. Their current local prices are temporary placeholders only.
- Phase 2M controlled service QA passed locally: manual course grant/revoke, manual subscription grant/revoke, duplicate prevention, read-layer recognition/denial, linked manual grant revocation, checkout stub non-writing behavior, cleanup restore, and no checkout/payment/order/coupon/enrol/progress side effects.
- Phase 2N Manual Grants dashboard/UI is implemented and authenticated UI-QA tested locally: Root Admin normal login, YounGo navigation, list/create pages, manual course grant/revoke, manual subscription grant/revoke, duplicate rejection, read-layer recognition/denial, cleanup restore from `D:\Work\YounGo\backups\youngo_school (14).sql`, and no checkout/payment/order/coupon/enrol/progress side effects.
- Phase 2O learner-facing course-card/detail/lesson/PDF/review entitlement alignment is implemented and controlled browser-QA tested locally.
- Phase 2P My Courses / My Access learner visibility is implemented and controlled-QA tested locally: My Courses active direct course-access visibility, My Access active subscription summary visibility, duplicate rejection, revocation, GET-only cart boundary, cleanup restore from `D:\Work\YounGo\backups\youngo_school (16).sql`, and no checkout/payment/order/coupon/enrol/progress side effects.
- Phase 2Q curated QA learner baseline setup is completed locally: pre-setup backup `D:\Work\YounGo\backups\youngo_school (17).sql`, post-setup backup `D:\Work\YounGo\backups\youngo_school_qa_baseline_after_phase_2q2.sql`, dedicated QA learner user `8`, Root Admin and existing users preserved, user `2` / course `6` enrolment preserved, and no entitlement/checkout/payment/order/coupon/enrol/progress rows created.
- Phase 2Q.3 learner-authenticated browser QA is completed locally using QA learner user `8`: no-access My Courses/My Access baselines, manual course grant visibility/revocation, manual subscription visibility/revocation, duplicate rejection, course listing/detail access states, review-area visibility without submission, GET-only cart boundary, restore from `D:\Work\YounGo\backups\youngo_school_qa_baseline_after_phase_2q2.sql`, and final clean diagnostics.
- Phase 2R read-only admin/user entitlement summaries are implemented and authenticated-QA tested locally: user edit summary, course edit summary, `grant_manual_access` guard, Manual Grants filtered links, temporary course/subscription grant summary updates, duplicate rejection, revocation, cart boundary, restore from `D:\Work\YounGo\backups\youngo_school_qa_baseline_after_phase_2q2.sql`, final clean diagnostics, and no checkout/payment/order/coupon/enrol/progress side effects.
- Phase 2S CTA boundary fixes are implemented, reviewed, and manually validated locally: YounGo-managed courses are blocked from the legacy free-enrol route, Course 1 no longer exposes legacy Enroll Now/Add to cart/Buy Now, direct `/home/get_enrolled_to_free_course/1` redirects safely without creating a user 8 enrol row, Course 9 remains checkout-not-ready, wishlist CTAs/copy are aligned, and `scripts/phase_2/youngo_phase_2s_route_cta_boundary_diagnostic.php` passes.
- Phase 2U.4 canonical Arabic UI phrase support is implemented, reviewed, and locally applied: `language.arabic` exists, `application/language/arabic.json` exists, English remains the default language, `arabic_translated` is deprecated, the Phase 2U.4 diagnostic passes, and no Arabic course/content translation rows or frontend `/ar` routing were added.
- Phase 2U.5.2 translation model/helper foundation is implemented, reviewed, and locally diagnostic-tested: `Youngo_translation_model` exists, supports canonical `english`/`arabic` language handling, direct/fallback reads, future upserts, slug helpers, and missing-translation summaries, and no dashboard/frontend wiring or Arabic content rows were added.
- Phase 2U.5.3 category/subcategory bilingual form support is implemented, reviewed, runtime-QA tested, restored, and committed: category/subcategory add/edit forms now include English and optional RTL Arabic fields, canonical category name/slug sync from English, English category translations are upserted, Arabic category translations are optional/non-empty only, `arabic_translated` is not used, and the Phase 2U.5 category diagnostic passes.
- Phase 2U.5.4 course add/edit bilingual form support and course form UX alignment is implemented, reviewed, runtime-QA tested, restored, and committed: course add/edit now include English canonical and optional Arabic fields, English course translations are upserted, Arabic course translations are optional/non-empty only, course shortcut remains English-oriented, `language_made_in` supports metadata markers `english`, `arabic`, and `arabic_translated` while translation tables remain `english`/`arabic` only, the course edit blank/HTTP 500 issue was fixed, subscription-only pricing fields hide/disable while purchase modes keep price fields available, EGP display is readable, the temporary admin QA backup/restore completed, and `scripts/phase_2/youngo_phase_2u5_course_bilingual_forms_diagnostic.php` passes.
- Phase 2U.5.5 section/lesson bilingual form support is implemented, focused-reviewed, runtime-QA tested, restored, and committed: section add/edit now include `english_title` and optional RTL `arabic_title`, lesson add/edit now include `english_title`, optional `english_summary`, optional RTL `arabic_title`, optional RTL `arabic_summary`, and text lesson `english_text_content` / optional RTL `arabic_text_content`; canonical section/lesson/text fields sync from English, English translations are upserted, Arabic translations are optional/non-empty only, no empty Arabic rows are created, blank Arabic fields do not delete existing rows, shared media/type/course/section fields remain shared, `Crud_model::sync_legacy_lesson_post_fields()` preserves legacy media handler POST compatibility, the temporary admin backup/restore completed, and `scripts/phase_2/youngo_phase_2u5_section_lesson_bilingual_forms_diagnostic.php` passes.
- Current local post-cleanup entitlement write tables are empty: `youngo_course_access = 0`, `youngo_user_subscriptions = 0`, `youngo_manual_grants = 0`, and `youngo_checkout_orders = 0`.
- Remaining Phase 2 implementation work includes Phase 2U.6.6 frontend phrase conversion/inventory, Phase 2U.6.7 controlled frontend localization QA and diagnostics, Phase 2U.7 bilingual QA plus phrase polish QA, Phase 2T Course Data Rebuild planning after bilingual infrastructure is ready, optional PDF/mobile/API entitlement QA, checkout/order/coupon planning, Paymob sandbox/payment alignment after checkout/order/coupon foundation, broader API/mobile entitlement UX, broader QA, full RTL visual polish, My Courses/Wishlist AJAX language propagation, and Phase 2V.2 role-assignment docs/UX polish or QA follow-up if needed.

Phase 2E.9:

- Do not apply Phase 2 schema to the server or mark Phase 2 behavior implemented without separate approval.

Server migration remains a separate later phase.

---

## 15. Risks And Mitigations

### Risk: Server/local DB drift

Mitigation:

- Compare live/server schema before any server migration.
- Run and validate migration locally first.
- Never assume local DB equals server DB.

### Risk: Legacy LMS breakage

Mitigation:

- Use additive tables and columns only.
- Do not replace `enrol`, `payment`, `permissions`, `role_id`, `is_instructor`, cart, invoice, or lesson access behavior.

### Risk: Root Admin damage

Mitigation:

- Do not create a `permissions` row for Root Admin.
- Do not backfill root role assignments in the first migration.

### Risk: Restricted client admin broadened

Mitigation:

- Do not blindly assign broad Admin role to `role_id = 1` users.
- Preserve `permissions = ["course"]`.

### Risk: Subscription plans accidentally become sellable

Mitigation:

- Seed plans inactive or non-purchasable until pricing and checkout UI are approved.

### Risk: Update/install mechanisms overwrite source files or fresh-install schema

Mitigation:

- Do not use `Updater.php` for the first Phase 2E pass.
- Do not modify `uploads/install.sql` in the first Phase 2E pass.

---

## 16. Open Questions / Implementation-Time Decisions

Implementation-time decisions to confirm later:

- Final migration artifact folder path.
- Whether to include an optional local-only runner or use manual SQL execution.
- Exact inactive/non-purchasable column semantics for seeded subscription plans.
- Whether optional `payment` linkage fields are included in the first schema pass or deferred until checkout/invoice implementation.
- Whether optional `youngo_user_capabilities`, `youngo_entitlement_events`, or `youngo_course_instructor_assignments` are deferred or included.
- Exact server migration window and rollback owner.

---

## 17. Final Recommendation

Proceed from local schema/service/manual-grants/learner-visibility/learner-authenticated QA/admin-summary/CTA-boundary/localization-phrase/translation-model/category-form/course-form/section-lesson-form/frontend-language-helper/Arabic-route-alias/frontend-content-shaping/language-switcher-RTL-shell/role-assignment readiness into the next approved implementation planning step: Phase 2U.6.6 frontend phrase conversion/inventory, Phase 2U.6.7 controlled frontend localization QA and diagnostics, Phase 2U.7 bilingual QA plus phrase polish QA, Phase 2T Course Data Rebuild planning after bilingual infrastructure is ready, optional PDF/mobile/API entitlement QA, checkout/order/coupon planning, or Phase 2V.2 role-assignment docs/UX polish if needed. Keep migrations additive and compatibility-first. Do not use the Academy updater or installer path for Phase 2 schema, do not modify `uploads/install.sql`, do not backfill user roles automatically outside the approved role assignment flow, and do not apply Phase 2 schema to the server until a separate approval, server schema comparison, backup, local validation, and QA plan are complete. Do not treat the Phase 2N Manual Grants dashboard, Phase 2P My Access visibility page, Phase 2Q QA learner baseline, Phase 2Q.3 learner-authenticated QA, Phase 2R read-only admin summaries, Phase 2S CTA boundary fixes, Phase 2U.4 Arabic phrase support, Phase 2U.5.2 translation model/helper foundation, Phase 2U.5.3 category/subcategory bilingual form support, Phase 2U.5.4 course add/edit bilingual form support, Phase 2U.5.5 section/lesson bilingual form support, Phase 2U.6.2 frontend language context helpers, Phase 2U.6.3 Arabic route aliases, Phase 2U.6.4 frontend content shaping, Phase 2U.6.5 language switcher/RTL shell rendering, or Phase 2V.1 Role Assignment Management as checkout/order issuance, coupon behavior, Paymob payments, subscription purchase flow, full Arabic course-data readiness, mobile/API rewrite, lesson/player/PDF Arabic route readiness, My Courses/Wishlist AJAX localization, full RTL visual polish, or unrestricted Root Admin editing.
