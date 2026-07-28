# PAYMENT.CHECKOUT.PAYMOB.SANDBOX.PLAN.1 - Paymob Sandbox Network Execution Plan and Safety Gates

Date: 2026-07-22

Scope: planning only for the first real Paymob sandbox network execution phase. No deployment, push, DB modification, SQL execution, real credentials, secret output, Root Admin modification, real payment enablement, Paymob network request, real Paymob intention, production default change, public CTA expansion, or legacy payment/enrol write was performed.

## A. Current Branch/Status

Start command results:

```text
git branch --show-current
analysis/cms-audit

git status --short
<clean>

git log --oneline -10
0d663c1 QA gated YounGo checkout CTA clickthrough
68b9b28 Add gated YounGo checkout CTA helper
bda9cb1 Plan YounGo checkout CTA exposure
b59eaee QA local YounGo checkout smoke flow
bcb01b7 Fix YounGo checkout HTTP DB access
92fd18e QA authenticated YounGo checkout start blocker
cf98079 QA disabled YounGo checkout route safety
c26a933 Add controlled local YounGo checkout flow
a943b64 Add disabled YounGo checkout route skeleton
5ea5f16 Plan YounGo checkout route boundaries
```

The expected branch and clean starting worktree were confirmed. The latest commit includes PAYMENT.CHECKOUT.CTA.LOCAL.UI.QA.1.

## B. Files Inspected

Required reports read:

- `docs/qa/youngo_payment_config_1_paymob_research_and_sandbox_plan.md`
- `docs/qa/youngo_payment_config_2_architecture_schema_design.md`
- `docs/qa/youngo_payment_config_file_1_report.md`
- `docs/qa/youngo_payment_paymob_adapter_1_report.md`
- `docs/qa/youngo_payment_webhook_1_report.md`
- `docs/qa/youngo_payment_webhook_route_1_report.md`
- `docs/qa/youngo_payment_transaction_block_1_report.md`
- `docs/qa/youngo_payment_order_status_1_report.md`
- `docs/qa/youngo_payment_entitlement_block_1_report.md`
- `docs/qa/youngo_payment_checkout_cta_local_ui_qa_1_report.md`

Project guardrails read:

- `YOUNGO_PROJECT_CONTEXT.md`
- `docs/design/youngo_style_direction.md`
- `docs/planning/youngo_master_plan_v2.md`
- `docs/planning/youngo_phase_2_hybrid_access_subscriptions_roles_architecture.md`
- `docs/agents/implementation_rules.md`
- `docs/reference/README.md`

Source inspected:

- `application/config/youngo_paymob.php`
- `application/config/youngo_paymob.local.example.php`
- `application/libraries/Youngo_paymob_config.php`
- `application/libraries/Youngo_paymob_adapter.php`
- `application/libraries/Youngo_paymob_webhook.php`
- `application/controllers/Youngo_checkout.php`
- `application/controllers/Youngo_payment_webhook.php`
- `application/models/Youngo_checkout_model.php`
- `application/models/Youngo_payment_model.php`
- `application/models/Youngo_entitlement_write_model.php`
- `application/config/routes.php`
- `application/helpers/youngo_checkout_cta_helper.php`
- `application/views/frontend/youngo/course_page.php`

Current Paymob documentation was also spot-checked without calling Paymob APIs:

- Paymob API integration path: `https://developers.paymob.com/paymob-docs/integration-paths/apis`
- Paymob checkout experiences overview: `https://developers.paymob.com/paymob-docs/developers/checkout-experiences`
- Paymob webhook/callbacks and HMAC overview: `https://developers.paymob.com/paymob-docs/developers/webhook-callbacks-and-hmac`
- Paymob webhook testing tool: `https://developers.paymob.com/paymob-docs/developers/webhook-callbacks-and-hmac/webhook-testing-tool`
- Paymob official `PaymobAccept/paymob-js` repository: `https://github.com/PaymobAccept/paymob-js`

The current public docs still point to backend payment intention creation, Unified Checkout redirection, and webhook/HMAC callbacks as the relevant flow. Search snippets were crawled today, 2026-07-22. The detailed CONFIG.1 report remains the main in-repo source for the fuller Paymob documentation review.

## C. Current Foundation Status

Current local YounGo payment foundation:

- Course-detail CTA decision helper exists and is default-hidden.
- Course-detail CTA can appear only with ignored local flags enabled.
- CTA target is only `/youngo/checkout/start/{course_id}`.
- Homepage, course listing, and wishlist do not expose checkout-start CTAs.
- `Youngo_checkout` routes exist for start, order, status, and return.
- Checkout routes are disabled by default.
- Current checkout controller explicitly rejects `network_enabled = true`; this is correct for the completed local-only phases.
- Local checkout order creation and open-order reuse are implemented in `Youngo_checkout_model`.
- Orders are EGP-only and validate learner, course, course mode, positive amount, and existing active access.
- `Youngo_payment_model` records redacted local transaction summaries and enforces duplicate provider event checks.
- Fixture webhook processing can record verified/rejected transactions through `Youngo_paymob_fixture_processor`.
- Verified fixture transactions can mark local diagnostic orders `paid`.
- Rejected fixture transactions can mark local diagnostic orders `failed`.
- Paid course-purchase orders can issue YounGo course access through `Youngo_payment_model::issue_paid_order_entitlement()` and `Youngo_entitlement_write_model::issue_course_purchase_access()`.
- Entitlement issuance is idempotent and blocks duplicate access.
- Public webhook route exists at `/payment/paymob/webhook` and remains fail-closed by default.
- Public webhook controller is POST-only, session-independent, and currently validates only when `webhook_testing_enabled` is true.
- Paymob adapter exists but has no network code. `create_intention_disabled()` always fails closed.
- No `curl`, remote `file_get_contents`, Guzzle, or Composer dependency exists in the Paymob adapter.
- Legacy `Payment.php`, legacy gateways, legacy `payment`, and legacy `enrol` are not used by the YounGo checkout path.

Current local QA state from the latest CTA QA:

- Protected counts returned to baseline after cleanup:
  - `youngo_checkout_orders = 0`
  - `youngo_payment_transactions = 0`
  - `youngo_course_access = 0`
  - `payment = 0`
  - `enrol = 1`

## D. Required Sandbox Credential Fields

Owner must provide sandbox values through an ignored local config or another approved secret channel. Do not place values in Git, reports, screenshots, chat, SQL, logs, or DB dumps.

Required first-phase fields:

```text
PAYMOB_SECRET_KEY
PAYMOB_PUBLIC_KEY
PAYMOB_HMAC_SECRET
PAYMOB_INTEGRATION_ID_CARD_EGP
PAYMOB_RETURN_URL
PAYMOB_WEBHOOK_URL
PAYMOB_BASE_URL
```

Required operational config:

```text
PAYMOB_MODE=sandbox
PAYMOB_CURRENCY=EGP
PAYMOB_AMOUNT_MULTIPLIER=100
PAYMOB_PROVIDER=paymob
```

Conditional or deferred fields:

```text
PAYMOB_API_KEY
PAYMOB_MERCHANT_ID
PAYMOB_IFRAME_ID
PAYMOB_INTEGRATION_ID_WALLET_EGP
PAYMOB_TUNNEL_PUBLIC_BASE_URL
PAYMOB_TRANSACTION_INQUIRY_ENABLED
```

Notes:

- `PAYMOB_SECRET_KEY` is server-side only and should authenticate the Create Intention API request.
- `PAYMOB_PUBLIC_KEY` may be used only where Unified Checkout requires browser-facing public key behavior.
- `PAYMOB_HMAC_SECRET` is server-side only and is required for callback verification.
- `PAYMOB_INTEGRATION_ID_CARD_EGP` must belong to the same sandbox/test account and match EGP/card acceptance.
- `PAYMOB_API_KEY` is deferred unless transaction inquiry in the selected account requires the legacy auth-token path.
- `PAYMOB_IFRAME_ID` is deferred. CONFIG.1 recommends Unified Checkout, not iframe, for the first YounGo sandbox phase.
- `client_secret` is not a config field. It is a runtime response value from Paymob intention creation and must be stored/displayed only according to the eventual implementation design.

## E. Ignored Local Config Plan

Use:

```text
application/config/youngo_paymob.local.php
```

This file is already ignored by Git and must remain uncommitted.

For the first sandbox network phase, the local override should contain these shapes only on the developer machine:

```php
$config['youngo_paymob_local'] = array(
    'enabled' => true,
    'mode' => 'sandbox',
    'currency' => 'EGP',
    'amount_multiplier' => 100,
    'network_enabled' => true,
    'sandbox_network_testing_enabled' => true,
    'webhook_testing_enabled' => true,
    'checkout_routes_enabled' => true,
    'checkout_local_testing_enabled' => true,
    'checkout_cta_enabled' => false,
    'live_mode_allowed' => false,

    'base_url' => '<local-or-tunnel-base-url>',
    'secret_key' => '<sandbox-secret-key>',
    'public_key' => '<sandbox-public-key>',
    'hmac_secret' => '<sandbox-hmac-secret>',
    'integration_id_card_egp' => '<sandbox-card-integration-id>',
    'return_url' => '<local-or-tunnel-base-url>/youngo/checkout/return/{order_reference}',
    'webhook_url' => '<public-https-tunnel>/payment/paymob/webhook',
);
```

Recommended small config additions for the next implementation phase:

- Add `sandbox_network_testing_enabled = false` to tracked defaults.
- Add `base_url` and optional `paymob_base_url` or `api_base_url` placeholders if the adapter needs explicit base URL selection.
- Extend `Youngo_paymob_config` with safe getters:
  - `is_network_enabled()`
  - `is_sandbox_network_testing_enabled()`
  - `is_webhook_testing_enabled()`
  - `get_secret_key()`, returning value only to the adapter, never diagnostics
  - `get_public_key()`
  - `get_paymob_base_url()`
- Keep `get_safe_diagnostic_summary()` redacted.

Tracked defaults must remain:

```text
enabled = false
network_enabled = false
webhook_testing_enabled = false
checkout_routes_enabled = false
checkout_local_testing_enabled = false
checkout_cta_enabled = false
live_mode_allowed = false
currency = EGP
mode = sandbox
```

## F. First Sandbox Network Implementation Plan

Recommended next implementation phase:

```text
PAYMENT.PAYMOB.SANDBOX.INTENTION.1
```

Purpose:

- Perform the first real Paymob sandbox Create Intention request from local YounGo code.
- Redirect/render Paymob Unified Checkout only for a controlled authenticated QA learner/order.
- Keep webhook as source of truth.
- Keep public CTA off by default.
- Do not change production defaults.

Implementation steps:

1. Add network readiness gate.
   - Require `enabled = true`.
   - Require `mode = sandbox`.
   - Require `currency = EGP`.
   - Require `network_enabled = true`.
   - Require new `sandbox_network_testing_enabled = true`.
   - Require `checkout_routes_enabled = true`.
   - Require `checkout_local_testing_enabled = true`.
   - Require all required sandbox config fields.
   - Require `live_mode_allowed = false`.
   - Fail closed for `mode = live`.

2. Extend `Youngo_paymob_adapter`.
   - Add `create_intention($order, $customer)` only in this explicit phase.
   - Use a single HTTP implementation already available in PHP, preferably `curl` if installed; no Composer/Guzzle dependency.
   - Limit HTTP method to Paymob Create Intention endpoint.
   - Use short timeout and structured errors.
   - Do not log authorization headers, secret key, public key, HMAC secret, or full request/response bodies.
   - Build request from existing `build_intention_payload()`.
   - Add safe response parser for intention ID, Paymob order ID if present, and redacted `client_secret` state.

3. Add checkout pay/start behavior behind the new gate.
   - Current `Youngo_checkout::checkout_availability()` rejects network-enabled config. The next phase must split availability into:
     - local disabled/no-network order inspection mode; and
     - explicit sandbox network testing mode.
   - For course `9` QA, create or reuse one draft order through `Youngo_checkout_model`.
   - Mark the order `pending_gateway` before calling Paymob.
   - If intention creation succeeds, store safe Paymob refs on `youngo_checkout_orders` and mark `awaiting_webhook`.
   - Render or redirect to Paymob Unified Checkout URL using public key plus runtime `client_secret`.
   - Do not create entitlement from start/order/return UI.

4. Store only safe Paymob runtime data.
   - Store provider intent/order refs if returned.
   - Store `payment_gateway = paymob`.
   - Store `gateway_environment = sandbox`.
   - Store the YounGo order reference sent to Paymob.
   - Do not store raw secret key.
   - Do not store HMAC secret.
   - Do not store raw authorization headers.
   - Do not store card data.
   - Treat runtime `client_secret` as sensitive. If stored temporarily, store only with a short lifecycle and redact from safe summaries/reports.

5. Return URL behavior.
   - `return/{order_reference}` remains UX-only.
   - Return may mark `return_seen_at` if explicitly approved, but must not mark paid and must not issue entitlement.
   - If webhook has not arrived, show pending/awaiting verification.
   - If webhook already verified success, show paid/access state from local order/read layer.

6. Public CTA behavior.
   - Keep `checkout_cta_enabled = false` by default.
   - For first sandbox network execution, prefer manual URL start or temporary ignored local CTA flag in a controlled QA run.
   - Do not expose homepage/listing/wishlist CTAs.
   - Do not change tracked course-detail CTA defaults.

7. Diagnostics and cleanup.
   - Create a backup before any local DB writes.
   - Add a sandbox intention diagnostic that confirms config readiness and network gate behavior without printing secrets.
   - Add an end-to-end QA report.
   - Cleanup abandoned diagnostic orders where possible.
   - Keep paid sandbox orders that have received real Paymob callbacks only if needed for audit, otherwise document cleanup policy before test.

Commit boundary for the next phase:

- One commit for adapter/controller/config-reader network readiness implementation and report.
- Do not mix Paymob sandbox network implementation with production CTA expansion.

## G. Webhook/Tunnel/HMAC Plan

Local URLs:

```text
Local base URL:
http://school.local

Local return URL shape:
http://school.local/youngo/checkout/return/{order_reference}

Local webhook route:
http://school.local/payment/paymob/webhook
```

Paymob cannot reliably POST to `school.local` or `localhost` from its servers. For real sandbox callback testing, use a public HTTPS tunnel:

```text
Public webhook URL:
https://<temporary-tunnel-host>/payment/paymob/webhook
```

Tunnel rules:

- Tunnel host is temporary and local-only.
- Do not commit tunnel URL.
- Do not place tunnel URL in tracked config.
- Use HTTPS.
- Restrict to the local test window.
- Document the tunnel provider and start/stop time in the sandbox QA report.
- Update Paymob dashboard callback settings or Create Intention `notification_url` to the tunnel URL for the test.

Webhook implementation plan:

1. Keep public route POST-only and session-independent.
2. Require `webhook_testing_enabled = true` and `sandbox_network_testing_enabled = true`.
3. Read JSON/raw payload with size limit.
4. Normalize via `Youngo_paymob_webhook`.
5. Verify HMAC with configured sandbox HMAC secret using timing-safe comparison.
6. Validate required references:
   - YounGo order reference from merchant/order reference field.
   - Paymob transaction ID.
   - Paymob order ID if present.
   - integration ID.
   - amount cents.
   - currency `EGP`.
7. Match the local order by YounGo order reference first.
8. Reject mismatched amount, currency, integration ID, provider order ID, or user/order target.
9. Record redacted transaction summary in `youngo_payment_transactions`.
10. Mark transaction verified or rejected.
11. Mark order paid/failed only from verified/rejected trusted transaction.
12. Issue entitlement exactly once only after order is paid and verified.
13. Return safe 200/202 response for handled duplicate callbacks to avoid endless Paymob retries.

Safe logging:

- Log only event type, local order reference, provider IDs, amount, currency, HMAC verified boolean, status, and payload hash.
- Redact HMAC, secret key, API key, public key value in diagnostics, client secret, authorization header, source PAN, token fields, wallet identifiers beyond safe last digits, and raw customer/payment data.

Transaction inquiry:

- Optional in the first sandbox phase.
- Use only if current sandbox callback or return behavior is ambiguous.
- Keep behind `transaction_inquiry_enabled = true`.
- Do not add legacy auth-token/API-key flow unless the exact current Paymob inquiry docs and account requirements require it.

## H. QA Gates

Before any production CTA or live payment approval, these QA gates must pass locally/sandbox:

- Default tracked config keeps all payment, network, webhook, checkout routes, and CTA flags disabled.
- Ignored local config is recognized and stays uncommitted.
- Missing credential readiness fails closed.
- Wrong currency fails closed.
- Live mode fails closed unless a later live phase explicitly allows it.
- Manual local checkout start creates/reuses one EGP checkout order.
- Successful sandbox card payment creates a Paymob intention and reaches Unified Checkout.
- Successful sandbox transaction webhook verifies HMAC.
- Successful webhook records one transaction.
- Successful verified transaction marks order paid.
- Paid verified order issues course access exactly once.
- My Courses/My Access recognize the issued course access.
- Failed sandbox payment records rejected/failed state without entitlement.
- Duplicate webhook does not create a second transaction success, second paid transition, or second entitlement.
- Invalid HMAC is rejected and does not mark paid.
- Mismatched amount/currency/integration/order reference is rejected.
- Return URL before webhook shows pending and does not issue access.
- Webhook before return marks paid/issued correctly and return later shows safe final state.
- Refreshing return/status does not mutate order or issue entitlement.
- Public webhook GET remains rejected.
- Public webhook POST remains disabled without local flags.
- No Paymob network calls occur when `network_enabled = false`.
- No public checkout CTA appears on homepage, listing, or wishlist.
- Course detail CTA remains hidden by default.
- No legacy `payment` or `enrol` rows are written.
- No legacy gateway DB rows are modified.
- No secrets appear in logs, reports, HTML, JSON, Git diff, screenshots, or terminal output.
- Diagnostic cleanup restores checkout/payment/access counts or documents deliberately retained sandbox audit rows.

Minimum validation commands for the next implementation phase:

```text
php -l on every changed PHP file
php scripts/phase_2/youngo_payment_checkout_cta_local_block_1_diagnostic.php
php scripts/phase_2/youngo_payment_checkout_local_block_1_runtime_test.php
php scripts/phase_2/youngo_payment_paymob_adapter_1_diagnostic.php
php scripts/phase_2/youngo_payment_webhook_route_1_diagnostic.php
php scripts/phase_2/youngo_payment_entitlement_block_1_runtime_test.php
git diff --check
git status --short
```

Add a new sandbox network diagnostic only in the implementation phase, with a no-network mode by default and an explicit local override mode for the single sandbox request.

## I. Rollback Plan

Immediate rollback for sandbox testing:

1. Set ignored local config flags to false:
   - `enabled = false`
   - `network_enabled = false`
   - `sandbox_network_testing_enabled = false`
   - `webhook_testing_enabled = false`
   - `checkout_cta_enabled = false`
2. Remove `application/config/youngo_paymob.local.php`.
3. Stop the public HTTPS tunnel.
4. Remove/disable Paymob dashboard test callback URLs if they point to a temporary tunnel.
5. Cleanup local diagnostic checkout/payment/access rows created by the test, unless the phase explicitly decides to retain a sandbox audit row.
6. Confirm protected counts.
7. Keep tracked production defaults disabled.
8. Revert the implementation commit if source behavior is unsafe.
9. Do not touch live cPanel/server.
10. Do not modify Root Admin.
11. Do not alter legacy gateway rows.

If payment succeeded but entitlement issuance failed:

- Do not retry payment or create a second charge.
- Keep the order `paid`.
- Mark entitlement issuance failure safely.
- Use an admin-only retry plan later.
- Preserve enough redacted transaction data for reconciliation.

If webhook verification is uncertain:

- Do not mark paid.
- Store only rejected/error transaction state if safe.
- Use transaction inquiry only if explicitly implemented and configured.
- Block entitlement issuance.

## J. Risks/Blockers

- Owner must provide Paymob sandbox credentials and account/dashboard access through a secure channel outside Git/reports.
- A public HTTPS tunnel is required for real Paymob server-to-server webhook testing against local YounGo.
- Current `Youngo_checkout::checkout_availability()` intentionally rejects `network_enabled = true`; the next implementation must add a stricter explicit sandbox network mode instead of casually removing that protection.
- `Youngo_paymob_adapter` currently has no HTTP implementation; adding one is the main behavior change of the next phase.
- Current webhook HMAC field order is based on planning and fixtures; it must be confirmed against real sandbox callback payloads before production.
- Paymob dashboard labels/account capabilities may vary, especially public key, secret key, integration IDs, and Unified Checkout availability.
- Return URL behavior can occur before webhook; UI must stay pending until verified webhook/inquiry.
- QA learner scripted login is currently affected by Academy new-device confirmation. Browser QA may need an approved local QA session strategy.
- Subscription purchase issuance remains unimplemented; first sandbox network phase should stay course-purchase-only.
- Legacy invoice/admin revenue compatibility through the old `payment` table is not implemented and must not be faked in the sandbox phase.

## K. Recommended Next Phase

Recommended next phase:

```text
PAYMENT.PAYMOB.SANDBOX.INTENTION.1 - First Paymob Sandbox Intention and Unified Checkout Execution
```

Recommended scope:

- Add `sandbox_network_testing_enabled` to tracked defaults as false.
- Extend the config reader with redacted readiness and server-only getters.
- Add real sandbox Create Intention execution in `Youngo_paymob_adapter` behind explicit local flags.
- Add safe response parsing and redacted diagnostics.
- Update `Youngo_checkout` to support sandbox network mode without changing default-disabled behavior.
- Create a backup before any local order writes.
- Run one authenticated QA learner/course sandbox start flow.
- Do not issue entitlement from return URL.
- Keep webhook source-of-truth.
- Keep public CTAs off by default.

Owner inputs required before that phase:

- Paymob sandbox Secret Key.
- Paymob sandbox Public Key.
- Paymob sandbox HMAC secret.
- EGP card integration ID.
- Confirmation that Unified Checkout is enabled for the sandbox merchant account.
- Approved tunnel provider and public HTTPS tunnel URL strategy.
- Sandbox test card/payment instructions from Paymob dashboard/docs.
- Confirmation whether transaction inquiry is required in first sandbox phase.
- QA learner login/session approach that avoids Root Admin and avoids printing credentials.

## L. Git Status

Git status after creating this report:

```text
?? docs/qa/youngo_payment_paymob_sandbox_plan_1_report.md
```

No source files, config defaults, ignored local config files, DB rows, SQL files, credentials, Root Admin records, live server files, or legacy gateway/payment/enrol rows were changed.
