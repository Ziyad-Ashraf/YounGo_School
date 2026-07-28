# PAYMENT.MANUAL.INSTAPAY.CONFIG.1 Report

Phase: PAYMENT.MANUAL.INSTAPAY.CONFIG.1 — Add Manual Instapay Payment Target Configuration

## A. Current Branch/Status

- Branch: `analysis/cms-audit`
- Starting worktree: clean
- Latest expected commit present: `a91669e Add manual Instapay submission schema foundation`
- Scope: additive local schema/config support, Root-only settings UI, read-only/inert model helpers, diagnostic, and report

## B. Backup Created

Fresh local DB backup was created before applying the additive config schema:

- Path: `D:/Work/YounGo\backups\youngo_school_before_payment_manual_instapay_config_1_2026_07_26_140611.sql`
- Size: `1122337` bytes
- SHA256: `04d3b61408184ba97f3740983c6eae7f1747750eaabf1373d371ff75babce29e`

The backup helper used local CodeIgniter DB configuration internally and did not print database credentials.

## C. Files Inspected

- `docs/qa/youngo_payment_manual_instapay_schema_1_report.md`
- `docs/qa/youngo_payment_manual_instapay_coupon_checkout_plan_1_report.md`
- `docs/qa/youngo_payment_paymob_client_handoff_1.md`
- `application/controllers/Youngo_payment_settings.php`
- `application/models/Youngo_payment_config_model.php`
- `application/models/Youngo_payment_config_audit_model.php`
- `application/models/Youngo_instapay_payment_model.php`
- `application/views/backend/admin/youngo_payment_settings.php`
- `application/config/routes.php`
- `scripts/phase_2/youngo_payment_manual_instapay_schema_1_diagnostic.php`

## D. Files Changed

- `application/controllers/Youngo_payment_settings.php`
- `application/models/Youngo_payment_config_model.php`
- `application/models/Youngo_payment_config_audit_model.php`
- `application/models/Youngo_instapay_payment_model.php`
- `application/views/backend/admin/youngo_payment_settings.php`
- `scripts/phase_2/payment_manual_instapay_config_1_up.sql`
- `scripts/phase_2/payment_manual_instapay_config_1_down.sql`
- `scripts/phase_2/youngo_payment_manual_instapay_config_1_diagnostic.php`
- `docs/qa/youngo_payment_manual_instapay_config_1_report.md`

## E. Config Storage Summary

The safest storage path is the existing YounGo payment config table, `youngo_payment_provider_configs`, using:

- `provider = instapay_manual`
- `mode = manual`

Additive columns applied to that existing table:

- `instapay_enabled_for_checkout` default `0`
- `instapay_target_label`
- `instapay_target_address`
- `instapay_target_link`
- `instapay_instructions_ar`
- `instapay_instructions_en`
- `instapay_max_upload_mb` default `5.00`
- `instapay_allowed_mimes` default `image/jpeg,image/png,image/webp`

The up SQL only alters `youngo_payment_provider_configs`. The down SQL removes only these phase-added columns.

## F. Admin/Root Settings UI Summary

The existing Root-only YounGo payment settings page now includes a separate Manual Instapay setup card:

- Enable Instapay manual checkout flag
- Target label
- Instapay address
- Instapay link
- Arabic instructions
- English instructions
- Max screenshot size MB

The page remains protected by the existing `require_root_admin()` / `youngo_is_root_admin()` policy. It does not add learner UI, upload UI, admin review UI, or checkout payment behavior.

## G. Model Helper Summary

`Youngo_payment_config_model` now supports:

- `upsert_dashboard_instapay_manual_config($data, $actor_user_id = null)`
- `get_instapay_config()`
- `is_instapay_checkout_enabled()`
- `build_instapay_target_snapshot($language = 'english')`
- `instapay_schema_ready()`

`Youngo_instapay_payment_model` exposes read-only wrappers for config and target snapshot helpers so later submission phases can snapshot target details without hardcoding them in checkout views.

`Youngo_payment_config_audit_model` now supports redacted audit summaries for provider `instapay_manual`, mode `manual`.

## H. Default Enabled/Disabled Behavior

Manual Instapay checkout is disabled by default:

- missing config row resolves to safe defaults
- `instapay_enabled_for_checkout` defaults to `0`
- diagnostics confirmed `is_instapay_checkout_enabled()` returned false before and after the temporary config write

Even if config is saved, this phase does not wire checkout display, upload, review, approval, or access issuance.

## I. Diagnostic Result

Validation diagnostic:

```text
php scripts/phase_2/youngo_payment_manual_instapay_config_1_diagnostic.php
status: PASS
db_writes: temporary_instapay_manual_config_row_saved_then_restored
cleanup: completed
approved_instapay_review_statuses: pending_review, approved, rejected
instapay_config_existed_before: no
current_instapay_enabled_before: no
```

Prior schema diagnostic was also re-run:

```text
php scripts/phase_2/youngo_payment_manual_instapay_schema_1_diagnostic.php
summary: PASS
checks_total: 112
checks_failed: 0
```

## J. DB Impact/Cleanup

Persistent DB impact:

- Additive columns were applied to `youngo_payment_provider_configs`.
- No config row remained after the diagnostic because no `instapay_manual/manual` row existed before the diagnostic.
- No checkout orders, Instapay submissions, payment rows, enrolment rows, entitlement rows, subscription rows, or manual grant rows were created.

Diagnostic protected counts were restored:

- `youngo_payment_provider_configs`: `0 -> 0`
- `youngo_payment_config_audit_logs`: `0 -> 0`
- `youngo_instapay_payment_submissions`: `0 -> 0`
- `youngo_checkout_orders`: `0 -> 0`
- `youngo_course_access`: `0 -> 0`
- `youngo_user_subscriptions`: `0 -> 0`
- `youngo_manual_grants`: `0 -> 0`
- `payment`: `0 -> 0`
- `enrol`: `1 -> 1`

## K. Checkout/Payment/Access Safety

This phase did not:

- enable Paymob
- expose card or wallet payments
- expose learner Instapay checkout/upload UI
- add Instapay upload or admin review routes
- approve/reject any payment
- create access, enrolment, entitlement, subscription, or manual grant rows
- change checkout order creation behavior
- modify Root Admin identity or credentials

Manual Instapay status model remains limited to:

- `pending_review`
- `approved`
- `rejected`

## L. Remaining Risks/Blockers

- The config UI was validated by code diagnostics, not browser QA in this phase.
- Final real Instapay target values must be entered by Root Admin later and should be treated as operational payment routing data.
- Upload storage, protected file preview, admin review decisions, and access issuance from `approved_at` remain unimplemented.
- Later checkout work must keep Paymob/card/wallet behavior disabled unless explicitly approved.

## M. Recommended Next Phase

Recommended next phase:

`PAYMENT.MANUAL.INSTAPAY.SUBMISSION.UPLOAD.1`

That phase should consume `build_instapay_target_snapshot()` only after verifying `is_instapay_checkout_enabled()` and must still avoid granting access from screenshot upload.

## N. Git Status

Expected changed files after this phase:

```text
 M application/controllers/Youngo_payment_settings.php
 M application/models/Youngo_instapay_payment_model.php
 M application/models/Youngo_payment_config_audit_model.php
 M application/models/Youngo_payment_config_model.php
 M application/views/backend/admin/youngo_payment_settings.php
?? docs/qa/youngo_payment_manual_instapay_config_1_report.md
?? scripts/phase_2/payment_manual_instapay_config_1_down.sql
?? scripts/phase_2/payment_manual_instapay_config_1_up.sql
?? scripts/phase_2/youngo_payment_manual_instapay_config_1_diagnostic.php
```

No deploy or push was performed.
