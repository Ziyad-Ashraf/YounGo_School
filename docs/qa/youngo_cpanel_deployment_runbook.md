# YounGo cPanel Deployment Runbook

Phase: `CLIENT.UPLOAD.1 - cPanel Deployment Runbook and Final Upload Checklist`

Date: 2026-07-20

Branch: `analysis/cms-audit`

Target hosting: cPanel / Apache / MySQL or MariaDB

Status: deployment-prep documentation only. No upload, deployment, push, commit, DB write, or source runtime change was performed in this phase.

## 1. Deployment Result

Use this runbook for the YounGo client demo cPanel upload.

The current upload candidate is the package folder:

```text
D:\Work\YounGo\release_packages\youngo_client_demo_cpanel_2026_07_20_162623
```

Use the latest improved 1B sanitized SQL export:

```text
D:\Work\YounGo\release_packages\youngo_client_demo_cpanel_2026_07_20_162623\database\youngo_client_demo_cpanel_sanitized_1b_2026_07_20_145319.sql
```

SQL metadata:

- Size: `526060` bytes
- SHA256: `14de85b763646aeaf4826c3ffad3ed178ac9d691aaa0e74301d483dce9a75ab6`
- Tables: `64`
- Exported data rows: `2122`

The 1B export has already been imported locally into `youngo_client_restore_test_1b_20260720` and public-smoked against `http://school.local/`:

- Import errors: none
- Public pages checked: `14`
- Failed pages: `0`
- Local images checked: `96`
- Broken local images: `0`

## 2. Owner Inputs Required

Do not begin upload until these are known:

- Final cPanel domain or subdomain.
- Exact cPanel document root, such as `public_html` or a subdomain/addon-domain folder.
- cPanel MySQL database name.
- cPanel MySQL username.
- cPanel MySQL password.
- Live base URL, including HTTPS decision.
- Client Admin password delivery method.
- Confirmation that the client demo should use normal Admin user id `7`, not Root Admin.
- Confirmation that payment, checkout, Paymob, coupons, language/phrase tools, SMTP/server settings, installer/license/update, and Root/admin internals are out of the client demo.

Do not store passwords in Git, reports, package notes, screenshots, tickets, chat transcripts intended for the client, or public cPanel files.

## 3. Pre-Upload Server Backup

If the target cPanel account already has any existing site or database, take a server backup before upload:

- Download a backup of the current document root.
- Export the current cPanel database through phpMyAdmin if one exists.
- Record the backup timestamp and storage location outside the public web root.
- Confirm rollback access before replacing files or importing the YounGo DB.

If the target is a clean new subdomain with no existing app, still record the empty baseline state and cPanel path.

## 4. cPanel Target Selection

Choose the target document root:

- Main domain: usually `public_html`.
- Subdomain/addon domain: the folder shown by cPanel for that domain.

The CodeIgniter app root should be placed at the document root that serves the public domain. The live root should contain:

- `index.php`
- `.htaccess`
- `application/`
- `assets/`
- `system/`
- `uploads/`

Do not put the YounGo package folder itself inside the public web root unless it is only a temporary private upload staging folder and is removed before handoff.

## 5. What Must Be Uploaded

Upload the current source/runtime files from the committed branch `analysis/cms-audit`.

Required app/runtime source:

- `application/`
- `assets/`
- `system/`
- `index.php`
- `.htaccess`
- `composer.json` if the server process expects the same dependency metadata
- `.user.ini` or `php.ini` only after reviewing target cPanel account paths, or regenerate equivalent values through cPanel MultiPHP INI Editor

Include top-level runtime folders only when they are intentionally needed by the current app:

- `languages/` if the host/vendor flow expects these language JSON files.
- `themes/` only if it contains required installed theme artifacts; it is empty in the current local workspace.

Do not rely on a source-only upload. The DB import and media files are required for the demo.

## 6. Required Media/Upload Folders

Upload the required media folders and files from the current repo/package manifest.

Required YounGo frontend assets:

- `assets/frontend/youngo/images/`

Required upload files/folders:

- `uploads/.htaccess`
- `uploads/system/`
- `uploads/user_image/placeholder.png`
- `uploads/blog/page-banner/blog-page.png`
- `uploads/blog/banner/`
- `uploads/blog/thumbnail/`
- `uploads/thumbnails/category_thumbnails/`
- `uploads/thumbnails/course_thumbnails/`

Keep writable upload folders present for admin/course workflows even if the current public demo does not use media in them:

- `uploads/lesson_files/`
- `uploads/lesson_files/videos/`
- `uploads/lesson_files/audios/`
- `uploads/captions/`
- `uploads/resource_files/`
- `uploads/thumbnails/lesson_thumbnails/`
- `uploads/thumbnails/upcoming_thumbnails/`
- `uploads/user_image/optimized/`

Media package references:

- `media_manifest/MEDIA_MANIFEST.csv`
- `media_manifest/MEDIA_MANIFEST.txt`
- `media_manifest/DB_REFERENCED_MEDIA_CHECK.csv`

The CLIENT.PACKAGE.1 media check reported:

- Manifest rows: `79`
- Required missing files: `0`
- Blocking missing DB media: `0`

## 7. What Must Not Be Uploaded or Exposed

Do not upload or expose:

- Old SQL dumps.
- Local DB backups.
- `uploads/install.sql`.
- `.DS_Store` files.
- Local release package notes unless intentionally shared privately.
- Package `checks/` diagnostics unless intentionally shared privately.
- Cache files.
- Log files.
- Session files.
- Temporary screenshots.
- Local browser/tool caches.
- Local archives.
- Secrets.
- Local-only paths.
- Local-only database credentials.
- Development-only files or folders.

Specific local folders/files to exclude from public web root:

- `.git/`
- `.claude/`
- `.playwright/`
- `.playwright-cli/`
- `.playwright-mcp/`
- `.vscode/`
- `backups/`
- `docs/` unless the owner explicitly wants project docs uploaded privately
- `scripts/`
- `database/`
- `D:\Work\YounGo\release_packages\...`
- Root `.DS_Store`
- Any `*.sql`, `*.zip`, `*.tar`, `*.gz`, `*.rar`, `*.7z`, `*.bak`, `*.log` files not explicitly required

Current risky upload files documented in the package include:

- `uploads/install.sql`
- `.DS_Store` files under `uploads/` and child folders

Even though `uploads/.htaccess` blocked `/uploads/install.sql` locally with HTTP `403`, package hygiene still requires excluding `uploads/install.sql`.

## 8. Database Creation and Import

Use cPanel:

1. Open MySQL Databases.
2. Create a new database for the YounGo demo.
3. Create a new MySQL user with a strong password.
4. Assign the MySQL user to the new database.
5. Grant full privileges for that database.
6. Open phpMyAdmin from cPanel.
7. Select the new database.
8. Import:

   ```text
   D:\Work\YounGo\release_packages\youngo_client_demo_cpanel_2026_07_20_162623\database\youngo_client_demo_cpanel_sanitized_1b_2026_07_20_145319.sql
   ```

9. Confirm import completes without errors.

1B SQL contents:

- Course `43` excluded.
- `ci_sessions` imports empty.
- `users.sessions` cleared.
- Local/test/demo-only users excluded.
- Root Admin user id `1` preserved but not for client use.
- Normal client Admin user id `7` retained.
- Client Admin has broad operational legacy permissions.
- Payment gateways disabled/redacted.
- Credential-like settings redacted.
- Blog English/Arabic posts included.
- `youngo_blog_translations` included.
- YounGo course/category/section/lesson translation tables included.
- YounGo theme setting included.
- EGP currency setting included.

After import, verify in phpMyAdmin:

- `users` has 2 rows: Root Admin and client Admin.
- `permissions` has the client Admin row.
- `course` has 7 rows.
- `course.id = 43` is absent.
- `blogs` has 4 rows.
- `youngo_blog_translations` has 8 rows.
- `ci_sessions` has 0 rows immediately after import.
- Payment/access/checkout/coupon runtime tables are empty where expected.

## 9. Server Configuration

Update `application/config/database.php` on cPanel:

- `hostname`: usually `localhost` on cPanel.
- `username`: cPanel MySQL username.
- `password`: cPanel MySQL password.
- `database`: cPanel MySQL database name.
- `dbdriver`: `mysqli`.

Do not leave local DB credentials in the uploaded server file.

Base URL/domain:

- Current `application/config/config.php` builds `base_url` dynamically from the request host.
- Verify the live domain resolves correctly under HTTPS.
- If the host requires a fixed URL, set `base_url` to the exact live URL and test both English and Arabic routes.

Environment/error display:

- `index.php` currently defaults CodeIgniter `ENVIRONMENT` to `production`.
- Confirm `display_errors` is off.
- Confirm server PHP error logging is enabled.
- Do not expose PHP warnings/notices to the client.

cPanel PHP config:

- Local `.user.ini` and `php.ini` contain cPanel-generated paths such as account-specific log/session paths.
- Do not blindly upload those files to a different account if paths differ.
- Prefer configuring PHP limits through cPanel MultiPHP INI Editor.

Recommended PHP settings:

- `memory_limit >= 128M`, preferably `256M` if available.
- `upload_max_filesize >= 20M`.
- `post_max_size >= 25M`.
- `max_execution_time >= 120`.
- `display_errors = Off`.
- `log_errors = On`.

## 10. PHP Version and Extensions

Verify in cPanel:

- PHP version compatible with the current Academy/CodeIgniter build.
- The local `.htaccess` currently includes a cPanel handler for `ea-php84`; adjust through cPanel MultiPHP Manager if the target host uses another supported PHP version.

Required PHP extensions:

- `mysqli`
- `gd`
- `fileinfo`
- `mbstring`
- `curl`
- `openssl`
- `zip`
- `json`

If the app returns blank pages or image upload failures, check PHP version, extensions, and error logs before changing app source.

## 11. Apache Rewrite and htaccess Checks

Root `.htaccess` must be present in the document root.

Verify:

- `/` works without `index.php`.
- `/ar` works.
- `/home/courses` works.
- `/ar/courses` works.
- No server 404 from missing rewrite rules.

If routes only work with `index.php`, confirm Apache rewrite support and cPanel `.htaccess` override behavior.

Uploads protection:

- Confirm `uploads/.htaccess` exists on the server.
- Visit `/uploads/install.sql` if the file was accidentally uploaded.
- Expected result: `403` or `404`.
- If it returns `200`, remove the file immediately and fix upload protection.

## 12. Permissions

Set writable permissions for the web server user where needed:

- `uploads/`
- `uploads/system/`
- `uploads/user_image/`
- `uploads/user_image/optimized/`
- `uploads/blog/banner/`
- `uploads/blog/thumbnail/`
- `uploads/thumbnails/`
- `uploads/thumbnails/category_thumbnails/`
- `uploads/thumbnails/course_thumbnails/`
- `uploads/thumbnails/course_thumbnails/optimized/`
- `uploads/thumbnails/lesson_thumbnails/`
- `uploads/thumbnails/upcoming_thumbnails/`
- `uploads/lesson_files/`
- `uploads/lesson_files/videos/`
- `uploads/lesson_files/audios/`
- `uploads/captions/`
- `uploads/resource_files/`
- `application/cache/`
- `application/logs/`

Use the hosting provider's normal permission model. Avoid world-writable permissions unless cPanel support explicitly requires it.

## 13. Cache, Session, and Temp Hygiene

Before handoff:

- Do not upload local cache files.
- Do not upload local logs.
- Do not upload local session files.
- Do not upload SQL dumps or package archives under public paths.
- Verify `ci_sessions` is empty immediately after DB import.
- Clear browser/server caches if stale public pages appear after upload.

The live site will create new session rows after smoke testing. That is normal.

## 14. Admin Handoff Notes

Client should use:

- Normal Admin user id `7`.
- Email: `client@gmail.com`.

Client should not use:

- Root Admin user id `1`.
- Any owner/developer account.

Do not put passwords in:

- Git.
- Docs.
- Package notes.
- Screenshots.
- Public web root files.

Share the client Admin password securely outside the repo/package.

Client Admin access:

- Broad operational access is prepared for courses, categories, lessons, Blog, contact, normal users/students/instructors, newsletter/messaging, theme, and website/homepage/frontend settings.

Important guided-demo limitation:

- The legacy `settings` permission is coarse. It is included so the client Admin can reach website/frontend/YounGo homepage settings.
- Do not show payment, language/phrase, SMTP/server, backup/dev, installer/license/update, or Root/admin internals during the client dashboard demo.

## 15. Safe Dashboard Demo Flow

Use this guided flow:

1. Log in as the normal client Admin.
2. Open the dashboard overview.
3. View courses.
4. View categories/subcategories.
5. Open a demo course.
6. View sections/lessons.
7. View Blog posts.
8. View Blog categories/settings only if staying inside current demo-safe content.
9. View Contact info and frontend/homepage settings if safe.
10. Log out.

Do not use Root Admin for the client demo.

Do not test:

- Payments.
- Paymob.
- Coupons.
- Live checkout.
- Subscription purchase.
- Manual grants.
- Role assignment internals.
- Language/phrase editor.
- SMTP/server credentials.
- Installer/addons/update/license/system version.
- Database/backup/dev settings.
- Incomplete custom/legacy builder areas.

## 16. Public Post-Upload Smoke Checklist

Run this checklist on the live cPanel URL after upload.

Pages:

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

Verify each page:

- HTTP `200`.
- No PHP errors.
- No broken images.
- No repeated `????`.
- No `/en` links.
- No skeleton placeholders.
- Blog English content visible.
- Blog Arabic content visible.
- Contact display-only.
- No payment/cart/checkout/Paymob/coupon CTA regression.
- Mobile layout safe at phone width.

Also verify:

- Category links work.
- Featured course links work.
- Header language switcher stays between unprefixed English and `/ar`.
- Course thumbnails, category thumbnails, Blog thumbnails, and Blog banners load.

## 17. Admin Dashboard Smoke Checklist

After public smoke passes:

1. Log in as normal client Admin.
2. Confirm dashboard opens.
3. Confirm Courses list opens.
4. Confirm Categories list opens.
5. Confirm a course edit page opens.
6. Confirm section/lesson views open for a demo course.
7. Confirm Blog list opens and shows the 4 demo posts.
8. Confirm Contact/frontend/homepage settings can be viewed only as part of a guided safe demo.
9. Confirm Root Admin is not used.
10. Confirm payment/Paymob/coupon/checkout settings are not tested.

Do not submit destructive forms during the first server smoke unless there is a fresh server backup and explicit owner approval.

## 18. Rollback Plan

Before upload:

- Keep the previous server source backup.
- Keep the previous server DB export.
- Keep the local package folder:

  ```text
  D:\Work\YounGo\release_packages\youngo_client_demo_cpanel_2026_07_20_162623
  ```

- Keep the active SQL SHA256:

  ```text
  14de85b763646aeaf4826c3ffad3ed178ac9d691aaa0e74301d483dce9a75ab6
  ```

If upload fails:

1. Put the site in maintenance or restrict access if needed.
2. Restore previous source files to the cPanel document root.
3. Drop/recreate or overwrite the failed YounGo DB only after confirming the rollback target.
4. Import the previous server DB backup through phpMyAdmin.
5. Restore previous `application/config/database.php` values if they changed.
6. Clear caches/sessions.
7. Re-run public smoke on `/`, `/login`, and one known internal route.

If only the new YounGo demo DB import fails:

- Do not keep a partially imported database.
- Drop and recreate the target DB.
- Re-import the 1B SQL.
- If import fails again, stop and inspect phpMyAdmin error output before changing source.

## 19. Final Upload Checklist

Before upload:

- Confirm domain/subdomain and document root.
- Confirm cPanel MySQL DB/user/password.
- Confirm client Admin password handoff method.
- Confirm the 1B SQL path and SHA256.
- Confirm media manifest has no missing required files.
- Confirm do-not-upload files are excluded.
- Confirm server backup/rollback path exists.

During upload:

- Upload source/runtime files to the correct document root.
- Upload required media/upload folders.
- Import 1B SQL through phpMyAdmin.
- Update `application/config/database.php`.
- Verify base URL/domain behavior.
- Verify Apache rewrite.
- Verify upload permissions.
- Verify `uploads/.htaccess` protection.

After upload:

- Run public smoke checklist.
- Run guided Admin smoke checklist.
- Check cPanel PHP error log.
- Remove any accidental SQL dumps, package notes, or temp files from public web root.
- Do not push or commit deployment-only server changes unless intentionally ported back to the repo.

## 20. Final Deliverables Summary

Package folder:

```text
D:\Work\YounGo\release_packages\youngo_client_demo_cpanel_2026_07_20_162623
```

Active SQL export:

```text
D:\Work\YounGo\release_packages\youngo_client_demo_cpanel_2026_07_20_162623\database\youngo_client_demo_cpanel_sanitized_1b_2026_07_20_145319.sql
```

SQL:

- Size: `526060` bytes
- SHA256: `14de85b763646aeaf4826c3ffad3ed178ac9d691aaa0e74301d483dce9a75ab6`

Required media:

- `assets/frontend/youngo/images/`
- `uploads/.htaccess`
- `uploads/system/`
- `uploads/user_image/placeholder.png`
- `uploads/blog/page-banner/blog-page.png`
- `uploads/blog/banner/`
- `uploads/blog/thumbnail/`
- `uploads/thumbnails/category_thumbnails/`
- `uploads/thumbnails/course_thumbnails/`

Required config edits:

- `application/config/database.php`
- `application/config/config.php` base URL verification if dynamic base URL is unsuitable for the target.
- cPanel PHP version and PHP INI settings via MultiPHP tools.
- Root `.htaccess` PHP handler only through cPanel MultiPHP Manager if needed.

Remaining owner inputs:

- Final cPanel domain/subdomain.
- Final document root.
- cPanel DB name/user/password.
- Live base URL.
- Client Admin password delivery method.
- Approval to upload and run live smoke.

Recommendation:

- Do not upload until owner confirms the inputs above.
- Use this runbook plus the package `deployment_notes/CPANEL_DEPLOYMENT_RUNBOOK.md`.
