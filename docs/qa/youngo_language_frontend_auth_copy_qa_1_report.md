# YounGo QA Report: LANGUAGE.FRONTEND.AUTH.COPY.QA.1

## A. Current Branch/Status

- Branch: `analysis/cms-audit`
- Start state: clean worktree.
- Latest commit at start: `ad62df3 Wire Arabic public auth copy`
- Scope: focused QA/audit with one additive read-only diagnostic and this report.
- Deploy/push: not performed.
- Auth/security/session/form action behavior changes: not performed.
- Root Admin: not modified.

## B. Files Inspected

- `docs/qa/youngo_language_frontend_auth_copy_wire_1_report.md`
- `docs/qa/youngo_dynamic_content_arabic_public_localization_qa_1_report.md`
- `application/views/frontend/youngo/login.php`
- `application/views/frontend/youngo/sign_up.php`
- `application/views/frontend/youngo/forgot_password.php`
- `application/views/frontend/youngo/change_password_from_forgot_password.php`
- `application/views/frontend/youngo/verification_code.php`
- `application/views/frontend/youngo/new_login_confirmation.php`
- `application/controllers/Login.php`
- `application/controllers/Sign_up.php`
- `application/helpers/youngo_frontend_language_helper.php`
- `application/config/routes.php`
- `application/models/Crud_model.php`
- `application/models/User_model.php`
- `application/models/Email_model.php`
- `scripts/phase_2/`

## C. Arabic/Default Page QA

| URL | Result | Language shell | Form/action | Notes |
|---|---:|---|---|---|
| `/login` | 200 | `lang="ar" dir="rtl"` | `login/validate_login` POST | Arabic visible auth copy rendered; no payment/checkout CTA. |
| `/sign_up` | 200 | `lang="ar" dir="rtl"` | `login/register` POST | Arabic visible auth copy rendered; no payment/checkout CTA. |
| `/login/forgot_password_request` | 200 | `lang="ar" dir="rtl"` | `login/forgot_password/frontend` POST | Arabic visible auth copy rendered; no payment/checkout CTA. |

High-visibility auth display copy was Arabic on the tested Arabic/default pages. Expected brand/system text such as `YounGo`, `EN`, and technical URL/action strings were not treated as copy leakage.

## D. `/en` Page QA

| URL | Result | Language shell | Form/action | Notes |
|---|---:|---|---|---|
| `/en/login` | 200 | `lang="en" dir="ltr"` | `login/validate_login` POST | English visible auth copy rendered; no Arabic copy except the language switcher label; no payment/checkout CTA. |
| `/en/sign-up` | 200 | `lang="en" dir="ltr"` | `login/register` POST | English visible auth copy rendered; no Arabic copy except the language switcher label; no payment/checkout CTA. |
| `/en/login/forgot_password_request` | 200 | `lang="en" dir="ltr"` | `login/forgot_password/frontend` POST | English visible auth copy rendered; no Arabic copy except the language switcher label; no payment/checkout CTA. |

## E. Negative Login QA

Invalid credential submissions were tested against `/login` and `/en/login` using fake credentials only.

| Start URL | POST result | Redirect target | Rendered shell after redirect | Flash behavior |
|---|---:|---|---|---|
| `/login` | 200 refresh | `/login` | `ar/rtl` | Alert rendered as `Invalid login credentials`. |
| `/en/login` | 200 refresh | `/login` | `ar/rtl` | Alert rendered as `Invalid login credentials`; English context was not preserved after the legacy POST redirect. |

No server error occurred, and the login form remained usable after each failed submission.

## F. Forgot-Password QA

Blank and nonexistent email submissions were tested through the existing `login/forgot_password/frontend` action.

| Start URL | Submitted email class | POST result | Redirect target | Rendered shell after redirect | Flash behavior |
|---|---|---:|---|---|---|
| `/login/forgot_password_request` | blank | 200 refresh | `/login` | `ar/rtl` | Alert rendered as `User not found`. |
| `/en/login/forgot_password_request` | blank | 200 refresh | `/login` | `ar/rtl` | Alert rendered as `User not found`; English context was not preserved. |
| `/login/forgot_password_request` | nonexistent | 200 refresh | `/login` | `ar/rtl` | Alert rendered as `User not found`. |
| `/en/login/forgot_password_request` | nonexistent | 200 refresh | `/login` | `ar/rtl` | Alert rendered as `User not found`; English context was not preserved. |

Existing QA learner email submission was skipped as unsafe in this QA pass because `Crud_model::forgot_password()` updates the active user's `verification_code`/`last_modified` and attempts notification delivery. No password-reset token or email credential was printed.

## G. Reset/Verification/New-Device QA

| URL | Result | Notes |
|---|---:|---|
| `/sign_up/verification_code` | 200 empty body with refresh to `/sign_up` | Blocked by missing `register_email` session, as expected. |
| `/login/new_login_confirmation` | 200 empty body with refresh to `/login` | Blocked by missing valid new-device session/code, as expected. |
| `/login/change_password` | 200 empty body with refresh to `/login` | Missing code redirects through existing behavior. |
| `/login/change_password/not-a-valid-code` | 200 empty body with refresh to `/login/forgot_password_request` | Invalid/expired code path redirects through existing behavior. |

No valid verification-code URL was available safely. No token was invented or queried for browser display QA. Root Admin login was not used because Root Admin does not exercise the learner new-device confirmation path and was not needed for this public auth copy QA.

## H. Flash/Security Message Findings

- Backend-driven flash messages are still legacy controller/model output, not fully localized display copy.
- Arabic/default invalid login currently displays English `Invalid login credentials`.
- Arabic/default blank/nonexistent forgot-password currently displays English `User not found`.
- `/en` invalid login and forgot-password negative flows post to unchanged legacy actions and redirect to unprefixed `/login`, so the rendered page becomes Arabic/default after failure.
- Read-only phrase inspection found several backend flash/security phrase rows missing, blank in Arabic, or not containing Arabic script. This confirms the issue is broader than a single view string.

No flash/security message rewrite was made because a complete fix should be planned around backend phrase data quality and language-preserving redirects without changing auth validation/security behavior casually.

## I. Form/Action Preservation

Verified unchanged action boundaries:

- Login POST remains `login/validate_login`.
- Signup POST remains `login/register`.
- Forgot-password POST remains `login/forgot_password/frontend`.
- Change-password POST remains `login/change_password/{verification_code}`.
- Email verification endpoints remain `login/verify_email_address` and `login/resend_verification_code`.
- New-device confirmation endpoints remain `login/new_login_confirmation/submit` and `login/new_login_confirmation/resend`.

No form POST/action URLs were changed.

## J. Payment/CTA Safety

All tested auth pages passed payment/CTA checks:

- no Paymob links/forms
- no checkout/order/payment links/forms
- no cart/coupon/enrol/grant action links/forms
- no Buy Now/Add to cart/Checkout/Pay now/Subscribe now CTA text

No payment, Paymob, checkout, order, coupon, enrolment, grant, or access behavior was changed.

## K. Diagnostic Result

Added and passed:

```bash
php -l scripts/phase_2/youngo_language_frontend_auth_copy_qa_1_diagnostic.php
php scripts/phase_2/youngo_language_frontend_auth_copy_qa_1_diagnostic.php
```

Diagnostic PASS summary:

- Auth views use the YounGo frontend phrase helper for targeted display copy.
- The 88 scoped auth display phrase rows have nonblank English and Arabic values.
- Scoped Arabic auth display values contain Arabic script.
- `arabic_translated` is absent from auth UI usage and metadata rows.
- Expected auth form/action URLs are unchanged.
- Auth views contain no payment/checkout/Paymob links.
- Diagnostic uses read-only DB queries and does not submit forms.

The diagnostic also reports backend flash phrase readiness as documentation-only status, showing unresolved flash phrase gaps.

## L. DB Impact

No intentional content/user/auth/payment/access DB write was performed by the source changes or diagnostic.

Runtime HTTP QA likely touched normal CodeIgniter session storage. Protected table snapshot from the diagnostic:

- `payment = 0`
- `enrol = 1`
- `youngo_checkout_orders = 0`
- `youngo_coupon_usages = 0`
- `youngo_course_access = 0`
- `youngo_user_subscriptions = 0`
- `youngo_manual_grants = 0`

Existing active-user forgot-password submission was skipped to avoid mutating `users.verification_code`/`users.last_modified`.

## M. Remaining Risks/Blockers

- Backend-driven auth flash/security messages remain incompletely localized.
- `/en` negative auth submissions lose English display context after legacy POST redirects to `/login`.
- Valid reset-password display QA still needs an owner-provided or safely generated verification-code URL under a backed-up QA plan.
- Verification-code and new-device confirmation display QA need valid session state; this phase did not create users, tokens, or new-device codes.
- Arabic phrase quality/polish remains a broader language QA risk for legacy phrase rows outside the scoped auth display inventory.

## N. Recommended Next Phase

Recommended next phase:

`LANGUAGE.FRONTEND.AUTH.FLASH.REDIRECT.PLAN.1`

Scope should design a safe, display-only approach for localized backend auth flash messages and language-preserving negative-flow redirects, without changing credential validation, token generation, password reset behavior, or form action URLs.

## O. Git Status

Expected pending files for this QA phase:

```text
?? docs/qa/youngo_language_frontend_auth_copy_qa_1_report.md
?? scripts/phase_2/youngo_language_frontend_auth_copy_qa_1_diagnostic.php
```
