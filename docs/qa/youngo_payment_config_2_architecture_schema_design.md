# YounGo Payment Config 2 Architecture And Schema Design

Phase: PAYMENT.CONFIG.2 - YounGo Payment Architecture and Schema Design
Date: 2026-07-21
Scope: design and planning only. No deployment, push, commit, live cPanel access, real payment activation, real credentials, DB changes, SQL execution, Root Admin changes, public checkout CTA exposure, or Paymob API implementation.

## A. Current Branch/Status

Start command results:

```text
git branch --show-current
analysis/cms-audit

git status --short
<clean>

git log --oneline -10
dd601bd Plan YounGo Paymob sandbox integration
dda25bc Harden YounGo legacy payment entry points
a2e1f81 Document YounGo payment DB baseline
727ef72 Audit YounGo payment flow with local DB
e5f6a5f Document live cPanel client demo handoff
0216016 Add YounGo cPanel deployment runbook
9cd4b3d Prepare YounGo sanitized client admin export
49aa360 Test YounGo cPanel package restore locally
2337d79 Prepare YounGo cPanel client package
8357c24 Reconcile YounGo client upload plan
```

The expected branch and clean starting worktree were confirmed. The latest commit includes the PAYMENT.CONFIG.1 Paymob sandbox planning report.

## B. Files Inspected

Required phase inputs:

- `docs/qa/youngo_payment_flow_audit_and_test_plan.md`
- `docs/qa/youngo_payment_db_1_baseline_report.md`
- `docs/qa/youngo_payment_fix_1_legacy_cleanup_report.md`
- `docs/qa/youngo_payment_config_1_paymob_research_and_sandbox_plan.md`

Project guardrails:

- `YOUNGO_PROJECT_CONTEXT.md`
- `docs/design/youngo_style_direction.md`
- `docs/planning/README.md`
- `docs/planning/youngo_master_plan_v2.md`
- `docs/planning/youngo_phase_2_hybrid_access_subscriptions_roles_architecture.md`
- `docs/agents/implementation_rules.md`
- `docs/reference/README.md`

Local architecture/code inspected:

- `application/controllers/Home.php`
- `application/controllers/Payment.php`
- `application/controllers/Youngo_manual_grants.php`
- `application/config/routes.php`
- `application/models/Payment_model.php`
- `application/models/Crud_model.php`
- `application/models/Youngo_entitlement_model.php`
- `application/models/Youngo_entitlement_write_model.php`
- `application/models/Youngo_subscription_model.php`
- `application/helpers/youngo_entitlement_helper.php`
- `application/helpers/youngo_capability_helper.php`
- `application/views/payment-global/payment_gateway.php`
- YounGo learner/account/payment-related views under `application/views/frontend/youngo/`
- YounGo admin entitlement/manual grant views under `application/views/backend/admin/`
- `database/phase_2/youngo_phase_2e_schema_up.sql`
- `database/phase_2/youngo_phase_2m_entitlement_write_schema_up.sql`
- `uploads/install.sql` for legacy `payment` and `payment_gateways` table shape only
- `scripts/phase_2/payment_db_1_gateway_hygiene_proposed.sql`

## C. Existing Entitlement/Access Architecture Summary

Read layer:

- `application/helpers/youngo_entitlement_helper.php` exposes `youngo_get_course_access_state()` and `youngo_can_access_course()`.
- The access decision checks admin/root bypass, assigned instructor access, legacy enrolment, direct YounGo course access, and active subscription access.
- `application/models/Youngo_entitlement_model.php` is the central read model for legacy enrolment, YounGo course access, YounGo subscriptions, subscription eligibility, learner My Courses items, My Access summaries, and read-only admin entitlement summaries.
- My Courses intentionally lists active legacy enrolments and active direct YounGo/manual course access. It does not list every subscription-eligible course just because a subscription is active.
- My Access is visibility-only. It does not expose checkout, renewal, payment, coupon, Subscribe, Pay, or Renew actions.

Write layer:

- `application/models/Youngo_entitlement_write_model.php` is the required YounGo entitlement write boundary.
- It supports manual course grants, manual subscription grants, revocation, duplicate active entitlement prevention, transactional writes, and schema-readiness checks.
- It accepts only manual sources for current grant methods.
- `issue_course_purchase_access($checkout_order_id)` and `issue_subscription_purchase($checkout_order_id)` are intentionally non-writing stubs returning `checkout_issuance_not_implemented`.

Manual grants:

- `application/controllers/Youngo_manual_grants.php` enforces admin session plus `grant_manual_access`.
- Manual grant writes go through `Youngo_entitlement_write_model`.
- Revocation is POST-only and there is no delete path.
- Manual grants are auditable and separate from payment records.

Learner access enforcement:

- `Home::lesson()`, `Home::pdf_canvas()`, `Home::play_lesson()`, `Home::go_course_playing_page()`, mobile lesson helpers, course listing/detail CTAs, course review gates, My Courses, and My Access use or depend on the YounGo entitlement read layer where scoped.
- YounGo-managed no-access courses are blocked from legacy free enrol, Add to cart, Buy Now, course payment, and 100 percent coupon direct-enrol shortcuts.

Checkout/order schema:

- `youngo_checkout_orders` exists from Phase 2E and is currently empty in the local baseline.
- It has order/totals/provider fields, but old defaults include `currency DEFAULT 'USD'`.
- It does not currently include an order reference, gateway environment, Paymob order ID, transaction event table, HMAC state, idempotency key, or entitlement issuance status fields.
- `youngo_payment_transactions` does not exist.

Legacy compatibility:

- `Payment_model::configure_course_payment()` builds session-based legacy `payment_details`.
- `Payment::success_course_payment()` verifies a legacy gateway through `youngo_gateway_payment_check()`, then calls `Crud_model::enrol_student()` and `Crud_model::course_purchase()`.
- `Crud_model::enrol_student()` writes legacy `enrol` rows from session cart items.
- `Crud_model::course_purchase()` writes legacy `payment` rows from session cart items.
- The legacy `payment` table is useful for invoice/revenue compatibility, but it is not a safe YounGo order/access issuance system by itself.

## D. Proposed Payment Architecture

The YounGo payment architecture should be separate from inherited Academy gateway rows and should use CodeIgniter-style controllers/models.

Proposed components:

1. `Youngo_checkout` controller
   - Public learner checkout controller.
   - Requires learner login for checkout start/pay routes.
   - Validates course or plan eligibility.
   - Creates or loads YounGo checkout orders.
   - Redirects to Paymob Unified Checkout through a Paymob adapter later.
   - Handles learner return as UX-only.
   - Must not call legacy `Payment::success_course_payment()`.

2. `Youngo_checkout_model`
   - Owns order creation, pricing snapshots, order reference generation, status transitions, expiry checks, and order lookup.
   - Validates `EGP`, course access mode, plan purchasability, existing active access/subscription state, and duplicate pending orders.
   - Stores canonical order/totals data in `youngo_checkout_orders`.

3. `Youngo_payment_model`
   - Records return/webhook/inquiry events in `youngo_payment_transactions`.
   - Applies idempotency rules.
   - Matches Paymob events to local orders by order reference, Paymob order ID, transaction ID, and provider intent ID.
   - Updates order payment status only after trusted verification.

4. `Youngo_paymob_service` or `Youngo_paymob_model`
   - Paymob-specific adapter.
   - Reads non-secret config through a config reader.
   - Creates Paymob Intention requests later.
   - Builds Unified Checkout redirect URL later.
   - Verifies Paymob HMAC.
   - Performs transaction inquiry when required.
   - Must not store or print secrets.

5. `Youngo_payment_config_model` or helper
   - Reads local sandbox settings from environment variables or a non-committed local config override.
   - Exposes safe readiness state to code.
   - Returns masked values for future admin diagnostics only.
   - Keeps sandbox and live values separate.

6. `Youngo_entitlement_write_model` checkout issuance methods
   - Future implementation target for `issue_course_purchase_access()` and `issue_subscription_purchase()`.
   - Called only after verified payment or approved zero-total checkout.
   - Writes YounGo access/subscription records idempotently.

7. Future admin/payment review page
   - Read-only at first.
   - Route under `/admin/youngo/payments`.
   - Requires a future `manage_payments` capability or Root/core-admin-only gate.
   - Shows orders, transaction status, HMAC verification state, entitlement issuance state, and safe redacted payload metadata.
   - Does not expose raw secrets or let ordinary admins activate live payments.

Architecture rule:

- Paymob webhook handling must not be implemented inside the current `Payment` controller unless that controller is refactored, because its constructor requires learner session `payment_details` and would reject server-to-server callbacks. A route may use `/payment/paymob/webhook`, but it should map to a new webhook-safe controller.

## E. Proposed Route Map

Future routes only. Do not add them in this phase.

Learner checkout:

```php
$route['home/youngo_checkout/start/(:num)'] = 'youngo_checkout/start_course/$1';
$route['home/youngo_checkout/plan/(:num)'] = 'youngo_checkout/start_subscription/$1';
$route['home/youngo_checkout/pay/(:num)'] = 'youngo_checkout/pay/$1';
$route['home/youngo_checkout/return/(:any)'] = 'youngo_checkout/return/$1';
$route['home/youngo_checkout/status/(:any)'] = 'youngo_checkout/status/$1';
```

Paymob callbacks:

```php
$route['payment/paymob/webhook'] = 'youngo_paymob/webhook';
$route['payment/paymob/inquiry/(:any)'] = 'youngo_paymob/inquiry/$1';
```

Admin review:

```php
$route['admin/youngo/payments'] = 'youngo_payments_admin/index';
$route['admin/youngo/payments/(:num)'] = 'youngo_payments_admin/view/$1';
$route['admin/youngo/payments/(:num)/reconcile'] = 'youngo_payments_admin/reconcile/$1';
```

Route rules:

- No routes are added now.
- Do not add `/ar` payment/checkout aliases yet. Current localization guardrails intentionally exclude checkout/payment/cart/coupon/write routes.
- The webhook route must be public enough for Paymob to POST to it, but must trust only HMAC and order/status reconciliation, not session state.
- Return/status routes may require the learner session and must be UX-only.
- Admin routes must use future payment capability gating and should be read-only until implementation is QA-approved.

## F. Order/Transaction Status Design

Order statuses in `youngo_checkout_orders.status`:

| Status | Meaning |
|---|---|
| `draft` | Local checkout intent exists, but Paymob has not been contacted. Safe to abandon or replace. |
| `pending_gateway` | Local order is validated and ready to start gateway payment, or Paymob intention creation is in progress. No payment confirmation yet. |
| `awaiting_webhook` | Learner has been sent to Paymob or returned from Paymob, but YounGo is waiting for verified webhook or inquiry confirmation. |
| `paid` | Payment success has been verified by HMAC webhook and/or trusted transaction inquiry. Entitlement issuance is tracked separately. |
| `failed` | Payment was verified as declined/failed, or a trusted verification showed non-success. |
| `cancelled` | Learner or admin cancelled the checkout before verified payment. No access should be issued. |
| `expired` | Local order or Paymob intention expired before verified payment. No access should be issued. |

Compatibility note:

- Older diagnostics/planning references sometimes use `completed` for checkout orders. Future implementation should update those checks or treat `completed` as a legacy alias during migration. New YounGo payment design should use `paid` for verified payment and separate entitlement issuance fields for access creation.

Separate entitlement issuance statuses:

| Status | Meaning |
|---|---|
| `not_started` | No access issuance attempted. |
| `in_progress` | A verified paid/zero-total order is being issued. |
| `issued` | YounGo access/subscription was created and linked to the order. |
| `skipped_existing_access` | Active access already existed, so duplicate issuance was safely skipped. |
| `failed` | Payment is verified but access issuance failed and requires retry/admin review. |

Transaction statuses in `youngo_payment_transactions.status`:

| Status | Meaning |
|---|---|
| `received` | Return/webhook/inquiry event was captured but not yet trusted. |
| `verified` | HMAC and required references passed validation. |
| `rejected` | HMAC, amount, currency, integration ID, order reference, or expected state failed validation. |
| `duplicate` | Same provider event/idempotency key was already processed. |
| `reconciled` | Transaction was verified or corrected through Paymob transaction inquiry. |
| `error` | Internal processing failed before a reliable final decision. |

## G. Idempotency Rules

Order identity:

- Every order gets a unique `order_reference` that is sent to Paymob as YounGo's merchant reference/special reference.
- Every order gets a unique `idempotency_key`.
- A learner should not create another active pending order for the same course/plan while an existing non-expired order is `draft`, `pending_gateway`, or `awaiting_webhook`.

Duplicate webhook handling:

- Record every callback attempt as a transaction event when possible.
- Build the transaction idempotency key from gateway provider, gateway environment, event type, Paymob transaction ID, Paymob order ID, and YounGo order reference.
- If the same successful callback arrives again after the order is already `paid`, mark the new event `duplicate`, return a safe 200 response to Paymob, and do not issue access again.
- If the same Paymob transaction ID appears with conflicting amount/currency/order reference/status, record it as `rejected` or `error` and require admin review.

Duplicate return handling:

- Return URL can be refreshed or replayed.
- Return events may be recorded for audit, but must not issue access.
- If the order is `paid` and entitlement is issued, show success/access state.
- If the order is still `awaiting_webhook`, show pending state.
- If failed/cancelled/expired, show the corresponding safe state.

Already-paid order handling:

- Once an order is `paid`, do not create a new Paymob intention for it.
- If the learner reaches pay route again for a paid order, redirect to status/return view.
- If entitlement issuance failed, allow an admin-only retry of issuance, not a second charge.

Already-issued entitlement handling:

- `youngo_checkout_orders.entitlement_issued = 1` and `entitlement_issuance_status = 'issued'` are the primary order-level guard.
- `youngo_course_access.checkout_order_id` and `youngo_user_subscriptions.checkout_order_id` should be unique for future checkout-issued rows.
- The write service must also check active duplicate access/subscription state before inserting.

Provider references:

- Store Paymob intention ID in existing `provider_intent_id`.
- Store Paymob order ID in proposed `provider_order_id`.
- Store Paymob transaction ID in `provider_transaction_id`.
- Store integration ID on transaction events for validation/audit.

Safe retries:

- Retrying Paymob intention creation is allowed only for `draft`, `pending_gateway`, or expired/failed orders according to explicit future rules.
- Retrying entitlement issuance is allowed only after verified `paid` status and must be idempotent.
- Retrying webhook processing must never duplicate `payment`, `enrol`, `youngo_course_access`, `youngo_user_subscriptions`, or coupon usage rows.

## H. Proposed Schema SQL File

Created proposed SQL only:

```text
scripts/phase_2/payment_config_2_youngo_payment_schema_proposed.sql
```

The filename follows the requested path exactly. The SQL is clearly marked `NOT EXECUTED`.

The proposal includes:

- `CREATE TABLE IF NOT EXISTS youngo_checkout_orders` shape for future environments where Phase 2E is absent.
- Additive `ALTER TABLE youngo_checkout_orders` columns for order reference, EGP cents, gateway environment, Paymob order ID, idempotency key, HMAC state, payment timestamps, failure details, and entitlement issuance tracking.
- Proposed `currency DEFAULT 'EGP'` and `status DEFAULT 'draft'` changes for a future approved migration.
- New `youngo_payment_transactions` table for return/webhook/inquiry events, HMAC verification, payload hash, redacted payload storage, idempotency, gateway references, and status tracking.
- Proposed unique/index constraints for order reference, idempotency keys, provider transaction/event uniqueness, checkout-order access issuance uniqueness, and coupon usage per checkout order.
- Review queries for a future approved apply phase.

Nothing in the SQL was executed.

## I. Non-Secret Config Strategy

Recommended local config pattern:

- Add a future committed example file with placeholders only, such as `application/config/youngo_payment.example.php`.
- Use environment variables or a non-committed local override file for actual sandbox values, such as `application/config/youngo_payment.local.php`.
- Load local override conditionally only if the file exists.
- Ensure the local override is ignored by Git before any implementation.

Recommended placeholder keys:

```text
PAYMOB_ENV=sandbox
PAYMOB_REGION=EG
PAYMOB_BASE_URL=https://accept.paymob.com
PAYMOB_SECRET_KEY=<local-only-secret>
PAYMOB_PUBLIC_KEY=<local-only-public-key>
PAYMOB_HMAC_SECRET=<local-only-hmac-secret>
PAYMOB_INTEGRATION_ID_CARD=<local-only-card-integration-id>
PAYMOB_NOTIFICATION_URL=<public-https-tunnel>/payment/paymob/webhook
PAYMOB_REDIRECTION_URL=<local-base-url>/home/youngo_checkout/return/<order_reference>
PAYMOB_CURRENCY=EGP
PAYMOB_TEST_MODE=1
```

Deferred or conditional keys:

```text
PAYMOB_API_KEY=<only-if-transaction-inquiry-requires-auth-token>
PAYMOB_INTEGRATION_ID_WALLET=<only-if-wallets-are-approved>
PAYMOB_MERCHANT_ID=<only-if-current-Paymob-flow-requires-it>
PAYMOB_IFRAME_ID=<legacy-deferred-only>
```

Security rules:

- Do not store real secrets in Git, reports, screenshots, committed SQL, committed config, or legacy `payment_gateways.keys`.
- Keep sandbox and live credentials physically separate.
- Do not allow live mode unless a future implementation and QA phase explicitly approves it.
- Public key can be exposed to the browser only where Paymob docs require it; Secret Key, API Key, HMAC secret, and any merchant secrets are server-only.
- A future admin page should show masked readiness only, not raw values.
- Payment config should be Root/core-admin-only at first. A future `manage_payments` capability can be seeded once the admin review page is approved.

## J. Entitlement Issuance Integration Design

Successful verified payment should integrate through `Youngo_entitlement_write_model`, not direct controller inserts.

Course purchase issuance:

1. Payment model marks order `paid` only after trusted verification.
2. Payment model calls `Youngo_entitlement_write_model::issue_course_purchase_access($checkout_order_id, $actor_context)`.
3. The write service reloads the order by ID inside a transaction.
4. The write service validates:
   - order exists;
   - `order_type = course_purchase`;
   - `status = paid`;
   - `currency = EGP`;
   - `total_amount` and cents match the verified transaction;
   - course exists and remains purchasable;
   - entitlement has not already been issued;
   - no duplicate active YounGo course access exists for the same user/course unless the design explicitly allows extension/renewal later.
5. The write service inserts `youngo_course_access` with `access_source = course_purchase`, links `checkout_order_id`, sets `status = active`, and uses lifetime/timed access based on course purchase rules.
6. It updates order entitlement fields to `entitlement_issued = 1`, `entitlement_issuance_status = issued`, and stores the access row ID.

Subscription issuance:

1. Payment model marks order `paid` only after trusted verification.
2. Payment model calls `Youngo_entitlement_write_model::issue_subscription_purchase($checkout_order_id, $actor_context)`.
3. The write service validates:
   - order exists;
   - `order_type = subscription_purchase`;
   - `status = paid`;
   - `currency = EGP`;
   - plan exists, is EGP, is active/purchasable, and is not archived;
   - user has no active subscription if current business rule still forbids plan switching while active;
   - entitlement has not already been issued.
4. It inserts `youngo_user_subscriptions` with `source = checkout`, `status = active`, plan duration, `price_paid`, `currency = EGP`, `checkout_order_id`, and optional `payment_id`.
5. It updates order entitlement fields to issued and stores the subscription row ID.

Legacy enrol/payment compatibility:

- Do not call `Crud_model::enrol_student()` from the Paymob success path by default.
- Do not route YounGo-managed purchases through `Payment::success_course_payment()`.
- If a legacy enrol row is later required for a specific Academy page or mobile path, add a dedicated compatibility method after YounGo entitlement issuance succeeds.
- Any legacy sync must be idempotent, linked to the checkout order where possible, and must not be the source of truth for YounGo access.
- Legacy `payment` row creation may be considered later for invoice/revenue compatibility, but `youngo_checkout_orders` and `youngo_payment_transactions` should remain the payment source of truth.

Failure after payment success:

- If entitlement issuance fails after verified payment, keep order `paid`.
- Set `entitlement_issuance_status = failed` and store a safe error code/message.
- Do not attempt another charge.
- Provide admin-only review/retry later.
- Retry must re-run the same idempotency checks and must not duplicate access/subscription rows.

## K. Risks/Blockers

- This phase is planning-only; no runtime or DB apply was performed.
- Owner-provided Paymob sandbox dashboard details and credentials are still required before implementation.
- Local Paymob webhook testing needs a public HTTPS tunnel.
- Existing `youngo_checkout_orders` has USD-era defaults and needs an approved migration before use.
- `youngo_payment_transactions` does not exist yet.
- Checkout issuance methods are stubs and must be implemented before payment success can grant access.
- Existing Phase 2M diagnostics currently refer to `status = completed`; future diagnostics must be updated or compatibility-mapped to the new `paid` status.
- Legacy invoice/revenue/admin purchase views still depend on the legacy `payment` table; compatibility strategy must be tested before relying only on YounGo payment tables.
- The inherited `payment_gateways` rows remain active/non-EGP in DB but are source-hardened and should not be used for YounGo payment.
- Public checkout CTAs must stay checkout-not-ready until order, Paymob, webhook, idempotency, and entitlement issuance are implemented and QA-tested.
- Do not create real or sandbox config values in committed files.

## L. Recommended Next Phase

Recommended next phase:

```text
PAYMENT.CONFIG.3 - YounGo Payment Config and Schema Implementation Plan
```

Suggested scope:

1. Owner reviews this architecture and proposed SQL.
2. Decide whether to keep the requested SQL filename or rename it in a future cleanup phase.
3. Approve or revise order status names, especially `paid` versus older `completed` diagnostic wording.
4. Approve non-secret config file strategy and Git ignore handling.
5. Prepare diagnostics for schema readiness and config readiness only.
6. Do not execute SQL or implement Paymob API calls until the implementation phase is explicitly approved.

Later implementation phases should proceed in this order:

1. Add config/readiness diagnostics without secrets.
2. Apply reviewed local schema migration after backup and owner approval.
3. Implement local order lifecycle without Paymob calls.
4. Implement Paymob sandbox initiation and webhook verification.
5. Implement entitlement issuance and QA.
6. Restore public checkout CTAs only after controlled sandbox QA passes.

## M. Git Status

Initial status before this phase:

```text
git status --short
<clean>
```

Expected status after this report and proposed SQL are created:

```text
?? docs/qa/youngo_payment_config_2_architecture_schema_design.md
?? scripts/phase_2/payment_config_2_youngo_payment_schema_proposed.sql
```

No code files, live server files, database rows, credentials, Root Admin records, route files, or payment gateway settings were changed.
