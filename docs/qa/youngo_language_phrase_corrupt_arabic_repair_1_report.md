# LANGUAGE.PHRASE.CORRUPT.ARABIC.REPAIR.1 Report

## A. Current Branch/Status

- Branch: `analysis/cms-audit`
- Starting worktree: clean.
- Latest commit at start: `0effee0 QA Arabic YounGo course detail access states`
- Deploy: not performed.
- Push: not performed.

## B. Backup Created

- Backup path: `D:\Work\YounGo\backups\youngo_school_before_language_phrase_corrupt_arabic_repair_1_2026_07_26_134810.sql`
- Size: `728582` bytes
- SHA256: `50D1B97275706FFC3D4B9E740DB59DAED454B2037B8AA6C331363B65E3D3A3C2`

## C. Files Inspected

- `YOUNGO_PROJECT_CONTEXT.md`
- `docs/design/youngo_style_direction.md`
- `docs/planning/README.md`
- `docs/planning/youngo_master_plan_v2.md`
- `docs/planning/youngo_phase_2_hybrid_access_subscriptions_roles_architecture.md`
- `docs/planning/phase_2u6_6_controlled_phrase_seed_plan.md`
- `docs/planning/phase_2u6_6_frontend_phrase_inventory.md`
- `docs/agents/implementation_rules.md`
- `docs/reference/README.md`
- `docs/qa/youngo_language_frontend_course_detail_access_copy_qa_1_report.md`
- `docs/qa/youngo_language_frontend_course_detail_access_copy_wire_1_report.md`
- `docs/qa/youngo_language_frontend_auth_copy_qa_1_report.md`
- `docs/qa/youngo_language_frontend_wishlist_copy_wire_1_report.md`
- `docs/qa/youngo_dynamic_content_arabic_public_localization_qa_1_report.md`
- `application/helpers/youngo_frontend_language_helper.php`
- `application/views/frontend/youngo/`
- `scripts/phase_2/`
- `docs/qa/`

## D. Files Changed

- `scripts/phase_2/youngo_language_phrase_corrupt_arabic_repair_1.php`
- `scripts/phase_2/youngo_language_phrase_corrupt_arabic_repair_1_diagnostic.php`
- `docs/qa/youngo_language_phrase_corrupt_arabic_repair_1_report.md`

## E. Repair Allowlist Summary

- Scope: static public frontend phrase values only.
- Allowlisted phrase keys: `74`.
- Language rows seen for those keys, including duplicate legacy phrase rows: `124`.
- Rows repaired on first run: `92`.
- Rows already valid and preserved: `32`.
- Rows repaired on idempotency rerun: `0`.
- Public areas covered: auth, wishlist, course detail/access/status, course listing filters/sort/common labels, course sharing/reviews, header/footer/common labels, blog/contact common labels.
- Explicitly excluded: payment, checkout, Paymob, cart, coupon, dynamic course/category/section/lesson/blog/subscription-plan content, routes, Root Admin, and `arabic_translated`.

## F. Phrase Values Repaired

The old values were confirmed corrupt because `language.arabic` contained literal repeated question marks. Some old rows also included the English fallback after a placeholder prefix such as `????? ??????: Hours`; others were unrecoverable all-question-mark strings. The script repaired only rows whose current Arabic value still matched corruption rules and preserved valid Arabic duplicates.

| Phrase key | Old Arabic value class | New Arabic value | Public usage area |
| --- | --- | --- | --- |
| `1_star_rating` | `????? ??????: 1 star rating` | `تقييم نجمة واحدة` | course detail reviews |
| `2_star_rating` | `????? ??????: 2 star rating` | `تقييم نجمتين` | course detail reviews |
| `3_star_rating` | `????? ??????: 3 star rating` | `تقييم ثلاث نجوم` | course detail reviews |
| `4_star_rating` | `????? ??????: 4 star rating` | `تقييم أربع نجوم` | course detail reviews |
| `5_star_rating` | `????? ??????: 5 star rating` | `تقييم خمس نجوم` | course detail reviews |
| `access_for_this_course_is_managed_by_your_school/admin.` | all-question-mark Arabic placeholder | `يتم إدارة الوصول إلى هذا الكورس من خلال المدرسة أو المسؤول.` | course detail/access |
| `apply_filters` | `????? ??????: Apply filters` | `تطبيق الفلاتر` | course listing |
| `blog` | all-question-mark Arabic placeholder | `المدونة` | header/footer/blog |
| `categories` | `????? ??????: Categories` | `التصنيفات` | listing/blog common |
| `contact_us` | `????? ??????: Contact us` | `تواصل معنا` | contact/common |
| `course_access` | all-question-mark Arabic placeholder | `الوصول إلى الكورس` | course detail/access |
| `course_added_to_wishlist` | all-question-mark Arabic placeholder | `تمت إضافة الكورس إلى المفضلة` | wishlist toast |
| `course_catalog` | all-question-mark Arabic placeholder | `كتالوج الكورسات` | course listing |
| `course_details` | all-question-mark Arabic placeholder | `تفاصيل الكورس` | cards/wishlist |
| `course_filters` | `????? ??????: Course filters` | `فلاتر الكورسات` | course listing |
| `course_guide` | `????? ??????: Course guide` | `مرشد الكورس` | course detail |
| `course_layout` | `????? ??????: Course layout` | `طريقة عرض الكورسات` | course listing |
| `course_removed_from_wishlist` | all-question-mark Arabic placeholder | `تمت إزالة الكورس من المفضلة` | wishlist toast |
| `course_results` | `????? ??????: Course results` | `نتائج الكورسات` | course listing |
| `courses_you_saved` | `????? ??????: Courses you saved` | `الكورسات التي حفظتها` | wishlist |
| `discounted` | `????? ??????: Discounted` | `عليها خصم` | course listing |
| `edit` | `????? ??????: Edit` | `تعديل` | reviews |
| `email_address` | `????? ??????: Email address` | `البريد الإلكتروني` | auth |
| `English` | all-question-mark Arabic placeholder | `الإنجليزية` | taxonomy display |
| `enroll_now` | all-question-mark Arabic placeholder | `سجل الآن` | legacy-compatible public CTA |
| `explore_more_courses` | `????? ??????: Explore more courses` | `استكشف المزيد من الكورسات` | wishlist/account |
| `explore_youngo_courses` | `????? ??????: Explore youngo courses` | `استكشف كورسات YounGo` | course listing |
| `filters` | `????? ??????: Filters` | `الفلاتر` | course listing |
| `find_a_course` | `????? ??????: Find a course` | `ابحث عن كورس` | course listing |
| `find_structured,_friendly_learning_paths_for_curious_kids_and_the_families_supporting_them.` | `????? ??????: ...` | `اعثر على مسارات تعلم منظمة ولطيفة للأطفال الفضوليين وللأسر التي تدعمهم.` | course listing |
| `frequently_asked_questions` | `????? ??????: Frequently asked questions` | `الأسئلة الشائعة` | course detail |
| `highest_price` | `????? ??????: Highest price` | `الأعلى سعرا` | course listing |
| `highest_rating` | `????? ??????: Highest rating` | `الأعلى تقييما` | course listing |
| `hours` | `????? ??????: Hours` | `ساعات` | course duration |
| `language` | `????? ??????: Language` | `اللغة` | listing/detail |
| `log_in_to_view_your_wishlist` | all-question-mark Arabic placeholder | `سجل الدخول لعرض قائمتك المفضلة` | wishlist |
| `login` | all-question-mark Arabic placeholder | `تسجيل الدخول` | auth/header |
| `lowest_price` | `????? ??????: Lowest price` | `الأقل سعرا` | course listing |
| `my_courses` | all-question-mark Arabic placeholder | `كورساتي` | account navigation |
| `my_wishlist` | all-question-mark Arabic placeholder | `قائمتي المفضلة` | wishlist |
| `newly_published` | `????? ??????: Newly published` | `الأحدث نشرا` | course listing |
| `no_curriculum_sections_are_available_yet.` | `????? ??????: ...` | `لا توجد أقسام للمنهج متاحة بعد.` | course detail |
| `no_saved_courses_yet` | `????? ??????: No saved courses yet` | `لا توجد كورسات محفوظة بعد` | wishlist |
| `rating` | `????? ??????: Rating` | `التقييم` | reviews |
| `ratings` | `????? ??????: Ratings` | `التقييمات` | listing filters |
| `remove` | `????? ??????: Remove` | `إزالة` | wishlist/reviews |
| `remove_from_wishlist` | all-question-mark Arabic placeholder | `إزالة من المفضلة` | wishlist |
| `remove_review` | `????? ??????: Remove review` | `إزالة التقييم` | reviews |
| `reset` | `????? ??????: Reset` | `إعادة ضبط` | filters |
| `review` | `????? ??????: Review` | `التقييم` | reviews |
| `save_interesting_courses_while_browsing,_then_compare_options_before_enrolling.` | all-question-mark Arabic placeholder | `احفظ الكورسات التي تهمك أثناء التصفح، ثم قارن الخيارات قبل التسجيل.` | wishlist |
| `saved_courses` | `????? ??????: Saved courses` | `الكورسات المحفوظة` | wishlist |
| `search_by_keyword` | `????? ??????: Search by keyword` | `البحث بكلمة مفتاحية` | listing filters |
| `search_courses` | `????? ??????: Search courses` | `ابحث في الكورسات` | listing filters |
| `share_on_facebook` | `????? ??????: Share on facebook` | `مشاركة على فيسبوك` | course detail |
| `share_on_linkedin` | `????? ??????: Share on linkedin` | `مشاركة على لينكدإن` | course detail |
| `share_on_twitter` | `????? ??????: Share on twitter` | `مشاركة على تويتر` | course detail |
| `share_on_whatsapp` | `????? ??????: Share on whatsapp` | `مشاركة عبر واتساب` | course detail |
| `sign_in_required` | all-question-mark Arabic placeholder | `تسجيل الدخول مطلوب` | wishlist/auth |
| `sign_in_to_track_access` | all-question-mark Arabic placeholder | `سجل الدخول لمتابعة الوصول` | course access |
| `sort_by` | `????? ??????: Sort by` | `ترتيب حسب` | course listing |
| `stars` | `????? ??????: Stars` | `نجوم` | reviews |
| `structured_lessons` | `????? ??????: Structured lessons` | `دروس منظمة` | course detail |
| `submit` | `????? ??????: Submit` | `إرسال` | reviews/auth forms |
| `trusted_guide` | `????? ??????: Trusted guide` | `مرشد موثوق` | course detail |
| `use_a_student_account_to_keep_course_access_and_progress_in_one_place.` | `????? ??????: ...` | `استخدم حساب الطالب لحفظ الوصول والتقدم في مكان واحد.` | course access |
| `video_url_is_not_supported` | `????? ??????: Video url is not supported` | `رابط الفيديو غير مدعوم` | preview modal |
| `view_details` | `????? ??????: View details` | `عرض التفاصيل` | course cards |
| `wishlist` | all-question-mark Arabic placeholder | `المفضلة` | wishlist/header |
| `wishlist_items_are_saved_to_your_learner_account_so_they_stay_available_across_visits.` | all-question-mark Arabic placeholder | `يتم حفظ عناصر المفضلة في حساب المتعلم لتبقى متاحة في الزيارات القادمة.` | wishlist |
| `wishlist_summary` | all-question-mark Arabic placeholder | `ملخص المفضلة` | wishlist |
| `write_a_review` | `????? ??????: Write a review` | `اكتب تقييما` | reviews |
| `write_your_comment` | `????? ??????: Write your comment` | `اكتب تعليقك` | reviews |
| `yes` | `????? ??????: Yes` | `نعم` | course detail facts |

## G. Manual Override Behavior

- `youngo_language_phrase_meta` existed locally.
- No Arabic manual override metadata rows existed for the allowlisted repair keys.
- The repair script still checks manual override status and would only repair a manual override row when the current value matches the documented corruption rules for the allowlisted key.
- No manual override was overwritten in this run.

## H. Arabic/Default QA

HTTP QA passed for:

- `/`
- `/login`
- `/home/my_wishlist`
- `/home/courses`
- `/home/course/robotics-and-ai-explorers/9`
- `/subscriptions`
- `/home/blog`
- `/home/contact`

All returned HTTP `200` with `lang="ar" dir="rtl"`, no literal repeated question-mark placeholder markers, no mojibake `Ø`/`Ù` markers, and no targeted checkout/payment CTA hits.

## I. `/en` QA

HTTP QA passed for:

- `/en/login`
- `/en/home/course/robotics-and-ai-explorers/9`

Both returned HTTP `200` with `lang="en" dir="ltr"`, no repeated question-mark placeholder markers, no mojibake markers, and no checkout/payment CTA hits.

## J. Payment/CTA Safety

- The allowlist blocks keys containing `paymob`, `payment`, `checkout`, `shopping_cart`, `cart`, `coupon`, `buy_now`, `add_to_cart`, or `pay_now`.
- Known excluded payment/checkout/cart phrase hashes remained unchanged in the diagnostic.
- No Paymob, Buy Now, Add to cart, Checkout, Pay now, Pay with Paymob, or Subscribe now terms appeared in the tested pages.
- No checkout/payment/Paymob behavior or route changed.

## K. Diagnostic Result

Passed:

```bash
php -l scripts/phase_2/youngo_language_phrase_corrupt_arabic_repair_1.php
php -l scripts/phase_2/youngo_language_phrase_corrupt_arabic_repair_1_diagnostic.php
php scripts/phase_2/youngo_language_phrase_corrupt_arabic_repair_1.php
php scripts/phase_2/youngo_language_phrase_corrupt_arabic_repair_1.php
php scripts/phase_2/youngo_language_phrase_corrupt_arabic_repair_1_diagnostic.php
php scripts/phase_2/youngo_language_frontend_course_detail_access_copy_qa_1_diagnostic.php
php scripts/phase_2/youngo_dynamic_content_arabic_public_localization_qa_1_diagnostic.php
```

Repair first run:

- `allowlisted_keys`: `74`
- `rows_seen`: `124`
- `rows_repaired`: `92`
- `keys_repaired`: `74`
- `already_clean_rows`: `32`

Repair idempotency rerun:

- `rows_repaired`: `0`
- `keys_repaired`: `0`
- `already_clean_rows`: `124`

Repair diagnostic:

- `rows_checked`: `124`
- `question_mark_placeholders`: `0`
- `mojibake_markers`: `0`
- `english_mismatch`: `0`
- `arabic_translated_metadata_rows`: `0`
- helper Arabic/English sample resolution: passed
- payment/checkout phrase hash guard: passed

## L. DB Impact

- Updated only `language.arabic` for the 92 confirmed corrupt rows under the 74-key public allowlist.
- Did not change `language.english`.
- Did not write or use `arabic_translated`.
- Did not edit dynamic content tables:
  - `youngo_course_translations`
  - `youngo_category_translations`
  - `youngo_section_translations`
  - `youngo_lesson_translations`
  - subscription-plan translation rows
  - blog/category/content rows
- Protected table count snapshot after repair:
  - `payment = 0`
  - `enrol = 1`
  - `youngo_checkout_orders = 0`
  - `youngo_coupon_usages = 0`
  - `youngo_course_access = 0`
  - `youngo_user_subscriptions = 0`
  - `youngo_manual_grants = 0`

## M. Remaining Risks/Blockers

- The broader `language` table still contains many corrupt Arabic values outside this public allowlist, including backend/admin and checkout/cart/payment phrases. Those were intentionally left untouched.
- `/home/courses` still contains `arabic_translated` as the existing non-visible course content-language radio value. It is not used as a route/UI language code in this phase.
- This was HTTP/HTML QA, not pixel-level visual QA.

## N. Recommended Next Phase

- Run a second controlled repair only if another public frontend allowlist is approved, for example learner account pages or public 404/profile-adjacent surfaces.
- Keep checkout/payment/cart/coupon phrase repair deferred until the checkout/order/payment localization phase has an approved safety plan.

## O. Git Status

Pending changes after implementation are expected:

```text
?? docs/qa/youngo_language_phrase_corrupt_arabic_repair_1_report.md
?? scripts/phase_2/youngo_language_phrase_corrupt_arabic_repair_1.php
?? scripts/phase_2/youngo_language_phrase_corrupt_arabic_repair_1_diagnostic.php
```
