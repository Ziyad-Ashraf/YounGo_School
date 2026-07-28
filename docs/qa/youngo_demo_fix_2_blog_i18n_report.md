# DEMO.FIX.2 Admin Flash Phrase Safety and Blog i18n Report

## 1. Executive Summary

DEMO.FIX.2 completed the Blog/Contact demo content path enough for client screenshots.

The partial DEMO.CONTENT.1 Contact details and Blog categories were preserved. A narrow admin flash-message fix now prevents the targeted Blog/Contact content-write success paths from calling missing legacy phrase keys. A small YounGo-specific `youngo_blog_translations` table was added for minimal English/Arabic Blog text. Four Blog posts were then created through the existing authenticated Blog dashboard/system flow, with English base Blog rows and English/Arabic translation rows.

Public `/blog` and `/blogs` now show real English Blog posts. Public `/ar/blog` shows Arabic translated Blog posts with RTL shell metadata. Contact remains display-only and clean in English and Arabic. No public Contact form, Blog comment form, `/en`, payment/cart/checkout/coupon/Paymob CTA, or broken local Blog images were found in smoke checks.

## 2. Starting Git State

- Branch: `analysis/cms-audit`
- Latest commit: `ff262b7 Document post-XAMPP restore demo baseline`
- Starting dirty scope was limited to partial DEMO.CONTENT.1:
  - `application/views/frontend/youngo/blogs.php`
  - `application/views/frontend/youngo/contact_us.php`
  - `docs/qa/youngo_demo_content_1_blog_contact_report.md`
  - `scripts/phase_2/youngo_demo_content_1_blog_contact_diagnostic.php`

## 3. Backup Path and Metadata

- Backup before DEMO.FIX.2 writes: `D:\Work\YounGo\backups\youngo_school_before_demo_fix_2_blog_i18n_2026_07_19_130055.sql`
- Exists: yes
- Size: `498,118` bytes
- Last modified: `July 19, 2026 1:00:55 PM`
- SHA256: `BA1ECB685EBCFE8F0B1DFB0C6438D98146DEFF404B0366C6CC4206F58F7785D3`

## 4. Existing Partial Content State

Partial DEMO.CONTENT.1 had already:

- Updated Contact settings to YounGo demo values.
- Created Blog categories `Parent Guides`, `Learning Tips`, and `Future Skills`.
- Left `blogs = 0`.
- Inserted two legacy admin flash phrase rows: `contact_information_updated_successfully` and `blog_category_added_successfully`.

Before DEMO.FIX.2 writes, the measured baseline was:

| Table | Count |
|---|---:|
| language | 1460 |
| blogs | 0 |
| blog_category | 3 |
| blog_comments | 0 |
| contact | 0 |
| ci_sessions | 862 |
| payment | 0 |
| youngo_checkout_orders | 0 |
| youngo_course_access | 0 |
| youngo_user_subscriptions | 0 |
| youngo_manual_grants | 0 |
| youngo_coupon_usages | 0 |
| youngo_blog_translations | 0 |

Note: `language` had already drifted from `1449` to `1460` before DEMO.FIX.2 content writes. The newest rows were legacy Blog Add form labels such as `add_blog`, `add_a_new_blog`, `keywords`, `blog_banner`, `blog_thumbnail`, and `mark_as_popular`. They were not deleted or repaired.

## 5. Admin Flash Phrase Safety Fix

Changed targeted Blog/Contact content flash paths in `application/controllers/Admin.php` from missing legacy phrase helper calls to static admin strings:

- Contact info update.
- Blog category add/update/delete.
- Blog post add/update/status/delete.

This avoids new `language` rows from missing content-write flash phrase keys such as `blog_added_successfully` and `blog_updated_successfully`.

## 6. Blog Translation Schema/Table Created

Created additive table:

```text
youngo_blog_translations
```

Columns:

```text
id
blog_id
language_code
title
excerpt
description
slug
created_at
updated_at
```

Indexes:

- Primary key on `id`.
- Unique key `uniq_ybt_blog_language` on `blog_id`, `language_code`.
- Indexes on `language_code` and `slug`.

Rules verified:

- Translation languages are `english` and `arabic`.
- `arabic_translated` was not used.
- The legacy `blogs` table was not altered.

Reproducibility script added during DEMO.FIX.2 review:

- `scripts/phase_2/youngo_demo_fix_2_blog_i18n_schema.php`

The script is idempotent, uses `CREATE TABLE IF NOT EXISTS`, creates only `youngo_blog_translations` when missing, preserves existing data, does not touch `blogs` or `language`, and documents/enforces `english` / `arabic` language values.

## 7. Dashboard Changes Made

Updated:

- `application/views/backend/admin/blog_add.php`
- `application/views/backend/admin/blog_edit.php`
- `application/models/Crud_model.php`

Minimal fields added:

- English excerpt.
- Arabic title.
- Arabic excerpt.
- Arabic description.

The legacy Blog title/category/keywords/description/banner/thumbnail/popular fields remain the base Blog flow.

## 8. Blog Categories State

| ID | Title | Slug |
|---:|---|---|
| 1 | Parent Guides | `parent-guides` |
| 2 | Learning Tips | `learning-tips` |
| 3 | Future Skills | `future-skills` |

## 9. Blog Posts Created

Created through the existing authenticated Blog dashboard/system endpoint:

| ID | English Title | Category | Popular |
|---:|---|---|---:|
| 1 | Helping Children Start Their Coding Journey | Parent Guides | 1 |
| 2 | Why Robotics Builds Confidence | Future Skills | 1 |
| 3 | Screen Time with Purpose | Parent Guides | 0 |
| 4 | Creative Projects That Teach Problem Solving | Learning Tips | 0 |

Each post has one uploaded local thumbnail and one uploaded local banner copied through the dashboard upload path from existing local placeholder image assets.

Created thumbnail files:

- `uploads/blog/thumbnail/7d19203b5cef547e20676bef91235635.png`
- `uploads/blog/thumbnail/fa7be008c5bb2431d8a80cd0baffe858.png`
- `uploads/blog/thumbnail/68e1bca98eea77eb81723605ef4a29fe.png`
- `uploads/blog/thumbnail/8183be10ff3cf8ae609966db0d18b51b.png`

Created banner files:

- `uploads/blog/banner/984a9fa16980f9ce1821c18ef2fe8b79.png`
- `uploads/blog/banner/e2ab77a58574b476d89c377e463cae35.png`
- `uploads/blog/banner/640c00144e98502deef858fe01036162.png`
- `uploads/blog/banner/24cafe3ab2619be087605d31e1c10ebe.png`

## 10. Arabic Blog Translation Behavior

`/ar/blog` now uses `youngo_blog_translations` Arabic rows. If Arabic translation rows are missing for a DB post, that post is skipped on Arabic listing. If no Arabic translated posts remain, the existing Arabic fallback layout still renders.

Current translation rows:

| Language | Count |
|---|---:|
| english | 4 |
| arabic | 4 |

## 11. Contact Preserved/Changed State

Contact settings remained:

- Email: `hello@youngo.academy`
- Phone: `+20 100 123 4567`
- Address: `6th of October City, Giza, Egypt`
- Working hours: `Saturday to Thursday, 9:00 AM - 5:00 PM, Egypt time`

`/ar/contact` still uses local Arabic fallback for address and working hours. The Contact page remains display-only with no public POST form.

## 12. DB Counts Before/After

After implementation, content creation, and public smoke:

| Table | Before | After |
|---|---:|---:|
| language | 1460 | 1460 |
| blogs | 0 | 4 |
| blog_category | 3 | 3 |
| blog_comments | 0 | 0 |
| contact | 0 | 0 |
| ci_sessions | 862 | 875 |
| payment | 0 | 0 |
| youngo_checkout_orders | 0 | 0 |
| youngo_course_access | 0 | 0 |
| youngo_user_subscriptions | 0 | 0 |
| youngo_manual_grants | 0 | 0 |
| youngo_coupon_usages | 0 | 0 |
| youngo_blog_translations | 0 | 8 |

## 13. Language Count Before/After Blog Content Creation

- Before Blog content creation: `language = 1460`
- After Blog content creation: `language = 1460`
- After public GET smoke: `language = 1460`

Result: no new language rows were inserted by Blog creation after the flash-safety fix or by public GET smoke.

## 14. Runtime Smoke Matrix

| Route | Status | Lang | Dir | Result |
|---|---:|---|---|---|
| `/blog` | 200 | en | ltr | Real English Blog posts render |
| `/blogs` | 200 | en | ltr | Real English Blog posts render |
| `/ar/blog` | 200 | ar | rtl | Arabic translated Blog posts render |
| `/contact` | 200 | en | ltr | Contact clean, no form |
| `/home/contact_us` | 200 | en | ltr | Contact clean, no form |
| `/ar/contact` | 200 | ar | rtl | Arabic Contact clean, no form |
| `/` | 200 | en | ltr | Clean |
| `/ar` | 200 | ar | rtl | Clean |
| `/home/courses` | 200 | en | ltr | Clean |
| `/ar/courses` | 200 | ar | rtl | Clean |
| `/home/course/scratch-coding-for-young-creators/1` | 200 | en | ltr | Clean |
| `/ar/course/scratch-coding-for-young-creators/1` | 200 | ar | rtl | Clean |

Smoke checks found no skeleton text, visible repeated `????`, `/en`, public Contact form, Blog comment form, payment/cart/checkout/coupon/Paymob CTA, PHP warning/fatal output, or broken local images.

## 15. Diagnostics Results

Executed diagnostics:

- `php scripts/phase_2/youngo_demo_fix_2_blog_i18n_diagnostic.php`: `PASS_WITH_WARNINGS`; only warning was known Arabic language-table qmark corruption.
- `php scripts/phase_2/youngo_demo_content_1_blog_contact_diagnostic.php`: `PASS_WITH_WARNINGS`; warnings were generic social placeholders hidden by YounGo Contact, legacy flash phrase rows already present, and known Arabic qmark corruption.
- `php scripts/phase_2/youngo_post_restore_demo_baseline_diagnostic.php`: `PASS`; warning only for known Arabic legacy phrase-table corruption.
- `php scripts/phase_2/youngo_demo_phrase_safe_surface_cleanup_diagnostic.php`: `FAIL` only on stale DEMO.FIX.1 strict dirty-scope expectations; phrase safety, `/en`, payment-boundary, contact-form, Blog comment-form, and DB read-only checks passed.
- `php scripts/phase_2/youngo_demo_full_readiness_audit_diagnostic.php`: `PASS` with known Arabic language-table warnings.
- `php scripts/phase_2/youngo_blog_contact_implementation_diagnostic.php`: `PASS` with two stale/strict-scope warnings from older implementation-phase expectations.

DEMO.FIX.2-REVIEW note: the first review smoke of the English Blog detail route inserted one missing legacy phrase row, `blog_details`, raising `language` from `1461` to `1462`. A YounGo-scoped `Blog.php` fix was made so Blog detail page titles use a static string on the YounGo theme. The same route matrix was rerun afterward and `language` stayed `1462 -> 1462`. The inserted row was not deleted or repaired.

## 16. PHP Lint Result

PHP lint passed for:

- `application/controllers/Admin.php`
- `application/controllers/Blog.php`
- `application/models/Crud_model.php`
- `application/views/backend/admin/blog_add.php`
- `application/views/backend/admin/blog_edit.php`
- `application/views/frontend/youngo/blogs.php`
- `application/views/frontend/youngo/contact_us.php`
- `scripts/phase_2/youngo_demo_content_1_blog_contact_diagnostic.php`
- `scripts/phase_2/youngo_demo_fix_2_blog_i18n_diagnostic.php`
- `scripts/phase_2/youngo_demo_fix_2_blog_i18n_schema.php`

## 17. Git Status/Diff Summary

Expected source-controlled dirty scope includes:

- `application/controllers/Admin.php`
- `application/models/Crud_model.php`
- `application/views/backend/admin/blog_add.php`
- `application/views/backend/admin/blog_edit.php`
- `application/views/frontend/youngo/blogs.php`
- `application/views/frontend/youngo/contact_us.php`
- `docs/qa/youngo_demo_content_1_blog_contact_report.md`
- `docs/qa/youngo_demo_fix_2_blog_i18n_report.md`
- `scripts/phase_2/youngo_demo_content_1_blog_contact_diagnostic.php`
- `scripts/phase_2/youngo_demo_fix_2_blog_i18n_diagnostic.php`
- Blog upload image files created through the dashboard flow.

No route, language JSON, payment, checkout, coupon, Paymob, users, roles, grants, plans, enrolment, or Root Admin source changes were made.

Validation:

- `git diff --check`: passed; only Git line-ending warnings for touched PHP files.
- `git diff --name-only`: tracked modified files are `application/controllers/Admin.php`, `application/models/Crud_model.php`, `application/views/backend/admin/blog_add.php`, `application/views/backend/admin/blog_edit.php`, `application/views/frontend/youngo/blogs.php`, and `application/views/frontend/youngo/contact_us.php`.
- `git status --short`: also shows the two reports, two diagnostics, and eight Blog upload image files as untracked.

## 18. Warnings/Limitations

- Existing legacy `language` table corruption remains and was not repaired.
- Existing phrase rows inserted before this fix remain present and were not deleted.
- Blog i18n is intentionally minimal. It supports listing/card translations for demo posts, not a full bilingual Blog CMS with translated categories, detail routes, SEO, scheduling, or advanced editorial workflow.
- Arabic Blog detail routes remain deferred.
- Generic social placeholder settings remain in DB but are hidden from public YounGo Contact output.
- The newly uploaded Blog image files are content assets; keep them with any environment that uses this DB state.

## 19. Final Recommendation

This phase is acceptable for focused review and then commit if validation remains clean. The public Blog/Contact demo surface is now materially better for screenshots, and the Blog creation path did not increase `language` after the flash-safety fix.
