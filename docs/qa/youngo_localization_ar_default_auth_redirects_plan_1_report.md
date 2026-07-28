# LOCALIZATION.AR_DEFAULT.AUTH.REDIRECTS.PLAN.1 Report

Phase: LOCALIZATION.AR_DEFAULT.AUTH.REDIRECTS.PLAN.1 - Plan Auth Redirect Language Preservation

Date: 2026-07-25

## A. Current Branch/Status

- Branch at start: `analysis/cms-audit`
- Worktree at start: clean
- Latest commit at start: `b73d1e2 QA Arabic default authenticated public routes`
- This phase is planning-only.
- No deployment, push, DB write, SQL execution, source behavior change, route change, payment/Paymob change, checkout CTA exposure, Root Admin change, or credential output was performed.

## B. Files Inspected

- `YOUNGO_PROJECT_CONTEXT.md`
- `docs/design/youngo_style_direction.md`
- `docs/planning/youngo_master_plan_v2.md`
- `docs/planning/youngo_phase_2_hybrid_access_subscriptions_roles_architecture.md`
- `docs/agents/implementation_rules.md`
- `docs/reference/`
- `docs/qa/youngo_localization_ar_default_authenticated_public_qa_1_report.md`
- `docs/qa/youngo_localization_ar_default_links_1_report.md`
- `docs/qa/youngo_localization_ar_default_route_skeleton_1_report.md`
- `application/config/routes.php`
- `application/controllers/Home.php`
- `application/controllers/Login.php`
- `application/controllers/Sign_up.php`
- `application/helpers/youngo_frontend_language_helper.php`
- `application/helpers/common_helper.php`
- `application/helpers/youngo_frontend_content_helper.php`
- `application/models/User_model.php`
- `application/models/Youngo_entitlement_model.php`
- `application/views/frontend/youngo/`

Requested paths not present:

- `application/controllers/Signup.php`
- `application/controllers/Register.php`

## C. Current Redirect Map

Primary learner/account GET pages:

- `Home::my_courses()` redirects unauthenticated users to `site_url('home')`.
- `Home::my_access()` redirects unauthenticated users to `site_url('home')`.
- `Home::purchase_history()` redirects unauthenticated users to `site_url('home')`.
- `Home::profile()` redirects unauthenticated users to `site_url('home')`.
- `Home::instructor_following()` redirects unauthenticated users to `site_url('home')`.
- `Home::badges()` redirects unauthenticated users to `site_url('home')`.
- `Home::my_wishlist()` does not hard-redirect unauthenticated users; it renders a sign-in-required wishlist page with localized public links.

Course access and lesson/player paths:

- `Home::lesson()` redirects unauthenticated users, missing courses, mismatched lessons, denied access, and invalid lesson state to fixed `home` or fixed `home/course/{slug}/{id}`.
- `Home::play_lesson()` is an AJAX-style action. On allowed access it returns a JSON `redirectTo` value pointing to fixed `home/lesson/{slug}/{course_id}/{lesson_id}`.
- `Youngo_entitlement_model::get_learner_course_access_items()` builds fixed lesson URLs under `home/lesson/...`.
- `Home::get_enrolled_to_free_course()` uses fixed course detail URLs and fixed `login` for unauthenticated users, and writes URL history before login.

Login and URL history:

- `common_helper::set_url_history()` stores whatever absolute URL the caller passes into session.
- `User_model::set_login_userdata()` redirects Root/Admin users to fixed `admin/dashboard`; learner users are redirected to session `url_history` when present, otherwise fixed `home`.
- `User_model::check_session_data('login')` redirects an already-authenticated learner away from login to fixed `home/my_courses`.
- `Login::validate_login()`, registration, forgot-password, verification, and logout flows use fixed legacy endpoints.

View/link behavior:

- Header, auth views, My Courses, My Access, wishlist, and primary account menu already use frontend helper-generated display links for Arabic default and `/en`.
- Login and sign-up form actions remain intentionally fixed as `login/validate_login` and `login/register`.
- Wishlist mutation, free-enrol, lesson/player, profile update, cart, checkout, payment, and logout links remain fixed operational URLs by design.

## D. Redirects That Lose Language Context

Confirmed by prior public/authenticated QA and source inspection:

- `/en/home/my_courses` unauthenticated currently refreshes to `/home`, losing English `/en`.
- `/en/home/my_access` unauthenticated currently refreshes to `/home`, losing English `/en`.
- `/ar/home/my_courses` and `/ar/home/my_access` currently refresh to `/home`; this is acceptable only if the chosen `/ar` compatibility behavior is to canonicalize Arabic aliases to unprefixed Arabic. It should be made explicit instead of accidental.
- Direct unauthenticated `Home::lesson()` requests from `/en` route contexts can lose language when falling back to `home` or `home/course/...`.
- Expired/denied lesson access redirects to fixed Arabic-default course detail paths, so an English learner reaching the action through `/en` display context can be moved back to Arabic.
- `get_enrolled_to_free_course()` stores fixed Arabic-default course URLs in `url_history` and redirects to fixed `login`, so any English display context is lost before and after login.
- Login success fallback for learners with no `url_history` always goes to `/home`, even when login was submitted from `/en/login`.
- Already-authenticated learner visits to `/en/login` are redirected by `check_session_data('login')` to fixed `/home/my_courses`, losing English context.
- `Sign_up::verification_code()` redirects to fixed `sign_up`, and `Login::register()` redirects to fixed `sign_up/verification_code` or fixed `login`, so public auth flow error/success states can lose `/en`.

## E. Language Preservation Recommendation

Recommended approach: add a small language-aware public redirect layer and use it only for public GET/page redirects and safe post-auth display fallbacks.

Helper/controller strategy:

- Reuse `youngo_frontend_active_language()`, `youngo_frontend_public_url()`, `youngo_frontend_home_url()`, `youngo_frontend_login_url()`, `youngo_frontend_my_courses_url()`, `youngo_frontend_my_access_url()`, and course detail helper behavior.
- Add, if useful, one controller-private helper in `Home.php` for localized display redirects:
  - Determine language from the current URI.
  - Treat `/en` as English.
  - Treat `/ar` as Arabic but generate unprefixed Arabic canonical URLs.
  - Use helper-generated public URLs only when the target is a frontend GET page.
- Add, if useful, one shared helper for safe public `url_history` values:
  - Accept only local public frontend paths.
  - Canonicalize Arabic to unprefixed.
  - Preserve `/en` for English.
  - Reject admin, payment, Paymob, checkout, API, POST/AJAX, and external URLs.

Specific recommended behavior:

- Arabic/default unauthenticated page redirects should target unprefixed Arabic URLs.
- English `/en` unauthenticated page redirects should target `/en` equivalents.
- `/ar` compatibility redirects should canonicalize to unprefixed Arabic equivalents, not generate new `/ar` canonical destinations.
- `Home::my_courses()` and `Home::my_access()` should preserve current redirect semantics first by redirecting unauthenticated users to localized home:
  - `/home/my_courses` -> `/`
  - `/en/home/my_courses` -> `/en`
  - `/ar/home/my_courses` -> `/`
  - same pattern for My Access
- A later UX phase can decide whether unauthenticated account pages should redirect to localized login instead of home. Do not combine that behavior change with the preservation fix.
- `User_model::set_login_userdata()` should continue sending Root/Admin to `admin/dashboard`. For learner fallback with no `url_history`, use localized home or localized My Courses only if the login page language is available safely in session.
- `User_model::check_session_data('login')` should preserve login page context for already-authenticated learners:
  - `/login` -> `/home/my_courses`
  - `/en/login` -> `/en/home/my_courses`
  - `/ar/login` -> `/home/my_courses`
- `get_enrolled_to_free_course()` should preserve language only for the safe display URLs it stores or redirects to. It must not change the enrol/write behavior or expose checkout.
- `Home::lesson()` denied access fallbacks can use localized course detail URLs where the current route context is public-localizable; the actual lesson route should remain operational and unlocalized for now.

## F. Operational URL Safety

Keep these routes fixed and unlocalized in the implementation phase:

- Admin/backend routes, including `admin`, `admin/dashboard`, and all `admin/youngo/*`.
- Login/admin action routes, including `login/admin`, `login/validate_login`, `login/register`, `login/logout`, verification AJAX, and forgot/change-password submit endpoints.
- Wishlist mutation/action routes, including `home/toggleWishlistItems`, `home/handleWishList`, and `home/refreshwishlist`.
- Profile/account write routes, including `home/update_profile` and `home/account_disable`.
- Cart/checkout/payment/coupon routes, including `home/shopping_cart`, `home/course_payment`, `home/handle_cart_items`, `home/handle_buy_now`, `home/apply_coupon`, `payment`, and `payment/paymob/*`.
- Lesson/progress write or AJAX endpoints, including `home/play_lesson`, `home/update_watch_history_*`, `home/check_course_progress`, mobile lesson/player helper routes, and file/media endpoints.

No public checkout/payment/Paymob/cart CTA should be introduced as part of the redirect fix.

## G. Recommended Implementation Phase

Recommended next implementation phase: `LOCALIZATION.AR_DEFAULT.AUTH.REDIRECTS.FIX.1`

Scope:

- Add a minimal public redirect helper/controller method for language-aware GET display redirects.
- Update unauthenticated redirects for `my_courses`, `my_access`, profile/account display pages, lesson denied/missing display fallbacks, and already-authenticated login-page redirects where safe.
- Update safe public URL-history writes for course-detail/login flows without trusting user-supplied external URLs.
- Preserve existing action/payment/admin endpoints and form actions.
- Add a diagnostic covering language-aware redirects, URL-history safety, admin/payment exclusions, and no checkout CTA exposure.

Out of scope:

- No route rewrites.
- No `/ar` redirect cleanup.
- No canonical/hreflang work.
- No login UX redesign.
- No learner password handling.
- No payment/Paymob/cart/checkout behavior changes.

## H. QA Phase Recommendation

Recommended follow-up QA phase: `LOCALIZATION.AR_DEFAULT.AUTH.REDIRECTS.QA.1`

Unauthenticated QA:

- `/home/my_courses`
- `/home/my_access`
- `/en/home/my_courses`
- `/en/home/my_access`
- `/ar/home/my_courses`
- `/ar/home/my_access`
- `/home/lesson/{slug}/{course_id}`
- `/en/home/course/{slug}/{course_id}` followed by denied lesson/display paths where practical
- `/login`, `/en/login`, `/ar/login`

Authenticated QA:

- Rerun learner authenticated public QA when the QA learner password is supplied privately by the operator.
- Verify already-authenticated visits to `/login`, `/en/login`, and `/ar/login`.
- Verify logout remains fixed and operational.
- Verify URL history restores intended public pages without external/open redirect behavior.

Safety QA:

- Confirm admin routes are not localized.
- Confirm payment/Paymob routes are not localized.
- Confirm checkout/payment/cart CTAs remain absent from tested public surfaces.
- Confirm no DB writes except normal login/session behavior during authenticated QA.

## I. Risks/Blockers

- Authenticated learner browser QA remains blocked until the owner supplies the QA learner password privately.
- Some learner/account pages still include fixed profile and purchase-history display links; those should be reviewed separately from write/action routes.
- `set_url_history()` accepts arbitrary caller-provided URLs today. The implementation should avoid broad behavior changes but should not add more unsafe URL-history use.
- The local frontend 404 behavior can return HTTP 200 for unknown localized probes; that is a separate cleanup risk.
- `Home::lesson()` is an operational/player route. Language-aware fallback redirects can be improved, but localizing the route itself should remain deferred.
- Registration and verification flows include POST/write behavior; preserving language in display fallback redirects should be scoped carefully to avoid touching action semantics.

## J. Git Status

Expected final dirty status for this planning phase:

```text
?? docs/qa/youngo_localization_ar_default_auth_redirects_plan_1_report.md
```

Recommended commit:

```text
Plan Arabic default auth redirect preservation
```
