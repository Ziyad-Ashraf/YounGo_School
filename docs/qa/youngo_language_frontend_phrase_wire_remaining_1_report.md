# LANGUAGE.FRONTEND.PHRASE.WIRE.REMAINING.1 Report

## A. Current Branch/Status

- Branch: `analysis/cms-audit`
- Start status: clean.
- Latest commit at start: `441b4ad Seed missing public frontend phrase keys`
- No deploy, push, DB write, language pack import, phrase seed/edit, Paymob/payment change, checkout CTA exposure, Root Admin change, or credential printing was performed.

## B. Files Inspected

- `docs/qa/youngo_language_frontend_phrase_coverage_audit_1_report.md`
- `docs/qa/youngo_language_frontend_phrase_seed_missing_1_report.md`
- `docs/qa/youngo_language_frontend_phrase_wire_1_report.md`
- `application/views/frontend/youngo/`
- `application/helpers/youngo_frontend_language_helper.php`
- `application/helpers/youngo_frontend_content_helper.php`
- `application/controllers/Home.php`

## C. Files Changed

- `application/controllers/Home.php`
- `application/views/frontend/youngo/404.php`
- `application/views/frontend/youngo/account_disable.php`
- `application/views/frontend/youngo/change_password_from_forgot_password.php`
- `application/views/frontend/youngo/course_page_preview_modal.php`
- `application/views/frontend/youngo/forgot_password.php`
- `application/views/frontend/youngo/index.php`
- `application/views/frontend/youngo/my_access.php`
- `application/views/frontend/youngo/my_courses.php`
- `application/views/frontend/youngo/new_login_confirmation.php`
- `application/views/frontend/youngo/reload_my_courses.php`
- `application/views/frontend/youngo/update_user_photo.php`
- `application/views/frontend/youngo/user_credentials.php`
- `application/views/frontend/youngo/user_profile.php`
- `application/views/frontend/youngo/verification_code.php`
- `scripts/phase_2/youngo_language_frontend_phrase_wire_remaining_1_diagnostic.php`
- `docs/qa/youngo_language_frontend_phrase_wire_remaining_1_report.md`

## D. Labels/Views Converted

Converted safe public display labels from `get_phrase()` / `site_phrase()` to `youngo_frontend_phrase()` in:

- Auth/reset/verification views: forgot password, password reset, email verification, new-login confirmation
- Learner views: My Courses, My Access, reload My Courses
- Account/profile views: profile, credentials, photo update, account disable
- Shared/public utility views: 404, course preview modal, frontend placeholder shell

Also added `Home::youngo_frontend_public_phrase()` and used it for safe public page titles:

- Home
- Courses
- Course detail
- My Courses
- My Access
- My Wishlist
- Login
- Sign up
- Forgot password
- Contact us

## E. Deferred Labels/Surfaces

Deferred intentionally:

- `application/views/frontend/youngo/cart_items.php`
- `application/views/frontend/youngo/checkout_disabled.php`
- `application/views/frontend/youngo/checkout_order.php`
- `application/views/frontend/youngo/invoice.php`
- `application/views/frontend/youngo/payment_return_disabled.php`
- `application/views/frontend/youngo/purchase_history.php`
- `application/views/frontend/youngo/shopping_cart.php`
- `application/views/frontend/youngo/shopping_cart_inner_view.php`

These are cart/checkout/payment/purchase-history surfaces and should stay out of this language wiring phase while checkout remains disabled.

Controller flash messages, AJAX responses, POST targets, profile write URLs, login/register POST actions, lesson/action URLs, Paymob routes, and admin/backend routes were not localized.

## F. Arabic/Default QA

HTTP smoke QA with a temporary local PHP server:

- `/`: HTTP `200`, `<html lang="ar" dir="rtl">`
- `/home/courses`: HTTP `200`, `<html lang="ar" dir="rtl">`
- `/home/course/robotics-and-ai-explorers/9`: HTTP `200`, `<html lang="ar" dir="rtl">`
- `/subscriptions`: HTTP `200`, `<html lang="ar" dir="rtl">`
- `/home/my_wishlist`: HTTP `200`, `<html lang="ar" dir="rtl">`
- `/login`: HTTP `200`, `<html lang="ar" dir="rtl">`
- `/home/blog`: HTTP `200`, `<html lang="ar" dir="rtl">`
- `/home/contact`: HTTP `200`, `<html lang="ar" dir="rtl">`

Converted seeded labels rendered Arabic where the seeded phrase keys were used, especially subscriptions, blog/contact copy, wishlist body copy, and learner/access labels.

## G. `/en` QA

HTTP smoke QA:

- `/en`: HTTP `200`, `<html lang="en" dir="ltr">`
- `/en/home/courses`: HTTP `200`, `<html lang="en" dir="ltr">`
- `/en/home/course/robotics-and-ai-explorers/9`: HTTP `200`, `<html lang="en" dir="ltr">`
- `/en/subscriptions`: HTTP `200`, `<html lang="en" dir="ltr">`
- `/en/home/my_wishlist`: HTTP `200`, `<html lang="en" dir="ltr">`
- `/en/login`: HTTP `200`, `<html lang="en" dir="ltr">`
- `/en/home/blog`: HTTP `200`, `<html lang="en" dir="ltr">`
- `/en/home/contact`: HTTP `200`, `<html lang="en" dir="ltr">`

No Arabic script was detected in the seeded English phrase values by the diagnostic.

## H. `/ar` Compatibility QA

HTTP smoke QA:

- `/ar`: HTTP `200`, `<html lang="ar" dir="rtl">`

Generated `/ar` canonical link count on the tested pages: `0`. `/ar` remains a compatibility alias; generated Arabic links stay unprefixed.

## I. Fallback Behavior

The helper remains DB-first and URI-aware:

1. URI selected language value
2. English DB value
3. local phrase fallback
4. provided fallback
5. humanized key

Remaining copy-data limitation:

- Some older pre-existing keys still resolve to English on Arabic pages, including some nav/page-title/auth/course labels such as `Home`, `Courses`, `Login`, and similar legacy keys.
- This phase did not modify DB phrase values or seed additional keys, so those are documented as copy-data polish follow-up rather than source-wiring failures.

## J. Payment/CTA Safety

- No payment, Paymob, checkout, cart, invoice, or purchase-history files were changed.
- No Paymob routes were localized.
- No checkout/order/enrol/grant/payment URLs were introduced.
- HTTP QA found `0` forbidden Paymob/payment/checkout action links on every tested page.

## K. Diagnostic Result

Passed:

```text
php scripts/phase_2/youngo_language_frontend_phrase_wire_remaining_1_diagnostic.php
php scripts/phase_2/youngo_language_frontend_phrase_seed_missing_1_diagnostic.php
php scripts/phase_2/youngo_language_frontend_phrase_wire_1_diagnostic.php
```

Diagnostic highlights:

- `14` converted public view files have no legacy `get_phrase()` / `site_phrase()` calls.
- Non-deferred frontend views have no legacy `get_phrase()` / `site_phrase()` calls.
- Remaining frontend legacy phrase calls are confined to deferred cart/checkout/payment surfaces.
- All `83` seeded keys resolve with non-empty English and Arabic values.
- `arabic_translated` is not used in converted views.
- No forbidden payment/checkout routes were introduced in converted views.

## L. Remaining Risks/Blockers

- Legacy cart/checkout/payment/purchase-history surfaces still use legacy phrase calls by design.
- Some older common phrase DB rows still need Arabic copy cleanup or reseeding; this was outside the no-DB-write scope.
- Page titles now use the URI-aware helper, but will still show English when the underlying Arabic phrase value is missing, dirty, or intentionally fallback-only.

## M. Recommended Next Phase

Recommended next phase:

- `LANGUAGE.FRONTEND.PHRASE.COPY.POLISH.1`

Scope:

- Audit/polish existing common DB phrase values for `home`, `courses`, `login`, `sign_up`, `blog`, `contact`, `my_wishlist`, `course`, and other high-visibility legacy keys.
- Preserve manual overrides.
- Keep cart/checkout/payment phrases deferred until checkout readiness is approved.

## N. Git Status

Final expected `git status --short` for this phase:

```text
 M application/controllers/Home.php
 M application/views/frontend/youngo/404.php
 M application/views/frontend/youngo/account_disable.php
 M application/views/frontend/youngo/change_password_from_forgot_password.php
 M application/views/frontend/youngo/course_page_preview_modal.php
 M application/views/frontend/youngo/forgot_password.php
 M application/views/frontend/youngo/index.php
 M application/views/frontend/youngo/my_access.php
 M application/views/frontend/youngo/my_courses.php
 M application/views/frontend/youngo/new_login_confirmation.php
 M application/views/frontend/youngo/reload_my_courses.php
 M application/views/frontend/youngo/update_user_photo.php
 M application/views/frontend/youngo/user_credentials.php
 M application/views/frontend/youngo/user_profile.php
 M application/views/frontend/youngo/verification_code.php
?? docs/qa/youngo_language_frontend_phrase_wire_remaining_1_report.md
?? scripts/phase_2/youngo_language_frontend_phrase_wire_remaining_1_diagnostic.php
```
