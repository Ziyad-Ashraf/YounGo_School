# YounGo Client Upload Preflight Review

Phase: `CLIENT.UPLOAD.PREP.1 - Client Upload Preflight + Small Fixes`

Date: 2026-07-20

Branch: `analysis/cms-audit`

Runtime base: `http://school.local/`

## 1. Readiness Verdict

Conditionally ready for a limited client demo upload after the current source fixes are committed and a sanitized database/media package is prepared.

The public demo routes passed HTTP and image smoke after fixes. This is not production-ready and not payment-ready. Upload should include the current DB state needed by the bilingual/public demo, but sessions, dumps, secrets, and local QA artifacts must be excluded or cleaned.

## 2. Public Pages Checked

All requested routes returned HTTP `200` with no PHP/database error markers, no repeated `????`, no `/en` links, no visible skeleton text, no Paymob/cart/checkout/coupon CTA regression hits, and no broken images.

Checked:

- `/`
- `/ar`
- `/home/courses`
- `/ar/courses`
- `/home/course/scratch-coding-for-young-creators/1`
- `/ar/course/scratch-coding-for-young-creators/1`
- `/blog`
- `/blogs`
- `/ar/blog`
- `/contact`
- `/home/contact_us`
- `/ar/contact`
- `/login`
- `/ar/login`
- `/sign_up`
- `/ar/sign-up`

Additional checks:

- 21 unique rendered page images checked, broken image count `0`.
- Homepage/course/category links extracted from `/`, `/ar`, `/home/courses`, and `/ar/courses` resolved `200`.
- `/blog`, `/blogs`, and `/ar/blog` each rendered 4 blog cards.
- `/contact` and `/ar/contact` are display-only: no `<form>` and no submit controls; only email/contact links.
- Mobile screenshots at `390px` width were captured with Playwright Chromium for `/`, `/ar/courses`, `/home/course/scratch-coding-for-young-creators/1`, and `/ar/sign-up`; no obvious overflow, blank media, or clipped primary controls were observed after the sign-up copy fix.

## 3. Issues Found

- Fixed: fallback YounGo theme copy still used visible "theme skeleton" wording for unmapped pages.
- Fixed: `uploads/lesson_files/htaccess_domain_wise` and `uploads/lesson_files/videos/htaccess_domain_wise` contained `localhost` and malformed rewrite syntax.
- Fixed: Arabic sign-up showed "Apply to become an instructor" in English.
- Fixed: `uploads/install.sql` was publicly reachable at `/uploads/install.sql`; it now returns `403` under local Apache after adding `uploads/.htaccess`.
- Packaging caveat: older deployment docs list stale course thumbnail filenames. Current runtime course thumbnails are `last_modified` based and listed below.
- Data caveat: private local QA course `id 43` / `YounGo QA Course Render Test` exists in DB. It is not public, but it should be excluded from or cleaned before any client admin-facing DB export.

## 4. Fixes Made

- Replaced visible fallback "skeleton" copy in `application/views/frontend/youngo/index.php`.
- Added local YounGo phrase-map entries for `apply_to_become_an_instructor` in English and Arabic in `application/helpers/youngo_frontend_language_helper.php`.
- Replaced two passive `htaccess_domain_wise` upload helper files with inactive, client-domain-safe templates.
- Added `uploads/.htaccess` to block public access to SQL/archive/database dump files under uploads.

No DB writes were made. No payment, checkout, coupon, Paymob, role, Root Admin, language JSON, or legacy phrase-table changes were made.

## 5. Image/Media Readiness

Rendered public images are local and resolve successfully.

Required current public/demo media includes:

- `assets/frontend/youngo/images/logo_small_c.png`
- `assets/frontend/youngo/images/course-coding.webp`
- `uploads/system/favicon.png`
- `uploads/system/logo-dark.png`
- `uploads/system/logo-light-sm.png`
- `uploads/system/logo-light.png`
- `uploads/user_image/placeholder.png`
- `uploads/blog/page-banner/blog-page.png`
- `uploads/blog/thumbnail/placeholder.png`
- `uploads/blog/banner/placeholder.png`

Current course thumbnail files to include:

- `uploads/thumbnails/course_thumbnails/course_thumbnail_youngo_11784421120.jpg`
- `uploads/thumbnails/course_thumbnails/course_thumbnail_youngo_21784420052.jpg`
- `uploads/thumbnails/course_thumbnails/course_thumbnail_youngo_31784421120.jpg`
- `uploads/thumbnails/course_thumbnails/course_thumbnail_youngo_41784421120.jpg`
- `uploads/thumbnails/course_thumbnails/course_thumbnail_youngo_51784420052.jpg`
- `uploads/thumbnails/course_thumbnails/course_thumbnail_youngo_61784436100.jpg`
- `uploads/thumbnails/course_thumbnails/course_thumbnail_youngo_91784436176.jpg`

Current category thumbnails to include:

- `uploads/thumbnails/category_thumbnails/youngo-category-coding-for-kids.jpg`
- `uploads/thumbnails/category_thumbnails/youngo-category-science-explorers.jpg`
- `uploads/thumbnails/category_thumbnails/youngo-category-creative-arts.jpg`
- `uploads/thumbnails/category_thumbnails/youngo-category-reading-storytelling.jpg`
- `uploads/thumbnails/category_thumbnails/youngo-category-math-adventures.jpg`
- `uploads/thumbnails/category_thumbnails/youngo-category-life-skills.jpg`

Current blog media to include:

- `uploads/blog/thumbnail/7d19203b5cef547e20676bef91235635.png`
- `uploads/blog/thumbnail/fa7be008c5bb2431d8a80cd0baffe858.png`
- `uploads/blog/thumbnail/68e1bca98eea77eb81723605ef4a29fe.png`
- `uploads/blog/thumbnail/8183be10ff3cf8ae609966db0d18b51b.png`
- `uploads/blog/banner/984a9fa16980f9ce1821c18ef2fe8b79.png`
- `uploads/blog/banner/e2ab77a58574b476d89c377e463cae35.png`
- `uploads/blog/banner/640c00144e98502deef858fe01036162.png`
- `uploads/blog/banner/24cafe3ab2619be087605d31e1c10ebe.png`

Exclude `.DS_Store`, SQL dumps, local archives, cache, logs, and test-only artifacts from the upload package.

## 6. DB Export Requirements

A DB export is required. The current demo is DB-backed, including public content, active theme, Arabic/English translations, blog posts, and currency settings.

Read-only local state observed:

- `frontend_settings.theme = youngo`
- `settings.system_currency = EGP`
- `settings.currency_position = left`
- `settings.language = english`
- `blogs = 4`
- `youngo_blog_translations = 8` (`english = 4`, `arabic = 4`)
- `youngo_course_translations = 16` (`english = 8`, `arabic = 8`)
- `youngo_category_translations = 24` (`english = 12`, `arabic = 12`)
- entitlement/checkout/coupon write tables checked in this pass were empty.
- `ci_sessions = 1353`; do not export these rows.

Export must preserve:

- Core LMS tables for courses/categories/sections/lessons/users/roles/permissions/settings/frontend settings/blogs.
- YounGo translation tables: `youngo_course_translations`, `youngo_category_translations`, `youngo_section_translations`, `youngo_lesson_translations`, `youngo_blog_translations`.
- YounGo Phase 2 schema tables needed by current code, with empty protected access/payment/coupon/order tables kept empty unless intentionally seeded.
- `frontend_settings.youngo_homepage_content`.
- EGP currency settings.

Export must exclude or sanitize:

- `ci_sessions` rows.
- logs/cache/runtime rows.
- local SQL dumps such as `uploads/install.sql`.
- private QA course `id 43` unless the owner intentionally wants it visible in admin.
- any SMTP/payment/social/API secrets.
- temporary QA rows and local smoke-test artifacts.

## 7. Upload Checklist

- Commit the files listed below after review.
- Prepare a sanitized SQL export from the current DB.
- Exclude or clean private QA course `id 43` before any client admin-facing DB export.
- Empty `ci_sessions` and runtime/log/cache data in the export.
- Include current `uploads/` media listed in this report, not stale thumbnail names from older package docs.
- Include `uploads/.htaccess` so SQL/archive dump files under uploads are blocked.
- Upload `application/`, `assets/`, `uploads/`, `.htaccess`, and core CodeIgniter files required by the app.
- Configure server `application/config/database.php` with server credentials only.
- Confirm Apache rewrite and `.htaccess` are enabled.
- Confirm required writable folders under `uploads/`, `application/cache/`, and `application/logs/`.
- Confirm `display_errors` is off and logging is on for client testing.
- Disable or sandbox payment gateways; do not enable Paymob/real payments.
- Rotate server admin credentials and create restricted client admin only after import.
- Run post-upload smoke on the same public route list.

## 8. Admin Safe-To-Show List

Safe for a controlled client walkthrough:

- Public homepage, Arabic homepage, courses, course detail, blog, contact, login, and sign-up.
- YounGo homepage/content management areas that are already part of the demo.
- Blog list/add/edit for the 4 demo blog posts, if the presenter stays within current content fields.
- Course/category/section/lesson content management for demo content, with a prepared restricted admin.
- Uploading course thumbnails and basic lesson content in a non-production test course, if a fresh server backup exists.

## 9. Admin Do-Not-Show List

Do not show or ask the client to test yet:

- Language/phrase management.
- `arabic_translated` legacy phrase/admin areas.
- Payment settings, Paymob, checkout, cart, coupon, invoices, gateway callbacks, or real payment flows.
- Subscription purchase, checkout/order issuance, coupon scope, or Paymob flows.
- Manual grants, entitlement internals, role assignment internals, Root Admin/user internals.
- Custom page builder areas if incomplete.
- Installer/addon/system settings/theme switching.
- SMTP/password reset unless server SMTP is configured and explicitly in scope.
- Any admin screen still showing old Academy branding or broken Arabic.
- Private QA course `id 43`.

## 10. Blockers Before Upload

No remaining public source/runtime blocker was found after fixes.

Required before client upload:

- Commit the current source/report changes.
- Prepare sanitized DB export.
- Exclude or clean private QA course `id 43`.
- Package current media filenames listed above.
- Do not upload local dumps as client-visible assets; `uploads/.htaccess` now blocks dump access, but package hygiene still matters.
- Keep payment/checkout/Paymob/coupon/subscription purchase out of scope.

## 11. Acceptable Demo Limitations

- Public Arabic is demo-ready for checked surfaces, but full Arabic phrase polish remains future work.
- Contact is display-only and uses mail/contact links, not a submitted contact form.
- Subscription-only courses show access/subscription-not-ready messaging; checkout is intentionally not implemented.
- Legacy cart/payment code still exists for Academy LMS compatibility but should not be part of the YounGo client demo flow.
- Playwright browser binaries were downloaded to the local user tool cache to run mobile screenshots; no repo dependency files changed.

## 12. Exact Files Changed

- `application/helpers/youngo_frontend_language_helper.php`
- `application/views/frontend/youngo/index.php`
- `uploads/.htaccess`
- `uploads/lesson_files/htaccess_domain_wise`
- `uploads/lesson_files/videos/htaccess_domain_wise`
- `docs/qa/youngo_client_upload_preflight_review.md`

## 13. Exact Files To Stage

- `application/helpers/youngo_frontend_language_helper.php`
- `application/views/frontend/youngo/index.php`
- `uploads/.htaccess`
- `uploads/lesson_files/htaccess_domain_wise`
- `uploads/lesson_files/videos/htaccess_domain_wise`
- `docs/qa/youngo_client_upload_preflight_review.md`

## 14. Suggested Commit Message

```text
Prepare YounGo client upload preflight
```

## 15. Push/Upload Recommendation

Do not push or upload from this turn.

Recommended sequence:

1. Review the diff.
2. Commit the listed files.
3. Prepare a sanitized DB/media package from the current state.
4. Run the same public smoke on the target server.
5. Push/upload only after owner approval.

