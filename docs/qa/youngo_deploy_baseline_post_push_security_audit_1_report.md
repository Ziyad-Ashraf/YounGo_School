# DEPLOY.BASELINE.POST_PUSH.SECURITY.AUDIT.1 Report

## A. Local Root

Local root confirmed:

```text
D:\Work\YounGo\school
```

Required project root paths were present:

- `index.php`
- `application/`
- `system/`
- `assets/`
- `application/config/`
- `application/views/frontend/youngo/`

Initial working tree status before this report was clean.

## B. Remote/Branch State

Remote:

```text
origin  https://github.com/Ziyad-Ashraf/YounGo_School.git
```

Live remote heads from `git ls-remote --heads origin`:

- `refs/heads/main` -> `440e992c02c7ff72cd4c640563b3873b23ec0880`
- `refs/heads/development` -> `ace0a8ee9d0d945b6d869d75e076379dc81ca68c`

Local branches:

- `development` at `ace0a8e`, tracking `origin/development`
- `main` at `440e992`, tracking `origin/main`

Important branch note:

- The live remote currently advertises only `main` and `development`.
- Local `git branch -a` still shows a stale remote-tracking ref for `origin/analysis/cms-audit`; it was not present in `git ls-remote --heads origin` and was not treated as a relevant pushed remote head for this audit.

`origin/development` differs from `origin/main` only by:

```text
A docs/qa/youngo_deploy_baseline_local_to_github_main_1_report.md
```

## C. Main Tracked-File Audit

Audited ref:

```text
origin/main
```

Tracked file count:

```text
9166
```

Forbidden high-risk tracked paths were not present:

- `application/config/database.php`: absent
- `application/config/youngo_paymob.local.php`: absent
- `application/config/youngo_security.local.php`: absent
- `.env`: absent
- `backups/`: absent
- `release_packages/`: absent
- `test-results/`: absent
- `docs/qa/screenshots/`: absent
- database dump `.sql` files outside allowed `database/` and `scripts/`: absent
- `.zip`, `.tar`, `.gz`, `.log`: absent
- private key files: no private key material found by path audit

Allowed or reviewed tracked path notes:

- SQL files are tracked only under allowed `database/` and `scripts/` paths.
- `application/cache/index.html` and `application/logs/index.html` are tracked guard files only; no runtime cache/log files were found.
- Generic payment gateway image assets exist under `assets/payment/`; these are UI/vendor assets, not payment evidence screenshots.
- Public CA certificate bundles exist in vendor libraries; these are not private keys.
- `application/config/youngo_paymob.php` is tracked, but sensitive values reviewed there are empty or placeholders, not live credentials.
- `application/config/youngo_paymob.local.example.php` and `application/config/youngo_security.local.example.php` are example files only.

## D. Development Tracked-File Audit

Audited ref:

```text
origin/development
```

Tracked file count:

```text
9167
```

Forbidden high-risk tracked paths were not present:

- `application/config/database.php`: absent
- `application/config/youngo_paymob.local.php`: absent
- `application/config/youngo_security.local.php`: absent
- `.env`: absent
- `backups/`: absent
- `release_packages/`: absent
- `test-results/`: absent
- `docs/qa/screenshots/`: absent
- database dump `.sql` files outside allowed `database/` and `scripts/`: absent
- `.zip`, `.tar`, `.gz`, `.log`: absent
- private key files: no private key material found by path audit

Allowed or reviewed tracked path notes:

- `origin/development` adds only `docs/qa/youngo_deploy_baseline_local_to_github_main_1_report.md` over `origin/main`.
- The added report was inspected by keyword category. It contains audit/report references and acceptable project path references only; no credential values were identified.
- SQL files are tracked only under allowed `database/` and `scripts/` paths.
- `application/cache/index.html` and `application/logs/index.html` are tracked guard files only; no runtime cache/log files were found.
- `application/config/youngo_paymob.php` is tracked, but sensitive values reviewed there are empty or placeholders, not live credentials.

## E. Secret Keyword Audit Summary

Search scope:

- Tracked text files on `origin/main`
- Tracked text files on `origin/development`
- No file contents or secret values were printed in the audit output.

Keyword categories checked:

- password
- secret key
- HMAC secret
- public key
- API key
- encryption key
- database username/password
- Paymob keys
- Google client secret
- SMTP password
- token
- private key
- Root Admin credentials

Result:

- No tracked `application/config/database.php`, `.env`, or local credential config files were present.
- Targeted review of `application/config/youngo_paymob.php` found empty credential values and placeholder/example values only.
- Targeted review of `application/config/youngo_paymob.local.example.php` and `application/config/youngo_security.local.example.php` found placeholder/example values only.
- Broad keyword scanning produced expected matches in source identifiers, route names, framework/vendor code, translation phrases, docs, SQL schema names, and diagnostics.
- No committed live credential value was confirmed by the targeted config/path audit.

Limitations:

- A dedicated scanner such as `gitleaks`, `trufflehog`, or `detect-secrets` was not installed locally.
- This audit is a conservative static Git/path/content audit, not a cryptographic guarantee that every arbitrary token-like string is non-sensitive.

## F. .gitignore Effectiveness

Confirmed ignored with `git check-ignore -v --no-index`:

- `.env`
- `application/config/database.php`
- `application/config/config.php`
- `application/config/youngo_paymob.local.php`
- `application/config/youngo_security.local.php`
- `uploads/`
- `application/logs/`
- `application/cache/`
- `backups/`
- `release_packages/`
- `test-results/`
- `docs/qa/screenshots/`

Important note:

- Runtime uploads are ignored by `uploads/**`.
- Safe upload guard files are explicitly unignored.
- Cache/log runtime contents are ignored while `index.html` guard files are explicitly preserved.

## G. Uploads Tracking Audit

Tracked uploads on both `origin/main` and `origin/development`:

- `uploads/.htaccess`
- `uploads/index.html`
- nested `index.html` guard files
- nested `.htaccess` guard files
- nested `htaccess_domain_wise` guard files

Tracked upload count:

```text
27
```

Tracked real media/runtime upload count:

```text
0
```

No real media, user uploads, payment evidence screenshots, Instapay evidence images, course thumbnails, documents, audio, video, captions, or runtime upload artifacts were tracked under `uploads/`.

## H. Findings Classification

Classification:

```text
SAFE_TO_CONTINUE
```

Reason:

- No sensitive local config files were tracked on `main` or `development`.
- No `.env` files were tracked.
- No database dumps or backups were tracked outside allowed schema/script SQL paths.
- No runtime uploads were tracked beyond guard files.
- No logs, test results, QA screenshots, release archives, or package archives were tracked.
- No payment evidence files were tracked.
- No live credential values were confirmed in the pushed config files reviewed.

## I. Recommended Remediation If Needed

No history rewrite is recommended from this audit.

No index cleanup is required for `main` or `development`.

Recommended hardening before cPanel link:

- Keep `application/config/database.php`, `application/config/config.php`, `.env`, and `*.local.php` files untracked.
- Configure server credentials only on the server.
- Do not push deployment SQL exports, backups, cPanel archives, runtime logs, screenshots, or payment evidence.
- Run a dedicated secret scanner before production release if one is installed or approved later.

## J. Recommended Next Phase

Recommended next phase:

```text
DEPLOY.CPANEL.LINK.PREFLIGHT.1
```

Proceed only after the owner accepts this audit result.

Do not deploy real payments, Paymob production credentials, SMTP credentials, or production secrets as part of the cPanel link step.

## K. Final Git Status

Final local status after creating this report:

```text
?? docs/qa/youngo_deploy_baseline_post_push_security_audit_1_report.md
```

This report is intentionally uncommitted.
