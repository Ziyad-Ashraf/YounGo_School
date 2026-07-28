# LOCALIZATION.AR_DEFAULT.AUTHENTICATED.PUBLIC.QA.1 Report

## A. Current Branch/Status

- Branch: `analysis/cms-audit`
- Start status: clean worktree
- Latest commit at start: `dbe04f4 QA Arabic default public links`
- Local mount tested: `http://localhost/`
- Root Admin credentials were not used.
- No credentials were printed, stored, committed, or included in this report.

## B. URLs Tested

Unauthenticated control probes were run for the learner-facing route set because no learner QA password was available:

- `/home/my_wishlist`
- `/home/my_courses`
- `/home/my_access`
- `/en/home/my_wishlist`
- `/en/home/my_courses`
- `/en/home/my_access`
- `/ar/home/my_wishlist`
- `/ar/home/my_courses`
- `/ar/home/my_access`

Safety probes:

- `/admin/dashboard`
- `/payment/paymob/webhook`
- `/en/admin/dashboard`
- `/ar/admin/dashboard`
- `/en/payment/paymob/webhook`
- `/ar/payment/paymob/webhook`

## C. Auth/Session QA

Authenticated learner QA was blocked because no learner QA password was provided.

Confirmed from project rules:

- QA learner user 8 exists as `qa.learner@youngo.local`.
- The project rules explicitly state not to document, print, commit, store, or reuse the QA learner password, and that the owner must provide it when learner-authenticated QA is needed.

Unauthenticated control behavior:

- `/home/my_wishlist`, `/en/home/my_wishlist`, and `/ar/home/my_wishlist` rendered sign-in-required wishlist pages and did not expose checkout/payment CTAs.
- `/home/my_courses`, `/home/my_access`, `/en/home/my_courses`, `/en/home/my_access`, `/ar/home/my_courses`, and `/ar/home/my_access` returned the existing CodeIgniter refresh redirect header to `http://localhost/home` with empty body.
- Because no learner session was available, learner account/session persistence, authenticated My Courses/My Access content, logout behavior, profile menu behavior, lesson links, and wishlist mutation behavior could not be validated as authenticated learner flows.

## D. Arabic/Default QA

Unauthenticated Arabic/default control:

- `/home/my_wishlist` returned HTTP 200 with `lang="ar"` and `dir="rtl"`.
- It rendered a sign-in-required state.
- It generated no canonical `/ar` links.
- It generated no checkout/payment/Paymob/cart CTA markers.

Authenticated Arabic/default learner QA remains blocked pending learner credentials.

## E. /en QA

Unauthenticated English control:

- `/en/home/my_wishlist` returned HTTP 200 with `lang="en"` and `dir="ltr"`.
- It rendered a sign-in-required state.
- It generated no canonical `/ar` links.
- It generated no checkout/payment/Paymob/cart CTA markers.

Authenticated `/en` learner QA remains blocked pending learner credentials.

## F. /ar Compatibility QA

Unauthenticated `/ar` compatibility control:

- `/ar/home/my_wishlist` returned HTTP 200 with `lang="ar"` and `dir="rtl"`.
- It rendered a sign-in-required state.
- It generated no canonical `/ar` links.
- It generated no checkout/payment/Paymob/cart CTA markers.

Authenticated `/ar` learner QA remains blocked pending learner credentials.

## G. Operational URL Safety

Static diagnostics and source inspection confirmed operational URL boundaries remain unlocalized:

- Wishlist mutation URLs remain under `home/toggleWishlistItems/...`.
- Login validation and registration submit URLs remain unlocalized.
- Profile/account action URLs remain unlocalized.
- Lesson/player URLs remain unlocalized.
- Free enrol/action URLs remain unlocalized.
- Checkout/payment/Paymob routes remain unlocalized.

Unauthenticated My Courses/My Access refresh redirects currently target `http://localhost/home` for Arabic, English, and `/ar` prefixed requests. This is pre-existing controller behavior and should be reviewed in a follow-up auth redirect phase.

## H. Payment/CTA Safety

- No checkout/payment/Paymob/cart CTAs appeared in the tested public learner-facing control pages.
- `/payment/paymob/webhook` returned HTTP 405 on GET, confirming fail-closed behavior.
- `/en/payment/paymob/webhook` and `/ar/payment/paymob/webhook` returned frontend 404 pages, not localized webhook handlers.
- No Paymob calls were made.
- No payment, checkout, enrol, coupon, or access rows were written.

## I. Diagnostic Result

Passed:

- `php scripts/phase_2/youngo_localization_ar_default_links_1_diagnostic.php`
- `php scripts/phase_2/youngo_localization_ar_default_route_skeleton_1_diagnostic.php`

## J. Files Changed

- `docs/qa/youngo_localization_ar_default_authenticated_public_qa_1_report.md`

## K. Remaining Risks/Blockers

- Authenticated learner QA is blocked until the owner provides the QA learner password privately.
- My Courses/My Access unauthenticated redirects use the legacy `Refresh: 0;url=http://localhost/home` behavior and do not preserve `/en` or `/ar` route context.
- The local frontend 404 page returns HTTP 200 for unknown localized admin/payment probes; this was previously observed and remains a separate status-code cleanup risk.
- Root Admin was not used because admin login was not needed for this learner-focused QA and cannot substitute for learner QA.

## L. Recommended Next Phase

Recommended next phase: `LOCALIZATION.AR_DEFAULT.AUTH.REDIRECTS.PLAN.1`

Scope:

- Plan language-aware login/redirect behavior for learner-only pages.
- Preserve admin/payment/action routes as unlocalized.
- Then rerun authenticated learner QA after the owner provides the QA learner password.

## M. Git Status

Expected final dirty status:

```text
?? docs/qa/youngo_localization_ar_default_authenticated_public_qa_1_report.md
```
