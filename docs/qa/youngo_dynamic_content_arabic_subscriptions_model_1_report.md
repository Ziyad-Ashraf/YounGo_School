# DYNAMIC.CONTENT.ARABIC.SUBSCRIPTIONS.MODEL.1 Report

## A. Current Branch/Status

- Branch at start: `analysis/cms-audit`
- Worktree at start: clean
- Latest commit at start: `61238b4 Add subscription plan translation schema foundation`
- This phase wired localized subscription plan translation rows into public model output only.
- No deploy, push, subscription plan content edit, translation seed, route change, admin UI change, public checkout/payment CTA exposure, Paymob/payment change, Root Admin change, `arabic_translated` UI usage, or operational slug change was performed.

## B. Files Inspected

- `docs/qa/youngo_dynamic_content_arabic_subscriptions_schema_1_report.md`
- `docs/qa/youngo_dynamic_content_arabic_subscriptions_schema_plan_1_report.md`
- `docs/qa/youngo_subscriptions_page_dynamic_ui_1_report.md`
- `application/models/Youngo_subscription_model.php`
- `application/controllers/Home.php`
- `application/views/frontend/youngo/subscriptions.php`
- `application/views/frontend/youngo/header.php`
- `application/views/frontend/youngo/footer.php`
- `scripts/phase_2/`

## C. Files Changed

- `application/models/Youngo_subscription_model.php`
- `scripts/phase_2/youngo_dynamic_content_arabic_subscriptions_model_1_diagnostic.php`
- `scripts/phase_2/youngo_dynamic_content_arabic_subscriptions_schema_1_diagnostic.php`
- `scripts/phase_2/youngo_subscriptions_page_dynamic_ui_1_diagnostic.php`
- `docs/qa/youngo_dynamic_content_arabic_subscriptions_model_1_report.md`

## D. Public Model Localization Summary

Updated `Youngo_subscription_model::get_public_subscription_plans($language = null)` to:

- normalize the requested public subscription language through a subscription-specific safe path
- reject `arabic_translated` as a subscription UI/content language
- keep `english`/`en` and `arabic`/`ar` support
- fetch public-eligible base plans exactly as before
- bulk-load translation rows for returned plan IDs from `youngo_subscription_plan_translations`
- overlay content fields only:
  - `name`
  - `short_description`
  - `description`
  - `badge_label`
- keep operational fields from `youngo_subscription_plans`:
  - `slug`
  - `duration_days`
  - `price`
  - `currency`
  - `is_active`
  - `is_purchasable`
  - `is_featured`
  - `sort_order`

Added protected model helpers:

- `normalize_public_subscription_language($language)`
- `extract_plan_ids($rows)`
- `load_public_plan_translations($plan_ids, $language)`
- `apply_public_plan_translation($row, $translation)`

The normalized public row now also exposes `description` and `badge_label` for future view/admin use. Existing view rendering remains compatible because `name`, `short_description`, price, duration, and featured fields keep their shape.

## E. Fallback Behavior

Fallback behavior verified:

- Arabic/default uses an Arabic translation row when present.
- `/ar` compatibility behavior uses Arabic translation rows when present.
- `/en` uses English translation rows when present.
- Missing/blank translation fields fall back to base plan fields.
- If translated `short_description` is blank but translated `description` is present, public `short_description` falls back to translated `description`.
- If English translation is missing, English public output falls back to the base plan `name`.
- `arabic_translated` is rejected and is not treated as Arabic.

## F. Temporary Translation QA

Diagnostic QA:

- Used transaction-scoped temporary Arabic and English rows for one public plan.
- Verified Arabic overlay for `name`, `description`, `short_description` fallback, and `badge_label`.
- Verified English overlay for `name`, `short_description`, `description`, and `badge_label`.
- Verified `ar` compatibility input used Arabic values.
- Verified `arabic_translated` returned no public rows.
- Verified slug, price, currency, duration, and featured values remained from the base plan.
- Rolled back the transaction.
- Translation row count before/after diagnostic matched: `0` to `0`.

HTTP smoke QA:

- Inserted temporary Arabic and English translation rows for public plan ID `2`.
- Requested:
  - `http://localhost/subscriptions`
  - `http://localhost/en/subscriptions`
  - `http://localhost/ar/subscriptions`
- Results:
  - `/subscriptions`: HTTP `200`, temporary Arabic plan name rendered.
  - `/en/subscriptions`: HTTP `200`, temporary English plan name rendered.
  - `/ar/subscriptions`: HTTP `200`, temporary Arabic plan name rendered.
  - No Paymob/checkout/payment links detected in tested output.
- Deleted the temporary HTTP QA rows immediately after requests.
- Post-cleanup temporary marker rows: `0`.

## G. Public Page Behavior

No public view layout change was required.

`application/views/frontend/youngo/subscriptions.php` already renders dynamic model fields:

- `name`
- `price_display`
- `duration_label`
- `short_description`
- `featured`
- `currency`

Static labels remain phrase-backed through the frontend phrase helper. CTA behavior remains contact/coming-soon only.

## H. Payment/CTA Safety

- No Paymob/payment/checkout files were changed.
- No checkout links were added.
- No order/enrolment/access/grant/coupon behavior was changed.
- No public subscription checkout CTA was exposed.
- Public eligibility filters still come from base plan fields.
- Operational slugs remain unchanged and are not localized.

## I. Diagnostic Result

Passed:

```text
php scripts/phase_2/youngo_dynamic_content_arabic_subscriptions_model_1_diagnostic.php
php scripts/phase_2/youngo_dynamic_content_arabic_subscriptions_schema_1_diagnostic.php
php scripts/phase_2/youngo_subscriptions_page_dynamic_ui_1_diagnostic.php
```

Also passed PHP lint for changed PHP files.

The existing subscription UI diagnostic was updated only for diagnostic compatibility:

- accepts current `youngo_frontend_phrase_e('subscriptions', ...)` usage in header/footer
- allows the model-phase changed files while preserving route, payment/CTA, and DB-write safety checks

## J. DB Cleanup

Permanent DB changes in this phase: none.

Temporary DB activity:

- Transaction-scoped diagnostic rows were rolled back.
- HTTP QA temporary translation rows were inserted and deleted.

Final cleanup:

- Temporary translation marker rows: `0`
- No real translation rows were seeded.
- No base subscription plan data was edited.

## K. Remaining Risks/Blockers

- Public Arabic/default subscription plan names will still fall back to base English until real Arabic translation rows are entered through an approved admin UI/content phase.
- Admin UI still does not expose bilingual subscription plan fields.
- No persistent English/Arabic translation rows were created for the current 3 plans.
- `badge_label` is available in model output, but the current public view still uses static phrase-backed featured/access labels; a later UI phase can decide whether per-plan badge copy should render.

## L. Recommended Next Phase

Recommended next phase:

`DYNAMIC.CONTENT.ARABIC.SUBSCRIPTIONS.ADMIN.UI.1`

Suggested scope:

- add bilingual subscription plan fields to the existing admin create/edit form
- save English/Arabic translation rows through the existing model foundation
- keep operational fields shared
- preserve no-checkout/no-payment public CTA boundary

## M. Git Status

Pending files after this phase:

- `application/models/Youngo_subscription_model.php`
- `docs/qa/youngo_dynamic_content_arabic_subscriptions_model_1_report.md`
- `scripts/phase_2/youngo_dynamic_content_arabic_subscriptions_model_1_diagnostic.php`
- `scripts/phase_2/youngo_dynamic_content_arabic_subscriptions_schema_1_diagnostic.php`
- `scripts/phase_2/youngo_subscriptions_page_dynamic_ui_1_diagnostic.php`
