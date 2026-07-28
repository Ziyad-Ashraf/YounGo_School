# YounGo Post-XAMPP Restore Demo Baseline Verification

Phase: DEMO.RESTORE.CHECK - Post-XAMPP Restore Demo Baseline Verification
Date: 2026-07-19
Branch: analysis/cms-audit

## 1. Executive Summary

The owner restored the local database after an XAMPP MySQL failure from:

```text
D:\Work\YounGo\backups\youngo_school in case of failure (5).sql
```

The restored database is acceptable as the current practical YounGo demo baseline. Public demo routes load successfully, the YounGo frontend theme and EGP currency settings are present, protected payment/access/checkout tables remain clean, and safe public GET checks did not insert new `language` rows.

A new post-restore database export was created:

```text
D:\Work\YounGo\backups\youngo_school_after_xampp_restore_demo_baseline_2026_07_19.sql
```

## 2. Git State

Starting branch:

```text
analysis/cms-audit
```

Latest commit:

```text
4cb8cb9 Make YounGo demo frontend phrases safe
```

Recent relevant commits present:

```text
4cb8cb9 Make YounGo demo frontend phrases safe
e4241c6 Add YounGo demo readiness audit
fd6c3eb Polish YounGo blog fallback layout
04d7968 Refine YounGo subscription and EGP price display
98f4b8e Add YounGo blog and contact demo pages
e8acdd9 Fix YounGo Arabic demo UI rendering
2ad7746 Rebuild YounGo demo content baseline
84014ff Make YounGo demo CTAs payment-safe
```

The worktree was clean at phase start.

## 3. Restore Source Backup Path And Metadata

Imported restore source reported by owner:

```text
D:\Work\YounGo\backups\youngo_school in case of failure (5).sql
```

Verified metadata:

```text
Exists: yes
Size: 604,640 bytes
Last modified: 2026-07-19 09:15:42
SHA256: 0F77E6BD9878583DA27CA5F375C9C3C1A4C3E584695D90A4621E4AE36BD7F6FA
```

The file begins as a phpMyAdmin SQL dump and references:

```text
Database: `youngo_school`
Server version: 10.4.32-MariaDB
```

The backup was inspected only. It was not imported again.

## 4. New Post-Restore Backup Path And Metadata

New backup created from the current restored DB:

```text
D:\Work\YounGo\backups\youngo_school_after_xampp_restore_demo_baseline_2026_07_19.sql
```

Verified metadata:

```text
Exists: yes
Size: 485,965 bytes
Last modified: 2026-07-19 12:04:05
SHA256: 6DA220B16299D3A27FEE887331AD6D68EC20C15E33988D9028741B947E9559B9
```

The file begins as a MariaDB dump for:

```text
Host: localhost
Database: youngo_school
Server version: 10.4.32-MariaDB
```

This backup is outside the repo and must not be committed.

## 5. DB Config / Core Settings Verification

DB config read from `application/config/database.php`:

```text
host: localhost
user: root
database: youngo_school
password: empty
```

Core settings:

```text
frontend_settings.theme = youngo
settings.system_currency = EGP
settings.currency_position = left
```

The `currency_position` value is still `left`; source-level formatting handles readable EGP display.

## 6. Language Table State

Read-only counts:

```text
language rows: 1447
duplicate phrase names: 65
Arabic empty/null values: 43
Arabic repeated-question-mark rows: 1400
previous audit-inserted wishlist phrase rows: 0
```

The previous audit-inserted phrase is absent in the restored DB:

```text
saved_courses_stay_here_so_you_can_compare_learning_paths_before_access_is_granted_or_checkout_becomes_available.
```

This was observed only. No phrase rows were deleted or repaired during this phase.

Critical phrase status remains mixed:

```text
Clean/local-safe public behavior: handled by YounGo route-aware helper/local map.
Legacy DB Arabic risk: home, courses, blog, contact, login, sign_up, contact_us, email, phone, address still have repeated-question-mark DB values.
Missing DB rows: subscription_access, latest_articles, helpful_notes_for_families, get_in_touch, working_hours, follow_us, read_more.
Clean DB rows: primary_navigation, language_switcher, footer_navigation, showing_results.
```

This is acceptable for the current public demo because the YounGo frontend uses route-aware local fallbacks for demo-critical labels.

## 7. Translation-Table State

```text
youngo_course_translations: total 16, english 8, arabic 8, invalid 0, arabic_translated 0
youngo_category_translations: total 24, english 12, arabic 12, invalid 0, arabic_translated 0
youngo_section_translations: total 34, english 20, arabic 14, invalid 0, arabic_translated 0
youngo_lesson_translations: total 68, english 40, arabic 28, invalid 0, arabic_translated 0
```

Translation table language values are limited to `english` and `arabic`.

## 8. Demo Course / Category / Content State

Counts:

```text
categories: 12
courses: 8 total, 5 active
sections: 20
lessons: 40
```

Courses:

```text
1  Scratch Coding for Young Creators        active   subscription_only          price 900
2  Space Science Adventures                 private  subscription_only          price 0
3  Digital Design for Kids                  active   subscription_only          price 950
4  STEM Challenges Lab                      active   subscription_only          price 1100
5  Storytelling and Reading Confidence      private  subscription_only          price 0
6  Young Entrepreneurs Starter Program      active   purchase_only              price 1500
9  Robotics and AI Explorers                active   subscription_and_purchase  price 1200, discounted 1000
43 YounGo QA Course Render Test             private  subscription_only          price 0
```

Course section/lesson counts:

```text
Course 1: 3 sections, 6 lessons
Course 2: 3 sections, 6 lessons
Course 3: 3 sections, 6 lessons
Course 4: 3 sections, 6 lessons
Course 5: 3 sections, 6 lessons
Course 6: 3 sections, 6 lessons
Course 9: 2 sections, 4 lessons
Course 43: 0 sections, 0 lessons
```

Course 1 remains `subscription_only`. Course 9 is `subscription_and_purchase`.

Raw DB thumbnail filenames for courses 1-6 do not exist under the current thumbnail directory, but the Academy runtime thumbnail helper falls back to configured theme placeholders. HTTP route smoke found zero broken rendered images.

## 9. Blog State

Blog tables exist:

```text
blogs
blog_category
blog_comments
```

Counts:

```text
blogs: 0
blog_category: 0
blog_comments: 0
```

The public Blog remains fallback/editorial-layout driven and demo-safe. Real Blog CMS content is still a content setup gap.

## 10. Contact State

Contact table:

```text
contact rows: 0
```

Relevant frontend settings:

```text
contact_info: {"email":"admin@example.com,\r\nsystem@example.com","phone":"609-502-5899\r\n345-444-2122","address":"455 Wolff Streets Suite 674","office_hours":"10:00 AM - 6:00 PM"}
facebook: https://facebook.com
twitter: https://twitter.com
linkedin: empty
```

The public Contact page remains display-only and demo-safe, but content is placeholder quality.

## 11. Payment / Access / Checkout Table Counts

```text
payment: 0
youngo_checkout_orders: 0
youngo_course_access: 0
youngo_user_subscriptions: 0
youngo_manual_grants: 0
youngo_coupon_usages: 0
youngo_coupon_subscription_plans: 0
youngo_coupon_courses: 0
enrol: 1
watch_histories: 0
watched_duration: 0
ci_sessions before route smoke: 821
```

No payment, checkout, coupon, manual grant, or YounGo entitlement rows are present.

## 12. Public Route Smoke Matrix

All checks were safe GET requests only. No forms were submitted.

```text
/                                                 200 en/ltr images 16 broken 0
/ar                                               200 ar/rtl images 16 broken 0
/home                                             200 en/ltr images 16 broken 0
/home/courses                                     200 en/ltr images 5  broken 0
/ar/courses                                       200 ar/rtl images 5  broken 0
/home/course/scratch-coding-for-young-creators/1  200 en/ltr images 3  broken 0
/ar/course/scratch-coding-for-young-creators/1    200 ar/rtl images 3  broken 0
/blog                                             200 en/ltr images 7  broken 0
/ar/blog                                          200 ar/rtl images 7  broken 0
/contact                                          200 en/ltr images 1  broken 0
/ar/contact                                       200 ar/rtl images 1  broken 0
/login                                            200 en/ltr images 2  broken 0
/ar/login                                         200 ar/rtl images 2  broken 0
/sign_up                                          200 en/ltr images 2  broken 0
/ar/sign-up                                       200 ar/rtl images 2  broken 0
/home/my_wishlist                                 200 en/ltr images 1  broken 0
/ar/wishlist                                      200 ar/rtl images 1  broken 0
```

Every checked route was clean for:

```text
repeated visible ????
skeleton text
/en links
visible Buy Now / Add to cart / Checkout / Paymob / coupon CTA signatures
PHP warnings/errors/fatals
obvious broken rendered images
```

## 13. Language / ci_sessions Before-After Counts

Before smoke:

```text
language: 1447
ci_sessions: 821
```

After smoke:

```text
language: 1447
ci_sessions: 838
```

Result:

```text
language did not increase
ci_sessions increased by 17, normal public GET/session side effect
```

## 14. Source / Commit State Confirmation

Confirmed present:

```text
Add YounGo demo readiness audit
Make YounGo demo frontend phrases safe
Polish YounGo blog fallback layout
Refine YounGo subscription and EGP price display
Add YounGo blog and contact demo pages
Fix YounGo Arabic demo UI rendering
Rebuild YounGo demo content baseline
Make YounGo demo CTAs payment-safe
```

## 15. Diagnostics Results

Run after DB restore and backup creation:

```text
php scripts/phase_2/youngo_post_restore_demo_baseline_diagnostic.php
php scripts/phase_2/youngo_demo_phrase_safe_surface_cleanup_diagnostic.php
php scripts/phase_2/youngo_demo_full_readiness_audit_diagnostic.php
php scripts/phase_2/youngo_blog_contact_implementation_diagnostic.php
php scripts/phase_2/youngo_phase_2s_route_cta_boundary_diagnostic.php
php scripts/phase_2/youngo_phase_2l_subscription_plan_diagnostic.php
```

Known expected warnings:

```text
Arabic legacy phrase table corruption remains.
Some older diagnostics may warn on stale strict-scope/nested phase expectations.
```

## 16. Warnings And Limitations

The legacy `language.arabic` DB column is still heavily corrupted and should not be shown in admin Arabic phrase management during a client demo.

Raw DB thumbnail filenames for several demo courses are stale/missing, but runtime fallback rendering is working and no broken rendered images were found in public smoke.

Blog DB content is empty; public Blog fallback is demo-safe but not CMS-content complete.

Contact settings are placeholder quality; Contact remains display-only.

The imported restore source is ad-hoc named. The new post-restore backup gives the project a clearer practical baseline.

## 17. Current DB Baseline Acceptability

The current restored DB is acceptable as the new practical local YounGo demo baseline for public demo verification and final screenshot preparation, with the documented caveat that Arabic legacy phrase-table repair, Blog content setup, and Contact content polish remain pending.

## 18. Recommended Next Phase

Recommended next phase:

```text
DEMO.CONTENT.1 - Blog and Contact Demo Content Setup
```

Reason:

The public surface is stable after restore, phrase-safe GET behavior is verified, and the biggest remaining demo gap is content quality rather than source behavior.

This next phase is DB/content work. A phpMyAdmin backup or the new post-restore backup should be treated as the restore point before any content writes.
