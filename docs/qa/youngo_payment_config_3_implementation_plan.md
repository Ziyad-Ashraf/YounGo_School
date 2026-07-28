# YounGo Payment Config 3 Implementation Plan

Phase: PAYMENT.CONFIG.3 - YounGo Payment Config and Schema Implementation Plan

Scope: planning only. No deployment, push, commit, live cPanel access, real payment activation, credential storage, secret output, DB modification, SQL execution, Root Admin change, public checkout CTA exposure, or Paymob API implementation was performed.

## A. Current Branch/Status

Initial branch check:

```text
git branch --show-current
analysis/cms-audit
```

Initial worktree check:

```text
git status --short
<clean>
```

Latest commits reviewed:

```text
20c21f0 Design YounGo payment architecture and schema
dd601bd Plan YounGo Paymob sandbox integration
dda25bc Harden YounGo legacy payment entry points
a2e1f81 Document YounGo payment DB baseline
727ef72 Audit YounGo payment flow with local DB
e5f6a5f Document live cPanel client demo handoff
0216016 Add YounGo cPanel deployment runbook
9cd4b3d Prepare YounGo sanitized client admin export
49aa360 Test YounGo cPanel package restore locally
2337d79 Prepare YounGo client package
```

The expected baseline is present: branch `analysis/cms-audit`, clean worktree before this planning file, and latest commit `20c21f0` contains PAYMENT.CONFIG.2 architecture/schema design.

## B. Schema SQL Review

Reviewed file:

```text
scripts/phase_2/payment_config_2_youngo_payment_schema_proposed.sql
```

Execution status:

- The SQL is clearly marked `STATUS: NOT EXECUTED`.
- It was reviewed only as text in this phase.
- It was not run, copied into a DB client, applied, or tested against the database.

Schema shape:

- `youngo_checkout_orders` remains the order anchor.
- `youngo_payment_transactions` is proposed as the Paymob event/transaction ledger.
- Existing `youngo_course_access`, `youngo_user_subscriptions`, and `youngo_coupon_usages` receive checkout-order indexes/unique guards for later idempotent issuance.
- The proposal avoids hard foreign keys, matching current Phase 2 schema style.

Positive findings:

- EGP is explicit through `currency DEFAULT 'EGP'`.
- `total_amount_cents` supports Paymob's smallest-currency-unit amount requirement.
- `order_reference` gives YounGo a merchant-side reference to send to Paymob.
- `idempotency_key` exists on both orders and transactions.
- Gateway references cover Paymob intention, order, transaction, and integration IDs.
- Transaction rows include HMAC state, verification source, payload hash, redacted payload storage, and event status.
- Order rows include return/webhook timestamps, payment status timestamps, failure fields, and entitlement issuance tracking.
- Unique constraints on order reference, order idempotency key, transaction idempotency key, provider transaction/event, and checkout-order entitlement links support safe duplicate callback handling.

Compatibility notes:

- The proposed `ADD COLUMN IF NOT EXISTS` and `ADD INDEX IF NOT EXISTS` syntax is appropriate for the local MariaDB 10.4-style environment already used by prior Phase 2 work, but it should still be preflighted against the actual target local DB version before apply.
- MySQL/MariaDB DDL auto-commits, so this is not transaction-rollback-safe. A full fresh DB backup is the primary rollback mechanism.
- Unique indexes on nullable columns are acceptable in MariaDB because multiple `NULL` values are allowed. Before apply, preflight must confirm no non-null duplicates in any existing checkout/access/coupon rows.
- `youngo_checkout_orders.status` changes the target default to `draft`; earlier diagnostics or schema assumptions that reference `completed` must be updated or compatibility-mapped to `paid`.
- `payment_gateway` on orders and `gateway_provider` on transactions should both normalize to `paymob` in implementation to avoid reporting ambiguity.
- `provider_intent_id` should be treated as the Paymob Intention ID despite the shorter internal column name.
- `uniq_yca_checkout_order` and `uniq_yus_checkout_order` assume a single direct course access row or subscription row per checkout order. That matches the CONFIG.2 direct checkout design and should be revisited only if bundle orders are introduced later.
- `uniq_ycu_checkout_order` assumes one coupon usage per checkout order. That matches the current coupon design direction.

Rollback needs before any future execution:

- Create a timestamped local DB backup immediately before applying schema.
- Add a reviewed down/rollback SQL file where practical, but treat full DB restore as authoritative because DDL auto-commits.
- Preflight existing row counts, duplicate candidates, existing columns, and existing indexes.
- Apply only in a local approved schema phase, never during planning and never on cPanel.

Safe apply readiness:

- The proposal is suitable as a reviewed starting point for a local schema implementation phase after backup and owner approval.
- It is not yet an executable migration artifact because it lacks a paired down script, preflight script, post-apply diagnostic, and explicit approval record.

## C. Implementation Files Likely Needed

Future controllers:

- `application/controllers/Youngo_checkout.php` - learner checkout start, pay, return, and status pages.
- `application/controllers/Youngo_paymob.php` - webhook-safe Paymob callback endpoint and optional inquiry/reconciliation endpoint.
- `application/controllers/Youngo_payments_admin.php` - later admin payment review/retry surface.
- `application/controllers/Home.php` - only in a later CTA phase, to route YounGo-managed CTAs into the new checkout after local QA.
- `application/controllers/Payment.php` - should remain legacy. Do not add the Paymob webhook here because its constructor depends on session payment state.

Future models/services:

- `application/models/Youngo_checkout_model.php` - local order creation, lookup, status transitions, and active pending order reuse.
- `application/models/Youngo_payment_model.php` - transaction recording, webhook processing state, reconciliation state, and entitlement dispatch.
- `application/models/Youngo_paymob_model.php` or `application/libraries/Youngo_paymob_service.php` - Paymob request payload building, Unified Checkout URL handling, HMAC verification, and later API calls.
- `application/models/Youngo_entitlement_write_model.php` - add checkout-issued course/subscription issuance methods after schema is applied.
- `application/models/Youngo_entitlement_model.php` - likely read-only checks only; avoid expanding it into payment writes.
- `application/models/Youngo_subscription_model.php` - plan validation for purchasable EGP subscription checkout.

Future helpers/config:

- `application/config/youngo_payment.php` - committed non-secret defaults and placeholder keys only.
- `application/config/youngo_payment.local.php` - optional local-only ignored override, never committed.
- `.gitignore` - later update to ignore local secret config if that pattern is used.
- `application/helpers/youngo_payment_helper.php` - optional status labels, masking/redaction helpers, and amount/currency normalization.

Future routes:

- `application/config/routes.php` - add YounGo checkout, Paymob webhook, and admin review routes only in implementation phases.

Future views:

- `application/views/frontend/youngo/checkout_review.php`
- `application/views/frontend/youngo/checkout_pay.php`
- `application/views/frontend/youngo/checkout_return.php`
- `application/views/frontend/youngo/checkout_status.php`
- `application/views/backend/admin/youngo_payments.php`
- `application/views/backend/admin/youngo_payment_view.php`
- Existing course-card/course-detail/wishlist views only in a later CTA exposure phase after sandbox QA passes.

Future diagnostics/reports:

- `scripts/phase_2/youngo_payment_schema_1_diagnostic.php`
- `scripts/phase_2/youngo_payment_config_file_1_diagnostic.php`
- `scripts/phase_2/youngo_payment_order_1_diagnostic.php`
- `scripts/phase_2/youngo_payment_paymob_adapter_1_diagnostic.php`
- `scripts/phase_2/youngo_payment_webhook_1_diagnostic.php`
- `scripts/phase_2/youngo_payment_entitlement_1_diagnostic.php`
- `docs/qa/youngo_payment_schema_1_apply_report.md`
- `docs/qa/youngo_payment_config_file_1_report.md`
- `docs/qa/youngo_payment_order_1_report.md`
- `docs/qa/youngo_payment_paymob_adapter_1_report.md`
- `docs/qa/youngo_payment_webhook_1_report.md`
- `docs/qa/youngo_payment_entitlement_1_report.md`
- `docs/qa/youngo_payment_qa_1_sandbox_e2e_report.md`

## D. Proposed Phase Breakdown

### PAYMENT.SCHEMA.1 - Apply Payment Schema Locally With Backup

Purpose:

- Convert the CONFIG.2 proposed SQL into a controlled local schema migration package.

Allowed changes:

- Create reviewed up/down SQL files under `database/phase_2/` or `scripts/phase_2/`.
- Create schema preflight and post-apply diagnostics.
- Create a timestamped local DB backup.
- Apply schema locally only after explicit owner approval.

Disallowed changes:

- No cPanel/live server access.
- No Paymob credentials.
- No Paymob API calls.
- No public checkout CTAs.
- No Root Admin changes.
- No legacy gateway enabling.

DB write status:

- DB writes allowed only in this future phase, only locally, only for approved schema DDL after backup.

Required tests:

- Preflight duplicate/index/column check.
- Post-apply column/index/default check.
- Protected-row count check for `payment`, `enrol`, `youngo_course_access`, `youngo_user_subscriptions`, `youngo_manual_grants`, `youngo_checkout_orders`, and coupon tables.
- Existing relevant Phase 2 diagnostics that cover entitlement and CTA boundaries.
- `git diff --check`.

Rollback requirement:

- Restore the pre-apply local DB backup if anything fails.
- Use down SQL only if it has been separately reviewed and is safer than full restore.

Commit boundary:

- Commit only schema files, diagnostics, and the apply report. No runtime Paymob/order code.

### PAYMENT.CONFIG.FILE.1 - Add Non-Secret Paymob Config Placeholder/Reader

Purpose:

- Establish a local-safe configuration mechanism without storing secrets.

Allowed changes:

- Add a committed placeholder config file with non-secret defaults.
- Add a config reader/helper that loads local overrides if present.
- Add `.gitignore` protection for local override files if needed.
- Add diagnostics that fail if placeholder-only mode is not respected.

Disallowed changes:

- No real keys, HMAC secrets, API keys, merchant secrets, or dashboard values in Git/docs.
- No DB credential storage.
- No live mode.
- No Paymob HTTP requests.

DB write status:

- No DB writes.

Required tests:

- PHP syntax check for touched PHP files.
- Diagnostic proving required keys exist as placeholders.
- Diagnostic proving local override filename is ignored and not committed.
- Secret-pattern scan of changed files.
- `git diff --check`.

Rollback requirement:

- Revert file changes only; no DB rollback needed.

Commit boundary:

- Commit config placeholder, reader, ignore rule if needed, diagnostics, and report.

### PAYMENT.ORDER.1 - Create Local Checkout Order Service

Purpose:

- Implement YounGo-owned local order lifecycle without Paymob calls.

Allowed changes:

- Add `Youngo_checkout_model` and minimal checkout controller/service methods.
- Generate `order_reference` and `idempotency_key`.
- Validate learner, course/plan, access mode, EGP amount, and active pending order reuse.
- Write local `youngo_checkout_orders` rows in controlled local QA only.

Disallowed changes:

- No public CTA exposure.
- No gateway redirects.
- No Paymob API calls.
- No entitlement issuance.
- No legacy `payment` or `enrol` writes.

DB write status:

- Local DB writes allowed only after PAYMENT.SCHEMA.1, with backup and controlled cleanup/restore.

Required tests:

- Order create for approved course/plan fixture.
- Duplicate pending order reuse/rejection.
- Invalid currency/non-purchasable plan rejection.
- Expired/cancelled state behavior.
- Confirm no writes to `payment`, `enrol`, entitlement tables, or coupon usage tables.
- `git diff --check`.

Rollback requirement:

- Restore DB backup or clean controlled test rows using reviewed cleanup after approval.

Commit boundary:

- Commit local order service/controller stubs, diagnostics, and report only.

### PAYMENT.PAYMOB.ADAPTER.1 - Add Paymob Adapter Skeleton Without Real Credentials

Purpose:

- Add the Paymob abstraction and payload builder while keeping network disabled.

Allowed changes:

- Add Paymob service/model skeleton.
- Build Intention API payload from a local order using placeholders.
- Build Unified Checkout redirect/page data shape.
- Validate config readiness and EGP integration ID presence.
- Add HMAC helper function signatures and fixture-only tests if no secrets are needed.

Disallowed changes:

- No outbound HTTP calls.
- No real credentials.
- No payment start from public pages.
- No webhook DB processing yet unless deferred to webhook phase.

DB write status:

- No DB writes expected.

Required tests:

- PHP syntax checks.
- Payload-shape diagnostic with fake placeholder data.
- Network-disabled diagnostic or code guard.
- Secret-pattern scan.
- `git diff --check`.

Rollback requirement:

- Revert files only; no DB rollback needed.

Commit boundary:

- Commit adapter skeleton, tests/diagnostic, and report only.

### PAYMENT.WEBHOOK.1 - Add Webhook Route And HMAC Verification Skeleton

Purpose:

- Add a webhook-safe endpoint and transaction event recording path using fixture payloads first.

Allowed changes:

- Add route for `/payment/paymob/webhook`.
- Add `Youngo_paymob` controller with no session-dependent constructor.
- Add raw payload parsing, redaction, HMAC calculation, timing-safe comparison, status mapping, and duplicate handling.
- Record `youngo_payment_transactions` rows only if schema is applied and only in controlled local tests.

Disallowed changes:

- No entitlement issuance.
- No reliance on return URL for access.
- No real Paymob callbacks until sandbox QA phase.
- No public CTAs.
- No legacy payment/enrol writes.

DB write status:

- Local transaction writes allowed only if schema exists and fake fixture tests are approved.

Required tests:

- HMAC pass fixture.
- HMAC fail fixture.
- Missing HMAC rejection.
- Duplicate event handling.
- Amount/currency/order-reference mismatch rejection.
- Safe 200 behavior for known duplicates where appropriate.
- Confirm no entitlement, payment, enrol, or coupon rows are created.
- `git diff --check`.

Rollback requirement:

- Restore local backup or remove controlled fixture rows after approved cleanup.

Commit boundary:

- Commit webhook skeleton, route, transaction logic, diagnostics, and report only.

### PAYMENT.ENTITLEMENT.1 - Issue Entitlement After Verified Payment

Purpose:

- Connect verified paid orders to the existing YounGo entitlement write boundary.

Allowed changes:

- Add checkout issuance methods to `Youngo_entitlement_write_model`.
- Issue direct course access for paid course purchases.
- Issue user subscription rows for paid subscription orders.
- Mark order entitlement fields idempotently.
- Add failure handling for paid-but-entitlement-failed orders.

Disallowed changes:

- No Paymob real calls unless already approved in a separate sandbox phase.
- No public CTA exposure.
- No default legacy `Crud_model::enrol_student()` call.
- No Root Admin changes.

DB write status:

- Local DB writes allowed after backup for controlled paid-order fixtures.

Required tests:

- Verified paid course order creates exactly one `youngo_course_access` row.
- Verified paid subscription order creates exactly one `youngo_user_subscriptions` row.
- Duplicate webhook/retry does not duplicate entitlement.
- Existing active access is handled without duplicate rows.
- Payment paid but issuance failure remains recoverable and does not re-charge.
- My Courses/My Access reflect issued access.
- Legacy `payment` and `enrol` remain unchanged unless a separate compatibility sync is explicitly approved.
- `git diff --check`.

Rollback requirement:

- Restore DB backup after controlled QA, or apply reviewed cleanup only if owner approves.

Commit boundary:

- Commit entitlement issuance methods, integration tests/diagnostics, and report only.

### PAYMENT.QA.1 - Local Paymob Sandbox End-To-End Test

Purpose:

- Test the approved local sandbox flow with owner-provided sandbox credentials supplied outside Git.

Allowed changes:

- Use local-only ignored config.
- Use a public HTTPS tunnel for webhook delivery.
- Start a Paymob sandbox payment for a controlled fixture.
- Verify return page, webhook verification, transaction record, order status, and entitlement issuance.

Disallowed changes:

- No live credentials.
- No production mode.
- No cPanel/live server access.
- No broad public CTA rollout.
- No secret logging.

DB write status:

- Local DB writes allowed under backup/restore discipline.

Required tests:

- Create order in EGP.
- Start Paymob sandbox Unified Checkout.
- Complete successful sandbox card payment.
- Return page shows UX state only.
- Webhook HMAC verifies and marks order `paid`.
- Entitlement is issued once.
- Duplicate webhook is idempotent.
- Failed/cancelled payment does not issue access.
- My Courses/My Access update after successful issuance.
- Logs and reports contain no secrets.
- `git diff --check`.

Rollback requirement:

- Restore the pre-QA local DB backup and remove local-only config/tunnel artifacts.

Commit boundary:

- Commit only code proven by sandbox QA plus sanitized report. Do not commit local config.

### PAYMENT.CTA.1 - Controlled Checkout CTA Exposure

Purpose:

- Replace checkout-not-ready messages with the new YounGo checkout entry only after sandbox QA passes.

Allowed changes:

- Update YounGo course card/detail/wishlist CTA paths for approved access modes.
- Keep feature flag or environment guard if needed.
- Keep legacy cart/buy/free-enrol boundary protections.

Disallowed changes:

- No production public rollout before owner approval.
- No inherited gateway usage.
- No bypass around order/webhook/entitlement lifecycle.

DB write status:

- No DB schema writes. Local order/payment writes only during controlled CTA QA.

Required tests:

- No-access paid/subscription course routes to YounGo checkout.
- Legacy cart/buy/free-enrol shortcuts remain blocked for YounGo-managed courses.
- Successful sandbox payment grants access.
- Failed/pending payment does not grant access.
- Logged-out flow preserves intended checkout path safely.
- `git diff --check`.

Rollback requirement:

- Revert CTA file changes and restore DB backup after QA if needed.

Commit boundary:

- Commit CTA routing changes and CTA QA report separately from schema/config/webhook work.

## E. Acceptance Criteria Per Phase

Global acceptance criteria for every future phase:

- The phase must start with `git branch --show-current`, `git status --short`, and `git log --oneline -10`.
- Work must stay on `analysis/cms-audit` unless the owner explicitly changes branch strategy.
- No deployment, push, cPanel access, live payment activation, real credential storage, Root Admin change, or public checkout CTA exposure unless that exact future phase authorizes it.
- Any phase touching DB writes must first create and document a fresh local backup.
- Every report must state what was changed, what was not changed, validation results, rollback path, and final git status.
- Each phase should be a separate commit boundary after review; do not batch schema, config, Paymob calls, webhook, entitlement issuance, and CTA exposure together.

Phase-specific acceptance criteria:

| Phase | Allowed change boundary | DB writes | Required acceptance signal |
| --- | --- | --- | --- |
| `PAYMENT.SCHEMA.1` | Schema files, preflight/post-apply diagnostics, apply report | Yes, local schema only after backup/approval | Proposed columns/indexes exist, protected row counts stable, rollback backup exists |
| `PAYMENT.CONFIG.FILE.1` | Placeholder config, reader, ignore rule, diagnostics | No | Placeholder config loads, secrets absent from Git/docs, live mode unavailable |
| `PAYMENT.ORDER.1` | Local order service and controlled order lifecycle | Yes, local fixture rows only | EGP order lifecycle works, duplicates controlled, no Paymob/enrol/payment/access writes |
| `PAYMENT.PAYMOB.ADAPTER.1` | Adapter skeleton and payload builder, no network | No | Intention payload shape validates with placeholders, no outbound requests, no secrets |
| `PAYMENT.WEBHOOK.1` | Webhook route, HMAC skeleton, transaction recording | Optional local fixture rows | HMAC pass/fail/duplicate/mismatch fixtures behave correctly, no access issuance |
| `PAYMENT.ENTITLEMENT.1` | Checkout-issued entitlement methods | Yes, local fixtures | Verified paid order issues exactly one entitlement and is retry-safe |
| `PAYMENT.QA.1` | Local sandbox E2E using local-only credentials | Yes, local sandbox rows | Paymob sandbox success/failure paths verified, webhook is source of truth, no secrets logged |
| `PAYMENT.CTA.1` | Controlled CTA routing to YounGo checkout | Local QA rows only | YounGo-managed courses use new checkout and never legacy cart/buy/free-enrol shortcuts |

## F. Owner Inputs Required

Before schema execution:

- Explicit approval to apply payment schema locally.
- Confirmed local DB backup target/location.
- Approval of status names, especially `paid` versus any older `completed` wording.
- Approval that one checkout order maps to one direct course access or one subscription for the first release.

Before config/Paymob implementation:

- Paymob sandbox dashboard access for the owner or developer.
- Current Paymob sandbox credential names available in the dashboard, supplied only through local ignored config.
- Paymob sandbox Secret Key.
- Paymob sandbox Public Key.
- Paymob sandbox HMAC secret.
- EGP card integration ID for sandbox card payments.
- Confirmation that Unified Checkout is enabled for the account.
- Confirmation whether transaction inquiry requires API Key/auth token for the selected account/API path.
- Any required merchant/account identifier if Paymob dashboard or current docs require it for the selected flow.
- Confirmation that iframe ID is not required; if required, provide the sandbox iframe ID outside Git.

Before webhook/sandbox QA:

- Public HTTPS tunnel decision for local callbacks, such as ngrok or cloudflared.
- Local base URL and tunnel base URL to use for `notification_url` and `redirection_url`.
- Official Paymob sandbox test card/wallet details from dashboard/docs, supplied outside Git and not written to reports.
- Final local QA learner, likely QA learner user 8 with password supplied privately by the owner when needed.
- Final test course and/or subscription plan for checkout.
- Approval whether sandbox QA should restore DB backup afterward or preserve sanitized local rows for inspection.

Before entitlement/compatibility work:

- Confirmation whether YounGo entitlement rows alone are sufficient for learner surfaces.
- Decision on whether legacy `payment` rows are needed later for invoice/revenue compatibility.
- Decision on whether legacy `enrol` rows are ever needed as a compatibility mirror after YounGo entitlement issuance.
- Approval for any admin payment review/retry capability and which capability should guard it.

## G. Rollback/Safety Plan

Planning phase safety:

- This CONFIG.3 phase created a report only.
- No DB connection was used.
- No SQL was executed.
- No credentials were added or printed.
- No routes, controllers, models, config, views, or live files were changed.

Future DB phase safety:

- Back up local DB before every schema or payment-flow write phase.
- Record preflight row counts for payment, enrolment, entitlement, checkout, coupon, and progress/watch tables.
- Treat DB restore as the main rollback path because MySQL/MariaDB DDL auto-commits.
- Keep any down SQL reviewed but secondary.
- Never apply schema to live cPanel during local implementation phases.

Future credential safety:

- Store real sandbox values only in local ignored config or environment variables.
- Commit only placeholders and example keys.
- Mask all credential-like fields in logs, diagnostics, reports, and admin views.
- Never echo Paymob Secret Key, API Key, HMAC secret, or test card details.
- Keep sandbox and live config physically separated.

Future payment safety:

- Webhook/HMAC verification is the source of truth.
- Return URL is UX-only and must not issue access.
- Do not use inherited Academy gateway rows for YounGo payments.
- Do not call legacy `Payment::success_course_payment()` for YounGo checkout.
- Do not expose public checkout CTAs until order, webhook, idempotency, and entitlement issuance are verified in sandbox.

## H. Recommended Next Phase

Recommended next phase:

```text
PAYMENT.SCHEMA.1 - Apply Payment Schema Locally With Backup
```

Suggested scope:

1. Create a fresh local DB backup.
2. Convert `scripts/phase_2/payment_config_2_youngo_payment_schema_proposed.sql` into approved up/down migration artifacts.
3. Add a schema preflight diagnostic for duplicate candidate values, existing columns, existing indexes, and protected row counts.
4. Apply only locally after explicit owner approval.
5. Run post-apply diagnostics and existing entitlement/CTA boundary diagnostics.
6. Write a sanitized apply report with no credentials.

Do not proceed to config files, Paymob adapter, webhook, or entitlement issuance until the schema baseline is approved and stable.

## I. Git Status

Expected status after creating this planning report:

```text
?? docs/qa/youngo_payment_config_3_implementation_plan.md
```

No code files, route files, SQL execution state, database rows, credentials, Root Admin records, gateway settings, or checkout CTAs were changed in this phase.
