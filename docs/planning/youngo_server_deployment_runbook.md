# YounGo Server Deployment Runbook

## Master Plan Reference

The current source of truth for project priorities, architecture, Academy LMS reuse decisions, deployment preparation, and implementation sequencing is:

[`youngo_master_plan_v2.md`](./youngo_master_plan_v2.md)

This runbook deploys YounGo as a product/theme layer on top of Academy LMS. Do not rebuild course creation, media handling, authentication, categories, sections, lessons, or publishing workflows during deployment.

---

## 1. Deployment Overview

Purpose:

- Deploy a client testing server for the current YounGo/Academy LMS build.

This runbook deploys the Phase 1 baseline only. It does not deploy Phase 2 subscription, hybrid entitlement, manual grant, expanded coupon scope, Paymob, or multi-role/capability features.

Client testing scope:

- Homepage
- Course listing
- Course details
- Login and sign-up
- Admin course creation
- Thumbnail upload
- Sections
- Lessons
- Public course visibility

This is not a production launch. Payments, SMTP-dependent flows, destructive admin actions, and broad system configuration changes are outside the first client testing scope.

Phase 2 deployment will require a separate approved schema/export/migration plan, payment QA plan, and rollout checklist.

---

## 2. Required Deployment Inputs

The deployer must have:

- Domain or subdomain.
- Hosting type: cPanel/shared hosting/VPS.
- Server document root path.
- Database name, database user, and database password.
- PHP version available on the server.
- phpMyAdmin or shell access.
- Ability to set writable permissions.
- SSL/HTTPS status.
- Ability to enable PHP extensions.

Do not write real credentials into this runbook.

---

## 3. Packages To Deploy

Code source:

```text
Git branch: analysis/cms-audit
```

Media package:

```text
D:\Work\YounGo\deployment_staging\youngo_demo_20260712_2333\youngo_demo_media_20260712_2333.zip
```

Database package:

```text
D:\Work\YounGo\deployment_staging\youngo_demo_20260712_2333\database\youngo_demo_db_20260712_2333_r2.zip
```

Sanitized SQL:

```text
D:\Work\YounGo\deployment_staging\youngo_demo_20260712_2333\database\youngo_demo_db_20260712_2333_r2.sql
```

The media package contains curated `uploads/` media and required empty writable folder structure under `uploads/` and `application/`.

The database package contains the sanitized deployment SQL and export report. DB R2 is the recommended deployment database because it includes the restricted client admin and excludes the local manual test course ID `9`. It does not contain upload media, source code, local tooling, raw unsanitized dumps, or server credentials.

DB R2 is a Phase 1 baseline database package. It does not contain Phase 2 subscription, entitlement, manual grant, expanded coupon, Paymob, or multi-role/capability schema/features.

---

## 4. Server Requirements Checklist

Required:

- PHP 8.2 preferred, or PHP 8.4 only if fully tested on the target server.
- MySQL or MariaDB.
- Apache rewrite support and `.htaccess` enabled.
- HTTPS enabled.
- PHP extensions:
  - `mysqli`
  - `gd`
  - `fileinfo`
  - `mbstring`
  - `curl`
  - `openssl`
  - `zip` / `ZipArchive`
  - `json`

Recommended PHP limits:

```text
upload_max_filesize >= 20M
post_max_size >= 25M
memory_limit >= 256M
max_execution_time >= 120
```

---

## 5. Code Deployment Steps

### Option A: Git-Based Deployment

1. Clone the repository or pull the current deployment branch.
2. Checkout:

   ```bash
   git checkout analysis/cms-audit
   ```

3. Set the server document root to the app root or the hosting path expected by this CodeIgniter installation.
4. Confirm `.htaccess` is present.
5. Do not deploy local tooling folders:
   - `.claude/`
   - `.playwright/`
   - `.playwright-cli/`
   - `.playwright-mcp/`
   - `.vscode/mcp.json`
6. Do not deploy local cache, logs, temp files, local archives, or database dumps.

### Option B: ZIP / Manual Upload Deployment

1. Prepare a clean code package from Git if shell access is not available.
2. Include tracked application files only.
3. Exclude local tooling, cache, logs, temp files, local archives, and database dumps.
4. Upload the clean code package to the server.
5. Extract it into the application root.
6. Confirm core paths exist:
   - `application/`
   - `assets/`
   - `uploads/`
   - `.htaccess`
   - `index.php`

Do not create the code ZIP as part of this runbook.

---

## 6. Database Deployment Steps

1. Create the server database.
2. Create the database user.
3. Grant the database user access to the database.
4. Import:

   ```text
   youngo_demo_db_20260712_2333_r2.sql
   ```

5. Confirm `ci_sessions` exists and has no rows.
6. Configure server `application/config/database.php` with the server database credentials.
7. Confirm:
   - `frontend_settings.theme = youngo`
   - `frontend_settings.youngo_homepage_content` exists
   - Courses `1-6` exist
   - Categories `1-7` exist, including Scratch Basics
8. Do not create the restricted client admin until after the import is complete and verified.

---

## 7. Media Deployment Steps

1. Upload:

   ```text
   youngo_demo_media_20260712_2333.zip
   ```

2. Extract it so the package `uploads/` and `application/` folders merge into the application root.
3. Verify restored system media:
   - `uploads/system/favicon.png`
   - `uploads/system/logo-dark.png`
   - `uploads/system/logo-light-sm.png`
   - `uploads/system/logo-light.png`
4. Verify blog banner:
   - `uploads/blog/page-banner/blog-page.png`
5. Verify course thumbnails for demo courses `1-6`.
6. Verify category thumbnails for demo categories.
7. Verify required empty writable folders exist.

Do not overwrite server-specific config files with local config files.

---

## 8. Writable Permissions Checklist

Make these writable by the web server user:

- `uploads/`
- `uploads/thumbnails/`
- `uploads/thumbnails/course_thumbnails/`
- `uploads/thumbnails/course_thumbnails/optimized/`
- `uploads/thumbnails/category_thumbnails/`
- `uploads/thumbnails/lesson_thumbnails/`
- `uploads/thumbnails/upcoming_thumbnails/`
- `uploads/lesson_files/`
- `uploads/lesson_files/videos/`
- `uploads/lesson_files/audios/`
- `uploads/captions/`
- `uploads/resource_files/`
- `uploads/system/`
- `uploads/home-pages/`
- `uploads/user_image/`
- `uploads/user_image/optimized/`
- `application/cache/`
- `application/logs/`

Use the hosting provider's normal permission model. Avoid world-writable permissions unless the host specifically requires them.

---

## 9. Server Config Checklist

Review on the server:

- `application/config/database.php` uses server DB credentials.
- `application/config/config.php` base URL behavior matches the server domain.
- `index_page` is set to an empty string if rewrite is enabled.
- `.htaccess` rewrite works.
- `display_errors` is off for client testing.
- Error logging is on.
- `encryption_key` should be set before production.
- CSRF and cookie security should be reviewed before production.
- SSL works and public pages do not have mixed content warnings.

---

## 10. Payment And SMTP Setup

For this client testing deployment:

- Keep payment gateways disabled or sandbox-only.
- Do not allow real payment testing.
- Do not test subscriptions, manual grants, expanded coupons, Paymob, or multi-role/capability behavior from this Phase 1 deployment package.
- SMTP can be deferred unless password reset or email flows are explicitly required.
- If SMTP is deferred, tell the client not to test password reset or email-dependent flows.

Before production, configure real payment and SMTP credentials only through a secure server process.

---

## 11. Account Strategy

Developer/root admin:

- Use only for deployment support.
- Rotate the root admin password on the server before sharing any access.

Restricted client admin:

- Create after server import.
- Must have an explicit `permissions` row.
- An admin account without a permissions row may behave as root/full access.

Recommended permissions:

- `course`
- `category` only if needed for course category selection/review

Do not grant:

- `settings`
- `theme`
- `addon`
- `admin`
- `user`
- `instructor`
- `student`
- `revenue`
- `coupon`
- `newsletter`
- `contact`
- `messaging`

---

## 12. Restricted Client Admin Creation Checklist

Do not create the account until after deployment import is verified.

After deployment:

1. Create an admin user for the client.
2. Create an explicit permissions row for that admin.
3. Grant only the approved course/category permissions.
4. Verify menu access.
5. Verify the client admin cannot access:
   - Settings
   - Theme switching
   - Payment settings
   - User management
   - Addons
6. Verify the client admin can access course creation.
7. Verify the client admin can create a course using Scratch Basics.

---

## 13. Post-Deployment Smoke Test

Run this checklist before sending access to the client:

- Homepage loads.
- `/home/courses` loads.
- All 6 demo course detail pages load.
- `/login` loads.
- `/sign_up` loads.
- Admin login works.
- `/admin/course_form/add_course` loads.
- Thumbnail field appears.
- Scratch Basics subcategory appears.
- Create one temporary server test course.
- Upload a thumbnail.
- Add one section.
- Add one text lesson.
- Activate the course.
- Verify `/home/courses` shows the temporary course.
- Verify `/home/course/{slug}/{id}` shows the temporary course.
- No broken images.
- No skeleton placeholder on tested public routes.
- No visible PHP errors.
- Upload folders are writable.
- Rewrite works without `index.php`.

Record the temporary course ID, section ID, lesson ID, slug, and thumbnail file paths.

---

## 14. Server Test Course Cleanup

Only clean up the temporary server test course after it is no longer needed.

Safe cleanup rules:

- Delete only the recorded lesson ID.
- Delete only the recorded section ID.
- Delete only the recorded course ID.
- Delete only the recorded thumbnail files for that course.
- Never truncate tables.
- Never delete demo courses `1-6`.
- Never delete demo categories `1-7`.
- Never delete unrelated uploads.

---

## 15. Client Testing Guide

The client may test:

- Login.
- Homepage.
- Course listing.
- Course details.
- Create course.
- Upload thumbnail.
- Add section.
- Add text lesson.
- Activate or publish course.
- Confirm public visibility.

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

## 16. Rollback Plan

Before deployment:

- Back up current server files.
- Back up current server database.
- Back up current server uploads.

Rollback steps:

1. Restore the database backup.
2. Restore the files/uploads backup.
3. Clear sessions/cache.
4. Retest homepage.
5. Retest login.
6. Confirm no client-facing PHP errors.

---

## 17. Final Deployment Notes

Do not treat this deployment as production-ready until:

- Production secrets are configured securely.
- Root/admin credentials are rotated.
- SMTP is configured and tested if email flows are in scope.
- Payments are explicitly configured for production or intentionally disabled.
- CSRF, cookie security, SSL, and error logging are reviewed.
- A final smoke test passes.
