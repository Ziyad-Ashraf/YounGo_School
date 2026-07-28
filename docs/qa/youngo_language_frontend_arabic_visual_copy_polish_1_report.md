# LANGUAGE.FRONTEND.ARABIC_VISUAL_COPY.POLISH.1 Report

## A. Current Branch/Status

- Branch: `analysis/cms-audit`
- Start status: not clean locally. `LANGUAGE.FRONTEND.PHRASE.WIRE.REMAINING.1` changes were still uncommitted when this phase began.
- Latest commit at start: `441b4ad Seed missing public frontend phrase keys`
- No deploy, push, Arabic pack import, force overwrite, Paymob/payment change, checkout CTA exposure, Root Admin modification, credential printing, course data update, or subscription plan data update was performed.

## B. Backup Created

- Backup path: `D:\Work\YounGo\backups\youngo_school_before_language_frontend_arabic_visual_copy_polish_1_2026_07_26_025019.sql`
- Size: `612194` bytes
- SHA256: `41D6C5C9710FCA68EE5EDC10B98A6725B9E94A75AD988D1A04AD040166C9AB44`
- Notes: XAMPP `mysqldump` failed locally due socket/host errors, so the backup was created with a temporary PHP `mysqli` SQL dump script and the temporary script was deleted.

## C. Files Inspected

- `docs/qa/youngo_language_frontend_phrase_wire_remaining_1_report.md`
- `docs/qa/youngo_language_frontend_phrase_seed_missing_1_report.md`
- `docs/qa/youngo_language_frontend_phrase_coverage_audit_1_report.md`
- `docs/qa/youngo_language_frontend_phrase_wire_1_report.md`
- `application/views/frontend/youngo/`
- `application/controllers/Home.php`
- `application/controllers/Blog.php`
- `application/helpers/youngo_frontend_language_helper.php`
- `application/helpers/youngo_frontend_content_helper.php`
- `application/models/Youngo_language_phrase_model.php`
- `application/models/Youngo_subscription_model.php`
- `scripts/phase_2/`

## D. Files Changed

- `application/controllers/Blog.php`
- `application/helpers/youngo_frontend_language_helper.php`
- `application/models/Youngo_subscription_model.php`
- `scripts/phase_2/youngo_language_frontend_arabic_visual_copy_polish_1.php`
- `scripts/phase_2/youngo_language_frontend_arabic_visual_copy_polish_1_diagnostic.php`
- `docs/qa/youngo_language_frontend_arabic_visual_copy_polish_1_report.md`

Existing uncommitted previous-phase files remain present and were not reverted.

## E. Screenshot-Driven Issues Fixed

Targeted high-visibility English labels from the screenshots were classified and handled:

- Header/footer/nav labels were phrase-backed but Arabic DB values were English/dirty, then polished.
- Courses listing filter, sort, badge, status, and card labels were phrase-backed but Arabic DB values were English/dirty, then polished.
- Course detail section labels, access labels, instructor/review labels, and support labels were phrase-backed but Arabic DB values were English/dirty, then polished.
- Subscriptions static labels were phrase-backed and polished; public price display now localizes the EGP label for Arabic.
- Blog/contact static labels and page titles were phrase-backed or controller-title-backed and polished.

## F. Phrase Copy Updates

The phase script considered `148` approved high-visibility phrase keys.

First apply:

- Inserted missing rows: `2`
- Updated Arabic values: `106`
- Existing Arabic values preserved: `38`
- Manual overrides preserved: `0`

Second idempotent apply after adding blog title keys:

- Keys considered: `148`
- Inserted rows: `0`
- Updated Arabic values: `2`
- Existing Arabic values preserved: `146`
- Manual overrides preserved: `0`

Only `language.arabic` was updated for existing rows. Existing non-empty English values were not changed. `arabic_translated` was not written.

## G. Hardcoded Labels Wired

- `Blog.php` public page titles now use a private URI-aware frontend phrase wrapper when the YounGo theme is active.
- `Youngo_subscription_model::format_public_plan_price()` now accepts frontend language context and displays Arabic public prices with the localized EGP phrase.
- `youngo_frontend_phrase_value_is_clean()` now rejects raw mojibake marker characters so broken Arabic values cannot pass as clean phrase values.

No form POST, AJAX, payment, checkout, cart, Paymob, admin/backend, logout, profile write, or lesson action URLs were localized.

## H. Dynamic Content Still English and Why

Dynamic content records were intentionally not changed:

- Subscription plan names such as `Monthly`, `Yearly`, and `3 Months` come from `youngo_subscription_plans` data.
- Course names, descriptions, section/lesson titles, instructor names, and blog/category records are content data, not UI phrase labels.
- This phase was limited to public UI phrase copy and safe display formatting. Dynamic content localization needs a separate content/data localization phase.

## I. Arabic/Default QA

HTTP smoke QA through a temporary local PHP server:

- `/`: HTTP `200`, `lang="ar"`, `dir="rtl"`, title `الرئيسية | YounGo`
- `/home/courses`: HTTP `200`, `lang="ar"`, `dir="rtl"`, title `الدورات | YounGo`
- `/home/course/robotics-and-ai-explorers/9`: HTTP `200`, `lang="ar"`, `dir="rtl"`, title `دورة | YounGo`
- `/subscriptions`: HTTP `200`, `lang="ar"`, `dir="rtl"`, title `الاشتراكات | YounGo`
- `/home/blog`: HTTP `200`, `lang="ar"`, `dir="rtl"`, title `المدونة | YounGo`
- `/home/contact`: HTTP `200`, `lang="ar"`, `dir="rtl"`, title `تواصل معنا | YounGo`

Targeted static English-label scan was clean except:

- `Follow` appears only inside a JavaScript function/id context; the visible button text rendered Arabic.
- `Months` appears as a dynamic subscription plan name from DB content and was intentionally not edited.

## J. `/en` QA

HTTP smoke QA:

- `/en`: HTTP `200`, `lang="en"`, `dir="ltr"`, title `Home | YounGo`
- `/en/home/courses`: HTTP `200`, `lang="en"`, `dir="ltr"`, title `Courses | YounGo`
- `/en/subscriptions`: HTTP `200`, `lang="en"`, `dir="ltr"`, title `Subscriptions | YounGo`
- `/en/home/blog`: HTTP `200`, `lang="en"`, `dir="ltr"`, title `Blog | YounGo`
- `/en/home/contact`: HTTP `200`, `lang="en"`, `dir="ltr"`, title `Contact us | YounGo`

English pages remained English.

## K. Payment/CTA Safety

- No payment, Paymob, checkout, cart, invoice, purchase-history, order, enrolment, grant, or access-issuance source files were changed.
- No checkout/payment/Paymob links were detected in HTTP QA output for tested pages.
- The phase script explicitly skips protected payment/checkout/order/enrol/grant/paymob keys.
- Public subscription CTAs remain safe contact/coming-soon display actions only.

## L. Diagnostic Result

Passed:

```text
php scripts/phase_2/youngo_language_frontend_arabic_visual_copy_polish_1_diagnostic.php
php scripts/phase_2/youngo_language_frontend_phrase_wire_remaining_1_diagnostic.php
php scripts/phase_2/youngo_language_edit_phrase_pagination_ui_1_diagnostic.php
```

Diagnostic highlights:

- `148` targeted keys checked.
- Missing rows: `0`
- Blank English values: `0`
- Blank Arabic values: `0`
- Arabic values without Arabic script: `0`
- Dirty Arabic values: `0`
- `arabic_translated` metadata rows: `0`
- Payment/checkout files changed for this phase: `0`

## M. DB Impact

Persistent DB impact:

- Targeted updates to existing `language.arabic` phrase values.
- Two high-visibility phrase rows were inserted before the final idempotent rerun.

No other DB impact:

- No course records changed.
- No subscription plan records changed.
- No blog/contact content records changed.
- No payment, Paymob, checkout, order, enrolment, grant, access, or Root Admin rows changed.
- No `arabic_translated` rows or metadata were created.

## N. Remaining Risks/Blockers

- Dynamic subscription plan names remain English until a plan/content localization phase is approved.
- Dynamic course/category/blog/lesson/instructor content may still be English where Arabic content records are missing.
- Some lower-priority public labels outside the screenshot-driven target list may still need later copy QA.
- The local worktree includes uncommitted previous-phase files; this phase was layered on top without reverting them.

## O. Recommended Next Phase

Recommended next phase:

- `LANGUAGE.FRONTEND.DYNAMIC.CONTENT.AR_COPY.PLAN.1`

Scope:

- Plan localization for dynamic subscription plan names/descriptions.
- Plan content translation coverage for course/category/blog/section/lesson public data.
- Preserve canonical IDs and existing bilingual translation-table strategy.

## P. Git Status

Final `git status --short` includes this phase plus still-uncommitted previous-phase files:

```text
 M application/controllers/Blog.php
 M application/controllers/Home.php
 M application/helpers/youngo_frontend_language_helper.php
 M application/models/Youngo_subscription_model.php
 M application/views/frontend/youngo/404.php
 M application/views/frontend/youngo/account_disable.php
 M application/views/frontend/youngo/change_password_from_forgot_password.php
 M application/views/frontend/youngo/course_page_preview_modal.php
 M application/views/frontend/youngo/forgot_password.php
 M application/views/frontend/youngo/index.php
 M application/views/frontend/youngo/my_access.php
 M application/views/frontend/youngo/my_courses.php
 M application/views/frontend/youngo/new_login_confirmation.php
 M application/views/frontend/youngo/reload_my_courses.php
 M application/views/frontend/youngo/update_user_photo.php
 M application/views/frontend/youngo/user_credentials.php
 M application/views/frontend/youngo/user_profile.php
 M application/views/frontend/youngo/verification_code.php
?? docs/qa/youngo_language_frontend_arabic_visual_copy_polish_1_report.md
?? docs/qa/youngo_language_frontend_phrase_wire_remaining_1_report.md
?? scripts/phase_2/youngo_language_frontend_arabic_visual_copy_polish_1.php
?? scripts/phase_2/youngo_language_frontend_arabic_visual_copy_polish_1_diagnostic.php
?? scripts/phase_2/youngo_language_frontend_phrase_wire_remaining_1_diagnostic.php
```

No deploy or push was performed.
