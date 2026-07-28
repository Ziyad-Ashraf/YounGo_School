# YounGo Payment Config 1 Paymob Research And Sandbox Plan

Phase: PAYMENT.CONFIG.1 - Paymob Latest Documentation Research and Sandbox Gateway Planning
Date: 2026-07-21
Scope: research and planning only. No deployment, no push, no commit, no live cPanel access, no real payment activation, no real credentials, no DB writes, no gateway hygiene SQL execution, no Root Admin changes, no public checkout CTA exposure, and no Paymob implementation.

## A. Current Branch/Status

Initial command results:

```text
git branch --show-current
analysis/cms-audit

git status --short
<clean>

git log --oneline -10
dda25bc Harden YounGo legacy payment entry points
a2e1f81 Document YounGo payment DB baseline
727ef72 Audit YounGo payment flow with local DB
e5f6a5f Document live cPanel client demo handoff
0216016 Add YounGo cPanel deployment runbook
9cd4b3d Prepare YounGo sanitized client admin export
49aa360 Test YounGo cPanel package restore locally
2337d79 Prepare YounGo cPanel client package
8357c24 Reconcile YounGo client upload plan
7222c37 Prepare YounGo client upload preflight
```

The expected branch and clean starting worktree were confirmed. The latest commit is the PAYMENT.FIX.1 legacy payment entry-point hardening commit.

## B. Paymob Sources Researched

Official/current Paymob sources reviewed first:

- Paymob developer docs index: `https://developers.paymob.com/paymob-docs/getting-started/overview/llms.txt`
- Paymob overview: `https://developers.paymob.com/paymob-docs/getting-started/overview.md`
- Integration checklist: `https://developers.paymob.com/paymob-docs/getting-started/integration-checklist.md`
- API integration path: `https://developers.paymob.com/paymob-docs/integration-paths/apis.md`
- Create Intention API: `https://developers.paymob.com/paymob-docs/developers/intention-apis/create-intention.md`
- Checkout experiences: `https://developers.paymob.com/paymob-docs/developers/checkout-experiences.md`
- Webhooks, callbacks, and HMAC: `https://developers.paymob.com/paymob-docs/developers/webhook-callbacks-and-hmac.md`
- HMAC overview: `https://developers.paymob.com/paymob-docs/developers/webhook-callbacks-and-hmac/hmac.md`
- Card-token HMAC reference: `https://developers.paymob.com/paymob-docs/developers/webhook-callbacks-and-hmac/hmac/hmac-for-card-tokens.md`
- Transaction inquiry by transaction ID: `https://developers.paymob.com/paymob-docs/developers/transaction-inquiry-apis/by-transaction-id.md`
- Transaction inquiry by order ID or merchant order ID: `https://developers.paymob.com/paymob-docs/developers/transaction-inquiry-apis/by-order-id-or-reference.md`
- Authentication request/generate auth token: `https://developers.paymob.com/paymob-docs/developers/authentication-request-generate-auth-token-1.md`
- Getting integration credentials FAQ: `https://developers.paymob.com/paymob-docs/need-help/faq/getting-integration-credentials.md`
- Test credentials FAQ: `https://developers.paymob.com/paymob-docs/need-help/faq/test-credentials.md`
- Common issues and inquiries FAQ: `https://developers.paymob.com/paymob-docs/need-help/faq/common-issues-and-inquires.md`

Official Paymob GitHub and package sources reviewed:

- Paymob AI Integration Skill repository: `https://github.com/PaymobAccept/Paymob-AI-Integration-Skill`
- Paymob AI Integration Skill AGENTS reference: `https://raw.githubusercontent.com/PaymobAccept/Paymob-AI-Integration-Skill/main/AGENTS.md`
- Paymob AI Integration Skill Intention API reference: `https://raw.githubusercontent.com/PaymobAccept/Paymob-AI-Integration-Skill/main/skills/paymob-integration/references/intention-api.md`
- Paymob AI Integration Skill HMAC reference: `https://raw.githubusercontent.com/PaymobAccept/Paymob-AI-Integration-Skill/main/skills/paymob-integration/references/hmac-verification.md`
- Paymob AI Integration Skill live resources reference: `https://raw.githubusercontent.com/PaymobAccept/Paymob-AI-Integration-Skill/main/skills/paymob-integration/references/live-resources.md`
- Official Paymob PHP SDK/library repository: `https://github.com/PaymobAccept/paymob-php`
- Paymob PHP package listing: `https://packagist.org/packages/paymob/php-library`

Visible freshness:

- The official docs overview showed last update July 16, 2026.
- The integration checklist showed last update July 14, 2026.
- Create Intention, API integration path, credential, HMAC, and auth-token pages showed June 2026 update dates.
- Transaction inquiry pages showed June 28, 2026 update dates.
- The Paymob AI Integration Skill repository was active in July 2026 and is maintained under the official PaymobAccept GitHub organization.
- The official PHP package listing showed version 1.0.4, published 2024-11-13, with package activity updated in July 2026. The GitHub SDK repository did not show GitHub releases.

Source access note:

- Some direct browser views of Paymob docs returned a JavaScript protection/interstitial page. The official `.md` documentation endpoints and `llms.txt` index were fetched successfully and cross-checked with official Paymob GitHub references.

## C. Paymob Latest Documentation Summary

Current recommended payment creation flow:

1. YounGo backend creates a local checkout order first.
2. YounGo backend calls Paymob Create Intention API: `POST /v1/intention/`.
3. The request uses `Authorization: Token <SECRET_KEY>`. Current payment creation does not start with the legacy API-key auth-token, order-registration, and payment-key sequence.
4. The request sends amount in the smallest currency unit, currency, payment method integration IDs or method names, billing data, YounGo order reference, notification URL, and redirection URL.
5. Paymob returns a Paymob order/intention identifier and a `client_secret`.
6. The learner is sent to Paymob Unified Checkout, using the browser-safe public key and the returned `client_secret`.
7. Paymob redirects the learner back to YounGo for UX using the redirection URL.
8. Paymob sends the server-to-server transaction processed callback to the notification URL.
9. YounGo verifies the callback HMAC, reconciles transaction status if needed, records the payment result, and issues YounGo access idempotently.

Card, iframe, and payment-page guidance:

- Current Paymob documentation and official integration skill emphasize the Intention API plus Unified Checkout or Pixel checkout.
- Unified Checkout is the safest recommended fit for YounGo's first sandbox phase because it avoids embedding JavaScript card collection in the local app and keeps card handling on Paymob's hosted page.
- Paymob documentation now recommends Unified Checkout for new merchants and says iframe setup requires support/account-manager involvement for merchants who still need it.
- `PAYMOB_IFRAME_ID` should therefore be treated as legacy/deferred unless the owner or Paymob explicitly requires an iframe integration.
- The legacy 3-step card flow of auth token, order registration, and payment key should not be used for new YounGo payment creation unless current Paymob support documentation explicitly mandates it for the merchant account.

Auth token and API key:

- Current payment creation uses Secret Key authentication with `Authorization: Token <SECRET_KEY>`.
- The legacy/API key auth-token endpoint still exists and is documented for APIs such as transaction inquiry and other non-Intention flows.
- For YounGo, `PAYMOB_API_KEY` should be stored only if the transaction inquiry/reconciliation APIs require it in the selected Paymob account flow. It must not be exposed client-side.

Order and payment request:

- Paymob Create Intention is the current order/payment-start request.
- Required request concepts include amount, currency, payment methods/integration IDs, billing data, YounGo reference, notification URL, and redirection URL.
- Amount is sent in cents/piasters. Example: `100.00 EGP` becomes `10000`.
- Currency must match the configured Paymob integration ID. YounGo's target commercial currency is EGP, so the sandbox Paymob integration must also be configured for EGP.

Callback and return URL behavior:

- `notification_url` is the server-to-server Transaction Processed Callback and should be the source of truth.
- `redirection_url` is the browser Transaction Response Callback/return experience and is suitable for learner UX only.
- Redirect parameters must not be trusted to issue access without server-side HMAC verification and/or transaction inquiry reconciliation.
- Redirection URL support is documented for card and wallet flows; method-specific behavior should be rechecked during sandbox QA.

HMAC and verification:

- Paymob transaction callbacks include an `hmac` value.
- YounGo must recompute the expected HMAC with the Paymob HMAC secret, using SHA-512 and Paymob's documented field ordering for the callback type.
- For transaction processed callbacks, official Paymob references list an ordered concatenation including amount, timestamps, status flags, transaction ID, integration ID, order ID, owner, pending/success flags, and source data fields.
- Card-token HMAC uses a different field order and is out of scope for first YounGo checkout unless saved-card/tokenization support is intentionally added later.
- HMAC comparison should use a timing-safe comparison.
- Transaction inquiry APIs can be used for reconciliation after callback or return uncertainty. The documented inquiry paths include by transaction ID and by Paymob order ID or merchant order/special reference.

Integration IDs, iframe IDs, and merchant/account identifiers:

- Integration IDs are method/account/environment-specific. The dashboard integration ID mode must match the key mode, test versus live.
- Paymob documents common 404 issues when the integration ID does not belong to the account, the key mode does not match, or the ID is malformed.
- Iframe IDs are not required for the recommended Unified Checkout plan and should be deferred.
- Merchant/account identifiers should be stored only if required by the selected current Paymob APIs, dashboard configuration, or callback HMAC/tokenization path.

Sandbox/testing mode requirements:

- Test and live use the same regional base URL; environment is controlled by test/live keys and integration IDs.
- Egypt base URL: `https://accept.paymob.com`.
- Use sandbox/test keys, test integration IDs, and official Paymob test card/wallet values only.
- Do not activate real payment plans or live integration IDs before explicit owner approval and QA.
- Go-live requires Paymob account validation, contract/risk approval, successful testing, technical approval, and live credentials.

Local development limitations:

- A local browser can return to a local YounGo URL only when the learner is testing in that same local environment.
- Paymob server-to-server webhook/callback cannot reach `localhost`; a public HTTPS tunnel is needed for local webhook QA.
- Local configuration must not hardcode cPanel/live URLs. Base URL and callback URLs must be environment-specific placeholders.

Uncertainty and missing documentation:

- Paymob docs are current, but some page access required `.md` endpoints because direct rendered pages showed JavaScript protection.
- Exact dashboard labels and account-specific credential availability can vary by merchant/account mode.
- The owner must provide sandbox dashboard access or owner-confirmed credential names before implementation.
- Any requirement to use iframe IDs instead of Unified Checkout must be confirmed by Paymob support or the merchant dashboard before implementation.

## D. Existing Local Payment Architecture Summary

Files reviewed:

- `docs/qa/youngo_payment_flow_audit_and_test_plan.md`
- `docs/qa/youngo_payment_db_1_baseline_report.md`
- `docs/qa/youngo_payment_fix_1_legacy_cleanup_report.md`
- `scripts/phase_2/payment_db_1_gateway_hygiene_proposed.sql`
- `application/controllers/Payment.php`
- `application/controllers/Home.php`
- `application/models/Payment_model.php`
- `application/models/Crud_model.php`
- `application/models/Youngo_entitlement_model.php`
- `application/models/Youngo_entitlement_write_model.php`
- `application/helpers/youngo_entitlement_helper.php`
- `application/views/payment-global/payment_gateway.php`
- Gateway form views under `application/views/payment-global/`
- Cart, checkout, course listing/detail, wishlist, My Courses, and My Access related views
- Phase 2 schema files defining YounGo checkout/access/subscription/coupon tables

Legacy Academy payment behavior:

- `Payment_model::configure_course_payment()` builds session-based `payment_details` from legacy cart items.
- `Payment::success_course_payment()` validates a selected legacy gateway, then writes legacy enrolment through `Crud_model::enrol_student()` and legacy purchase rows through `Crud_model::course_purchase()`.
- Existing provider-specific `check_*_payment()` methods are legacy gateway handlers and are not a YounGo order/access issuance flow.
- The legacy `payment_gateways` table remains populated with inherited providers.

PAYMENT.FIX.1 hardening now in place:

- YounGo-managed course modes are blocked from direct legacy cart, buy-now, course-payment, free-enrol, and 100 percent coupon direct-enrol paths.
- `Payment::youngo_gateway_payment_check()` fails closed for unsafe gateway identifiers, inactive gateway rows, missing handler models, missing handler methods, and gateway currency mismatches.
- `payment_gateway.php` filters out active gateways whose currency differs from the configured system currency.
- Local reports state that 15 inherited gateway rows remain active, test-mode, and non-EGP. They are hidden/unsafe for the current EGP baseline and must not be used for YounGo payments.

YounGo entitlement architecture:

- `Youngo_entitlement_model` is the read layer for legacy enrolments, direct YounGo course access, active subscriptions, and manual grants.
- `Youngo_entitlement_write_model` is the required write-service boundary for YounGo access/subscription issuance.
- Manual grants are implemented and QA-tested through the YounGo Manual Grants UI.
- Checkout issuance methods currently return `checkout_issuance_not_implemented` and must be implemented deliberately in a future payment phase.

Existing YounGo schema context:

- `youngo_checkout_orders` exists from Phase 2 schema work and can become the local order anchor, but it needs a payment-phase review before use.
- Current schema defaults include USD in some checkout/subscription fields, so future EGP implementation must explicitly set and validate `EGP`.
- There is no dedicated `youngo_payment_transactions` table in the inspected schema.
- No Paymob controller, model, webhook handler, config mechanism, or transaction table is currently implemented.

## E. Recommended Sandbox Gateway Strategy

Recommendation:

- Do not use inherited Academy `payment_gateways` rows for YounGo payment implementation.
- Do not enable the existing legacy gateway rows as the YounGo sandbox path.
- Do not reuse the old Academy `Payment::success_course_payment()` path for YounGo-managed course or subscription access.
- Add a YounGo-specific checkout/order abstraction in a later implementation phase.
- Add a YounGo-specific Paymob adapter/config mechanism in a later implementation phase.
- Use Paymob Intention API plus Unified Checkout for the first sandbox payment flow.
- Store sandbox credentials outside Git and outside reports.
- Keep payment configuration Root/core-admin-only until the flow is stable, audited, and QA-approved.

Rationale:

- The legacy payment flow writes legacy `enrol` and `payment` rows immediately after gateway success and does not understand YounGo subscriptions, direct course access, manual grant boundaries, callback idempotency, or YounGo checkout orders.
- The inherited gateway table currently has active non-EGP rows that are known unsafe for the YounGo baseline.
- Paymob's current documentation points to Intention API and Unified Checkout, not inherited generic iframe/payment-key form snippets.
- YounGo needs source-of-truth order and entitlement issuance records before any public CTA can safely start payment.

## F. Required Placeholder Config Fields

Placeholders only. Do not add real values to Git, reports, screenshots, tickets, or committed config files.

Recommended first sandbox placeholders:

```text
PAYMOB_ENV=sandbox
PAYMOB_REGION=EG
PAYMOB_BASE_URL=https://accept.paymob.com
PAYMOB_SECRET_KEY=<paymob-test-secret-key>
PAYMOB_PUBLIC_KEY=<paymob-test-public-key>
PAYMOB_HMAC_SECRET=<paymob-test-hmac-secret>
PAYMOB_INTEGRATION_ID_CARD=<paymob-test-card-integration-id>
PAYMOB_NOTIFICATION_URL=<public-https-tunnel>/youngo/payments/paymob/webhook
PAYMOB_REDIRECTION_URL=<local-or-public-base-url>/youngo/payments/paymob/return
PAYMOB_CURRENCY=EGP
PAYMOB_TEST_MODE=1
```

Conditional/deferred placeholders:

```text
PAYMOB_API_KEY=<paymob-api-key-for-auth-token-transaction-inquiry-if-needed>
PAYMOB_INTEGRATION_ID_WALLET=<paymob-test-wallet-integration-id-if-wallets-are-added>
PAYMOB_MERCHANT_ID=<paymob-merchant-or-account-id-if-required-by-selected-api-or-hmac-path>
PAYMOB_IFRAME_ID=<legacy-deferred-only-if-paymob-or-owner-requires-iframe-flow>
PAYMOB_WEBHOOK_TUNNEL_PROVIDER=<ngrok-or-cloudflared-or-other-approved-tool>
```

Configuration storage recommendation:

- For local development, use a non-committed environment file or local-only CodeIgniter config override.
- Add only placeholder documentation/examples to Git.
- Do not store credentials in the existing `payment_gateways` table until a deliberate YounGo config storage decision is made.
- If database-backed settings are later needed, store encrypted or otherwise protected values where the application can do so safely, and restrict reads/writes to Root/core-admin capability.

## G. Local Callback/URL Strategy

Current local base URL behavior:

- `application/config/config.php` dynamically derives `base_url` from request scheme, host, and script path.
- This is suitable for local route construction only if the request host is the intended local or tunneled host.

Recommended local URLs for a future implementation:

```text
Local app base URL:
<developer-local-base-url>

Learner return/redirection URL:
<developer-local-base-url>/youngo/payments/paymob/return

Paymob notification/webhook URL:
<public-https-tunnel>/youngo/payments/paymob/webhook
```

Rules:

- Do not hardcode cPanel or live domain URLs in source.
- Derive route paths from CodeIgniter helpers and environment-specific base URL config.
- Treat the return URL as UX-only. It can show pending/success/failure states, but it must not issue access by itself.
- Use a public HTTPS tunnel for Paymob server-to-server webhook testing because Paymob cannot POST to local `localhost`.
- Keep the public tunnel URL temporary and local-only; do not commit it.
- During local QA, set the Paymob sandbox dashboard callback URL and Create Intention `notification_url`/`redirection_url` to the current local/tunnel URLs.

## H. Target YounGo Checkout/Payment/Access Flow

Future intended flow:

1. Learner views a YounGo-managed paid course, subscription-only course, or subscription plan.
2. Public CTAs remain disabled until checkout is implemented and QA-approved.
3. When enabled later, learner starts a YounGo checkout, not legacy Academy cart/payment.
4. YounGo validates learner login, course/plan eligibility, price, currency `EGP`, and access mode.
5. YounGo creates a local `youngo_checkout_orders` row with status such as `pending`.
6. YounGo calls Paymob Create Intention API using sandbox Secret Key and EGP integration ID.
7. YounGo stores Paymob intention/order identifiers against the local checkout order.
8. YounGo redirects the learner to Paymob Unified Checkout using Public Key and Paymob `client_secret`.
9. Learner completes or abandons payment in sandbox.
10. Paymob redirects learner to YounGo return URL for UX.
11. Paymob sends transaction processed callback to YounGo webhook URL.
12. YounGo verifies HMAC using the Paymob HMAC secret.
13. YounGo records callback payload safely, redacting sensitive values.
14. YounGo optionally calls transaction inquiry when callback or return state needs reconciliation.
15. YounGo marks local order paid, failed, expired, or pending review based on verified server-side status.
16. For successful verified payment, YounGo calls a future checkout issuance method in `Youngo_entitlement_write_model`.
17. Course purchase issues direct YounGo course access. Subscription purchase issues YounGo subscription access.
18. Legacy enrolment is skipped by default for YounGo-managed access. If compatibility requires a legacy enrol row for lesson playback, it must be synchronized intentionally after entitlement issuance, not created by legacy payment success.
19. Duplicate callbacks are handled idempotently by Paymob transaction ID, Paymob order ID, and YounGo order reference.
20. Learner sees My Courses/My Access updates through the existing YounGo entitlement read layer.

## I. Proposed Future DB Design

Existing table to review and likely use:

```text
youngo_checkout_orders
```

Recommended future order fields or constraints:

```text
id
user_id
order_type                       course_purchase | subscription_purchase
status                           draft | pending | payment_started | paid | failed | expired | cancelled | issued | issuance_failed
course_id
plan_id
currency                         EGP only for YounGo commercial checkout
subtotal_amount
discount_amount
tax_amount
total_amount
payment_gateway                  paymob
gateway_environment              sandbox | live
provider_intention_id
provider_order_id
provider_transaction_id
merchant_order_reference
idempotency_key
expires_at
paid_at
failed_at
issued_at
failure_reason
metadata                         safe, redacted JSON
created_at
updated_at
```

Recommended new table:

```text
youngo_payment_transactions
```

Proposed fields:

```text
id
checkout_order_id
user_id
gateway                          paymob
gateway_environment              sandbox | live
currency                         EGP
amount_cents
amount_decimal
provider_intention_id
provider_order_id
provider_transaction_id
provider_integration_id
merchant_order_reference
event_type                       return | transaction_processed | inquiry | manual_reconcile
status                           pending | success | failed | declined | refunded | voided | unknown
hmac_received                    boolean or redacted marker, not raw secret
hmac_verified                    boolean
verification_source              hmac | inquiry | admin_review
raw_payload_redacted             JSON/text with secrets and card data removed
payload_hash                     hash for duplicate detection/audit
idempotency_key
processed_at
created_at
updated_at
```

Recommended entitlement issuance tracking:

```text
youngo_checkout_orders.entitlement_issuance_status
youngo_checkout_orders.entitlement_issued_at
youngo_checkout_orders.entitlement_access_id
youngo_checkout_orders.entitlement_subscription_id
youngo_checkout_orders.issuance_error
```

or a separate issuance log if multiple issuance actions must be audited:

```text
youngo_entitlement_issuance_log
```

Additional design notes:

- `youngo_checkout_orders.currency` and any subscription/payment currency fields must be explicitly set to `EGP`; do not rely on older USD defaults.
- Store all money as integer cents/piasters or validated decimal with a clear conversion boundary. Paymob API amount must use integer smallest currency unit.
- Do not store card PAN, CVV, or sensitive cardholder data.
- Store Paymob callback bodies only after redaction and size limits.
- Add uniqueness around Paymob transaction ID and YounGo merchant order reference to make callbacks idempotent.
- Keep coupon usage separate from access issuance. Coupon/discount must never directly grant access without a verified paid/free-order issuance path.

## J. Admin/Security Notes

- Payment configuration must remain hidden from ordinary admins and instructors.
- Initial configuration should be Root/core-admin-only, but no Root Admin account changes should be made for this planning phase.
- Secrets must be kept outside Git, reports, screenshots, issue text, and committed database dumps.
- Public key may be used in browser checkout, but Secret Key, API Key, HMAC secret, and merchant credentials must remain server-side only.
- Webhook endpoints must verify HMAC before trusting status.
- Return URLs must never issue access without verified server-side confirmation.
- All Paymob callback processing must be idempotent.
- Log only redacted payloads. Do not log HMAC secret, Secret Key, API Key, raw authorization headers, card PAN, CVV, or full sensitive wallet/payment details.
- Keep live mode disabled until a separate implementation, QA, and owner approval phase explicitly enables it.
- The proposed gateway hygiene SQL remains proposal-only and must not be executed in this phase.

## K. Risks/Blockers/Unknowns

- Paymob merchant dashboard access and sandbox credentials are owner-provided and were not available in this phase.
- Paymob dashboard labels and enabled payment methods may differ by account.
- The merchant may already have legacy iframe integrations; if so, Paymob support must confirm whether Unified Checkout is available before any fallback is designed.
- The local server-to-server webhook cannot be fully tested without a public HTTPS tunnel or equivalent public callback tool.
- Existing `youngo_checkout_orders` schema needs payment-phase review before implementation because some defaults are not yet EGP-first.
- Entitlement checkout issuance methods are currently stubs and must be implemented before payment success can grant access.
- Legacy lesson playback may still need compatibility synchronization for some access paths; this must be decided explicitly and tested.
- Current inherited `payment_gateways` rows remain active/non-EGP in DB and are intentionally not used for the proposed YounGo Paymob architecture.
- Public paid/subscription CTAs must remain checkout-not-ready until the full order, Paymob, callback, and entitlement issuance path is built and QA-tested.

## L. Recommended Next Phase

Recommended next phase: PAYMENT.CONFIG.2 - YounGo Sandbox Payment Configuration Design.

Scope for the next phase:

1. Design a non-secret local config mechanism for Paymob sandbox placeholders.
2. Propose CodeIgniter route names for Paymob start, return, webhook, and inquiry/reconcile handlers.
3. Review and, if needed, propose additive schema changes for `youngo_checkout_orders` and `youngo_payment_transactions`.
4. Define status transitions and idempotency rules.
5. Define exactly how successful verified payment calls `Youngo_entitlement_write_model` checkout issuance methods.
6. Prepare diagnostics only. Do not start real payments or expose public checkout CTAs yet.

Implementation should wait until the owner supplies current Paymob sandbox dashboard information and approves the selected credential/config storage approach.

## M. Git Status

Initial status before this report:

```text
git status --short
<clean>
```

Expected status after this report is created:

```text
?? docs/qa/youngo_payment_config_1_paymob_research_and_sandbox_plan.md
```

No code files, database files, credentials, Root Admin records, live server files, or payment gateway settings were changed in this phase.
