# YounGo DEMO.FIX.3 Mobile Layout Report

Phase: DEMO.FIX.3-FINALIZE — Mobile Layout Fix Report Cleanup  
Date: July 19, 2026  
Scope: Report-only finalization using already completed DEMO.FIX.3 work, existing screenshots, existing metrics, and current git diff.

## Executive Summary

DEMO.FIX.3 addressed the screenshot blockers from DEMO.SCREENSHOTS.1:

- Mobile 390px layout overflow/clipping was resolved in measured routes.
- The mobile header/navigation was made usable through responsive wrapping and stacked controls.
- Blog placeholder-looking upload images were replaced with local branded PNG graphics under the same existing filenames, preserving DB references.
- Arabic demo-surface fallback fragments were reduced by adding route-aware local phrase-map entries and converting visible course/detail labels to the YounGo frontend phrase helper.
- Contact was restyled as display-only contact information instead of a form-like panel.

No DB writes, form submissions, commits, or pushes were performed in this phase.

## Mobile Overflow Root Causes

The initial mobile screenshot issue was not a single document-width failure. Existing CSS masked some layout pressure with horizontal clipping, while several inner elements still exceeded their containers at 390px.

Root causes identified during the completed DEMO.FIX.3 work:

- Header/nav/action controls were desktop-like on mobile and did not wrap cleanly.
- Homepage hero action buttons and trust chips needed explicit mobile stacking and max-width constraints.
- Decorative pseudo-elements on the hero, final CTA, testimonial cards, and instructor avatar frame created internal overflow/clipping.
- Blog card images were laid out as narrow side media, causing the generated graphics to crop into blank/gray-looking strips.
- Contact used a message-preview panel that visually resembled a form even though no `<form>` existed.
- Scroll-reveal CSS could leave screenshot captures with low-opacity content if the capture happened before reveal state settled.

## Source/Static Files Changed

Expected modified source/static files:

- `application/helpers/youngo_frontend_language_helper.php`
- `application/views/frontend/youngo/blogs.php`
- `application/views/frontend/youngo/contact_us.php`
- `application/views/frontend/youngo/course_page.php`
- `assets/frontend/youngo/css/youngo.css`
- `uploads/blog/banner/24cafe3ab2619be087605d31e1c10ebe.png`
- `uploads/blog/banner/640c00144e98502deef858fe01036162.png`
- `uploads/blog/banner/984a9fa16980f9ce1821c18ef2fe8b79.png`
- `uploads/blog/banner/e2ab77a58574b476d89c377e463cae35.png`
- `uploads/blog/thumbnail/68e1bca98eea77eb81723605ef4a29fe.png`
- `uploads/blog/thumbnail/7d19203b5cef547e20676bef91235635.png`
- `uploads/blog/thumbnail/8183be10ff3cf8ae609966db0d18b51b.png`
- `uploads/blog/thumbnail/fa7be008c5bb2431d8a80cd0baffe858.png`

Report created:

- `docs/qa/youngo_demo_fix_3_mobile_layout_report.md`

## Screenshot Folder

Screenshots and metrics used:

- `docs/qa/screenshots/demo_fix_3/`

Important files in that folder:

- Desktop screenshots for home, Arabic home, courses, Arabic courses, blog, Arabic blog, contact, Arabic contact.
- Mobile screenshots for home, Arabic home, courses, Arabic courses, course detail, Arabic course detail, blog, Arabic blog, contact, Arabic contact, login, Arabic login, signup, Arabic signup.
- `mobile_metrics.csv`
- `mobile_metrics.json`
- `screenshot_capture.csv`

Probe file present and excluded from commit recommendation:

- `docs/qa/screenshots/demo_fix_3/home_en_mobile_cdp_probe.png`

## Before/After Mobile Overflow

Before DEMO.FIX.3:

- Mobile document width was partially masked by existing horizontal clipping.
- Internal overflow/clipping was observed on the homepage hero, final CTA panel, testimonial cards, contact grid, and instructor avatar frame.
- Header/nav controls were usable on desktop but not demo-safe at 390px.

After DEMO.FIX.3:

- Existing final metrics reported every checked mobile route at `htmlScrollWidth=390`, `htmlClientWidth=390`, and `bodyScrollWidth=390`.
- Final offender list was empty for all checked mobile routes after the final CSS adjustments.
- Checked mobile routes included:
  - `/`
  - `/ar`
  - `/home/courses`
  - `/ar/courses`
  - `/home/course/scratch-coding-for-young-creators/1`
  - `/ar/course/scratch-coding-for-young-creators/1`
  - `/blog`
  - `/blogs`
  - `/ar/blog`
  - `/contact`
  - `/home/contact_us`
  - `/ar/contact`
  - `/login`
  - `/ar/login`
  - `/sign_up`
  - `/ar/sign-up`
  - `/home/my_wishlist`
  - `/ar/wishlist`

## DB Counts Before/After

Before browser QA:

- `language=1462`
- `ci_sessions=943`
- `blogs=4`
- `youngo_blog_translations=8`
- `payment=0`
- `youngo_checkout_orders=0`

After final read-only SELECT:

- `language=1462`
- `ci_sessions=1004`
- `blogs=4`
- `youngo_blog_translations=8`
- `payment=0`
- `youngo_checkout_orders=0`

Result:

- `language` did not increase.
- Blog/content/payment/checkout counts did not change.
- `ci_sessions` increased normally from safe GET/browser activity.

## Diagnostics/Lint Result

PHP lint passed for:

- `application/helpers/youngo_frontend_language_helper.php`
- `application/views/frontend/youngo/blogs.php`
- `application/views/frontend/youngo/contact_us.php`
- `application/views/frontend/youngo/course_page.php`

Diagnostics run before finalization:

- `php scripts/phase_2/youngo_demo_fix_2_blog_i18n_diagnostic.php`
  - Result: `PASS_WITH_WARNINGS`
  - Warning: known Arabic language-table qmark corruption.
- `php scripts/phase_2/youngo_post_restore_demo_baseline_diagnostic.php`
  - Result: pass with known Arabic phrase-table warning.
- `php scripts/phase_2/youngo_demo_full_readiness_audit_diagnostic.php`
  - Result: pass with known Arabic phrase-table warnings.
- `php scripts/phase_2/youngo_blog_contact_implementation_diagnostic.php`
  - Result: pass with known stale strict-scope warnings.

`git diff --check` passed, with line-ending warnings only.

## Browser/Native-Host Loop Note

During DEMO.FIX.3, browser screenshot automation became slow and was interrupted. A `chrome-native-host.exe` process associated with Claude was detected and stopped.

Per owner instruction in DEMO.FIX.3-FINALIZE:

- No browser automation was run for this final report cleanup.
- Claude Chrome Native Host was not used again.
- No new screenshots were taken.
- The report uses already captured screenshots and existing CSV/JSON metrics only.

## Remaining Risks

- The legacy Arabic `language` table remains corrupted with many repeated question-mark values; this phase did not repair phrase data.
- Some deeper learner/account pages outside the final public screenshot scope still contain legacy Academy phrase-helper usage and were not part of this source-only screenshot polish phase.
- The screenshot folder contains a probe file, `home_en_mobile_cdp_probe.png`, which should be excluded from the commit unless deliberately retained.
- Existing diagnostics may continue warning about Arabic phrase-table health and stale strict dirty-scope expectations.

## Final Recommendation

Commit the focused DEMO.FIX.3 frontend/static screenshot polish after human review of the existing screenshots.

Stage these files/folders:

- `application/helpers/youngo_frontend_language_helper.php`
- `application/views/frontend/youngo/blogs.php`
- `application/views/frontend/youngo/contact_us.php`
- `application/views/frontend/youngo/course_page.php`
- `assets/frontend/youngo/css/youngo.css`
- `uploads/blog/banner/24cafe3ab2619be087605d31e1c10ebe.png`
- `uploads/blog/banner/640c00144e98502deef858fe01036162.png`
- `uploads/blog/banner/984a9fa16980f9ce1821c18ef2fe8b79.png`
- `uploads/blog/banner/e2ab77a58574b476d89c377e463cae35.png`
- `uploads/blog/thumbnail/68e1bca98eea77eb81723605ef4a29fe.png`
- `uploads/blog/thumbnail/7d19203b5cef547e20676bef91235635.png`
- `uploads/blog/thumbnail/8183be10ff3cf8ae609966db0d18b51b.png`
- `uploads/blog/thumbnail/fa7be008c5bb2431d8a80cd0baffe858.png`
- `docs/qa/youngo_demo_fix_3_mobile_layout_report.md`
- `docs/qa/screenshots/demo_fix_3/`

Do not stage:

- `docs/qa/screenshots/demo_fix_3/home_en_mobile_cdp_probe.png`

Optional:

- Keep `docs/qa/youngo_demo_screenshots_1_report.md` and the previous `docs/qa/screenshots/demo_screenshots_1/` artifacts only if the screenshot QA history should be committed. They were pre-existing untracked QA artifacts, not new DEMO.FIX.3 source fixes.

Suggested commit message:

```text
Polish YounGo mobile demo layout and blog visuals
```
