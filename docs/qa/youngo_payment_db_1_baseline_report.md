# YounGo Payment DB Baseline Report

Phase: PAYMENT.DB.1 - Local Payment DB Baseline Lock and Gateway Hygiene Plan  
Date: 2026-07-20  
Scope: local DB backup, read-only baseline inspection, and non-executed hygiene planning

## A. Current Branch/Status

Start commands:

```text
git branch --show-current
analysis/cms-audit

git status --short
<clean>
```

Recent history reviewed:

```text
727ef72 Audit YounGo payment flow with local DB
e5f6a5f Document live cPanel client demo handoff
0216016 Add YounGo cPanel deployment runbook
9cd4b3d Prepare YounGo sanitized client admin export
49aa360 Test YounGo cPanel package restore locally
2337d79 Prepare YounGo cPanel client package
8357c24 Reconcile YounGo client upload plan
7222c37 Prepare YounGo client upload preflight
7a04e30 Polish YounGo category images and homepage navigation
b80473a Fill YounGo demo surfaces with polished local imagery
```

## B. Backup Created

A local SQL backup was created before any future payment DB hygiene work.

```text
Path: D:\Work\YounGo\backups\youngo_school_before_payment_db_1_baseline_2026_07_20_205512.sql
Size: 627911 bytes
SHA256: ccdfaa7ef2de011b388056349a1bfaa50898892c9f9bc19d06230290e3eef460
```

No live cPanel server was touched.

## C. DB/Config Baseline

Current local settings:

| Setting | Value |
|---|---|
| `system_currency` | `EGP` |
| `currency_position` | `left` |
| `course_selling_tax` | `0` |
| `course_accessibility` | `publicly` |

Current local table baseline:

| Table/Lookup | Exists | Row Count |
|---|---:|---:|
| `payment_gateway` | no | n/a |
| `payment_gateways` | yes | 15 |
| `payment` | yes | 0 |
| `enrol` | yes | 1 |
| `course` | yes | 8 |
| `coupon` | no | n/a |
| `coupons` | yes | 0 |
| `coupon_code` | no | n/a |
| `settings` | yes | 66 |
| `youngo_checkout_orders` | yes | 0 |
| `youngo_coupon_usages` | yes | 0 |
| `youngo_coupon_subscription_plans` | yes | 0 |
| `youngo_coupon_courses` | yes | 0 |
| `youngo_course_access` | yes | 0 |
| `youngo_user_subscriptions` | yes | 0 |
| `youngo_manual_grants` | yes | 0 |
| `youngo_subscription_plans` | yes | 3 |

Paymob baseline:

- No Paymob-related DB table was found.
- No Paymob-related settings row was found.
- No application Paymob payment flow is implemented yet.

Legacy gateway-like settings baseline:

- Legacy PayPal, Stripe, and Razorpay settings rows exist with credential-like values present.
- Related legacy currency settings include non-EGP values.
- Values were treated as secrets and are intentionally not reproduced here.

Course/payment baseline:

- `course = 8`.
- `subscription_only = 6`.
- `subscription_and_purchase = 1`.
- `purchase_only = 1`.
- All current local courses are YounGo-managed by `youngo_access_mode`.
- 5 courses have prices above 0.
- 1 course has discount enabled.
- Course price range is 0 to 1500.

Subscription baseline:

- `youngo_subscription_plans = 3`.
- All plans are `EGP`.
- All plans are inactive.
- All plans are non-purchasable.

## D. Gateway Rows Summary

Gateway table totals:

| Metric | Count |
|---|---:|
| Gateway rows | 15 |
| Active rows | 15 |
| Test-mode rows | 15 |
| Rows with credential-like `keys` present | 15 |
| Rows with `model_name` present | 15 |
| EGP currency rows | 0 |

Every inherited gateway row is currently active in test mode and configured with a non-EGP currency.

## E. Gateway Hygiene Classification

Credential-like fields are reported only as present/absent. Values were not copied.

| ID | Identifier | Title | Active | Test Mode | Currency | Credential-Like Fields | Risk | Recommended Action |
|---:|---|---|---:|---:|---|---|---|---|
| 1 | `paypal` | Paypal | 1 | 1 | USD | present | High | Disable now; redact legacy settings later; replace with Paymob sandbox as target flow. |
| 2 | `stripe` | Stripe | 1 | 1 | USD | present | High | Disable now; keep only as future sandbox candidate if explicitly approved. |
| 3 | `razorpay` | Razorpay | 1 | 1 | INR | present | High | Disable now; redact legacy settings later; not suitable for EGP baseline. |
| 4 | `xendit` | Xendit | 1 | 1 | USD | present | High | Disable now; keep disabled unless a deliberate sandbox test requires it. |
| 5 | `payu` | Payu | 1 | 1 | PLN | present | High | Disable now; keep disabled. |
| 6 | `pagseguro` | Pagseguro | 1 | 1 | BRL | present | High | Disable now; fix hardcoded notify URL before any future use. |
| 7 | `sslcommerz` | SSL Commerz | 1 | 1 | USD | present | High | Disable now; keep disabled unless intentionally tested later. |
| 8 | `skrill` | Skrill | 1 | 1 | USD | present | High | Disable now; keep disabled. |
| 10 | `doku` | Doku | 1 | 1 | USD | present | High | Disable now; keep disabled. |
| 11 | `bkash` | Bkash | 1 | 1 | BDT | present | High | Disable now; review frontend credential exposure before any future use. |
| 12 | `cashfree` | CashFree | 1 | 1 | INR | present | High | Disable now; keep disabled. |
| 13 | `maxicash` | Maxicash | 1 | 1 | USD | present | High | Disable now; keep disabled. |
| 14 | `aamarpay` | Aamarpay | 1 | 1 | BDT | present | High | Disable now; keep disabled. |
| 15 | `flutterwave` | Flutterwave | 1 | 1 | NGN | present | High | Disable now; keep disabled unless explicitly selected for sandbox. |
| 16 | `tazapay` | Tazapay | 1 | 1 | USD | present | High | Disable now; keep disabled. |

Recommended default stance:

- Disable all inherited gateway rows before checkout testing.
- Do not delete rows.
- Preserve schema and gateway records for reference.
- Redact credential-like fields only after confirming admin forms tolerate empty gateway JSON.
- Keep YounGo payment implementation focused on a future Paymob sandbox flow.

## F. Currency/EGP Issues

- Target commercial currency is EGP.
- `settings.system_currency` is correctly set to `EGP`.
- `payment_gateways.currency` has 0 EGP rows.
- Active gateway currencies are mixed across USD, INR, BDT, PLN, BRL, and NGN.
- Legacy gateway settings also include non-EGP currency values.
- Payment testing must not rely on those inherited currencies.
- Future checkout/order writes must explicitly use EGP and must not rely on older USD defaults in schema artifacts.

## G. Coupon/Enrolment Baseline

- `coupons = 0`.
- `coupon` table does not exist.
- `coupon_code` table does not exist.
- `coupons` has both legacy fields and YounGo Phase 2 additive fields.
- `payment = 0`.
- `enrol = 1`.
- `youngo_coupon_usages = 0`.
- Existing legacy code still contains a 100 percent coupon direct-enrol path. It must be fixed or quarantined before checkout testing.

## H. YounGo Entitlement/Access Interaction Risks

- Current legacy payment success writes `enrol` and `payment`.
- It does not write `youngo_checkout_orders`, `youngo_course_access`, `youngo_coupon_usages`, or subscription issuance rows.
- The YounGo read layer can recognize active legacy enrolments, but that is compatibility behavior, not the target payment issuance model.
- Manual grants remain separate and auditable through `Youngo_entitlement_write_model`.
- All current courses are YounGo-managed, so payment CTA suppression is currently preventing accidental legacy checkout entry from normal course pages.
- Direct legacy routes still exist and must be guarded before any local checkout UI is restored.

## I. Proposed SQL File Created

Created:

```text
scripts/phase_2/payment_db_1_gateway_hygiene_proposed.sql
```

The SQL is marked `NOT EXECUTED` and ends with `ROLLBACK`.

The proposed future actions are:

- Disable all inherited legacy payment gateways.
- Keep test mode on for future sandbox-only work.
- Normalize gateway currency labels to EGP only after approval.
- Leave optional key redaction commented until admin form compatibility is checked.
- Leave optional legacy settings cleanup commented until old gateway paths are removed or guarded.
- Preserve rows and schema. No deletion is proposed.

## J. What Was NOT Executed

Not executed:

- The proposed gateway hygiene SQL.
- Gateway disabling.
- Currency updates.
- Credential/key clearing.
- Legacy settings cleanup.
- Payment gateway activation.
- Real payment setup.
- Paymob setup.
- Checkout UI restoration.
- Root Admin changes.
- Live cPanel actions.
- Git commit or push.

Only the backup/export and read-only diagnostics were performed.

## K. Recommended Next Phase

Recommended next phase:

```text
PAYMENT.FIX.1 - Legacy/cart/payment cleanup
```

Suggested focus:

- Guard or remove direct legacy checkout entry points for YounGo-managed courses.
- Quarantine the 100 percent coupon direct-enrol path.
- Fix the gateway selection view bug.
- Remove or guard frontend exposure of credential-like gateway fields.
- Add idempotency and server-side transaction verification requirements to the payment design.
- Keep gateway rows disabled until `PAYMENT.CONFIG.1`.

## L. Git Status

Validation commands run after this report was saved:

```text
git diff --check
<no output>

git status --short
?? docs/qa/youngo_payment_db_1_baseline_report.md
?? scripts/phase_2/payment_db_1_gateway_hygiene_proposed.sql
```
