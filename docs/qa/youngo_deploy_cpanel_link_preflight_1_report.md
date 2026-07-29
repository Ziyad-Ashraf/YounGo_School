# DEPLOY.CPANEL.LINK.PREFLIGHT.1

## A. Local Git State

Phase: DEPLOY.CPANEL.LINK.PREFLIGHT.1 - Prepare Safe cPanel Git Link to Production Main

Local root:

```text
D:\Work\YounGo\school
```

Local root markers verified in the project:

```text
index.php
application/
system/
assets/
application/config/
application/views/frontend/youngo/
```

Remote:

```text
origin https://github.com/Ziyad-Ashraf/YounGo_School.git
```

Current local branch:

```text
development
```

Remote branch state at preflight:

```text
origin/main        e9d2794dc6770e082bd33c690c0fb300b6414e6f
origin/development bb6edf41c274b597a79f97bc22e4ddfdfbea50d2
```

Current production branch target:

```text
main
```

Known production-main cleanup commit:

```text
e9d2794 Remove internal docs and diagnostics from production main
```

Local source-change assessment:

```text
No source changes were present before creating this report.
This report is development-side only and must not be added to main unless the owner explicitly approves.
```

## B. cPanel Information Still Needed

Do not link or deploy until the owner confirms these production details:

```text
Domain or subdomain currently serving YounGo
Current live URL
Exact document root, such as public_html or a subfolder
Whether live files are directly inside public_html
Whether cPanel Terminal or SSH access is available
Whether cPanel Git Version Control is available
Whether public_html already contains a .git folder
Current PHP version selected for the site
Hosting account username and absolute account paths if commands will be run
Whether production .user.ini or php.ini differs from Git main
Any known production-only upload or payment evidence folders
Backup/restore method available in cPanel
```

These details are required because the current live site was uploaded manually and may not match Git main byte-for-byte.

## C. Recommended cPanel Linking Method

Recommended safest default:

```text
Use a staging/release folder, pull or clone GitHub main there, then copy/sync code into public_html with explicit preservation excludes.
```

This avoids initializing Git directly over an existing live `public_html` folder and avoids overwriting runtime data.

Option A - cPanel Git Version Control:

```text
1. Do not point cPanel Git Version Control directly at the existing live public_html until backups and exclusions are approved.
2. Clone main into a separate deployment folder if cPanel allows it.
3. Verify the checked-out commit.
4. Sync only code files into public_html with explicit excludes for runtime config, uploads, logs, cache, and server-only files.
```

Option B - SSH or cPanel Terminal with Git:

```text
1. Back up production files and database first.
2. Clone or pull main into a release folder beside or outside public_html.
3. Do not run git clean.
4. Do not run force reset in public_html.
5. Sync release code into public_html with explicit excludes.
```

Option C - Manual Git package:

```text
1. Download the main branch package.
2. Extract it into a staging folder, not directly over public_html.
3. Copy code into public_html with explicit excludes.
4. Preserve production config, uploads, logs, cache, and server-only files.
```

If SSH/Terminal access with `rsync` is available, Option B is the cleanest operational path. If SSH is unavailable but cPanel Git Version Control can clone to a separate folder, Option A is acceptable. If neither is available, Option C is the fallback.

## D. Production Preservation List

The following must be preserved on cPanel and excluded from overwrite during any future code sync:

```text
application/config/database.php
application/config/youngo_paymob.local.php
application/config/youngo_security.local.php
uploads/
application/logs/
application/cache/
backups/
.env
runtime media
client/user uploaded files
payment evidence upload folders if present
server-specific files
production database
production .user.ini if different from Git main
production php.ini if different from Git main
```

`application/config/config.php` should also be reviewed before first sync because CodeIgniter base URL and cookie/session settings can be environment-specific.

Example preservation exclusions for a future owner-approved sync plan:

```text
application/config/database.php
application/config/config.php
application/config/youngo_paymob.local.php
application/config/youngo_security.local.php
.env
uploads/
application/logs/
application/cache/
backups/
```

Do not print or compare credential values in reports or terminal output.

## E. Backup Checklist

Before any future cPanel Git link or code sync, create and verify:

```text
Full public_html file backup
Production database export
uploads/ backup
application/config/ backup
application/logs/ backup if needed for troubleshooting history
application/cache/ backup only if needed; normally cache is disposable, but do not delete it in this phase
Server-only files backup, including .user.ini and php.ini where applicable
Confirmation that backups are downloadable
Confirmation that backups can be restored
Owner confirmation of backup location and timestamp
```

No production backup was created in this preflight because no cPanel access or owner-approved production command was provided.

## F. Safe Deploy/Link Procedure

Future first connection procedure, after owner provides cPanel details and approves exact commands:

```text
1. Confirm the live document root and live URL.
2. Confirm whether public_html already has a .git folder.
3. Create full file, config, uploads, and database backups.
4. Verify backups are downloadable and restorable.
5. Create a staging/release folder outside or beside public_html.
6. Clone or pull https://github.com/Ziyad-Ashraf/YounGo_School.git main into the staging/release folder.
7. Verify the staging/release folder is at the approved main commit.
8. Compare staging runtime roots against current public_html before syncing.
9. Sync code into public_html with explicit excludes for production config, uploads, logs, cache, backups, .env, and server-only files.
10. Do not use delete mode on the first sync unless a separate owner-approved removal list exists.
11. Preserve production DB and do not execute SQL.
12. Run smoke tests.
13. If smoke tests fail, stop and roll back from backups.
```

The first Git-based deployment should be treated as a controlled reconciliation between the manually uploaded live site and Git main, not as a destructive replacement.

## G. Smoke Test Checklist

After a future owner-approved sync, verify:

```text
Homepage loads
/en loads if that route is configured in production
/login loads
/sign_up loads
/home/courses loads
At least one course detail page loads
Frontend CSS and JS assets load
Uploaded images and media still load
Admin login page loads
No visible PHP errors
No HTTP 500 errors
No missing CSS/JS console errors that break layout
Existing production courses/categories/users remain visible
Existing production uploads remain visible
No unexpected checkout/payment exposure appears
No payment gateway status or credentials are changed
```

If Arabic routes are live in production, also smoke-test the current Arabic URL pattern approved by the owner.

## H. Rollback Checklist

If a future sync fails smoke testing:

```text
1. Stop further changes.
2. Restore the previous public_html backup.
3. Restore application/config/ files if any were overwritten.
4. Restore uploads/ if any user/client media was affected.
5. Restore server-only files such as .user.ini or php.ini if affected.
6. Restore the database only if a future phase changed DB state; this preflight and the first link plan should not change DB.
7. Re-run smoke tests against the restored live site.
8. Record the failure and do not retry with destructive commands.
```

## I. Commands That Must Never Be Used

Do not use these commands or equivalent destructive variants during cPanel linking:

```text
git clean
git clean -fdx
git reset --hard origin/main inside live public_html
git checkout -f inside live public_html
git pull --force
force push
rm -rf public_html
Deleting or emptying public_html
git clone directly into non-empty public_html
rsync --delete on the first production sync
SQL import/export or migrations in this phase
Commands that print passwords, API keys, tokens, or database credentials
```

Do not initialize Git over the existing live `public_html` without a separate owner-approved backup and exclusion plan.

## J. Risks/Blockers

Current blockers before execution:

```text
cPanel document root is not yet confirmed.
Current live URL is not yet confirmed.
SSH/Terminal availability is not yet confirmed.
cPanel Git Version Control availability is not yet confirmed.
Existing public_html .git state is not yet confirmed.
Production PHP version is not yet confirmed.
Production server-specific config differences are not yet confirmed.
Production upload/payment evidence folder layout is not yet confirmed.
```

Operational risks:

```text
The manually uploaded live site may contain server-only files not represented in Git.
Git main may differ from live production files because production was uploaded manually.
Overwriting application/config/database.php would break production DB access and may expose/replace credentials.
Overwriting uploads/ could lose client/user media.
Deleting files during the first sync could remove production-only runtime files.
Running SQL or migrations in this phase would violate the phase boundary and risk production data.
```

## K. Recommended Next Phase

Recommended next phase:

```text
DEPLOY.CPANEL.LINK.SAFE_EXECUTE.1
```

Required inputs for that phase:

```text
Owner-confirmed cPanel document root and live URL
Owner-confirmed SSH/Terminal or cPanel Git Version Control availability
Owner-confirmed backup completion
Owner approval of exact clone/sync commands
Owner confirmation of production-only files and folders to preserve
```

Recommended execution scope:

```text
Create verified backups.
Create staging/release folder.
Clone or pull main to staging/release.
Sync into public_html with explicit excludes.
Do not touch DB.
Do not run git clean.
Do not force reset.
Run smoke tests.
Rollback if smoke fails.
```

