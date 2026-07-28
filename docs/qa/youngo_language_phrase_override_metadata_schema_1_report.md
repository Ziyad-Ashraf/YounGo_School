# LANGUAGE.PHRASE.OVERRIDE.METADATA.SCHEMA.1 Report

## A. Current Branch/Status

- Branch: `analysis/cms-audit`
- Start status: clean
- Latest commit at start:
  - `76cd147 Plan safe Arabic language pack import`

No language files were imported, no phrase values were edited, no frontend behavior was changed, no payment/Paymob behavior was changed, no checkout CTAs were exposed, and Root Admin was not modified.

## B. Backup Created

Created before applying schema:

```text
D:\Work\YounGo\backups\youngo_school_before_language_phrase_override_metadata_schema_1_2026_07_26_024845.sql
```

- Size: `598432` bytes
- SHA256: `20CC14DD498915B044E893EA017296F357125932E55C270040DFEF2085FA6BEB`

## C. Files Inspected

- `docs/qa/youngo_language_arabic_pack_import_plan_1_report.md`
- `docs/qa/youngo_language_arabic_pack_import_override_pagination_audit_1_report.md`
- `application/controllers/Admin.php`
- `application/models/Language_model.php` - not present
- `application/models/Crud_model.php`
- `application/views/backend/admin/manage_language.php`
- `application/config/routes.php`
- `scripts/phase_2/`

## D. Files Changed

- `application/models/Youngo_language_phrase_model.php`
- `scripts/phase_2/language_phrase_override_metadata_schema_1_up.sql`
- `scripts/phase_2/language_phrase_override_metadata_schema_1_down.sql`
- `scripts/phase_2/youngo_language_phrase_override_metadata_schema_1_diagnostic.php`
- `docs/qa/youngo_language_phrase_override_metadata_schema_1_report.md`

## E. Schema/Table Summary

Created additive metadata tables:

1. `youngo_language_import_batches`
   - Tracks future import batch status and counts.
   - Stores target language, source filename, source hash, mode, status, count summaries, actor, and timestamps.
   - Does not store raw phrase values.

2. `youngo_language_phrase_meta`
   - Tracks phrase/language metadata for import and manual override safety.
   - Stores phrase identity, language code, source state, import batch link, hashes, manual override actor/timestamp, and timestamps.
   - Includes `UNIQUE KEY uniq_ylpm_phrase_language (phrase_key, language_code)`.
   - Does not store raw translation text.

Current post-diagnostic table counts:

```text
youngo_language_import_batches = 0
youngo_language_phrase_meta = 0
arabic_translated metadata rows = 0
```

The legacy `language` table was not renamed, removed, or widened in this phase.

## F. Model Foundation Summary

Added `Youngo_language_phrase_model` as a dedicated YounGo metadata model instead of creating a legacy `Language_model.php`.

Added methods:

- `metadata_tables_exist()`
- `normalize_ui_language_code()`
- `is_supported_ui_language()`
- `get_phrase_meta_status($phrase_key, $language_code)`
- `mark_phrase_manual_override($phrase_key, $language_code, $actor_id = null)`
- `build_import_meta_preview_stub($language_code, $source_file, $import_mode)`

Model behavior:

- Supports canonical UI languages `english` and `arabic`.
- Treats `arabic_translated` as unsupported for UI metadata.
- Returns status/hash presence only, not raw phrase values.
- `mark_phrase_manual_override()` updates only metadata and is not wired into Edit Phrase yet.

## G. Diagnostic Result

Created and ran:

```text
php scripts/phase_2/youngo_language_phrase_override_metadata_schema_1_diagnostic.php
```

Result:

- PASS

Verified:

- Metadata tables exist.
- Expected columns exist.
- Raw translation value columns do not exist.
- Unique phrase key/language code constraint exists.
- No `arabic_translated` metadata rows exist.
- Model `metadata_tables_exist()` reports ready.
- Model preview stub allows `arabic` and blocks `arabic_translated`.
- Diagnostic metadata row was inserted and cleaned up.
- Legacy `language` table checksum remained unchanged.
- No Paymob/payment/checkout behavior was changed.

## H. DB Impact/Cleanup

DB impact:

- Added two empty metadata tables.
- No import batch rows remain.
- No phrase metadata rows remain.
- No phrase values changed in `language`.
- No `arabic_translated` metadata rows were created.

Diagnostic cleanup:

- Temporary metadata-only diagnostic row `youngo_language_meta_schema_diag` was inserted into `youngo_language_phrase_meta`.
- The row was deleted before diagnostic exit.
- Post-check confirmed zero matching diagnostic rows.

## I. What Was Not Changed

- No Arabic pack import was run.
- No values in the legacy `language` table were edited.
- Current Manage Language import behavior was not changed.
- Current Edit Phrase save behavior was not changed.
- No frontend phrase wiring changed.
- No routes changed.
- No payment, Paymob, checkout, enrolment, grant, or order behavior changed.
- Root Admin was not modified.
- `arabic_translated` was not used as a UI language code.

## J. Remaining Risks/Blockers

- Metadata is present but not yet wired to import or Edit Phrase saves.
- Current `Admin::language_import()` can still overwrite values until replaced or guarded in the safe import phase.
- Current `Admin::update_phrase_with_ajax()` does not yet mark metadata as manual override.
- Current language list can still discover `arabic_translated.json`; UI blocking/filtering remains a future phase.
- Existing `get_phrase()` and `site_phrase()` can still create/update phrases as runtime side effects.
- `application/language/english.json` remains missing, so safe import validation should use the DB phrase registry.

## K. Recommended Next Phase

Recommended next phase:

```text
LANGUAGE.ARABIC.PACK.IMPORT.SAFE.MODEL.1
```

That phase should add safe Arabic import preview/apply model logic using the new metadata tables, while still avoiding broad UI changes until pagination/UI phases.

## L. Git Status

Expected final status:

```text
?? application/models/Youngo_language_phrase_model.php
?? docs/qa/youngo_language_phrase_override_metadata_schema_1_report.md
?? scripts/phase_2/language_phrase_override_metadata_schema_1_down.sql
?? scripts/phase_2/language_phrase_override_metadata_schema_1_up.sql
?? scripts/phase_2/youngo_language_phrase_override_metadata_schema_1_diagnostic.php
```
