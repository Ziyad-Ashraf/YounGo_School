# YounGo Deployment Package Manifest

## Master Plan Reference

The current source of truth for deployment priorities, Academy LMS reuse decisions, and implementation sequencing is:

[`youngo_master_plan_v2.md`](./youngo_master_plan_v2.md)

This deployment plan keeps YounGo as a product/theme layer on top of Academy LMS. Existing Academy LMS database, uploads, course creation, authentication, media, category, section, lesson, and publishing workflows must be reused.

---

## 1. Deployment Objective

This deployment package is for client testing of the current YounGo/Academy LMS build on a server.

This package is a Phase 1 deployment baseline package. It does not include Phase 2 subscription, hybrid entitlement, manual grant, expanded coupon scope, Paymob, or multi-role/capability schema/features.

The client test scope is:

- Homepage
- Course listing
- Course details
- Login and sign-up
- Admin course creation
- Course thumbnail upload
- Sections
- Lessons
- Public course visibility

This is not a standalone YounGo rebuild. Deployment must preserve the working Academy LMS core and the YounGo theme layer.

Phase 2 deployment will require a separate approved database/schema plan, export/migration plan, payment QA plan, and server rollout checklist.

---

## 2. Code Deployment Source

Source branch:

```text
analysis/cms-audit
```

Deploy:

- Tracked application code.
- YounGo frontend views and assets.
- `assets/frontend/youngo/config/theme-config.json`.
- Tracked documentation needed for project continuity.
- Tracked restored system media:
  - `uploads/system/favicon.png`
  - `uploads/system/logo-dark.png`
  - `uploads/system/logo-light-sm.png`
  - `uploads/system/logo-light.png`

Do not deploy local tooling:

- `.claude/`
- `.playwright/`
- `.playwright-cli/`
- `.playwright-mcp/`
- `.vscode/mcp.json`

Do not deploy cache, log, temporary, archive, backup, dependency-generated, or local-only files unless explicitly required.

---

## 3. Database Export Plan

Use a sanitized selected-table export where possible. Do not export local session rows or smoke-test records.

The current export plan is for Phase 1 baseline deployment only. Do not assume the listed DB R2 export contains Phase 2 subscription, entitlement, manual grant, expanded coupon, Paymob, or multi-role data structures.

Recommended output filename:

```text
youngo_demo_db_YYYYMMDD_HHMM.sql
```

### 3.1 Export Structure And Data

Export these tables with structure and data:

- `users`
- `role`
- `permissions`
- `category`
- `course`
- `section`
- `lesson`
- `rating`
- `frontend_settings`
- `settings`
- `home_pages`
- `payment_gateways`
- `language`
- `seo_fields`
- `currency`
- `notification_settings`

### 3.2 Export If Present And Useful

Export these tables with structure and data if present and useful for the server demo:

- `addons`
- `applications`
- `badges`
- `coupons`
- `custom_page`
- `enrol`
- `instructor_followings`
- `message`
- `message_thread`
- `newsletter`
- `newsletter_history`
- `newsletter_subscriber`
- `notifications`
- `payment`
- `payout`
- `question`
- `quiz_results`
- `resource_files`
- `watch_histories`
- `watched_duration`

### 3.3 Export Structure Only Or Empty Data

Export structure only, or import empty data, for runtime tables:

- `ci_sessions`
- `log`
- Any runtime/cache/session table present in the local database.

Do not export `ci_sessions` rows.

### 3.4 Demo Data To Preserve

Preserve:

- Demo course IDs `1-6`.
- Category IDs `1-7`, including Scratch Basics.
- Section IDs `1-18`.
- Lesson IDs `1-36`.
- Rating IDs `1-12`.
- `frontend_settings.youngo_homepage_content`.
- Active frontend theme setting value `youngo`.

Do not export local smoke-test course records.

PD-1B readiness test course ID `7` should be absent from the export.

---

## 4. Sanitization Checklist

Before or immediately after server import:

- Rotate the root/developer admin password.
- Create the restricted client admin after server import.
- Ensure every restricted admin has an explicit `permissions` row.
- Disable payment gateways or keep them sandbox-only.
- Clear SMTP settings if SMTP is not configured for the server.
- Disable social login until server credentials are configured.
- Clear `ci_sessions`.
- Review site email, domain, SEO, and contact settings.
- Ensure no real local secrets are deployed from the local database.

Do not deploy real payment credentials, SMTP passwords, API keys, cloud storage keys, social login secrets, or local-only credentials.

---

## 5. Suggested Database Export Commands

These are command templates only. Do not run them until the export phase is explicitly approved.

Use placeholders only:

- `DB_NAME`
- `DB_USER`
- `DB_PASSWORD`
- `OUTPUT_FILE`

### Option 1: Selected Tables Export

Export selected demo tables with structure and data:

```bash
mysqldump -u DB_USER -pDB_PASSWORD DB_NAME \
  users role permissions category course section lesson rating frontend_settings settings home_pages payment_gateways language seo_fields currency notification_settings \
  addons applications badges coupons custom_page enrol instructor_followings message message_thread newsletter newsletter_history newsletter_subscriber notifications payment payout question quiz_results resource_files watch_histories watched_duration \
  > OUTPUT_FILE
```

Export runtime table structure only:

```bash
mysqldump -u DB_USER -pDB_PASSWORD --no-data DB_NAME ci_sessions log > runtime_tables_structure.sql
```

If a listed optional table does not exist, remove that table name from the command and rerun the export.

### Option 2: Full Dump Then Offline Sanitization

Use this only if selected-table export is impractical:

```bash
mysqldump -u DB_USER -pDB_PASSWORD --single-transaction --routines --triggers DB_NAME > youngo_demo_db_YYYYMMDD_HHMM_full_local.sql
```

Before server import, sanitize the dump offline:

- Remove or empty `ci_sessions` rows.
- Remove local smoke-test records.
- Remove or replace secrets.
- Disable or sandbox payment credentials.
- Clear SMTP credentials if not configured.
- Confirm active theme remains `youngo`.
- Confirm demo course/category/section/lesson/rating IDs are preserved.

### Option 3: phpMyAdmin Export

If shell access is unavailable:

1. Open phpMyAdmin.
2. Select the local YounGo database.
3. Use Custom export.
4. Select the required structure + data tables from this document.
5. Select runtime/session tables as structure only, or export them empty.
6. Use SQL format.
7. Use UTF-8 compatible export settings.
8. Avoid `DROP DATABASE`.
9. Save as `youngo_demo_db_YYYYMMDD_HHMM.sql`.
10. Review the SQL before import for sessions, smoke-test records, and secrets.

---

## 6. Uploads And Media Manifest

Package only the curated uploads required for the server demo and required writable folder structure.

### 6.1 Required System Media

Include `uploads/system/` with guard files where present and these restored files:

- `uploads/system/favicon.png`
- `uploads/system/logo-dark.png`
- `uploads/system/logo-light-sm.png`
- `uploads/system/logo-light.png`

### 6.2 Required Blog Banner

Include:

- `uploads/blog/page-banner/blog-page.png`

### 6.3 Required Course Thumbnails

Include `uploads/thumbnails/course_thumbnails/` guard files where present and these demo course thumbnails:

- `uploads/thumbnails/course_thumbnails/course_thumbnail_youngo_11783873172.jpg`
- `uploads/thumbnails/course_thumbnails/course_thumbnail_youngo_21783873172.jpg`
- `uploads/thumbnails/course_thumbnails/course_thumbnail_youngo_31783873172.jpg`
- `uploads/thumbnails/course_thumbnails/course_thumbnail_youngo_41783873172.jpg`
- `uploads/thumbnails/course_thumbnails/course_thumbnail_youngo_51783873172.jpg`
- `uploads/thumbnails/course_thumbnails/course_thumbnail_youngo_61783873172.jpg`

### 6.4 Required Category Thumbnails

Include `uploads/thumbnails/category_thumbnails/` guard files where present and these category thumbnails:

- `uploads/thumbnails/category_thumbnails/youngo-category-coding-for-kids.jpg`
- `uploads/thumbnails/category_thumbnails/youngo-category-science-explorers.jpg`
- `uploads/thumbnails/category_thumbnails/youngo-category-creative-arts.jpg`
- `uploads/thumbnails/category_thumbnails/youngo-category-reading-storytelling.jpg`
- `uploads/thumbnails/category_thumbnails/youngo-category-math-adventures.jpg`
- `uploads/thumbnails/category_thumbnails/youngo-category-life-skills.jpg`

### 6.5 Guard And Server Files

Include guard/security files where present:

- `index.html`
- `.htaccess`
- `htaccess_domain_wise`, if required by the app.

### 6.6 Empty Writable Folders To Create On Server

Create these folders on the server if missing and make them writable by the web server user:

- `uploads/home-pages/`
- `uploads/thumbnails/course_thumbnails/optimized/`
- `uploads/thumbnails/lesson_thumbnails/`
- `uploads/thumbnails/upcoming_thumbnails/`
- `uploads/lesson_files/`
- `uploads/lesson_files/videos/`
- `uploads/lesson_files/audios/`
- `uploads/captions/`
- `uploads/resource_files/`
- `uploads/user_image/`
- `uploads/user_image/optimized/`
- `application/cache/`
- `application/logs/`

---

## 7. Uploads Exclusion Checklist

Exclude:

- `.DS_Store`
- Cache files
- Log files
- Local temp files
- Old smoke-test artifacts
- Unrelated backup/archive files
- Unused blog images unless intentionally used
- Local tooling folders
- Generated browser/testing folders

Known PD-1B smoke-test artifacts should remain absent:

- `uploads/thumbnails/course_thumbnails/course_thumbnail_youngo_7.jpg`
- `uploads/thumbnails/course_thumbnails/optimized/course_thumbnail_youngo_7.jpg`
- `uploads/thumbnails/upcoming_thumbnails/fe98dc0a8b17b483e94fae082f0ac95f.jpg`

---

## 8. Suggested Upload Packaging Commands

These are PowerShell command templates only. Do not run them until the packaging phase is explicitly approved.

Create a staging folder outside the repository:

```powershell
$DateStamp = "YYYYMMDD"
$Stage = "D:\Work\YounGo\deployment_staging\youngo_demo_$DateStamp"
New-Item -ItemType Directory -Force -Path $Stage
```

Create selected upload folders:

```powershell
$UploadDirs = @(
  "uploads\system",
  "uploads\blog\page-banner",
  "uploads\thumbnails\course_thumbnails",
  "uploads\thumbnails\category_thumbnails",
  "uploads\thumbnails\course_thumbnails\optimized",
  "uploads\thumbnails\lesson_thumbnails",
  "uploads\thumbnails\upcoming_thumbnails",
  "uploads\lesson_files",
  "uploads\lesson_files\videos",
  "uploads\lesson_files\audios",
  "uploads\captions",
  "uploads\resource_files",
  "uploads\user_image",
  "uploads\user_image\optimized"
)

foreach ($Dir in $UploadDirs) {
  New-Item -ItemType Directory -Force -Path (Join-Path $Stage $Dir)
}
```

Copy selected required media:

```powershell
$Files = @(
  "uploads\system\favicon.png",
  "uploads\system\logo-dark.png",
  "uploads\system\logo-light-sm.png",
  "uploads\system\logo-light.png",
  "uploads\blog\page-banner\blog-page.png",
  "uploads\thumbnails\course_thumbnails\course_thumbnail_youngo_11783873172.jpg",
  "uploads\thumbnails\course_thumbnails\course_thumbnail_youngo_21783873172.jpg",
  "uploads\thumbnails\course_thumbnails\course_thumbnail_youngo_31783873172.jpg",
  "uploads\thumbnails\course_thumbnails\course_thumbnail_youngo_41783873172.jpg",
  "uploads\thumbnails\course_thumbnails\course_thumbnail_youngo_51783873172.jpg",
  "uploads\thumbnails\course_thumbnails\course_thumbnail_youngo_61783873172.jpg",
  "uploads\thumbnails\category_thumbnails\youngo-category-coding-for-kids.jpg",
  "uploads\thumbnails\category_thumbnails\youngo-category-science-explorers.jpg",
  "uploads\thumbnails\category_thumbnails\youngo-category-creative-arts.jpg",
  "uploads\thumbnails\category_thumbnails\youngo-category-reading-storytelling.jpg",
  "uploads\thumbnails\category_thumbnails\youngo-category-math-adventures.jpg",
  "uploads\thumbnails\category_thumbnails\youngo-category-life-skills.jpg"
)

foreach ($File in $Files) {
  $Target = Join-Path $Stage $File
  New-Item -ItemType Directory -Force -Path (Split-Path $Target)
  Copy-Item -LiteralPath $File -Destination $Target
}
```

Copy guard files where present:

```powershell
$GuardFiles = Get-ChildItem -Path uploads -Recurse -Force -File |
  Where-Object { $_.Name -in @("index.html", ".htaccess", "htaccess_domain_wise") }

foreach ($Guard in $GuardFiles) {
  $Relative = Resolve-Path -LiteralPath $Guard.FullName -Relative
  $Relative = $Relative.TrimStart(".\")
  $Target = Join-Path $Stage $Relative
  New-Item -ItemType Directory -Force -Path (Split-Path $Target)
  Copy-Item -LiteralPath $Guard.FullName -Destination $Target
}
```

Remove `.DS_Store` from staging if present:

```powershell
Get-ChildItem -LiteralPath $Stage -Recurse -Force -Filter ".DS_Store" |
  Remove-Item -Force
```

Optional compression later:

```powershell
Compress-Archive -Path $Stage -DestinationPath "$Stage.zip"
```

---

## 9. Server Import And Deployment Sequence

1. Pull or upload code from `analysis/cms-audit`.
2. Configure `application/config/database.php` on the server.
3. Import the sanitized SQL file.
4. Upload the curated uploads package.
5. Set writable permissions on required upload/cache/log folders.
6. Confirm required PHP extensions, especially GD.
7. Clear `ci_sessions`.
8. Confirm active frontend theme is `youngo`.
9. Confirm `frontend_settings.youngo_homepage_content` exists.
10. Create restricted client admin with an explicit permissions row.
11. Disable or sandbox payment gateways.
12. Configure SMTP or defer SMTP-dependent flows.
13. Run the post-deployment smoke tests.

---

## 10. Post-Deployment Smoke Test Checklist

Test:

- Homepage renders.
- `/home/courses` renders.
- Each demo course details page renders.
- `/login` renders.
- `/sign_up` renders.
- Admin login works.
- `/admin/course_form/add_course` renders.
- A temporary server test course can be created.
- Course thumbnail upload works.
- A section can be added.
- A text lesson can be added.
- Public listing/details show the temporary course when active.
- Free enroll works with a student account if in scope.
- Subscription, manual grant, expanded coupon, Paymob, and multi-role flows are not part of this Phase 1 smoke test.
- No broken images appear.
- No skeleton placeholders remain on tested public routes.
- No visible PHP errors appear.
- Upload folders are writable.
- Rewrites work without `index.php`.

Do not delete existing demo courses during smoke testing.

---

## 11. Client Testing Boundaries

The client may test:

- Login.
- Public homepage and course pages.
- Course creation.
- Thumbnail upload.
- Section creation.
- Text lesson creation.
- Activate/publish course.
- Public course visibility.

The client should not test yet:

- Real payments.
- Subscriptions.
- Manual grants.
- Expanded coupon/discount scope.
- Paymob payment methods.
- Multi-role/capability behavior.
- Destructive deletes.
- System settings.
- Theme switching.
- Addons.
- SMTP/password reset if SMTP is deferred.
- Instructor payouts.
- Deep student dashboard polish.
- Lesson player polish beyond basic rendering.

---

## 12. Restricted Client Admin Plan

Create the restricted client admin after server import.

Grant only the permissions required for the client testing scope:

- Course management.
- Category access only if required for selecting or reviewing course categories.

Do not grant:

- Settings
- Theme
- Addon
- Admin management
- User management
- Instructor management
- Student management
- Revenue
- Payment
- Coupon
- Newsletter
- Contact
- Messaging

Important permission rule:

```text
A restricted admin must have an explicit permissions row.
An admin account without a permissions row may behave as root/full access.
```

---

## 13. Final Pre-Deployment Checks

Before executing the export/package commands:

- Confirm Git status.
- Confirm no source behavior changes are pending unexpectedly.
- Confirm restored system media are tracked.
- Confirm no local tooling is staged or included.
- Confirm PD-1B smoke-test course/media is absent.
- Confirm demo course/category/section/lesson/rating IDs are preserved.
- Confirm no real secrets are present in the SQL export.
- Confirm no archive, SQL dump, or staging folder is created until explicitly approved.
