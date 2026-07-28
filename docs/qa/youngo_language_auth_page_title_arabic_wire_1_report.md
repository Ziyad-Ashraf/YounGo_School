# LANGUAGE.AUTH.PAGE_TITLE.ARABIC.WIRE.1 Report

## A. Current Branch/Status

- Branch: `analysis/cms-audit`
- Start worktree status: clean
- Latest commit at start: `b1e43a7 Add Arabic public demo readiness QA`
- No deploy or push performed.
- No route, form action, auth/security/session, payment/Paymob, checkout/cart, Root Admin, or `arabic_translated` UI-language change was made.

## B. Files Inspected

- `docs/qa/youngo_demo_arabic_public_readiness_qa_1_report.md`
- `docs/qa/youngo_language_frontend_auth_copy_qa_1_report.md`
- `docs/qa/youngo_language_frontend_auth_copy_wire_1_report.md`
- `application/controllers/Home.php`
- `application/controllers/Login.php`
- `application/controllers/Sign_up.php`
- `application/views/frontend/youngo/login.php`
- `application/views/frontend/youngo/sign_up.php`
- `application/views/frontend/youngo/forgot_password.php`
- `application/views/frontend/youngo/change_password_from_forgot_password.php`
- `application/views/frontend/youngo/verification_code.php`
- `application/views/frontend/youngo/new_login_confirmation.php`
- `application/views/frontend/youngo/index.php`
- `application/helpers/youngo_frontend_language_helper.php`
- `application/config/routes.php`
- `scripts/phase_2/`

## C. Files Changed

- `application/controllers/Login.php`
- `application/controllers/Sign_up.php`
- `scripts/phase_2/youngo_language_auth_page_title_arabic_wire_1_diagnostic.php`
- `docs/qa/youngo_language_auth_page_title_arabic_wire_1_report.md`

No seed script was created because the required public auth title phrases already resolve through the existing YounGo frontend phrase helper and phrase map.

## D. Title Source Identified

The browser title is rendered by:

```text
application/views/frontend/youngo/index.php
```

using:

```php
<title><?php echo htmlspecialchars($page_title); ?> | <?php echo get_settings('system_name'); ?></title>
```

The active public auth routes set `$page_data['page_title']` in:

- `application/controllers/Login.php`
- `application/controllers/Sign_up.php`

Before this phase, those controllers used `site_phrase(...)`, which returned English titles on Arabic/default auth pages. `Home.php` already used the YounGo frontend phrase helper for its legacy auth wrapper methods, but the tested public auth routes do not use those `Home` methods.

This phase added a controller-local `youngo_public_auth_page_title()` helper in `Login.php` and `Sign_up.php`. It uses:

- `youngo_frontend_active_language($this->uri->uri_string())`
- `youngo_frontend_phrase($phrase_key, $fallback, $language)`

and falls back to `site_phrase($phrase_key)` only if the YounGo helper path is unavailable.

Covered title keys:

- `/login`: `login`
- `/sign_up`, `/en/sign-up`, `/ar/sign-up`: `sign_up`
- `/login/forgot_password_request`: `forgot_password`
- reset password page: `change_password`
- verification code page: `verification_code`
- new-device confirmation page: `login_confirmation`

## E. Arabic/Default Title QA

| URL | HTTP | lang/dir | Browser title | Form action |
|---|---:|---|---|---|
| `/login` | 200 | `ar`/`rtl` | `تسجيل الدخول | YounGo` | `login/validate_login` |
| `/sign_up` | 200 | `ar`/`rtl` | `إنشاء حساب | YounGo` | `login/register` |
| `/login/forgot_password_request` | 200 | `ar`/`rtl` | `نسيت كلمة المرور | YounGo` | `login/forgot_password/frontend` |

Also verified compatibility auth routes:

- `/ar/login`: `تسجيل الدخول | YounGo`
- `/ar/sign-up`: `إنشاء حساب | YounGo`
- `/ar/login/forgot_password_request`: `نسيت كلمة المرور | YounGo`

## F. `/en` Title QA

| URL | HTTP | lang/dir | Browser title | Form action |
|---|---:|---|---|---|
| `/en/login` | 200 | `en`/`ltr` | `Login | YounGo` | `login/validate_login` |
| `/en/sign-up` | 200 | `en`/`ltr` | `Sign up | YounGo` | `login/register` |
| `/en/login/forgot_password_request` | 200 | `en`/`ltr` | `Forgot password | YounGo` | `login/forgot_password/frontend` |

## G. Body/Auth Behavior Preservation

- Auth body views were not edited.
- Form actions were not changed.
- Routes were not changed.
- Login validation, registration, forgot-password, reset-password, verification, new-device confirmation, session, redirect, CSRF/recaptcha, and security logic were not changed.
- Existing deferred issues remain deferred:
  - backend flash/security messages can still be English
  - `/en` negative auth POST redirects may still lose English context
  - valid reset/verification/new-device display QA still needs valid session/token setup

## H. Payment/CTA Safety

The focused diagnostic found no Paymob, payment, checkout, cart, order, coupon, `Buy Now`, `Add to cart`, `Pay now`, or subscription purchase CTA/link on the tested auth pages.

No payment/checkout/Paymob source reference was added to the changed controllers.

## I. Diagnostic Result

Passed:

```text
php -l application/controllers/Login.php
php -l application/controllers/Sign_up.php
php -l scripts/phase_2/youngo_language_auth_page_title_arabic_wire_1_diagnostic.php
php scripts/phase_2/youngo_language_auth_page_title_arabic_wire_1_diagnostic.php
```

Diagnostic result:

```text
PASS: public auth browser titles are localized by route language without auth action or payment-surface changes.
```

## J. Public Readiness Diagnostic Rerun Result

Passed:

```text
php scripts/phase_2/youngo_demo_arabic_public_readiness_qa_1_diagnostic.php
```

Result:

```text
PASS: Arabic public demo-readiness QA diagnostic passed read-only content/protected-table checks.
```

The readiness diagnostic continued to report no corrupt placeholders, mojibake markers, high-visibility English body UI labels, unexpected Arabic body UI on English pages, payment/checkout/Paymob CTAs, invalid dynamic translation rows, or content/protected table drift.

## K. DB Impact

No intentional DB writes were performed and no seed script was needed.

Runtime HTTP diagnostics may create normal `ci_sessions` rows because the app uses CodeIgniter's database session driver. Content, phrase, dynamic translation, payment, checkout, coupon, entitlement, enrolment, and progress tables remained unchanged in the public readiness diagnostic.

## L. Remaining Risks/Blockers

- Backend-driven auth flash/security messages remain incompletely localized.
- `/en` negative auth POST redirects may still return to unprefixed Arabic/default pages.
- Valid reset-password, verification-code, and new-device confirmation browser QA still require safe token/session setup.

No remaining public unauthenticated auth title blocker was found.

## M. Recommended Next Phase

Recommended next phase:

```text
DEMO.ARABIC.PUBLIC.READINESS.QA.2
```

Scope: rerun the final public Arabic demo-readiness audit after this title fix is committed, with browser-title checks included in the diagnostic/report criteria.

## N. Git Status

Expected changed files after this phase:

```text
M  application/controllers/Login.php
M  application/controllers/Sign_up.php
?? docs/qa/youngo_language_auth_page_title_arabic_wire_1_report.md
?? scripts/phase_2/youngo_language_auth_page_title_arabic_wire_1_diagnostic.php
```
