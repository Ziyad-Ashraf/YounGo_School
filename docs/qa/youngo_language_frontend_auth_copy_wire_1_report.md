# YounGo QA Report: LANGUAGE.FRONTEND.AUTH.COPY.WIRE.1

## A. Current Branch/Status

- Branch: `analysis/cms-audit`
- Start state: clean worktree.
- Latest commit at start included `DYNAMIC.CONTENT.ARABIC.PUBLIC.LOCALIZATION.QA.1`.

## B. Files Inspected

- `docs/qa/youngo_dynamic_content_arabic_public_localization_qa_1_report.md`
- `docs/qa/youngo_language_frontend_arabic_visual_copy_polish_1_report.md`
- `docs/qa/youngo_language_frontend_phrase_seed_missing_1_report.md`
- `application/helpers/youngo_frontend_language_helper.php`
- `application/views/frontend/youngo/login.php`
- `application/views/frontend/youngo/sign_up.php`
- `application/views/frontend/youngo/forgot_password.php`
- `application/views/frontend/youngo/change_password_from_forgot_password.php`
- `application/views/frontend/youngo/verification_code.php`
- `application/views/frontend/youngo/new_login_confirmation.php`
- `application/config/routes.php`
- `application/controllers/Home.php`
- `application/controllers/Login.php`
- `scripts/phase_2/`

## C. Files Changed

- `application/helpers/youngo_frontend_language_helper.php`
- `application/views/frontend/youngo/login.php`
- `application/views/frontend/youngo/sign_up.php`
- `application/views/frontend/youngo/forgot_password.php`
- `application/views/frontend/youngo/change_password_from_forgot_password.php`
- `application/views/frontend/youngo/verification_code.php`
- `application/views/frontend/youngo/new_login_confirmation.php`
- `scripts/phase_2/youngo_language_frontend_auth_copy_wire_1_seed.php`
- `scripts/phase_2/youngo_language_frontend_auth_copy_wire_1_diagnostic.php`
- `docs/qa/youngo_language_frontend_auth_copy_wire_1_report.md`

## D. Auth Labels Wired

The YounGo public auth templates now use escaped URI-aware phrase output for safe visible display text:

- Login marketing/sidebar copy, login heading, labels, placeholders, password toggle text, forgot link, and signup switch.
- Signup marketing/sidebar copy, form labels/placeholders, instructor-application display labels, buttons, social divider, and login switch.
- Forgot-password heading, intro text, form label/placeholder, submit button, and back-to-login link.
- Reset-password display labels/placeholders and back-to-login link.
- Email verification and new-device confirmation display labels/placeholders/resend/status labels.

Operational behavior was intentionally left unchanged.

## E. Phrase Keys Seeded

An explicit auth-only seed script was added and run:

- Keys considered: `88`
- First run inserted rows: `0`
- First run filled blank English values: `0`
- First run updated Arabic auth display values: `66`
- First run preserved existing values: `22`
- Manual overrides preserved: `0`
- Protected/payment keys skipped: `0`
- `arabic_translated` writes: `false`

Idempotency rerun:

- Inserted rows: `0`
- Arabic values updated: `0`
- Existing values preserved: `88`

The script prints counts only and does not print phrase values.

## F. Arabic/Default QA

HTTP QA passed locally:

- `/login`: HTTP `200`, `lang="ar"`, `dir="rtl"`, Arabic auth copy rendered.
- `/sign_up`: HTTP `200`, `lang="ar"`, `dir="rtl"`, Arabic auth copy rendered.
- `/login/forgot_password_request`: HTTP `200`, `lang="ar"`, `dir="rtl"`, Arabic auth copy rendered.

Reset/change-password pages require a verification code and were verified by source/action checks rather than direct unauthenticated HTTP flow.

## G. /en QA

HTTP QA passed locally:

- `/en/login`: HTTP `200`, `lang="en"`, `dir="ltr"`, English auth copy rendered.
- `/en/sign-up`: HTTP `200`, `lang="en"`, `dir="ltr"`, English auth copy rendered.
- `/en/login/forgot_password_request`: HTTP `200`, `lang="en"`, `dir="ltr"`, English auth copy rendered.

The English pages may still include the Arabic language-switch option in the shell; this is expected and not treated as mixed auth copy.

## H. Form/Action Preservation

Unchanged auth actions verified:

- Login POST remains `login/validate_login`.
- Signup POST remains `login/register`.
- Forgot-password POST remains `login/forgot_password/frontend`.
- Change-password POST remains `login/change_password/{verification_code}`.
- Email verification POST/fetch endpoints remain `login/verify_email_address`, `login/resend_verification_code`, and `login/new_login_confirmation/resend`.

No validation, session, CSRF, redirect, or controller auth logic was changed.

## I. Payment/CTA Safety

No Paymob/payment/checkout/order/enrol/grant URLs or CTAs were introduced.

The phase did not change Paymob, checkout, payment settings, access grants, enrolment behavior, or Root Admin.

## J. Diagnostic Result

Passed:

- `php scripts/phase_2/youngo_language_frontend_auth_copy_wire_1_diagnostic.php`
- `php scripts/phase_2/youngo_dynamic_content_arabic_public_localization_qa_1_diagnostic.php`

The new diagnostic verified:

- Auth phrase rows exist and have English/Arabic values.
- Targeted Arabic auth values contain Arabic script.
- `arabic_translated` metadata rows are absent.
- Auth views use `youngo_frontend_phrase_e()`.
- Legacy `get_phrase()` / `site_phrase()` calls are absent from the scoped auth views.
- Public auth pages render expected lang/dir and safe form actions.
- No payment/checkout links render on tested auth pages.

## K. DB Impact

Backup created before the phrase-table write:

- Path: `D:\Work\YounGo\backups\youngo_school_before_language_frontend_auth_copy_wire_1_2026_07_26_083758.sql`
- Size: `662294` bytes
- SHA256: `0E3D971526EDD402531FA48057E8F2EF61050D1A2DB1479E6437368772916C64`

DB changes:

- Updated targeted `language.arabic` values for public auth display keys only.
- Did not change `arabic_translated`.
- Did not write payment, checkout, enrolment, entitlement, course, subscription plan, user, or Root Admin data.

## L. Remaining Risks/Blockers

- Backend-driven flash/error/security messages still come from existing auth/controller logic and were not reworked in this phase.
- Direct reset-password rendering requires a valid verification-code URL to fully browser-test the form.
- Dynamic user-provided/social-login provider text can still render in provider-controlled language where applicable.

## M. Recommended Next Phase

Recommended next phase:

- `LANGUAGE.FRONTEND.AUTH.COPY.QA.1` for authenticated/negative-flow auth QA, including invalid login, forgot-password submission, verification-code URLs, and flash-message localization review.

## N. Git Status

Worktree has expected modified/new files for this phase only; no deploy, push, or commit was performed.
