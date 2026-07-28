# DEMO.CONTENT.1 Blog and Contact Demo Content Setup Report

## 1. Executive Summary

DEMO.CONTENT.1 partially improved the visible demo content surface.

Contact settings were updated through the existing Academy LMS admin dashboard flow and now render clean YounGo demo contact details on English public pages. Because the legacy contact settings are shared/non-bilingual, `/ar/contact` uses a small YounGo-only local Arabic fallback for address and working hours while preserving the shared email/phone.

Three Blog categories were created through the existing Blog category dashboard flow. Blog post creation was not completed because the current admin Blog add endpoint would create another missing legacy admin flash phrase row in the `language` table. After the first Contact/category dashboard writes, the language table increased from `1447` to `1449` because `contact_information_updated_successfully` and `blog_category_added_successfully` were inserted by legacy `get_phrase()` admin flash messages. These rows were not repaired or deleted.

Public GET smoke after the source adjustments was clean: the tested public routes returned 200, used expected `lang`/`dir`, had no visible repeated `????`, no skeleton copy, no `/en` links, no visible payment/cart/checkout/coupon/Paymob CTA, no public Contact form, and no broken local images. Public GET checks did not increase the language table.

## 2. Starting Git State

- Branch: `analysis/cms-audit`
- Latest commit: `ff262b7 Document post-XAMPP restore demo baseline`
- Recent expected commits present: `Document post-XAMPP restore demo baseline`, `Make YounGo demo frontend phrases safe`, `Add YounGo demo readiness audit`, `Polish YounGo blog fallback layout`, `Refine YounGo subscription and EGP price display`, `Add YounGo blog and contact demo pages`
- Starting worktree: clean

## 3. Backup Path and Metadata

- Backup created before content writes: `D:\Work\YounGo\backups\youngo_school_before_demo_content_1_blog_contact_2026_07_19_122145.sql`
- Exists: yes
- Size: `489,286` bytes
- Last modified: `July 19, 2026 12:21:45 PM`
- SHA256: `CA847D7CCFBF88DDEF1A9578B8CC5F8559C309A5CFE224644CE65871A3DF0982`
- Notes: SQL dump content for `youngo_school` verified when the backup was created.

## 4. Data Writes Performed Through Dashboard/System

Writes were performed through existing authenticated Academy LMS dashboard endpoints only:

- POST `/admin/frontend_settings/contact_info`
- POST `/admin/blog_category/add`

No direct SQL INSERT/UPDATE/DELETE/ALTER/DROP/TRUNCATE was run.

The attempted Blog post creation through POST `/admin/blog/add` did not persist any rows. It returned curl status `000`; DB verification showed `blogs = 0` and no uploaded Blog image files were created. A retry was intentionally skipped after verifying that `blog_added_successfully` is missing from `language` and the endpoint would create another phrase row.

## 5. Contact Details Before/After

Before:

- Email: `admin@example.com`, `system@example.com`
- Phone: `609-502-5899`, `345-444-2122`
- Address: `455 Wolff Streets Suite 674`
- Office hours: `10:00 AM - 6:00 PM`
- Generic social placeholders: `https://facebook.com`, `https://twitter.com`

After:

- Email: `hello@youngo.academy`
- Phone / WhatsApp: `+20 100 123 4567`
- Address: `6th of October City, Giza, Egypt`
- Working hours: `Saturday to Thursday, 9:00 AM - 5:00 PM, Egypt time`

The generic Facebook/Twitter values still exist in DB, but the YounGo Contact view filters those generic placeholder URLs from the public page.

## 6. Blog Categories/Posts Created or Reason Skipped

Created categories:

| ID | Title | Slug |
|---:|---|---|
| 1 | Parent Guides | `parent-guides` |
| 2 | Learning Tips | `learning-tips` |
| 3 | Future Skills | `future-skills` |

Blog posts created: `0`.

Reason skipped: the existing `Admin::blog('add')` path sets the flash message with the missing phrase key `blog_added_successfully`. Running it successfully would insert another row into `language`, which conflicts with this phase's language-table guardrail.

## 7. Arabic Blog Behavior Decision

The existing Blog system has no bilingual Blog fields. A small YounGo-only source adjustment keeps `/ar/blog` fallback/editorial-card driven even when English Blog DB rows exist later. This prevents Arabic screenshots from showing English-only Blog rows until a proper bilingual Blog model/content path is implemented.

English `/blog` and `/blogs` remain DB-driven when rows exist. With `blogs = 0`, they render the polished fallback layout.

## 8. Blog Image Handling

No new Blog uploaded images were created because Blog posts were skipped. Existing local fallback images render without broken local image responses:

- `assets/frontend/youngo/images/blog-family.webp`
- `assets/frontend/youngo/images/blog-coding.webp`
- `assets/frontend/youngo/images/blog-science.webp`
- course fallback images used by the existing editorial layout

The existing dashboard placeholders remain available:

- `uploads/blog/thumbnail/placeholder.png`
- `uploads/blog/banner/placeholder.png`

## 9. DB Counts Before/After

Baseline before content writes:

| Table | Before |
|---|---:|
| blogs | 0 |
| blog_category | 0 |
| blog_comments | 0 |
| contact | 0 |
| language | 1447 |
| ci_sessions | 838 |
| payment | 0 |
| youngo_checkout_orders | 0 |

After dashboard writes and public smoke:

| Table | After |
|---|---:|
| blogs | 0 |
| blog_category | 3 |
| blog_comments | 0 |
| contact | 0 |
| language | 1449 |
| ci_sessions | 858 |
| payment | 0 |
| youngo_checkout_orders | 0 |
| youngo_course_access | 0 |
| youngo_user_subscriptions | 0 |
| youngo_manual_grants | 0 |
| youngo_coupon_usages | 0 |

Language table note: the increase to `1449` came from admin dashboard flash phrase side effects during content writes, not from public GET smoke.

## 10. Runtime Smoke Matrix

| Route | Status | Lang | Dir | Result |
|---|---:|---|---|---|
| `/blog` | 200 | en | ltr | Clean fallback Blog; no broken local images |
| `/blogs` | 200 | en | ltr | Clean fallback Blog; no broken local images |
| `/ar/blog` | 200 | ar | rtl | Clean Arabic fallback/editorial Blog; no English DB posts |
| `/contact` | 200 | en | ltr | Clean demo contact details; no public form |
| `/home/contact_us` | 200 | en | ltr | Clean demo contact details; no public form |
| `/ar/contact` | 200 | ar | rtl | Clean Arabic local fallback details; no public form |
| `/` | 200 | en | ltr | Clean |
| `/ar` | 200 | ar | rtl | Clean |
| `/home/courses` | 200 | en | ltr | Clean |
| `/ar/courses` | 200 | ar | rtl | Clean |

Checks performed per route: no repeated visible `????`, no skeleton text, no `/en`, no visible Add to cart / Buy Now / Checkout / Paymob / coupon CTA, no PHP warning/fatal output, and no broken local images.

## 11. Language/Session Count Before-After GET

Before public GET smoke:

- `language = 1449`
- `ci_sessions = 848`

After public GET smoke:

- `language = 1449`
- `ci_sessions = 858`

Result: public GET did not insert language rows. Normal `ci_sessions` growth occurred.

## 12. Diagnostics Results

Executed diagnostics:

- `php scripts/phase_2/youngo_demo_content_1_blog_contact_diagnostic.php`: `PASS_WITH_WARNINGS`
- `php scripts/phase_2/youngo_post_restore_demo_baseline_diagnostic.php`: `PASS` with one known Arabic language-table corruption warning
- `php scripts/phase_2/youngo_demo_phrase_safe_surface_cleanup_diagnostic.php`: `FAIL` only on stale strict dirty-scope expectations from DEMO.FIX.1; it otherwise passed phrase-helper, `/en`, payment-boundary, contact-form, Blog comment-form, and read-only DB checks
- `php scripts/phase_2/youngo_demo_full_readiness_audit_diagnostic.php`: `PASS` with known Arabic legacy phrase-table warnings
- `php scripts/phase_2/youngo_blog_contact_implementation_diagnostic.php`: `PASS` with two known stale/strict-scope warnings

New diagnostic warning details:

- Blog rows are still `0`; Blog post creation was skipped because the current dashboard path would create a missing legacy admin phrase row.
- Generic `facebook` and `twitter` placeholders remain in DB but are hidden from the public YounGo Contact page.
- Legacy admin flash phrase rows now present: `contact_information_updated_successfully`, `blog_category_added_successfully`.
- Known Arabic legacy phrase-table corruption remains and was reported only.

## 13. PHP Lint Result

- `php -l scripts/phase_2/youngo_demo_content_1_blog_contact_diagnostic.php`: pass
- `php -l application/views/frontend/youngo/blogs.php`: pass
- `php -l application/views/frontend/youngo/contact_us.php`: pass

## 14. Git Status/Diff Summary

Expected modified/created files for this phase:

- `application/views/frontend/youngo/blogs.php`
- `application/views/frontend/youngo/contact_us.php`
- `scripts/phase_2/youngo_demo_content_1_blog_contact_diagnostic.php`
- `docs/qa/youngo_demo_content_1_blog_contact_report.md`

No route, language JSON, backend/admin source, controller, model, payment, checkout, coupon, Paymob, database/schema, or default theme source files were modified.

Final validation commands are run after this report update:

- `git diff --check`
- `git status --short`
- `git diff --name-only`

## 15. Warnings/Limitations

- Two legacy admin flash phrase rows were inserted during dashboard content writes: `contact_information_updated_successfully` and `blog_category_added_successfully`.
- The inserted language rows were not deleted or repaired because language phrase deletion/repair and direct SQL writes are out of scope.
- Blog rows remain `0`; English Blog still uses the polished fallback layout.
- `/ar/blog` is intentionally fallback-driven until Blog has a proper bilingual content model.
- Existing generic social settings remain in DB but are hidden from YounGo Contact public output.
- Contact remains display-only. No contact form was enabled or submitted.
- Blog comments remain disabled/no public comment form in the YounGo Blog detail view.

## 16. Final Recommendation

DEMO.CONTENT.1 was superseded by DEMO.FIX.2. DEMO.FIX.2 added targeted admin flash phrase safety, created the minimal YounGo Blog translation table, and created four English/Arabic Blog posts through the dashboard/system flow without increasing the `language` count from the DEMO.FIX.2 baseline.
