# PAYMENT.PAYMOB.SANDBOX.INTENTION.PLAN.2 - Hybrid Config Sandbox Intention Execution Plan

Planning-only report for the first future Paymob sandbox Intention execution phase after hybrid Paymob config readiness.

No deployment, push, database write, SQL execution, credential entry, encryption key change, Paymob network call, production CTA exposure, Root Admin modification, or legacy `payment_gateways` usage was performed in this phase.

## A. Current Branch/Status

- Branch: `analysis/cms-audit`
- Start worktree status: clean
- Latest commit at start: `81e6197 Add hybrid YounGo Paymob private config readiness`
- Current phase type: planning only

## B. Files Inspected

- `docs/qa/youngo_payment_secrets_hybrid_config_1_report.md`
- `docs/qa/youngo_payment_paymob_sandbox_plan_1_report.md`
- `docs/qa/youngo_payment_paymob_config_dashboard_save_nonprivate_1_report.md`
- `docs/qa/youngo_payment_checkout_cta_local_ui_qa_1_report.md`
- `docs/qa/youngo_payment_entitlement_block_1_report.md`
- `YOUNGO_PROJECT_CONTEXT.md`
- `docs/design/youngo_style_direction.md`
- `docs/planning/youngo_master_plan_v2.md`
- `docs/planning/youngo_phase_2_hybrid_access_subscriptions_roles_architecture.md`
- `docs/agents/implementation_rules.md`
- `application/libraries/Youngo_paymob_config.php`
- `application/libraries/Youngo_paymob_adapter.php`
- `application/controllers/Youngo_checkout.php`
- `application/models/Youngo_checkout_model.php`
- `application/models/Youngo_payment_config_model.php`

## C. Current Hybrid Readiness State

The current YounGo Paymob foundation is intentionally split:

- Non-private Paymob configuration is stored in the dedicated YounGo table `youngo_payment_provider_configs`.
- Private Paymob values are not stored in DB because `application/config/config.php` still has an empty CodeIgniter `encryption_key`.
- Private values are expected only from ignored local/server config, currently `application/config/youngo_paymob.local.php`.
- `application/config/youngo_paymob.local.php` remains ignored and must not be committed.
- Dashboard save flow accepts only non-private sandbox fields and rejects private fields and activation flags.
- Dashboard/audit/readiness output uses redacted presence labels such as `missing`, `configured_redacted`, `server_config_required`, and `db_private_storage_blocked`.
- `Youngo_paymob_config` can read default config and optional ignored local override; it exposes runtime getters for private values but diagnostics must only use presence summaries.
- `Youngo_paymob_adapter` is still a disabled skeleton. It can build a safe Intention payload shape but does not make network calls.
- `Youngo_checkout` supports local checkout start/order/status/return behind local testing flags, but currently rejects `network_enabled=true` as part of the previous no-network phase.

Current local checkout/payment foundation already has:

- Local course detail CTA gating with defaults hidden.
- Local checkout start/order/status/return routes.
- Checkout order create/reuse behavior.
- Fixture transaction recording.
- Paid/failed status transitions from verified/rejected fixture transactions.
- Entitlement issuance from verified paid diagnostic orders.
- Public webhook route fail-closed by default.
- No real Paymob execution.

## D. Sandbox Execution Prerequisites

The future `PAYMENT.PAYMOB.SANDBOX.INTENTION.1` phase must fail closed unless all required dashboard, server config, route, account, and runtime gates are satisfied.

Required dashboard DB non-private config:

- Provider: `paymob`
- Mode: `sandbox`
- Currency: `EGP`
- Amount multiplier: `100`
- Card integration ID for EGP: numeric value present
- API base URL: valid sandbox `https` URL
- Checkout base URL: valid sandbox `https` URL
- Return URL: valid YounGo checkout return URL
- Notification/webhook URL: valid public HTTPS URL that reaches `/payment/paymob/webhook`

Required ignored local/server private config:

- `secret_key`
- `public_key`
- `hmac_secret`

Required explicit local/sandbox gates:

- `enabled=true`
- `network_enabled=true`
- `sandbox_network_testing_enabled=true`
- `checkout_routes_enabled=true`
- `checkout_local_testing_enabled=true`
- `webhook_testing_enabled=true` for real webhook testing
- `checkout_cta_enabled=false` unless a local CTA QA phase explicitly enables it
- `live_mode_allowed=false`
- Mode must remain `sandbox`
- Currency must remain `EGP`

Required user/course boundary:

- Authenticated learner only.
- Do not use Root Admin for learner checkout.
- Use an approved local learner fixture, such as `qa.learner@youngo.local`.
- Use an approved YounGo purchase-compatible EGP course fixture.
- Reject invalid, inactive, free, subscription-only-only-when-not-purchase-compatible, already-access, and non-EGP cases safely.

Required environmental boundary:

- Sandbox execution must run only on local/test environment.
- No production defaults may change.
- No real credentials may be committed or copied into reports.
- No legacy Academy `payment_gateways`, cart, payment, or enrol rows may be used as a shortcut.

## E. Implementation Boundary For PAYMENT.PAYMOB.SANDBOX.INTENTION.1

The next implementation phase should be narrow and reversible.

Allowed future behavior:

- Create or reuse one local YounGo checkout order through `Youngo_checkout_model`.
- Read non-private Paymob config from `Youngo_payment_config_model`.
- Read private Paymob runtime values only from ignored local/server config through `Youngo_paymob_config`.
- Call the Paymob sandbox Intention API only when all gates pass.
- Store safe gateway references only, such as Paymob intention/order identifiers and redacted response metadata.
- Mark order `pending_gateway` after a successful sandbox Intention response.
- Mark order `awaiting_webhook` after rendering/redirecting to Unified Checkout.
- Render or redirect to the sandbox Unified Checkout URL.
- Keep return URL as UX-only.
- Keep webhook/HMAC verification as source of truth for paid/failed decisions.

Explicitly disallowed in `PAYMENT.PAYMOB.SANDBOX.INTENTION.1`:

- No live mode.
- No production CTA exposure.
- No credentials in Git, reports, logs, screenshots, SQL, or audit rows.
- No payment entitlement issuance from the return URL or order page.
- No legacy `payment`, `enrol`, cart, gateway, or invoice writes.
- No use of inherited Academy gateway rows.
- No Root Admin learner checkout.
- No DB private-value storage while encryption/key-management remains unresolved.

Likely code changes for the next phase:

- Extend `Youngo_paymob_adapter` with a real sandbox-only `create_intention()` method.
- Update adapter readiness to use hybrid config, including runtime-only private getters.
- Add strict sandbox URL allowlist/validation for configured Paymob API and checkout base URLs.
- Add HTTP execution with short timeout and structured error handling. Do not add new dependencies unless explicitly approved.
- Update `Youngo_checkout::checkout_availability()` so `network_enabled=true` is allowed only when `sandbox_network_testing_enabled=true` and all hybrid readiness checks pass.
- Add a controller path that starts the Paymob sandbox Intention from an existing local checkout order.
- Add redacted response handling and no-secret logging policy.
- Add diagnostics that scan for accidental raw secret output and legacy payment/enrol writes.

Schema note:

- If existing `youngo_checkout_orders` or `youngo_payment_transactions` columns cannot safely store the required Paymob references, the next phase should stop and report a schema gap. It should not add ad hoc schema changes inside the network-execution phase.

## F. Manual Values Owner Must Provide Outside Git/Reports

The owner must provide the following names/values through ignored local/server config or dashboard non-private fields, but reports and commits must list names only:

- `PAYMOB_SECRET_KEY`
- `PAYMOB_PUBLIC_KEY`
- `PAYMOB_HMAC_SECRET`
- `PAYMOB_INTEGRATION_ID_CARD_EGP`
- `PAYMOB_API_BASE_URL`
- `PAYMOB_CHECKOUT_BASE_URL`
- `PAYMOB_RETURN_URL`
- `PAYMOB_WEBHOOK_URL`
- `PAYMOB_TUNNEL_PUBLIC_BASE_URL` if using a callback tunnel
- Approved QA learner identifier
- Approved QA course identifier
- Paymob sandbox test-card instructions from the Paymob dashboard/docs

Potential account-specific fields to confirm before implementation:

- `PAYMOB_API_KEY`, if the account/docs still require an API-key naming variant.
- `PAYMOB_MERCHANT_ID`, if required for dashboard matching or webhook validation.
- `PAYMOB_IFRAME_ID`, only if the account flow falls back to iframe instead of Unified Checkout.

## G. Webhook/Tunnel/HMAC Plan

Local return URL:

- Use a local YounGo return route such as `/youngo/checkout/return/{order_reference}`.
- The return route must not mark paid, issue entitlement, or trust query parameters as payment truth.

Webhook URL:

- Use the existing dedicated webhook route `/payment/paymob/webhook`.
- Public webhook processing must remain fail-closed until `webhook_testing_enabled=true` and HMAC config presence is confirmed.
- Paymob server-to-server callbacks cannot reach plain `localhost`; real sandbox webhook QA needs a public HTTPS tunnel or a deployed non-production test endpoint.

Tunnel strategy:

- Use a temporary tunnel public base URL only for local sandbox QA.
- Store the tunnel URL in ignored local/server config or dashboard non-private URL fields, not in committed defaults.
- Remove or rotate the tunnel after QA.

HMAC/source-of-truth strategy:

- Verify Paymob webhook HMAC with the runtime HMAC secret from ignored local/server config.
- Reject missing, malformed, invalid-HMAC, wrong-currency, wrong-integration, wrong-provider, and unmatched-order payloads.
- Record received/verified/rejected/duplicate transaction rows with redacted payload summaries only.
- Mark paid/failed only from verified webhook processing.
- Issue entitlement exactly once only after verified paid order processing in a later approved execution phase.

Safe logging:

- Log event type, order reference, Paymob reference identifiers, status, amount/currency, HMAC verification status, and redacted error codes.
- Never log private keys, raw HMAC secret, full client secret, authorization headers, or raw payload fields that may contain private data.

## H. QA Gates

Before any sandbox network execution is considered acceptable:

- Missing ignored private config fails closed.
- Missing dashboard non-private config fails closed.
- Non-sandbox mode fails closed.
- Non-EGP currency fails closed.
- Network execution requires explicit local/sandbox flags.
- Checkout session is created only for an authenticated learner and approved fixture.
- Sandbox Intention request payload uses EGP smallest-unit amount.
- Paymob response stores only safe references and redacted metadata.
- Unified Checkout URL is sandbox-only.
- Return URL before webhook leaves order pending/awaiting webhook.
- Webhook before return can mark order paid only after HMAC verification.
- Duplicate webhook does not create duplicate paid state or duplicate entitlement.
- Failed sandbox payment marks rejected/failed safely.
- Invalid HMAC is rejected.
- No private values appear in Git, reports, logs, browser output, dashboard output, SQL files, or audit rows.
- No legacy `payment` or `enrol` rows are written.
- Public course/listing/home/wishlist CTAs remain hidden unless an explicit local CTA QA phase enables them.
- Production defaults remain disabled.

## I. Rollback Plan

Immediate rollback/safe-off actions:

- Set `enabled=false`, `network_enabled=false`, `sandbox_network_testing_enabled=false`, and `webhook_testing_enabled=false` in ignored local/server config.
- Keep `checkout_cta_enabled=false`.
- Remove `application/config/youngo_paymob.local.php` if testing must be fully disabled.
- Leave committed default config disabled.
- Clean up local sandbox diagnostic checkout orders and transactions if they are test-only.
- Do not delete real historical sandbox evidence unless the phase report explicitly identifies it as diagnostic data.
- Revert the next source commit if code behavior needs rollback.
- Do not touch live cPanel or live DB.

If webhook tunnel is used:

- Stop the tunnel.
- Remove tunnel URLs from ignored local/server config and dashboard non-private fields if they were temporary.
- Verify the public webhook route returns fail-closed after flags are disabled.

## J. Risks/Blockers

- Real Paymob Intention request/response shape must be verified against the current account/docs again during implementation, especially checkout URL construction and any account-specific key naming.
- A public HTTPS tunnel is required for real Paymob webhook delivery to a local machine; `localhost` is not enough for server-to-server webhook testing.
- `Youngo_checkout::checkout_availability()` currently rejects `network_enabled=true`; the next phase must revise that gate deliberately for sandbox-only execution.
- `Youngo_paymob_adapter::is_ready_for_sandbox()` currently returns disabled by design and still reads generic config values for private fields; it must switch to runtime-only private getters before real network execution.
- The public key may be safe to render in Paymob frontend contexts, but YounGo currently treats it as private/server config for conservative handling. This should be confirmed against the final Paymob Unified Checkout integration requirements.
- CodeIgniter `encryption_key` is still empty, so DB-backed private secret storage remains blocked.
- Sandbox execution will create real sandbox provider records; cleanup and evidence retention rules must be agreed before the first real call.

## K. Recommended Next Phase

Recommended next phase:

`PAYMENT.PAYMOB.SANDBOX.INTENTION.1 - Controlled Hybrid Sandbox Intention Implementation`

Suggested acceptance boundary:

- One local authenticated learner/course fixture.
- Explicit ignored local/server config with sandbox-only flags and private values.
- One sandbox Intention creation path.
- No production defaults changed.
- No public CTA exposure beyond approved local flags.
- Webhook remains source of truth.
- No entitlement from return URL.
- No legacy payment/enrol writes.

## L. Git Status

- After this planning report is created, expected Git status should show only:
  - `?? docs/qa/youngo_payment_paymob_sandbox_intention_plan_2_report.md`

