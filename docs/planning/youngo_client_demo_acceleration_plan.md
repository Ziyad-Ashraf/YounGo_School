# YounGo Client Demo Acceleration Plan

Status: DEMO.0 planning only. No screenshots, source changes, database writes, route changes, or payment implementation were performed in this phase.

## Current Status Summary

YounGo has the core localization foundation needed to prepare a credible client demo, but the demo should not be captured or presented yet. The platform can now support English canonical frontend URLs, Arabic `/ar/...` public route aliases, route-derived language context, RTL shell metadata, frontend content translation shaping, and bilingual dashboard content forms for course/category/section/lesson content.

The next work should focus on making the demo pages read well, look consistent, and avoid payment/checkout promises that are not implemented yet.

## Why Screenshots Are Deferred

Screenshot QA should happen after the visible demo surface is intentionally prepared. Taking screenshots now would lock attention onto incomplete copy/content rather than validating the final demo experience.

Screenshots are deferred until after:

- demo-critical visible phrases are seeded and converted;
- English and Arabic course/category content is cleaned up;
- demo-safe CTA/payment messaging is in place;
- incomplete or legacy demo data is hidden or deprioritized;
- Arabic and English pages are ready for page-by-page visual review.

## Already Ready

Arabic public routes:

- `/ar`
- `/ar/courses`
- `/ar/course/{slug}/{id}`
- `/ar/search`
- `/ar/my-courses`
- `/ar/my-access`
- `/ar/wishlist`
- `/ar/login`
- `/ar/sign-up`

Language switcher:

- frontend header includes `EN | عربي`;
- links are helper-generated;
- query strings are preserved;
- `/en` is not generated.

RTL shell:

- English pages render `lang="en"` and `dir="ltr"`;
- Arabic `/ar/...` pages render `lang="ar"` and `dir="rtl"`;
- body metadata includes language and direction classes/data attributes;
- admin/backend shell is unaffected.

Content shaping:

- Arabic pages can show Arabic translated course/category/section/lesson display fields where translations exist;
- English pages keep English/canonical fallback behavior;
- IDs, slugs, access state, entitlement behavior, media fields, progress, pricing, and CTA logic remain canonical/shared.

Bilingual dashboard forms:

- category/subcategory bilingual fields are implemented;
- course bilingual fields are implemented;
- section/lesson/text lesson bilingual fields are implemented;
- English canonical sync is preserved;
- Arabic fields are optional and do not create empty Arabic translation rows.

## Not Demo-Ready Yet

Visible copy:

- some course listing and shell strings still need phrase conversion;
- broad phrase polish is not complete;
- fallback skeleton copy is not final public copy.

Phrases:

- the controlled seed plan is ready, but the four approved phrase keys are not seeded yet;
- phrase helpers must not be used to accidentally create missing rows.

Demo content:

- the current course/category dataset includes legacy or incomplete content;
- final English and Arabic course/category/section/lesson values are not yet curated for the demo.

Categories/courses:

- the demo should show a small clean catalog, not all old/incomplete data;
- old/incomplete courses should preferably be hidden/deactivated, not deleted, until diagnostics and content expectations are updated.

CTA/payment boundary:

- Paymob is deferred;
- real checkout/subscription purchase is not demo-ready;
- CTAs need demo-safe wording that does not imply live payment.

Arabic/English final content:

- Arabic translated content exists at the framework level but still needs curated demo values;
- English demo copy should be simplified and client-ready before screenshot QA.

## Immediate Implementation Sequence

1. Phase 2U.6.6.3 - Controlled phrase seed implementation.
   - Seed only the approved phrase keys from `docs/planning/phase_2u6_6_controlled_phrase_seed_plan.md`.
   - Approved keys: `primary_navigation`, `language_switcher`, `footer_navigation`, `showing_results`.
   - Create backup/restore guard because this phase writes the `language` table.

2. Phase 2U.6.6.4 - Low-risk frontend phrase conversion for demo-critical pages.
   - Convert the existing-key safe strings identified by the inventory.
   - Convert the four newly seeded shell/listing keys.
   - Keep language switcher compact labels raw.
   - Do not do broad phrase polish.

3. DEMO.2 - Payment/CTA demo-safe boundary without Paymob.
   - Replace or gate demo-facing unavailable checkout language with safe copy such as `Request Access`, `Contact Us`, `Coming Soon`, or `Admin Activation`.
   - Preserve existing payment/checkout/cart/coupon route boundaries.
   - Do not implement Paymob or real purchase activation.

4. DEMO.3 - Demo categories/courses rebuild through dashboard.
   - Use dashboard forms, not manual SQL, after backup.
   - Build a minimal clean catalog with 3 to 5 courses.
   - Add English and Arabic content through bilingual forms.
   - Hide/deactivate old incomplete demo courses where practical instead of deleting them.

5. DEMO.4 - Final page-by-page screenshot QA.
   - Run screenshots only after phrases, CTAs, and demo content are ready.
   - Capture English and Arabic homepage, course listing, course detail, login, sign-up, wishlist/my access safe states, and boundary pages.

6. DEMO.5 - Client demo handoff checklist.
   - Confirm demo account behavior.
   - Confirm protected routes remain protected.
   - Confirm no real payment path is presented as live.
   - Prepare concise client-facing walkthrough notes.

## Skip Until After Client Review

Skip these until the client has seen the product direction:

- Paymob implementation;
- real checkout/payment/coupon purchase flow;
- subscription purchase activation;
- lesson/player/PDF Arabic aliases;
- full phrase polish;
- hreflang/canonical SEO;
- broad AJAX language propagation;
- deletion of old canonical courses/categories;
- screenshot QA before demo content is ready.

## Demo-Safe Payment Strategy

Because Paymob is deferred, the demo should avoid live purchase promises and avoid routing users into incomplete payment surfaces.

Recommended demo-safe CTAs:

- `Request Access`
- `Contact Us`
- `Coming Soon`
- `Admin Activation`

Recommended behavior:

- subscription-only and school-managed courses show access-managed or request-access messaging;
- purchase/payment routes remain out of the main demo path;
- checkout/cart/coupon write endpoints stay unlocalized and untouched;
- any paid-course discussion is framed as planned post-demo payment integration.

Paymob should be scheduled only after the client approves the demo flow, final course offer, pricing model, and purchase copy.

## Demo Content Strategy

Use a minimal, polished dataset rather than a large incomplete catalog.

Recommended categories:

| English | Arabic |
| --- | --- |
| Coding & Programming | البرمجة وتطوير المهارات الرقمية |
| Robotics & AI | الروبوتات والذكاء الاصطناعي |
| Digital Creativity | الإبداع الرقمي |
| STEM Foundations | أساسيات العلوم والتكنولوجيا |
| Entrepreneurship & Future Skills | ريادة الأعمال ومهارات المستقبل |

Recommended course count:

- 3 to 5 demo courses only.

Recommended course direction:

- one coding course;
- one robotics/AI course;
- one digital creativity course;
- optional STEM foundations course;
- optional entrepreneurship/future skills course.

Content rules:

- use bilingual dashboard forms;
- English canonical fields remain the source for legacy LMS compatibility;
- Arabic translations are optional but should be complete for demo courses;
- avoid manually editing canonical data through SQL;
- hide/deactivate old incomplete courses instead of deleting them until diagnostics are updated.

## Final Screenshot QA Timing

Screenshots should happen in DEMO.4, after:

- Phase 2U.6.6.3 phrase seed is complete;
- Phase 2U.6.6.4 low-risk phrase conversion is complete;
- DEMO.2 demo-safe CTA/payment boundary is complete;
- DEMO.3 demo content is entered and restored/verified as needed;
- Arabic and English content has been reviewed in-browser.

Screenshot QA should include both desktop and mobile for:

- `/`
- `/ar`
- `/home/courses`
- `/ar/courses`
- one demo `/home/course/{slug}/{id}`
- matching `/ar/course/{slug}/{id}`
- `/ar/login`
- `/ar/sign-up`
- wishlist/my access signed-out states where safe

## Exact Next Phases

1. Phase 2U.6.6.3 - Controlled phrase seed implementation.
2. Phase 2U.6.6.4 - Low-risk frontend phrase conversion for demo-critical pages.
3. DEMO.2 - Payment/CTA demo-safe boundary without Paymob.
4. DEMO.3 - Demo categories/courses rebuild through dashboard.
5. DEMO.4 - Final page-by-page screenshot QA.
6. DEMO.5 - Client demo handoff checklist.

