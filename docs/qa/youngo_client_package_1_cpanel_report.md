# YounGo Client Package 1 cPanel Report

Phase: `CLIENT.PACKAGE.1 - Sanitized DB and Media Package for cPanel`

Date: 2026-07-20

Branch: `analysis/cms-audit`

Runtime base checked: `http://school.local/`

Deployment target: cPanel hosting

## 1. Executive Result

CLIENT.PACKAGE.1 produced a practical cPanel upload package outside the repo:

- Sanitized SQL export candidate.
- DB export metadata and table summary.
- Media/upload manifest with hashes.
- Risky upload file list.
- cPanel deployment notes and source upload checklist.
- Local source/path scan result.
- Quick public smoke result.

No push, deployment, upload, commit, source fix, live DB write, user/role/Root Admin change, payment/Paymob/checkout/coupon change, or language JSON change was performed.

The package is ready for owner review and a separate local restore-test phase. It should not be uploaded to cPanel until `CLIENT.PACKAGE.2` restore testing passes.

## 2. Git State

Starting checks:

- `git branch --show-current`: `analysis/cms-audit`
- Latest commits included:
  - `8357c24 Reconcile YounGo client upload plan`
  - `7222c37 Prepare YounGo client upload preflight`
  - `7a04e30 Polish YounGo category images and homepage navigation`
- Starting `git status --short`: clean

## 3. Package Folder Path

Package folder:

```text
D:\Work\YounGo\release_packages\youngo_client_demo_cpanel_2026_07_20_162623
```

Package subfolders created:

- `database/`
- `media_manifest/`
- `deployment_notes/`
- `checks/`

Package files were created outside the repo. No large package archive was added to the repository.

## 4. DB Export Path and Metadata

Sanitized SQL export:

```text
D:\Work\YounGo\release_packages\youngo_client_demo_cpanel_2026_07_20_162623\database\youngo_client_demo_cpanel_sanitized_2026_07_20_162623.sql
```

Metadata:

- Size: `532821` bytes
- SHA256: `23AF47AA17072293D721C49884AFD3D05B2E412F8F850B47E58CE414C9322726`
- Source database: `youngo_school`
- Base tables exported with structure: `64`
- Exported data rows: `2140`
- Live DB modified: no
- Import not tested in this phase; restore/import validation is the next phase.

Supporting DB package files:

- `database/DB_EXPORT_TABLE_SUMMARY.csv`
- `deployment_notes/DB_EXPORT_NOTES.txt`
- `checks/db_export_metadata.json`
- `checks/generate_sanitized_export.php`

## 5. Sanitization Actions

The export was generated from read-only DB queries and sanitized in the SQL file only.

Actions applied:

- Exported all base table structures.
- Exported current demo content, Blog posts, translations, frontend settings, EGP settings, and YounGo schema tables needed by current code.
- Excluded course `43` from the `course` table.
- Excluded `youngo_course_translations` rows for course `43`.
- Course `43` had no related section or lesson rows to exclude.
- Exported `ci_sessions` with structure only and no rows.
- Exported runtime/test/payment/access tables listed below with structure only and no rows.
- Disabled payment gateway status/enabled fields in the export where present.
- Redacted credential-like payment gateway fields.
- Redacted credential-like `settings` values, including purchase/API/SMTP/payment/cloud key values.
- Normalized exported `course.thumbnail` values to existing runtime `course.last_modified` thumbnail filenames for courses `1`, `2`, `3`, `4`, `5`, `6`, and `9`.

Important note:

- `users`, `role`, and `permissions` were exported as current DB state. The owner should confirm/rotate the normal cPanel Admin credentials after import. The client should use a normal Admin account, not Root Admin.

## 6. Course ID 43 Handling

Course `43` exists in the live local DB:

```text
YounGo QA Course Render Test
```

Handling:

- Excluded from sanitized SQL export.
- No related section rows found.
- No related lesson rows found.
- Runtime thumbnail for course `43` is missing locally, but it is not included in the sanitized export.

## 7. Tables Included, Excluded, or Truncated

All `64` base table structures were included.

Key tables exported with data include:

- `users`
- `role`
- `permissions`
- `settings`
- `frontend_settings`
- `currency`
- `category`
- `course` minus course `43`
- `section`
- `lesson`
- `blogs`
- `blog_category`
- `youngo_blog_translations`
- `youngo_course_translations` minus course `43` rows
- `youngo_category_translations`
- `youngo_section_translations`
- `youngo_lesson_translations`
- `youngo_subscription_plans`
- `payment_gateways` sanitized/disabled

Structure-only/truncated tables in the export:

- `ci_sessions`
- `log`
- `payment`
- `payout`
- `enrol`
- `watch_histories`
- `watched_duration`
- `quiz_results`
- `message`
- `message_thread`
- `notifications`
- `contact`
- `coupons`
- `youngo_course_access`
- `youngo_user_subscriptions`
- `youngo_manual_grants`
- `youngo_checkout_orders`
- `youngo_coupon_usages`
- `youngo_coupon_subscription_plans`
- `youngo_coupon_courses`

Selected table summary:

| Table | Source Rows | Exported Rows | Mode |
| --- | ---: | ---: | --- |
| `course` | 8 | 7 | data |
| `section` | 20 | 20 | data |
| `lesson` | 40 | 40 | data |
| `blogs` | 4 | 4 | data |
| `blog_category` | 3 | 3 | data |
| `youngo_blog_translations` | 8 | 8 | data |
| `youngo_course_translations` | 16 | 14 | data |
| `youngo_category_translations` | 24 | 24 | data |
| `youngo_section_translations` | 34 | 34 | data |
| `youngo_lesson_translations` | 68 | 68 | data |
| `ci_sessions` | 1430 | 0 | structure only |
| `enrol` | 1 | 0 | structure only |
| `payment` | 0 | 0 | structure only |

## 8. Media Manifest Summary

Media manifest:

```text
D:\Work\YounGo\release_packages\youngo_client_demo_cpanel_2026_07_20_162623\media_manifest\MEDIA_MANIFEST.csv
```

Summary:

- Manifest rows: `79`
- Required missing files: `0`
- DB-referenced media check rows: `28`
- Blocking missing DB media: `0`
- Risky upload files found: `14`

Required media coverage includes:

- `assets/frontend/youngo/images/` recursively
- `uploads/.htaccess`
- `uploads/system/` logo/favicon files
- `uploads/user_image/placeholder.png`
- `uploads/blog/page-banner/blog-page.png`
- `uploads/blog/banner/`
- `uploads/blog/thumbnail/`
- `uploads/thumbnails/category_thumbnails/`
- `uploads/thumbnails/course_thumbnails/`

Supporting media files:

- `media_manifest/MEDIA_MANIFEST.txt`
- `media_manifest/DB_REFERENCED_MEDIA_CHECK.csv`
- `media_manifest/RISKY_UPLOAD_FILES.csv`
- `checks/media_manifest_summary.json`

## 9. Missing or Risky Files

Missing required files:

- None.

Risky files found under `uploads/` and explicitly marked do-not-upload:

- `uploads/install.sql`
- `.DS_Store` files under `uploads/` and child folders

`uploads/install.sql` local HTTP guard check:

- URL checked: `http://school.local/uploads/install.sql`
- Expected: `403`
- Actual: `403`
- Result: protected locally by `uploads/.htaccess`

Known media caveat:

- Live DB raw `course.thumbnail` values are stale for some rows. The sanitized SQL export corrects this in the export only by normalizing exported `course.thumbnail` values to existing runtime filenames. The live local DB was not changed.

## 10. cPanel Deployment Checklist

Use `deployment_notes/README_DEPLOY_CPANEL.md` and `deployment_notes/SOURCE_UPLOAD_CHECKLIST_CPANEL.md`.

Checklist summary:

- Confirm the target cPanel domain and document root.
- Upload source to the correct document root, such as `public_html` or the configured subdomain/addon-domain folder.
- Create a MySQL database and MySQL user in cPanel.
- Assign the database user required privileges.
- Import the sanitized SQL through cPanel phpMyAdmin.
- Update `application/config/database.php` with cPanel MySQL credentials.
- Verify live `base_url`/domain configuration.
- Verify root `.htaccess` works on Apache/cPanel.
- Verify `uploads/.htaccess` blocks SQL/archive/database dump files.
- Upload required media folders/files from `MEDIA_MANIFEST.csv`.
- Exclude `RISKY_UPLOAD_FILES.csv` entries.
- Set writable permissions for `uploads/`, optimized thumbnails, lesson/resource folders, `application/cache/`, and `application/logs/`.
- Confirm PHP version/extensions for Academy LMS/CodeIgniter, including `mysqli`, `mbstring`, `curl`, `gd`, `json`, `fileinfo`, `zip`, `openssl`, and `intl` where available.
- Keep `display_errors` off and logging enabled.
- Run post-upload public smoke on the live cPanel URL.

## 11. Required cPanel Config Changes

Must be changed or verified on cPanel:

- `application/config/database.php`
  - Hostname
  - Database name
  - Username
  - Password
- `application/config/config.php`
  - `base_url` and domain/HTTPS behavior if hardcoded or locally configured.
- cPanel document root
  - `public_html` for primary domain or the configured subdomain/addon-domain folder.
- Apache rewrite support
  - root `.htaccess`
  - clean routes without `index.php`
- Upload permissions
  - writable upload/cache/log paths.
- Admin account
  - normal Admin account for the client, not Root Admin.
- Payment/SMTP/social secrets
  - keep disabled/blank unless explicitly configured later.

## 12. Public Smoke Result

Quick HTTP smoke was run directly against `http://school.local/`.

Routes checked:

- `/`
- `/ar`
- `/home/courses`
- `/ar/courses`
- `/blog`
- `/ar/blog`
- `/contact`
- `/ar/contact`
- `/login`
- `/ar/login`
- `/sign_up`
- `/ar/sign-up`

Result summary:

- Routes checked: `12`
- HTTP 200: `12`
- Routes with PHP/error markers: `0`
- Broken images: `0`
- Repeated `????` hits: `0`
- `/en` link hits: `0`
- Skeleton hits: `0`
- Payment/cart/checkout/Paymob/coupon CTA hits: `0`
- Contact display-only pages: `2`
- Blog real EN/AR content visible: `2`

Smoke outputs:

- `checks/public_smoke.csv`
- `checks/public_smoke_images.csv`
- `checks/public_smoke_summary.json`

## 13. Remaining Blockers

Blockers before client upload:

- The sanitized SQL has not yet been restored/import-tested into a clean local DB.
- The exact cPanel document root/domain is not confirmed in this phase.
- The cPanel MySQL database/user does not exist yet.
- Server `application/config/database.php` and `base_url` are not configured yet.
- Server PHP version/extensions, Apache rewrite behavior, and writable permissions still need live cPanel verification.
- Normal client Admin credentials must be confirmed/rotated by the owner after import.
- Payment/checkout/Paymob/coupon areas remain deferred and must stay out of the client demo.
- The media manifest is a manifest/checklist, not a copied media archive.

Non-blocking package notes:

- Source path scan found expected `localhost` references in local DB config, installer/addon code, inactive default-new theme, and vendor/library docs/examples. No `C:\Users`, `AppData\Local\Temp`, `/mnt/data`, `D:\Work`, or `school.local` runtime-source hits were found in the focused scan.
- The inactive `default-new` theme still has an old localhost link in a legacy my-courses view. Active frontend theme is `youngo`; do not switch/demo old themes.

## 14. Next Phase Recommendation

Recommended next phase:

```text
CLIENT.PACKAGE.2 - Local Restore Test From Package
```

Purpose:

- Import the sanitized SQL into a clean local test database.
- Point a local test copy/config at that restored DB.
- Confirm YounGo theme, EGP, Blog translations, Contact settings, media, `ci_sessions` emptiness, course `43` exclusion, and payment/access table emptiness.
- Rerun public smoke from the restored package.

Do not upload to cPanel until the restore test passes and the owner approves upload.

## Package Files Created Outside Repo

```text
D:\Work\YounGo\release_packages\youngo_client_demo_cpanel_2026_07_20_162623\database\DB_EXPORT_TABLE_SUMMARY.csv
D:\Work\YounGo\release_packages\youngo_client_demo_cpanel_2026_07_20_162623\database\youngo_client_demo_cpanel_sanitized_2026_07_20_162623.sql
D:\Work\YounGo\release_packages\youngo_client_demo_cpanel_2026_07_20_162623\deployment_notes\DB_EXPORT_NOTES.txt
D:\Work\YounGo\release_packages\youngo_client_demo_cpanel_2026_07_20_162623\deployment_notes\README_DEPLOY_CPANEL.md
D:\Work\YounGo\release_packages\youngo_client_demo_cpanel_2026_07_20_162623\deployment_notes\SOURCE_UPLOAD_CHECKLIST_CPANEL.md
D:\Work\YounGo\release_packages\youngo_client_demo_cpanel_2026_07_20_162623\media_manifest\DB_REFERENCED_MEDIA_CHECK.csv
D:\Work\YounGo\release_packages\youngo_client_demo_cpanel_2026_07_20_162623\media_manifest\MEDIA_MANIFEST.csv
D:\Work\YounGo\release_packages\youngo_client_demo_cpanel_2026_07_20_162623\media_manifest\MEDIA_MANIFEST.txt
D:\Work\YounGo\release_packages\youngo_client_demo_cpanel_2026_07_20_162623\media_manifest\RISKY_UPLOAD_FILES.csv
D:\Work\YounGo\release_packages\youngo_client_demo_cpanel_2026_07_20_162623\checks\PACKAGE_FILE_HASHES.csv
D:\Work\YounGo\release_packages\youngo_client_demo_cpanel_2026_07_20_162623\checks\db_export_metadata.json
D:\Work\YounGo\release_packages\youngo_client_demo_cpanel_2026_07_20_162623\checks\generate_sanitized_export.php
D:\Work\YounGo\release_packages\youngo_client_demo_cpanel_2026_07_20_162623\checks\media_manifest_summary.json
D:\Work\YounGo\release_packages\youngo_client_demo_cpanel_2026_07_20_162623\checks\public_smoke.csv
D:\Work\YounGo\release_packages\youngo_client_demo_cpanel_2026_07_20_162623\checks\public_smoke_images.csv
D:\Work\YounGo\release_packages\youngo_client_demo_cpanel_2026_07_20_162623\checks\public_smoke_summary.json
D:\Work\YounGo\release_packages\youngo_client_demo_cpanel_2026_07_20_162623\checks\source_path_scan.txt
D:\Work\YounGo\release_packages\youngo_client_demo_cpanel_2026_07_20_162623\checks\source_path_scan_summary.txt
D:\Work\YounGo\release_packages\youngo_client_demo_cpanel_2026_07_20_162623\checks\uploads_install_sql_http_check.json
```

## Files Changed In Repo

- `docs/qa/youngo_client_package_1_cpanel_report.md`

Suggested commit message if approved later:

```text
Prepare YounGo cPanel client package
```
