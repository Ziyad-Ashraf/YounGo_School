# LANGUAGE.FRONTEND.COURSE_DETAIL.ACCESS_COPY.QA.1 Report

## A. Current Branch/Status

- Branch: `analysis/cms-audit`
- Starting worktree: clean.
- Latest commit at start: `837d9ec Wire Arabic YounGo course detail access copy`

## B. Backup Created Or No-Backup Rationale

- Backup was required because authenticated QA used controlled temporary entitlement setup.
- Backup path: `D:\Work\YounGo\backups\youngo_school_before_language_frontend_course_detail_access_copy_qa_1_2026_07_26_132255.sql`
- Size: `727618` bytes
- SHA256: `1A83F96EC968D796A5EF10A21BE75D12BB5A22B0F2F603162C25D2C2D34F8D84`

## C. Files Inspected

- `docs/qa/youngo_language_frontend_course_detail_access_copy_wire_1_report.md`
- `docs/qa/youngo_content_translation_reuse_audit_1_report.md`
- `docs/qa/youngo_dynamic_content_arabic_public_localization_qa_1_report.md`
- `application/views/frontend/youngo/course_page.php`
- `application/views/frontend/youngo/course_page_reviews.php`
- `application/controllers/Home.php`
- `application/models/Youngo_entitlement_write_model.php`
- `application/models/Youngo_entitlement_model.php`
- `application/models/Youngo_subscription_model.php`
- `application/helpers/youngo_frontend_language_helper.php`
- `scripts/phase_2/`

## D. QA Accounts/States Used Without Credentials

- Root Admin browser/session context was used privately for admin preview checks.
- QA learner user `10` was used for learner authenticated checks.
- No passwords or credential values were printed, stored, or included in this report.
- `application/models/Youngo_entitlement_read_model.php` was requested for inspection but is not present; current read-layer functionality is in `application/models/Youngo_entitlement_model.php`.

## E. Learner No-Access QA

- Tested `GET /home/course/robotics-and-ai-explorers/9` and `GET /en/home/course/robotics-and-ai-explorers/9` with an authenticated QA learner and no active YounGo access rows.
- Both returned HTTP `200`.
- Arabic/default rendered `lang="ar" dir="rtl"` and no targeted English access/status copy remained after the display fix.
- `/en` rendered `lang="en" dir="ltr"` and remained English.
- No checkout/payment/Paymob CTAs were rendered.

## F. Learner Access/Grant QA

- Created a temporary manual course grant for QA learner user `10` on course `9` using `Youngo_entitlement_write_model`.
- `grant_course_access` returned `course_access_granted`.
- Arabic/default and `/en` course detail pages returned HTTP `200`.
- `/en` displayed the expected `Manual grant access` and `Start now` state labels.
- Arabic/default had no targeted English access/status copy after the display fix.
- The temporary course grant was revoked through `Youngo_entitlement_write_model`; the `/en` revoked state displayed `Access locked`.

## G. Locked/Expired/Admin Preview QA Or Deferral

- Revoked/locked display was tested using the temporary course grant revocation path; `/en` displayed `Access locked`, and Arabic/default had no targeted English access/status copy.
- Expired-state QA was deferred because safely creating an expired row would require special dated setup beyond the approved temporary grant/revoke flow.
- Admin preview was tested with private Root Admin session context on Arabic/default and `/en`; both returned HTTP `200`, and `/en` displayed `Admin access`.
- Instructor preview was deferred because no separate safe instructor fixture was needed for this access-copy phase.

## H. Arabic/Default QA

- Tested authenticated learner no-access, subscription access, manual grant access, revoked access, and admin preview states.
- Arabic/default pages consistently returned HTTP `200` with `lang="ar" dir="rtl"`.
- Confirmed fix for the legacy `hours` placeholder issue that rendered `????? ??????: Hours` on Arabic course-detail duration labels.
- Dynamic course, section, and lesson content continued through the existing content translation helpers.

## I. /en QA

- Tested authenticated learner no-access, subscription access, manual grant access, revoked access, and admin preview states.
- `/en` pages consistently returned HTTP `200` with `lang="en" dir="ltr"`.
- Expected English state labels were detected where applicable:
  - `Subscription access` on course `1`
  - `Manual grant access` and `Start now` on course `9`
  - `Access locked` after revocation on course `9`
  - `Admin access` for admin preview on course `9`

## J. Access/Enrolment Preservation

- No course access, enrolment, entitlement, subscription, checkout, payment, Paymob, or session logic was changed.
- Temporary writes used `Youngo_entitlement_write_model`, not manual row-level SQL.
- Route/action strings remained present for course preview, lesson/play-lesson, free-enrol boundary, rating, and review actions.

## K. Payment/CTA Safety

- Authenticated QA found no rendered `Paymob`, `Buy Now`, `Add to cart`, `Checkout`, `Pay now`, `Pay with Paymob`, or `Subscribe now` terms in tested course-detail pages.
- Diagnostic rendered checks also found no payment/checkout CTA terms.
- No payment, Paymob, checkout, order, or coupon rows were created.

## L. Cleanup Result

- Restored the backup after temporary authenticated QA.
- Final cleanup counts:
  - `youngo_course_access`: `0`
  - `youngo_user_subscriptions`: `0`
  - `youngo_manual_grants`: `0`
  - `payment`: `0`
  - `enrol`: `1`
  - `youngo_checkout_orders`: `0`
  - `youngo_coupon_usages`: `0`
  - temporary QA manual grants: `0`
  - temporary QA sessions: `0`

## M. Diagnostic Result

- Added `scripts/phase_2/youngo_language_frontend_course_detail_access_copy_qa_1_diagnostic.php`.
- Diagnostic verifies targeted phrase resolution, no `arabic_translated` UI usage, public rendered course-detail language/payment safety, route string preservation, and temporary QA cleanup.
- `php -l application/views/frontend/youngo/course_page.php`: PASS.
- `php -l scripts/phase_2/youngo_language_frontend_course_detail_access_copy_qa_1_diagnostic.php`: PASS.
- `php scripts/phase_2/youngo_language_frontend_course_detail_access_copy_qa_1_diagnostic.php`: PASS.
- `php scripts/phase_2/youngo_language_frontend_course_detail_access_copy_wire_1_diagnostic.php`: PASS.
- `php scripts/phase_2/youngo_content_translation_reuse_audit_1_diagnostic.php`: PASS.
- `git diff --check`: PASS.

## N. DB Impact

- Temporary DB writes were made only for authenticated QA setup through the entitlement write model.
- The pre-QA backup was restored after QA.
- Final DB state returned to protected-table baseline; no course/content/payment/checkout/order/coupon/Root Admin changes remain.

## O. Remaining Risks/Blockers

- Stored Arabic phrase `hours` still contains a legacy placeholder value locally; this phase avoided DB phrase overwrite and fixed course-detail display safely in the view wrapper.
- Expired-state and instructor-specific preview copy remain deferred until a safe fixture or explicit setup phase exists.
- Some helper local Arabic fallback values appear mojibake in CLI output; rendered public pages passed for the tested course-detail paths.

## P. Recommended Next Phase

- Run a controlled Arabic phrase-data repair phase for non-empty legacy placeholder/corrupt phrase values, with explicit overwrite rules.
- Add a focused expired/instructor access-copy QA phase if those states become demo-critical.

## Q. Git Status

- Final `git status --short`:
  - `M application/views/frontend/youngo/course_page.php`
  - `?? docs/qa/youngo_language_frontend_course_detail_access_copy_qa_1_report.md`
  - `?? scripts/phase_2/youngo_language_frontend_course_detail_access_copy_qa_1_diagnostic.php`
