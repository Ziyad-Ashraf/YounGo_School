# YounGo QA Report: PAYMENT.MANUAL.INSTAPAY.ADMIN.REVIEW.UI.1

## A. Current Branch/Status

- Branch: `analysis/cms-audit`
- Start status: clean before the phase work began.
- Latest expected commit before this phase: `07945ec Add manual Instapay checkout submission upload`
- Deployment: not performed.
- Push: not performed.

## B. Backup Created

The diagnostic creates a database backup before inserting temporary review/evidence fixtures.

- Backup path: `D:\Work\YounGo/backups/youngo_school_before_payment_manual_instapay_admin_review_ui_1_2026_07_26_211623.sql`
- Size: `1146457` bytes
- SHA256: `6e4e99fb88c4b06ac7e3b615b2f45c8160891c394c945d46cfef49da1922a7d3`

## C. Files Inspected

- `docs/qa/youngo_payment_manual_instapay_submission_upload_1_report.md`
- `docs/qa/youngo_payment_manual_instapay_schema_1_report.md`
- `docs/qa/youngo_payment_manual_instapay_config_1_report.md`
- `docs/qa/youngo_payment_coupon_checkout_ui_1_report.md`
- `application/controllers/Admin.php`
- `application/controllers/Youngo_payment_settings.php`
- `application/controllers/Youngo_checkout.php`
- `application/models/Youngo_instapay_payment_model.php`
- `application/models/Youngo_checkout_model.php`
- `application/views/backend/admin/`
- `application/config/routes.php`
- `uploads/youngo/instapay_evidence/.htaccess`
- `scripts/phase_2/`

## D. Files Changed

- `application/controllers/Youngo_instapay_payments.php`
- `application/models/Youngo_instapay_payment_model.php`
- `application/views/backend/admin/youngo_instapay_payments.php`
- `application/views/backend/admin/youngo_instapay_payment_view.php`
- `application/views/backend/admin/navigation.php`
- `application/config/routes.php`
- `scripts/phase_2/youngo_payment_manual_instapay_admin_review_ui_1_diagnostic.php`
- `scripts/phase_2/youngo_payment_manual_instapay_submission_upload_1_diagnostic.php`
- `scripts/phase_2/youngo_payment_manual_instapay_config_1_diagnostic.php`
- `scripts/phase_2/youngo_payment_manual_instapay_schema_1_diagnostic.php`
- `scripts/phase_2/youngo_payment_manual_instapay_coupon_checkout_plan_1_diagnostic.php`

## E. Admin Routes/Controller Summary

Added a root-only YounGo admin controller:

- `Youngo_instapay_payments::index()` renders `/admin/youngo/instapay-payments`
- `Youngo_instapay_payments::view($submission_id)` renders a read-only detail page
- `Youngo_instapay_payments::evidence($submission_id, $mode)` streams protected image evidence for preview or download

Routes added:

- `/admin/youngo/instapay-payments`
- `/admin/youngo/instapay-payments/{id}`
- `/admin/youngo/instapay-payments/{id}/evidence`
- `/admin/youngo/instapay-payments/{id}/evidence/download`

The controller requires an admin session and the same root-only policy used by YounGo payment settings.

## F. Model Read-Helper Summary

Added read-only helpers to `Youngo_instapay_payment_model`:

- `get_admin_review_list($status = null, $limit = 50, $offset = 0)`
- `get_admin_review_detail($submission_id)`
- `get_evidence_file_for_admin($submission_id)`
- `count_admin_review_by_status()`

The helpers join submissions to checkout orders and users, decode snapshot JSON, expose coupon/final amount details, and do not approve, reject, mark paid, or issue access.

## G. Inbox UI Summary

Added `application/views/backend/admin/youngo_instapay_payments.php`.

The inbox includes:

- Status tabs for `Pending Review`, `Approved`, `Rejected`, and `All`
- Submission/order identifiers
- User, item, original amount, coupon, discount, final expected amount, submitted amount, submitted time, status, and details action
- Read-only reminder that payment must be verified externally and decision actions are deferred

## H. Detail UI Summary

Added `application/views/backend/admin/youngo_instapay_payment_view.php`.

The detail page shows:

- User information
- Item/order snapshot
- Coupon code, discount type/value, discount amount, original amount, and final expected amount
- Submitted amount, transaction reference, user note, and status
- Instapay target snapshot
- Evidence metadata, preview, and download links
- Disabled approve/reject controls with text that actions are deferred to the next phase

## I. Evidence Preview/Download Protection

Evidence streaming is admin-only and model-validated before file output.

Protection rules:

- Submission must exist.
- Path must start with `uploads/youngo/instapay_evidence/`.
- `realpath()` must resolve inside the evidence directory.
- Only `image/jpeg`, `image/png`, and `image/webp` are served.
- Output uses `nosniff`, no-store cache headers, safe content type, safe length, and sanitized download filenames.
- The admin detail view uses protected evidence routes, not direct public file paths.
- Existing `uploads/youngo/instapay_evidence/.htaccess` denies direct web access for the evidence directory.

## J. Browser/Manual QA Result

Authenticated local HTTP/session QA was run for `PAYMENT.MANUAL.INSTAPAY.ADMIN.REVIEW.UI.BROWSER.QA.1` against `http://school.local`.

Node Playwright was not installed in this workspace, so the QA used the same local route/session approach used by previous payment dashboard QA phases. Private Root Admin credentials were not placed into shell commands, files, reports, or logs. Temporary CodeIgniter session rows were created for an existing Root Admin identity and an existing learner identity, then deleted during cleanup.

Browser/manual QA fixture backup:

- Backup path: `D:\Work\YounGo/backups/youngo_school_before_payment_manual_instapay_admin_review_ui_browser_qa_1_2026_07_26_212934.sql`
- Size: `1140009` bytes
- SHA256: `525e0c1dd39b58e5988c0463e1fe51c71b8412de3aa21ecc2c53a36f0ee63cac`

Command:

```text
php scripts/phase_2/youngo_payment_manual_instapay_admin_review_ui_browser_qa_1.php
```

Result: `PASS`

Verified:

- `/admin/youngo/instapay-payments` rendered HTTP `200` for the temporary Root Admin session.
- Pending Review filter rendered the temporary `pending_review` submission.
- Approved and Rejected filters rendered without showing the pending fixture.
- All filter rendered the temporary fixture.
- Detail page rendered checkout/coupon/final amount snapshot data and disabled approve/reject controls.
- Evidence preview returned `image/png` with inline disposition.
- Evidence download returned `image/png` with attachment disposition.
- Learner session did not receive the admin inbox content or PNG evidence content.
- No approve route/action was available.
- Fixture order remained `draft`, unpaid, and without entitlement issuance.

## K. Diagnostic Result

Command:

```text
php scripts/phase_2/youngo_payment_manual_instapay_admin_review_ui_1_diagnostic.php
```

Result: `PASS`

Key checks passed:

- Admin routes exist for inbox, detail, evidence preview, and evidence download.
- Controller is root-only and GET-only for review surfaces.
- Model exposes read-only review helpers.
- No approve/reject/access methods were added.
- Views include read-only inbox/detail controls and disabled future actions.
- Evidence traversal/outside path was rejected.
- Temporary submission remained `pending_review`.
- Order stayed `draft` and unpaid.
- Paymob remained disabled.
- Temporary DB rows and evidence file were deleted.

Compatibility command:

```text
php scripts/phase_2/youngo_payment_manual_instapay_submission_upload_1_diagnostic.php
```

Result: `PASS`

Browser/manual QA command:

```text
php scripts/phase_2/youngo_payment_manual_instapay_admin_review_ui_browser_qa_1.php
```

Result: `PASS`

## L. DB/Filesystem Cleanup

The diagnostic inserted temporary checkout and Instapay submission rows and created a temporary PNG evidence fixture after backup.

Cleanup result:

- Temporary `youngo_instapay_payment_submissions` rows deleted.
- Temporary `youngo_checkout_orders` row deleted.
- Temporary evidence file deleted.
- Temporary browser-QA `ci_sessions` rows deleted.
- Protected counts restored:
  - `ci_sessions`: restored to baseline during browser QA
  - `youngo_instapay_payment_submissions`: `0`
  - `youngo_checkout_orders`: `0`
  - `youngo_payment_transactions`: `0`
  - `youngo_course_access`: `0`
  - `youngo_user_subscriptions`: `0`
  - `youngo_manual_grants`: `0`
  - `youngo_coupon_usages`: `0`
  - `payment`: `0`
  - `enrol`: `1`

## M. Payment/Access Safety

- No approval or rejection action was added.
- No order was marked paid.
- No Paymob configuration was enabled.
- No card or wallet flow was exposed.
- No entitlement, enrolment, subscription, manual grant, payment, or access issuance behavior was changed.
- Status model remains limited to `pending_review`, `approved`, and `rejected`.

## N. Remaining Risks/Blockers

- Full pixel-level browser/screenshot QA remains optional because Node Playwright was not installed; authenticated local route/session QA passed.
- Admin note entry, approve/reject decisions, and access issuance are intentionally deferred.
- Evidence files remain under the existing upload tree with directory deny rules in place; deployment packaging should preserve that `.htaccess`.

## O. Recommended Next Phase

`PAYMENT.MANUAL.INSTAPAY.APPROVAL.ACCESS.1`

The next phase should add explicit approve/reject actions, external-payment confirmation copy, idempotent access issuance through the existing entitlement/subscription foundation, and access duration starting from `approved_at`.

## P. Git Status

At report creation time, the worktree contains the phase changes and the new report/diagnostic files. Final validation status is recorded in the assistant final response.
