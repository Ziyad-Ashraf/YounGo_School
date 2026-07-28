# YounGo Client Package 1B Admin Export Hygiene Report

Phase: `CLIENT.PACKAGE.1B - Admin Account and Sanitized Export Hygiene Pass`

Date: 2026-07-20

Branch: `analysis/cms-audit`

Deployment target: cPanel hosting

Package folder:

```text
D:\Work\YounGo\release_packages\youngo_client_demo_cpanel_2026_07_20_162623
```

## 1. Executive Result

CLIENT.PACKAGE.1B fixed the restore-test hygiene issues found in CLIENT.PACKAGE.2 and produced a new improved sanitized SQL export for the cPanel package.

Result: package DB/export hygiene passed, with one operational credential handoff item remaining.

Completed:

- Created a fresh full DB backup before live DB writes.
- Kept Root Admin protected and did not use Root Admin as the client demo login.
- Retained the existing normal client Admin account.
- Expanded the client Admin's legacy permission row from course-only to broad operational demo access.
- Cleared stale `users.sessions` values in the live local DB after backup.
- Generated a new `1B` sanitized SQL export.
- Excluded local/test/demo-only users from the new client export.
- Imported the new export into a fresh restore-test DB.
- Validated admin/session/content/payment hygiene after restore.
- Ran public smoke against the restored 1B DB.

No commit, push, cPanel upload, deployment, Root Admin credential change, payment/Paymob/checkout/coupon enablement, source feature change, language JSON change, or legacy Arabic phrase-table repair was performed.

Passwords were not printed or written into this report.

## 2. Git State

Starting checks:

- `git branch --show-current`: `analysis/cms-audit`
- Latest commit: `49aa360 Test YounGo cPanel package restore locally`
- Starting `git status --short`: clean

## 3. Backup Created

Backup before DB writes:

```text
D:\Work\YounGo\backups\youngo_school_before_client_package_1b_admin_export_hygiene_2026_07_20_170414.sql
```

Metadata:

- Size: `624591` bytes
- SHA256: `45B94B7D6B02E71796114A9D3027DB988F127411E67E03DA0EA90CDA96141C86`

The live DB writes were performed only after this backup was created.

## 4. Admin Account Readiness

Client demo Admin account:

- User id: `7`
- Name: `Client Admin`
- Email: `client@gmail.com`
- Role: normal Admin, `role_id = 1`
- Status: active
- Root Admin: no

Root Admin handling:

- Root Admin user id `1` was retained.
- Root Admin identity and credentials were not changed.
- Root Admin should not be used as the client demo login.

Client Admin permission state after hygiene pass:

```json
["blog","category","contact","course","enrolment","instructor","messaging","newsletter","revenue","settings","student","theme","user"]
```

The client Admin is now broad enough for normal operational demo access across content, courses, categories/subcategories, lessons, Blog, contact, users/students/instructors, newsletter/messaging, theme, and legacy website/homepage/frontend settings.

Credential handoff note:

- The client Admin password was not inspected, printed, or changed.
- Before client upload or handoff, the owner should confirm the client Admin password through a secure out-of-band process.

## 5. Permission/Hygiene Changes Made

Live DB changes made after backup:

- Updated `permissions.admin_id = 7`.
- Cleared stale `users.sessions` values for 2 user rows.

Permission row before:

```json
["course"]
```

Permission row after:

```json
["blog","category","contact","course","enrolment","instructor","messaging","newsletter","revenue","settings","student","theme","user"]
```

Excluded legacy permission keys for the client Admin:

- `admin`
- `addon`
- `academy_cloud`
- `coupon`

Important legacy limitation:

- The current Academy LMS permission model gates `frontend_settings`, `youngo_homepage`, `payment_settings`, `manage_language`, system settings, SMTP/server settings, and other settings children behind the same coarse `settings` key.
- `settings` is included so the client Admin can reach website/frontend/YounGo homepage settings.
- Payment, language/phrase, SMTP/server, database/backup/dev, installer/license/update, and Root/admin internals remain do-not-show in the guided demo.
- A true child-level settings permission split would require a source feature phase and was not implemented here.

## 6. Session Cleanup Result

Before cleanup:

- `users.sessions` had non-empty stale session arrays for user ids `2` and `8`.

After cleanup:

- `users.sessions` non-empty rows: `0`

The live local `ci_sessions` runtime table was not truncated in this phase. The sanitized export still imports `ci_sessions` as structure-only with 0 rows.

## 7. Local-Only User/Data Handling

The live local DB users were not deleted.

The new sanitized export keeps only:

- User id `1`: protected Root Admin.
- User id `7`: normal client Admin.

The new sanitized export excludes local/test/demo-only user ids:

- `2`
- `5`
- `6`
- `8`
- `9`

Export exclusions also removed related local/test references where applicable:

- Permission row for excluded local admin id `9`.
- Local/test user reference rows from non-structure-only tables.

Validation after restore found:

- Restored `users`: `2`
- Local-only email matches: `0`
- Non-empty `users.sessions`: `0`

## 8. New Sanitized Export Metadata

New 1B sanitized SQL:

```text
D:\Work\YounGo\release_packages\youngo_client_demo_cpanel_2026_07_20_162623\database\youngo_client_demo_cpanel_sanitized_1b_2026_07_20_145319.sql
```

Metadata:

- Size: `526060` bytes
- SHA256: `14de85b763646aeaf4826c3ffad3ed178ac9d691aaa0e74301d483dce9a75ab6`
- Base tables: `64`
- Exported data rows: `2122`
- Previous export rows: `2140`
- Previous export size: `532821` bytes

Key retained content/settings:

- YounGo demo courses/categories/sections/lessons.
- Blog posts.
- `youngo_blog_translations`.
- YounGo course/category/section/lesson translation tables.
- Contact/frontend settings.
- `frontend_settings.theme = youngo`.
- EGP currency settings.
- Required Phase 2 schema tables.

Sanitization retained or improved:

- Course `43` excluded.
- `ci_sessions` structure-only.
- Runtime/payment/access/coupon tables structure-only.
- `users.sessions` force-cleared in exported users.
- Payment gateways disabled/redacted.
- Credential-like `settings` values redacted.
- Credential-like `frontend_settings` reCAPTCHA values redacted.
- User payment keys redacted.
- Local/test/demo-only users excluded.

Supporting package artifacts:

- `database/DB_EXPORT_TABLE_SUMMARY_1B_2026_07_20_145319.csv`
- `checks/db_export_metadata_1b_2026_07_20_145319.json`
- `checks/client_admin_hygiene_1b.json`
- `deployment_notes/DB_EXPORT_NOTES_1B_2026_07_20_145319.txt`
- `checks/CLIENT_PACKAGE_1B_RESULT.txt`

## 9. Restore/Import Validation Result

Restore-test DB:

```text
youngo_client_restore_test_1b_20260720
```

Import validation:

- Import success: yes
- Base tables restored: `64`
- Import errors: none

Restored DB validation:

| Check | Result |
| --- | ---: |
| `users` | 2 |
| `permissions` | 1 |
| `course` | 7 |
| `category` | 12 |
| `section` | 20 |
| `lesson` | 40 |
| `blogs` | 4 |
| `youngo_blog_translations` | 8 |
| `ci_sessions` immediately after import | 0 |
| `payment` | 0 |
| `enrol` | 0 |
| `youngo_course_access` | 0 |
| `youngo_user_subscriptions` | 0 |
| `youngo_manual_grants` | 0 |
| `youngo_checkout_orders` | 0 |
| `youngo_coupon_usages` | 0 |

Content/config validation:

- Course `43` count: `0`
- Blog translations: `4` English, `4` Arabic
- `settings.system_currency`: `EGP`
- `settings.currency_position`: `left`
- `settings.system_name`: `YounGo`
- `frontend_settings.theme`: `youngo`
- `frontend_settings.youngo_homepage_content`: present
- `frontend_settings.contact_info`: present
- Settings scan for local paths/domains: no matches

Payment validation:

- Payment gateway rows: `15`
- Active gateway rows: `0`
- Non-empty gateway key rows: `0`
- Non-empty gateway model rows: `0`
- Test-mode enabled rows: `0`

Public restore smoke:

- Runtime base: `http://school.local/`
- Temporary DB target: `youngo_client_restore_test_1b_20260720`
- Pages checked: `14`
- Failed pages: `0`
- Local images checked: `96`
- Broken local images: `0`
- Config restored: yes
- Config SHA256 before/after matched: `F26EC021DC62A959B2CFC1E4533B7C84D2F31C16CB5D4C6F5A97E995D8C0F2C0`

Smoke pages checked:

- `/`
- `/ar`
- `/home/courses`
- `/ar/courses`
- `/home/course/scratch-coding-for-young-creators/1`
- `/ar/course/scratch-coding-for-young-creators/1`
- `/blog`
- `/ar/blog`
- `/contact`
- `/ar/contact`
- `/login`
- `/ar/login`
- `/sign_up`
- `/ar/sign-up`

The runtime smoke created normal local test session rows in the isolated restore-test DB after the import validation. The SQL export itself still imports `ci_sessions` empty.

## 10. Remaining Blockers

No package restore blocker remains for the public cPanel demo after the 1B export.

Remaining operational items before handoff/upload:

- Confirm the client Admin password securely out-of-band; it was not changed or printed here.
- In the guided dashboard demo, do not show payment/Paymob/checkout/coupon settings, language/phrase editor, SMTP/server credentials, backup/dev settings, installer/license/update/system-version areas, Root Admin identity, role/grant internals, or incomplete legacy/custom builder areas.
- Treat the coarse legacy `settings` permission as a demo-management risk until a future source phase separates safe website/homepage settings from core/server/payment/language settings.

## 11. Next Phase Recommendation

Recommended next phase:

```text
CLIENT.PACKAGE.2B - Restore Test From 1B Sanitized Export
```

Scope:

- Re-run focused restore validation from the new 1B export.
- Optionally verify client Admin login if the owner supplies the client Admin password securely.
- Confirm cPanel upload checklist uses the 1B SQL as the active DB package.

After that:

```text
CLIENT.UPLOAD.1 - cPanel Server Upload Checklist / Deployment Runbook
CLIENT.SMOKE.1 - Post-Upload Public Smoke Test
```
