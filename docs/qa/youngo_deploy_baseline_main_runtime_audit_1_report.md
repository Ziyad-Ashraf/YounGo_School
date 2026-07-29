# DEPLOY.BASELINE.MAIN.RUNTIME.AUDIT.1 Report

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

Remote heads:

- `origin/main` -> `440e992c02c7ff72cd4c640563b3873b23ec0880`
- `origin/development` -> `ace0a8ee9d0d945b6d869d75e076379dc81ca68c`

Current local working tree had an existing untracked report before this phase:

```text
?? docs/qa/youngo_deploy_baseline_post_push_security_audit_1_report.md
```

No branch checkout, staging, commit, push, deploy, DB operation, cleanup, or file deletion was performed.

## C. origin/main Top-Level Classification

Tracked file count on `origin/main`:

```text
9166
```

Top-level tracked paths:

| Path | Count | Classification | Notes |
|---|---:|---|---|
| `.htaccess` | 1 | Production runtime required | Apache rewrite/security entry file. |
| `.user.ini` | 1 | Possibly runtime required | cPanel/PHP runtime settings; owner/server decision. |
| `.gitignore` | 1 | Development/source-control only | Not needed inside public runtime deployment package. |
| `AGENTS.md` | 1 | Development/docs/QA only | Internal agent instructions. |
| `YOUNGO_PROJECT_CONTEXT.md` | 1 | Development/docs/QA only | Internal project context. |
| `application/` | 5015 | Production runtime required, with cleanup caveats | Core CodeIgniter app, controllers, models, views, config examples, libraries. |
| `assets/` | 3485 | Production runtime required, with cleanup caveats | Public CSS/JS/images/vendor assets. |
| `composer.json` | 1 | Needs owner decision | Not required if dependencies are bundled and no Composer install runs on server. |
| `database/` | 25 | Development/deployment source, not public runtime | Schema/migration/demo SQL should not sit in public_html runtime. |
| `docs/` | 184 | Development/docs/QA only | Internal docs, plans, QA reports, deployment notes, references. |
| `index.php` | 1 | Production runtime required | Public entry point. |
| `languages/` | 16 | Needs owner decision | Top-level language JSON pack/export files; no direct runtime reference confirmed in narrow grep. |
| `php.ini` | 1 | Needs owner decision | Host-level PHP config; may not be honored on cPanel and may be better server-managed. |
| `scripts/` | 179 | Development/deployment tooling only | Diagnostics, QA scripts, migration helpers, fixtures. |
| `system/` | 202 | Production runtime required | CodeIgniter framework core. |
| `update/` | 25 | Needs owner decision | Academy updater package referenced by `Updater.php`; should be excluded if production updates are not performed through web UI. |
| `uploads/` | 27 | Possibly runtime required | Only guard files tracked; writable directories still needed on server. |

## D. Production-Runtime Required Files/Folders

Recommended to stay on production `main` or be included in runtime deployment:

- `index.php`
- `.htaccess`
- `application/`, excluding optional docs/tests/examples where safe
- `system/`
- `assets/`, excluding demo/template-only assets where safe
- `uploads/` guard files and required writable folder placeholders
- `.user.ini` only if confirmed useful for the target cPanel runtime

Runtime notes:

- `application/config/database.php` and server-specific `application/config/config.php` must remain untracked and be configured on the server.
- `application/cache/index.html` and `application/logs/index.html` are guard files only; runtime cache/log contents should remain untracked.
- `uploads/` should be created/writable on the server, but runtime media should not be tracked in Git unless a curated deployment media package is explicitly approved.

## E. Development/Docs/QA-Only Files/Folders Found On Main

`origin/main` currently contains documentation and internal reports that are useful for project continuity but should not be deployed to production/public_html:

- `AGENTS.md`
- `YOUNGO_PROJECT_CONTEXT.md`
- `docs/agents/` - 3 files
- `docs/design/` - 1 file
- `docs/planning/` - 17 files
- `docs/qa/` - 145 files
- `docs/reference/` - 18 files

`docs/qa/` includes internal reports by category:

- deployment/client/runtime reports: 9
- general QA/audit/demo reports: 11
- localization/language/translation reports: 43
- payment/Paymob/Instapay/checkout/coupon reports: 82

Recommendation:

- Keep these files on `development` or a docs/archive branch.
- Remove them from production `main` in a later reviewed cleanup phase.
- Do not move or delete them in this audit phase.

## F. Scripts/Tools Classification

`origin/main` contains `scripts/phase_2/` with 179 tracked files:

- diagnostic PHP scripts: 113
- runtime/browser QA PHP scripts: 9
- other seed/helper PHP scripts: 19
- SQL schema/migration files: 23
- fixture/seed JSON files: 15

Classification:

- Needed on production server: none by default.
- Development-only: diagnostics, runtime tests, QA helpers, browser QA scripts, phrase/import helpers, local audit scripts.
- Migration/schema source: SQL files may be needed by a controlled deployer outside public web root, not inside `public_html`.
- Should remain on development but not main/public runtime: all `scripts/phase_2/` unless a future deployment runbook explicitly names a non-public server-side migration package.

Public runtime concern:

- These scripts reveal internal implementation, QA strategy, payment planning, schema intentions, and route/security checks.
- Several scripts are executable PHP files. Even if app routing blocks direct execution in normal cases, placing them under public web root is unnecessary exposure.

## G. SQL/Database Files Classification

Tracked SQL files on `origin/main`:

- `database/phase_2/`: 25 SQL/source files
- `scripts/phase_2/`: 23 SQL/source files
- total SQL files: 48

Observed SQL categories:

- database phase schema/migration files: 16
- database demo seed/alignment files: 3
- database QA dataset files: 2
- other database SQL files: 2
- script phase schema/migration files: 22
- proposed script SQL files: 1

Classification:

- Source schema/reference: yes, useful in Git for development and controlled review.
- Controlled production deployment package: selected schema/migration SQL may be needed separately when a deployment phase explicitly approves it.
- Test/demo/dev-only: QA datasets, demo alignment, seed/testing SQL.
- Public_html runtime: should not be deployed by default.

Recommendation:

- Keep SQL artifacts in development/source branches or a private migration package.
- Do not include `database/` or `scripts/**/*.sql` in public runtime deployment unless the server deployment step explicitly needs them outside web root.

## H. Public Exposure / Non-Secret Risk Findings

No new secret audit was performed in this phase beyond avoiding content printing. This phase focused on non-secret public exposure and deployment bloat.

Non-secret content on `origin/main` that should not be publicly deployed:

- `docs/qa/` reports exposing internal QA history, payment readiness, cPanel notes, deployment process, and implementation decisions.
- `docs/planning/` exposing roadmap, architecture, future payment/subscription/role plans, and rollout sequence.
- `docs/agents/` and `AGENTS.md` exposing internal agent prompts and operating rules.
- `YOUNGO_PROJECT_CONTEXT.md` exposing internal project history and development state.
- `scripts/phase_2/` exposing diagnostics, fixtures, runtime tests, migration helpers, payment/Paymob/Instapay work, and schema plans.
- `database/phase_2/qa/` exposing QA datasets.
- `docs/reference/stitch_outputs/` exposing prototype/reference material not needed at runtime.

Runtime-looking bloat/exposure inside otherwise valid runtime areas:

- `application/libraries/*/README`, `CHANGELOG`, `LICENSE`, docs, and similar vendor metadata: 80 files identified.
- `application/libraries/xendit/examples/` and `application/libraries/xendit/tests/`: 47 files identified.
- `assets/backend/js/pages/demo.*`: 33 admin template demo JavaScript files identified.
- `assets/frontend/*/fonts/*/demo.html`: 4 font demo HTML files identified.
- `application/libraries/phpqrcode/cache/`: 400 tracked cache/template files; owner/runtime verification needed before deciding whether to prune.

Needs owner/runtime decision:

- `update/update_7.1/` is referenced by `application/controllers/Updater.php`, but it looks like an Academy LMS update package. It should not be included in a lean production package unless web-based Academy updates are intentionally supported.
- `languages/` top-level JSON files may be language pack exports or legacy support; no narrow runtime reference was confirmed.
- `composer.json`, root `php.ini`, and `.user.ini` need deployment policy decisions based on cPanel setup.

## I. Recommended Main Inclusion Policy

Production `main` should include only:

- CodeIgniter runtime entry/config scaffolding: `index.php`, `.htaccess`, approved `.user.ini` if needed.
- Runtime app code under `application/`.
- Runtime framework code under `system/`.
- Runtime public assets under `assets/`.
- Required safe upload/cache/log guard files and empty-folder placeholders.
- Safe config examples only where they are needed for deployment reference and contain no real values.
- Bundled PHP libraries only when actually used by the app at runtime.

## J. Recommended Main Exclusion Policy

Production `main` should exclude:

- `docs/`
- `docs/qa/`
- internal phase reports and audit reports
- screenshots and design references
- local diagnostics and QA scripts
- `scripts/`
- `database/` SQL artifacts from public runtime
- QA datasets and demo seed SQL
- local deployment/package reports
- backups and release packages
- runtime uploads and payment evidence
- local config/secrets and DB dumps
- vendor examples/tests/docs where safe to remove
- admin template demo assets where not used
- updater packages unless explicitly approved for runtime support

## K. Proposed Cleanup Approach

Recommended future phase:

```text
DEPLOY.BASELINE.MAIN.RUNTIME.CLEAN.PLAN.1
```

That phase should:

- Decide exact paths to remove from `main` only.
- Keep documentation/reports on `development` or a docs/archive branch.
- Avoid deleting local files.
- Use branch-specific cleanup carefully, likely through `git rm --cached` or a clean production branch/package strategy after owner review.
- Verify the app still runs from the cleaned production tree.
- Keep SQL/migration files available in a non-public deployment package if needed.
- Not touch cPanel.
- Not touch DB.
- Not push until owner review and approval.

Suggested first cleanup candidates for review:

- `docs/`
- `AGENTS.md`
- `YOUNGO_PROJECT_CONTEXT.md`
- `scripts/`
- `database/`
- `update/` if updater support is not required
- `application/libraries/xendit/examples/`
- `application/libraries/xendit/tests/`
- vendor README/CHANGELOG/docs/examples where safe
- `assets/backend/js/pages/demo.*` where unused
- frontend font demo HTML files

## L. Risks/Blockers

Cleanup must be planned carefully because this is an inherited Academy LMS codebase:

- Some vendor metadata/examples may be harmless but bundled by upstream package expectations.
- Some `assets/backend/js/pages/demo.*` files may still be referenced by the legacy admin dashboard.
- `update/` is referenced by `Updater.php`; excluding it changes update-flow availability.
- `languages/` needs owner/code verification before removal.
- Root `php.ini` and `.user.ini` behavior depends on the target cPanel host.
- Removing files from `main` must not remove the local docs history or development branch continuity.

No blocker prevents planning a production-main cleanup, but owner approval is required before removing tracked files from `main`.

## M. Final Git Status

Final local status after creating this report:

```text
?? docs/qa/youngo_deploy_baseline_main_runtime_audit_1_report.md
?? docs/qa/youngo_deploy_baseline_post_push_security_audit_1_report.md
```

This report is intentionally uncommitted and must not be added to `main`.
