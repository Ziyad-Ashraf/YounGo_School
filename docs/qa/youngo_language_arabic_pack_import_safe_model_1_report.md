# LANGUAGE.ARABIC.PACK.IMPORT.SAFE.MODEL.1 Report

## A. Current Branch/Status

- Branch: `analysis/cms-audit`
- Start status: clean.
- Latest commit at start: `f4d5eed Add language phrase override metadata schema`
- Recent history also includes the Arabic import plan/audit, dynamic subscriptions page QA, and Arabic default routing/link/auth redirect work.

## B. Files Inspected

- `docs/qa/youngo_language_arabic_pack_import_plan_1_report.md`
- `docs/qa/youngo_language_phrase_override_metadata_schema_1_report.md`
- `docs/qa/youngo_language_arabic_pack_import_override_pagination_audit_1_report.md`
- `application/controllers/Admin.php`
- `application/models/Youngo_language_phrase_model.php`
- `application/models/Crud_model.php`
- `application/views/backend/admin/manage_language.php`
- `application/language/arabic.json`
- `application/language/arabic_translated.json`
- `application/helpers/common_helper.php`
- `scripts/phase_2/`

## C. Files Changed

- `application/models/Youngo_language_phrase_model.php`
- `application/controllers/Admin.php`
- `scripts/phase_2/youngo_language_arabic_pack_import_safe_model_1_diagnostic.php`
- `docs/qa/youngo_language_arabic_pack_import_safe_model_1_report.md`

## D. Arabic JSON Validation Summary

- The safe import source is fixed to `application/language/arabic.json`.
- The canonical target UI language code is fixed to `arabic`.
- `application/language/arabic_translated.json` is rejected as a UI import source.
- Validation confirms `arabic.json` exists, is valid JSON, and is a flat object.
- Diagnostic counts:
  - `total_keys`: 1246
  - `valid_keys`: 1245
  - `invalid_keys`: 1
  - `invalid_values`: 0
  - `blank_values`: 0
  - `duplicate_normalized_keys`: 0
- The one invalid/unsafe key is skipped by preview logic. No phrase key names or phrase values are printed.

## E. Safe Import Model Summary

Added Arabic-specific safe import foundations to `Youngo_language_phrase_model`:

- `validate_arabic_pack_file($path)`
- `preview_arabic_pack_import($mode = 'missing_blank_only')`
- `apply_arabic_pack_import($mode, $actor_id, $dry_run = true)`

The model:

- accepts only `arabic.json`
- uses only `arabic` as the target UI language
- rejects `arabic_translated`
- validates a flat key/value JSON object
- normalizes phrase keys before matching
- converts scalar values to strings for preview logic
- stores and reports counts only
- keeps `full_apply_allowed` false
- blocks non-dry-run full apply until a later explicit phase

## F. Import Mode Preview Summary

Preview before temporary diagnostic fixtures:

- `missing_blank_only`
  - matching existing phrase keys: 1245
  - missing phrase keys: 0
  - blank Arabic values: 0
  - non-blank Arabic values: 1245
  - legacy existing preserved: 1245
  - would update phrase values: 0
  - would skip: 1245
- `update_imported_non_manual`
  - matching existing phrase keys: 1245
  - legacy existing preserved: 1245
  - would update phrase values: 0
  - would skip: 1245
- `force_overwrite`
  - matching existing phrase keys: 1245
  - would insert metadata: 1245
  - would update phrase values: 1245
  - full apply still blocked

All previews include `invalid_keys: 1` for the skipped unsafe key and `stores_raw_phrase_values: false`.

## G. Override/Metadata Behavior

The diagnostic created temporary metadata rows only and cleaned them up.

Verified:

- `manual_override` rows are preserved in default preview mode.
- `imported` non-manual rows are updatable in `update_imported_non_manual`.
- `force_overwrite` preview identifies manual rows that would be overwritten, while real apply remains blocked.
- Non-empty Arabic phrase values without metadata are treated as `legacy_existing` and preserved by default.
- Metadata uses hashes/status only and does not store raw translation values.
- No `arabic_translated` metadata rows exist.

## H. Controller/Admin Safety

Added `Admin::youngo_arabic_pack_import_preview()` as a preview-only endpoint.

Safety controls:

- GET-only.
- Requires admin login.
- Requires existing `settings` permission.
- Requires Root Admin helper check when available.
- Uses the fixed `arabic.json` source.
- Does not accept uploaded files.
- Does not accept arbitrary target languages.
- Does not run full import or phrase updates.

## I. Diagnostic Result

Commands run:

- `php -l application/models/Youngo_language_phrase_model.php`
- `php -l application/controllers/Admin.php`
- `php -l scripts/phase_2/youngo_language_arabic_pack_import_safe_model_1_diagnostic.php`
- `php scripts/phase_2/youngo_language_arabic_pack_import_safe_model_1_diagnostic.php`
- `php scripts/phase_2/youngo_language_phrase_override_metadata_schema_1_diagnostic.php`

Result:

- Safe Arabic pack import model diagnostic: PASS.
- Language phrase override metadata schema diagnostic: PASS.

## J. DB Impact/Cleanup

- No full Arabic import was executed.
- No real phrase values were changed.
- The diagnostic inserted temporary metadata fixture rows only and cleaned them up.
- Language table checksum remained unchanged.
- Metadata row count returned to its original value after diagnostic cleanup.
- No schema changes were made in this phase.

## K. What Was Not Changed

- No full `arabic.json` import.
- No phrase value edits.
- No frontend language behavior changes.
- No Edit Phrase save behavior changes.
- No Manage Language import UI changes.
- No payment, Paymob, checkout, order, enrolment, or access behavior changes.
- No Root Admin changes.
- No language files removed or renamed.
- The legacy global import path was not replaced for all languages.

## L. Remaining Risks/Blockers

- The real Arabic import remains intentionally blocked until an explicit apply/QA phase.
- Edit Phrase does not yet mark manual override metadata on save.
- The Manage Language UI does not yet expose the safe Arabic preview/import workflow.
- The legacy blind overwrite import path still needs a guard so Arabic uses only the safe path.
- `arabic.json` contains one invalid/unsafe key that preview skips; it should be cleaned or accepted as skipped in the import QA phase.
- Phrase pagination/search is still pending.

## M. Recommended Next Phase

Recommended next phase:

- `LANGUAGE.ARABIC.PACK.IMPORT.UI.PREVIEW.1` to add the Root Admin preview UI and explicitly block apply controls.

Then:

- `LANGUAGE.EDIT.PHRASE.PAGINATION.MODEL.1`
- `LANGUAGE.EDIT.PHRASE.PAGINATION.UI.1`
- `LANGUAGE.ARABIC.PACK.IMPORT.QA.1`

## N. Git Status

- `git diff --check`: passed; Git reported line-ending normalization warnings for existing PHP files only.
- `git status --short`:

```text
 M application/controllers/Admin.php
 M application/models/Youngo_language_phrase_model.php
?? docs/qa/youngo_language_arabic_pack_import_safe_model_1_report.md
?? scripts/phase_2/youngo_language_arabic_pack_import_safe_model_1_diagnostic.php
```
