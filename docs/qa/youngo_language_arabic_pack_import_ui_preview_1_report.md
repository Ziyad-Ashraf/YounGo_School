# LANGUAGE.ARABIC.PACK.IMPORT.UI.PREVIEW.1 Report

## A. Current Branch/Status

- Branch: `analysis/cms-audit`
- Start status: clean.
- Latest commit at start: `8e070aa Add safe Arabic language pack import preview foundation`
- No deployment, push, full Arabic import, phrase-value edit, Paymob/payment change, checkout CTA change, or Root Admin change was performed.

## B. Files Inspected

- `YOUNGO_PROJECT_CONTEXT.md`
- `docs/design/youngo_style_direction.md`
- `docs/planning/youngo_master_plan_v2.md`
- `docs/planning/youngo_phase_2_hybrid_access_subscriptions_roles_architecture.md`
- `docs/agents/implementation_rules.md`
- `docs/reference/README.md`
- `docs/qa/youngo_language_arabic_pack_import_safe_model_1_report.md`
- `application/controllers/Admin.php`
- `application/views/backend/admin/manage_language.php`
- `application/models/Youngo_language_phrase_model.php`
- `application/config/routes.php`
- `application/language/arabic.json`
- `application/language/arabic_translated.json`
- `scripts/phase_2/`

## C. Files Changed

- `application/config/routes.php`
- `application/controllers/Admin.php`
- `application/views/backend/admin/manage_language.php`
- `scripts/phase_2/youngo_language_arabic_pack_import_ui_preview_1_diagnostic.php`
- `docs/qa/youngo_language_arabic_pack_import_ui_preview_1_report.md`

## D. UI Preview Summary

Added a safe Arabic import preview panel to the Manage Language import tab.

The panel shows:

- Target language: `Arabic (arabic)`
- Source file: `arabic.json`
- Import mode selector:
  - `missing_blank_only`
  - `update_imported_non_manual`
  - `force_overwrite preview only / dangerous`
- Preview button only.
- Preview result table with count/status fields only.

The UI does not expose phrase values, source hashes, full apply controls, import submit controls for Arabic, or decrypted/sensitive values.

## E. Legacy Import Guard Summary

`Admin::language_import()` now:

- requires admin login
- keeps the existing `settings` permission check
- preflights uploaded filenames before `dbforge` or phrase writes
- blocks legacy blind import when a target resolves to `arabic`
- blocks legacy blind import when a target resolves to `arabic_translated`
- tells the admin to use the safe Arabic import preview workflow

This prevents the old upload path from blindly overwriting Arabic UI phrases.

## F. Arabic/ar_translated Safety

- Canonical UI language remains `arabic`.
- `arabic.json` remains the approved source for safe preview.
- `arabic_translated` is blocked as a UI language target in the Add Language path.
- `arabic_translated.json` remains present but is not accepted by the safe Arabic import model and is blocked from the legacy UI import target path.

## G. Browser/Local HTTP QA Summary

Authenticated browser QA was not completed in this phase.

Reason:

- The phase explicitly forbids SQL writes except harmless read-only diagnostics.
- A fresh Root Admin login can write normal session/device-tracking rows.
- Credentials were therefore not used or printed.

Limited local HTTP checks:

- `http://localhost/school/admin/manage_language` returned HTTP 200 but was an unauthenticated/login-context response, so the authenticated panel could not be verified there without login.
- The Apache-served route did not reliably reflect the current workspace route state during unauthenticated checks.
- A temporary PHP built-in server attempt timed out and left no running PHP server process.

UI behavior was therefore verified by source inspection and the read-only diagnostic in this phase.

## H. Diagnostic Result

Passed:

- `php -l application/controllers/Admin.php`
- `php -l application/views/backend/admin/manage_language.php`
- `php -l application/config/routes.php`
- `php -l scripts/phase_2/youngo_language_arabic_pack_import_ui_preview_1_diagnostic.php`
- `php scripts/phase_2/youngo_language_arabic_pack_import_ui_preview_1_diagnostic.php`
- `php scripts/phase_2/youngo_language_arabic_pack_import_safe_model_1_diagnostic.php`

Diagnostic highlights:

- Manage Language preview UI markers exist.
- Preview endpoint exists and remains GET-only.
- Preview endpoint has admin/session, `settings`, and Root Admin guard markers.
- Explicit admin preview route exists.
- Preview returns count/status fields only.
- Full apply remains disabled.
- Legacy Arabic blind import is blocked before `dbforge` or phrase writes.
- `arabic_translated` is blocked for UI import.
- Language table checksum remained unchanged.

## I. DB/Phrase Impact

- No full Arabic import was run.
- No phrase values were changed.
- No metadata rows were inserted by the UI diagnostic.
- No schema changes were made.
- No payment, Paymob, checkout, order, enrolment, access, or CTA behavior was changed.

## J. Remaining Risks/Blockers

- Authenticated browser QA still needs to be run in a phase that permits normal session writes or with an existing authenticated browser session.
- Full Arabic apply/import remains intentionally disabled.
- Edit Phrase still does not mark manual override metadata on save.
- Edit Phrase search/pagination remains pending.
- `arabic.json` still contains one invalid/unsafe key skipped by preview logic.
- The legacy import path remains available for non-Arabic language files.

## K. Recommended Next Phase

Recommended next phase:

- `LANGUAGE.EDIT.PHRASE.PAGINATION.MODEL.1`

Then:

- `LANGUAGE.EDIT.PHRASE.PAGINATION.UI.1`
- `LANGUAGE.ARABIC.PACK.IMPORT.QA.1`

## L. Git Status

- `git diff --check`: passed; Git reported line-ending normalization warnings for changed PHP/config/view files only.
- `git status --short`:

```text
 M application/config/routes.php
 M application/controllers/Admin.php
 M application/views/backend/admin/manage_language.php
?? docs/qa/youngo_language_arabic_pack_import_ui_preview_1_report.md
?? scripts/phase_2/youngo_language_arabic_pack_import_ui_preview_1_diagnostic.php
```
