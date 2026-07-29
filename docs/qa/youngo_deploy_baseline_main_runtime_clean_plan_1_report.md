# DEPLOY.BASELINE.MAIN.RUNTIME.CLEAN.PLAN.1 Report

## A. Local Root

Local root confirmed:

```text
D:\Work\YounGo\school
```

Required project paths are present:

- `index.php`
- `application/`
- `system/`
- `assets/`
- `application/config/`
- `application/views/frontend/youngo/`

## B. Branch/Remote State

Current local branch:

```text
development
```

Remote URL:

```text
https://github.com/Ziyad-Ashraf/YounGo_School.git
```

Remote heads checked during this phase:

- `origin/main` -> `440e992c02c7ff72cd4c640563b3873b23ec0880`
- `origin/development` -> `db1e1983b91daf8a2f5e9504ad1573fa52b8e17d`

Current working tree before creating this report:

```text
clean
```

Working tree source-change status:

- No pending tracked source changes were present.
- No untracked reports were present before this report.
- No branch checkout, staging, commit, push, deploy, DB operation, SQL execution, cleanup, or file deletion was performed.

## C. Current origin/main Runtime Problem Summary

`origin/main` is security-safe enough to continue based on the previous post-push audit, but it is not yet lean enough to be the long-term cPanel/public_html production branch.

Tracked file count on `origin/main`:

```text
9166
```

Top-level tracked paths on `origin/main`:

| Path | Count | Runtime cleanup classification |
|---|---:|---|
| `.htaccess` | 1 | Keep on main |
| `.user.ini` | 1 | Owner decision |
| `.gitignore` | 1 | Remove from public runtime package; owner decision for Git branch |
| `AGENTS.md` | 1 | Remove from main later |
| `YOUNGO_PROJECT_CONTEXT.md` | 1 | Remove from main later |
| `application/` | 5015 | Keep on main, with selective cleanup decisions |
| `assets/` | 3485 | Keep on main, with selective cleanup decisions |
| `composer.json` | 1 | Owner decision |
| `database/` | 25 | Remove from public runtime; owner decision for main |
| `docs/` | 184 | Remove from main later |
| `index.php` | 1 | Keep on main |
| `languages/` | 16 | Owner decision |
| `php.ini` | 1 | Owner decision |
| `scripts/` | 179 | Remove from main later |
| `system/` | 202 | Keep on main |
| `update/` | 25 | Owner decision |
| `uploads/` | 27 | Keep guard files only |

Main currently includes development docs, QA reports, planning markdown, diagnostics, SQL artifacts, fixtures, updater files, vendor docs/tests/examples, and demo assets that are not required for serving the public site.

## D. Keep-On-Main List

These paths should remain on production `main` because they are required or expected for CodeIgniter runtime:

| Path | Why keep | Notes |
|---|---|---|
| `index.php` | Public CodeIgniter entry point | Required in public_html. |
| `.htaccess` | Rewrite/security behavior | Required for clean URLs and Apache handling. |
| `application/` | App controllers, models, views, helpers, libraries, config scaffolding | Keep, but review vendor docs/tests/examples inside it separately. |
| `application/cache/index.html` | Writable cache folder guard | Keep guard file only; runtime cache contents stay excluded. |
| `application/logs/index.html` | Writable logs folder guard | Keep guard file only; runtime logs stay excluded. |
| `application/config/index.html` and safe config scaffolding | Runtime app config folder structure | Do not track server secrets. |
| `application/views/frontend/youngo/` | YounGo frontend theme views | Required. |
| `assets/` | Public CSS/JS/images/fonts/vendor assets | Keep runtime assets, but review template demo assets. |
| `assets/frontend/youngo/` | YounGo frontend CSS/images | Required. |
| `system/` | CodeIgniter framework core | Required. |
| `uploads/` guard files | Writable upload folder structure and access guards | Keep only `.htaccess`, `index.html`, and `htaccess_domain_wise`. |
| `.user.ini` | Possible cPanel PHP runtime setting | Keep only if confirmed needed on the target host. |

Already excluded and still required as server-only files:

- `application/config/database.php`
- `application/config/config.php`
- `application/config/youngo_paymob.local.php`
- `application/config/youngo_security.local.php`
- runtime `uploads/` media
- runtime `application/logs/` contents
- runtime `application/cache/` contents

## E. Remove-From-Main Proposed List

These are proposed for removal from `main` in a later cleanup phase while staying available on `development`.

| Path | Category | Why not production runtime | Removal risk | Stay on development | Owner confirmation |
|---|---|---|---|---|---|
| `docs/` | Docs/QA/internal | Internal plans, QA reports, references, deployment notes, screenshots references, payment/localization history | Low for runtime | Yes | Recommended before cleanup |
| `AGENTS.md` | Internal agent instructions | Reveals AI/development operating rules, not app runtime | Low | Yes | Recommended before cleanup |
| `YOUNGO_PROJECT_CONTEXT.md` | Internal project context | Reveals internal roadmap/history, not app runtime | Low | Yes | Recommended before cleanup |
| `scripts/` | Diagnostics/QA/migration helpers | 179 development scripts, fixtures, runtime tests, seed/apply helpers, SQL helpers | Low for normal runtime; medium if someone expects on-server diagnostics | Yes | Recommended before cleanup |
| `database/phase_2/qa/` | QA SQL datasets | Test data source only | Low | Yes | Recommended before cleanup |
| `database/phase_2/demo_alignment/` | Demo alignment SQL/docs | Dev/demo data alignment source, not runtime | Low | Yes | Recommended before cleanup |
| `database/phase_2/youngo_courses_44_45_seed.sql` | Demo seed SQL | Demo/dev seed source | Low | Yes | Recommended before cleanup |
| `.gitignore` | Git metadata | Not needed inside deployed public runtime package | Low for deployment package; medium for branch hygiene | Yes | Owner decision if removing from `main` branch itself |

Recommended cleanup stance:

- Remove docs/internal/report/script paths from `main`.
- Keep them fully available on `development`.
- Do not delete local files during the cleanup implementation; only change the production branch/index after approval.

## F. Owner-Decision List

These paths should not be removed automatically until the owner approves the runtime policy.

| Path | Why owner decision is needed | Suggested default |
|---|---|---|
| `database/` | SQL is not public runtime, but migration/source SQL may be needed for controlled deploy packages | Remove from public_html/main only if migrations are preserved on development or a private deploy package. |
| `scripts/**/*.sql` | SQL helpers are not runtime but may be useful for deployer-controlled migrations | Keep on development; exclude from production public runtime. |
| `update/` | `application/controllers/Updater.php` references `./update/{package}/update_config.json` and `update_script.php`; `system_settings.php` posts to `updater/update` | Exclude only if web-based Academy updater is not supported on production. |
| `languages/` | Top-level language JSON exports/packs; direct runtime use was not confirmed from narrowed source checks | Verify with runtime/code owner before removal. |
| `composer.json` | Root Composer metadata may be unnecessary if dependencies are bundled, but useful for dependency review | Keep or exclude based on deployment method. |
| `php.ini` | May be ignored or overridden by cPanel; host-specific | Keep only if target host honors and needs it. |
| `.user.ini` | cPanel/PHP-FPM setting file may be useful at runtime | Keep if currently needed on cPanel. |
| Vendor docs/LICENSE files | Public bloat, but license retention may be a compliance concern | Prefer excluding from public package only after legal/runtime review. |
| `application/libraries/xendit/examples/` | Vendor example PHP files, not app runtime | Remove from main only after confirming Xendit library does not autoload examples. |
| `application/libraries/xendit/tests/` | Vendor tests, not app runtime | Remove from main after confirming not autoloaded. |
| `application/libraries/phpqrcode/cache/` | 400 tracked cache/template files; may be library-supplied QR assets | Runtime verification needed before pruning. |
| `assets/backend/js/pages/demo.*` | Admin template demo scripts; may or may not be referenced by legacy dashboard pages | Remove only after asset reference check. |
| `assets/frontend/default-new/css/fonts/custom/*/demo.html` | Font demo HTML pages, not runtime | Low risk, but verify no references. |

## G. docs/Markdown Recommendation

Markdown/internal docs found on `origin/main`:

- `docs/agents/`: 3 markdown files
- `docs/design/`: 1 markdown file
- `docs/planning/`: 17 markdown files
- `docs/qa/`: 145 markdown/report files
- `docs/reference/`: 7 markdown/reference files plus reference images/HTML
- root internal docs: `AGENTS.md`, `YOUNGO_PROJECT_CONTEXT.md`
- database README files: `database/phase_2/demo_alignment/README.md`, `database/phase_2/qa/README.md`
- vendor markdown files under `application/libraries/`: 38

Recommendation:

- Remove project docs, reports, prompts, planning, QA reports, and database README files from production `main`.
- Keep all project docs and reports on `development`.
- Optionally preserve an archive branch such as `docs/archive` later, if the owner wants long-term frozen report history outside `development`.
- Treat vendor markdown separately from project docs because license/compliance and package integrity may matter.

## H. scripts/Diagnostics Recommendation

`origin/main` contains only `scripts/phase_2/` under top-level `scripts/`.

Script classification:

- diagnostics: 113 files
- runtime/browser QA scripts: 9 files
- seed/apply/repair helpers: 16 files
- SQL migration/schema helpers: 23 files
- fixture payloads: 7 files
- other helpers: 11 files

Recommendation:

- Remove `scripts/` from production `main` in the cleanup implementation phase.
- Preserve `scripts/` on `development`.
- If a future deployment needs migrations, package the exact approved SQL/PHP migration files outside public_html and remove them after use.
- Do not run these scripts on cPanel unless a future phase explicitly authorizes it.

## I. database/SQL Recommendation

SQL files found on `origin/main`:

- database schema/migration SQL: 15
- database demo/seed SQL: 6
- database QA dataset SQL: 2
- script schema/migration SQL: 21
- script proposed SQL: 2

Recommendation:

- Do not deploy `database/` or SQL artifacts inside public_html runtime.
- Keep source SQL on `development` for auditability and controlled migration planning.
- If production migration SQL is needed, create a separate non-public deployment package with only the exact approved files.
- Do not execute SQL during cleanup.

Proposed main cleanup default:

- Remove `database/phase_2/qa/` and demo/seed SQL from `main`.
- Remove the rest of `database/` from `main` only if the owner confirms migration source should live solely on `development` or a deploy package.

## J. update/ Recommendation

`update/update_7.1/` contains 25 files, including copied controllers, views, assets, `common_script.php`, `update_config.json`, and `update_script.php`.

The legacy updater flow references this path:

- `application/controllers/Updater.php` reads `./update/{package}/update_config.json`.
- `application/controllers/Updater.php` requires `./update/{package}/update_script.php`.
- `application/views/backend/admin/system_settings.php` posts to `updater/update`.

Recommendation:

- Mark `update/` as owner decision.
- Remove from production `main` only if the owner confirms web-based Academy update packages are not supported on the production server.
- If retained, consider protecting access to updater routes and keeping update packages outside public web access where possible.

## K. Vendor/Demo/Test Recommendation

Potential cleanup candidates inside otherwise runtime-looking areas:

- vendor docs/license/readme/changelog files under `application/libraries/`: 80 files identified.
- `application/libraries/xendit/examples/`: vendor examples.
- `application/libraries/xendit/tests/`: vendor tests.
- `assets/backend/js/pages/demo.*`: 33 admin template demo JavaScript files.
- `assets/frontend/default-new/css/fonts/custom/*/demo.html`: 4 font demo pages.
- `application/libraries/phpqrcode/cache/`: 400 tracked cache/template files.

Recommendation:

- Do not remove core vendor source files without runtime tests.
- Treat vendor examples/tests as likely removable after autoload/reference checks.
- Treat vendor docs and license files as owner/legal decision.
- Treat admin template demo JS as owner/runtime decision until reference checks confirm unused files.
- Treat `phpqrcode/cache/` as owner/runtime decision because library behavior may depend on those files.

## L. Proposed Cleanup Implementation Procedure

Future implementation phase:

```text
DEPLOY.BASELINE.MAIN.RUNTIME.CLEAN.1
```

Safe procedure:

1. Confirm owner approval of this cleanup plan.
2. Confirm `development` contains all docs/reports/scripts that will be removed from `main`.
3. Create a cleanup branch from `main`, for example `cleanup/main-runtime-only`, or work on `main` only after explicit approval.
4. Remove approved non-runtime files from the production branch only.
5. Prefer Git index/branch cleanup over filesystem deletion when local preservation matters.
6. Do not use `git clean`.
7. Do not touch cPanel.
8. Do not touch DB.
9. Do not execute SQL.
10. Do not remove server-only ignored files.
11. Run static checks and, if available, local smoke tests from the cleaned tree.
12. Review the diff carefully.
13. Commit cleanup only after review.
14. Push `main` only after owner approval.
15. Verify `development` still contains docs/reports/diagnostics after cleanup.

Suggested first cleanup batch after approval:

- `docs/`
- `AGENTS.md`
- `YOUNGO_PROJECT_CONTEXT.md`
- `scripts/`

Suggested second cleanup batch after owner decision:

- `database/`
- `update/`
- `languages/`
- root `composer.json`, `php.ini`, `.user.ini` policy
- vendor examples/tests/docs/demo assets

## M. cPanel Preservation Rules

After production `main` is cleaned, cPanel future updates may pull from `main`, but must preserve server-only and runtime-owned files/directories.

Preserve on cPanel:

- `application/config/database.php`
- `application/config/config.php`
- `application/config/youngo_paymob.local.php`, if used
- `application/config/youngo_security.local.php`, if used
- `uploads/`
- `application/logs/`
- `application/cache/`
- server-created media and evidence files
- server-specific `.htaccess`, `.user.ini`, or `php.ini` adjustments if manually changed on host
- any server-only files required by the hosting provider

Rules:

- Never run `git clean -fdx` on cPanel.
- Never overwrite server credentials with local placeholders.
- Never remove runtime uploads during Git update.
- Keep production secrets configured only on the server or approved secret storage.
- Keep migration SQL outside public_html unless a temporary controlled deploy step explicitly requires it.

## N. Risks/Blockers

Risks:

- Removing `update/` disables or changes the legacy Academy web updater flow.
- Removing `languages/` may break a legacy import/export path if the top-level language JSON files are still expected.
- Removing vendor examples/tests is likely safe but must be checked against package autoloading.
- Removing vendor docs/licenses may create license/compliance concerns.
- Removing `assets/backend/js/pages/demo.*` could break legacy admin pages if those files are still referenced.
- Removing `database/` from `main` means production migration source must be preserved elsewhere.
- cPanel already has a manually uploaded working site, so future Git pulls must avoid overwriting server-only config and runtime media.

Blockers:

- Owner confirmation is needed before removing anything from `main`.
- Runtime smoke testing should be planned for any cleaned production tree before pushing it as the future update baseline.

## O. Final Git Status

Final local status after creating this report:

```text
?? docs/qa/youngo_deploy_baseline_main_runtime_clean_plan_1_report.md
```

This report is development-side only and must not be added to production `main`.
