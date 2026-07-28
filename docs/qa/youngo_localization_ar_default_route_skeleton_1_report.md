# LOCALIZATION.AR_DEFAULT.ROUTE.SKELETON.1 Report

Phase: LOCALIZATION.AR_DEFAULT.ROUTE.SKELETON.1 - Arabic Default and English /en Route Skeleton

Date: 2026-07-25

## A. Current Branch/Status

- Branch at start: `analysis/cms-audit`
- Worktree at start: clean
- Latest commit at start: `80f8de8 Add Arabic default URL strategy plan`
- This phase implemented a route/language skeleton only.
- No deployment, push, DB modification, SQL execution, redirects, payment changes, Paymob changes, or Root Admin changes were performed.

## B. Files Inspected

- `docs/qa/youngo_localization_ar_default_plan_1_report.md`
- `docs/qa/youngo_system_ar_default_and_dynamic_subscriptions_audit_1_report.md`
- `application/config/routes.php`
- `application/controllers/Home.php`
- `application/controllers/Blog.php`
- `application/controllers/Login.php`
- `application/controllers/Sign_up.php`
- `application/helpers/youngo_frontend_language_helper.php`
- `application/helpers/youngo_frontend_content_helper.php`
- `application/helpers/youngo_checkout_cta_helper.php`
- `application/views/frontend/youngo/`
- `application/models/Youngo_translation_model.php`
- Existing Phase 2U/2S diagnostics

Requested paths still not present:

- `application/controllers/Language.php`
- `application/views/frontend/youngo/layout/`
- `application/models/Youngo_course_translation_model.php`
- `application/models/Youngo_category_translation_model.php`

## C. Files Changed

- `application/config/routes.php`
- `application/controllers/Home.php`
- `application/helpers/youngo_frontend_language_helper.php`
- `application/helpers/youngo_frontend_content_helper.php`
- `scripts/phase_2/youngo_localization_ar_default_route_skeleton_1_diagnostic.php`
- `docs/qa/youngo_localization_ar_default_route_skeleton_1_report.md`

## D. Route Skeleton Summary

Added GET-safe public route aliases for the new skeleton:

- Arabic default/unprefixed:
  - `/`
  - `/home`
  - `/home/courses`
  - `/home/course/{slug}/{id}`
  - `/home/blog`
  - `/home/contact`
- English `/en`:
  - `/en`
  - `/en/home`
  - `/en/home/courses`
  - `/en/home/courses/{page}`
  - `/en/home/course/{slug}/{id}`
  - `/en/home/search`
  - `/en/home/blog`
  - `/en/home/contact`
  - `/en/login`
  - `/en/sign-up`
- Arabic `/ar` compatibility:
  - existing `/ar`, `/ar/courses`, `/ar/course/{slug}/{id}`, `/ar/search`, `/ar/blog`, `/ar/contact`, `/ar/my-courses`, `/ar/my-access`, `/ar/wishlist`, `/ar/login`, and `/ar/sign-up` remain.
  - added `/ar/home`, `/ar/home/courses`, `/ar/home/course/{slug}/{id}`, `/ar/home/search`, `/ar/home/blog`, `/ar/home/contact`, and learner account aliases.

No redirects were added.

## E. Language Detection Summary

`youngo_frontend_language_helper.php` now uses this public frontend priority:

1. `/en` prefix forces `english`.
2. `/ar` prefix forces `arabic`.
3. Unprefixed localizable public frontend URLs default to `arabic`.
4. Admin/backend/auth action/payment/callback/API/cron/write-style routes remain non-localizable and fall back outside Arabic public detection.

Helper changes:

- English supported language prefix changed to `en`.
- Arabic supported language prefix changed to empty/unprefixed, with `ar` retained as compatibility metadata.
- Prefix stripping now handles both `en` and `ar`.
- Prefix adding now adds `en` for English and leaves Arabic unprefixed.
- Public route-equivalent helpers now generate Arabic unprefixed page paths and English `/en/...` page paths for the skeleton surfaces.
- `youngo_frontend_content_helper.php` now builds course/detail/search route paths as Arabic unprefixed or English `/en/...`.

`Home::courses()` and `Home::search()` now use a route-shape tolerant numeric pagination offset helper instead of fixed URI segment 3, so `/home/courses`, `/ar/home/courses`, and `/en/home/courses` render safely.

## F. /ar Compatibility Result

`/ar` compatibility was preserved and expanded for the requested `/ar/home/...` route shape.

Smoke-tested URLs:

- `/ar` -> HTTP 200, `lang=ar`, `dir=rtl`
- `/ar/home/courses` -> HTTP 200, `lang=ar`, `dir=rtl`
- `/ar/home/course/scratch-coding-for-young-creators/1` -> HTTP 200, `lang=ar`, `dir=rtl`
- `/ar/home/blog` -> HTTP 200, `lang=ar`, `dir=rtl`
- `/ar/home/contact` -> HTTP 200, `lang=ar`, `dir=rtl`

Existing older `/ar/courses` and `/ar/course/{slug}/{id}` aliases remain in `routes.php`.

## G. /en Route Result

English `/en` route skeleton was added and smoke-tested.

Smoke-tested URLs:

- `/en` -> HTTP 200, `lang=en`, `dir=ltr`
- `/en/home/courses` -> HTTP 200, `lang=en`, `dir=ltr`
- `/en/home/course/scratch-coding-for-young-creators/1` -> HTTP 200, `lang=en`, `dir=ltr`
- `/en/home/blog` -> HTTP 200, `lang=en`, `dir=ltr`
- `/en/home/contact` -> HTTP 200, `lang=en`, `dir=ltr`

No `/en` payment, checkout, admin, API, or callback routes were added.

## H. Admin/Payment Route Safety

- Admin route localization was not added.
- `/admin/dashboard` remained reachable separately in local HTTP smoke with HTTP 200.
- `/payment/paymob/webhook` remained fail-closed in local HTTP smoke with HTTP 405.
- `/payment/paymob/return` routes were not changed.
- No Paymob library/controller/model/view files were changed.
- No checkout/payment/coupon/order/enrol files were changed.
- No payment network behavior was enabled.
- No checkout CTA gates were enabled.
- No legacy `payment_gateways` usage was added.

Non-localizable helper exclusions were tightened for known public auth action and AJAX/write endpoints such as login validation, registration, wishlist toggle, reviews, contact submit, profile updates, coupon/cart/payment paths, Paymob/payment callbacks, API, and cron.

## I. Browser/Local HTTP QA Summary

Local Apache/PHP was already running. No new server was started.

Smoke checks:

| URL | Result |
| --- | --- |
| `/` | HTTP 200, `lang=ar`, `dir=rtl` |
| `/home/courses` | HTTP 200, `lang=ar`, `dir=rtl` |
| `/ar` | HTTP 200, `lang=ar`, `dir=rtl` |
| `/ar/home/courses` | HTTP 200, `lang=ar`, `dir=rtl` |
| `/en` | HTTP 200, `lang=en`, `dir=ltr` |
| `/en/home/courses` | HTTP 200, `lang=en`, `dir=ltr` |
| `/admin/dashboard` | HTTP 200, admin route not localized |
| `/payment/paymob/webhook` | HTTP 405, fail-closed |

Additional smoke checks passed:

- `/home/course/scratch-coding-for-young-creators/1`
- `/ar/home/course/scratch-coding-for-young-creators/1`
- `/en/home/course/scratch-coding-for-young-creators/1`
- `/home/blog`
- `/ar/home/blog`
- `/en/home/blog`
- `/home/contact`
- `/ar/home/contact`
- `/en/home/contact`

Rendered public pages checked in this phase did not contain `youngo/checkout`, `home/course_payment`, `payment/paymob`, `checkout_order`, or `checkout/start` links.

## J. Diagnostic Result

Created and ran:

```text
php scripts/phase_2/youngo_localization_ar_default_route_skeleton_1_diagnostic.php
```

Result: PASS.

The diagnostic verifies:

- `/en` route prefix is recognized for English.
- `/ar` route prefix remains recognized for Arabic.
- Unprefixed public frontend defaults to Arabic.
- Admin/backend routes are not localized.
- Payment/webhook/return routes are not localized.
- Auth action and AJAX/write-style routes stay outside language routing.
- Checkout CTA gating remains present.
- Paymob routes remain unlocalized.
- Changed-file scope excludes DB/payment/checkout/Paymob files.
- No redirects/header redirect behavior was added.
- Existing Phase 2S CTA boundary diagnostic still passes.

## K. What Was Not Changed

- No DB schema or data was changed.
- No SQL was executed.
- No redirects were added.
- No canonical or hreflang metadata was added.
- No admin/backend localization was added.
- No broad frontend link-localization pass was performed.
- No payment, checkout, coupon, Paymob, order, enrolment, entitlement, or Root Admin behavior was changed.
- No real Paymob calls, intentions, charges, or payment network actions were enabled.

## L. Remaining Risks/Blockers

- Many views still contain hardcoded public links and need the next helper/link pass.
- Blog detail route/link behavior is still mixed between `blog`, `blogs`, and `blog/details`.
- The language switcher will now generate the new skeleton paths, but a full link audit remains required.
- Full visual RTL polish remains incomplete.
- Lesson/player/PDF localized aliases remain deferred.
- Canonical/hreflang SEO and staged redirects remain deferred.
- Arabic phrase/content polish remains required before public Arabic launch.

## M. Recommended Next Phase

Recommended next phase:

```text
LOCALIZATION.AR_DEFAULT.LINKS.1
```

Scope:

- Convert public frontend generated links to helper-built Arabic unprefixed and English `/en` URLs.
- Keep POST/AJAX/payment/callback endpoints unchanged.
- Add route/link diagnostics for header, footer, course pages, blog, contact, auth pages, wishlist, My Courses, and My Access.

## N. Git Status

Final validation status:

```text
 M application/config/routes.php
 M application/controllers/Home.php
 M application/helpers/youngo_frontend_content_helper.php
 M application/helpers/youngo_frontend_language_helper.php
?? docs/qa/youngo_localization_ar_default_route_skeleton_1_report.md
?? scripts/phase_2/youngo_localization_ar_default_route_skeleton_1_diagnostic.php
```

Recommended commit:

```text
Add Arabic default route skeleton
```
