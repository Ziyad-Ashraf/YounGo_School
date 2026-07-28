# DEMO.ARABIC.PUBLIC.READINESS.QA.1 Report

## A. Current Branch/Status

- Branch: `analysis/cms-audit`
- Start worktree status: clean
- Latest commit at start: `a5d1179 Repair corrupt Arabic public phrase values`
- No deploy, push, content edit, DB content write, Arabic pack import, phrase seed, route change, payment/Paymob change, Root Admin change, or `arabic_translated` UI-language use was performed.

## B. Files/Reports Inspected

- `YOUNGO_PROJECT_CONTEXT.md`
- `docs/design/youngo_style_direction.md`
- `docs/planning/README.md`
- `docs/planning/youngo_master_plan_v2.md`
- `docs/planning/youngo_phase_2_hybrid_access_subscriptions_roles_architecture.md`
- `docs/agents/implementation_rules.md`
- `docs/reference/README.md`
- `docs/qa/youngo_language_phrase_corrupt_arabic_repair_1_report.md`
- `docs/qa/youngo_language_frontend_course_detail_access_copy_qa_1_report.md`
- `docs/qa/youngo_language_frontend_wishlist_copy_wire_1_report.md`
- `docs/qa/youngo_language_frontend_auth_copy_qa_1_report.md`
- `docs/qa/youngo_content_translation_blog_category_admin_ui_qa_1_report.md`
- `docs/qa/youngo_dynamic_content_arabic_subscriptions_public_qa_1_report.md`
- `application/config/config.php`
- `application/helpers/youngo_frontend_language_helper.php`

## C. Arabic/Default QA Matrix

| URL | HTTP | lang/dir | Corrupt placeholders | High-visibility body UI language | Dynamic/content notes | CTA/payment safety | Link language behavior |
|---|---:|---|---|---|---|---|---|
| `/` | 200 | `ar`/`rtl` | Pass | Pass | Arabic shell/home copy rendered | Pass | Arabic links unprefixed, `/en` link present |
| `/login` | 200 | `ar`/`rtl` | Pass | Pass in body | Browser title remains `Login | YounGo` | Pass | Arabic links unprefixed, `/en` link present |
| `/sign_up` | 200 | `ar`/`rtl` | Pass | Pass in body | Browser title remains `Sign up | YounGo` | Pass | Arabic links unprefixed, `/en` link present |
| `/login/forgot_password_request` | 200 | `ar`/`rtl` | Pass | Pass in body | Browser title remains `Forgot password | YounGo` | Pass | Arabic links unprefixed, `/en` link present |
| `/home/courses` | 200 | `ar`/`rtl` | Pass | Pass | Course-content `arabic_translated` appears only as course language metadata value, not UI language code | Pass | Arabic links unprefixed, `/en` link present |
| `/home/course/robotics-and-ai-explorers/9` | 200 | `ar`/`rtl` | Pass | Pass | Course detail/access copy localized where supported | Pass | Arabic links unprefixed, `/en` link present |
| `/home/my_wishlist` | 200 | `ar`/`rtl` | Pass | Pass | Logged-out wishlist public state rendered | Pass | Arabic links unprefixed, `/en` link present |
| `/subscriptions` | 200 | `ar`/`rtl` | Pass | Pass | Arabic subscription plan rows rendered | Pass | Arabic links unprefixed, `/en` link present |
| `/home/blog` | 200 | `ar`/`rtl` | Pass | Pass | Arabic Blog/category rows rendered | Pass | Arabic links unprefixed, `/en` link present |
| `/blog/details/helping-children-start-their-coding-journey/1` | 200 | `ar`/`rtl` | Pass | Pass | Arabic Blog detail rendered | Pass | Arabic links unprefixed, `/en` link present |
| `/home/contact` | 200 | `ar`/`rtl` | Pass | Pass | Arabic contact shell/content rendered | Pass | Arabic links unprefixed, `/en` link present |

## D. `/en` QA Matrix

| URL | HTTP | lang/dir | Corrupt placeholders | Unexpected Arabic body UI | CTA/payment safety | Link language behavior |
|---|---:|---|---|---|---|---|
| `/en` | 200 | `en`/`ltr` | Pass | Pass | Pass | `/en` links present |
| `/en/login` | 200 | `en`/`ltr` | Pass | Pass | Pass | `/en` links present |
| `/en/sign-up` | 200 | `en`/`ltr` | Pass | Pass | Pass | `/en` links present |
| `/en/home/courses` | 200 | `en`/`ltr` | Pass | Pass | Pass | `/en` links present |
| `/en/home/course/robotics-and-ai-explorers/9` | 200 | `en`/`ltr` | Pass | Pass | Pass | `/en` links present |
| `/en/subscriptions` | 200 | `en`/`ltr` | Pass | Pass | Pass | `/en` links present |
| `/en/home/blog` | 200 | `en`/`ltr` | Pass | Pass | Pass | `/en` links present |
| `/en/blog/details/helping-children-start-their-coding-journey/1` | 200 | `en`/`ltr` | Pass | Pass | Pass | `/en` links present |
| `/en/home/contact` | 200 | `en`/`ltr` | Pass | Pass | Pass | `/en` links present |

## E. `/ar` Compatibility QA Matrix

| URL | HTTP | lang/dir | Corrupt placeholders | High-visibility body UI language | CTA/payment safety | Link language behavior |
|---|---:|---|---|---|---|---|
| `/ar` | 200 | `ar`/`rtl` | Pass | Pass | Pass | Generated Arabic links remain unprefixed; `/en` link present |
| `/ar/home/courses` | 200 | `ar`/`rtl` | Pass | Pass | Pass | Generated Arabic links remain unprefixed; `/en` link present |
| `/ar/subscriptions` | 200 | `ar`/`rtl` | Pass | Pass | Pass | Generated Arabic links remain unprefixed; `/en` link present |
| `/ar/home/blog` | 200 | `ar`/`rtl` | Pass | Pass | Pass | Generated Arabic links remain unprefixed; `/en` link present |

## F. Remaining Visible Findings

1. Arabic/default auth browser titles remain English:
   - `/login`: `Login | YounGo`
   - `/sign_up`: `Sign up | YounGo`
   - `/login/forgot_password_request`: `Forgot password | YounGo`

No repeated `????` placeholders, mojibake markers, high-visibility English body UI labels, unexpected Arabic body UI on English pages, or public payment/checkout/cart/order CTAs were found on the tested pages.

## G. Demo Blocker Classification

- Demo blocker: Arabic auth page browser titles remain English if browser tabs/title metadata are visible during the demo or included in acceptance.
- No body-level Arabic/default demo blockers were found.
- No `/en` demo blockers were found.
- No `/ar` compatibility demo blockers were found.
- Deferred authenticated-only issue: logged-in wishlist AJAX/reload localization remains outside this public unauthenticated pass.
- Deferred checkout/payment/cart issue: checkout/payment/cart localization remains out of scope and no CTAs/links were exposed.
- Backend/admin issue outside public demo scope: admin phrase/backend-only Arabic polish remains outside this public frontend pass.

## H. Dynamic Content/Proper-Name Notes

- `YounGo`, EGP, route slugs, and course/blog proper names are acceptable when they appear as brand, currency, URL, or canonical/proper-name content.
- Course-content `arabic_translated` remains present only as course language metadata on the course listing filter and was not treated as UI language code.
- Dynamic translation row health:
  - `youngo_subscription_plan_translations`: English `3`, Arabic `3`, invalid `0`, `arabic_translated` `0`
  - `youngo_blog_translations`: English `4`, Arabic `4`, invalid `0`, `arabic_translated` `0`
  - `youngo_blog_category_translations`: English `3`, Arabic `3`, invalid `0`, `arabic_translated` `0`

## I. Payment/CTA Safety

- No tested public page rendered Paymob references, checkout/payment/order/cart links, `Buy Now`, `Add to cart`, `Pay now`, `Pay with Paymob`, `Subscribe now`, or equivalent Arabic payment CTAs.
- `/subscriptions` continues to render non-purchasable/contact-oriented subscription presentation only.
- Course `9` remains checkout-not-ready with no legacy cart or purchase CTA exposure.

## J. Diagnostic Result

Created and ran:

```text
scripts/phase_2/youngo_demo_arabic_public_readiness_qa_1_diagnostic.php
```

Result:

```text
PASS: Arabic public demo-readiness QA diagnostic passed read-only content/protected-table checks.
```

The diagnostic verified:

- requested Arabic/default, `/en`, and `/ar` compatibility pages returned HTTP 200
- expected `html lang` and `dir`
- no repeated question-mark placeholders
- no mojibake/corrupt Arabic markers
- no forbidden payment/checkout/Paymob CTAs or links
- dynamic subscription, Blog, and Blog category translation rows remain valid
- no content/protected DB table drift

`ci_sessions` was observed separately because this CodeIgniter app uses the database session driver. Anonymous HTTP QA changed session rows during rendering; content, phrase, dynamic translation, payment, checkout, coupon, entitlement, enrolment, and progress tables remained unchanged.

## K. Final Public Arabic Demo-Readiness Verdict

Not fully demo-ready if browser/tab titles are in scope, because three Arabic/default auth pages still have English `<title>` values.

Body-level public Arabic demo readiness is otherwise clean for the tested routes: no corrupt placeholders, no mojibake, no high-visibility English body UI labels, correct RTL/lang metadata, dynamic translations render where supported, and checkout/payment/cart CTAs remain hidden.

## L. Recommended Next Phase

Recommended next phase:

```text
LANGUAGE.AUTH.PAGE_TITLE.ARABIC.WIRE.1
```

Scope should be limited to localizing Arabic/default auth page `<title>` values for login, sign-up, and forgot password, then rerunning this public readiness diagnostic. Keep negative POST flash-message localization and logged-in wishlist AJAX localization as separate deferred phases unless the demo script requires them.

## M. Git Status

Expected changed files after this QA phase:

```text
docs/qa/youngo_demo_arabic_public_readiness_qa_1_report.md
scripts/phase_2/youngo_demo_arabic_public_readiness_qa_1_diagnostic.php
```
