# SUBSCRIPTIONS.PAGE.DYNAMIC.MODEL.1 Report

## A. Current branch/status

- Branch: `analysis/cms-audit`
- Initial status: clean worktree.
- Latest commits at start included:
  - `73a2af2 Preserve Arabic default auth redirect language`
  - `78a4092 Plan Arabic default auth redirect preservation`
  - `b73d1e2 QA Arabic default authenticated public routes`
  - `dbe04f4 QA Arabic default public links`
  - `8922dff Add Arabic default public link helpers`

## B. Files inspected

- `docs/qa/youngo_system_ar_default_and_dynamic_subscriptions_audit_1_report.md`
- `docs/qa/youngo_localization_ar_default_plan_1_report.md`
- `docs/qa/youngo_localization_ar_default_links_1_report.md`
- `docs/qa/youngo_localization_ar_default_auth_redirects_fix_1_report.md`
- `application/models/Youngo_subscription_model.php`
- `application/controllers/Home.php`
- `application/config/routes.php`
- `application/views/frontend/youngo/`
- `application/helpers/youngo_frontend_language_helper.php`
- `application/helpers/youngo_frontend_content_helper.php`
- `scripts/phase_2/`

## C. Files changed

- `application/models/Youngo_subscription_model.php`
- `scripts/phase_2/youngo_subscriptions_page_dynamic_model_1_diagnostic.php`
- `docs/qa/youngo_subscriptions_page_dynamic_model_1_report.md`

## D. Existing subscription data findings

- Existing data source: `youngo_subscription_plans`.
- Existing model: `Youngo_subscription_model`.
- Existing admin surface: `/admin/youngo/subscription-plans`.
- Relevant fields available locally:
  - `id`
  - `name`
  - `slug`
  - `duration_days`
  - `price`
  - `currency`
  - `is_active`
  - `is_purchasable`
  - `is_featured`
  - `sort_order`
  - `created_at`
  - `updated_at`
  - `archived_at`
  - `archived_by_user_id`
- `slug` is usable as a public identifier because the subscription plan schema has a unique slug key.
- No bilingual subscription-plan name or description fields were found.
- Current local public-eligible result count is `0` because placeholder plans are not active/purchasable.

## E. Public model method summary

Added `Youngo_subscription_model::get_public_subscription_plans($language = null)`.

The method is read-only and returns normalized public plan rows only. It:

- returns an empty array if the table or required fields are missing
- includes active plans only
- includes purchasable plans only
- filters to `EGP`
- excludes non-positive prices and durations
- excludes archived rows when `archived_at` exists
- excludes soft-deleted rows if `deleted_at` or `is_deleted` exists in a future schema
- sorts featured plans first when supported
- sorts by `sort_order ASC`, then `id ASC`

Normalized public fields:

- `id`
- `name`
- `slug`
- `duration`
- `duration_days`
- `duration_label`
- `price`
- `price_display`
- `currency`
- `featured`
- `short_description`

## F. Language/content behavior

- The method accepts current canonical language inputs through the existing frontend language helper.
- Compatibility inputs such as `ar`, `en`, and `arabic_translated` normalize through `youngo_frontend_normalize_language_code()`.
- Because the current subscription table has no bilingual fields, the method returns existing shared `name` values.
- Optional bilingual field support is defensive only: if future fields such as `arabic_name`, `english_name`, `name_ar`, or `name_en` exist, the method can prefer them by requested language.
- No subscription translation schema or content was created in this phase.

## G. Price/duration formatting behavior

Added display-only formatters:

- `format_public_plan_price($price, $currency = null)` returns values such as `EGP 100.00`.
- `format_public_plan_duration($duration_days, $language = null)` returns simple labels such as `1 day` or `30 days`.

These are safe model-level defaults for the future page. Full Arabic duration phrase polish can be handled in the UI/content phase.

## H. Payment/CTA safety

- No checkout links were generated.
- No payment links were generated.
- No Paymob calls were added.
- No checkout orders, payments, enrolments, grants, or entitlement rows are written.
- No public subscriptions route or page was created.
- No admin/backend/action/payment URL localization was added.

## I. Diagnostic result

Created and ran:

```text
php scripts/phase_2/youngo_subscriptions_page_dynamic_model_1_diagnostic.php
```

Result: passed.

Diagnostic coverage:

- model method exists
- price and duration formatters exist
- public row normalization exists
- active/purchasable/EGP/positive price/positive duration filters exist
- archive exclusion exists when supported
- sorting behavior exists
- frontend language helper compatibility works
- actual model call returns only normalized public-safe rows
- current empty state is supported with `0` public-eligible rows
- no subscription public page/route was created
- no payment/checkout/write behavior was added
- changed-file scope is limited to this phase

## J. Remaining risks/blockers

- No public subscriptions page exists yet.
- No bilingual subscription-plan fields exist yet; future Arabic content will need either schema/content work or owner-approved shared copy.
- Current placeholder plans are inactive/non-purchasable, so the future public page will show an empty state until real plans are approved and activated.
- CTA behavior must remain gated in the UI phase to avoid exposing checkout before payment readiness is approved.

## K. Recommended next phase

Recommended next phase:

```text
SUBSCRIPTIONS.PAGE.DYNAMIC.UI.1
```

Scope:

- add Arabic/default `/subscriptions`
- add English `/en/subscriptions`
- keep `/ar/subscriptions` as compatibility if route strategy supports it
- render active/purchasable public plans from `get_public_subscription_plans()`
- show an empty state when no active/purchasable plans exist
- do not expose checkout/payment CTAs until the payment gate is explicitly approved

## L. Git status

Final status after this phase:

```text
 M application/models/Youngo_subscription_model.php
?? docs/qa/youngo_subscriptions_page_dynamic_model_1_report.md
?? scripts/phase_2/youngo_subscriptions_page_dynamic_model_1_diagnostic.php
```
