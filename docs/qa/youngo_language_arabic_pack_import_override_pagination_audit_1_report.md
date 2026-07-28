# LANGUAGE.ARABIC.PACK.IMPORT.OVERRIDE.PAGINATION.AUDIT.1 Report

## A. Current Branch/Status

- Branch: `analysis/cms-audit`
- Start status:
  - `?? docs/qa/youngo_subscriptions_page_dynamic_qa_1_report.md`
- Latest commits inspected:
  - `a4ac829 Add dynamic public subscriptions page`
  - `4def036 Add public subscription plan model support`
  - `73a2af2 Preserve Arabic default auth redirect language`
  - `78a4092 Plan Arabic default auth redirect preservation`
  - `b73d1e2 QA Arabic default authenticated public routes`
  - `dbe04f4 QA Arabic default public links`

The worktree was not fully clean at phase start because the previous subscriptions QA report was already untracked. This audit leaves that file untouched.

## B. Files Inspected

- `YOUNGO_PROJECT_CONTEXT.md`
- `docs/design/youngo_style_direction.md`
- `docs/planning/README.md`
- `docs/planning/youngo_master_plan_v2.md`
- `docs/agents/implementation_rules.md`
- `docs/reference/README.md`
- `application/controllers/Admin.php`
- `application/controllers/Data_center.php`
- `application/controllers/Language.php` - not present
- `application/models/Language_model.php` - not present
- `application/models/Crud_model.php`
- `application/views/backend/admin/manage_language.php`
- `application/views/backend/admin/edit_phrase.php` - not present
- `application/views/backend/admin/language.php` - not present
- `application/views/backend/admin/navigation.php`
- `application/helpers/common_helper.php`
- `application/helpers/multi_language_helper.php`
- `application/helpers/youngo_frontend_language_helper.php`
- `application/helpers/youngo_frontend_content_helper.php`
- `application/config/routes.php`
- `application/language/`
- `uploads/`
- `assets/backend/`
- `scripts/phase_2/`

## C. Manage Language/Import Current Behavior

The Manage Language page is implemented through `Admin::manage_language($param1, $param2, $param3)` and rendered by `application/views/backend/admin/manage_language.php`.

Current tabs:

- Language list
- Add language
- Import language
- Edit Phrase, shown when visiting `admin/manage_language/edit_phrase/{language}`

Current access:

- `manage_language()` checks `admin_login` and `check_permission('settings')`.
- The Settings navigation exposes Language Settings under the existing settings permission.
- `Admin::language_import()`, `Admin::update_phrase_with_ajax()`, and `Admin::export_language()` are separate methods and do not contain their own explicit `check_permission('settings')` checks.
- `Data_center::language_import()` duplicates the import behavior under the Data Center controller, protected by that controller constructor's admin session guard.

Current import behavior:

- Upload form posts to `admin/language_import`.
- Upload input is `language_files[]`, accepts `.json`, and allows multiple files.
- The uploaded filename determines the language code. For example, `arabic.json` becomes `arabic`.
- If a matching column does not exist on the `language` table, import adds a new DB column using `dbforge->add_column()`.
- JSON is validated only by `json_decode(..., true)` returning an array.
- On valid JSON, the uploaded file is moved into `application/language/{language}.json`.
- Each JSON key is normalized by lowercasing and replacing whitespace with underscores.
- Existing phrase rows are updated; missing phrase rows are inserted.
- There is no preview, dry-run, insert-missing-only mode, preserve-manual-overrides mode, or explicit force-overwrite choice.

## D. JSON Language Pack Format Finding

The compatible import format is a flat JSON object:

```json
{
  "phrase_key": "Translated value",
  "another phrase key": "Another translated value"
}
```

Observed examples:

- `application/language/arabic.json` exists, is valid JSON, and contains `1246` keys.
- `application/language/arabic_translated.json` exists, is valid JSON, and contains `995` keys, but it is deprecated and must not be used as the active UI language code.

Recommended future Arabic pack rules:

- File name should be `arabic.json`.
- Canonical UI language code must be `arabic`.
- Keys should be validated against existing/base phrase keys before import.
- Values should be strings or safely cast to strings; nested objects/arrays should be rejected.
- Import should report missing English/base keys, extra pack keys, blank values, and potentially corrupted text before writing.

## E. Phrase/Language Storage Finding

Current phrase storage is the `language` DB table.

Observed schema:

- `phrase_id`
- `phrase`
- `english`
- `arabic`
- No `arabic_translated` column

Observed counts from read-only diagnostics:

- Total phrase rows: `1553`
- English non-empty rows: `1552`
- Arabic non-empty rows: `1404`
- English rows still missing Arabic value: `148`

Language files are also present in `application/language/`, and `Crud_model::get_all_languages()` discovers languages from JSON files. That means `arabic_translated.json` can appear in the language list even though there is no DB column for `arabic_translated`. This is a current admin UX/data-safety risk if an admin tries to edit it as a UI language.

## F. Edit Phrase Current Behavior

Edit Phrase is not a separate controller/view. It is a mode inside:

- Controller: `Admin::manage_language('edit_phrase', $language)`
- View: `application/views/backend/admin/manage_language.php`

Phrase loading:

- The view calls `openJSONFile($edit_profile)`.
- Despite the name, `openJSONFile()` reads all rows from the `language` DB table.
- For each row, it returns the selected language column value or a fallback generated from the phrase key.

Phrase update:

- The view posts AJAX to `admin/update_phrase_with_ajax`.
- `Admin::update_phrase_with_ajax()` passes the selected language, phrase key, and value to `saveJSONFile()`.
- Despite the name, `saveJSONFile()` updates the `language` DB table only.

Performance and UX:

- Edit Phrase renders all phrases at once as cards.
- Current local count is `1553` phrases.
- No search input exists.
- No server-side pagination exists.
- No DataTables/server-side mode exists.

## G. Import-To-Edit-Phrase Relationship

Import Language and Edit Phrase already target the same effective data source: the `language` DB table.

Current relationship:

- Import writes imported values into `language.{language_code}`.
- Edit Phrase updates `language.{language_code}`.
- The most recent write wins.

Current limitation:

- The system cannot distinguish imported values from manual Edit Phrase overrides.
- A future re-import of `arabic.json` would overwrite manual Arabic edits unless import behavior changes first.

## H. Arabic Language Code Decision

Canonical UI language code: `arabic`.

Do not use `arabic_translated` as:

- UI language code
- Route language code
- `language` table column
- Frontend phrase language
- YounGo translation-table language code

`arabic_translated` remains valid only as legacy/deprecated context and as a course-content marker where already documented. It must not become the Arabic UI phrase source.

## I. Override Precedence Recommendation

Recommended effective behavior:

1. English/base phrase key exists in `language.phrase`.
2. Imported Arabic pack fills `language.arabic`.
3. Edit Phrase manual changes update the effective Arabic value.
4. Future re-import preserves manual overrides by default.
5. Force overwrite is available only as an explicit admin choice with preview counts.

Current schema cannot enforce this because it has no source metadata.

Recommended additive metadata:

- Add a small phrase metadata table rather than widening the legacy `language` table further, for example:
  - `phrase`
  - `language_code`
  - `source`
  - `import_batch_id`
  - `imported_hash`
  - `imported_at`
  - `imported_by_user_id`
  - `manually_overridden_at`
  - `manually_overridden_by_user_id`

Recommended import default:

- Update missing/blank values and values still marked imported.
- Do not overwrite values marked manually overridden.
- Provide explicit force overwrite only after a preview.

## J. Search/Pagination Recommendation

Implement server-side Edit Phrase browsing.

Recommended behavior:

- Route stays under the admin language settings area.
- Language filter is required.
- Search by phrase key and selected translated value.
- Page size options: `25`, `50`, `100`.
- Default page size: `50`.
- Results sorted by phrase key.
- Inline updates should update one phrase at a time.
- Empty search state should be explicit.
- Pagination/search should query the DB directly and avoid rendering all phrases.

Recommended safety:

- Add explicit admin/session/settings-permission checks to import, export, update, and future paginated fetch/update endpoints.
- Validate language code against existing safe language columns and reject `arabic_translated`.
- Keep CSRF behavior aligned with the existing admin setup.

## K. Required Schema/Model/UI Changes

Model/controller needs:

- Add a dedicated language phrase service/model or controller-private query methods.
- Add a paginated phrase read method:
  - language code
  - search term
  - limit
  - offset
  - total count
  - filtered count
- Add a safe single-phrase update method.
- Add import preview and import apply paths.
- Add source/override metadata if manual overrides must survive future imports.

UI needs:

- Keep the existing admin style.
- Replace full-card all-phrase rendering with a searchable, paginated table/list.
- Add visible language context: editing `arabic`, not `arabic_translated`.
- Add import mode choice:
  - Insert missing/update blanks
  - Update non-manual imported values
  - Force overwrite, explicit only
- Add import summary after apply.

Data safety needs:

- Preserve `english` as base phrase source.
- Preserve `arabic` as canonical Arabic UI language.
- Prevent `arabic_translated` from being edited/imported as UI language.
- Do not remove existing language files in the implementation phase.

## L. Risks/Blockers

- `arabic_translated.json` is discoverable by the current language-list code even though it is not a DB UI language column.
- Current import overwrites phrase values and cannot preserve manual Edit Phrase overrides.
- Current import can create arbitrary DB columns from upload filenames unless stricter language-code validation is added.
- Current Edit Phrase renders all phrases at once, which will degrade as phrase count grows.
- Current AJAX update method lacks an explicit method-local settings permission check.
- Current import method lacks an explicit method-local settings permission check.
- Existing `get_phrase()` and `site_phrase()` can create/update DB phrases as side effects when a phrase is missing.
- Many frontend views still use mixed `get_phrase()`, `site_phrase()`, local YounGo maps, and `youngo_frontend_phrase()` patterns, so frontend phrase wiring should be phased.

## M. Recommended Implementation Order

1. `LANGUAGE.ARABIC.PACK.IMPORT.PLAN.1`
   - Define exact import modes, validation, metadata, and override behavior.
2. `LANGUAGE.EDIT.PHRASE.PAGINATION.SCHEMA_OR_MODEL.1`
   - Add read/query model support and source metadata if approved.
3. `LANGUAGE.EDIT.PHRASE.PAGINATION.UI.1`
   - Replace all-phrase rendering with searchable paginated admin UI.
4. `LANGUAGE.ARABIC.PACK.IMPORT.UI.QA.1`
   - Import Arabic JSON through approved modes and verify Edit Phrase values.
5. `LANGUAGE.FRONTEND.PHRASE.WIRE.1`
   - Move remaining public labels to `youngo_frontend_phrase()` where safe.
6. `SUBSCRIPTIONS.PAGE.AR_COPY.PHRASE.1`
   - Fix subscription page/navbar labels through phrase keys after the phrase system is reliable.

## N. Diagnostic Result

Created and ran:

```text
scripts/phase_2/youngo_language_arabic_pack_import_override_pagination_audit_1_diagnostic.php
```

Result:

- PASS

Key diagnostic findings:

- Import controller method exists.
- Edit Phrase controller path exists.
- Phrase storage target exists.
- `language.arabic` exists.
- `language.arabic_translated` does not exist.
- `arabic.json` exists and is valid JSON.
- `arabic_translated.json` exists and is valid JSON but is non-canonical/deprecated.
- No search/pagination support exists for Edit Phrase.
- Edit Phrase loads all phrases.
- No Paymob/payment/checkout behavior was changed.
- Diagnostic performed read-only checks only.

## O. Git Status

Final expected status after this audit:

```text
?? docs/qa/youngo_language_arabic_pack_import_override_pagination_audit_1_report.md
?? docs/qa/youngo_subscriptions_page_dynamic_qa_1_report.md
?? scripts/phase_2/youngo_language_arabic_pack_import_override_pagination_audit_1_diagnostic.php
```

The subscriptions QA report was already untracked before this phase and was not modified by this audit.
