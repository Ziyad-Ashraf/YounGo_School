# YounGo DEMO.3 Content Rebuild Report

Date: 2026-07-19

## Backup

Full database backup created before dashboard content writes:

```text
D:\Work\YounGo\backups\youngo_school_before_demo_3_content_rebuild_2026_07_19_025545.sql
```

Backup size recorded: 427,023 bytes.

## Pre-change Counts

| Table | Count |
| --- | ---: |
| category | 12 |
| course | 8 |
| section | 18 |
| lesson | 36 |
| youngo_category_translations | 12 |
| youngo_course_translations | 9 |
| youngo_section_translations | 18 |
| youngo_lesson_translations | 36 |
| enrol | 1 |
| payment | 0 |
| youngo_course_access | 0 |
| youngo_user_subscriptions | 0 |
| youngo_manual_grants | 0 |
| youngo_checkout_orders | 0 |
| youngo_coupon_usages | 0 |
| youngo_coupon_subscription_plans | 0 |
| youngo_coupon_courses | 0 |

## Dashboard Actions Performed

All content writes were submitted through authenticated admin dashboard form endpoints on `http://school.local`.

No manual SQL writes were used for content creation, editing, hiding, or translation updates.

Actions:

- Edited 12 existing categories/subcategories with English and Arabic names/slugs.
- Edited active demo courses 1, 3, 4, 6, and 9 with polished English and Arabic course content.
- Set old/incomplete courses 2, 5, and 43 to `private` through the course edit form.
- Edited existing sections and lessons for courses 1, 3, 4, and 6.
- Added 2 sections and 4 text lessons to course 9 through dashboard forms.
- Uploaded no new images or media files.

## Categories

Final category/subcategory content:

| ID | English | Arabic | Parent |
| ---: | --- | --- | ---: |
| 1 | Coding & Programming | البرمجة وتطوير المهارات الرقمية | 0 |
| 2 | Robotics & AI | الروبوتات والذكاء الاصطناعي | 0 |
| 3 | Digital Creativity | الإبداع الرقمي | 0 |
| 4 | Reading & Storytelling | القراءة ورواية القصص | 0 |
| 5 | STEM Foundations | أساسيات العلوم والتكنولوجيا | 0 |
| 6 | Entrepreneurship & Future Skills | ريادة الأعمال ومهارات المستقبل | 0 |
| 7 | Creative Coding | البرمجة الإبداعية | 1 |
| 8 | STEM Challenges | تحديات العلوم والتكنولوجيا | 5 |
| 9 | Robotics and AI Projects | مشروعات الروبوتات والذكاء الاصطناعي | 2 |
| 10 | Digital Design | التصميم الرقمي | 3 |
| 11 | Reading Foundations | أساسيات القراءة | 4 |
| 12 | Future Skills | مهارات المستقبل | 6 |

The dashboard category form does not expose a category active/private toggle, so old category rows were cleaned rather than hidden.

## Courses

Active client-demo courses:

| ID | English | Arabic | Category | Status | Access |
| ---: | --- | --- | --- | --- | --- |
| 1 | Scratch Coding for Young Creators | برمجة سكراتش للمبدعين الصغار | Creative Coding | active | subscription_only |
| 3 | Digital Design for Kids | التصميم الرقمي للأطفال | Digital Design | active | subscription_only |
| 4 | STEM Challenges Lab | مختبر تحديات العلوم والتكنولوجيا | STEM Challenges | active | subscription_only |
| 6 | Young Entrepreneurs Starter Program | برنامج رواد الأعمال الصغار | Future Skills | active | subscription_only |
| 9 | Robotics and AI Explorers | مستكشفو الروبوتات والذكاء الاصطناعي | Robotics and AI Projects | active | subscription_only |

Hidden/deprioritized courses:

| ID | Title | Status | Handling |
| ---: | --- | --- | --- |
| 2 | Space Science Adventures | private | Preserved but removed from active demo catalog |
| 5 | Storytelling and Reading Confidence | private | Preserved but removed from active demo catalog |
| 43 | YounGo QA Course Render Test | private | QA fixture preserved outside active demo catalog |

## Sections And Lessons

Final counts:

| Entity | Count |
| --- | ---: |
| section | 20 |
| lesson | 40 |

Active demo course structure:

| Course ID | Sections | Lessons |
| ---: | ---: | ---: |
| 1 | 3 | 6 |
| 3 | 3 | 6 |
| 4 | 3 | 6 |
| 6 | 3 | 6 |
| 9 | 2 | 4 |

Course 9 new sections:

- Robotics Thinking Basics / أساسيات التفكير الروبوتي
- AI Missions and Responsible Ideas / مهام الذكاء الاصطناعي والأفكار المسؤولة

Course 9 new lessons:

- How Robots Sense and Act / كيف تشعر الروبوتات وتتحرك
- Plan Your First Robot Mission / خطط لأول مهمة روبوتية
- Simple AI: Patterns and Choices / ذكاء اصطناعي بسيط: الأنماط والاختيارات
- Responsible AI Mini Project / مشروع صغير عن الذكاء الاصطناعي المسؤول

Existing lessons for courses 1, 3, 4, and 6 were edited to polished bilingual text summaries.

## Translation Status

Final translation counts:

| Table | Count |
| --- | ---: |
| youngo_category_translations | 24 |
| youngo_course_translations | 16 |
| youngo_section_translations | 34 |
| youngo_lesson_translations | 68 |

English and Arabic translation rows exist for all active demo categories and courses. Arabic section/lesson rows exist for active demo content edited in this phase. Translation table language codes remain limited to `english` and `arabic`.

## Old Data Handling

Old demo data was not deleted.

Course IDs 1, 6, and 9 were preserved because prior diagnostics and QA notes reference them. Course 9 was rebuilt in place from the ugly placeholder title into a clean robotics/AI demo course.

## Images And Thumbnails

No new images were uploaded.

The Academy/YounGo course edit flow automatically renames existing tracked course thumbnail files when `last_modified` changes. This caused six old thumbnail filenames to be deleted and six replacement filenames to appear under `uploads/thumbnails/course_thumbnails/`.

This is a dashboard side effect, not a manual file edit. The new filenames match the current database `last_modified` values for edited courses.

## Access And Payment Safety

Paymob/payment/checkout/cart/coupon implementation was not modified.

All active demo courses remain `subscription_only`, and visible frontend CTAs remain demo-safe through the DEMO.2 boundary. Runtime smoke found no direct cart/payment write links on the tested pages.

Limitation: subscription-only course edits did not persist the legacy `is_free_course` checkbox for active demo courses, so some course cards/details display `EGP 0` while still showing safe subscription/contact messaging. This should be reviewed in DEMO.4 screenshot QA.

## Root Admin Safety

Root Admin was used only for normal dashboard login/content administration. Root Admin user fields, credentials, role, status, and permissions were not edited through dashboard forms.

Read-only verification after content changes showed Root Admin id 1 retained `role_id = 1` and `status = 1`.

## Diagnostics

Passed:

- `youngo_phase_2u6_controlled_phrase_seed_diagnostic.php`
- `youngo_phase_2s_route_cta_boundary_diagnostic.php`
- `youngo_phase_2p_learner_access_visibility_diagnostic.php`
- `youngo_phase_2r_admin_entitlement_summary_diagnostic.php`
- `youngo_phase_2u3_localization_schema_diagnostic.php`

Failed for expected post-DEMO.3 baseline reasons:

- `youngo_phase_2u5_course_bilingual_forms_diagnostic.php`
- `youngo_phase_2u5_category_bilingual_forms_diagnostic.php`
- `youngo_phase_2u5_section_lesson_bilingual_forms_diagnostic.php`
- `youngo_phase_2u5_translation_model_diagnostic.php`
- `youngo_phase_2u4_arabic_phrase_diagnostic.php`

Those failures are caused by intentional Arabic category/course/section/lesson translation rows created through dashboard forms in DEMO.3. The failing diagnostics still assert earlier phase restore baselines where Arabic content rows outside the scoped QA turn had to remain zero.

Failed due nested older diagnostic assumptions:

- `youngo_phase_2u6_frontend_language_context_diagnostic.php`
- `youngo_phase_2u6_arabic_route_alias_diagnostic.php`
- `youngo_phase_2u6_frontend_content_translation_diagnostic.php`
- `youngo_phase_2u6_language_switcher_rtl_diagnostic.php`
- `youngo_phase_2u6_low_risk_phrase_conversion_diagnostic.php`
- `youngo_demo_payment_cta_boundary_diagnostic.php`

Direct checks inside those diagnostics passed for source boundaries, `/ar` route aliases, no `/en`, no payment/cart/coupon localization, valid language codes, and protected table counts. Failures propagate from older nested diagnostics that still expect pre-DEMO.3 Arabic content row counts.

Additional diagnostic note:

- Before temporary thumbnail stashing, several diagnostics also failed strict dirty-scope checks because dashboard course edits renamed tracked thumbnail files.
- After temporarily stashing only `uploads/thumbnails/course_thumbnails`, phrase seed passed and dirty-scope failures were removed.
- The thumbnail changes were restored after diagnostics.

## Runtime GET Smoke

Safe GET smoke was performed after the backup. No forms were submitted.

| URL | Status | lang/dir | Result |
| --- | ---: | --- | --- |
| `/` | 200 | en/ltr | Clean content found, no `/en`, no direct cart/payment write links |
| `/ar` | 200 | ar/rtl | Arabic shell/content found, no `/en`, no direct cart/payment write links |
| `/home/courses` | 200 | en/ltr | Clean catalog content found |
| `/ar/courses` | 200 | ar/rtl | Arabic catalog content found |
| `/home/search?query=Scratch` | 200 | en/ltr | Scratch result content found |
| `/ar/search?query=Scratch` | 200 | ar/rtl | Arabic Scratch result content found |
| `/home/course/scratch-coding-for-young-creators/1` | 200 | en/ltr | Course detail resolves |
| `/ar/course/scratch-coding-for-young-creators/1` | 200 | ar/rtl | Arabic course detail resolves |
| `/home/course/robotics-and-ai-explorers/9` | 200 | en/ltr | Robotics course detail resolves |
| `/ar/course/robotics-and-ai-explorers/9` | 200 | ar/rtl | Arabic robotics course detail resolves |

## Final Counts

| Table | Count |
| --- | ---: |
| category | 12 |
| course | 8 |
| section | 20 |
| lesson | 40 |
| youngo_category_translations | 24 |
| youngo_course_translations | 16 |
| youngo_section_translations | 34 |
| youngo_lesson_translations | 68 |
| enrol | 1 |
| payment | 0 |
| youngo_course_access | 0 |
| youngo_user_subscriptions | 0 |
| youngo_manual_grants | 0 |
| youngo_checkout_orders | 0 |
| youngo_coupon_usages | 0 |
| youngo_coupon_subscription_plans | 0 |
| youngo_coupon_courses | 0 |

## Remaining Issues

- DEMO.4 should screenshot-review visible `EGP 0` labels on subscription-only demo courses.
- Course thumbnails were not redesigned; existing thumbnails were retained and automatically renamed by the dashboard edit flow.
- Older diagnostics should be updated later to understand the durable DEMO.3 Arabic content baseline instead of expecting zero Arabic category/section/lesson rows.
- Category-level hiding is not available in the current dashboard category form; inactive categories were cleaned rather than hidden.

## Recommended Next Phase

Proceed to DEMO.4 screenshot QA, with focused attention on price labels, thumbnail fit, and Arabic page visual polish.

## DEMO.3 Review Cleanup Addendum

Date: 2026-07-19

### Thumbnail Reconciliation

YounGo frontend course images are resolved through `Crud_model::get_course_thumbnail_url()`. The rendered filename is derived from the course id, active frontend theme, and `course.last_modified`. The legacy `course.thumbnail` column still contains older filenames for courses 1 through 6, but the reviewed YounGo frontend surfaces do not use that column directly for course thumbnails.

Active demo course runtime thumbnail mapping:

| Course ID | Course | Runtime filename | Disk status | Notes |
| ---: | --- | --- | --- | --- |
| 1 | Scratch Coding for Young Creators | `course_thumbnail_youngo_11784421120.jpg` | Exists | Use this file |
| 3 | Digital Design for Kids | `course_thumbnail_youngo_31784421120.jpg` | Exists | Use this file |
| 4 | STEM Challenges Lab | `course_thumbnail_youngo_41784421120.jpg` | Exists | Use this file |
| 6 | Young Entrepreneurs Starter Program | `course_thumbnail_youngo_61784421121.jpg` | Exists | Use this file |
| 9 | Robotics and AI Explorers | `course_thumbnail_youngo_91784421121.jpg` | Missing | Safe fallback to `assets/frontend/youngo/images/course-coding.webp` |

Private course runtime thumbnail mapping:

| Course ID | Course | Runtime filename | Disk status | Notes |
| ---: | --- | --- | --- | --- |
| 2 | Space Science Adventures | `course_thumbnail_youngo_21784420052.jpg` | Exists | Private, keep referenced file |
| 5 | Storytelling and Reading Confidence | `course_thumbnail_youngo_51784420052.jpg` | Exists | Private, keep referenced file |

The six old tracked thumbnail files ending in `1783873172.jpg` are no longer used by the current YounGo runtime helper after dashboard edits changed `last_modified`. They should remain deleted.

Thumbnail commit recommendation:

- Commit the six current `uploads/thumbnails/course_thumbnails/course_thumbnail_youngo_*178442*.jpg` files.
- Commit the deletion of the six old `uploads/thumbnails/course_thumbnails/course_thumbnail_youngo_*1783873172.jpg` files.
- Do not create or fake a course-specific course 9 thumbnail in this cleanup; the YounGo placeholder fallback exists and renders.

### EGP 0 Review And Cleanup

Runtime review found visible `EGP 0` labels on the English and Arabic course listing pages and on the Scratch course detail page. These courses were `subscription_only`, not true free courses, so `EGP 0` looked like incomplete demo pricing.

Dashboard-only pricing edits were submitted through `admin/course_actions/edit/{id}` while preserving the existing bilingual content and `subscription_only` access mode:

| Course ID | Course | Previous price | New demo price |
| ---: | --- | ---: | ---: |
| 1 | Scratch Coding for Young Creators | 0 EGP | 900 EGP |
| 3 | Digital Design for Kids | 0 EGP | 950 EGP |
| 4 | STEM Challenges Lab | 0 EGP | 1100 EGP |
| 6 | Young Entrepreneurs Starter Program | 0 EGP | 1500 EGP |
| 9 | Robotics and AI Explorers | 500 EGP | 1200 EGP |

Post-cleanup runtime GET checks found `EGP 0` count is now zero on:

- `/home/courses`
- `/ar/courses`
- `/home/course/scratch-coding-for-young-creators/1`
- `/ar/course/scratch-coding-for-young-creators/1`
- `/home/course/robotics-and-ai-explorers/9`
- `/ar/course/robotics-and-ai-explorers/9`

No Paymob, checkout, cart, coupon, route, source-code, or language JSON files were changed. Payment/access protected table counts remained clean after the dashboard pricing edits.

### DEMO.4 Readiness

DEMO.4 screenshot QA is allowed after this cleanup. Focus screenshot review on visual fit of existing thumbnails, the shared placeholder used by course 9, and the demo-safe subscription/contact CTA copy beside the non-zero EGP prices.
