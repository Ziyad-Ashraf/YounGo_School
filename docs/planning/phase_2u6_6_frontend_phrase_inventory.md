# Phase 2U.6.6.1 Frontend Phrase Conversion Inventory

Status: audit/inventory only.

This inventory identifies visible YounGo public frontend strings that should eventually be converted to safe phrase lookups. No source views, controllers, helpers, routes, phrase files, or database rows were changed during this phase.

## Summary

Phase 2U.6.6.1 scanned the current YounGo frontend shell, public course/listing/account/auth views, and relevant frontend controller/helper files.

Findings:

- 20 requested files/paths were checked; `application/views/frontend/youngo/lesson.php` is not present.
- 315 unique literal `get_phrase()` / `site_phrase()` calls already exist in the scanned files.
- 24 hardcoded visible frontend strings/fragments were found.
- 15 hardcoded strings have existing normalized phrase keys and can be converted later without phrase seeding.
- 4 hardcoded strings need controlled phrase keys before conversion.
- 3 hardcoded strings/fragments need copy/design or technical-content review before conversion.
- 2 hardcoded language switcher labels are deliberately not phrase candidates.

Phrase availability was checked with direct read-only `SELECT` queries against the `language` table. The audit did not call `get_phrase()` or `site_phrase()` for unknown keys because those helpers can write missing phrase rows.

## Files Scanned

Frontend shell and public views:

- `application/views/frontend/youngo/index.php`
- `application/views/frontend/youngo/header.php`
- `application/views/frontend/youngo/footer.php`
- `application/views/frontend/youngo/course_page.php`
- `application/views/frontend/youngo/courses_page.php`
- `application/views/frontend/youngo/course_listing/course_card.php`
- `application/views/frontend/youngo/course_listing/filter_panel.php`
- `application/views/frontend/youngo/course_listing/sorting_bar.php`
- `application/views/frontend/youngo/my_courses.php`
- `application/views/frontend/youngo/my_access.php`
- `application/views/frontend/youngo/my_wishlist.php`
- `application/views/frontend/youngo/wishlist_items.php`
- `application/views/frontend/youngo/login.php`
- `application/views/frontend/youngo/sign_up.php`
- `application/views/frontend/youngo/includes_top.php`
- `application/views/frontend/youngo/includes_bottom.php`

Controller/helper references:

- `application/controllers/Home.php`
- `application/helpers/common_helper.php`
- `application/helpers/youngo_frontend_language_helper.php`
- `application/helpers/youngo_frontend_content_helper.php`

Missing expected optional file:

- `application/views/frontend/youngo/lesson.php`

## Already Phrase-Based Strings

The scanned YounGo frontend already uses phrase helpers extensively. Examples include:

- Header/footer navigation: `home`, `courses`, `blog`, `contact`, `login`, `sign_up`
- Course listing filters: `Course filters`, `Filters`, `Search by keyword`, `Search courses`, `Categories`, `Price`, `Level`, `Language`, `Ratings`, `Apply filters`
- Course cards/detail: `Free`, `New`, `lessons`, `Start Now`, `Access locked`, `View details`, `Course access`, `Course Description`, `Curriculum`, `Preview`, `Reviews`
- Access and entitlement messages: `Subscription checkout is not available yet`, `Access for this course is managed by your school/admin.`, `Access active`, `Access until`
- My Courses/My Access/Wishlist empty states and summaries
- Login/sign-up labels, help text, and password toggle labels

Important safety note: because `get_phrase()` and `site_phrase()` can insert or update missing phrase rows, later conversion must seed missing keys first or prove the key already exists with read-only SQL.

## Hardcoded Visible Strings Found

| File | String or fragment | Category | Recommended key | Availability |
| --- | --- | --- | --- | --- |
| `index.php` | `YounGo theme skeleton` | C | `youngo_theme_skeleton` | missing |
| `index.php` | `This YounGo page has not been implemented yet. The side-by-side theme skeleton is loaded safely.` | C | `youngo_theme_skeleton_not_implemented` | missing |
| `header.php` | `Primary navigation` | B | `primary_navigation` | missing |
| `header.php` | `Language switcher` | B | `language_switcher` | missing |
| `header.php` | `EN` | E | none | not a phrase |
| `header.php` | Arabic compact switcher label rendered by HTML entities | E | none | not a phrase |
| `footer.php` | `Footer navigation` | B | `footer_navigation` | missing |
| `courses_page.php` | `Course discovery` | A | `course_discovery` | exists, Arabic present |
| `courses_page.php` | `Explore YounGo courses` | A | `explore_youngo_courses` | exists, Arabic present |
| `courses_page.php` | `Find structured, friendly learning paths for curious kids and the families supporting them.` | A | exact normalized phrase key | exists, Arabic present |
| `courses_page.php` | `Course not found` | A | `course_not_found` | exists, Arabic present |
| `courses_page.php` | `Try adjusting your search or clearing a few filters to see more courses.` | A | exact normalized phrase key | exists, Arabic present |
| `courses_page.php` | `Reset filters` | A | `reset_filters` | exists, Arabic present |
| `filter_panel.php` | `Find a course` | A | `find_a_course` | exists, Arabic present |
| `filter_panel.php` | `All categories` | A | `all_categories` | exists, Arabic present |
| `sorting_bar.php` | `Course catalog` | A | `course_catalog` | exists, Arabic present |
| `sorting_bar.php` | `Showing {count} of {total} results` | B | `showing_results` or parameterized phrase | missing |
| `sorting_bar.php` | `Clear all filters` | A | `clear_all_filters` | exists, Arabic present |
| `sorting_bar.php` | `Newly published` | A | `newly_published` | exists, Arabic present |
| `sorting_bar.php` | `Highest rating` | A | `highest_rating` | exists, Arabic present |
| `sorting_bar.php` | `Lowest price` | A | `lowest_price` | exists, Arabic present |
| `sorting_bar.php` | `Highest price` | A | `highest_price` | exists, Arabic present |
| `sorting_bar.php` | `Discounted` | A | `discounted` | exists, Arabic present |
| `sign_up.php` | `(doc, docs, pdf, txt, png, jpg, jpeg)` | C | `allowed_file_types` or revised copy | missing |

## Category Summary

### A. Safe Convert Existing Key

These hardcoded visible strings already have normalized phrase rows with Arabic values and are the lowest-risk later conversion set:

- `Course discovery`
- `Explore YounGo courses`
- `Find structured, friendly learning paths for curious kids and the families supporting them.`
- `Course not found`
- `Try adjusting your search or clearing a few filters to see more courses.`
- `Reset filters`
- `Find a course`
- `All categories`
- `Course catalog`
- `Clear all filters`
- `Newly published`
- `Highest rating`
- `Lowest price`
- `Highest price`
- `Discounted`

### B. Safe Convert After Seed

These are visible shell or listing strings that are safe to convert after controlled phrase seeding:

- `Primary navigation` -> `primary_navigation`
- `Language switcher` -> `language_switcher`
- `Footer navigation` -> `footer_navigation`
- `Showing {count} of {total} results` -> recommend a parameterized phrase key such as `showing_results`

### C. Needs Design/Copy Decision

These should not be blindly converted without confirming intended public copy:

- `YounGo theme skeleton`
- `This YounGo page has not been implemented yet. The side-by-side theme skeleton is loaded safely.`
- `(doc, docs, pdf, txt, png, jpg, jpeg)` in the instructor document upload hint

The skeleton copy is a fallback implementation placeholder and may be better replaced by final user-facing 404/coming-soon copy in a later phase. The file-extension hint is technical auth/signup copy and may need a clearer phrase such as `Allowed file types: doc, docx, pdf, txt, png, jpg, jpeg`.

### D. Defer

Defer phrase conversion or phrase polish for these areas:

- Checkout/payment/cart/coupon write surfaces and response messages.
- Lesson/player/PDF surfaces, because Arabic aliases remain deferred.
- My Courses/Wishlist AJAX reload/search partial language propagation.
- Auth form behavior and submission messages beyond simple existing phrase usage.
- Global helper pluralization/date formatting in `common_helper.php`; this is cross-system behavior, not YounGo-only frontend copy.
- Technical diagnostic/admin/backend strings.

### E. Not A Phrase

Do not convert:

- Language switcher compact labels `EN` and the Arabic label.
- Dynamic course/category/section/lesson content supplied by translation shaping.
- Instructor names, learner names, emails, prices, currency, durations, dates, slugs, route paths, URLs, CSS classes, HTML IDs, data attributes, JS variable names, and icon names.
- Course `language_made_in` values; those are course-content metadata and not frontend UI language.

## Existing Phrase Key Availability

The following recommended conversion keys already exist with Arabic values:

- `course_discovery`
- `explore_youngo_courses`
- `find_structured,_friendly_learning_paths_for_curious_kids_and_the_families_supporting_them.`
- `course_not_found`
- `try_adjusting_your_search_or_clearing_a_few_filters_to_see_more_courses.`
- `reset_filters`
- `find_a_course`
- `all_categories`
- `course_catalog`
- `clear_all_filters`
- `newly_published`
- `highest_rating`
- `lowest_price`
- `highest_price`
- `discounted`

The following related phrase keys are already used by the frontend and exist with Arabic values:

- `home`
- `courses`
- `blog`
- `contact`
- `course_filters`
- `filters`
- `reset`
- `search_by_keyword`
- `search_courses`
- `search`
- `categories`
- `price`
- `all`
- `free`
- `paid`
- `level`
- `beginner`
- `intermediate`
- `advanced`
- `language`
- `ratings`
- `apply_filters`
- `course_layout`
- `grid_view`
- `list_view`
- `sort_by`
- `course_results`
- `course_available`
- `courses_available`
- `course_pagination`
- `breadcrumb`
- `shopping_cart`
- `my_wishlist`
- `login`
- `sign_up`

## Missing Phrase Keys Requiring Controlled Seed

Seed these only in a controlled phrase seed phase before converting code to call phrase helpers:

- `primary_navigation`
- `language_switcher`
- `footer_navigation`
- `showing_results`

Potential copy-review keys:

- `youngo_theme_skeleton`
- `youngo_theme_skeleton_not_implemented`
- `allowed_file_types`

## Recommended Low-Risk Conversion Set

Phase 2U.6.6.2 can safely convert the 15 Category A strings in:

- `application/views/frontend/youngo/courses_page.php`
- `application/views/frontend/youngo/course_listing/filter_panel.php`
- `application/views/frontend/youngo/course_listing/sorting_bar.php`

A small controlled seed can precede or accompany the Category B conversion if the owner approves seeding:

- `primary_navigation`
- `language_switcher`
- `footer_navigation`
- `showing_results`

## Arabic / Language-Code Safety

The scan found `arabic_translated` only in `application/helpers/youngo_frontend_language_helper.php`, where it is accepted as an input alias and normalized to `arabic`.

No scanned YounGo frontend file uses `arabic_translated` as a route language, UI language, or translation table language.

## Out Of Scope

This inventory intentionally excludes:

- Admin/backend phrase conversion.
- Checkout/payment/cart/coupon localization.
- `/en` route creation.
- Route changes.
- RTL shell changes.
- Language switcher changes.
- Frontend translated content shaping changes.
- Translation table content edits.
- Phrase table or JSON writes.

## Proposed Implementation Split

Recommended next steps:

1. Phase 2U.6.6.2 - Convert Category A existing-key strings in the course listing views only.
2. Phase 2U.6.6.3 - Add controlled phrase seed for Category B shell/listing keys, then convert those strings.
3. Phase 2U.6.6.4 - Copy/design review for fallback skeleton and technical signup file-type hint.
4. Phase 2U.6.7 - Controlled frontend localization QA, including Arabic phrase rendering, RTL shell smoke, and AJAX partial limitations.
5. Phase 2U.7 - broader bilingual QA and phrase polish QA.
