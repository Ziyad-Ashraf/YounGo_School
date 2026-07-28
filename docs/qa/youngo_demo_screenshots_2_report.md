# YounGo DEMO.SCREENSHOTS.2 Final Quick Public Screenshot QA

Phase: DEMO.SCREENSHOTS.2 — Final Quick Public Screenshot QA  
Date: July 19, 2026  
Mode: Read-only screenshot QA after DEMO.FIX.3

## 1. Git State

Starting checks:

- Branch: `analysis/cms-audit`
- Latest commit: `bb4532a Polish YounGo mobile demo layout and blog visuals`
- Starting worktree: clean

No commit or push was performed.

## 2. Screenshot Method

Screenshots were captured using direct Microsoft Edge headless automation from PowerShell through Edge DevTools Protocol.

Not used:

- Claude Chrome Native Host
- MCP browser bridge
- Form submissions
- SQL writes

The browser run completed in about one minute, below the 10-minute stop threshold.

## 3. Screenshot Folder

Output folder:

```text
docs/qa/screenshots/demo_screenshots_2/
```

Generated support files:

- `screenshot_qa_matrix.csv`
- `screenshot_qa_matrix.json`

## 4. Pages Captured

Desktop `1440x1000`:

- `/` -> `home_en_desktop.png`
- `/ar` -> `home_ar_desktop.png`
- `/home/courses` -> `courses_en_desktop.png`
- `/ar/courses` -> `courses_ar_desktop.png`
- `/blog` -> `blog_en_desktop.png`
- `/ar/blog` -> `blog_ar_desktop.png`
- `/contact` -> `contact_en_desktop.png`
- `/ar/contact` -> `contact_ar_desktop.png`

Mobile `390x844`:

- `/` -> `home_en_mobile.png`
- `/ar` -> `home_ar_mobile.png`
- `/home/courses` -> `courses_en_mobile.png`
- `/ar/courses` -> `courses_ar_mobile.png`
- `/blog` -> `blog_en_mobile.png`
- `/ar/blog` -> `blog_ar_mobile.png`
- `/contact` -> `contact_en_mobile.png`
- `/ar/contact` -> `contact_ar_mobile.png`
- `/login` -> `login_en_mobile.png`
- `/ar/login` -> `login_ar_mobile.png`
- `/sign_up` -> `signup_en_mobile.png`
- `/ar/sign-up` -> `signup_ar_mobile.png`

## 5. Mobile Overflow Result

Pass.

All captured mobile routes reported:

- `htmlScrollWidth=390`
- `htmlClientWidth=390`

No horizontal overflow was detected in the final mobile QA matrix.

## 6. Blog Result

Pass with minor visual note.

- `/blog` and `/ar/blog` rendered real DB posts.
- English and Arabic Blog pages rendered with correct `lang`/`dir`.
- No skeleton text, repeated `????`, `/en` links, PHP errors, payment CTA, or comment forms were detected.
- Blog thumbnail URLs returned HTTP 200 and `image/png`.
- Visual check confirmed blog graphics are visible and no longer gray dimension placeholders.

Minor note:

- One generated image card crops some text inside the image artwork. This is cosmetic, not a broken-image blocker.

## 7. Contact Result

Pass.

- `/contact` and `/ar/contact` rendered display-only contact content.
- DOM matrix reported zero forms on contact pages.
- No POST form, submit button, PHP error, payment CTA, skeleton text, repeated `????`, or `/en` link was detected.
- Visual check confirms the page reads as contact information rather than a live form.

## 8. Arabic Result

Pass for final screenshot scope.

- Arabic routes rendered with `lang=ar` and `dir=rtl`.
- No repeated visible `????` detected.
- No `/en` links detected.
- Arabic Blog displays translated post content.
- Arabic Contact/Login/Signup routes rendered as RTL and clean in the captured QA matrix.

## 9. DB Counts Before/After

Before screenshot run:

- `language=1462`
- `ci_sessions=1004`
- `blogs=4`
- `youngo_blog_translations=8`
- `payment=0`
- `youngo_checkout_orders=0`

After screenshot run:

- `language=1462`
- `ci_sessions=1005`
- `blogs=4`
- `youngo_blog_translations=8`
- `payment=0`
- `youngo_checkout_orders=0`

Result:

- `language` did not increase.
- Blog, translation, payment, and checkout counts stayed unchanged.
- `ci_sessions` increased by 1, which is a normal GET/browser-session side effect.

## 10. Blockers

No screenshot blockers found in this quick final pass.

Known non-blocking notes:

- Legacy Arabic phrase-table corruption remains outside this phase.
- The matrix briefly flagged some blog images as incomplete at evaluation time, but direct HTTP checks returned 200/image PNG and screenshots show visible images.

## 11. Final Demo Screenshot Verdict

Pass.

The public demo screenshot surface is acceptable for final review/handoff based on this quick pass.

## 12. Commit Recommendation

Commit the SCREENSHOTS.2 report and screenshot artifacts if accepted.

Stage:

- `docs/qa/youngo_demo_screenshots_2_report.md`
- `docs/qa/screenshots/demo_screenshots_2/`

Suggested commit message:

```text
Add final YounGo public demo screenshots QA
```
