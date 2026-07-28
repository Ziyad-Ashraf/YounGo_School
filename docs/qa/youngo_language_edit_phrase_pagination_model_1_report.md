# LANGUAGE.EDIT.PHRASE.PAGINATION.MODEL.1 Report

## A. Current Branch/Status

- Branch: `analysis/cms-audit`
- Start status: clean.
- Latest commit at start: `5dcedb8 Add Arabic language pack preview UI and import guard`
- No deployment, push, phrase value edit, Arabic import, frontend language behavior change, Paymob/payment change, checkout CTA change, or Root Admin change was performed.

## B. Files Inspected

- `docs/qa/youngo_language_arabic_pack_import_ui_preview_1_report.md`
- `docs/qa/youngo_language_arabic_pack_import_safe_model_1_report.md`
- `application/controllers/Admin.php`
- `application/config/routes.php`
- `application/models/Youngo_language_phrase_model.php`
- `application/views/backend/admin/manage_language.php`
- `application/helpers/multi_language_helper.php`
- `scripts/phase_2/`

## C. Files Changed

- `application/config/routes.php`
- `application/controllers/Admin.php`
- `application/models/Youngo_language_phrase_model.php`
- `scripts/phase_2/youngo_language_edit_phrase_pagination_model_1_diagnostic.php`
- `docs/qa/youngo_language_edit_phrase_pagination_model_1_report.md`

## D. Current Edit Phrase Flow

Current UI behavior remains unchanged in this phase:

- `Admin::manage_language('edit_phrase', $language)` sets `edit_profile`.
- `application/views/backend/admin/manage_language.php` still loops `openJSONFile($edit_profile)`.
- `openJSONFile()` still reads all phrase rows from the `language` table.
- `Admin::update_phrase_with_ajax()` still exists for the existing update button flow.

This phase adds a new server-side data layer for later UI wiring without replacing the current cards UI.

## E. Pagination/Search Model Summary

Added to `Youngo_language_phrase_model`:

- `get_edit_phrase_language_options()`
- `normalize_edit_phrase_language_filter($language_code)`
- `get_paginated_edit_phrases($language_code = 'english', $page = 1, $per_page = 25, $search = '')`

Behavior:

- Supports page, per-page, search, selected language, total rows, total pages, offset, and returned rows.
- Enforces page sizes of `25`, `50`, or `100`; invalid sizes default to `25`.
- Sorts by phrase key for stable paging.
- Uses validated language column names before embedding any column name in SQL.
- Uses bound query values and explicit LIKE escaping for search input.

## F. Controller/Route Summary

Added:

- `Admin::youngo_edit_phrase_paginated_data()`
- Route: `admin/youngo/language/edit-phrase-data`

Endpoint behavior:

- GET-only.
- Requires admin login.
- Requires existing `settings` permission.
- Calls the model pagination method.
- Does not update phrase values.
- Does not import language packs.

## G. Language Filter Behavior

Supported language filters are discovered from valid columns on the existing `language` table.

Verified locally:

- `english` is supported.
- `arabic` is supported.
- Alias `en` normalizes to `english`.
- Alias `ar` normalizes to `arabic`.
- Any valid existing UI language column can be listed unless explicitly blocked.

## H. arabic_translated Safety

- `arabic_translated` is excluded from Edit Phrase language options.
- `normalize_edit_phrase_language_filter('arabic_translated')` returns `null`.
- Paginated data requests for `arabic_translated` return invalid/empty data status.
- `arabic_translated` was not treated as a UI language code.

## I. Search Behavior

Search covers:

- phrase key
- selected language value
- English fallback value when selected language is not English

Diagnostic verified search by an existing phrase key against both English and Arabic contexts. The diagnostic prints only counts/status and does not print phrase keys or phrase values.

## J. Diagnostic Result

Passed:

- `php -l application/models/Youngo_language_phrase_model.php`
- `php -l application/controllers/Admin.php`
- `php -l application/config/routes.php`
- `php -l scripts/phase_2/youngo_language_edit_phrase_pagination_model_1_diagnostic.php`
- `php scripts/phase_2/youngo_language_edit_phrase_pagination_model_1_diagnostic.php`
- `php scripts/phase_2/youngo_language_arabic_pack_import_ui_preview_1_diagnostic.php`

Diagnostic highlights:

- English pagination valid: total rows `1553`, returned rows `25`.
- Arabic pagination valid: returned rows `50`.
- Invalid per-page value defaulted to `25`.
- Search returned matching rows in English and Arabic contexts.
- `arabic_translated` was hidden/rejected.
- Existing edit/update route markers remain present.
- Language table checksum remained unchanged.

## K. DB/Phrase Impact

- No phrase values were changed.
- No Arabic pack was imported.
- No metadata rows were inserted.
- No schema changes were made.
- No frontend language behavior changed.
- No payment, Paymob, checkout, order, enrolment, access, or CTA behavior changed.

## L. Remaining Risks/Blockers

- The current UI still uses the legacy all-row card rendering path until the next UI phase wires the paginated endpoint.
- `update_phrase_with_ajax()` is not yet wired to manual override metadata.
- Edit Phrase UI search controls and pagination controls are pending.
- Browser QA for the new endpoint is deferred to the UI phase or an authenticated session phase.

## M. Recommended Next Phase

Recommended next phase:

- `LANGUAGE.EDIT.PHRASE.PAGINATION.UI.1`

Then:

- `LANGUAGE.EDIT.PHRASE.MANUAL_OVERRIDE.WIRE.1`
- `LANGUAGE.ARABIC.PACK.IMPORT.QA.1`

## N. Git Status

- `git diff --check`: passed; Git reported line-ending normalization warnings for changed PHP/config files only.
- `git status --short`:

```text
 M application/config/routes.php
 M application/controllers/Admin.php
 M application/models/Youngo_language_phrase_model.php
?? docs/qa/youngo_language_edit_phrase_pagination_model_1_report.md
?? scripts/phase_2/youngo_language_edit_phrase_pagination_model_1_diagnostic.php
```
