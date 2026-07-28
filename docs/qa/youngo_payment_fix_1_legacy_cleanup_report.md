# YounGo Payment Fix 1 Legacy Cleanup Report

Phase: PAYMENT.FIX.1 - Legacy Cart and Payment Cleanup Plan  
Date: 2026-07-21  
Scope: local source cleanup and read-only diagnostics only

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
a2e1f81 Document YounGo payment DB baseline
727ef72 Audit YounGo payment flow with local DB
e5f6a5f Document live cPanel client demo handoff
0216016 Add YounGo cPanel deployment runbook
9cd4b3d Prepare YounGo sanitized client admin export
49aa360 Test YounGo cPanel package restore locally
2337d79 Prepare YounGo cPanel client package
8357c24 Reconcile YounGo client upload plan
7222c37 Prepare YounGo client upload preflight
7a04e30 Polish YounGo category images and homepage navigation
```

## B. Files Inspected

Required phase inputs:

- `docs/qa/youngo_payment_flow_audit_and_test_plan.md`
- `docs/qa/youngo_payment_db_1_baseline_report.md`
- `scripts/phase_2/payment_db_1_gateway_hygiene_proposed.sql`

Project guardrails:

- `YOUNGO_PROJECT_CONTEXT.md`
- `docs/design/youngo_style_direction.md`
- `docs/agents/implementation_rules.md`
- `docs/planning/youngo_master_plan_v2.md`
- `docs/planning/youngo_phase_2_hybrid_access_subscriptions_roles_architecture.md`
- `docs/planning/README.md`
- `docs/reference/README.md`

Code paths inspected:

- `application/controllers/Home.php`
- `application/controllers/Payment.php`
- `application/models/Payment_model.php`
- `application/models/Crud_model.php`
- `application/models/Youngo_entitlement_model.php`
- `application/helpers/youngo_entitlement_helper.php`
- `application/views/payment-global/index.php`
- `application/views/payment-global/payment_gateway.php`
- `application/views/payment-global/*/payment_form.php`
- `application/views/frontend/youngo/course_page.php`
- `application/views/frontend/youngo/course_listing/course_card.php`
- `application/views/frontend/youngo/shopping_cart.php`
- `application/views/frontend/youngo/shopping_cart_inner_view.php`
- `application/views/frontend/youngo/cart_items.php`
- `application/views/frontend/youngo/my_wishlist.php`
- `application/views/frontend/youngo/wishlist_items.php`

## C. Files Changed

- `application/controllers/Home.php`
- `application/controllers/Payment.php`
- `application/views/payment-global/payment_gateway.php`
- `scripts/phase_2/youngo_payment_fix_1_legacy_cleanup_diagnostic.php`
- `docs/qa/youngo_payment_fix_1_legacy_cleanup_report.md`

## D. Safe Fixes Applied

`Home.php`:

- Added a small legacy-cart eligibility guard for YounGo-managed courses.
- Prevented direct `handle_buy_now`, `handle_cart_items`, and related cart handlers from adding YounGo-managed courses to the legacy cart.
- Added checkout-time cleanup so any existing YounGo-managed cart items are removed before `course_payment()` can configure legacy payment.
- Kept YounGo-managed course public CTAs suppressed.
- Quarantined the 100 percent coupon direct-enrol path so it no longer grants access directly.
- Added missing array/empty checks around cart and coupon data to avoid notices.

`Payment.php`:

- Added `youngo_gateway_payment_check()` to fail closed when a gateway identifier is empty, invalid, missing, disabled, not EGP-aligned, missing a model, or missing the expected check method.
- Updated course-payment success handling to use the defensive check.
- Updated instructor-payout success handling to avoid the previous no-model success fallback and to validate payout id before status update.

`payment_gateway.php`:

- Replaced raw active-gateway rendering with a safe filtered list.
- Active gateway rows are now rendered only when the configured gateway currency matches `settings.system_currency`.
- The current local DB has 15 active non-EGP inherited gateways, so the payment page will show a disabled-state message instead of gateway forms.
- Removed the suspicious variable-variable check `isset($$empty_key_of_instructor)`.
- Added defensive defaults for missing `payment_details`.
- Skips invalid identifiers and missing gateway form files.

## E. Gateway Selection Behavior After Cleanup

The DB was not changed:

- `payment_gateways = 15`.
- Active gateway rows = 15.
- Active non-EGP gateway rows = 15.
- Active EGP gateway rows = 0.

Runtime selection behavior changed:

- `payment_gateway.php` still reads active gateway rows.
- It now filters unsafe rows before rendering.
- Because all active local rows are non-EGP, no inherited gateway forms should appear.
- The payment page displays a clear disabled-state message that checkout remains disabled until gateway hygiene and sandbox setup are approved.
- Direct callback success methods also reject non-EGP gateway rows.

## F. Public CTA/Demo Safety Result

Public demo safety remains intact:

- YounGo course detail still treats managed access modes as non-checkout-ready.
- YounGo course cards still show managed-access or subscription-not-ready states.
- Payment CTAs were not restored.
- Homepage/course pages were not changed to expose cart or payment shortcuts.
- All 8 current local courses are YounGo-managed by `youngo_access_mode`, and the diagnostic confirms they remain guarded from legacy cart shortcuts.

## G. Coupon/Enrolment Notes

- `coupons = 0` locally.
- `payment = 0` locally.
- `enrol = 1` locally.
- `youngo_coupon_usages = 0` locally.
- The legacy 100 percent coupon route no longer directly calls `Crud_model::enrol_student()`.
- Future coupon support still needs formal checkout/order/access issuance, including zero-total coupons.

## H. YounGo Entitlement Interaction Notes

- The YounGo read layer remains unchanged.
- Manual grants remain the only implemented YounGo entitlement write path.
- Legacy `enrol` compatibility remains readable by `Youngo_entitlement_model`.
- Payment success still uses legacy enrol/payment methods if a future gateway passes validation, but current local gateway configuration cannot pass because no active gateway is EGP-aligned.
- Formal YounGo checkout order, coupon usage, course access, and subscription issuance remain future work.

## I. Diagnostic Result

Created and ran:

```text
php scripts/phase_2/youngo_payment_fix_1_legacy_cleanup_diagnostic.php
```

Result:

```text
ok: true
gateway_total: 15
gateway_active: 15
gateway_active_non_egp: 15
gateway_active_egp: 0
course_count: 8
managed_course_count: 8
payment_count: 0
coupons_count: 0
youngo_checkout_orders_count: 0
youngo_coupon_usages_count: 0
youngo_course_access_count: 0
youngo_user_subscriptions_count: 0
youngo_manual_grants_count: 0
hardcoded_payment_url_file_count: 10
hardcoded_payment_url_hits: 33
```

The diagnostic does not print secrets.

## J. What Was Not Changed

Not changed:

- Live cPanel server.
- Database rows.
- Root Admin.
- Payment gateway credentials.
- Payment gateway DB status.
- Payment gateway DB currency.
- `scripts/phase_2/payment_db_1_gateway_hygiene_proposed.sql` execution state.
- Paymob implementation.
- Real payment activation.
- Public checkout CTA restoration.
- YounGo entitlement write service behavior.
- Manual grant behavior.
- Subscription plan activation or purchasability.

## K. Remaining Blockers

- Gateway DB hygiene SQL still needs owner approval before execution.
- All inherited payment gateways remain active in DB, with non-EGP currencies and credential-like fields present.
- Paymob is not implemented.
- Formal checkout/order lifecycle is not implemented.
- YounGo payment success does not yet write `youngo_checkout_orders`, `youngo_coupon_usages`, `youngo_course_access`, or subscription issuance rows.
- Hardcoded legacy payment URLs remain in inherited gateway files.
- Gateway callback idempotency and server-side transaction verification still need implementation.
- Gateway credential redaction needs admin-form compatibility review before DB cleanup.

## L. Recommended Next Phase

Recommended next phase:

```text
PAYMENT.CONFIG.1 - Sandbox Gateway Setup Planning
```

Purpose:

- Decide whether to execute a reviewed copy of the gateway hygiene SQL.
- Disable inherited gateways in DB.
- Preserve schema and rows.
- Redact or empty credential-like fields only after admin-form compatibility is confirmed.
- Keep payment disabled until Paymob or another approved EGP-compatible sandbox path is intentionally configured.

## M. Git Status

Validation commands run after this report was saved:

```text
php -l application/controllers/Home.php
No syntax errors detected in application/controllers/Home.php

php -l application/controllers/Payment.php
No syntax errors detected in application/controllers/Payment.php

php -l application/views/payment-global/payment_gateway.php
No syntax errors detected in application/views/payment-global/payment_gateway.php

php -l scripts/phase_2/youngo_payment_fix_1_legacy_cleanup_diagnostic.php
No syntax errors detected in scripts/phase_2/youngo_payment_fix_1_legacy_cleanup_diagnostic.php

php scripts/phase_2/youngo_payment_fix_1_legacy_cleanup_diagnostic.php
ok: true

git diff --check
No whitespace errors. Git reported line-ending normalization warnings for the three edited existing PHP files.

git status --short
 M application/controllers/Home.php
 M application/controllers/Payment.php
 M application/views/payment-global/payment_gateway.php
?? docs/qa/youngo_payment_fix_1_legacy_cleanup_report.md
?? scripts/phase_2/youngo_payment_fix_1_legacy_cleanup_diagnostic.php
```
