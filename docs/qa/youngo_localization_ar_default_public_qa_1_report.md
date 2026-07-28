# LOCALIZATION.AR_DEFAULT.PUBLIC.QA.1 Report

## A. Current Branch/Status

- Branch: `analysis/cms-audit`
- Start status: clean worktree
- Latest commit at start: `8922dff Add Arabic default public link helpers`
- Local mount tested: `http://localhost/`
- In-app browser surface was unavailable in this session; QA used local HTTP rendered HTML inspection.

## B. URLs Tested

Arabic/default:

- `/`
- `/home/courses`
- `/home/course/robotics-and-ai-explorers/9`
- `/home/blog`
- `/home/contact`
- `/home/my_wishlist`

Arabic compatibility:

- `/ar`
- `/ar/home/courses`
- `/ar/home/course/robotics-and-ai-explorers/9`
- `/ar/home/blog`
- `/ar/home/contact`

English:

- `/en`
- `/en/home/courses`
- `/en/home/course/robotics-and-ai-explorers/9`
- `/en/home/blog`
- `/en/home/contact`

Safety:

- `/admin/dashboard`
- `/payment/paymob/webhook`
- Additional negative probes: `/en/admin/dashboard`, `/ar/admin/dashboard`, `/en/payment/paymob/webhook`, `/ar/payment/paymob/webhook`

## C. Arabic/Default QA

All Arabic/default URLs returned HTTP 200 and rendered:

- `lang="ar"`
- `dir="rtl"`
- Main nav links unprefixed:
  - `http://localhost/home/courses`
  - `http://localhost/home/blog`
  - `http://localhost/home/contact`
- No canonical `/ar` links were generated.
- No checkout/payment/Paymob/cart CTA links or markers appeared.

## D. /ar Compatibility QA

All `/ar` compatibility URLs returned HTTP 200 and rendered:

- `lang="ar"`
- `dir="rtl"`
- Main nav links canonicalized to unprefixed Arabic URLs.
- Arabic language switch target used the unprefixed equivalent.
- English language switch target used the `/en` equivalent.
- No canonical `/ar` links were generated from the rendered pages.
- No checkout/payment/Paymob/cart CTA links or markers appeared.

## E. /en QA

All `/en` URLs returned HTTP 200 and rendered:

- `lang="en"`
- `dir="ltr"`
- Main nav links under `/en`:
  - `http://localhost/en/home/courses`
  - `http://localhost/en/home/blog`
  - `http://localhost/en/home/contact`
- English language switch target stayed under `/en`.
- Arabic language switch target used the unprefixed Arabic equivalent.
- No checkout/payment/Paymob/cart CTA links or markers appeared.

## F. Link/Language Switcher QA

Verified examples:

- `/home/courses` -> English switch `http://localhost/en/home/courses`
- `/en/home/courses` -> Arabic switch `http://localhost/home/courses`
- `/ar/home/courses` -> Arabic switch `http://localhost/home/courses`
- `/home/course/robotics-and-ai-explorers/9` -> English switch `http://localhost/en/home/course/robotics-and-ai-explorers/9`
- `/en/home/course/robotics-and-ai-explorers/9` -> Arabic switch `http://localhost/home/course/robotics-and-ai-explorers/9`

Critical QA finding fixed during this phase:

- `/en/home/blog` and `/en/home/contact` initially rendered English but generated unprefixed `home/courses` header/footer links because those pages loaded the language helper but not the content helper where `youngo_frontend_courses_url()` previously lived.
- Fix applied in `application/helpers/youngo_frontend_language_helper.php` by adding guarded `youngo_frontend_courses_path()`, `youngo_frontend_courses_url()`, `youngo_frontend_search_path()`, and `youngo_frontend_search_url()` definitions to the shared language helper.
- Rerun confirmed English blog/contact header nav now stays under `/en`.

## G. Admin/Payment Safety

- `/admin/dashboard` remained unlocalized and reachable through the original admin path.
- `/en/admin/dashboard` and `/ar/admin/dashboard` did not resolve to localized admin dashboards; they returned the frontend 404 page.
- `/payment/paymob/webhook` returned HTTP 405 on GET, confirming fail-closed behavior for a callback endpoint.
- `/en/payment/paymob/webhook` and `/ar/payment/paymob/webhook` did not resolve to localized callback routes; they returned the frontend 404 page.
- No payment, Paymob, checkout, cart, enrol, or Root Admin behavior was changed.

## H. Diagnostic Result

Passed:

- `php scripts/phase_2/youngo_localization_ar_default_links_1_diagnostic.php`
- `php scripts/phase_2/youngo_localization_ar_default_route_skeleton_1_diagnostic.php`

Additional check:

- `php -l application/helpers/youngo_frontend_language_helper.php`: PASS

## I. Files Changed

- `application/helpers/youngo_frontend_language_helper.php`
- `docs/qa/youngo_localization_ar_default_public_qa_1_report.md`

## J. Remaining Risks/Blockers

- The local CodeIgniter frontend 404 page returns HTTP 200 for some unknown localized safety probes; this appears pre-existing and should be reviewed in a separate 404/status-code cleanup phase.
- Cart, checkout-disabled, invoice, purchase-history, and profile-update views still need a later route-by-route review before any localization changes are applied to payment/account-adjacent surfaces.
- No redirects, canonical tags, or hreflang changes were added by design.

## K. Recommended Next Phase

Recommended next phase: `LOCALIZATION.AR_DEFAULT.AUTHENTICATED.PUBLIC.QA.1`

Scope:

- Authenticated learner QA for My Courses, My Access, Wishlist, profile menu navigation, login/signup paths, and language switch behavior.
- Keep admin/payment/action route localization out of scope.
- Do not add redirects until authenticated public navigation is verified.

## L. Git Status

Expected final dirty status:

```text
 M application/helpers/youngo_frontend_language_helper.php
?? docs/qa/youngo_localization_ar_default_public_qa_1_report.md
```
