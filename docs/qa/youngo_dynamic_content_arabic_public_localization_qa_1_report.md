# DYNAMIC.CONTENT.ARABIC.PUBLIC.LOCALIZATION.QA.1 Report

## A. Current Branch/Status

- Branch at start: `analysis/cms-audit`
- Worktree at start: clean
- Latest commit at start: `283b9f1 QA localized subscription plan public pages`
- Scope: QA/audit only
- Deploy/push: not performed
- DB writes/content edits/source behavior changes: not performed
- Payment/Paymob/checkout behavior: unchanged

## B. Files/Pages Inspected

Reports read:

- `docs/qa/youngo_dynamic_content_arabic_subscriptions_public_qa_1_report.md`
- `docs/qa/youngo_language_frontend_arabic_visual_copy_polish_1_report.md`
- `docs/qa/youngo_language_frontend_dynamic_content_ar_copy_plan_1_report.md`

Files inspected:

- `application/models/Youngo_subscription_model.php`
- `application/views/frontend/youngo/subscriptions.php`
- `application/controllers/Home.php`
- `application/helpers/youngo_frontend_language_helper.php`
- `application/views/frontend/youngo/header.php`
- `application/views/frontend/youngo/footer.php`
- `application/config/routes.php`
- `scripts/phase_2/`

Pages tested by local HTTP:

- Arabic/default: `/`, `/home/courses`, `/home/course/robotics-and-ai-explorers/9`, `/subscriptions`, `/home/blog`, `/home/contact`, `/home/my_wishlist`, `/home/my_courses`, `/home/my_access`, `/login`
- `/ar`: `/ar`, `/ar/subscriptions`, `/ar/home/courses`
- English: `/en`, `/en/home/courses`, `/en/home/course/robotics-and-ai-explorers/9`, `/en/subscriptions`, `/en/home/blog`, `/en/home/contact`, `/en/login`

## C. Arabic/Default QA Matrix

| URL | Result | Language shell | Notes |
|---|---:|---|---|
| `/` | 200 | `lang="ar" dir="rtl"` | Mostly Arabic; proper names remain English; `beginner` still appears on course card content. |
| `/home/courses` | 200 | `lang="ar" dir="rtl"` | Static labels mostly Arabic; proper names remain English; no payment CTA. |
| `/home/course/robotics-and-ai-explorers/9` | 200 | `lang="ar" dir="rtl"` | Course title is Arabic; several access/duration labels remain English. |
| `/subscriptions` | 200 | `lang="ar" dir="rtl"` | Subscription static labels and plan copy localized; no payment CTA. |
| `/home/blog` | 200 | `lang="ar" dir="rtl"` | Visible static blog labels localized in tested state. |
| `/home/contact` | 200 | `lang="ar" dir="rtl"` | Static labels localized; email address remains English as expected. |
| `/home/my_wishlist` | 200 | `lang="ar" dir="rtl"` | Several wishlist/auth labels remain English. |
| `/home/my_courses` | 200 empty body | Refresh to `/` | Logged-out protected page; no body to localize. |
| `/home/my_access` | 200 empty body | Refresh to `/` | Logged-out protected page; no body to localize. |
| `/login` | 200 | `lang="ar" dir="rtl"` | Major login form/marketing copy still English. |

## D. `/ar` Compatibility QA Matrix

| URL | Result | Language shell | Notes |
|---|---:|---|---|
| `/ar` | 200 | `lang="ar" dir="rtl"` | Same visible behavior as `/`; no `/ar` canonical public links generated. |
| `/ar/subscriptions` | 200 | `lang="ar" dir="rtl"` | Subscription copy localized; generated Arabic links canonicalize to `/subscriptions`. |
| `/ar/home/courses` | 200 | `lang="ar" dir="rtl"` | Same visible behavior as `/home/courses`; no payment CTA. |

## E. `/en` QA Matrix

| URL | Result | Language shell | Notes |
|---|---:|---|---|
| `/en` | 200 | `lang="en" dir="ltr"` | English content as expected. |
| `/en/home/courses` | 200 | `lang="en" dir="ltr"` | English content as expected. |
| `/en/home/course/robotics-and-ai-explorers/9` | 200 | `lang="en" dir="ltr"` | English content as expected. |
| `/en/subscriptions` | 200 | `lang="en" dir="ltr"` | English plan names rendered; Arabic plan copy absent. |
| `/en/home/blog` | 200 | `lang="en" dir="ltr"` | English content as expected. |
| `/en/home/contact` | 200 | `lang="en" dir="ltr"` | English content as expected. |
| `/en/login` | 200 | `lang="en" dir="ltr"` | English content as expected. |

The visible Arabic language-switcher label on English pages is intentional and was excluded from “Arabic leakage” findings.

## F. Remaining English/Mixed Arabic Findings

Arabic/default findings:

- Homepage/course cards: `beginner` still appears as an English level/taxonomy value.
- Homepage/course cards: `Ziyad Ashraf`, `Nadine Omar`, `Client Admin`, and similar names remain English; these are treated as proper names unless owner wants localized display names.
- Course detail `/home/course/robotics-and-ai-explorers/9`: `Hours`, `Sign in to track access`, `Use a student account to keep course access and progress in one place.`, and `Access for this course is managed by your school/admin.` remain English.
- Wishlist `/home/my_wishlist`: `My wishlist`, `Saved courses`, `Sign in required`, `Log in to view your wishlist`, and related helper text remain English.
- Login `/login`: form and marketing copy still English, including `A safe place to keep learning`, `Pick up the next lesson with confidence.`, `Email address`, `Password`, `Show`, `Forgot password?`, and `Log in`.
- Contact `/home/contact`: `hello@youngo.academy` remains English, classified as contact data/technical address.
- Courses pages contain `arabic_translated` only as a non-visible course content-language radio value, not as the active UI language code.

No subscription-plan English copy remained on Arabic subscription pages in this pass.

## G. Classification of Findings

| Finding | Classification | Recommended handling |
|---|---|---|
| Login English form/marketing copy | Static phrase/view issue | Convert remaining login view strings to `youngo_frontend_phrase_e()` and seed/polish missing keys if needed. |
| Wishlist English labels/copy | Static phrase/view issue | Convert remaining wishlist labels and unauthenticated helper copy to URI-aware frontend phrases. |
| Course detail access messaging English | Static phrase/access-state copy issue | Wire YounGo access messages to frontend phrase helper without changing access logic. |
| `Hours` on course detail | Static/taxonomy formatting issue | Localize duration/unit formatter for course detail display. |
| `beginner` level value | Course taxonomy/display value issue | Map course level values through frontend phrase helper. |
| Proper names like `Ziyad Ashraf` / `Client Admin` | Intentional personal/proper name | Leave unchanged unless owner requests display-name localization. |
| Email address `hello@youngo.academy` | Acceptable technical/contact term | Leave unchanged. |
| `arabic_translated` course filter value | Deferred course-content marker | Allowed only as course `language_made_in` marker; do not use as UI language code. |
| My Courses/My Access logged-out empty redirects | Auth redirect behavior | Requires authenticated learner QA for rendered page copy. |

## H. Subscription Localization Status

Subscription localization is clean:

- `youngo_subscription_plan_translations`: `6` rows
- Arabic rows: `3`
- English rows: `3`
- `arabic_translated` rows: `0`
- `/subscriptions` and `/ar/subscriptions` show Arabic plan names/descriptions
- `/en/subscriptions` shows English plan names and no Arabic plan copy
- shared plan fields remain from `youngo_subscription_plans`

## I. Payment/CTA Safety

All tested public pages passed payment/CTA checks:

- no Paymob links/forms
- no checkout/order/enrol/grant action links/forms
- no Buy Now/Add to cart/Checkout/Pay now/Subscribe now CTA text
- subscription page remains safe contact/coming-soon only

No payment, checkout, route, or DB behavior was changed.

## J. Diagnostic Result

Passed:

```bash
php scripts/phase_2/youngo_dynamic_content_arabic_public_localization_qa_1_diagnostic.php
php scripts/phase_2/youngo_dynamic_content_arabic_subscriptions_public_qa_1_diagnostic.php
```

The new diagnostic is read-only and verifies:

- Arabic/default public pages render `lang="ar" dir="rtl"` where a body is rendered
- protected logged-out My Courses/My Access redirect shells preserve Arabic/default or English target context
- `/ar` compatibility pages render Arabic shell
- `/en` pages render `lang="en" dir="ltr"`
- no unexpected Arabic content appears on `/en` pages beyond the intentional language-switcher label
- `arabic_translated` is not used as a UI language code
- subscription translation rows remain valid
- no payment/checkout/Paymob CTAs appear on tested pages

## K. Recommended Fix Phases

Recommended next phases:

1. `LANGUAGE.FRONTEND.AUTH.COPY.WIRE.1`
2. `LANGUAGE.FRONTEND.WISHLIST.COPY.WIRE.1`
3. `LANGUAGE.FRONTEND.COURSE_DETAIL.ACCESS_COPY.WIRE.1`
4. `LANGUAGE.FRONTEND.COURSE_TAXONOMY_LABELS.WIRE.1`
5. `DYNAMIC.CONTENT.ARABIC.AUTHENTICATED.LEARNER.QA.1`
6. `DYNAMIC.CONTENT.ARABIC.BLOG.CONTACT.HOMEPAGE.COVERAGE.QA.1`

## L. Remaining Risks/Blockers

- Authenticated learner copy for My Courses/My Access still needs QA with learner credentials.
- Login and wishlist remain visibly English on Arabic pages until the next phrase/view wiring pass.
- Course detail access/status copy needs localization without changing entitlement behavior.
- Broader visual/pixel QA was not performed because the in-app browser was unavailable; this phase used local HTTP and rendered HTML/text inspection.

## M. Git Status

At report creation time, pending changes are limited to this phase:

```text
?? docs/qa/youngo_dynamic_content_arabic_public_localization_qa_1_report.md
?? scripts/phase_2/youngo_dynamic_content_arabic_public_localization_qa_1_diagnostic.php
```
