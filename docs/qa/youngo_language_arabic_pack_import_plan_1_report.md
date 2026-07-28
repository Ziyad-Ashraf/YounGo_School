# LANGUAGE.ARABIC.PACK.IMPORT.PLAN.1 Report

## A. Current Branch/Status

- Branch: `analysis/cms-audit`
- Start status: clean
- Latest commit includes prior audit:
  - `cd9c2e7 Audit Arabic language pack import and phrase pagination`

No DB writes, language imports, phrase edits, frontend behavior changes, payment changes, or Root Admin changes were performed in this planning phase.

## B. Files Inspected

- `docs/qa/youngo_language_arabic_pack_import_override_pagination_audit_1_report.md`
- `application/controllers/Admin.php`
- `application/models/Language_model.php` - not present
- `application/models/Crud_model.php`
- `application/views/backend/admin/manage_language.php`
- `application/language/arabic.json`
- `application/language/arabic_translated.json`
- `application/language/english.json` - not present
- `application/language/english/`
- `application/helpers/common_helper.php`
- `application/helpers/multi_language_helper.php`

## C. Safe Arabic Import Behavior

Target behavior:

- The only Arabic UI language code is `arabic`.
- The source file for the approved Arabic UI pack is `arabic.json`.
- `arabic_translated.json` must be ignored for UI import and blocked from UI-language editing/import targets.
- Imported values are written to the existing effective value store: `language.arabic`.
- The existing `language` DB table remains the phrase value table for compatibility with `get_phrase()`, `site_phrase()`, `openJSONFile()`, and YounGo frontend phrase reads.

Validation rules:

- Language target must be selected from an allowlist: initially `arabic` only for this feature.
- Column name must match a strict pattern such as `^[a-z][a-z0-9_]{1,31}$`.
- Reject `arabic_translated`, `ar`, `ara`, path-like values, filenames with multiple language meanings, and arbitrary new columns.
- Uploaded/imported JSON must decode to a flat object.
- Keys must be scalar strings after normalization.
- Values must be scalar strings; reject arrays/objects.
- Normalize keys with the same legacy phrase behavior where needed: lowercase and whitespace to underscores.
- Reject or report duplicate normalized keys before writing.
- Compare keys against `language.phrase` as the base registry because `application/language/english.json` is currently missing.
- Produce a preview before apply:
  - total JSON keys
  - valid keys
  - duplicate normalized keys
  - keys matching existing DB phrases
  - keys missing from DB phrases
  - blank values
  - rows that would be inserted
  - rows that would update blank Arabic
  - rows that would update imported/non-manual Arabic
  - rows skipped because manually overridden
  - rows skipped because target is unsafe

Default write behavior must never blindly overwrite non-empty manual Arabic values.

## D. Override Metadata Plan

Add additive metadata instead of changing the shape of the legacy `language` table.

Recommended tables:

1. `youngo_language_import_batches`
   - `id`
   - `language_code`
   - `source_filename`
   - `source_sha256`
   - `mode`
   - `status`
   - `total_keys`
   - `matched_keys`
   - `inserted_count`
   - `blank_updated_count`
   - `imported_updated_count`
   - `manual_skipped_count`
   - `force_overwritten_count`
   - `invalid_count`
   - `created_by_user_id`
   - `created_at`
   - `applied_at`

2. `youngo_language_phrase_meta`
   - `id`
   - `phrase`
   - `language_code`
   - `source`
   - `current_value_hash`
   - `last_imported_value_hash`
   - `last_import_batch_id`
   - `imported_at`
   - `imported_by_user_id`
   - `manually_overridden_at`
   - `manually_overridden_by_user_id`
   - `force_overwritten_at`
   - `force_overwritten_by_user_id`
   - `created_at`
   - `updated_at`

Recommended constraints/indexes:

- Unique key on `(phrase, language_code)`.
- Index on `(language_code, source)`.
- Index on `last_import_batch_id`.
- Valid `language_code` values should be enforced in application logic as `english` or `arabic`, with this phase allowing import only to `arabic`.

Source states:

- `imported`: current effective DB value matches an imported value and has no later manual override.
- `manual_override`: Edit Phrase saved the current effective value.
- `legacy_existing`: value existed before metadata and should be preserved by default.
- `blank`: no effective value exists.
- `force_imported`: force overwrite deliberately replaced a manual or legacy value.

Important bootstrap rule:

- Existing non-empty `language.arabic` rows without metadata must be treated as `legacy_existing` and preserved by default.
- Existing blank/missing Arabic rows can be filled by missing/blank import mode.

## E. Import Modes Recommendation

Recommended modes:

1. `missing_blank_only`
   - Inserts missing phrase rows only when acceptable.
   - Updates `language.arabic` only when it is null or blank.
   - Preserves every non-empty Arabic value.
   - Safest default for the first Arabic pack apply.

2. `update_imported_non_manual`
   - Updates blank values.
   - Updates values whose metadata source is `imported` and not manually overridden.
   - Preserves `manual_override` and `legacy_existing` values.
   - Best default for future pack refreshes after metadata exists.

3. `force_overwrite`
   - Updates matching values even if manually overridden or legacy existing.
   - Must require explicit admin confirmation after preview.
   - Must record `force_overwritten_count`, actor, and timestamp.
   - Should not be the default mode.

Recommended initial default:

- First implementation should default to `missing_blank_only`.
- Once metadata is seeded and a successful import exists, future default can become `update_imported_non_manual`.

## F. Edit Phrase Override Behavior

Edit Phrase should remain the manual override surface.

Future behavior:

- Saving a phrase from Edit Phrase updates `language.{language_code}`.
- Saving a phrase also upserts `youngo_language_phrase_meta`.
- For `arabic`, the metadata row is marked:
  - `source = manual_override`
  - `manually_overridden_at = now`
  - `manually_overridden_by_user_id = current admin user id`
  - `current_value_hash = sha256(normalized saved value)`
- Import preview and import apply must treat that phrase/language as protected unless force overwrite is selected.

Language safety:

- Edit Phrase should allow `english` and `arabic` only unless a later phase explicitly supports more UI languages.
- Edit Phrase should reject `arabic_translated` even if `arabic_translated.json` exists in `application/language/`.
- If a user lands on `admin/manage_language/edit_phrase/arabic_translated`, the future safe behavior should show a blocked/non-canonical language message and link to `arabic`.

Legacy helper side effects:

- `get_phrase()` and `site_phrase()` can currently insert/update missing values.
- The import/override model should not call unknown phrase keys casually.
- Metadata should be updated only by approved admin import/edit paths, not by every runtime phrase fallback.

## G. Admin UI Recommendation

Manage Language import UI should be narrowed for this Arabic pack flow.

Recommended UI changes:

- Add a dedicated "Import Arabic pack" panel or mode.
- Show target language as `Arabic (arabic)`.
- Show source requirement: `arabic.json`.
- Show an explicit warning that `arabic_translated` is deprecated and ignored for UI import.
- Show import mode selector:
  - Missing/blank only, recommended
  - Update imported/non-manual
  - Force overwrite, dangerous
- Run preview before apply.
- Show preview counts before any write.
- Require explicit confirmation for force overwrite.
- Show post-import summary:
  - inserted
  - updated blanks
  - updated imported values
  - skipped manual overrides
  - skipped legacy existing values
  - invalid keys
  - duplicate normalized keys
- Hide or disable `arabic_translated` in the language list as an editable UI language.

Access/safety:

- Add explicit method-local admin login and `check_permission('settings')` checks to import preview/apply, update phrase, export language, and future pagination endpoints.
- Keep the existing Settings navigation permission model unless a later roles phase defines a stricter language-management capability.
- Do not modify Root Admin.

## H. Recommended Implementation Order

1. `LANGUAGE.PHRASE.OVERRIDE.METADATA.SCHEMA.1`
   - Create additive metadata/import-batch SQL artifacts only.
   - Include rollback SQL.
   - Do not import phrase values in this phase.

2. `LANGUAGE.ARABIC.PACK.IMPORT.SAFE.MODEL.1`
   - Add a YounGo-specific phrase import/model service, preferably `Youngo_language_phrase_model`.
   - Implement read-only preview first.
   - Implement apply paths with the three approved import modes.
   - Block `arabic_translated`.

3. `LANGUAGE.EDIT.PHRASE.PAGINATION.MODEL.1`
   - Add paginated phrase reads and safe single-phrase update methods.
   - Update metadata on manual phrase edits.

4. `LANGUAGE.EDIT.PHRASE.PAGINATION.UI.1`
   - Replace all-phrase rendering with server-side search and pagination.
   - Keep the existing admin visual style.

5. `LANGUAGE.ARABIC.PACK.IMPORT.QA.1`
   - Backup DB.
   - Run preview.
   - Apply a temporary or approved Arabic pack through the UI.
   - Verify metadata and Edit Phrase override preservation.
   - Restore if QA data was temporary.

6. `LANGUAGE.FRONTEND.PHRASE.WIRE.1`
   - Convert remaining public-facing labels to the safe frontend phrase helper.
   - Keep admin/backend/payment/action URLs unchanged.

7. `SUBSCRIPTIONS.PAGE.AR_COPY.PHRASE.1`
   - Move subscription page/navbar labels to approved phrase keys and Arabic copy.
   - Keep subscription checkout/payment CTAs disabled until payment phases approve them.

## I. Risks/Blockers

- `application/language/english.json` is missing, so import validation should use the `language` DB table as the base phrase registry.
- Existing `arabic.json` already exists; the implementation must decide whether it is the approved source file or just a current local artifact before applying writes.
- `arabic_translated.json` exists and is discoverable by `Crud_model::get_all_languages()`, so UI filtering/blocking is required.
- Existing import behavior can create arbitrary language columns from uploaded filenames; safe Arabic import must not reuse that blindly.
- Existing import overwrites values, which is unsafe until metadata and modes are implemented.
- Existing Edit Phrase updates cannot preserve manual overrides until metadata is added and save paths are changed.
- `get_phrase()` and `site_phrase()` have DB write side effects when phrases are missing or blank; this should be considered during frontend phrase wiring.
- Search/pagination and import safety should be implemented before broad Arabic phrase polish or subscription label cleanup.

## J. Git Status

Expected final status after this planning phase:

```text
?? docs/qa/youngo_language_arabic_pack_import_plan_1_report.md
```

## K. Commit Recommendation

Commit this report as:

```text
Plan safe Arabic language pack import
```
