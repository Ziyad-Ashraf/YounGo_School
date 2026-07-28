# SYSTEM.AR_DEFAULT_AND_DYNAMIC_SUBSCRIPTIONS.AUDIT.1

## A. Current Branch/Status

- Branch at start: `analysis/cms-audit`.
- Worktree at start: clean.
- Latest commits at start included Paymob preparation:
  - `fa1711a Add Paymob sandbox Test Center skeleton`
  - `35ef20b Add Paymob wallet integration dashboard support`
  - `d5c1ecd Add encrypted Paymob credential dashboard save`

This phase was audit/planning only. No routes, DB data, SQL migrations, payment gates, checkout CTAs, Root Admin records, or public subscriptions page were changed.

## B. Files Inspected

- `YOUNGO_PROJECT_CONTEXT.md`
- `docs/design/youngo_style_direction.md`
- `docs/planning/youngo_master_plan_v2.md`
- `docs/planning/youngo_phase_2_hybrid_access_subscriptions_roles_architecture.md`
- `docs/agents/implementation_rules.md`
- `application/config/routes.php`
- `application/config/config.php`
- `application/controllers/Home.php`
- `application/controllers/Blog.php`
- `application/controllers/Login.php`
- `application/controllers/Sign_up.php`
- `application/controllers/Admin.php`
- `application/controllers/Youngo_subscription_plans.php`
- `application/controllers/Youngo_manual_grants.php`
- `application/controllers/Youngo_checkout.php`
- `application/controllers/Youngo_payment_settings.php`
- `application/controllers/Youngo_payment_webhook.php`
- `application/controllers/Youngo_payment_return.php`
- `application/helpers/youngo_frontend_language_helper.php`
- `application/helpers/youngo_frontend_content_helper.php`
- `application/helpers/youngo_checkout_cta_helper.php`
- `application/helpers/multi_language_helper.php`
- `application/helpers/common_helper.php`
- `application/models/Youngo_translation_model.php`
- `application/models/Youngo_subscription_model.php`
- `application/models/Youngo_entitlement_model.php`
- `application/models/Youngo_entitlement_write_model.php`
- `application/models/Youngo_checkout_model.php`
- `application/views/frontend/youngo/index.php`
- `application/views/frontend/youngo/header.php`
- `application/views/frontend/youngo/footer.php`
- `application/views/frontend/youngo/course_page.php`
- `application/views/frontend/youngo/course_listing/course_card.php`
- `application/views/frontend/youngo/course_listing/filter_panel.php`
- `application/views/frontend/youngo/blogs.php`
- `application/views/frontend/youngo/blog_details.php`
- `application/views/frontend/youngo/contact_us.php`
- `application/views/frontend/youngo/login.php`
- `application/views/frontend/youngo/sign_up.php`
- `application/views/frontend/youngo/my_courses.php`
- `application/views/frontend/youngo/my_access.php`
- `application/views/frontend/youngo/my_wishlist.php`
- `application/views/frontend/youngo/wishlist_items.php`
- `application/views/frontend/youngo/checkout_disabled.php`
- `application/views/frontend/youngo/checkout_order.php`
- `application/views/frontend/youngo/payment_return_disabled.php`
- `application/views/frontend/default-new/home_language.php`
- `application/views/frontend/default-new/home_language_assets.php`
- `application/views/backend/admin/youngo_subscription_plans.php`
- `application/views/backend/admin/youngo_subscription_plan_form.php`
- `application/views/backend/admin/youngo_subscription_plan_view.php`
- `application/views/backend/admin/manage_language.php`
- `database/phase_2/youngo_phase_2e_schema_up.sql`
- `database/phase_2/youngo_phase_2e_seed_data.sql`
- `database/phase_2/youngo_phase_2l_subscription_plan_management_schema_up.sql`
- `docs/qa/youngo_payment_paymob_sandbox_test_center_skeleton_1_report.md`

Requested paths not present under those exact names:

- `application/controllers/Language.php`
- `application/controllers/Subscription.php`
- `application/controllers/Subscriptions.php`
- `application/models/Language_model.php`
- `application/models/Youngo_course_translation_model.php`
- `application/models/Youngo_category_translation_model.php`
- `application/models/Youngo_home_model.php`
- `application/views/frontend/default/`

## C. Current Language/Default URL Behavior

CodeIgniter is still configured with `$config['language'] = 'english'`, and the default controller is `home`.

The YounGo public frontend uses `youngo_frontend_language_helper.php` for request language detection:

- `english`: no URI prefix, `html lang="en"`, `dir="ltr"`.
- `arabic`: `ar` URI prefix, `html lang="ar"`, `dir="rtl"`.
- `youngo_frontend_language_from_uri()` returns `arabic` only when the first URI segment is `ar` and the URI is localizable.
- All other localizable public URIs resolve to `english`.
- Admin, payment, Paymob, checkout, cart, coupon, API, and cron-style prefixes are explicitly treated as non-localizable.

Current effective public URL map:

- English/unprefixed: `/`, `/home`, `/home/courses`, `/home/course/{slug}/{id}`, `/home/search`, `/blogs`, `/blog`, `/contact`, `/login`, `/sign_up`, `/home/my_courses`, `/home/my_access`, `/home/my_wishlist`.
- Arabic aliases: `/ar`, `/ar/courses`, `/ar/courses/{page}`, `/ar/course/{slug}/{id}`, `/ar/search`, `/ar/search/{query}`, `/ar/blog`, `/ar/contact`, `/ar/my-courses`, `/ar/my-access`, `/ar/wishlist`, `/ar/login`, `/ar/sign-up`.
- No explicit aliases were found for `/ar/home/courses` or `/ar/home/course/...`.

Language switcher behavior:

- Header/footer links call `youngo_frontend_language_switch_url()`.
- The switcher currently maps English to the unprefixed route and Arabic to the `/ar` route.
- Query strings are preserved for helper-supported public routes.
- Some blog category/detail links still point to unprefixed legacy paths such as `blogs?category=...` and `blog/details/...`.

Legacy session language behavior:

- `Home::site_language()` and `Home::switch_language()` set `$this->session->userdata('language')`.
- `get_phrase()` reads session language or the `settings.language` DB value.
- `site_phrase()` creates a session language default of `english` if missing.
- The YounGo frontend active language is URI-driven, not session/cookie-driven. No frontend language cookie flow was found.

## D. Arabic Default Target Behavior

The target behavior should be:

- Arabic becomes the default public language on unprefixed public URLs.
- Unprefixed public URLs render Arabic content and RTL layout.
- English receives an explicit URL namespace.
- Existing `/ar` URLs remain supported during the transition.
- Admin/dashboard, payment, checkout, API, cart, coupon, webhook, return, and other non-public operational routes remain outside the public language switch.

The first implementation should change YounGo frontend route/helper behavior, not the global CodeIgniter language or legacy phrase session behavior. Changing `config.php` or `settings.language` first would risk backend/admin and legacy LMS phrase behavior outside the intended public surface.

## E. English URL Strategy Options and Recommendation

Option A: Arabic unprefixed, English under `/en`.

- Recommended.
- Gives one canonical URL per language.
- Allows Arabic to own `/`, `/courses`, `/course/...`, `/blog`, `/contact`, `/login`, `/sign-up`, `/my-courses`, `/my-access`, and `/wishlist`.
- Allows English equivalents under `/en`, for example `/en`, `/en/courses`, `/en/course/...`, `/en/blog`, `/en/contact`.
- Minimizes ambiguity in helper logic, canonical tags, hreflang, QA, and future subscription routes.

Option B: Arabic unprefixed, keep old English unprefixed as redirects/legacy aliases.

- Useful only as a temporary migration bridge.
- Unsafe as a final strategy because unprefixed URLs cannot be both Arabic canonical URLs and English canonical URLs.

Option C: Mixed approach.

- Possible if legacy route limits block full `/en` coverage, but it increases QA and SEO complexity.
- Should be avoided unless a route-by-route implementation blocker is proven.

Recommended path:

1. Add `/en` route aliases for all current English public pages while keeping existing English unprefixed pages during QA.
2. Update YounGo language helpers so localizable unprefixed public URLs resolve to `arabic`, and `/en` resolves to `english`.
3. Update header/footer/course/blog/contact/auth/account links to generate Arabic unprefixed and English `/en` URLs.
4. Keep `/ar` as a compatibility alias initially; after QA, redirect `/ar/*` to the matching unprefixed Arabic URL.
5. Redirect old English unprefixed URLs to `/en/*` only after Arabic unprefixed pages are verified and stakeholders accept the bookmark/SEO impact.
6. Add canonical and hreflang metadata: Arabic canonical unprefixed, English canonical `/en`, `hreflang="ar"`, `hreflang="en"`, and `x-default` pointing to Arabic root.

## F. Language/Content Source Findings

Arabic and English UI labels come from several layers:

- YounGo frontend labels: `youngo_frontend_phrase()` local phrase map first, then `language` table columns `english` and `arabic`, then English/fallback text.
- Legacy LMS labels: `get_phrase()` and `site_phrase()` using session language and/or `settings.language`.
- Course/category/section/lesson content: `Youngo_translation_model` and translation tables using `language_code` values `english` and `arabic`.
- Blog list content: `application/views/frontend/youngo/blogs.php` checks `youngo_blog_translations` using `english` or `arabic`.
- Homepage text can be localized through YounGo frontend helper maps and CMS-managed home page content.

Confirmed language-code rule:

- The UI/content translation language code should be `arabic`.
- `arabic_translated` is accepted only as a compatibility input alias in normalization.
- `language_made_in = arabic_translated` is a course-content/video/material marker and must not be used as a site UI language code or translation-table language code.

## G. RTL/LTR/Layout Risks

Arabic default requires RTL by default for public pages. The YounGo shell already sets `html dir` and body metadata from the active language.

Existing support:

- `application/views/frontend/youngo/index.php` sets `html lang` and `dir`.
- `assets/frontend/youngo/css/youngo.css` includes some `html[dir="rtl"]` rules for header/navigation/language switcher.
- Currency helpers already detect Arabic routes in some cases and can display Arabic EGP symbols.

Risks:

- `assets/frontend/youngo/css/youngo.css` still contains many physical `left`, `right`, `padding-left`, `padding-right`, `margin-left`, `margin-right`, and `text-align: left` rules.
- `assets/frontend/youngo/css/lesson-player.css` also has LTR-oriented alignment.
- Blog category/detail links and some breadcrumbs use hardcoded unprefixed route strings.
- Auth pages, My Courses, My Access, Wishlist, disabled checkout pages, and payment return pages need visual RTL smoke QA even if payment routes remain non-localizable.
- Legacy `default-new` theme files still contain hardcoded `home/...` links, but the current YounGo public theme is the relevant target for this change.

## H. Current Subscription Data Source

Primary plan table:

- `youngo_subscription_plans`

Plan fields from the schema/model:

- `id`
- `name`
- `slug`
- `duration_days`
- `price`
- `currency`
- `is_active`
- `is_purchasable`
- `is_featured`
- `sort_order`
- `created_at`
- `updated_at`
- `archived_at`
- `archived_by_user_id`

Related subscription/access tables:

- `youngo_user_subscriptions`
- `youngo_manual_grants`
- `youngo_checkout_orders`
- `youngo_coupon_subscription_plans`

Admin-managed model/controller:

- `Youngo_subscription_model`
- `Youngo_subscription_plans`

Current model behavior:

- Admin listing uses `list_plans(true)`.
- Create/edit/status/archive/restore are gated by schema readiness and EGP currency readiness.
- Activation requires EGP, positive price, and positive duration.
- Purchasable requires active.
- Archive forces inactive and non-purchasable.
- No public plan-listing method exists yet.

Project context and prior reports indicate the local seed/subscription state is three EGP placeholder plans (`monthly`, `3-months`, `yearly`) that remain inactive and non-purchasable until owner approval.

No subscription-plan translation fields/table were found. Plan names are currently single canonical strings; no plan description field exists in `youngo_subscription_plans`.

## I. Current Subscription UI/Static Content Findings

Existing admin UI:

- `/admin/youngo/subscription-plans`
- `/admin/youngo/subscription-plans/create`
- `/admin/youngo/subscription-plans/{id}`
- `/admin/youngo/subscription-plans/{id}/edit`
- POST-only status/archive/restore routes

Existing learner UI:

- `My Access` displays active/inactive user subscription records through `Youngo_entitlement_model::get_learner_subscription_summary()`.
- `My Courses` can show subscription-access state, but does not list every subscription-eligible course just because a learner has an active subscription.
- Course cards and course detail pages show subscription access or checkout-not-ready messaging.

No dedicated public YounGo subscriptions/pricing page or route was found.

No dynamic public plan cards were found.

Some legacy/default-new files contain newsletter subscribe sections or generic pricing CSS, but those are not a YounGo subscription-plan page and do not read `youngo_subscription_plans`.

## J. Dynamic Subscriptions Page Recommendation

Recommended public route strategy depends on the Arabic-default decision:

- Arabic canonical route after Arabic-default implementation: `/subscriptions`.
- English canonical route if Option A is accepted: `/en/subscriptions`.
- Temporary compatibility route: `/ar/subscriptions`, initially aliasing or 302 redirecting to `/subscriptions`, then 301 after QA.

Recommended behavior:

- Read from `youngo_subscription_plans`, not static cards.
- Show only plans that are:
  - not archived,
  - `is_active = 1`,
  - `is_purchasable = 1`,
  - `currency = EGP`,
  - positive `price`,
  - positive `duration_days`.
- Sort by featured, `sort_order`, then `id`.
- Show an empty state if no active purchasable plans exist.
- Show EGP price and duration.
- Do not create checkout orders.
- Do not call Paymob.
- Do not link to legacy cart/payment.
- Do not expose public checkout CTAs until a later approved payment/subscription checkout phase.

Recommended implementation shape:

- Add a read-only public method to `Youngo_subscription_model`, for example `list_public_purchasable_plans($language_code = 'arabic')`.
- Add a public frontend action, preferably `Home::subscriptions()` for consistency with current YounGo public pages.
- Add a YounGo frontend view, for example `application/views/frontend/youngo/subscriptions.php`.
- Add helper route/link support in `youngo_frontend_language_helper.php` and header/footer only after the Arabic-default URL strategy is implemented.

Bilingual content decision:

- Minimal first version can render current plan fields dynamically with translated page chrome and a safe empty state.
- A production-quality bilingual plan page needs additive plan translation support because current plan rows have no Arabic/English description fields. Recommended future schema: `youngo_subscription_plan_translations` with `plan_id`, `language_code`, `name`, optional `description`, optional `summary/badge`, timestamps, and a unique `plan_id/language_code` key.

## K. Payment/CTA Safety Notes

Current safety state:

- Paymob Test Center exists and is read-only.
- Paymob network execution remains disabled.
- Checkout CTA gate remains disabled.
- Live mode remains disabled.
- `/payment/paymob/webhook` is fail-closed while webhook testing is disabled.
- `/payment/paymob/return` is disabled/no-write.
- `youngo_checkout_cta_helper.php` blocks CTAs unless checkout CTA, route, local testing, sandbox, EGP, and network/readiness gates allow it.
- Subscription checkout issuance is explicitly not implemented in `Youngo_entitlement_write_model::issue_subscription_purchase()`.

Subscriptions page CTA rule:

- Public plan cards should render no payment/checkout URL in the first dynamic page phase.
- If a visible action is needed, use disabled/non-functional text such as `Subscription checkout is not available yet` or a contact/browse-courses link.
- Do not use legacy `payment_gateways`, `payment`, `enrol`, or cart routes to shortcut subscription purchase.

## L. Recommended Implementation Phases

Arabic default:

- `LOCALIZATION.AR_DEFAULT.PLAN.1`
- `LOCALIZATION.AR_DEFAULT.ROUTE.SKELETON.1`
- `LOCALIZATION.AR_DEFAULT.LINKS.1`
- `LOCALIZATION.AR_DEFAULT.SEO.1`
- `LOCALIZATION.AR_DEFAULT.PUBLIC.QA.1`
- `LOCALIZATION.EN_ALIAS.ROUTES.1`

Dynamic subscriptions:

- `SUBSCRIPTIONS.PAGE.DYNAMIC.PLAN.1`
- `SUBSCRIPTIONS.PAGE.DYNAMIC.MODEL.1`
- `SUBSCRIPTIONS.PAGE.DYNAMIC.UI.1`
- `SUBSCRIPTIONS.PAGE.DYNAMIC.I18N.CONTENT.1` if bilingual plan content is approved
- `SUBSCRIPTIONS.PAGE.DYNAMIC.QA.1`

Suggested sequencing:

1. Add `/en` aliases and Arabic-default helper skeleton.
2. Convert header/footer/content links and add canonical/hreflang.
3. Run public Arabic/English route QA.
4. Add the dynamic subscriptions model/view with disabled CTA behavior.
5. Run subscriptions page QA across Arabic default and English `/en`.

## M. Risks/Blockers

- `/en` does not exist yet.
- `/ar` currently owns Arabic canonical traffic, so redirects must be staged carefully to avoid breaking existing bookmarks.
- Old unprefixed English URLs will conflict with Arabic canonical URLs after the default switch unless `/en` is created.
- Legacy `get_phrase()`/`site_phrase()` use session/settings language and can write language rows if used in the wrong public context; do not use a global language switch as the first step.
- Blog category/detail and pagination links need route-helper cleanup.
- Some views still hardcode `home`, `home/courses`, `blog`, or `contact`.
- RTL CSS coverage is incomplete because many physical directional CSS properties remain.
- Subscription plans have no translation/description fields, so a rich Arabic default subscriptions page needs a small additive bilingual content plan.
- Current placeholder plans are inactive/non-purchasable; a dynamic page should show an empty state until owner-approved activation.
- Payment/checkout is intentionally not ready for subscription purchase.

## N. Git Status

Starting status:

```text
clean
```

Expected status after this report is created:

```text
?? docs/qa/youngo_system_ar_default_and_dynamic_subscriptions_audit_1_report.md
```
