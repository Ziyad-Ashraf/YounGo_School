# LOCALIZATION.AR_DEFAULT.PLAN.1 Report

Phase: LOCALIZATION.AR_DEFAULT.PLAN.1 - Arabic Default Public URL Strategy Plan

Date: 2026-07-25

## A. Current Branch/Status

- Branch: `analysis/cms-audit`
- Starting worktree status: clean
- Latest commit at start: `1590c34 Add Arabic default and subscriptions audit report`
- This phase is planning only. No routes, settings, DB data, views, payment behavior, or Root Admin behavior were changed.

Recent commits observed:

```text
1590c34 Add Arabic default and subscriptions audit report
fa1711a Add Paymob sandbox Test Center skeleton
35ef20b Add Paymob wallet integration dashboard support
d5c1ecd Add encrypted Paymob credential dashboard save
14890a8 Add encrypted YounGo Paymob credential schema
9119041 QA configured YounGo encryption key readiness
299efd6 Report local YounGo encryption key creation
c353f78 QA YounGo encryption key readiness display
cfe3ed5 Add non-committed YounGo encryption key loading
be2b0cd Plan encrypted YounGo Paymob credential storage
```

## B. Files Inspected

- `docs/qa/youngo_system_ar_default_and_dynamic_subscriptions_audit_1_report.md`
- `application/config/routes.php`
- `application/controllers/Home.php`
- `application/controllers/Blog.php`
- `application/controllers/Login.php`
- `application/controllers/Sign_up.php`
- `application/helpers/youngo_frontend_language_helper.php`
- `application/helpers/youngo_frontend_content_helper.php`
- `application/helpers/youngo_checkout_cta_helper.php`
- `application/views/frontend/youngo/index.php`
- `application/views/frontend/youngo/header.php`
- `application/views/frontend/youngo/footer.php`
- `application/views/frontend/youngo/course_page.php`
- `application/views/frontend/youngo/course_listing/course_card.php`
- `application/views/frontend/youngo/blogs.php`
- `application/views/frontend/youngo/blog_details.php`
- `application/views/frontend/youngo/contact_us.php`
- `application/views/frontend/youngo/my_courses.php`
- `application/views/frontend/youngo/my_access.php`
- `application/views/frontend/youngo/my_wishlist.php`
- `application/views/frontend/youngo/wishlist_items.php`
- `application/views/frontend/youngo/login.php`
- `application/views/frontend/youngo/sign_up.php`
- `application/models/Youngo_translation_model.php`
- `assets/frontend/youngo/css/youngo.css`
- `assets/frontend/youngo/css/lesson-player.css`

Requested paths that are not present:

- `application/controllers/Language.php`
- `application/views/frontend/youngo/layout/`
- `application/models/Youngo_course_translation_model.php`
- `application/models/Youngo_category_translation_model.php`

## C. Final URL Target

The recommended final public URL strategy is:

- Arabic canonical URLs are unprefixed.
- English canonical URLs live under `/en`.
- Existing `/ar` URLs remain compatible during transition, then redirect to the Arabic unprefixed canonical URLs after browser QA and SEO verification.
- Admin, backend, payment, Paymob, checkout, cart, coupon, API, addon, cron, and other operational routes remain outside public language routing.

Target examples:

| Page group | Arabic canonical | English canonical | Arabic compatibility |
| --- | --- | --- | --- |
| Home | `/` | `/en` | `/ar` |
| Courses | `/courses` | `/en/courses` | `/ar/courses` |
| Courses pagination | `/courses/{page}` | `/en/courses/{page}` | `/ar/courses/{page}` |
| Course detail | `/course/{slug}/{id}` | `/en/course/{slug}/{id}` | `/ar/course/{slug}/{id}` |
| Search | `/search`, `/search/{query}` | `/en/search`, `/en/search/{query}` | `/ar/search`, `/ar/search/{query}` |
| Blog list | `/blog` | `/en/blog` | `/ar/blog` |
| Blog detail | `/blog/details/{slug}/{id}` | `/en/blog/details/{slug}/{id}` | `/ar/blog/details/{slug}/{id}` |
| Contact | `/contact` | `/en/contact` | `/ar/contact` |
| Wishlist | `/wishlist` | `/en/wishlist` | `/ar/wishlist` |
| My Courses | `/my-courses` | `/en/my-courses` | `/ar/my-courses` |
| My Access | `/my-access` | `/en/my-access` | `/ar/my-access` |
| Login | `/login` | `/en/login` | `/ar/login` |
| Sign up | `/sign-up` | `/en/sign-up` | `/ar/sign-up` |
| Future subscriptions | `/subscriptions` | `/en/subscriptions` | `/ar/subscriptions` |

The old Academy-style English public aliases should not be removed immediately. Routes such as `/home/courses`, `/home/course/{slug}/{id}`, `/blogs`, `/blog/details/{slug}/{id}`, and `/sign_up` should remain reachable during the first implementation and QA phases.

## D. Affected Route Groups

### Home

Current:

- `/` uses `default_controller = home`.
- `/ar` routes to `home/index`.
- `home/index` and `home` continue to exist as legacy controller URLs.

Plan:

- Keep `/` mapped to `Home::index`.
- Add `/en` mapped to `Home::index`.
- Keep `/ar` mapped to `Home::index` during compatibility.
- Language is chosen from URI prefix, not from changing the global backend language.

### Courses

Current:

- English primary links point to `/home/courses`.
- Arabic alias is `/ar/courses`.
- `Home::courses()` uses current helper support and pagination built around the existing route shape.

Plan:

- Add `/courses` as Arabic canonical.
- Add `/en/courses` as English canonical.
- Keep `/ar/courses` as Arabic compatibility.
- Keep `/home/courses` as a temporary English legacy alias until `/en` links and browser QA are complete.
- Review pagination offset handling because `/courses/{page}`, `/en/courses/{page}`, `/ar/courses/{page}`, and `/home/courses/{page}` have different URI segment positions.

### Course Detail

Current:

- English helper output is `/home/course/{slug}/{id}`.
- Arabic helper output is `/ar/course/{slug}/{id}`.
- The course route relies on the numeric course ID for loading. The slug is display/SEO context.

Plan:

- Add `/course/{slug}/{id}` as Arabic canonical.
- Add `/en/course/{slug}/{id}` as English canonical.
- Keep `/ar/course/{slug}/{id}` as Arabic compatibility.
- Keep `/home/course/{slug}/{id}` as temporary English legacy alias.
- Do not change course IDs, entitlement checks, enrolment checks, payment checks, or lesson access behavior.

### Categories/Subcategories

Current:

- No dedicated public category/subcategory route group was found in `routes.php`.
- Course browsing appears to use the courses page and query/filter state.
- Category translation data exists through `youngo_category_translations`, but dedicated translated category slug routing is not active.

Plan:

- Use `/courses?category={slug}` style behavior in the first Arabic-default route phase.
- Canonical Arabic should be `/courses?...`.
- Canonical English should be `/en/courses?...`.
- Compatibility Arabic should be `/ar/courses?...`.
- Translated category slugs can be planned later, but should not be mixed into the route skeleton before route identity and canonical behavior are stable.

### Blog

Current:

- `Blog::index()` renders the frontend blog list.
- Existing routes include `blogs`, `blogs/(:any)`, and `/ar/blog`.
- Blog pagination and details include hardcoded unprefixed paths such as `blogs/` and `blog/details/...`.

Plan:

- Add `/blog` as Arabic canonical list route.
- Add `/en/blog` as English canonical list route.
- Keep `/ar/blog` as Arabic compatibility.
- Add or preserve detail routes for `/blog/details/{slug}/{id}`, `/en/blog/details/{slug}/{id}`, and `/ar/blog/details/{slug}/{id}`.
- Keep `/blogs` and `/blog/details/{slug}/{id}` as temporary English legacy aliases until canonical tags and redirects are introduced.

### Contact

Current:

- `/contact` maps to `home/contact_us`.
- `/ar/contact` maps to `home/contact_us`.
- The YounGo contact view already reads the active frontend language and has some Arabic-specific contact display fallbacks.

Plan:

- Keep `/contact` as Arabic canonical.
- Add `/en/contact` as English canonical.
- Keep `/ar/contact` as Arabic compatibility.
- Preserve `/home/contact_us` as a legacy controller URL, then redirect later only if GET-safe.

### Wishlist

Current:

- Main legacy route is `/home/my_wishlist`.
- Arabic alias is `/ar/wishlist`.
- Wishlist item actions use operational `home/toggleWishlistItems/{id}` URLs.

Plan:

- Add `/wishlist` as Arabic canonical.
- Add `/en/wishlist` as English canonical.
- Keep `/ar/wishlist` as Arabic compatibility.
- Keep action/AJAX URLs operational and unlocalized unless a later phase explicitly designs localized AJAX endpoints.
- Do not redirect write/action URLs.

### My Courses/My Access

Current:

- Main legacy routes are `/home/my_courses` and `/home/my_access`.
- Arabic aliases are `/ar/my-courses` and `/ar/my-access`.
- Views contain hardcoded links back to `home/courses`, `home/my_access`, and `home/course/...`.

Plan:

- Add `/my-courses` and `/my-access` as Arabic canonical.
- Add `/en/my-courses` and `/en/my-access` as English canonical.
- Keep `/ar/my-courses` and `/ar/my-access` as Arabic compatibility.
- Leave protected lesson/player/action URLs unchanged unless a later player localization phase scopes them.

### Login/Sign Up

Current:

- Public auth pages are `/login` and `/sign_up`.
- Arabic aliases are `/ar/login` and `/ar/sign-up`.
- Forms post to operational endpoints such as `login/validate_login` and `login/register`.

Plan:

- Keep `/login` as Arabic canonical login page.
- Add `/en/login` as English canonical login page.
- Add `/sign-up` as Arabic canonical sign-up page.
- Add `/en/sign-up` as English canonical sign-up page.
- Keep `/ar/login` and `/ar/sign-up` as Arabic compatibility.
- Keep `login/validate_login`, `login/register`, password reset, OAuth, and other POST/action endpoints operational and unlocalized.
- Do not redirect auth POST/action routes.

### Future Subscriptions Page

Current:

- No production-ready public dynamic subscriptions page exists.
- Existing subscription plan data is managed separately from public checkout enablement.

Plan:

- Reserve `/subscriptions` as Arabic canonical.
- Reserve `/en/subscriptions` as English canonical.
- Reserve `/ar/subscriptions` as Arabic compatibility.
- Page content should load from active subscription plan data only when the dynamic subscriptions page phase is implemented.
- CTAs must remain informational/disabled until checkout/payment gates are explicitly approved.

## E. Language Detection Priority

Recommended detection order:

1. If URI begins with a non-localizable prefix, do not apply public language routing. This includes admin/backend/payment/Paymob/checkout/cart/coupon/API/addon/cron style routes.
2. If first URI segment is `en`, force frontend language `english`.
3. If first URI segment is `ar`, force frontend language `arabic` as compatibility.
4. Otherwise, for localizable public frontend pages, default to `arabic`.
5. Normalize compatibility inputs only: `en` maps to `english`, `ar` maps to `arabic`, and deprecated `arabic_translated` maps to `arabic` only as input compatibility. It must not become a canonical UI language code.

Session/cookie behavior should be secondary to route prefix. A user session can remember preference for switcher convenience, but it must not override an explicit `/en` or `/ar` prefix, and it must not make unprefixed Arabic public URLs render English.

## F. Helper/View Link Generation Plan

The central implementation point should be helper-driven. Route additions alone will not be enough because current frontend output still generates legacy URLs.

Required helper changes:

- Update `youngo_frontend_supported_languages()` so:
  - `arabic` has `uri_prefix = ''`, `html_lang = 'ar'`, and `html_dir = 'rtl'`.
  - `english` has `uri_prefix = 'en'`, `html_lang = 'en'`, and `html_dir = 'ltr'`.
  - `/ar` is represented as a compatibility prefix, not the canonical Arabic prefix.
- Update `youngo_frontend_language_from_uri()` to detect `/en`, `/ar`, and unprefixed Arabic.
- Update `youngo_frontend_strip_language_prefix()` to strip both `en` and `ar` for route equivalence.
- Update `youngo_frontend_add_language_prefix()` so Arabic canonical routes are unprefixed and English routes are prefixed with `en`.
- Keep a separate compatibility helper or route map for `/ar`; do not make `/ar` the generated Arabic URL after the route skeleton phase.
- Update `youngo_frontend_public_route_equivalent_path()` to map legacy route names to the new canonical public paths.
- Update `youngo_frontend_language_switch_url()` so switching from Arabic to English returns `/en/...`, and switching from English to Arabic returns the unprefixed canonical URL.
- Add or extend small route-builder helpers for common page groups:
  - home
  - courses
  - course detail
  - search
  - blog list/detail
  - contact
  - wishlist
  - my courses
  - my access
  - login
  - sign-up
  - future subscriptions

Required view/link updates in later implementation phases:

- `application/views/frontend/youngo/header.php`: navigation, auth links, account links, wishlist, and language switcher.
- `application/views/frontend/youngo/footer.php`: footer navigation.
- `application/views/frontend/youngo/index.php`: fallback home link.
- `application/views/frontend/youngo/course_page.php`: breadcrumbs, share URL, related courses, contact fallback, login-required CTA URL, and browse courses fallback.
- `application/views/frontend/youngo/course_listing/course_card.php`: course detail links and CTA fallback paths.
- `application/views/frontend/youngo/blogs.php` and `blog_details.php`: list, pagination, detail, and breadcrumb URLs.
- `application/views/frontend/youngo/contact_us.php`: already uses helper-driven home/courses URLs and should be kept aligned.
- `application/views/frontend/youngo/my_courses.php`, `my_access.php`, `my_wishlist.php`, and `wishlist_items.php`: breadcrumbs, browse links, learner links, fallback course detail links, and AJAX-safe action boundaries.
- `application/views/frontend/youngo/login.php` and `sign_up.php`: public page links should become localized; POST/action endpoints should stay operational.

Controllers needing later review:

- `Home::courses()` for pagination and language-specific base URL.
- `Home::course()` for route aliases and canonical slug behavior.
- `Home::my_courses()`, `Home::my_access()`, `Home::my_wishlist()`, and `Home::contact_us()` for page language context and redirect targets.
- `Home::site_language()` and `Home::switch_language()` because session-based language switching should not override explicit URL language.
- `Blog::blogs()` for pagination base URL.
- `Blog::details()` for canonical detail URL and route compatibility.
- `Login` and `Sign_up` for public redirects that currently point to unprefixed legacy URLs.

## G. Redirect and Canonical Plan

### Phase 1: Alias-first, no redirects

During the first implementation phase, route aliases should render pages without redirects:

- `/` renders Arabic.
- `/en` renders English.
- `/ar` renders Arabic compatibility.
- `/courses`, `/en/courses`, and `/ar/courses` all render their intended language.
- Legacy routes such as `/home/courses`, `/home/course/...`, `/blogs`, `/blog/details/...`, and `/sign_up` remain reachable.

This avoids breaking existing bookmarks while the route map, link generation, and browser QA are still incomplete.

### Phase 2: Canonical and hreflang

After alias rendering works, add canonical/hreflang helpers:

- Arabic page canonical points to the unprefixed Arabic URL.
- English page canonical points to the `/en` URL.
- `/ar` compatibility pages should have canonical tags pointing to the matching unprefixed Arabic URL.
- Legacy English alias pages should have canonical tags pointing to matching `/en` URLs once `/en` coverage is complete.
- Add `hreflang="ar"`, `hreflang="en"`, and `hreflang="x-default"` where applicable.

### Phase 3: Staged redirects

Only after full public QA:

- Redirect `/ar/*` to the matching unprefixed Arabic URL.
- Redirect old English public aliases to `/en/*`.
- Use temporary `302` redirects first in local/staging QA.
- Move to permanent `301` only after production-domain verification, analytics/search-console review, and owner approval.

Do not redirect:

- Admin/backend routes.
- Paymob/payment/webhook/return routes.
- Checkout/order/cart/coupon routes.
- API/cron/addon routes.
- POST routes.
- AJAX/action routes such as wishlist toggles and auth submissions.
- Lesson/player/media routes unless a dedicated player localization phase approves that change.

## H. RTL/LTR Plan

Arabic default behavior:

- Unprefixed public frontend pages should render with `html lang="ar"` and `dir="rtl"`.
- Body classes should continue to expose `youngo-lang-arabic` and `youngo-dir-rtl`.
- Arabic phrases should use canonical UI language code `arabic`.
- `arabic_translated` remains a course-content marker only and must not be used as a UI/site language.

English behavior:

- `/en` public frontend pages should render with `html lang="en"` and `dir="ltr"`.
- English phrases should use canonical UI language code `english`.

Admin/backend behavior:

- Admin should remain unchanged unless a separate admin localization phase is approved.
- Root Admin permissions and account behavior must not be modified.

Layout risks:

- `assets/frontend/youngo/css/youngo.css` already has some `html[dir="rtl"]` handling for header/nav/language switcher, but there are many physical `left`, `right`, `padding-left`, `padding-right`, `margin-left`, `margin-right`, and `text-align` rules that need visual QA.
- `assets/frontend/youngo/css/lesson-player.css` has LTR-oriented alignment rules. Lesson/player localization should be treated as a separate risk area unless included in frontend QA.
- Language switcher direction should remain intentionally LTR enough for the `EN`/`AR` controls to stay readable.

## I. Phased Implementation Plan

### 1. LOCALIZATION.AR_DEFAULT.ROUTE.SKELETON.1

Goal:

- Add route aliases for Arabic unprefixed canonical URLs, English `/en` URLs, and `/ar` compatibility URLs.
- Keep legacy English routes reachable.
- Update language detection helpers enough for `/`, `/en`, `/ar`, and route aliases to render the intended language.
- Add diagnostics for route map, helper detection, non-localizable prefixes, and payment/admin route exclusion.

No redirects yet.

### 2. LOCALIZATION.AR_DEFAULT.HELPERS.1

Goal:

- Complete helper-level URL generation for public frontend routes.
- Add route-builder helpers for home, courses, course detail, search, blog, contact, auth, wishlist, learner pages, and future subscriptions.
- Ensure query strings are preserved when switching language.
- Ensure `/ar` is accepted but not generated as canonical Arabic.

### 3. LOCALIZATION.AR_DEFAULT.LINKS.1

Goal:

- Replace hardcoded public frontend `site_url('home/...')`, `site_url('blog...')`, `site_url('login')`, and `site_url('sign_up')` links with language-aware helpers where the link is a public page.
- Keep operational action endpoints unchanged.
- Verify header, footer, course list/detail, blog, contact, auth, wishlist, My Courses, and My Access links.

### 4. LOCALIZATION.AR_DEFAULT.SEO.1

Goal:

- Add canonical and hreflang helpers.
- Canonicalize Arabic to unprefixed URLs and English to `/en`.
- Mark `/ar` compatibility pages with Arabic canonical URLs.
- Avoid redirect behavior until QA approves it.

### 5. LOCALIZATION.AR_DEFAULT.PUBLIC.QA.1

Goal:

- Browser-QA the full route matrix:
  - `/`, `/en`, `/ar`
  - courses and pagination
  - course details
  - category/subcategory filters
  - blog list/detail
  - contact
  - wishlist
  - my courses/my access
  - login/sign-up
  - future subscriptions placeholder if present
- Confirm Arabic RTL and English LTR.
- Confirm admin/backend routes remain unchanged.
- Confirm no checkout/payment CTAs are exposed.
- Confirm Paymob/network behavior remains disabled.

### 6. LOCALIZATION.AR_DEFAULT.REDIRECTS.1

Goal:

- Add staged GET-only redirects after the route skeleton, link generation, SEO helpers, and public QA are stable.
- Start with `302` redirects.
- Only later consider `301` redirects after production-domain review and explicit owner approval.

### 7. SUBSCRIPTIONS.PAGE.DYNAMIC.ROUTE_ALIGNMENT.1

Goal:

- Apply the finalized language route strategy to the future dynamic subscriptions page.
- Use `/subscriptions` Arabic canonical, `/en/subscriptions` English canonical, and `/ar/subscriptions` compatibility.
- Keep CTAs disabled/informational until payment and checkout phases approve execution.

## J. Payment/CTA/Backend Safety

- No Paymob calls should be introduced by localization routing.
- No checkout CTAs should be exposed as part of route/link localization.
- Public checkout routes such as `/youngo/checkout/start/...` should remain excluded from public language redirects.
- Payment routes such as `/payment/paymob/webhook` and `/payment/paymob/return` should remain excluded from public language redirects.
- Legacy `payment_gateways`, `payment`, `enrol`, checkout orders, coupons, and entitlement write paths should not be touched in any Arabic-default routing phase.
- Admin/backend paths should remain outside public language detection and redirect behavior.

## K. Risks/Blockers

- Pagination segment differences are the highest route-level risk. `Home::courses()` and blog pagination should be reviewed before adding `/courses/{page}` and `/en/courses/{page}`.
- Many frontend views still contain hardcoded public links. Route aliases alone will not make generated links canonical.
- Blog currently has mixed route naming (`blog`, `blogs`, `blog/details`). This should be normalized cautiously with aliases first.
- Login/sign-up pages mix public page links and operational POST/action URLs. Only public page links should be localized.
- `/ar` compatibility should render first and redirect later. Immediate redirects would make QA harder and risk breaking existing Arabic links.
- Existing browser history/bookmarks to old English unprefixed URLs need a transition period before redirecting to `/en`.
- RTL support exists but is incomplete. Physical CSS properties and lesson/player CSS need visual QA before Arabic launch.
- Arabic content rows are not guaranteed to exist for all course/category/section/lesson records. Frontend must continue using the established fallback chain.
- No dedicated `Language.php`, YounGo layout folder, `Youngo_course_translation_model.php`, or `Youngo_category_translation_model.php` exists, so implementation should use the existing helper/model foundation instead of inventing parallel language components.

## L. Validation

Planned validation for this report-only phase:

```text
git diff --check
git status --short
```

## M. Git Status

Final git status should show only this new report file before commit.

Recommended commit:

```text
Add Arabic default URL strategy plan
```
