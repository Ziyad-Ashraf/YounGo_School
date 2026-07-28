# LOCALIZATION.AR_DEFAULT.AUTH.REDIRECTS.FIX.1 Report

Phase: LOCALIZATION.AR_DEFAULT.AUTH.REDIRECTS.FIX.1 - Preserve Language Context in Public Auth Redirects

Date: 2026-07-25

## A. Current Branch/Status

- Branch at start: `analysis/cms-audit`
- Worktree at start: clean
- Latest commit at start: `78a4092 Plan Arabic default auth redirect preservation`
- No deployment, push, DB migration, SQL execution, credential output, Root Admin modification, Paymob/payment behavior change, checkout CTA exposure, or admin/backend localization was performed.

## B. Files Inspected

- `docs/qa/youngo_localization_ar_default_auth_redirects_plan_1_report.md`
- `docs/qa/youngo_localization_ar_default_authenticated_public_qa_1_report.md`
- `docs/qa/youngo_localization_ar_default_links_1_report.md`
- `application/config/routes.php`
- `application/controllers/Home.php`
- `application/controllers/Login.php`
- `application/controllers/Sign_up.php`
- `application/helpers/youngo_frontend_language_helper.php`
- `application/helpers/common_helper.php`
- `application/helpers/youngo_frontend_content_helper.php`
- `application/models/User_model.php`
- `application/views/frontend/youngo/`

Requested but not present:

- `application/controllers/Signup.php`
- `application/controllers/Register.php`

## C. Files Changed

- `application/controllers/Home.php`
- `application/controllers/Login.php`
- `application/models/User_model.php`
- `scripts/phase_2/youngo_localization_ar_default_auth_redirects_fix_1_diagnostic.php`
- `docs/qa/youngo_localization_ar_default_auth_redirects_fix_1_report.md`

## D. Redirect Fixes Made

- Added private public-redirect helpers in `Home.php` that use existing frontend language URL helpers.
- Updated unauthenticated `Home::my_courses()` and `Home::my_access()` redirects from fixed `site_url('home')` to helper-generated public home redirects.
- Updated selected public display/account fallback redirects in `Home.php`, including profile, purchase history, wishlist-adjacent account pages, public lesson denied/missing fallbacks, and course-detail fallback redirects.
- Updated `Home::get_enrolled_to_free_course()` display destinations and URL-history course destination to helper-generated public course URLs, while leaving the action route itself fixed.
- Added `Login::youngo_store_public_auth_language()` for login/forgot-password display pages so `/en/login` context survives the unlocalized login POST.
- Updated learner login fallback in `User_model::set_login_userdata()` to use the stored public auth language.
- Updated already-authenticated learner login-page redirect in `User_model::check_session_data('login')` to use localized My Courses.
- Root/Admin login redirects remain fixed to admin/backend destinations.

## E. Language Preservation Behavior

Unauthenticated public account redirects now behave as follows:

| URL | Result |
| --- | --- |
| `/home/my_courses` | refreshes to `http://localhost/` |
| `/home/my_access` | refreshes to `http://localhost/` |
| `/en/home/my_courses` | refreshes to `http://localhost/en` |
| `/en/home/my_access` | refreshes to `http://localhost/en` |
| `/ar/home/my_courses` | refreshes to `http://localhost/` |
| `/ar/home/my_access` | refreshes to `http://localhost/` |

This preserves English `/en` context, keeps Arabic/default unprefixed, and canonicalizes `/ar` compatibility display redirects to unprefixed Arabic.

## F. Operational URL Safety

- Login POST action remains `login/validate_login`.
- Registration POST action remains `login/register`.
- Logout remains `login/logout`.
- Wishlist mutation, profile/account write, lesson/progress AJAX, cart, checkout, payment, Paymob, and admin/backend routes were not localized.
- No `/en` or `/ar` admin/payment/action route aliases were added.
- No checkout/payment/Paymob CTA links were added.

## G. HTTP QA Summary

Local unauthenticated HTTP smoke checks:

| URL | Result |
| --- | --- |
| `/home/my_courses` | HTTP 200 with `Refresh: 0;url=http://localhost/` |
| `/home/my_access` | HTTP 200 with `Refresh: 0;url=http://localhost/` |
| `/en/home/my_courses` | HTTP 200 with `Refresh: 0;url=http://localhost/en` |
| `/en/home/my_access` | HTTP 200 with `Refresh: 0;url=http://localhost/en` |
| `/ar/home/my_courses` | HTTP 200 with `Refresh: 0;url=http://localhost/` |
| `/ar/home/my_access` | HTTP 200 with `Refresh: 0;url=http://localhost/` |
| `/login` | HTTP 200, `lang=ar`, `dir=rtl` |
| `/en/login` | HTTP 200, `lang=en`, `dir=ltr` |
| `/admin/dashboard` | HTTP 200 with fixed refresh to `http://localhost/login`; not localized |
| `/payment/paymob/webhook` | HTTP 405; fail-closed |

## H. Diagnostic Result

Passed:

- `php -l application/controllers/Home.php`
- `php -l application/controllers/Login.php`
- `php -l application/models/User_model.php`
- `php -l scripts/phase_2/youngo_localization_ar_default_auth_redirects_fix_1_diagnostic.php`
- `php scripts/phase_2/youngo_localization_ar_default_auth_redirects_fix_1_diagnostic.php`
- `php scripts/phase_2/youngo_localization_ar_default_links_1_diagnostic.php`

Diagnostic coverage:

- My Courses/My Access language detection and public redirect helper output.
- Arabic/default unprefixed behavior.
- `/en` preservation.
- `/ar` compatibility canonicalization.
- Static source wiring for public redirect helpers.
- Login auth-language storage and learner fallback redirects.
- Admin/payment/action route exclusions.
- No added checkout/payment/Paymob or DB-write code in the diff.

## I. Remaining Risks/Blockers

- Authenticated learner QA remains blocked until the owner provides the QA learner password privately.
- Login POST failure redirects remain fixed to `/login` by design because this phase avoided changing POST failure behavior.
- Registration and verification POST/display fallback language preservation remains deferred because registration writes were intentionally kept unchanged.
- Lesson/player routes themselves remain operational and unlocalized; this phase only improved safe display fallback destinations.
- The local frontend 404 behavior can still return HTTP 200 for unknown localized probes; that is a separate cleanup risk.

## J. Recommended Next Phase

Recommended next phase:

```text
LOCALIZATION.AR_DEFAULT.AUTH.REDIRECTS.QA.1
```

Scope:

- Rerun unauthenticated redirect matrix.
- Rerun authenticated learner QA when learner password is supplied privately.
- Verify post-login fallback from `/login`, `/en/login`, and `/ar/login` with a learner session.
- Confirm logout, wishlist action URLs, lesson/player actions, admin routes, and Paymob routes remain operational and unlocalized.

## K. Git Status

Expected final dirty status:

```text
 M application/controllers/Home.php
 M application/controllers/Login.php
 M application/models/User_model.php
?? docs/qa/youngo_localization_ar_default_auth_redirects_fix_1_report.md
?? scripts/phase_2/youngo_localization_ar_default_auth_redirects_fix_1_diagnostic.php
```

Recommended commit:

```text
Preserve Arabic default auth redirect language
```
