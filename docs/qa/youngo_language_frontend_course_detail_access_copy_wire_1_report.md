# LANGUAGE.FRONTEND.COURSE_DETAIL.ACCESS_COPY.WIRE.1 Report

## A. Current Branch/Status

- Branch: `analysis/cms-audit`
- Starting worktree: clean.
- Latest commit at start: `3f03f82 Wire Arabic YounGo wishlist public copy`

## B. Files Inspected

- `docs/qa/youngo_dynamic_content_arabic_public_localization_qa_1_report.md`
- `docs/qa/youngo_language_frontend_arabic_visual_copy_polish_1_report.md`
- `docs/qa/youngo_content_translation_reuse_audit_1_report.md`
- `docs/qa/youngo_language_frontend_wishlist_copy_wire_1_report.md`
- `application/controllers/Home.php`
- `application/views/frontend/youngo/course_page.php`
- `application/views/frontend/youngo/course_page_reviews.php`
- `application/views/frontend/youngo/course_page_preview_modal.php`
- `application/helpers/youngo_frontend_language_helper.php`
- `application/helpers/youngo_frontend_content_helper.php`
- `application/models/Youngo_translation_model.php`
- `scripts/phase_2/`

## C. Files Changed

- `application/helpers/youngo_frontend_language_helper.php`
- `application/views/frontend/youngo/course_page.php`
- `application/views/frontend/youngo/course_page_reviews.php`
- `scripts/phase_2/youngo_language_frontend_course_detail_access_copy_wire_1_seed.php`
- `scripts/phase_2/youngo_language_frontend_course_detail_access_copy_wire_1_diagnostic.php`

## D. Course Detail Behavior Summary

- Route/controller path remains `Home::course($slug, $course_id)`.
- Main public view remains `application/views/frontend/youngo/course_page.php`.
- Review partial remains `application/views/frontend/youngo/course_page_reviews.php`.
- Existing dynamic content translation path remains in place:
  - `youngo_frontend_translate_course_row()`
  - `youngo_frontend_translate_section_rows()`
  - `youngo_frontend_translate_lesson_rows()`
- Existing access/status variables and entitlement checks were not changed.

## E. Static Labels Wired

- Replaced remaining course-detail visible static labels with `youngo_course_detail_phrase()`, which delegates to `youngo_frontend_phrase()` using the current request language.
- Normalized legacy capitalized phrase keys to existing lowercase keys for labels such as `course`, `breadcrumb`, `free`, `preview`, `instructor`, `follow`, `unfollow`, `reviews`, `questions`, and `close`.
- Wired access/status headings, notes, facts, overview/curriculum/instructor/review labels, empty review copy, share aria labels, modal close label, and review-form labels.

## F. Taxonomy/Duration Display Handling

- Course level/language display values are mapped through the phrase helper at render time only.
- Stored course taxonomy values were not changed.
- Duration display now localizes raw `Hours`, `0 Hours`, `Minutes`, `HH:MM`, and `HH:MM:SS` style labels for Arabic/default and `/ar`.

## G. Phrase Keys Seeded, If Any

- Added and ran `scripts/phase_2/youngo_language_frontend_course_detail_access_copy_wire_1_seed.php`.
- First run:
  - `keys_considered`: `81`
  - `inserted_rows`: `1`
  - `blank_arabic_values_filled`: `1`
  - `writes_arabic_translated`: `false`
- Idempotency rerun:
  - `inserted_rows`: `0`
  - `blank_english_values_filled`: `0`
  - `blank_arabic_values_filled`: `0`

## H. Arabic/Default QA

- `GET /home/course/robotics-and-ai-explorers/9`: HTTP `200`, `lang="ar" dir="rtl"`.
- Targeted English terms checked and not found after the fix: `COURSE ACCESS`, `Start now`, `Join with your account`, `Lectures`, `Hours`, `Months`, `Expiry period`, `Course confidence`, `Overview`, `Course description`, `Curriculum`, `Lessons inside this course`, `Instructor`, `Meet your guide`, `Follow`, `View profile`, `Reviews`, `Family and learner feedback`, `No reviews yet`, `beginner`.
- `GET /home/courses`: HTTP `200`, `lang="ar" dir="rtl"`, no targeted course-label regressions detected.

## I. /en QA

- `GET /en/home/course/robotics-and-ai-explorers/9`: HTTP `200`, `lang="en" dir="ltr"`.
- English course-detail labels remained English.
- Arabic script scan on `/en` pages found only the expected language-switcher label `عربي`.
- `GET /en/home/courses`: HTTP `200`, `lang="en" dir="ltr"`.

## J. /ar QA If Applicable

- `GET /ar/home/course/robotics-and-ai-explorers/9`: HTTP `200`, `lang="ar" dir="rtl"`.
- Targeted English terms checked and not found after the fix.

## K. Access/Enrolment Preservation

- Existing course access/enrolment/action route strings remain present:
  - `home/course_preview/{course_id}`
  - `home/lesson/{slug}/{course_id}`
  - `home/get_enrolled_to_free_course/{course_id}`
  - `home/play_lesson/{lesson_id}`
  - `home/play_lesson/{lesson_id}/preview`
  - `home/rate_course`
  - `home/remove_rating/{course_id}/{rating_id}`
- No entitlement, grant, enrolment, session, course access, or review permission logic was changed.

## L. Payment/CTA Safety

- HTTP QA found no visible `Paymob`, `Buy Now`, `Add to cart`, `Checkout`, `Pay now`, `Pay with Paymob`, or `Subscribe now` terms on tested pages.
- The new diagnostic found no hardcoded payment/cart route links in changed course-detail files.
- Existing checkout-not-ready/access-managed display behavior was preserved.

## M. Diagnostic Result

- `php -l application/views/frontend/youngo/course_page.php`: PASS.
- `php -l application/views/frontend/youngo/course_page_reviews.php`: PASS.
- `php -l application/helpers/youngo_frontend_language_helper.php`: PASS.
- `php -l scripts/phase_2/youngo_language_frontend_course_detail_access_copy_wire_1_seed.php`: PASS.
- `php -l scripts/phase_2/youngo_language_frontend_course_detail_access_copy_wire_1_diagnostic.php`: PASS.
- `php scripts/phase_2/youngo_language_frontend_course_detail_access_copy_wire_1_diagnostic.php`: PASS.
- `php scripts/phase_2/youngo_dynamic_content_arabic_public_localization_qa_1_diagnostic.php`: PASS.
- `php scripts/phase_2/youngo_content_translation_reuse_audit_1_diagnostic.php`: PASS.

## N. DB Impact, If Any

- DB writes were limited to the explicit course-detail phrase seed script.
- Initial seed inserted `1` missing phrase row and filled `1` blank Arabic phrase value.
- Seed did not write `arabic_translated`.
- No course, section, lesson, enrolment, entitlement, payment, checkout, Paymob, order, coupon, or Root Admin rows were modified.

## O. Remaining Risks/Blockers

- Some existing non-empty Arabic phrase rows can still contain legacy placeholder/corrupt non-Arabic text. The runtime course-detail wrapper and frontend phrase helper use clean local Arabic fallback for display, but a future phrase-data repair phase should address the stored values under an approved overwrite policy.
- Dynamic course/content translation remains dependent on existing translation-table coverage; this phase did not edit content rows.

## P. Recommended Next Phase

- Run `LANGUAGE.FRONTEND.COURSE_DETAIL.ACCESS_COPY.QA.1` with authenticated learner/admin states if access/status copy needs coverage across active manual grant, subscription access, expired/locked access, instructor access, and admin preview states.
- Plan a controlled Arabic phrase-data repair pass for non-empty legacy placeholder values that cannot be corrected by blank-only seed scripts.

## Q. Git Status

- Final `git status --short`:
  - `M application/helpers/youngo_frontend_language_helper.php`
  - `M application/views/frontend/youngo/course_page.php`
  - `M application/views/frontend/youngo/course_page_reviews.php`
  - `?? docs/qa/youngo_language_frontend_course_detail_access_copy_wire_1_report.md`
  - `?? scripts/phase_2/youngo_language_frontend_course_detail_access_copy_wire_1_diagnostic.php`
  - `?? scripts/phase_2/youngo_language_frontend_course_detail_access_copy_wire_1_seed.php`
