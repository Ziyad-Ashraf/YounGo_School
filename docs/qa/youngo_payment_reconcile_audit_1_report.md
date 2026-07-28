# PAYMENT.RECONCILE.AUDIT.1 - Reconcile New YounGo Paymob Work With Existing Academy Payment System

Date: 2026-07-23

Scope: audit/report only. No deployment, push, database write, SQL execution, credential change, private value output, payment activation, Paymob network request, checkout CTA exposure, Root Admin modification, legacy payment behavior change, or refactor was performed.

## A. Current Branch/Status

Start command results:

```text
git branch --show-current
analysis/cms-audit

git status --short
<clean>

git log --oneline -20
769af57 QA blocked YounGo Paymob sandbox readiness
125023c Add controlled YounGo Paymob sandbox intention flow
207fa95 Plan hybrid YounGo Paymob sandbox intention flow
81e6197 Add hybrid YounGo Paymob private config readiness
179bc01 Plan YounGo payment private value handling
73846af QA YounGo Paymob config audit panel
8b7fa78 Wire YounGo Paymob config saves to audit logs
8351916 Add YounGo Paymob config audit schema
9360b74 Fix YounGo payment DB access regressions
3271d7b Fix YounGo Paymob settings HTTP DB access
41dd2f4 QA Paymob dashboard save form blocker
2dfcf0e Add non-private YounGo Paymob dashboard save flow
476bfa7 Plan YounGo Paymob dashboard save flow
f2bb2f8 Add read-only YounGo Paymob dashboard config summary
7b0864d Add YounGo Paymob dashboard config schema foundation
7694e2c Plan YounGo Paymob dashboard configuration
022f936 Plan YounGo Paymob sandbox execution
0d663c1 QA gated YounGo checkout CTA clickthrough
68b9b28 Add gated YounGo checkout CTA helper
bda9cb1 Plan YounGo checkout CTA exposure
```

The expected branch and clean starting worktree were confirmed. The latest commits include the recent YounGo Paymob dashboard/config/audit/hybrid/sandbox work.

## B. Files Inspected

Project guidance:

- `YOUNGO_PROJECT_CONTEXT.md`
- `docs/design/youngo_style_direction.md`
- `docs/planning/README.md`
- `docs/planning/youngo_master_plan_v2.md`
- `docs/planning/youngo_phase_2_hybrid_access_subscriptions_roles_architecture.md`
- `docs/agents/implementation_rules.md`
- `docs/reference/README.md`

Recent YounGo payment reports:

- `docs/qa/youngo_payment_paymob_config_dashboard_plan_1_report.md`
- `docs/qa/youngo_payment_paymob_config_dashboard_schema_1_report.md`
- `docs/qa/youngo_payment_paymob_config_dashboard_ui_1_report.md`
- `docs/qa/youngo_payment_paymob_config_dashboard_save_nonprivate_1_report.md`
- `docs/qa/youngo_payment_paymob_config_dashboard_save_ui_fix_1_report.md`
- `docs/qa/youngo_payment_paymob_config_audit_schema_1_report.md`
- `docs/qa/youngo_payment_paymob_config_audit_wire_1_report.md`
- `docs/qa/youngo_payment_secrets_hybrid_config_1_report.md`
- `docs/qa/youngo_payment_paymob_sandbox_intention_1_report.md`
- `docs/qa/youngo_payment_paymob_sandbox_intention_ui_qa_1_report.md`
- Supporting context from `docs/qa/youngo_payment_schema_1_apply_report.md`, `docs/qa/youngo_payment_entitlement_block_1_report.md`, `docs/qa/youngo_payment_checkout_cta_local_ui_qa_1_report.md`, and `docs/qa/youngo_payment_db_access_sweep_1_report.md`

New YounGo payment source inspected:

- `application/controllers/Youngo_checkout.php`
- `application/controllers/Youngo_payment_webhook.php`
- `application/controllers/Youngo_payment_settings.php`
- `application/models/Youngo_checkout_model.php`
- `application/models/Youngo_payment_model.php`
- `application/models/Youngo_payment_config_model.php`
- `application/models/Youngo_payment_config_audit_model.php`
- `application/models/Youngo_entitlement_write_model.php`
- `application/models/Youngo_entitlement_model.php`
- `application/libraries/Youngo_paymob_config.php`
- `application/libraries/Youngo_paymob_adapter.php`
- `application/libraries/Youngo_paymob_webhook.php`
- `application/libraries/Youngo_paymob_fixture_processor.php`
- `application/helpers/youngo_checkout_cta_helper.php`
- `application/helpers/youngo_entitlement_helper.php`
- `application/views/backend/admin/youngo_payment_settings.php`
- `application/views/frontend/youngo/checkout_order.php`
- `application/views/frontend/youngo/course_page.php`
- `application/views/frontend/youngo/course_listing/course_card.php`
- `application/views/frontend/youngo/my_wishlist.php`
- `application/views/frontend/youngo/wishlist_items.php`
- `application/config/routes.php`
- `application/config/youngo_paymob.php`
- `application/config/youngo_paymob.local.example.php`
- `.gitignore`
- `scripts/phase_2/*payment*schema*.sql`

Existing Academy payment/admin source inspected:

- `application/controllers/Payment.php`
- `application/controllers/Home.php`
- `application/controllers/Admin.php`
- `application/models/Payment_model.php`
- `application/models/Crud_model.php`
- `application/views/payment-global/`
- `application/views/backend/admin/payment_settings.php`
- `application/views/backend/admin/navigation.php`
- `application/views/backend/admin/user_add.php`
- `application/views/backend/admin/user_edit.php`
- `application/helpers/common_helper.php`
- `application/helpers/user_helper.php`

Search terms covered included payment, gateway, Paymob variants, accept, intention, HMAC, webhook, callback, `success_course_payment`, `configure_course_payment`, `payment_gateways`, coupon, cart, enrol, settings, audit, encryption, `encryption_key`, `form_validation`, CSRF, `admin_login`, role, and permission.

## C. New YounGo Payment Work Summary

Checkout/order:

- `Youngo_checkout` provides dedicated YounGo checkout routes:
  - `/youngo/checkout/start/{course_id}`
  - `/youngo/checkout/order/{order_reference}`
  - `/youngo/checkout/return/{order_reference}`
  - `/youngo/checkout/status/{order_reference}`
- Routes are disabled by default through `Youngo_paymob_config`.
- `Youngo_checkout_model` creates/reuses local EGP checkout orders, enforces active learner/course checks, rejects free and non-purchase-compatible courses, prevents duplicate open order creation, and stores safe Paymob references when an Intention is created.
- Return/status routes are UX/status only and do not mutate paid state or issue access.

Transaction/webhook:

- `Youngo_payment_webhook` exposes `/payment/paymob/webhook` as a dedicated, sessionless Paymob endpoint.
- The public webhook route remains fail-closed by default through `webhook_testing_enabled = false`.
- `Youngo_paymob_webhook` provides fixture-safe payload normalization, HMAC source field construction, HMAC calculation/verification, event classification, gateway reference extraction, and redacted payload summaries.
- `Youngo_paymob_fixture_processor` links fixture payload processing to local checkout and transaction models for diagnostics.
- `Youngo_payment_model` records redacted transaction summaries in `youngo_payment_transactions`, marks verified/rejected/duplicate transactions, updates order paid/failed state from verified/rejected transactions, and keeps duplicate handling idempotent.

Entitlement:

- Verified paid order entitlement issuance is bridged through `Youngo_payment_model::issue_paid_order_entitlement()`.
- The bridge calls the existing `Youngo_entitlement_write_model::issue_course_purchase_access()` rather than writing access directly.
- Entitlement issuance requires a paid order, verified transaction, HMAC verification, EGP currency, matching amount/reference, and `entitlement_issued = 0`.
- Duplicate issuance is rejected safely.
- Legacy `payment` and `enrol` rows are not written by the YounGo payment path.

CTA:

- `application/helpers/youngo_checkout_cta_helper.php` centralizes course-detail CTA decisions.
- The helper defaults to hidden unless `checkout_cta_enabled`, `checkout_routes_enabled`, and `checkout_local_testing_enabled` are true.
- It enforces EGP/sandbox, YounGo purchase-compatible course modes, positive amount, active learner access checks, and no legacy cart/payment URLs.
- Only `application/views/frontend/youngo/course_page.php` is wired to the YounGo CTA helper. Homepage, listing, and wishlist are not wired to expose checkout CTAs.

Paymob config/dashboard:

- `Youngo_paymob_config` reads tracked safe defaults and optional ignored `application/config/youngo_paymob.local.php`.
- `application/config/youngo_paymob.php` has all behavior gates false by default and no real values.
- `application/config/youngo_paymob.local.example.php` documents placeholder-only local/server override fields.
- `Youngo_payment_settings` provides `/admin/youngo/payment-settings`.
- `Youngo_payment_config_model` stores non-private Paymob dashboard settings in `youngo_payment_provider_configs`.
- Non-private save currently supports sandbox-only, EGP-only values and rejects activation gates/private fields.
- `Youngo_payment_settings` is Root-Admin-only through the YounGo root admin helper and existing admin session check.

Audit logs:

- `youngo_payment_config_audit_logs` stores redacted dashboard config change audit rows.
- `Youngo_payment_config_audit_model` records non-private changed fields and secret-presence transitions only.
- Dashboard save plus audit insert are wrapped in a transaction; audit failure blocks the config save.
- Recent audit summaries are displayed on the Paymob settings page without raw private values.

Hybrid private config:

- `application/config/config.php` has an empty CodeIgniter `encryption_key`.
- Private DB storage remains blocked.
- Private runtime values are expected only from ignored local/server config.
- Dashboard readiness combines non-private DB config with redacted local/server private presence.
- Allowed private presence labels are limited to states such as `missing`, `configured_redacted`, `server_config_required`, and `db_private_storage_blocked`.

Sandbox Intention:

- `Youngo_paymob_adapter` now includes a controlled `create_sandbox_intention($order, $customer)` method.
- Execution is gated by sandbox mode, EGP, explicit enabled/network/sandbox/local route flags, dashboard non-private config, ignored private config presence, and valid learner/course/order.
- The adapter posts to Paymob Intention endpoint only when all gates pass, builds a Unified Checkout URL from returned data, and redacts client secret/private values.
- Current browser QA was blocked because local ignored private config and dashboard non-private sandbox config were absent. No Paymob call was made.

## D. Existing Academy Payment System Summary

Core legacy payment path:

- `Home::course_payment()` reads `cart_items` from session, removes YounGo-managed access courses from the cart, requires learner login, calls `Payment_model::configure_course_payment()`, then redirects to `payment`.
- `Payment_model::configure_course_payment()` builds session-based `payment_details` from `cart_items`, course prices/discounts, coupon state, tax, gift state, and legacy success/cancel URLs.
- `Payment` requires `payment_details` and `user_id` in session before displaying the gateway page.
- `Payment::success_course_payment($payment_method)` validates the selected legacy gateway via `youngo_gateway_payment_check()`, then calls:
  - `Crud_model::enrol_student()`
  - `Crud_model::course_purchase()`
  - course purchase notification methods
- `Crud_model::enrol_student()` writes or updates `enrol`.
- `Crud_model::course_purchase()` writes `payment`, applies coupon/tax/revenue calculations, and optionally writes affiliate data.

Legacy gateway configuration:

- `Admin::payment_settings()` is protected by admin session plus `check_permission('settings')`.
- `application/views/backend/admin/payment_settings.php` renders global system currency and one form per row from `payment_gateways`.
- `Crud_model::get_payment_gateways()` reads `payment_gateways`.
- `Crud_model::update_payment_settings()` saves `identifier`, `currency`, `enabled_test_mode`, `status`, and arbitrary posted fields into `payment_gateways.keys` JSON.
- `application/config/payment_gateways.php` does not exist in this repo.
- Existing gateway view assets live under `application/views/payment-global/` for inherited gateways such as PayPal, Stripe, Razorpay, Paystack-like/generic providers, SSLCommerz, Xendit, Doku, bKash, Cashfree, Maxicash, Aamarpay, Flutterwave, Tazapay, and others.

Legacy callbacks and verification:

- Gateway checks are implemented in `Payment_model::check_*_payment()` methods and `Payment` gateway-specific methods.
- Existing callbacks are generally session/payment-details oriented.
- Several legacy gateway methods perform network calls directly with curl and read keys from `payment_gateways.keys`.
- The legacy system does not contain an existing maintained Paymob-specific implementation. Repository Paymob references are limited to new YounGo files and language-key text.

Existing YounGo hardening around legacy paths:

- `Home` contains guards such as `youngo_remove_managed_access_courses_from_cart()`.
- Prior phases hardened free enrol, Buy Now, Add to cart, cart, coupon, and payment entry points so YounGo-managed courses are not routed into the inherited Academy payment flow as a shortcut.

## E. Reuse Map

`Youngo_checkout.php`

- Legacy equivalent: `Home::course_payment()`, legacy cart routes, and `Payment` entry point.
- Reuse: CodeIgniter controller/session/view patterns, `site_url()`, `get_phrase()`, existing course/user tables, and YounGo entitlement checks through the model/helper layer.
- Avoided: legacy cart session, `Payment_model::configure_course_payment()`, `Payment.php`, legacy gateway selection, and legacy `enrol`/`payment` writes.
- Reason: YounGo needs direct checkout orders, webhook source-of-truth, EGP-only Paymob flow, idempotency, and entitlement issuance separate from legacy Academy checkout.

`Youngo_checkout_model.php`

- Legacy equivalent: no formal Academy checkout order model; closest behavior is session `payment_details` plus `payment`/`enrol` writes.
- Reuse: `course`, `users`, `youngo_course_access`, CodeIgniter query builder, existing course pricing fields.
- Avoided: legacy `cart_items`, session-only order state, `payment` table as the primary order record.
- Reason: Paymob webhook reconciliation requires stable local order references and idempotency before payment completion.

`Youngo_payment_webhook.php`

- Legacy equivalent: `Payment.php` gateway callbacks/checks.
- Reuse: CodeIgniter controller/output response patterns.
- Avoided: user session requirement, browser payment session assumptions, legacy success redirect behavior.
- Reason: Paymob webhooks are server-to-server and must be verifiable without learner/admin session state.

`Youngo_paymob_webhook.php`

- Legacy equivalent: gateway-specific `check_*_payment()` methods in `Payment_model`.
- Reuse: none at the HMAC algorithm level; no legacy Paymob HMAC implementation exists.
- Avoided: generic legacy gateway check methods and session-driven verification.
- Reason: Paymob HMAC verification is provider-specific and must be deterministic/source-of-truth for transaction state.

`Youngo_payment_model.php`

- Legacy equivalent: `Crud_model::course_purchase()`, `Crud_model::enrol_student()`, and payment history reads.
- Reuse: YounGo entitlement write service, YounGo checkout tables, CodeIgniter DB patterns.
- Avoided: direct legacy `payment` and `enrol` writes.
- Reason: YounGo paid access must be idempotent, tied to verified webhook/transaction state, and not conflict with legacy enrolment assumptions.

`Youngo_entitlement_write_model.php`

- Legacy equivalent: `Crud_model::enrol_student()` and manual enrol helpers.
- Reuse: existing YounGo entitlement write boundary from Phase 2M.
- Avoided: duplicating access writes in payment model; legacy enrolment rows.
- Reason: entitlement issuance should stay centralized and auditable.

`youngo_checkout_cta_helper.php`

- Legacy equivalent: CTA conditionals in course detail/card/wishlist views and cart routes.
- Reuse: YounGo entitlement read helper/model and `site_url()`.
- Avoided: legacy Buy Now/Add to cart/payment URL generation.
- Reason: CTA exposure must remain gated and YounGo-specific.

`Youngo_payment_settings.php` and `youngo_payment_settings.php`

- Legacy equivalent: `Admin::payment_settings()` and `application/views/backend/admin/payment_settings.php`.
- Reuse: existing admin session convention, backend shell view `backend/index`, navigation placement, flash messages, basic CodeIgniter form rendering.
- Avoided: `payment_gateways` and generic `settings` permission as the sole gate.
- Reason: legacy page is a generic gateway editor that stores arbitrary gateway JSON and can expose/activate inherited gateways; YounGo needs Root-only, redacted, readiness-aware Paymob config.

`Youngo_payment_config_model.php`

- Legacy equivalent: `payment_gateways` table plus `Crud_model::update_payment_settings()`.
- Reuse: CodeIgniter model/query-builder convention.
- Avoided: legacy `payment_gateways`.
- Reason: dedicated provider/mode config, non-private-only saves, activation gates, readiness status, and private storage blocking are YounGo-specific requirements.

`Youngo_payment_config_audit_model.php`

- Legacy equivalent: no matching generic payment-config audit component was found in inspected payment/admin code.
- Reuse: CodeIgniter model/query-builder convention.
- Avoided: storing audit metadata in `payment_gateways`, `settings`, or raw logs.
- Reason: payment settings require redacted audit history and no secret persistence.

`Youngo_paymob_config.php` and config files

- Legacy equivalent: `payment_gateways.keys`, user `payment_keys`, and generic `settings`.
- Reuse: CodeIgniter config loading convention and `.gitignore`.
- Avoided: committing credentials or saving private values to DB while key management is unresolved.
- Reason: hybrid config keeps private values out of Git/reports/DB until encryption/key policy is approved.

`Youngo_paymob_adapter.php`

- Legacy equivalent: direct curl gateway integrations in `Payment.php` and `Payment_model.php`.
- Reuse: curl is used as the available local HTTP primitive.
- Avoided: legacy gateway model/key storage and old callback success flow.
- Reason: Paymob Intention/Unified Checkout needs provider-specific payloads, host restrictions, redaction, and explicit sandbox gates.

New tables:

- `youngo_checkout_orders`: closest legacy equivalent is session `payment_details` plus `payment`; intentionally separate.
- `youngo_payment_transactions`: closest legacy equivalent is `payment.transaction_id`; intentionally separate until reconciliation/compatibility is approved.
- `youngo_payment_provider_configs`: closest legacy equivalent is `payment_gateways`; intentionally separate.
- `youngo_payment_config_audit_logs`: no clear legacy equivalent found; intentionally new.

## F. Intentionally Avoided Legacy Pieces

The following legacy pieces should remain avoided for YounGo Paymob unless a later compatibility phase explicitly bridges them:

- `payment_gateways` as the YounGo Paymob config source.
- `Crud_model::update_payment_settings()` because it saves arbitrary posted gateway keys and activation status into legacy gateway rows.
- `Payment.php` as the Paymob webhook endpoint because it requires session payment details and is browser/payment-page oriented.
- `Payment_model::configure_course_payment()` because it builds session-only payment state from cart items, coupons, tax, gift handling, and legacy success URLs.
- Legacy cart routes for YounGo-managed paid/subscription courses.
- Legacy `success_course_payment()` because it directly writes `enrol` and `payment` after gateway checks.
- `Crud_model::enrol_student()` and `Crud_model::course_purchase()` for YounGo Paymob paid access.
- Legacy 100 percent coupon shortcut as access issuance.
- Existing active non-EGP legacy gateway rows for YounGo payment.
- User/instructor payout `payment_keys` UI as a Paymob merchant config surface.
- Return URL mutation as payment source-of-truth.

## G. Potential Duplication Findings

Acceptable isolation:

- Dedicated YounGo order, transaction, provider config, and config audit tables are appropriate. The legacy system lacks durable provider-agnostic checkout orders, webhook idempotency, redacted provider config audit, and YounGo entitlement linkage.
- Dedicated Paymob adapter/webhook libraries are appropriate because no maintained legacy Paymob code exists.
- Dedicated `/payment/paymob/webhook` is appropriate because public callbacks must not depend on a user session.

Should be adjusted before the next sandbox/browser phase:

- `youngo_checkout_cta_helper.php` still blocks any `network_enabled = true` state with `paymob_network_must_remain_disabled`. That was safe for local no-network CTA QA, but it conflicts with the newer sandbox Intention path where network is allowed only when `sandbox_network_testing_enabled = true`. Before CTA-based sandbox browser QA, the helper should allow a strictly sandbox-gated network state or separate "local no-network CTA" from "sandbox network CTA" states.
- Naming should be reconciled between `notification_url` in dashboard DB config and `webhook_url` in tracked/local config. The adapter maps dashboard `notification_url` into the Paymob payload, but mixed names will confuse setup and diagnostics.
- Dashboard readiness currently reports `ready_for_network = false` by design even when hybrid inputs are present, while the adapter has its own executable sandbox readiness. This should be made clearer as "configured but activation-disabled" versus "eligible to enable sandbox network after explicit flags".
- Public key handling is split: schema contains `public_key`, dashboard non-private save blocks `public_key`, and hybrid readiness expects public key presence from ignored server config. This is safe, but the dashboard should explicitly label public key as "server config only in current hybrid mode" to avoid confusion.

Refactor later:

- `Youngo_checkout::checkout_amount_for_course()` and `youngo_checkout_cta_course_amount()` both compute course amount from price/discount. This is acceptable now, but should become a shared pricing/order amount service before coupons, taxes, subscriptions, or production payments.
- Course purchase compatibility checks exist in both `Youngo_checkout_model::can_start_checkout()` and `youngo_checkout_cta_decision()`. This is acceptable for UI safety, but should eventually share one policy service to avoid route/helper drift.
- Admin/root access checks are implemented locally in `Youngo_payment_settings`. Later payment setup wizard work should reuse or formalize a `manage_payment_settings` capability instead of permanently hardcoding Root-only access.
- `Youngo_paymob_adapter` uses curl directly. That is acceptable for the first controlled sandbox call, but transaction inquiry/refunds/retries should probably introduce a small provider HTTP helper for consistent timeout/redaction/error handling.
- Diagnostics contain expected local DB injection patterns for CLI tests. This was swept in PAYMENT.DB.ACCESS.SWEEP.1 and is acceptable, but future HTTP wiring should continue to prefer normal CodeIgniter model loading.
- Existing dashboard form style and flash messaging can be reused more consistently in a future setup wizard.

No action needed:

- The new audit model is not an unnecessary duplicate of a detected generic audit module. No existing payment-config audit module was found in the inspected Academy payment code.
- Keeping legacy `payment`/`enrol` untouched is correct until a deliberate reporting/export compatibility layer is designed.

## H. Dashboard Setup Flow Status

Current status:

- YounGo has a Root-Admin-only Paymob settings/readiness/audit page at `/admin/youngo/payment-settings`.
- It can save only non-private sandbox fields:
  - mode
  - currency
  - amount multiplier
  - card integration ID
  - API base URL
  - checkout base URL
  - return URL
  - notification URL
- It rejects private fields and activation gates.
- It shows hybrid storage/readiness and redacted private presence.
- It shows recent redacted audit logs for non-private saves.

It is not yet a full client-facing Paymob setup wizard.

Missing for a proper setup wizard:

- Step-by-step provider setup state.
- Explicit sandbox/live profile separation UX.
- Clear owner checklist for dashboard non-private fields versus ignored/server private fields.
- Masked secret-entry UX, if encrypted DB storage is later approved.
- Or, if hybrid-only remains the decision, a "server config required" checklist without secret inputs.
- Setup preflight that reconciles dashboard DB config plus ignored server config presence.
- Safe "test readiness" action that does not create a Paymob Intention.
- Later "sandbox create test checkout" action with explicit one-time confirmation.
- Webhook/tunnel instructions and public callback URL verification.
- Audit display with filter/pagination if this becomes an operational page.
- Separate activation/CTA approvals after sandbox QA, not in the same save button.

Existing dashboard modules to reuse for the wizard:

- `backend/index` page shell.
- `application/views/backend/admin/navigation.php` YounGo group placement.
- Existing admin session checks via `user_model->check_session_data('admin')`.
- Existing flash message conventions.
- Existing form layout conventions from `payment_settings.php` and other admin settings pages.
- Future capability patterns from `youngo_capability_helper.php`, once payment-setting delegation is approved.

## I. Security/Key-Management Status

Current key state:

```text
application/config/config.php
$config['encryption_key'] = '';
```

Current private storage policy:

- DB-backed private Paymob value storage is blocked.
- `Youngo_payment_config_model` rejects private fields such as `secret_key`, `hmac_secret`, `api_key`, `client_secret`, `authorization`, and `auth_header`.
- Private values are expected only in ignored local/server config.
- `.gitignore` contains `application/config/youngo_paymob.local.php`.
- The tracked example contains only null placeholders.

Redaction status:

- Config summaries and dashboard readiness show private presence only.
- Audit logs store changed non-private fields and secret-presence transitions only.
- Raw secret-like fields are rejected by audit model guards.
- Sandbox Intention adapter redacts authorization/client-secret details in returned summaries and errors.

Future encryption reuse:

- CodeIgniter encryption library may be reused later only after a real `encryption_key` is generated and managed outside Git/reports.
- Before encrypted DB storage is implemented, the project needs a decision on key generation, cPanel deployment storage, backup/export implications, key rotation, and disaster recovery.

## J. DB/Schema Impact

New/additive YounGo payment schema from PAYMENT.SCHEMA.1:

- `youngo_checkout_orders`
  - EGP defaults.
  - order reference/idempotency fields.
  - provider intent/order/transaction references.
  - payment gateway/environment/status fields.
  - HMAC and entitlement issuance tracking.
  - safe timestamps and metadata.
- `youngo_payment_transactions`
  - gateway provider/environment/status fields.
  - Paymob provider references.
  - amount/currency EGP fields.
  - HMAC received/verified fields.
  - idempotency key and duplicate-event unique constraints.
  - redacted raw payload storage.

Existing YounGo entitlement/coupon table indexes added for checkout linkage:

- `youngo_course_access` checkout/payment indexes and unique checkout-order linkage.
- `youngo_user_subscriptions` checkout/payment indexes.
- `youngo_coupon_usages` checkout-order indexes.

New Paymob dashboard config schema:

- `youngo_payment_provider_configs`
  - provider/mode/currency/amount multiplier.
  - behavior gates default disabled.
  - non-private Paymob URLs and card integration ID.
  - private presence/status fields only.
  - readiness/audit metadata.
  - unique provider/mode index.

New Paymob config audit schema:

- `youngo_payment_config_audit_logs`
  - provider/mode/action.
  - actor id/type/role metadata.
  - changed fields JSON.
  - redacted before/after summaries.
  - secret presence changes JSON.
  - optional redacted request metadata.

Legacy table impact:

- `payment_gateways` was not modified by this audit and should not be used for YounGo Paymob config.
- `payment` was not modified and is not written by YounGo Paymob code.
- `enrol` was not modified and is not written by YounGo Paymob code.
- `settings` was not modified for Paymob.
- Existing Academy gateway views and gateway rows remain in place for legacy compatibility.

Reported local protected counts from prior payment phases repeatedly restored to baseline after diagnostics:

- `youngo_checkout_orders`: no persistent QA rows.
- `youngo_payment_transactions`: no persistent QA rows.
- `youngo_course_access`: no persistent payment QA rows.
- `payment`: unchanged.
- `enrol`: unchanged at the local baseline count reported by prior QA.
- `payment_gateways`: unchanged at the previously reported count.

## K. Recommendations

Keep as-is:

- Keep YounGo Paymob config out of `payment_gateways`.
- Keep private values out of DB while `encryption_key` is empty.
- Keep ignored local/server config for private sandbox values in the current hybrid approach.
- Keep the dedicated sessionless Paymob webhook controller.
- Keep return URL UX-only.
- Keep YounGo order/transaction models separate from legacy `payment` and `enrol`.
- Keep entitlement issuance through `Youngo_entitlement_write_model`.
- Keep public/default checkout CTA and payment activation disabled.

Adjust before continuing sandbox browser QA:

- Update the CTA decision policy so the course-detail CTA can support explicit local sandbox network testing when all sandbox gates pass, instead of blocking any `network_enabled` state.
- Reconcile dashboard/readiness naming for `notification_url` versus `webhook_url`.
- Clarify dashboard readiness states so "configured but disabled", "eligible for sandbox network if explicit flags are on", and "currently executable" are visibly distinct.
- Label `public_key` consistently as server-config-only under the current hybrid strategy, or deliberately move it into non-private DB config in a later approved phase.

Build next:

- A Paymob setup/preflight wizard or checklist phase should come before rerunning real sandbox browser QA.
- The wizard should reuse current `Youngo_payment_settings`, `Youngo_payment_config_model`, `Youngo_payment_config_audit_model`, admin shell/navigation, flash messages, and readiness helpers.
- The wizard should not save private values unless a separate encryption/key-management phase approves encrypted DB storage.

Refactor later:

- Centralize course purchase eligibility and amount calculation for CTA, order creation, and future Paymob payloads.
- Decide how taxes, coupons, subscription purchase, and legacy reporting compatibility will map into YounGo orders.
- Consider a small Paymob HTTP helper once additional Paymob APIs such as transaction inquiry, refund, or capture are introduced.
- Introduce a delegated `manage_payment_settings` capability only after owner approval and QA.

Do not continue directly to live/prod:

- Production CTAs and payment enablement remain blocked.
- Existing Academy gateway rows remain unsuitable as a YounGo Paymob shortcut.
- No production Paymob path should be attempted until sandbox Intention, webhook, HMAC, duplicate callback, paid entitlement issuance, and rollback are browser/server QA-tested.

## L. Recommended Next Phase

Recommended next phase:

```text
PAYMENT.PAYMOB.CONFIG.SETUP.PREFLIGHT.1 - Hybrid Paymob Sandbox Setup Checklist and CTA/Readiness Alignment
```

Acceptance boundary:

- No real private values in Git/reports.
- No DB private storage.
- No production defaults changed.
- No Paymob calls.
- No payment activation.
- No public production CTA exposure.
- CTA helper and dashboard readiness align with the sandbox Intention gates.
- Missing field names are reported safely without values.

After that, rerun:

```text
PAYMENT.PAYMOB.SANDBOX.INTENTION.UI.QA.1
```

only when:

- non-private dashboard sandbox config exists;
- ignored local/server private config exists;
- explicit local sandbox flags are enabled only outside Git;
- public webhook/tunnel strategy is known for later webhook QA.

## M. Git Status

Expected final Git status after creating this audit report:

```text
?? docs/qa/youngo_payment_reconcile_audit_1_report.md
```

No source files or database state were changed in this audit.
