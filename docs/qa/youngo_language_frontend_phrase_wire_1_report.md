# LANGUAGE.FRONTEND.PHRASE.WIRE.1 Report

## A. Current Branch/Status

- Branch: `analysis/cms-audit`
- Starting worktree: clean
- Latest commit at start: `dde3df7 Run safe Arabic language pack import QA`
- Final worktree: changed files listed in section N

## B. Files Inspected

- `docs/qa/youngo_language_arabic_pack_import_qa_1_report.md`
- `docs/qa/youngo_language_edit_phrase_pagination_auth_ui_qa_1_report.md`
- `docs/qa/youngo_localization_ar_default_links_1_report.md`
- `docs/qa/youngo_subscriptions_page_dynamic_ui_1_report.md`
- `application/helpers/common_helper.php`
- `application/helpers/multi_language_helper.php`
- `application/helpers/youngo_frontend_language_helper.php`
- `application/helpers/youngo_frontend_content_helper.php`
- `application/controllers/Home.php`
- `application/views/frontend/youngo/header.php`
- `application/views/frontend/youngo/footer.php`
- `application/views/frontend/youngo/subscriptions.php`
- `application/models/Youngo_subscription_model.php`
- `application/models/Youngo_language_phrase_model.php`
- `application/config/routes.php`

## C. Files Changed

- `application/helpers/youngo_frontend_language_helper.php`
- `application/models/Youngo_subscription_model.php`
- `application/views/frontend/youngo/header.php`
- `application/views/frontend/youngo/footer.php`
- `application/views/frontend/youngo/subscriptions.php`
- `scripts/phase_2/youngo_language_frontend_phrase_wire_1_diagnostic.php`
- `docs/qa/youngo_language_frontend_phrase_wire_1_report.md`

## D. Frontend Phrase Helper Summary

Added DB-first public frontend phrase behavior in `youngo_frontend_phrase()`.

- Uses URI language context, not backend/session language:
  - `/en` resolves `english`
  - `/ar` resolves `arabic`
  - unprefixed public frontend resolves `arabic`
- Uses canonical UI language codes only: `english`, `arabic`
- Rejects `arabic_translated` as a phrase language code and falls back safely
- Reads `phrase`, `english`, and `arabic` from the existing `language` DB table
- Uses request-level static caching for phrase rows and language-table readiness
- Does not call legacy `get_phrase()` or `site_phrase()` for frontend label lookup
- Does not insert, update, or create phrase rows
- Added `youngo_frontend_phrase_e()` for escaped view output

## E. Labels Wired In This Phase

Updated the current safe public surfaces only:

- Header/nav labels: Home, Courses, Subscriptions, Blog, Contact, Login, Sign up, My Courses, My Wishlist, nav aria labels
- Footer labels: tagline, footer navigation links
- Subscriptions page labels: page title/copy, empty state, featured/subscription badges, safe contact CTA, coming-soon copy
- Subscription plan display labels: Duration, day/days, and currency label fallback

No broad frontend conversion was attempted in this phase.

## F. Arabic/Default QA

HTTP smoke checks passed for:

- `/`
- `/subscriptions`

Results:

- HTTP `200`
- `lang="ar"`
- `dir="rtl"`
- header rendered
- subscriptions nav link generated as unprefixed `/subscriptions`
- no canonical `/ar` links generated
- no Paymob/checkout/payment form/action links detected

## G. `/en` QA

HTTP smoke checks passed for:

- `/en`
- `/en/subscriptions`

Results:

- HTTP `200`
- `lang="en"`
- `dir="ltr"`
- header rendered
- subscriptions nav link generated as `/en/subscriptions`
- no Paymob/checkout/payment form/action links detected

## H. `/ar` Compatibility QA

HTTP smoke checks passed for:

- `/ar`
- `/ar/subscriptions`

Results:

- HTTP `200`
- `lang="ar"`
- `dir="rtl"`
- generated Arabic links canonicalize to unprefixed URLs
- no new `/ar` canonical links generated
- no Paymob/checkout/payment form/action links detected

## I. Fallback Behavior

Fallback order is:

1. Selected URI language value from `language` DB table
2. English value from `language` DB table
3. existing local phrase map fallback
4. provided fallback string
5. humanized phrase key

The diagnostic confirmed DB rows with English and Arabic values for key public labels including `home`, `courses`, `subscriptions`, `blog`, `contact`, `contact_us`, and `duration`.

Some subscription-specific keys are not present in the DB yet, including keys such as `subscription_plans`, `featured_plan`, `days`, and `egp`. They resolve safely through fallback, but should be added/polished through a controlled phrase seed/copy phase rather than by this no-DB-write phase.

## J. Payment/CTA Safety

- No Paymob behavior changed
- No payment routes localized
- No checkout/cart/payment/order/enrol/grant links added to header/footer/subscriptions
- Subscriptions page still uses safe contact/coming-soon actions only
- No DB writes were added to frontend phrase helper or diagnostics

## K. Diagnostic Result

Passed:

- `php scripts/phase_2/youngo_language_frontend_phrase_wire_1_diagnostic.php`
- `php scripts/phase_2/youngo_language_edit_phrase_pagination_ui_1_diagnostic.php`
- `php scripts/phase_2/youngo_localization_ar_default_links_1_diagnostic.php`

Syntax validation passed for every changed PHP file.

`git diff --check` passed.

## L. Remaining Risks/Blockers

- This phase did not seed missing phrase keys because DB/phrase edits were explicitly out of scope.
- Some subscription labels may still use fallback/local-map text until a controlled phrase seed and copy-polish phase adds DB rows.
- This phase did not convert every frontend hardcoded label; it only wired the current header/footer/subscriptions surfaces.
- Exact Arabic copy quality was not changed or audited here.

## M. Recommended Next Phase

Recommended next phase:

- `LANGUAGE.FRONTEND.PHRASE.SEED.SUBSCRIPTIONS.1`

Scope:

- Add missing subscription/navbar phrase keys through the approved phrase-management path
- Polish Arabic copy through Edit Phrase
- Re-run frontend phrase/browser QA for `/`, `/en`, `/ar`, `/subscriptions`, `/en/subscriptions`, and `/ar/subscriptions`

## N. Git Status

Final `git status --short`:

```text
 M application/helpers/youngo_frontend_language_helper.php
 M application/models/Youngo_subscription_model.php
 M application/views/frontend/youngo/footer.php
 M application/views/frontend/youngo/header.php
 M application/views/frontend/youngo/subscriptions.php
?? docs/qa/youngo_language_frontend_phrase_wire_1_report.md
?? scripts/phase_2/youngo_language_frontend_phrase_wire_1_diagnostic.php
```
