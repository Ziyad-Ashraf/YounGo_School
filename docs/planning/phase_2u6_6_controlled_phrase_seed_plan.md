# Phase 2U.6.6.2 Controlled Phrase Seed Plan

Status: planning/report only.

This plan prepares the missing YounGo frontend phrase keys that should be seeded before later source conversion. No application source code, route configuration, language JSON file, or database row was changed in this planning phase.

## Summary

Phase 2U.6.6.1 identified a small set of missing frontend UI phrase keys. Phase 2U.6.6.2 approves four low-risk shell/listing keys for a future controlled seed phase and defers three copy-sensitive keys for design/copy review.

Approved for controlled seed:

- `primary_navigation`
- `language_switcher`
- `footer_navigation`
- `showing_results`

Deferred for copy/design review:

- `youngo_theme_skeleton`
- `youngo_theme_skeleton_not_implemented`
- `allowed_file_types`

## Source Inventory Reference

Source inventory:

- `docs/planning/phase_2u6_6_frontend_phrase_inventory.md`

The inventory reported:

- 24 hardcoded visible frontend strings/fragments.
- 15 existing-key safe conversions.
- 4 missing-key seed-needed conversions.
- 3 copy/design decision candidates.
- 2 compact language-switcher labels that are not phrase candidates.

## Read-Only Phrase Table Verification

Verification method:

- direct read-only `SELECT` checks against the `language` table;
- no calls to `get_phrase()` or `site_phrase()`;
- no inserts, updates, JSON writes, or settings/session/cookie writes.

Current state:

| Phrase key | Exists in `language` | English value | Arabic value | Decision |
| --- | --- | --- | --- | --- |
| `primary_navigation` | no | missing | missing | approve seed |
| `language_switcher` | no | missing | missing | approve seed |
| `footer_navigation` | no | missing | missing | approve seed |
| `showing_results` | no | missing | missing | approve seed |
| `youngo_theme_skeleton` | no | missing | missing | defer |
| `youngo_theme_skeleton_not_implemented` | no | missing | missing | defer |
| `allowed_file_types` | no | missing | missing | defer |

## Approved Seed Candidates

These four keys are safe to seed in the next controlled phrase seed implementation because they are generic frontend UI labels, do not affect routes or data identity, and are needed before converting hardcoded shell/listing strings.

| Phrase key | English | Arabic | Future usage location | Safe for low-risk conversion |
| --- | --- | --- | --- | --- |
| `primary_navigation` | Primary navigation | التنقل الرئيسي | `application/views/frontend/youngo/header.php` nav `aria-label` | yes |
| `language_switcher` | Language switcher | مبدّل اللغة | `application/views/frontend/youngo/header.php` language switcher `aria-label` | yes |
| `footer_navigation` | Footer navigation | روابط التذييل | `application/views/frontend/youngo/footer.php` footer nav `aria-label` | yes |
| `showing_results` | Showing results | عرض النتائج | `application/views/frontend/youngo/course_listing/sorting_bar.php` results-count label | yes, with count formatting handled outside the phrase |

## Deferred Copy/Design Candidates

These are not approved for the next seed phase. Keep them deferred until the owner confirms final public copy.

| Phrase key | Draft English only | Draft Arabic only | Current usage | Reason deferred |
| --- | --- | --- | --- | --- |
| `youngo_theme_skeleton` | YounGo theme skeleton | هيكل واجهة YounGo | `application/views/frontend/youngo/index.php` fallback skeleton | placeholder implementation copy, not final user-facing copy |
| `youngo_theme_skeleton_not_implemented` | This section is not implemented yet. | هذا الجزء لم يتم تنفيذه بعد. | `application/views/frontend/youngo/index.php` fallback skeleton paragraph | should be replaced or reviewed before public use |
| `allowed_file_types` | Allowed file types | أنواع الملفات المسموح بها | `application/views/frontend/youngo/sign_up.php` instructor document hint | technical upload hint needs copy and file-extension wording review |

## Conversion Dependency Map

Phase 2U.6.6.3 controlled phrase seed implementation:

- Seed only `primary_navigation`, `language_switcher`, `footer_navigation`, and `showing_results`.
- Use a read/write seed script with explicit owner-approved keys and values.
- Do not seed the deferred copy/design keys unless a later approval explicitly moves them into the approved set.

Phase 2U.6.6.4 low-risk frontend phrase conversion:

- Convert Category A existing-key strings from the inventory in:
  - `application/views/frontend/youngo/courses_page.php`
  - `application/views/frontend/youngo/course_listing/filter_panel.php`
  - `application/views/frontend/youngo/course_listing/sorting_bar.php`
- Convert the four newly seeded Category B keys after the seed diagnostic confirms they exist.
- Keep `EN` and the Arabic switcher label raw.

Phase 2U.6.6.5 conversion QA/docs, if needed:

- Validate English pages still render existing English copy.
- Validate Arabic `/ar/...` pages render Arabic phrase values for converted strings.
- Confirm no `/en` links or routes are generated.
- Confirm route, RTL shell, language switcher, content shaping, checkout/payment/cart/coupon, and admin boundaries remain unchanged.

## Actual Seed Phase Checklist

Before seed:

- Verify branch `analysis/cms-audit`.
- Verify clean working tree or only expected seed-phase files.
- Create a full DB backup and record the exact path.
- Record `language` row count.
- Record existence/value state for approved keys.
- Run current Phase 2U.6 diagnostics.

During seed:

- Insert or update only the four approved phrase keys.
- Use canonical UI language columns `english` and `arabic`.
- Do not use `arabic_translated`.
- Do not modify `application/language/*.json` unless a later phase explicitly includes JSON export/import.
- Do not call `get_phrase()` or `site_phrase()` for missing keys.

After seed:

- Verify the four keys exist.
- Verify English and Arabic values match this plan.
- Verify no deferred keys were seeded.
- Verify no unrelated phrase rows changed.
- Run phrase seed diagnostic and existing Phase 2U.6 diagnostics.

## Rollback / Restore Expectations

The actual seed implementation phase must be protected by a DB backup because it will write phrase rows.

Rollback options for the future seed phase:

- preferred: restore the full DB backup created immediately before seed;
- acceptable for focused local rollback, if explicitly approved: remove only the four seeded phrase rows when they did not exist before the seed.

After rollback or restore, verify:

- `language` row count matches the pre-seed count;
- the four approved keys return to their pre-seed state;
- deferred keys remain absent;
- route/content/RTL diagnostics still pass;
- protected entitlement/payment/checkout/coupon/access rows remain unchanged.

## Safety Rules

- Do not call `get_phrase()` or `site_phrase()` with missing keys.
- Do not rely on accidental phrase-helper writes for seeding.
- Do not add `/en` routes, links, or canonical URLs.
- English frontend URLs remain unprefixed.
- Arabic frontend URLs remain `/ar/...`.
- Do not use `arabic_translated` as UI language, route language, or translation table language.
- Translation table language codes remain `english` and `arabic`.
- Course `language_made_in` remains separate course-content metadata.
- Do not modify checkout/payment/cart/coupon write endpoints.
- Do not modify admin/backend phrase behavior in this seed plan.

## Diagnostics Required For Seed Implementation

Future Phase 2U.6.6.3 should include a read-only diagnostic after seeding that checks:

- approved keys exist in `language`;
- English and Arabic values match the approved plan;
- deferred keys remain absent unless separately approved;
- no `/en` routes or links were added;
- no `arabic_translated` UI/table language usage exists;
- no source conversion calls missing phrase keys;
- Phase 2U.6.6.1 inventory diagnostic still passes or is intentionally updated for committed seed-plan files;
- Phase 2U.6 route, language context, content translation, RTL switcher, section/lesson, role, course/category, translation model, schema, and access diagnostics remain compatible.

## Proposed Next Phases

1. Phase 2U.6.6.3 - Controlled phrase seed implementation for the four approved keys.
2. Phase 2U.6.6.4 - Low-risk frontend phrase conversion using existing and newly seeded keys.
3. Phase 2U.6.6.5 - Conversion QA/docs if needed.
4. Phase 2U.6.7 - Controlled frontend localization QA and diagnostics.
5. Phase 2U.7 - broader bilingual QA and phrase polish QA.
