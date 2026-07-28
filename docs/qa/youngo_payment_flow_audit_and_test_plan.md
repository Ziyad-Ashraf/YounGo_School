# YounGo Payment Flow Audit And Test Plan

Phase: PAYMENT.REVIEW.1 - Local Payment Flow Audit and Test Plan  
Date: 2026-07-20  
Scope: local source and read-only configuration audit only

## A. Current Branch And Status

Start commands:

```text
git branch --show-current
analysis/cms-audit

git status --short
<clean>
```

Recent history reviewed:

```text
e5f6a5f Document live cPanel client demo handoff
0216016 Add YounGo cPanel deployment runbook
9cd4b3d Prepare YounGo sanitized client admin export
49aa360 Test YounGo cPanel package restore locally
2337d79 Prepare YounGo cPanel client package
8357c24 Reconcile YounGo client upload plan
7222c37 Prepare YounGo client upload preflight
7a04e30 Polish YounGo category images and homepage navigation
b80473a Fill YounGo demo surfaces with polished local imagery
bd170fe Add final YounGo public demo screenshots QA
```

Constraints followed:

- No live cPanel work.
- No deploy, upload, push, or commit.
- No database writes.
- No real payment credentials added or printed.
- No payment gateway activation.
- No Paymob/live checkout enablement.

## B. Payment-Related Files Inspected

Primary controllers and models:

- `application/controllers/Home.php`
- `application/controllers/Payment.php`
- `application/controllers/Admin.php`
- `application/controllers/Youngo_manual_grants.php`
- `application/controllers/Youngo_subscription_plans.php`
- `application/models/Payment_model.php`
- `application/models/Crud_model.php`
- `application/models/Youngo_entitlement_model.php`
- `application/models/Youngo_entitlement_write_model.php`
- `application/models/Youngo_subscription_model.php`

Helpers, config, and routes:

- `application/helpers/common_helper.php`
- `application/helpers/user_helper.php`
- `application/helpers/youngo_entitlement_helper.php`
- `application/helpers/youngo_frontend_language_helper.php`
- `application/config/routes.php`
- `application/config/database.php` was inspected for structure only. Secret values were not copied into this report.

Frontend and payment views:

- `application/views/frontend/youngo/course_listing/course_card.php`
- `application/views/frontend/youngo/course_page.php`
- `application/views/frontend/youngo/shopping_cart.php`
- `application/views/frontend/youngo/shopping_cart_inner_view.php`
- `application/views/frontend/youngo/cart_items.php`
- `application/views/frontend/youngo/invoice.php`
- `application/views/frontend/youngo/my_wishlist.php`
- `application/views/frontend/youngo/wishlist_items.php`
- `application/views/payment-global/index.php`
- `application/views/payment-global/payment_gateway.php`
- `application/views/payment-global/*/payment_form.php`

Backend payment/admin views:

- `application/views/backend/admin/payment_settings.php`
- `application/views/backend/admin/coupons.php`
- `application/views/backend/admin/coupon_add.php`
- `application/views/backend/admin/coupon_edit.php`
- `application/views/backend/admin/purchase_history.php`
- `application/views/backend/admin/enrol_history.php`
- `application/views/backend/admin/enrol_student.php`
- `application/views/backend/admin/invoice.php`
- `application/views/backend/admin/invoice_print.php`

Database and planning/schema artifacts:

- `database/phase_2/youngo_phase_2e_schema_up.sql`
- `docs/planning/youngo_master_plan_v2.md`
- `docs/planning/youngo_phase_2_hybrid_access_subscriptions_roles_architecture.md`
- `docs/planning/README.md`
- `docs/qa/youngo_client_upload_reconciliation_review.md`
- `docs/qa/youngo_client_package_1b_admin_export_hygiene_report.md`
- `docs/qa/youngo_client_package_2_restore_test_report.md`

## C. DB/Config Items Inspected

Fresh DB row inspection was rerun read-only after local MySQL was started. No DB rows were modified and no credential values were copied into this report.

Current local settings:

- `settings.system_currency = EGP`.
- `settings.currency_position = left`.
- `settings.course_selling_tax = 0`.
- `settings.course_accessibility = publicly`.

Current local table counts:

| Table | Exists | Row Count |
|---|---:|---:|
| `settings` | yes | 66 |
| `payment_gateways` | yes | 15 |
| `coupons` | yes | 0 |
| `payment` | yes | 0 |
| `enrol` | yes | 1 |
| `invoice` | no | n/a |
| `youngo_checkout_orders` | yes | 0 |
| `youngo_coupon_usages` | yes | 0 |
| `youngo_coupon_subscription_plans` | yes | 0 |
| `youngo_coupon_courses` | yes | 0 |
| `youngo_course_access` | yes | 0 |
| `youngo_user_subscriptions` | yes | 0 |
| `youngo_manual_grants` | yes | 0 |
| `youngo_subscription_plans` | yes | 3 |

Current local payment gateway summary:

| Gateway | Status | Test Mode | Currency |
|---|---:|---:|---|
| `aamarpay` | 1 | 1 | BDT |
| `bkash` | 1 | 1 | BDT |
| `cashfree` | 1 | 1 | INR |
| `doku` | 1 | 1 | USD |
| `flutterwave` | 1 | 1 | NGN |
| `maxicash` | 1 | 1 | USD |
| `pagseguro` | 1 | 1 | BRL |
| `paypal` | 1 | 1 | USD |
| `payu` | 1 | 1 | PLN |
| `razorpay` | 1 | 1 | INR |
| `skrill` | 1 | 1 | USD |
| `sslcommerz` | 1 | 1 | USD |
| `stripe` | 1 | 1 | USD |
| `tazapay` | 1 | 1 | USD |
| `xendit` | 1 | 1 | USD |

Gateway credential/config shape, without printing values:

- `payment_gateways` rows: 15.
- Active gateway rows: 15.
- Rows with non-empty `keys`: 15.
- Rows with non-empty `model_name`: 15.
- Gateway currencies are not aligned to EGP.

Coupon/payment/enrolment/access state:

- `coupons = 0`.
- `payment = 0`.
- `enrol = 1`, covering 1 user and 1 course.
- `youngo_checkout_orders = 0`.
- `youngo_coupon_usages = 0`.
- `youngo_course_access = 0`.
- `youngo_user_subscriptions = 0`.
- `youngo_manual_grants = 0`.
- `youngo_subscription_plans = 3`, all `EGP`, inactive, and non-purchasable.

Course/access-mode state:

- `course = 8`.
- `subscription_only = 6`.
- `subscription_and_purchase = 1`.
- `purchase_only = 1`.
- All current local courses are YounGo-managed by `youngo_access_mode`.
- 5 courses have a price above 0, 1 course has discount enabled, and prices range from 0 to 1500.

Source/config findings:

- Gateway selection reads active rows from `payment_gateways` with `status = 1`.
- Gateway credentials and modes are stored in `payment_gateways.keys`, `payment_gateways.model_name`, `enabled_test_mode`, and `status`.
- `Payment_model::configure_course_payment()` builds session-based `payment_details`.
- `Payment::success_course_payment()` uses the selected gateway identifier to call a dynamic `check_{gateway}_payment` method.
- No application-level Paymob controller, model, or payment view was found. Paymob remains a future phase.
- Several gateway APIs and SDK URLs are hardcoded for third-party providers.
- `database/phase_2/youngo_phase_2e_schema_up.sql` contains YounGo checkout/coupon/access tables and includes USD defaults that must be overridden/aligned before real order issuance.

## D. Current Payment Flow Map

1. Course discovery starts from course listing, wishlist, or course detail.
2. YounGo-managed courses with access modes `subscription_only`, `subscription_and_purchase`, or `purchase_only` currently hide legacy Add to cart, Buy Now, and Enroll CTAs when the learner has no active access. They show access-managed or checkout-not-ready messaging instead.
3. Non-managed legacy courses can still use legacy cart and buy-now paths.
4. Cart state is stored in the session as `cart_items`.
5. `Home::handleCartItems()`, `Home::handle_cart_items()`, `Home::handle_buy_now()`, and `Home::handleCartItemForBuyNowButton()` add/remove/update session cart items.
6. `Home::shopping_cart()` renders the YounGo cart views.
7. `Home::apply_coupon()` reloads the cart fragment after a coupon code is posted.
8. `application/views/frontend/youngo/shopping_cart_inner_view.php` validates coupon codes through `Crud_model::check_coupon_validity()` and writes `applied_coupon` into the session from the view layer.
9. `Home::course_payment()` requires a logged-in user and non-empty cart, calls `Payment_model::configure_course_payment()`, and redirects to `/payment`.
10. `Payment_model::configure_course_payment()` calculates line items, coupon discount, tax, total payable amount, and payment URLs, then stores `payment_details` in the session.
11. `Payment::index()` renders `application/views/payment-global/index.php`.
12. `application/views/payment-global/payment_gateway.php` reads enabled gateway rows from `payment_gateways` and includes each gateway's `payment_form.php`.
13. Gateway initiation happens through provider-specific forms or `Payment::create_*` methods.
14. Gateway return/success routes call `Payment::success_course_payment($payment_method)`.
15. `Payment::success_course_payment()` loads the gateway row, dynamically calls `Payment_model::check_{gateway}_payment()`, and treats a truthy result as payment success.
16. On successful course payment, `Crud_model::enrol_student()` writes/updates legacy `enrol` rows from the session cart.
17. `Crud_model::course_purchase()` writes legacy `payment` rows from the session cart.
18. On success, session `cart_items`, `payment_details`, and `applied_coupon` are cleared and the learner is redirected to `home/my_courses`.
19. On failed verification, the learner is redirected back to `home/shopping_cart`.
20. Admin visibility is through existing Academy surfaces such as purchase history, invoices, enrol history, admin revenue, instructor revenue, and manual enrolment views.

Free course flow:

- `Home::get_enrolled_to_free_course($course_id)` still exists.
- It now blocks YounGo-managed access modes from legacy free enrolment.
- Non-managed free courses can still create/update legacy `enrol` rows through `Crud_model::enrol_to_free_course()`.

Coupon 100 percent flow:

- `Home::coupon_offer_100_percent()` still exists.
- If a valid 100 percent coupon produces a zero total, it calls `Crud_model::enrol_student()` directly.
- This bypasses gateway verification and does not create a YounGo checkout order, YounGo coupon usage, YounGo access row, or normal paid gateway transaction.

## E. Current Blockers/Risks

Local payment configuration blocker:

- All 15 local gateway rows are active (`status = 1`) and test mode enabled (`enabled_test_mode = 1`).
- All 15 gateway rows have non-empty `keys` and non-empty `model_name` values.
- Gateway currencies are mixed and none are aligned to the required YounGo commercial currency EGP.
- Checkout testing must not start until gateway rows are deliberately reduced to an approved sandbox-only configuration with EGP-compatible provider behavior.

Gateway and credential risks:

- Active gateway rows could expose provider forms immediately because `payment_gateway.php` renders every `payment_gateways.status = 1` row.
- Several gateway forms pass public keys into JavaScript as expected, but some inherited forms appear to include more sensitive provider fields client-side if enabled. This must be reviewed before any gateway activation.
- No secrets were copied into this report.
- SSL peer verification is disabled in parts of legacy gateway verification code and must be corrected before any real payment traffic.
- Some callbacks rely heavily on session state and return parameters. Robust server-side transaction lookup, amount/currency checks, and replay protection are incomplete or inconsistent by gateway.

Currency risks:

- EGP is the required commercial currency, but the current local gateway rows use mixed non-EGP currencies.
- YounGo Phase 2 schema artifacts include USD defaults in checkout/subscription/access-related tables. Real order issuance must explicitly use EGP and should not rely on those defaults.
- Gateway currency alignment remains deferred and must be handled before sandbox or live payment approval.

Checkout and coupon risks:

- Legacy cart/checkout routes still exist even though YounGo-managed payment CTAs are hidden for the demo.
- Legacy percentage coupon logic is still wired into cart/payment calculations.
- Additive YounGo coupon schema supports richer scope/usage concepts, but current legacy coupon code does not fully enforce those target rules.
- The 100 percent coupon path can create enrolments directly and should not be used as the target YounGo payment/access model.
- No coupon usage row is written in `youngo_coupon_usages` by the legacy flow.

Entitlement/enrolment risks:

- Successful legacy payment creates `enrol` and `payment` rows only.
- It does not create `youngo_checkout_orders`, `youngo_course_access`, `youngo_coupon_usages`, or subscription issuance rows.
- The YounGo read layer can see active legacy enrolments, but payment success is not yet a formal YounGo order/access write.
- Manual grants are separate, auditable, and should remain the only YounGo entitlement write path until checkout issuance is implemented and QA-approved.

Paymob risks:

- No Paymob implementation was found in application payment code.
- Paymob sandbox initiation, callback validation, HMAC verification, idempotency, EGP enforcement, and order/access issuance still need implementation.

## F. Legacy Errors Noticed

- Including `application/config/database.php` outside the normal CodeIgniter entrypoint raised an `ENVIRONMENT` constant issue before the read-only DB probe was adjusted.
- The first read-only DB probe could not connect because XAMPP/MySQL was off. This was resolved by rerunning after MySQL started.
- `application/views/payment-global/payment_gateway.php` contains a suspicious variable-variable check: `isset($$empty_key_of_instructor)`.
- `application/views/payment-global/pagseguro/payment_form.php` contains a hardcoded placeholder notify URL to `https://creativeitem.com`.
- Several legacy gateway methods contain hardcoded sandbox/production URLs and provider-specific assumptions.
- Some gateway code paths use GET/session-driven return handling and should be treated as legacy until validated under sandbox.
- Some inherited gateway checks are stubs or incomplete compared with modern provider verification requirements.

## G. EGP/Currency Notes

- YounGo commercial currency must remain EGP.
- Fresh local DB inspection shows `settings.system_currency = EGP` and `settings.currency_position = left`.
- Previous display normalization made values readable as examples like `EGP 500` while respecting configured currency position.
- Payment gateway rows are active in test mode, but their provider currency fields are not EGP.
- The source-level YounGo Phase 2 schema includes USD defaults that are not acceptable for real YounGo payment issuance without explicit EGP handling.
- No real gateway should be activated until gateway currency alignment is verified under sandbox.

## H. YounGo Entitlement/Enrolment Interaction Notes

- `Youngo_entitlement_model` reads legacy enrolments, direct YounGo course access, active subscriptions, manual grants, and instructor/admin states.
- Learner My Courses intentionally lists active legacy enrolments and direct YounGo/manual course access, but does not flood the list with every subscription-eligible course.
- Manual Grants uses `Youngo_entitlement_write_model` for grant/revoke writes and remains the authoritative YounGo entitlement write surface.
- Current legacy successful payment uses `Crud_model::enrol_student()` and `Crud_model::course_purchase()`.
- Current legacy successful payment does not call `Youngo_entitlement_write_model` for order/access issuance.
- Payment implementation should add a compatibility layer that preserves Academy `enrol`/`payment` behavior while also writing auditable YounGo checkout/access/coupon records where required.
- Free-course legacy enrol is now blocked for YounGo-managed access modes, but still allowed for non-managed free courses.
- 100 percent coupon enrolment is the highest-priority conflict with the target access model because it grants legacy enrolment without a validated transaction or YounGo coupon usage record.

## I. Local Test Matrix

Run this matrix only after a local backup is taken, gateway rows are reduced to an approved sandbox-only configuration, EGP compatibility is confirmed, and test credentials are intentionally configured outside committed source.

| Scenario | Setup | Steps | Expected Result | Data Checks |
|---|---|---|---|---|
| Free course access | Logged-in learner, non-managed free course | Open course detail, click Enroll Now | Legacy enrol succeeds only for non-managed free course | One `enrol` row for learner/course; no `payment`; no YounGo checkout row |
| YounGo-managed free-course guard | Logged-in learner, YounGo-managed free-like course | Hit course detail and direct `home/get_enrolled_to_free_course/{id}` | CTA is access-managed; direct route redirects without enrol | No new `enrol`, `payment`, YounGo access, or coupon rows |
| Subscription-only display | Guest and logged-in learner, subscription-only course | Open listing/detail | Checkout-not-ready/access-managed message; no legacy Add to cart/Buy Now | No session cart item created |
| Paid legacy course checkout | Logged-in learner, non-managed paid course | Add to cart, continue to payment | Payment page loads only sandbox-enabled gateway choices | Session has `cart_items` and `payment_details`; no DB writes before gateway success |
| Coupon apply | Valid legacy coupon | Apply coupon in cart | Total recalculates in cart and payment details | Session `applied_coupon` set; no DB coupon usage row in legacy flow |
| Coupon remove/invalid | Empty or invalid coupon | Submit cart coupon area again or refresh cart | Discount clears or invalid message shows | Session `applied_coupon` absent/null; no DB writes |
| 100 percent coupon | Valid 100 percent coupon and paid legacy course | Apply coupon and attempt Enroll Now | Current legacy path grants enrol; should be flagged for cleanup before YounGo use | `enrol` row appears; no payment/order/coupon usage row |
| Failed payment | Sandbox gateway failure response | Start checkout and force failed verification | Redirects to cart/payment failure state | No `enrol`; no `payment`; no YounGo access/order |
| Cancelled payment | Start sandbox checkout then cancel | Return through cancel/back route | Learner returns to cart/payment page | Cart remains; no payment/enrol/access rows |
| Successful sandbox payment | Sandbox gateway with known test credential | Complete valid payment | Learner redirected to My Courses | Legacy `enrol` and `payment` rows created; verify amount and EGP; future YounGo order/access rows pending implementation |
| Duplicate callback | Repeat a successful return/callback | Replay same provider reference | No duplicate enrol/payment/order should be created | Legacy behavior must be measured; target behavior requires idempotency |
| Already-enrolled learner | Learner already has active enrol/access | Attempt paid checkout again | Checkout should block or safely avoid duplicate grant/payment | No duplicate active entitlement; legacy payment behavior must be verified |
| Guest checkout | Guest clicks buy/cart/checkout | Attempt Add to cart/Buy Now and Continue to Payment | User is redirected to login before payment | URL history preserved; no DB writes |
| Logged-in learner checkout | Logged-in learner with cart | Continue to Payment | `payment_details` is configured and payment page renders | No DB writes until successful gateway verification |
| Admin payment review | Admin after controlled sandbox payment | Open purchase history, invoice, enrol history, revenue views | Payment/enrol visible through existing Academy admin surfaces | Verify payment amount/currency, enrol user/course, invoice display |

## J. Recommended Next Phases

1. `PAYMENT.DB.1 - Local payment DB baseline lock`
   - Take a backup and preserve the current read-only baseline: EGP system currency, 15 active test-mode gateway rows with non-EGP currencies, 0 coupons/payments/YounGo order or access rows, 1 legacy enrol row, and 3 inactive non-purchasable EGP subscription plans.

2. `PAYMENT.FIX.1 - Legacy/cart/payment cleanup`
   - Fix or quarantine unsafe legacy payment paths before exposing checkout.
   - Prioritize 100 percent coupon direct enrol, active mixed-currency gateway rows, gateway form secret exposure, SSL verification, placeholder URLs, and duplicate callback behavior.

3. `PAYMENT.CONFIG.1 - Sandbox gateway setup`
   - Configure only sandbox/test gateway rows locally.
   - Keep credentials out of committed source and confirm EGP alignment.

4. `PAYMENT.FLOW.1 - Checkout UI restoration for local testing`
   - Restore payment CTAs only for controlled local fixtures.
   - Keep subscription-only public checkout-not-ready behavior until YounGo checkout is implemented.

5. `PAYMENT.PAYMOB.1 - Sandbox initiation/callback validation`
   - Implement Paymob sandbox initiation, EGP amount handling, callback/HMAC validation, idempotency, and failure/cancel states.

6. `PAYMENT.QA.1 - End-to-end local payment smoke`
   - Run the local test matrix against sandbox-only payment configuration.
   - Verify protected Root Admin behavior and confirm no unintended entitlement or payment rows remain after cleanup/restore.

## K. Git Status

Validation commands run after this report was saved:

```text
git diff --check
<no output>

git status --short
?? docs/qa/youngo_payment_flow_audit_and_test_plan.md
```

Final working tree status for this phase:

```text
?? docs/qa/youngo_payment_flow_audit_and_test_plan.md
```
