# YounGo Payment Schema 1 Apply Report

Phase: PAYMENT.SCHEMA.1 - Apply Payment Schema Locally With Backup

Scope: local database schema apply only. No deployment, push, commit, live cPanel access, real payment activation, real credentials, Root Admin changes, public checkout CTA exposure, Paymob API calls, or live DB changes were performed.

## A. Current Branch/Status

Start command results:

```text
git branch --show-current
analysis/cms-audit

git status --short
<clean>

git log --oneline -10
6111cf7 Plan YounGo payment implementation phases
20c21f0 Design YounGo payment architecture and schema
dd601bd Plan YounGo Paymob sandbox integration
dda25bc Harden YounGo legacy payment entry points
a2e1f81 Document YounGo payment DB baseline
727ef72 Audit YounGo payment flow with local DB
e5f6a5f Document live cPanel client demo handoff
0216016 Add YounGo cPanel deployment runbook
9cd4b3d Prepare YounGo sanitized client admin export
49aa360 Test YounGo cPanel package restore locally
```

The expected baseline was confirmed: branch `analysis/cms-audit`, clean worktree before this phase, and latest commit `6111cf7` includes PAYMENT.CONFIG.3.

## B. Backup Created

A fresh local DB backup was created before successful schema application.

```text
Path: D:\Work\YounGo\backups\youngo_school_before_payment_schema_1_2026_07_20_230736.sql
Size: 960056 bytes
SHA256: 6bea01ef66d4289fca93f06c86bf77e47e525b58559b31dae7e3e803173452d4
Tables dumped: 64
DB server version: 10.4.32-MariaDB
```

Backup notes:

- The backup is outside the Git repository.
- The backup may contain local table data and must not be committed.
- No database credentials were printed.

## C. SQL Safety Review

Reviewed:

```text
scripts/phase_2/payment_config_2_youngo_payment_schema_proposed.sql
```

Safety findings:

- No destructive `DROP`, `TRUNCATE`, `DELETE`, `UPDATE`, or `INSERT` data statements were present in executable SQL.
- No credential values were present.
- No live URLs were present.
- No Root Admin changes were present.
- No `payment_gateways` activation/status updates were present.
- EGP defaults were present for the payment schema targets.
- Idempotency fields were present.
- HMAC/verification tracking fields were present.
- Entitlement issuance tracking fields were present.
- Syntax is compatible with the local MariaDB 10.4.32 environment, but the final successful apply used smaller equivalent idempotent DDL steps rather than one multi-statement runner.

Important apply note:

- The first apply runner attempted to execute the approved SQL through one multi-statement mysqli call and the local MariaDB connection dropped with `MySQL server has gone away`.
- MariaDB was restarted locally through XAMPP only.
- Post-restart inspection showed the payment schema changes had not persisted: `youngo_checkout_orders` still had `status DEFAULT 'pending'`, `currency DEFAULT 'USD'`, and `youngo_payment_transactions` did not exist.
- The schema was then applied successfully with smaller idempotent DDL steps matching the approved CONFIG.2 schema.

No live/cPanel database was contacted.

## D. Schema Apply Result

Final schema apply result:

```text
schema_apply: completed
executed_statement_count: 38
duplicate_preflight:
  youngo_checkout_orders.idempotency_key: 0
  youngo_course_access.checkout_order_id: 0
  youngo_user_subscriptions.checkout_order_id: 0
  youngo_coupon_usages.checkout_order_id: 0
```

The local schema is now applied and the diagnostic confirms the expected tables, columns, defaults, and indexes.

No data rows were inserted, updated, or deleted by the successful schema apply.

## E. Tables/Columns/Indexes Created Or Confirmed

Tables confirmed:

- `youngo_checkout_orders`
- `youngo_payment_transactions`
- `youngo_course_access`
- `youngo_user_subscriptions`
- `youngo_coupon_usages`

Checkout order changes confirmed:

- Added `order_reference`
- Added `total_amount_cents`
- Added `gateway_environment`
- Added `provider_order_id`
- Added `idempotency_key`
- Added `last_hmac_verified`
- Added `entitlement_issued`
- Added `entitlement_issuance_status`
- Added `entitlement_course_access_id`
- Added `entitlement_subscription_id`
- Added `entitlement_issued_at`
- Added `entitlement_issuance_error`
- Added `failure_code`
- Added `failure_message`
- Added `payment_started_at`
- Added `return_seen_at`
- Added `last_webhook_at`
- Added `paid_at`
- Added `failed_at`
- Added `cancelled_at`
- Added `expired_at`
- Changed `youngo_checkout_orders.currency` default to `EGP`
- Changed `youngo_checkout_orders.status` default to `draft`

Payment transaction table confirmed:

- `youngo_payment_transactions` exists.
- Currency default is `EGP`.
- Status default is `received`.
- It includes Paymob/provider references, HMAC fields, verification source, payload hash, redacted payload storage, idempotency key, and processing timestamps.

Indexes/unique constraints confirmed:

- `youngo_checkout_orders.uniq_yco_order_reference`
- `youngo_checkout_orders.uniq_yco_idempotency_key`
- `youngo_checkout_orders.idx_yco_provider_order`
- `youngo_checkout_orders.idx_yco_provider_transaction`
- `youngo_checkout_orders.idx_yco_gateway_env_status`
- `youngo_checkout_orders.idx_yco_entitlement_status`
- `youngo_payment_transactions.uniq_ypt_idempotency_key`
- `youngo_payment_transactions.uniq_ypt_provider_tx_event`
- `youngo_course_access.uniq_yca_checkout_order`
- `youngo_course_access.idx_yca_checkout_payment`
- `youngo_course_access.idx_yca_user_course_source_status`
- `youngo_user_subscriptions.uniq_yus_checkout_order`
- `youngo_user_subscriptions.idx_yus_checkout_payment`
- `youngo_user_subscriptions.idx_yus_user_source_status`
- `youngo_coupon_usages.uniq_ycu_checkout_order`
- `youngo_coupon_usages.idx_ycu_user_coupon_order`

## F. Diagnostic Result

Created and ran:

```text
scripts/phase_2/youngo_payment_schema_1_diagnostic.php
```

Result:

```text
phase: PAYMENT.SCHEMA.1
ok: true
failed_checks: []
db_server_version: 10.4.32-MariaDB
```

Key diagnostic confirmations:

- Required payment schema tables exist.
- Required checkout and transaction columns exist.
- EGP defaults are present.
- Status defaults are present.
- Required indexes and unique constraints are present.
- Legacy gateway state remains unchanged:
  - active gateways: 15
  - active non-EGP gateways: 15
  - active EGP gateways: 0
- Public checkout/payment routes were not added.
- YounGo CTA boundary source markers are still present.
- Checkout and transaction rows remain empty.
- Entitlement/payment/coupon rows remain clean.

Protected table counts after schema apply:

```text
payment: 0
enrol: 1
youngo_checkout_orders: 0
youngo_payment_transactions: 0
youngo_course_access: 0
youngo_user_subscriptions: 0
youngo_manual_grants: 0
youngo_coupon_usages: 0
watch_histories: 0
watched_duration: 0
```

Validation commands:

```text
php scripts/phase_2/youngo_payment_schema_1_diagnostic.php
PASS

php -l scripts/phase_2/youngo_payment_schema_1_diagnostic.php
No syntax errors detected

git diff --check
<no output>
```

## G. What Was Not Changed

Not changed:

- No deployment.
- No push.
- No commit.
- No live cPanel server.
- No live/cPanel DB.
- No real payment activation.
- No real credentials.
- No printed secrets.
- No Root Admin record.
- No user records.
- No legacy payment gateway activation flags.
- No public checkout CTAs.
- No Paymob config.
- No Paymob API calls.
- No routes.
- No controllers.
- No models.
- No views.
- No legacy `payment` rows.
- No legacy `enrol` rows.
- No YounGo checkout order rows.
- No YounGo payment transaction rows.
- No YounGo entitlement rows.
- No manual grant rows.
- No coupon usage rows.

## H. Rollback/Down SQL Created

Created:

```text
scripts/phase_2/payment_schema_1_down.sql
```

Rollback status:

- The down SQL is clearly marked for local PAYMENT.SCHEMA.1 rollback only.
- It is marked `REVIEW BEFORE EXECUTION`.
- It is marked `NOT EXECUTED IN THIS PHASE UNLESS MANUAL ROLLBACK IS REQUIRED`.
- It was not executed.

Primary rollback:

```text
D:\Work\YounGo\backups\youngo_school_before_payment_schema_1_2026_07_20_230736.sql
```

Because MariaDB DDL auto-commits, restoring the fresh DB backup is the safest rollback method. The down SQL is secondary and should be used only after review.

## I. Risks/Blockers

- The first multi-statement apply runner caused a local MariaDB connection drop. Final schema state is healthy after local restart and idempotent stepwise apply, but future schema phases should avoid broad multi-statement execution and prefer preflighted stepwise DDL.
- `youngo_checkout_orders.status` now defaults to `draft`; older diagnostics or code that assume `pending` or `completed` need compatibility review before order implementation.
- `youngo_checkout_orders.currency` now defaults to `EGP`; this is intended for YounGo payment readiness but does not activate payments.
- No Paymob config mechanism exists yet.
- No order lifecycle, webhook handler, HMAC verification implementation, Paymob adapter, or entitlement issuance implementation exists yet.
- Inherited active legacy gateways remain active/non-EGP in the DB and remain unsuitable for YounGo payments, though source boundaries continue to suppress them.

## J. Recommended Next Phase

Recommended next phase:

```text
PAYMENT.CONFIG.FILE.1 - Add Non-Secret Paymob Config Placeholder/Reader
```

Scope for that phase:

- Add committed placeholder-only config.
- Add local-only ignored override strategy.
- Add a safe config reader.
- Add diagnostics proving no secrets are committed and live mode is unavailable.
- Do not call Paymob.
- Do not expose checkout CTAs.

## K. Git Status

Expected final status after this phase:

```text
?? docs/qa/youngo_payment_schema_1_apply_report.md
?? scripts/phase_2/payment_schema_1_down.sql
?? scripts/phase_2/youngo_payment_schema_1_diagnostic.php
```

No existing tracked source file changes are expected.
