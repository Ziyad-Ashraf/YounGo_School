# SUBSCRIPTIONS.PAGE.DYNAMIC.QA.1 Report

## A. Current branch/status

- Branch: `analysis/cms-audit`
- Initial worktree status: clean.
- Latest commit at start: `a4ac829 Add dynamic public subscriptions page`.
- No deployment, push, source-code change, Root Admin change, payment behavior change, Paymob change, checkout enablement, order creation, enrolment, access grant, or static subscription card change was performed.

## B. Backup created

Created before temporary QA data:

```text
D:\Work\YounGo\backups\youngo_school_before_subscriptions_page_dynamic_qa_1_2026_07_26_014450.sql
```

Backup size: `594941` bytes.

## C. URLs tested

Empty state, populated state, and post-cleanup state:

- `/subscriptions`
- `/en/subscriptions`
- `/ar/subscriptions`

Navigation/language smoke:

- `/`
- `/en`
- `/ar`

Local base URL: `http://localhost`.

## D. Empty state QA

Baseline public-eligible plan count before temporary data: `0`.

Empty-state results:

| URL | Status | Language/dir | Empty state | Payment-link check |
| --- | --- | --- | --- | --- |
| `/subscriptions` | 200 | `lang=ar`, `dir=rtl` | present | none found |
| `/en/subscriptions` | 200 | `lang=en`, `dir=ltr` | present | none found |
| `/ar/subscriptions` | 200 | `lang=ar`, `dir=rtl` | present | none found |

## E. Populated state QA

Inserted one temporary QA subscription plan using only `youngo_subscription_plans`.

Temporary QA values:

- name: `QA Dynamic Subscription Plan 20260726014450`
- slug: `qa-dynamic-subscriptions-page-20260726014450`
- active: `1`
- purchasable: `1`
- currency: `EGP`
- price: `123.45`
- duration: `45`
- featured: `1`
- sort order: `-500`

Public-eligible count during populated QA: `1`.

Populated-state results:

| URL | Status | Dynamic plan | Price | Duration | Featured marker | Empty state | Payment-link check |
| --- | --- | --- | --- | --- | --- | --- | --- |
| `/subscriptions` | 200 | present | `EGP 123.45` | `45 days` | present | absent | none found |
| `/en/subscriptions` | 200 | present | `EGP 123.45` | `45 days` | present | absent | none found |
| `/ar/subscriptions` | 200 | present | `EGP 123.45` | `45 days` | present | absent | none found |

The populated state confirmed the page reads from the DB-backed public model rather than static cards.

## F. DB cleanup

Deleted only the temporary QA row by its unique slug.

Cleanup verification:

- Temporary QA rows remaining: `0`
- Public-eligible plan count after cleanup: `0`
- `youngo_checkout_orders`: `0`
- `payment`: `0`
- `enrol`: `1` existing baseline row
- `youngo_course_access`: `0`
- `youngo_user_subscriptions`: `0`
- `youngo_manual_grants`: `0`

Post-cleanup HTTP checks confirmed the subscription routes returned to empty state and no QA plan text remained.

## G. Navigation/language QA

Navigation and language behavior:

- `/subscriptions`: HTTP 200, `lang=ar`, `dir=rtl`, nav points to `/subscriptions`
- `/en/subscriptions`: HTTP 200, `lang=en`, `dir=ltr`, nav points to `/en/subscriptions`
- `/ar/subscriptions`: HTTP 200, `lang=ar`, `dir=rtl`, generated Arabic nav canonicalizes to `/subscriptions`
- `/`: HTTP 200, Arabic/default nav includes `/subscriptions`
- `/en`: HTTP 200, English nav includes `/en/subscriptions`
- `/ar`: HTTP 200, compatibility page nav canonicalizes to `/subscriptions`

## H. Payment/CTA safety

- No checkout links were found.
- No payment links were found.
- No Paymob links or calls were found.
- No checkout CTA exposure was observed.
- No orders, enrolments, access grants, manual grants, or user subscriptions were created.
- Public CTA behavior remained contact/coming-soon only.

## I. Diagnostic result

Passed after cleanup:

```text
php scripts/phase_2/youngo_subscriptions_page_dynamic_ui_1_diagnostic.php
php scripts/phase_2/youngo_subscriptions_page_dynamic_model_1_diagnostic.php
php scripts/phase_2/youngo_localization_ar_default_links_1_diagnostic.php
```

## J. Files changed

- `docs/qa/youngo_subscriptions_page_dynamic_qa_1_report.md`

No source files were changed in this QA phase.

## K. Remaining risks/blockers

- Public plan count is back to `0`; real subscription plans remain inactive/non-purchasable until owner approval.
- Subscription plan text still has no bilingual schema.
- Subscription checkout remains intentionally unavailable.
- Full visual QA with screenshots across device sizes is still a useful follow-up before launch.

## L. Recommended next phase

Recommended next phase:

```text
SUBSCRIPTIONS.PAGE.DYNAMIC.CONTENT_AND_VISUAL_QA.1
```

Suggested scope:

- decide whether subscription plans need bilingual CMS fields
- perform mobile/desktop visual QA
- keep checkout/payment CTAs disabled until the payment phase explicitly approves them

## M. Git status

Final expected status:

```text
?? docs/qa/youngo_subscriptions_page_dynamic_qa_1_report.md
```
