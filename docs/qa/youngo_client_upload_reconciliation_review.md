# YounGo Client Upload Reconciliation Review

Phase: `CLIENT.UPLOAD.RECONCILE.1 - Reconcile Previous Upload Plans With Current Demo State`

Date: 2026-07-20

Branch: `analysis/cms-audit`

Deployment target: cPanel hosting.

Scope: review and planning only. No upload, deployment, push, commit, DB write, SQL write, or form submission was performed.

## 1. Executive Verdict

Verdict: the current demo is close enough to package for a controlled client upload, but the existing upload/deployment plans are not directly executable anymore.

The old deployment package manifest and server runbook remain useful as deployment structure, sanitization guidance, and rollback/checklist references. They are outdated as package manifests because they were Phase 1 baseline documents and predate the current bilingual Blog support, `youngo_blog_translations`, current Blog media, polished category/course/homepage images, latest public smoke, upload guard file, EGP/subscription boundary state, and current route strategy.

The next upload-prep work should be a fresh package phase, not a source fix phase: create a sanitized DB export and media package from the current state, restore-test that package locally, then use an updated server upload runbook.

## 2. Previous Upload/Deployment Plans Found

Primary upload/deployment documents:

- `docs/planning/youngo_deployment_package_manifest.md`
- `docs/planning/youngo_server_deployment_runbook.md`
- `docs/qa/youngo_client_upload_preflight_review.md`

Related demo/readiness planning and reports:

- `docs/planning/youngo_client_demo_acceleration_plan.md`
- `docs/planning/youngo_blog_contact_implementation_plan.md`
- `docs/qa/youngo_blog_contact_existing_system_audit.md`
- `docs/qa/youngo_demo_full_readiness_audit.md`
- `docs/qa/youngo_post_xampp_restore_demo_baseline.md`
- `docs/qa/youngo_demo_3_content_rebuild_report.md`
- `docs/qa/youngo_demo_content_1_blog_contact_report.md`
- `docs/qa/youngo_demo_fix_2_blog_i18n_report.md`
- `docs/qa/youngo_demo_fix_3_mobile_layout_report.md`
- `docs/qa/youngo_demo_screenshots_2_report.md`

Relevant read-only diagnostics/scripts found:

- `scripts/phase_2/youngo_phase_2e_preflight.php`
- `scripts/phase_2/youngo_post_restore_demo_baseline_diagnostic.php`
- `scripts/phase_2/youngo_demo_full_readiness_audit_diagnostic.php`
- `scripts/phase_2/youngo_demo_content_1_blog_contact_diagnostic.php`
- `scripts/phase_2/youngo_demo_fix_2_blog_i18n_schema.php`
- `scripts/phase_2/youngo_demo_fix_2_blog_i18n_diagnostic.php`
- `scripts/phase_2/youngo_blog_contact_implementation_plan_diagnostic.php`
- `scripts/phase_2/youngo_blog_contact_implementation_diagnostic.php`
- `scripts/phase_2/youngo_blog_contact_existing_system_audit_diagnostic.php`
- `scripts/phase_2/youngo_demo_route_aware_phrase_diagnostic.php`
- `scripts/phase_2/youngo_demo_payment_cta_boundary_diagnostic.php`
- `scripts/phase_2/youngo_demo_phrase_safe_surface_cleanup_diagnostic.php`

Recent commit history also confirms package-relevant work after the old deployment docs:

- `7222c37 Prepare YounGo client upload preflight`
- `7a04e30 Polish YounGo category images and homepage navigation`
- `b80473a Fill YounGo demo surfaces with polished local imagery`
- `bd170fe Add final YounGo public demo screenshots QA`
- `bb4532a Polish YounGo mobile demo layout and blog visuals`
- `8cc294a Add YounGo bilingual blog demo content support`
- `ff262b7 Document post-XAMPP restore demo baseline`
- `bfa8526 Add YounGo client demo acceleration plan`
- `21d673e Add YounGo server deployment runbook`
- `ae532b8 Add YounGo deployment package manifest`

## 3. What Remains Valid

- Deploy from `analysis/cms-audit` only after owner approval.
- Keep YounGo as a CodeIgniter/Academy LMS theme/product layer. Do not treat this as a Laravel or rebuilt app.
- A source-only upload is not enough; the demo requires a DB export and matching media/upload folders.
- Use a sanitized SQL export, with `ci_sessions` empty/excluded.
- Do not package cache, logs, temp folders, SQL dumps, archive files, local tooling, Playwright/browser caches, or secrets.
- For cPanel hosting, upload source to the correct document root, such as `public_html` or the configured subdomain folder.
- Create/import the MySQL database through cPanel/phpMyAdmin.
- Configure `application/config/database.php` on the cPanel server with the cPanel MySQL database name, username, and password, not local credentials.
- Verify `base_url`/domain configuration for the live cPanel URL.
- Verify Apache/cPanel `.htaccess` rewrite rules work without exposing `index.php` or protected files.
- Preserve `frontend_settings.theme = youngo`, `frontend_settings.youngo_homepage_content`, EGP currency settings, courses/categories/sections/lessons, Blog data, and current translation tables.
- Include uploads/system logos/favicon, current YounGo frontend assets, current course/category/blog media, and upload guard files.
- Keep payment, checkout, Paymob, coupon, subscription purchase, manual grant, ownership/user-role internals, Root Admin internals, and language/phrase management out of the client demo.
- The client should use a normal Admin account, not Root Admin. The account should have broad operational access for content, courses, categories, lessons, Blog, Contact info, homepage/frontend settings, and normal website management.
- Use English unprefixed routes and Arabic `/ar/...` routes. Do not add or demo `/en`.
- Contact remains display-only for the client demo.
- Run public smoke tests after local restore and again after server upload.
- Keep rollback/restore notes with the package.

## 4. What Is Outdated

- The old `youngo_deployment_package_manifest.md` and `youngo_server_deployment_runbook.md` are marked Phase 1 baseline and explicitly exclude Phase 2 subscription/entitlement/manual-grant/multi-role schema. Current code now depends on several Phase 2/YounGo tables existing, even when protected write tables remain empty.
- The old DB R2 recommendation is not current. The DB changed after Blog/contact content, bilingual Blog support, demo image replacements, category/homepage navigation polish, and upload preflight fixes.
- The old selected-table export list is incomplete for current upload. It omits current YounGo translation requirements, most importantly `youngo_blog_translations`, and it does not fully account for current YounGo Phase 2 schema tables required by code.
- The old media manifest lists stale course thumbnail filenames ending in `1783873172.jpg`. Current public runtime course thumbnails are derived from `course.id`, active theme `youngo`, and `course.last_modified`.
- Older Blog/contact planning expected no Blog bilingual model. Current demo has four real Blog rows plus four English and four Arabic rows in `youngo_blog_translations`.
- Earlier Arabic Blog fallback language is superseded by the current Arabic Blog translation path on `/ar/blog`.
- Earlier counts are stale. Current read-only counts include `course = 8`, `section = 20`, `lesson = 40`, `blogs = 4`, and `youngo_blog_translations = 8`.
- Some older screenshot/readiness notes are superseded by `docs/qa/youngo_client_upload_preflight_review.md` and the current DB/media inspection in this report.

## 5. What Must Be Repeated After Recent Changes

- Fresh DB backup/export after Blog, image, navigation, and upload-preflight changes.
- Fresh sanitized DB checklist against the current live DB state.
- Fresh media manifest based on runtime-resolved course thumbnails plus DB-linked Blog/category media.
- Fresh package contents list that includes `assets/frontend/youngo/images/`.
- Fresh local restore test from the package into a clean test DB/folder.
- Fresh public smoke after local package restore.
- Fresh cPanel upload checklist/runbook using the current route list, target document root, cPanel MySQL/phpMyAdmin import path, CodeIgniter DB config path, live domain/base URL, Apache rewrite checks, and post-upload smoke URL.
- Fresh Admin scope and itemized safe-to-show/do-not-show list.
- Fresh rollback/restore note tied to the exact DB/media package.
- Fresh post-upload public smoke after the server upload.

## 6. Current Source Readiness

Current source contains the required YounGo frontend/demo pieces for packaging:

- YounGo public views for homepage, courses, course detail, Blog, Contact, login, and sign-up.
- YounGo route-aware language/content helpers.
- `application/models/Youngo_translation_model.php`.
- YounGo entitlement read layer files used by public course/access CTA boundaries.
- Blog bilingual persistence/rendering source in `Admin.php`, `Blog.php`, `Crud_model.php`, Blog admin views, and `application/views/frontend/youngo/blogs.php`.
- `application/config/routes.php` contains Arabic `/ar/...` aliases for home, courses, course detail, Blog, Contact, login, sign-up, wishlist, My Courses, and My Access.
- No `/en` route was found in `application/config/routes.php`.
- `uploads/.htaccess` exists and blocks public access to SQL/archive/database dump extensions under `uploads/`.
- `uploads/lesson_files/htaccess_domain_wise` and `uploads/lesson_files/videos/htaccess_domain_wise` were converted to inactive client-domain-safe templates in the previous upload preflight.

Local URL/path scan notes:

- No required YounGo runtime media was found pointing to `C:\Users`, `AppData\Local\Temp`, `/mnt/data`, or `D:\Work`.
- `application/config/database.php` contains local `localhost` DB config, as expected for local development. Server upload must use server-local DB config.
- Legacy installer/addon code still contains localhost checks. Do not show installer/addon areas to the client.
- `application/views/frontend/youngo/facebook_login.php` contains the external Facebook SDK locale `en_US`; this is not an `/en` route.
- Legacy `Crud_model.php` contains an external `html5rocks.com/en/...` demo media URL in mobile app lesson helpers; this is not part of the checked public YounGo upload path.

## 7. Current DB Readiness

Read-only local DB state observed on 2026-07-20:

- `frontend_settings.theme = youngo`
- `frontend_settings.blog_page_banner = blog-page.png`
- `frontend_settings.contact_info` contains YounGo demo email, phone, address, and office hours.
- `frontend_settings.youngo_homepage_content` exists.
- `settings.system_currency = EGP`
- `settings.currency_position = left`
- `settings.language = english`
- `settings.system_name = YounGo`
- `settings.system_email = academy@example.com` and should be reviewed during sanitization.

Current content counts:

- `course = 8`
- `category = 12`
- `section = 20`
- `lesson = 40`
- `blogs = 4`
- `blog_category = 3`
- `contact = 0`
- `enrol = 1`
- `payment = 0`
- `coupons = 0`

Current translation counts:

- `youngo_blog_translations = 8` (`english = 4`, `arabic = 4`, invalid language rows `0`)
- `youngo_course_translations = 16` (`english = 8`, `arabic = 8`, invalid language rows `0`)
- `youngo_category_translations = 24` (`english = 12`, `arabic = 12`, invalid language rows `0`)
- `youngo_section_translations = 34` (`english = 20`, `arabic = 14`, invalid language rows `0`)
- `youngo_lesson_translations = 68` (`english = 40`, `arabic = 28`, invalid language rows `0`)

Protected/payment/access table state:

- `ci_sessions = 1430`; do not export these rows.
- `youngo_subscription_plans = 3`; all observed plan rows are inactive EGP placeholders.
- `youngo_course_access = 0`
- `youngo_user_subscriptions = 0`
- `youngo_manual_grants = 0`
- `youngo_checkout_orders = 0`
- `youngo_coupon_usages = 0`
- `youngo_coupon_subscription_plans = 0`
- `youngo_coupon_courses = 0`
- `payment_gateways = 15`; observed gateway rows have `status = 1` and mixed non-EGP gateway currencies. Public YounGo payment CTAs are hidden/deferred, but payment settings/gateway rows must be sanitized or kept strictly out of client scope.

Private/demo rows that need an owner decision before a normal client Admin DB export:

- Course `43`, `YounGo QA Course Render Test`, status `private`, has no runtime thumbnail and should be excluded or cleaned before any client Admin-facing package.
- Courses `2` and `5` are private demo courses with valid runtime thumbnails. They are not public, but they will be visible in broad Admin course lists. Keep them only if the client Admin demo intentionally includes hidden/private course examples.

## 8. Current Media/Upload Readiness

Required public media is local and present for current checked surfaces.

System/fallback/theme assets to include:

- `uploads/system/favicon.png`
- `uploads/system/logo-dark.png`
- `uploads/system/logo-light-sm.png`
- `uploads/system/logo-light.png`
- `uploads/user_image/placeholder.png`
- `uploads/blog/page-banner/blog-page.png`
- `uploads/blog/thumbnail/placeholder.png`
- `uploads/blog/banner/placeholder.png`
- `assets/frontend/youngo/images/`

Current runtime course thumbnail files to include:

- `uploads/thumbnails/course_thumbnails/course_thumbnail_youngo_11784421120.jpg`
- `uploads/thumbnails/course_thumbnails/course_thumbnail_youngo_21784420052.jpg`
- `uploads/thumbnails/course_thumbnails/course_thumbnail_youngo_31784421120.jpg`
- `uploads/thumbnails/course_thumbnails/course_thumbnail_youngo_41784421120.jpg`
- `uploads/thumbnails/course_thumbnails/course_thumbnail_youngo_51784420052.jpg`
- `uploads/thumbnails/course_thumbnails/course_thumbnail_youngo_61784436100.jpg`
- `uploads/thumbnails/course_thumbnails/course_thumbnail_youngo_91784436176.jpg`

Category thumbnails to include:

- `uploads/thumbnails/category_thumbnails/youngo-category-coding-for-kids.jpg`
- `uploads/thumbnails/category_thumbnails/youngo-category-science-explorers.jpg`
- `uploads/thumbnails/category_thumbnails/youngo-category-creative-arts.jpg`
- `uploads/thumbnails/category_thumbnails/youngo-category-reading-storytelling.jpg`
- `uploads/thumbnails/category_thumbnails/youngo-category-math-adventures.jpg`
- `uploads/thumbnails/category_thumbnails/youngo-category-life-skills.jpg`

Blog media to include:

- `uploads/blog/thumbnail/7d19203b5cef547e20676bef91235635.png`
- `uploads/blog/thumbnail/fa7be008c5bb2431d8a80cd0baffe858.png`
- `uploads/blog/thumbnail/68e1bca98eea77eb81723605ef4a29fe.png`
- `uploads/blog/thumbnail/8183be10ff3cf8ae609966db0d18b51b.png`
- `uploads/blog/banner/984a9fa16980f9ce1821c18ef2fe8b79.png`
- `uploads/blog/banner/e2ab77a58574b476d89c377e463cae35.png`
- `uploads/blog/banner/640c00144e98502deef858fe01036162.png`
- `uploads/blog/banner/24cafe3ab2619be087605d31e1c10ebe.png`

Lesson/course media note:

- Current inspected demo lessons are text-only and do not require local video, audio, PDF, caption, or resource files for the current public demo flow.
- Writable lesson/resource folders should still be created on the server for future admin/course testing.

Packaging risk:

- The raw `course.thumbnail` DB field for courses `1-6` points to stale filenames, but current YounGo runtime rendering uses `Crud_model::get_course_thumbnail_url()` and `course.last_modified`. Package the runtime files above, not the stale `course.thumbnail` filenames.
- `uploads/install.sql` still exists locally and is blocked by `uploads/.htaccess`, but it must be excluded from the upload package.

## 9. Sanitized DB Export Requirements

The sanitized export must include structure and data for current public/admin demo dependencies:

- Core LMS tables required by Academy/CodeIgniter runtime: `users`, `role`, `permissions`, `category`, `course`, `section`, `lesson`, `rating`, `frontend_settings`, `settings`, `home_pages`, `language`, `seo_fields`, `currency`, `notification_settings`, and other existing CMS tables required by the app.
- Blog tables: `blogs`, `blog_category`, and `youngo_blog_translations`.
- YounGo translation tables: `youngo_course_translations`, `youngo_category_translations`, `youngo_section_translations`, and `youngo_lesson_translations`.
- YounGo access/subscription/checkout/coupon schema tables required by current code, with protected write tables kept empty unless intentionally seeded.
- Current `frontend_settings.youngo_homepage_content`, `frontend_settings.contact_info`, `frontend_settings.blog_page_banner`, and `frontend_settings.theme`.
- EGP currency setting and current inactive placeholder subscription plan definitions.

The sanitized export must exclude, empty, or sanitize:

- `ci_sessions` rows.
- Cache/log/runtime tables.
- Temporary QA rows and smoke-test artifacts.
- Private QA course `43`.
- Any unapproved Admin users, test users, local passwords, API keys, SMTP credentials, social login secrets, payment gateway secrets, and local machine paths.
- Local SQL/archive dump files.
- Real payment, checkout, coupon usage, and grant/access history unless explicitly approved for a separate test scenario.

## 10. Media Package Requirements

The media package must include:

- `assets/frontend/youngo/images/`
- `assets/frontend/youngo/css/` and other tracked YounGo frontend assets as part of source upload.
- `uploads/system/`
- `uploads/user_image/placeholder.png`
- `uploads/blog/page-banner/`
- `uploads/blog/thumbnail/` current demo Blog thumbnails plus placeholder.
- `uploads/blog/banner/` current demo Blog banners plus placeholder.
- `uploads/thumbnails/category_thumbnails/` current YounGo category thumbnails.
- `uploads/thumbnails/course_thumbnails/` current runtime course thumbnails listed in this report.
- Guard files such as `index.html`, `.htaccess`, and safe `htaccess_domain_wise` files where present.
- Empty writable folder structure for lesson files, videos, audios, captions, resource files, optimized thumbnails, cache, and logs.

The media package must exclude:

- `uploads/install.sql`
- `.DS_Store`
- SQL dumps, DB dumps, archives, backups, temp files, logs, cache files, and generated local tool output.
- Old stale course thumbnails that are not used by current `course.last_modified`.
- Files from `C:\Users`, `AppData\Local\Temp`, `/mnt/data`, or `D:\Work` outside the repo.

## 11. Security/Demo Hygiene Requirements

- Do not upload or expose local DB credentials.
- Do not upload SQL dumps or backups into public web folders.
- Keep `uploads/.htaccess` in the package, but still exclude dangerous files from the package.
- Empty `ci_sessions` in the DB export.
- Use server-only DB config after upload.
- Turn `display_errors` off on the server and keep logging enabled.
- Disable or sandbox payment gateways in the sanitized server DB/config before any client can reach payment settings or payment routes.
- Do not create or expose Root Admin credentials in docs or package notes.
- Create the client demo account as a normal Admin account, not Root Admin.
- Give the normal Admin broad operational access for content, courses, categories, lessons, Blog, Contact info, homepage/frontend settings, and normal website management.
- Do not describe the Admin account as heavily restricted. The restriction is on sensitive/core areas, not normal operating areas.
- Do not classify the whole System Setting dropdown as unsafe. Classify dashboard/sidebar items individually.
- Keep Root Admin internals, ownership/user-role internals, payment/Paymob/checkout/coupon areas, language/phrase editor, addons/installer/update/license/system version, SMTP/server credentials, database/backup/dev settings, and incomplete legacy/custom builder areas out of the client walkthrough.
- Keep Contact display-only until live contact submission has a dedicated QA phase.

## 12. Admin Safe-To-Show List

Safe for a controlled walkthrough with a prepared normal Admin account:

- Public homepage and Arabic homepage.
- Public course listing and category filters.
- Public course detail for approved demo courses.
- Blog list and Blog detail pages in English/Arabic.
- Contact display-only pages in English/Arabic.
- Login and sign-up screens.
- YounGo homepage/content management areas already prepared for the demo.
- Blog list/add/edit for the four demo Blog posts.
- Course/category/section/lesson content management for approved demo content only.
- Normal operational/content settings that do not expose secrets or core infrastructure.
- Website/profile/frontend settings that control visible site content and branding.
- Contact info, social links, homepage/frontend content, menus/navigation, SEO/content metadata, and normal website management.
- System Setting sidebar items that are operational and content-facing, when they do not expose SMTP, database, license, update, payment, server, or core infrastructure controls.
- Basic thumbnail upload in a non-production test course only after a fresh backup/restore point exists.

## 13. Admin Do-Not-Show List

Do not show or ask the client to test:

- Language/phrase management.
- Legacy Arabic phrase/admin areas, including `arabic_translated`.
- Payment settings, Paymob, checkout, cart, coupons, invoices, callbacks, payment gateways, or real payment flows.
- Subscription purchase, checkout orders, coupon scope, Paymob, or gateway currency alignment.
- Manual grants, entitlement internals, ownership/user-role internals, Root Admin internals, and broad user/permission management.
- Root/core-only dashboard items, including Root Admin/user internals.
- Addons, installer, update/license/system version, and marketplace/system update areas.
- SMTP/server credentials, database/backup/dev settings, and infrastructure configuration.
- Payment, checkout, Paymob, coupon, and gateway configuration even if those appear under settings/sidebar menus.
- Incomplete legacy/custom builder areas.
- SMTP/password reset testing unless server SMTP is configured and explicitly in scope.
- Any admin surface still showing old Academy branding, broken Arabic, or internal debug/legacy behavior.
- Contact inbox/reply/delete flows unless live contact has been approved and QA-tested.
- Private QA course `43`.
- Private courses `2` and `5` unless the demo intentionally includes hidden/private course examples.

## 14. Blockers Before Upload

Blockers before client upload:

- No sanitized DB export has been created from the current post-Blog/post-image/post-preflight DB state.
- No current media package has been built from the runtime-resolved media list.
- No local restore test has been run from the final DB/media package.
- Private QA course `43` remains in the DB and must be excluded/cleaned before a normal client Admin-facing export.
- `ci_sessions` currently has rows and must not be exported with data.
- `uploads/install.sql` remains physically present locally and must be excluded from the package.
- Payment gateway rows are present and marked active in the local DB; payment areas must be sanitized/disabled or strictly inaccessible/out of demo scope before client upload.
- The Admin sidebar still needs an item-by-item demo map in the upload runbook so normal operational/content areas remain available while Root/core-only areas stay out of the walkthrough.
- The old package manifest/runbook are stale and must not be followed literally.

## 15. High-Priority Tasks Before Upload

- Confirm the exact cPanel document root for the demo domain: `public_html` for the primary domain or the configured subdomain/addon-domain folder.
- Create a fresh sanitized SQL export from the current DB.
- Create/import the target MySQL database through cPanel and phpMyAdmin.
- Decide whether private courses `2` and `5` should remain as hidden demo content or be excluded from the normal client Admin export.
- Exclude or clean private QA course `43`.
- Empty/exclude `ci_sessions`.
- Review/sanitize `settings.system_email`, SMTP/social/payment secrets, and Admin/user records without converting the client demo account into Root Admin.
- Prepare an itemized Admin sidebar safe/do-not-show checklist. Do not blanket-hide the whole System Setting dropdown; separate normal operational settings from Root/core-only settings.
- Confirm the sanitized DB includes `youngo_blog_translations` and all YounGo translation/access schema tables required by current code.
- Build a media package using the current runtime course thumbnail list, not the stale `course.thumbnail` filenames.
- Include all required uploads/media folders in the cPanel file package.
- Exclude `uploads/install.sql` and any dumps/backups/archives.
- Exclude secrets, temp files, local paths, and local-only config from the cPanel package.
- Update the correct CodeIgniter DB config file, `application/config/database.php`, with cPanel MySQL credentials after upload.
- Verify live domain/base URL configuration for the cPanel URL.
- Verify Apache/cPanel `.htaccess` rewrite rules work after upload.
- Restore-test the package locally.
- Run public smoke after restore on the preflight route list.
- Update or supersede the older deployment manifest/runbook with current cPanel package facts before upload.
- Run post-upload smoke on the live cPanel URL after owner-approved upload.

## 16. Deferred After-Demo Tasks

- Payment/checkout/Paymob/coupon implementation and QA.
- Live Contact form submission/inbox/reply hardening.
- Central Media Library.
- Full Arabic phrase-table repair and legacy Arabic admin cleanup.
- `/en` routes, unless a future approved routing phase changes strategy.
- Production subscription activation, real pricing approval, and gateway currency alignment.
- Full admin UX polish and old Academy branding cleanup across all backend screens.
- Role/grant internals or Root Admin management improvements.
- Lesson media replacement with final videos/PDFs/resources.

## 17. Revised Upload Sequence

1. `CLIENT.PACKAGE.1 - Sanitized DB and Media Package`
   - Create a fresh DB backup/export from current state.
   - Sanitize DB contents and package the exact current media required by this report.
   - Produce a package manifest with checksums or at least file counts/paths.

2. `CLIENT.PACKAGE.2 - Local Restore Test From Package`
   - Restore the sanitized DB and media into a clean local test target.
   - Verify theme, EGP, Blog translations, contact settings, media, and protected table emptiness.
   - Run public smoke on the preflight route list.

3. `CLIENT.UPLOAD.1 - cPanel Upload Checklist / Deployment Runbook`
   - Update the old server runbook with current cPanel package contents, target document root such as `public_html` or the subdomain folder, cPanel MySQL/phpMyAdmin import steps, the correct CodeIgniter DB config file path, live domain/base URL checks, Apache/cPanel `.htaccess` rewrite checks, required uploads/media folders, normal Admin account scope, itemized sidebar safe/do-not-show guidance, payment disable/sandbox expectations, exclusions, and rollback.
   - Do not upload until owner approval.

4. `CLIENT.SMOKE.1 - Post-Upload Public Smoke Test`
   - After upload, run public route smoke, image checks, mobile width checks, `/en` scan, Blog/Contact checks, and payment CTA boundary checks on the live cPanel URL.

## 18. Recommended Next Phase

Recommended next phase: `CLIENT.PACKAGE.1 - Sanitized DB and Media Package`.

Reason: the public source is already preflighted, but upload is still blocked by the absence of a current sanitized DB/media package and restore-tested package manifest. The next phase should create the package inputs and document exactly what will move to the client server. It should still not push, deploy, or upload without explicit owner approval.

Suggested commit message for this report, if approved later:

```text
Reconcile YounGo client upload plan
```

Files changed by this phase:

- `docs/qa/youngo_client_upload_reconciliation_review.md`

Files to stage, if this report is accepted:

- `docs/qa/youngo_client_upload_reconciliation_review.md`
