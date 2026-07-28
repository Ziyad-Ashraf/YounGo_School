# LANGUAGE.FRONTEND.PHRASE.COVERAGE.AUDIT.1 Report

## A. Current Branch/Status

- Branch: `analysis/cms-audit`
- Start status: clean.
- Latest commit at start: `215cf0e Wire public frontend labels to phrase system`
- No deploy, push, DB write, phrase seed, language import, payment/Paymob change, checkout CTA exposure, Root Admin change, or route change was performed.

## B. Files Inspected

- `docs/qa/youngo_language_frontend_phrase_wire_1_report.md`
- `docs/qa/youngo_language_arabic_pack_import_qa_1_report.md`
- `docs/qa/youngo_localization_ar_default_links_1_report.md`
- `docs/qa/youngo_subscriptions_page_dynamic_ui_1_report.md`
- `application/views/frontend/youngo/`
- `application/views/frontend/youngo/layout/`
- `application/views/frontend/youngo/course_listing/`
- `application/views/frontend/youngo/home_sections/`
- `application/helpers/youngo_frontend_language_helper.php`
- `application/helpers/youngo_frontend_content_helper.php`
- `application/controllers/Home.php`
- `application/models/Youngo_language_phrase_model.php`
- `application/models/Youngo_subscription_model.php`

## C. Phrase Coverage Inventory Summary

The read-only diagnostic scanned `58` public frontend/helper/controller/model files.

Inventory counts:

- `youngo_frontend_phrase()` / `youngo_frontend_phrase_e()` calls: `355`
- Phrase-backed calls with missing or blank DB values: `51`
- Phrase-backed calls using fallback arguments: `19`
- Legacy public `get_phrase()` / `site_phrase()` calls: `450`
- Legacy missing/blank calls outside deferred payment/cart surfaces: `117`
- Hardcoded visible literal candidates: `8`
- Hardcoded missing/blank non-deferred candidates: `5`
- Deferred operational payment/cart items excluded from seed scope: `132`
- Proposed missing frontend seed keys after dedupe/filtering: `83`

Proposed missing keys by area:

- Auth: `20`
- Learner pages: `20`
- Subscriptions: `12`
- Blog: `8`
- Contact: `6`
- Profile/account: `6`
- Course detail: `5`
- Home page: `3`
- Shared/frontend placeholders: `2`
- Courses listing/cards: `1`

## D. Hardcoded Labels Found

Hardcoded/fallback coverage is no longer limited to the subscriptions page.

Primary findings:

- Header/footer/subscriptions are now helper-wired, but several subscription labels still rely on helper fallback because DB phrase keys are missing.
- Learner pages still use many public `get_phrase()` calls, especially `my_courses.php`, `my_access.php`, wishlist fragments, and profile/menu surfaces.
- Auth views are mostly helper-backed, but verification/reset/signup labels still contain missing phrase keys or fallback-only behavior.
- Blog/contact views are helper-backed in many places but need DB phrase rows for new YounGo copy.
- Course detail/listing pages still contain missing phrase keys for access status, details, certificate, and video labels.
- Home page has a few short decorative labels (`Play`, `Create`, `Grow`) that should become phrase-backed if retained as visible copy.

Hardcoded/technical text that should not be converted in this phase:

- Password mask placeholders such as `........`
- File-extension hint text such as `(doc, docs, pdf, txt, png, jpg, jpeg)`
- Static language switch abbreviations such as `EN` and visible Arabic language name
- Logo/system-name alt labels generated from settings
- Copyright year/system-name display
- Social-share platform URL/action labels where the route/action is external and technical

## E. Missing Phrase Keys Found

The diagnostic generated `83` proposed frontend seed candidates. The next phase should use these as seed input, then copy-polish Arabic values in Edit Phrase.

| Batch | Phrase key | English value | Proposed Arabic value | Priority |
| --- | --- | --- | --- | --- |
| Auth | `change_password` | Change password | تغيير كلمة المرور | High |
| Auth | `change_your_password_to_secure_your_account` | Change your password to secure your account | غيّر كلمة المرور لحماية حسابك | High |
| Auth | `confirm_your_new_password` | Confirm your new password | أكّد كلمة المرور الجديدة | High |
| Auth | `enter_a_new_password` | Enter a new password | أدخل كلمة مرور جديدة | High |
| Auth | `enter_the_code_from_the_email_sent_to` | Enter the code from the email sent to | أدخل الرمز من البريد المرسل إلى | High |
| Auth | `enter_the_verification_code` | Enter the verification code | أدخل رمز التحقق | High |
| Auth | `enter_your_verification_code` | Enter your verification code | أدخل رمز التحقق الخاص بك | High |
| Auth | `enter_your_verification_code_here` | Enter your verification code here | أدخل رمز التحقق هنا | High |
| Auth | `let_us_know_that_this_email_address_belongs_to_you` | Let us know that this email address belongs to you | ساعدنا على التأكد أن هذا البريد الإلكتروني يخصك | High |
| Auth | `login_confirmation` | Login confirmation | تأكيد تسجيل الدخول | High |
| Auth | `new_device_verification_code` | New device verification code | رمز تحقق لجهاز جديد | High |
| Auth | `or_continue_with` | Or continue with | أو تابع باستخدام | High |
| Auth | `or_sign_up_with` | Or sign up with | أو سجّل باستخدام | High |
| Auth | `please_try_again` | Please try again | يرجى المحاولة مرة أخرى | High |
| Auth | `resend_mail` | Resend mail | إعادة إرسال البريد | High |
| Auth | `resend_verification_code` | Resend verification code | إعادة إرسال رمز التحقق | High |
| Auth | `retype_your_new_password` | Retype your new password | أعد كتابة كلمة المرور الجديدة | High |
| Auth | `sending` | Sending | جارٍ الإرسال | High |
| Auth | `sent` | Sent | تم الإرسال | High |
| Auth | `verification_code` | Verification code | رمز التحقق | High |
| Learner | `access` | Access | الوصول | High |
| Learner | `access_active` | Access active | الوصول نشط | High |
| Learner | `access_until` | Access until | الوصول متاح حتى | High |
| Learner | `checkout_and_renewal_actions_are_not_available_yet.` | Checkout and renewal actions are not available yet. | إجراءات الدفع والتجديد غير متاحة بعد. | High |
| Learner | `days` | Days | أيام | High |
| Learner | `keep_favorite_youngo_courses_in_one_place_then_return_when_your_child_is_ready_to_start.` | Keep favorite YounGo courses in one place then return when your child is ready to start. | احتفظ بدورات YounGo المفضلة في مكان واحد ثم عُد عندما يكون طفلك مستعدًا للبدء. | High |
| Learner | `no_active_access_matched_this_filter` | No active access matched this filter | لا يوجد وصول نشط يطابق هذا الفلتر | High |
| Learner | `no_active_subscription` | No active subscription | لا يوجد اشتراك نشط | High |
| Learner | `no_matching_courses` | No matching courses | لا توجد دورات مطابقة | High |
| Learner | `quizzes` | Quizzes | الاختبارات | High |
| Learner | `saved_courses_stay_here_so_you_can_compare_learning_paths_before_access_is_granted.` | Saved courses stay here so you can compare learning paths before access is granted. | تبقى الدورات المحفوظة هنا لتتمكن من مقارنة مسارات التعلم قبل منح الوصول. | High |
| Learner | `subscription_access` | Subscription access | وصول الاشتراك | High |
| Learner | `subscription_active` | Subscription active | الاشتراك نشط | High |
| Learner | `subscription_inactive` | Subscription inactive | الاشتراك غير نشط | High |
| Learner | `subscription_plan` | Subscription plan | خطة الاشتراك | High |
| Learner | `try_another_search_or_browse_all_youngo_courses.` | Try another search or browse all YounGo courses. | جرّب بحثًا آخر أو تصفح كل دورات YounGo. | High |
| Learner | `until` | Until | حتى | High |
| Learner | `your_active_learning_access` | Your active learning access | وصولك التعليمي النشط | High |
| Learner | `your_latest_subscription_is_not_active._checkout_and_renewal_actions_are_not_available_yet.` | Your latest subscription is not active. Checkout and renewal actions are not available yet. | اشتراكك الأخير غير نشط. إجراءات الدفع والتجديد غير متاحة بعد. | High |
| Learner | `your_subscription_access_is_active` | Your subscription access is active | وصول الاشتراك الخاص بك نشط | High |
| Subscriptions | `choose_a_learning_plan_for_consistent_youngo_access._online_access_requests_are_not_available_yet.` | Choose a learning plan for consistent YounGo access. Online access requests are not available yet. | اختر خطة تعلم لوصول مستمر إلى YounGo. طلبات الوصول عبر الإنترنت غير متاحة بعد. | High |
| Subscriptions | `coming_soon` | Coming soon | قريبًا | High |
| Subscriptions | `contact_us_to_choose_the_right_starting_point_before_subscriptions_open_online.` | Contact us to choose the right starting point before subscriptions open online. | تواصل معنا لاختيار نقطة البداية المناسبة قبل فتح الاشتراكات عبر الإنترنت. | High |
| Subscriptions | `family_access_plans` | Family access plans | خطط وصول العائلة | High |
| Subscriptions | `featured_plan` | Featured plan | خطة مميزة | High |
| Subscriptions | `no_subscription_plans_available_yet` | No subscription plans available yet | لا توجد خطط اشتراك متاحة بعد | High |
| Subscriptions | `plan_available` | Plan available | خطة متاحة | High |
| Subscriptions | `plan_options` | Plan options | خيارات الخطط | High |
| Subscriptions | `plans_available` | Plans available | خطط متاحة | High |
| Subscriptions | `subscription_plans` | Subscription plans | خطط الاشتراك | High |
| Subscriptions | `subscription_plans_will_appear_here_after_they_are_approved_and_made_purchasable.` | Subscription plans will appear here after they are approved and made purchasable. | ستظهر خطط الاشتراك هنا بعد اعتمادها وإتاحتها للشراء. | High |
| Subscriptions | `talk_to_us_about_subscriptions` | Talk to us about subscriptions | تواصل معنا بخصوص الاشتراكات | High |
| Blog | `all_articles` | All articles | كل المقالات | Medium |
| Blog | `back_to_blog` | Back to blog | العودة إلى المدونة | Medium |
| Blog | `helpful_notes_for_families` | Helpful notes for families | ملاحظات مفيدة للعائلات | Medium |
| Blog | `latest_articles` | Latest articles | أحدث المقالات | Medium |
| Blog | `no_blog_posts_yet` | No blog posts yet | لا توجد مقالات بعد | Medium |
| Blog | `practical_articles_and_learning_tips_for_parents_will_appear_here_soon.` | Practical articles and learning tips for parents will appear here soon. | ستظهر هنا قريبًا مقالات عملية ونصائح تعلم للأهل. | Medium |
| Blog | `read_more` | Read more | اقرأ المزيد | Medium |
| Blog | `we_will_share_family_learning_notes_here_as_the_youngo_library_grows.` | We will share family learning notes here as the YounGo library grows. | سنشارك هنا ملاحظات تعلم للعائلات مع نمو مكتبة YounGo. | Medium |
| Contact | `contact_us_by_email` | Contact us by email | تواصل معنا عبر البريد الإلكتروني | Medium |
| Contact | `follow_us` | Follow us | تابعنا | Medium |
| Contact | `get_in_touch` | Get in touch | تواصل معنا | Medium |
| Contact | `have_a_question_about_youngo_programs?_we_would_be_happy_to_hear_from_you.` | Have a question about YounGo programs? We would be happy to hear from you. | هل لديك سؤال عن برامج YounGo؟ يسعدنا التواصل معك. | Medium |
| Contact | `ready_to_talk_about_the_right_learning_path?` | Ready to talk about the right learning path? | هل أنت مستعد للحديث عن مسار التعلم المناسب؟ | Medium |
| Contact | `send_us_an_email_and_the_youngo_team_will_help_you_choose_a_good_starting_point.` | Send us an email and the YounGo team will help you choose a good starting point. | أرسل لنا بريدًا إلكترونيًا وسيساعدك فريق YounGo في اختيار نقطة بداية مناسبة. | Medium |
| Course detail | `additional_information` | Additional information | معلومات إضافية | Medium |
| Course detail | `certificate` | Certificate | شهادة | Medium |
| Course detail | `more_details` | More details | المزيد من التفاصيل | Medium |
| Course detail | `starts` | Starts | يبدأ | Medium |
| Course detail | `watch_video` | Watch video | شاهد الفيديو | Medium |
| Courses listing/cards | `access_locked` | Access locked | الوصول مقفل | Medium |
| Home page | `create` | Create | ابتكر | Medium |
| Home page | `grow` | Grow | انمُ | Medium |
| Home page | `play` | Play | العب | Medium |
| Profile/account | `confirm` | Confirm | تأكيد | Medium |
| Profile/account | `confirm_your_password` | Confirm your password | أكّد كلمة المرور | Medium |
| Profile/account | `if_you_want_to_reactivate_the_account_after_it_has_been_disabled,_you_must_first_authenticate_your_account_from_signup_page.` | If you want to reactivate the account after it has been disabled, you must first authenticate your account from signup page. | إذا أردت إعادة تفعيل الحساب بعد تعطيله، يجب أولًا توثيق حسابك من صفحة التسجيل. | Medium |
| Profile/account | `short_title_about_yourself` | Short title about yourself | عنوان قصير عنك | Medium |
| Profile/account | `youngo_learner` | YounGo learner | متعلم في YounGo | Medium |
| Profile/account | `your_skills` | Your skills | مهاراتك | Medium |
| Shared/frontend | `this_page_is_not_part_of_the_current_public_demo_flow.` | This page is not part of the current public demo flow. | هذه الصفحة ليست جزءًا من مسار العرض العام الحالي. | Medium |
| Shared/frontend | `youngo_demo_page` | YounGo demo page | صفحة عرض YounGo | Medium |

## F. Proposed English/Arabic Values

The table above is the proposed seed list for this audit. It intentionally keeps the exact current phrase-key shape, including punctuation, because existing phrase lookup normalizes and stores those keys in the current `language` table style. A later cleanup can rationalize punctuation-heavy phrase keys only if all calling views are updated in the same phase.

Arabic copy is suitable as first-pass product UI copy, but the next seed phase should still preserve Edit Phrase as the final copy-polish layer.

## G. Priority/Batch Recommendation

Recommended seed batches:

- Batch 1: core/common and learner safety labels: learner page access, subscription status, disabled checkout/renewal messaging, and shared placeholders.
- Batch 2: subscriptions page labels: plan title/copy, empty state, featured badge, safe contact CTA, day/days/EGP copy where missing.
- Batch 3: auth pages: login confirmation, verification, password reset, resend states, social-login separators.
- Batch 4: home/course listing/course detail labels: Play/Create/Grow, access locked, certificate/details/video labels.
- Batch 5: blog/contact/profile labels and longer empty-state/support copy.

## H. Safety/Preservation Rules

Seed behavior should be conservative:

- Insert missing phrase rows only.
- Update blank `english` or `arabic` values only.
- Preserve existing non-empty `english` and `arabic` values.
- Preserve `manual_override` metadata.
- Never write `arabic_translated`.
- Never run force overwrite.
- Do not seed payment/checkout/Paymob/order/enrol/grant CTAs as active purchase language.
- Keep route/action/form/AJAX/admin/backend/payment URLs unchanged.

## I. What Should Not Be Changed Yet

Do not convert or seed in the next phrase-coverage seed phase:

- Legacy cart/checkout/payment/invoice/purchase-history pages, because public checkout is intentionally disabled.
- Paymob callback/return/webhook labels or routes.
- Admin/backend labels.
- Form action URLs, AJAX endpoints, logout routes, profile write routes, wishlist mutation routes, lesson progress routes, or payment routes.
- `arabic_translated`, which remains deprecated for UI language use.

## J. Diagnostic Result

Passed:

```text
php -l scripts/phase_2/youngo_language_frontend_phrase_coverage_audit_1_diagnostic.php
php scripts/phase_2/youngo_language_frontend_phrase_coverage_audit_1_diagnostic.php
```

Diagnostic checks confirmed:

- `youngo_frontend_phrase()` exists.
- `youngo_frontend_phrase_e()` exists.
- `arabic_translated` is rejected for phrase language selection.
- Fallback arguments and hardcoded literal candidates were detected.
- Missing seed list generation works.
- Diagnostic is read-only.
- No payment/Paymob files were changed.

## K. Remaining Risks/Blockers

- Public learner/auth/blog/contact/profile views still have substantial legacy `get_phrase()` usage, which may depend on backend/session language instead of URI language.
- The current key style includes long sentence keys and punctuation-heavy keys. That matches existing application convention but makes future copy maintenance noisier.
- Some Arabic values need product-owner or native-copy review before public launch.
- The audit did not browser-QA every page because this phase was source/diagnostic coverage planning only.

## L. Recommended Next Phase

Recommended next phase:

- `LANGUAGE.FRONTEND.PHRASE.SEED.MISSING.1`

Scope:

- Add a read-only preview first.
- Seed the `83` missing frontend phrase candidates with insert-missing / update-blank-only behavior.
- Preserve manual overrides through `youngo_language_phrase_meta`.
- Re-run public frontend phrase diagnostics and smoke `/`, `/en`, `/ar`, `/subscriptions`, `/en/subscriptions`, auth, learner pages, blog, and contact.

## M. Git Status

Final expected `git status --short` for this phase:

```text
?? docs/qa/youngo_language_frontend_phrase_coverage_audit_1_report.md
?? scripts/phase_2/youngo_language_frontend_phrase_coverage_audit_1_diagnostic.php
```
