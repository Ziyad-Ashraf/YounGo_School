# DEPLOY.BASELINE.LOCAL_TO_GITHUB.MAIN.1 Report

Date: 2026-07-29

## A. Current Local Root

`D:\Work\YounGo\school`

Root verification passed. Required project markers were present:

- `application/`
- `assets/`
- `system/`
- `index.php`
- `application/config/`
- `application/views/frontend/youngo/`

Owner should keep a separate local ZIP/filesystem backup before relying on GitHub as the new baseline. No large backup artifact was created inside the repository for this phase.

## B. GitHub Remote

`origin`:

```text
https://github.com/Ziyad-Ashraf/YounGo_School.git
```

Remote heads were checked before push and no remote heads were returned.

## C. .gitignore Changes

`.gitignore` was updated to exclude:

- `application/config/database.php`
- `application/config/youngo_paymob.local.php`
- `application/config/youngo_security.local.php`
- `.env`
- `uploads/**`
- `application/logs/*`
- `application/cache/*`
- `backups/`
- `release_packages/`
- `test-results/`
- `docs/qa/screenshots/`
- `*.sql`
- `*.zip`
- `*.tar`
- `*.gz`
- `*.log`

Intentional source exceptions were added for:

- `database/**/*.sql`
- `scripts/**/*.sql`

Safe upload guard exceptions were added for:

- `uploads/**/index.html`
- `uploads/**/.htaccess`
- `uploads/**/htaccess_domain_wise`

These upload exceptions preserve empty writable directory placeholders and upload-directory protection files without tracking runtime uploaded media.

## D. Sensitive / Runtime Exclusions

Sensitive/runtime classes checked:

- Local database config: excluded; `application/config/database.php` was not tracked in the final baseline.
- Paymob local config: excluded; `application/config/youngo_paymob.local.php` was not tracked in the final baseline.
- YounGo security local config: excluded; `application/config/youngo_security.local.php` was not tracked in the final baseline.
- `.env`: excluded; no tracked `.env` was found.
- DB dumps: excluded by `*.sql`; intentional schema/migration source SQL under `database/` and `scripts/` was preserved.
- Backups and release packages: excluded; no tracked files remained in those paths.
- Logs/cache/session output: excluded, with only `application/cache/index.html` and `application/logs/index.html` preserved.
- Runtime uploads: excluded, with only guard/placeholders under `uploads/` preserved.
- QA screenshots and browser artifacts: excluded from the final baseline.
- Payment evidence screenshots: excluded by the `uploads/**` rule and not staged.

Path-only keyword scanning was noisy because the repository contains payment libraries, examples, diagnostics, and language strings. No secret values were printed. The concrete risky tracked local/runtime paths were handled through the index cleanup below.

## E. Files Removed From Git Index Only

The following paths were removed from Git tracking with `git rm --cached` behavior. Local files were not deleted.

```text
docs/qa/screenshots/demo_fix_3/blog_ar_desktop.png
docs/qa/screenshots/demo_fix_3/blog_ar_mobile.png
docs/qa/screenshots/demo_fix_3/blog_en_desktop.png
docs/qa/screenshots/demo_fix_3/blog_en_mobile.png
docs/qa/screenshots/demo_fix_3/contact_ar_desktop.png
docs/qa/screenshots/demo_fix_3/contact_ar_mobile.png
docs/qa/screenshots/demo_fix_3/contact_en_desktop.png
docs/qa/screenshots/demo_fix_3/contact_en_mobile.png
docs/qa/screenshots/demo_fix_3/course_detail_ar_mobile.png
docs/qa/screenshots/demo_fix_3/course_detail_en_mobile.png
docs/qa/screenshots/demo_fix_3/courses_ar_desktop.png
docs/qa/screenshots/demo_fix_3/courses_ar_mobile.png
docs/qa/screenshots/demo_fix_3/courses_en_desktop.png
docs/qa/screenshots/demo_fix_3/courses_en_mobile.png
docs/qa/screenshots/demo_fix_3/home_ar_desktop.png
docs/qa/screenshots/demo_fix_3/home_ar_mobile.png
docs/qa/screenshots/demo_fix_3/home_en_desktop.png
docs/qa/screenshots/demo_fix_3/home_en_mobile.png
docs/qa/screenshots/demo_fix_3/login_ar_mobile.png
docs/qa/screenshots/demo_fix_3/login_en_mobile.png
docs/qa/screenshots/demo_fix_3/mobile_metrics.csv
docs/qa/screenshots/demo_fix_3/mobile_metrics.json
docs/qa/screenshots/demo_fix_3/screenshot_capture.csv
docs/qa/screenshots/demo_fix_3/signup_ar_mobile.png
docs/qa/screenshots/demo_fix_3/signup_en_mobile.png
docs/qa/screenshots/demo_screenshots_2/blog_ar_desktop.png
docs/qa/screenshots/demo_screenshots_2/blog_ar_mobile.png
docs/qa/screenshots/demo_screenshots_2/blog_en_desktop.png
docs/qa/screenshots/demo_screenshots_2/blog_en_mobile.png
docs/qa/screenshots/demo_screenshots_2/contact_ar_desktop.png
docs/qa/screenshots/demo_screenshots_2/contact_ar_mobile.png
docs/qa/screenshots/demo_screenshots_2/contact_en_desktop.png
docs/qa/screenshots/demo_screenshots_2/contact_en_mobile.png
docs/qa/screenshots/demo_screenshots_2/courses_ar_desktop.png
docs/qa/screenshots/demo_screenshots_2/courses_ar_mobile.png
docs/qa/screenshots/demo_screenshots_2/courses_en_desktop.png
docs/qa/screenshots/demo_screenshots_2/courses_en_mobile.png
docs/qa/screenshots/demo_screenshots_2/home_ar_desktop.png
docs/qa/screenshots/demo_screenshots_2/home_ar_mobile.png
docs/qa/screenshots/demo_screenshots_2/home_en_desktop.png
docs/qa/screenshots/demo_screenshots_2/home_en_mobile.png
docs/qa/screenshots/demo_screenshots_2/login_ar_mobile.png
docs/qa/screenshots/demo_screenshots_2/login_en_mobile.png
docs/qa/screenshots/demo_screenshots_2/screenshot_qa_matrix.csv
docs/qa/screenshots/demo_screenshots_2/screenshot_qa_matrix.json
docs/qa/screenshots/demo_screenshots_2/signup_ar_mobile.png
docs/qa/screenshots/demo_screenshots_2/signup_en_mobile.png
uploads/blog/banner/24cafe3ab2619be087605d31e1c10ebe.png
uploads/blog/banner/640c00144e98502deef858fe01036162.png
uploads/blog/banner/984a9fa16980f9ce1821c18ef2fe8b79.png
uploads/blog/banner/e2ab77a58574b476d89c377e463cae35.png
uploads/blog/banner/placeholder.png
uploads/blog/page-banner/blog-page.png
uploads/blog/thumbnail/68e1bca98eea77eb81723605ef4a29fe.png
uploads/blog/thumbnail/7d19203b5cef547e20676bef91235635.png
uploads/blog/thumbnail/8183be10ff3cf8ae609966db0d18b51b.png
uploads/blog/thumbnail/fa7be008c5bb2431d8a80cd0baffe858.png
uploads/blog/thumbnail/placeholder.png
uploads/install.sql
uploads/seo-og-images/placeholder.png
uploads/system/87320168ec9dfc237178484e3c3a3bc2.png
uploads/system/about_us.png
uploads/system/bcbcf8c3bf769e9c83157f9e8603e031.png
uploads/system/c0d1a45e2391ca3e92684b79affa7dc9.png
uploads/system/cc01090a65650c8670a1d8b13a0a6fc2.png
uploads/system/course_page_banner.png
uploads/system/ebook_page_banner.png
uploads/system/favicon.png
uploads/system/forgot_password.png
uploads/system/home-1-1.png
uploads/system/home-1.png
uploads/system/home-2.png
uploads/system/home-3.png
uploads/system/home-4.png
uploads/system/home-5.png
uploads/system/home-6.png
uploads/system/logo-dark.png
uploads/system/logo-light-sm.png
uploads/system/logo-light.png
uploads/system/shopping_cart.png
uploads/system/sign_in.png
uploads/system/sign_up.png
uploads/system/terms_and_condition.png
uploads/thumbnails/category_thumbnails/5387928297ef99918db2235a6a578235.jpg
uploads/thumbnails/category_thumbnails/8dc45f3e1f8f2f52778088016d6736c6.jpg
uploads/thumbnails/category_thumbnails/category-thumbnail.png
uploads/thumbnails/category_thumbnails/dc37f30f76454facc92ce0d0ace3c6d9.jpg
uploads/thumbnails/category_thumbnails/youngo-category-coding-for-kids.jpg
uploads/thumbnails/category_thumbnails/youngo-category-creative-arts.jpg
uploads/thumbnails/category_thumbnails/youngo-category-life-skills.jpg
uploads/thumbnails/category_thumbnails/youngo-category-math-adventures.jpg
uploads/thumbnails/category_thumbnails/youngo-category-reading-storytelling.jpg
uploads/thumbnails/category_thumbnails/youngo-category-science-explorers.jpg
uploads/thumbnails/course_thumbnails/course-thumbnail.png
uploads/thumbnails/course_thumbnails/course_thumbnail_default-new_11782286733.jpg
uploads/thumbnails/course_thumbnails/course_thumbnail_youngo_11784421120.jpg
uploads/thumbnails/course_thumbnails/course_thumbnail_youngo_21784420052.jpg
uploads/thumbnails/course_thumbnails/course_thumbnail_youngo_31784421120.jpg
uploads/thumbnails/course_thumbnails/course_thumbnail_youngo_41784421120.jpg
uploads/thumbnails/course_thumbnails/course_thumbnail_youngo_51784420052.jpg
uploads/thumbnails/course_thumbnails/course_thumbnail_youngo_61784421121.jpg
uploads/thumbnails/course_thumbnails/course_thumbnail_youngo_61784436100.jpg
uploads/thumbnails/course_thumbnails/course_thumbnail_youngo_91784436176.jpg
uploads/thumbnails/course_thumbnails/optimized/course_thumbnail_default-new_1.jpg
uploads/thumbnails/course_thumbnails/optimized/course_thumbnail_default-new_11782286733.jpg
uploads/thumbnails/course_thumbnails/placeholder.png
uploads/thumbnails/lesson_thumbnails/lesson-thumbnail.png
uploads/user_image/placeholder.png
```

Removed-from-index count: `108`.

## F. Main Branch Commit Hash

```text
440e992c02c7ff72cd4c640563b3873b23ec0880
```

Commit message:

```text
Create clean YounGo production baseline
```

This is a root commit on a fresh local `main` history.

## G. Main Push Result

Success.

```text
main -> origin/main
```

No force push was used.

## H. Development Branch Creation / Push Result

Success.

`development` was created locally from `main` after the successful `main` push and pushed to:

```text
origin/development
```

## I. Remaining Risks

- GitHub source now excludes runtime upload media. Server/cPanel work must provide a curated media/uploads package separately.
- `application/config/database.php` and local payment/security configs are intentionally excluded. Server/cPanel setup must create environment-specific config securely.
- No database export/import, database mutation, or cPanel deployment happened in this phase.
- Intentional source SQL under `database/` and `scripts/` remains tracked as migration/diagnostic source, not as DB dumps.
- Production security still requires cPanel/server review: credentials, encryption key, HTTPS/cookie settings, writable permissions, disabled or sandbox-only payments, SMTP, and sanitized DB import.
- Windows line-ending normalization warnings appeared during staging; no functional source changes were made for line endings in this phase.

## J. Next Recommended Phase For cPanel Linking

Recommended next phase:

```text
DEPLOY.CPANEL.LINK_AND_CONFIG.1
```

Scope should include cloning/pulling the new `main` on cPanel, creating server-only config files, preparing/importing a sanitized database package, uploading curated media/runtime folders, setting writable permissions, disabling or sandboxing payments, and running a server smoke test. Do not enable real payments until the payment/Paymob phase is approved and QA-tested.
