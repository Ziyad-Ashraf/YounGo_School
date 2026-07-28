# YounGo Client Package 2 Restore Test Report

Phase: `CLIENT.PACKAGE.2 - Local Restore Test From cPanel Package`

Date: 2026-07-20

Branch: `analysis/cms-audit`

Runtime base tested: `http://school.local/`

Deployment target: cPanel hosting

## 1. Executive Result

The CLIENT.PACKAGE.1 cPanel package was restored into a new isolated local database and the public frontend was smoke-tested against that restored database.

Result: restore/runtime test passed for the public demo surfaces.

The sanitized SQL imported successfully, required YounGo tables and demo content restored, Blog English/Arabic content restored, course `43` was absent, DB-referenced media files were present, and all requested public pages returned HTTP 200 with no broken local images in the restore runtime smoke.

Do not upload yet without owner review of the two hygiene/admin readiness items:

- The restored `users.sessions` column contains stale session-token arrays for 2 user rows even though `ci_sessions` imports empty.
- A normal Admin account exists, but the current client admin candidate has only a `course` permission row, not the broad operational access expected for the client demo.

No commit, push, deployment, cPanel upload, main local DB modification, Root Admin credential change, source feature change, payment/checkout/coupon change, or language JSON change was performed.

## 2. Git State

Starting checks:

- `git branch --show-current`: `analysis/cms-audit`
- Latest commit: `2337d79 Prepare YounGo cPanel client package`
- Starting `git status --short`: clean

Temporary runtime config switching was restored after the smoke test. The `application/config/database.php` SHA256 before and after the test matched:

```text
F26EC021DC62A959B2CFC1E4533B7C84D2F31C16CB5D4C6F5A97E995D8C0F2C0
```

## 3. Package Tested

Package folder:

```text
D:\Work\YounGo\release_packages\youngo_client_demo_cpanel_2026_07_20_162623
```

Sanitized SQL:

```text
D:\Work\YounGo\release_packages\youngo_client_demo_cpanel_2026_07_20_162623\database\youngo_client_demo_cpanel_sanitized_2026_07_20_162623.sql
```

SQL metadata:

- Size: `532821` bytes
- SHA256: `23af47aa17072293d721c49884afd3d05b2e412f8f850b47e58ce414c9322726`

Restore-test package artifacts created:

- `checks/restore_import_result.json`
- `checks/restore_db_validation.json`
- `checks/restore_db_media_check.csv`
- `checks/restore_runtime_smoke.csv`
- `checks/restore_runtime_smoke_images.csv`
- `checks/restore_runtime_smoke_summary.json`
- `checks/restore_config_switch_result.json`
- `checks/restore_uploads_install_sql_http_check.json`
- `checks/RESTORE_TEST_RESULT.txt`

## 4. Restore-Test DB Name

Restore-test database:

```text
youngo_client_restore_test_20260720
```

The sanitized SQL was not imported into the main local `youngo_school` database.

## 5. Import Result

Import result: passed.

- Database created: yes
- Import success: yes
- Base tables imported: `64`
- Import errors: none

## 6. DB Validation Result

Required tables restored:

- `users`
- `role`
- `permissions`
- `settings`
- `frontend_settings`
- `currency`
- `category`
- `course`
- `section`
- `lesson`
- `blogs`
- `blog_category`
- `youngo_blog_translations`
- `youngo_course_translations`
- `youngo_category_translations`
- `youngo_section_translations`
- `youngo_lesson_translations`
- Phase 2 YounGo access/subscription/manual grant/order/coupon tables

Selected restored counts:

| Table / check | Restored count |
| --- | ---: |
| `users` | 7 |
| `role` | 2 |
| `permissions` | 2 |
| `settings` | 66 |
| `frontend_settings` | 50 |
| `currency` | 164 |
| `category` | 12 |
| `course` | 7 |
| Active courses | 5 |
| Private courses | 2 |
| `section` | 20 |
| `lesson` | 40 |
| `blogs` | 4 |
| `blog_category` | 3 |
| `youngo_blog_translations` | 8 |
| `youngo_course_translations` | 14 |
| `youngo_category_translations` | 24 |
| `youngo_section_translations` | 34 |
| `youngo_lesson_translations` | 68 |

Settings/content validation:

- `settings.system_currency`: `EGP`
- `settings.currency_position`: `left`
- `settings.language`: `english`
- `settings.system_name`: `YounGo`
- `frontend_settings.theme`: `youngo`
- `frontend_settings.youngo_homepage_content`: present
- `frontend_settings.contact_info`: present
- `frontend_settings.blog_page_banner`: `blog-page.png`
- Settings scan for `C:\Users`, `AppData`, `/mnt/data`, `D:\Work`, `school.local`, and `localhost`: no matches

## 7. Sanitization Validation

Sanitization result: mostly passed, with one hygiene risk to fix before upload.

Passed:

- `ci_sessions` imported with 0 rows.
- `log`, `payment`, `payout`, `enrol`, `watch_histories`, `watched_duration`, `quiz_results`, `message`, `message_thread`, `notifications`, `contact`, and `coupons` imported with 0 rows where present.
- `youngo_course_access`, `youngo_user_subscriptions`, `youngo_manual_grants`, `youngo_checkout_orders`, `youngo_coupon_usages`, `youngo_coupon_subscription_plans`, and `youngo_coupon_courses` imported with 0 rows.
- Payment gateways restored as disabled/redacted:
  - rows: `15`
  - active rows: `0`
  - non-empty gateway keys rows: `0`
  - non-empty model rows: `0`
  - test-mode enabled rows: `0`
- Credential-like settings checked in the validation were empty, including purchase code, SMTP fields, and Academy Cloud token.

Open hygiene risk:

- `users.sessions` still contains non-empty stale session arrays for 2 user rows. These are likely inert because `ci_sessions` is empty, but they should be cleared in the next sanitized export/package iteration before cPanel upload.

## 8. Course 43 Handling

Course `43` handling passed.

- `course.id = 43` count after restore: `0`
- Package metadata recorded course `43` as excluded.
- No related section or lesson rows for course `43` were included.

## 9. Blog/Translation Validation

Blog restore passed.

- `blogs`: `4`
- `blog_category`: `3`
- `youngo_blog_translations`: `8`
- English Blog translation rows: `4`
- Arabic Blog translation rows: `4`
- `/blog` displayed real English Blog content in the restore runtime smoke.
- `/ar/blog` displayed Arabic content in the restore runtime smoke.

## 10. Media Consistency Validation

Media consistency passed for DB-referenced public demo media.

- DB-referenced media rows checked: `23`
- Missing DB-referenced media rows: `0`
- `assets/frontend/youngo/images/` exists and contains `35` files.
- `uploads/.htaccess` exists.
- `uploads/install.sql` exists locally but returned HTTP `403 Forbidden`, confirming the local uploads guard blocks direct access.

Required upload folders/files remain:

- `assets/frontend/youngo/images/`
- `uploads/.htaccess`
- `uploads/blog/page-banner/blog-page.png`
- `uploads/blog/banner/`
- `uploads/blog/thumbnail/`
- `uploads/thumbnails/category_thumbnails/`
- `uploads/thumbnails/course_thumbnails/`
- `uploads/system/`
- `uploads/user_image/placeholder.png`

Files documented as risky/exclude-from-upload:

- `uploads/install.sql`
- `.DS_Store` files
- SQL dumps
- secrets
- temp/cache/local-only files

## 11. Runtime Smoke Result

Runtime smoke method:

- Backed up `application/config/database.php` to the package `checks/` folder.
- Temporarily switched only the local database name from `youngo_school` to `youngo_client_restore_test_20260720`.
- Ran HTTP smoke against `http://school.local/`.
- Restored the original config in a `finally` block.
- Confirmed config hash before/after matched.

Smoke result: passed.

| Path | HTTP | Result |
| --- | ---: | --- |
| `/` | 200 | pass |
| `/ar` | 200 | pass |
| `/home/courses` | 200 | pass |
| `/ar/courses` | 200 | pass |
| `/home/course/scratch-coding-for-young-creators/1` | 200 | pass |
| `/ar/course/scratch-coding-for-young-creators/1` | 200 | pass |
| `/blog` | 200 | pass |
| `/ar/blog` | 200 | pass |
| `/contact` | 200 | pass |
| `/ar/contact` | 200 | pass |
| `/login` | 200 | pass |
| `/ar/login` | 200 | pass |
| `/sign_up` | 200 | pass |
| `/ar/sign-up` | 200 | pass |

Smoke checks:

- Pages checked: `14`
- Failed pages: `0`
- Local images checked: `100`
- Broken local images: `0`
- No PHP error patterns detected.
- No repeated `????` detected.
- No `/en` links detected.
- No skeleton-loader placeholders detected.
- No payment/cart/checkout/Paymob/coupon CTA regression detected.
- Contact pages remained display-only.
- Blog English and Arabic content was visible.

Note: direct HTTP smoke was run in this phase. Separate visual mobile viewport screenshots were not rerun in this restore-test phase.

## 12. Admin Account Readiness

Admin readiness: partially ready, owner review required before client upload.

Read-only restored DB check:

- Root Admin row exists and remains present as `users.id = 1`.
- Normal Admin role users exist:
  - `users.id = 7`, active, email `client@gmail.com`
  - `users.id = 9`, active, email contains local-only `school.local`
- Permission rows:
  - Admin `7`: `["course"]`
  - Admin `9`: `[]`

Interpretation:

- The client should use a normal Admin account, not Root Admin.
- A normal Admin candidate exists, but the current permission row for the client admin candidate is narrow and does not yet match the desired broad operational client-demo scope.
- Admin `9` contains a local-only email and should not be used for the client demo unless reviewed/sanitized.
- Passwords were not inspected, changed, printed, or reported.

Before upload, confirm or create a normal Admin account with broad operational access for content, courses, categories, lessons, Blog, contact info, homepage/frontend settings, and normal website management while keeping Root/core-only areas out of the demo flow.

## 13. Config Switching Method Used and Restored

Temporary config switch used: yes.

Config file:

```text
application/config/database.php
```

Switch details:

- Original DB target: `youngo_school`
- Temporary DB target: `youngo_client_restore_test_20260720`
- Original config backup: package `checks/database.php.before_restore_test.bak`
- Config hash before: `F26EC021DC62A959B2CFC1E4533B7C84D2F31C16CB5D4C6F5A97E995D8C0F2C0`
- Config hash after: `F26EC021DC62A959B2CFC1E4533B7C84D2F31C16CB5D4C6F5A97E995D8C0F2C0`
- Restored: yes

Runtime smoke created normal `ci_sessions` rows in the isolated restore-test DB only after the import validation. The package SQL itself still imports `ci_sessions` empty.

## 14. Blockers/Open Risks

Blockers before client upload:

- Confirm/fix normal Admin account readiness. The restored DB has a normal client admin candidate, but its permission row is currently only `["course"]`, which is not broad enough for the intended client dashboard demo.
- Clear stale `users.sessions` values in the next sanitized DB export/package iteration.
- Exclude local-only admin/demo account data such as the `school.local` admin email unless the owner explicitly wants it retained.

Open risks:

- Mobile visual viewport was not rerun during this restore-test phase.
- The cPanel environment may differ in PHP version, Apache rewrite behavior, document root, and file permissions.
- Media package is currently manifest-based; the deployer still needs to copy the required folders/files exactly.
- Payment/Paymob/checkout/coupon/subscription purchase flows remain deferred and should not be demonstrated as ready.
- Media Library remains deferred; image readiness depends on the curated files and DB references validated here.

## 15. Final Recommendation

Do not upload this exact package to cPanel until the admin/hygiene items are reviewed.

The sanitized SQL and current source/media are sufficient to run the public YounGo demo after restore, based on the isolated DB import and runtime smoke. The next package iteration should regenerate the sanitized SQL after clearing stale user session fields and confirming a broad normal Admin demo account.

## 16. Next Phase

Recommended next phase:

```text
CLIENT.PACKAGE.1B - Admin Account and Sanitized Export Hygiene Pass
```

Scope:

- Confirm the normal client Admin account and broad operational permission scope.
- Exclude or sanitize local-only admin/demo accounts.
- Clear `users.sessions` in the sanitized export.
- Regenerate the sanitized SQL/package metadata.
- Rerun restore import and targeted public smoke.

After that passes:

```text
CLIENT.UPLOAD.1 - Server Upload Checklist / cPanel Deployment Runbook
CLIENT.SMOKE.1 - Post-Upload Public Smoke Test
```
