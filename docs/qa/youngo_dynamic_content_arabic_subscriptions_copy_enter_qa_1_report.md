# DYNAMIC.CONTENT.ARABIC.SUBSCRIPTIONS.COPY.ENTER.QA.1 Report

## A. Current Branch/Status

- Branch: `analysis/cms-audit`
- Start status: clean
- Latest commit at start: `74c9b8d Add admin bilingual subscription plan translation fields`
- Deployment/push: not performed
- Schema/routes/payment behavior: unchanged

## B. Backup Created

- Backup: `D:\Work\YounGo\backups\youngo_school_before_dynamic_content_arabic_subscriptions_copy_enter_qa_1_2026_07_26_044327.sql`
- Size: `925957` bytes
- SHA256: `aef3559b945d845dc3772143a5942522b46f11fac4f497f5670f21e9fc7e63a5`

## C. Plans Inspected

Public-eligible plans before content entry:

| ID | Canonical name | Slug | Duration | Price | Currency | Active | Purchasable | Featured | Sort |
|---:|---|---|---:|---:|---|---:|---:|---:|---:|
| 1 | Monthly | monthly | 30 | 100.00 | EGP | 1 | 1 | 0 | 10 |
| 2 | 3 Months | 3-months | 90 | 250.00 | EGP | 1 | 1 | 1 | 20 |
| 3 | Yearly | yearly | 365 | 900.00 | EGP | 1 | 1 | 0 | 30 |

The canonical names matched the approved copy mapping, so content entry proceeded.

## D. Translation Rows Entered

Six translation rows now exist in `youngo_subscription_plan_translations`:

- Plan 1 `Monthly`: `english`, `arabic`
- Plan 2 `3 Months`: `english`, `arabic`
- Plan 3 `Yearly`: `english`, `arabic`

English rows store the approved English display names. Arabic rows store the approved Arabic name, short description, description, and badge label.

No `arabic_translated` row exists.

## E. Shared Fields Preservation

Verified unchanged after content entry:

- canonical `name`
- `slug`
- `duration_days`
- `price`
- `currency`
- `is_active`
- `is_purchasable`
- `is_featured`
- `sort_order`
- `archived_at`

The copy was entered through the authenticated admin controller path. That path naturally updates plan metadata/audit even when shared values are posted unchanged, so the temporary metadata side effects were cleaned up:

- plan `updated_at` values restored to the pre-entry backup values
- three translation-entry `update` audit rows removed
- translation rows were preserved

## F. Arabic/Default QA

URL tested:

- `/subscriptions`

Result:

- HTTP 200
- `lang=ar`
- `dir=rtl`
- Arabic plan names rendered
- Arabic descriptions rendered
- prices/durations rendered
- payment/checkout/Paymob CTAs absent

## G. `/ar` QA

URL tested:

- `/ar/subscriptions`

Result:

- HTTP 200
- `lang=ar`
- `dir=rtl`
- Arabic plan names rendered
- Arabic descriptions rendered
- prices/durations rendered
- payment/checkout/Paymob CTAs absent

## H. `/en` QA

URL tested:

- `/en/subscriptions`

Result:

- HTTP 200
- `lang=en`
- `dir=ltr`
- English plan names rendered
- Arabic plan copy absent
- prices/durations rendered
- payment/checkout/Paymob CTAs absent

## I. Payment/CTA Safety

- No Paymob/payment files changed.
- No checkout/cart/order/enrol/grant behavior changed.
- No public checkout CTA was introduced.
- Public subscription page continues to use safe non-payment behavior.
- No plan slug, price, duration, currency, active/purchasable, featured, sort, or archive setting was changed.

## J. Diagnostic Result

Passed:

```bash
php scripts/phase_2/youngo_dynamic_content_arabic_subscriptions_copy_enter_qa_1_diagnostic.php
```

The diagnostic verified:

- exactly three public plans are present
- expected slugs and shared fields match
- six translation rows exist
- Arabic rows contain Arabic names and descriptions
- English rows contain English names
- `arabic_translated` is absent
- public model resolves Arabic/default, `/ar`, and `/en` correctly
- slugs, prices, durations, and currency remain from base plans
- no payment/checkout/Paymob links were introduced

Dependency diagnostics also passed:

```bash
php scripts/phase_2/youngo_dynamic_content_arabic_subscriptions_admin_ui_1_diagnostic.php
php scripts/phase_2/youngo_dynamic_content_arabic_subscriptions_model_1_diagnostic.php
```

## K. DB Impact

Persistent DB impact:

- `youngo_subscription_plan_translations`: `6` real content rows
- `youngo_subscription_plans`: shared plan values restored/preserved
- `youngo_subscription_plan_audit_log`: restored to pre-entry max ID after cleanup

No schema, route, payment, checkout, enrolment, grant, or Root Admin data was changed.

## L. Remaining Risks/Blockers

- Subscription checkout remains intentionally unavailable.
- Real commercial prices/activation policy still requires owner approval before any checkout implementation.
- Future public QA should recheck subscriptions after any checkout/payment work so CTAs remain gated until explicitly approved.

## M. Recommended Next Phase

Recommended next phase:

`DYNAMIC.CONTENT.ARABIC.SUBSCRIPTIONS.PUBLIC.QA.1`

Focus:

- browser visual QA for translated subscription cards across desktop/mobile
- verify no English dynamic plan copy remains on Arabic/default and `/ar`
- preserve payment/checkout CTA boundaries

## N. Git Status

At report creation time, source changes were limited to this phase’s diagnostic/report:

```text
?? docs/qa/youngo_dynamic_content_arabic_subscriptions_copy_enter_qa_1_report.md
?? scripts/phase_2/youngo_dynamic_content_arabic_subscriptions_copy_enter_qa_1_diagnostic.php
```
