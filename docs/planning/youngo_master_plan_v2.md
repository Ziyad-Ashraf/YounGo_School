# YounGo Master Plan v2

## Maximizing Academy LMS Reuse Instead of Rebuilding the System

## 1. Executive Summary

The correct plan for the project from this point forward is not:

> Build a new YounGo platform alongside Academy LMS.

The correct plan is:

> **Use Academy LMS as the platform's core engine, then develop YounGo as a product, identity, and experience layer on top of it, modifying or extending existing components whenever that supports our goals and saves time.**

The latest audit confirmed that the legacy system is not merely a simple CMS. It is a complete LMS that already includes:

- Admin, instructor, and student management.
- Course creation and management.
- Categories, sections, lessons, and quizzes.
- Enrollments and progress tracking.
- Cart, wishlist, and payments.
- Reviews, messages, and reports.
- Instructor applications and payouts.
- Student learning flow and lesson player.

Rebuilding these features from scratch would waste time and increase risk. The goal is to use the existing functionality, improve its user experience, and add only what is missing.

The current Phase 2 architecture reference is:

```text
docs/planning/youngo_phase_2_hybrid_access_subscriptions_roles_architecture.md
```

Any work touching access, subscriptions, coupons, manual grants, checkout, payments, roles, permissions, or instructor assignment must be checked against that document before implementation.

The current Phase 2B database schema and migration planning reference is:

```text
docs/planning/youngo_phase_2_database_schema_migration_plan.md
```

It documents the additive database direction and local live-DB verification notes for Phase 2B. It is planning-only and does not mean schema changes or Phase 2 features have been implemented.

The current Phase 2C entitlement/access compatibility planning reference is:

```text
docs/planning/youngo_phase_2_entitlement_access_compatibility_plan.md
```

It documents the read-only-first access-layer direction for course and lesson access compatibility. The original document remains the planning reference; the current Phase 2F/2G status below records the approved read-only helper/model, presentation-only integrations, and first controlled web hard-gate integrations now completed locally.

Current Phase 2F/2G entitlement status:

- The YounGo entitlement helper/model has been implemented as read-only local code.
- The local entitlement diagnostic script has been implemented and passed.
- Course Details, My Courses, and Course Listing cards now consume the read-only access state for presentation-only status display.
- `Home::lesson()` now uses the YounGo entitlement state as its hard access decision while preserving valid legacy enrolment, admin/root, and assigned instructor access.
- `Files::index()` now uses the YounGo entitlement state as its hard access decision before file path resolution while preserving valid legacy enrolment, admin/root, and assigned instructor access.
- `enroll_status()` remains unchanged.
- Active `youngo_course_access` rows and active subscriptions for subscription-eligible courses are accepted by the entitlement path.
- Later Phase 2O work also aligned `Home::play_lesson()`, `Home::pdf_canvas()`, selected helper routes, course cards, course detail CTAs, and course review gates with the YounGo entitlement read layer where scoped.
- Later Phase 2P work added learner-account visibility: My Courses now uses prepared learner access items for active legacy enrolments and active direct YounGo/manual course access rows, and My Access shows visibility-only subscription/access summaries.
- `Payment.php`, cart/payment/checkout/invoice behavior, role/permission behavior, progress write endpoints, and broader API/mobile gates remain untouched or not replaced.
- Subscription checkout, Paymob/payment integration, and production/server migration are not implemented. Manual Grants dashboard/UI, learner-facing access-surface alignment, and learner account visibility are implemented and QA-tested locally.

Phase 2G QA status and gaps:

- Denied logged-out `Home::lesson()` access was tested safely; `watch_histories` and `watched_duration` did not increase.
- `Home::lesson()` now decides access before calling `update_last_played_lesson()`.
- `Files::index()` authorization uses the session user id only; request/query `user_id` is not trusted, and a fake `user_id` parameter did not stream protected content.
- `Files::index()` validates course existence, lesson existence, and lesson/course match before file source/path resolution.
- Denied direct file access returned safe empty responses, and denied file tests did not create watch/progress/enrol data.
- Streaming internals were not intentionally changed.
- Phase 2G.8C reran the controlled entitlement-state QA matrix after cleanup/reapply of the fixed local QA dataset. The matrix passed for instructor access, valid and expired legacy enrol, active/expired/revoked course access, active/expired/revoked subscription access, purchase-only denial under subscription, and media-course access state.
- The fixed local QA dataset state for Phase 2G.8C was QA courses `32-42`, QA sections `43-53`, QA lessons `62-84`, enrol rows `8-9`, `youngo_course_access` rows `5-8`, and `youngo_user_subscriptions` rows `4-6`.
- The `TC-COURSE-ACCESS-REVOKED` isolation fix was confirmed: course `37` is `purchase_only`, `youngo_subscription_excluded=1`, user `6` has `has_access=false`, `access_source=course_purchase`, and `status=revoked`.
- Phase 2G.8C non-authenticated denial checks passed: logged-out `Home::lesson()` did not render protected content; logged-out `Files::index()` did not stream the protected PDF/file; fake request `user_id`, lesson/course mismatch, and missing lesson checks did not stream content or fatal.
- Denied Phase 2G.8C tests did not change `watch_histories` or `watched_duration`. No source code changed during QA, no manual session rows were created, and `ci_sessions` increased only through normal HTTP requests.
- The controlled entitlement QA dataset and matrix were created, tested, corrected, and cleaned up locally.
- Remaining QA gaps are authenticated Root/Admin lesson and file playback, authenticated assigned-instructor lesson and file playback, authenticated legacy-enrol playback, authenticated course-access playback, authenticated subscription playback, authorized PDF/media streaming, MP4 playback and HTTP range-request behavior, remaining web content/progress gates, and API/mobile gates.
- No source fix is needed from Phase 2G.8C. Do not claim full authenticated lesson/file/media playback coverage until authenticated browser and MP4/range QA are performed.

Phase 2H local demo data alignment status:

- Phase 2H.2 local demo data alignment was applied successfully to the local database only.
- User-created phpMyAdmin backup before apply: `D:\Work\YounGo\backups\youngo_school (4).sql`.
- Applied only: `database/phase_2/demo_alignment/youngo_phase_2h_demo_data_alignment_up.sql`.
- New subcategories created:
  - `9` Science Foundations under parent `2` Science Explorers
  - `10` Creative Foundations under parent `3` Creative Arts
  - `11` Reading Foundations under parent `4` Reading & Storytelling
  - `12` Life Skills Foundations under parent `6` Life Skills
- Demo courses `1-6` were aligned to valid child subcategories under their existing parent categories:
  - Course `1` -> `sub_category_id=7`, parent `1`
  - Course `2` -> `sub_category_id=9`, parent `2`
  - Course `3` -> `sub_category_id=10`, parent `3`
  - Course `4` -> `sub_category_id=8`, parent `5`
  - Course `5` -> `sub_category_id=11`, parent `4`
  - Course `6` -> `sub_category_id=12`, parent `6`
- Confirmed unchanged: course `9`, users/passwords, `enrol`, `payment`, `youngo_course_access`, `youngo_user_subscriptions`, and source code.
- Validation passed: PHP lint for `Admin.php`, `Crud_model.php`, `Home.php`, and `Files.php`; HTTP smoke for `/`, `/home/courses`, course details, `/login`, `/sign_up`, and `/home/shopping_cart`; read-only form compatibility confirmed categories `2`, `3`, `4`, and `6` now have selectable subcategories.
- This was not applied to production/server. Course `9` is still not fixed. Course pricing/free-course versus YounGo subscription-only behavior is still unresolved. Media/video/PDF demo content remains future work.

Phase 2I rebaseline status:

- Phase 2I starts from clean, pushed baseline commit `ca30def` on branch `analysis/cms-audit`.
- Commit `ca30def` is recorded only as the clean starting point for Phase 2I documentation alignment, not as a permanent current baseline after this phase.
- Phase 2I is documentation-only and records the current demo-data policy, completed local baseline, remaining QA gaps, and revised Phase 2 dependency roadmap.

Local demo data policy:

- The local YounGo system contains no real production, client, learner, payment, enrolment, or operational data.
- Local users, courses, categories/subcategories, sections/lessons, media/content, enrolments, access records, subscriptions, and progress records are demo, seed, development, incomplete, or testing data.
- Root Admin is the only protected local identity by default.
- Other local demo records may be edited, deleted, rebuilt, reassigned, completed, archived, or replaced whenever doing so improves system flow, current form compatibility, role/capability behavior, course ownership, instructor assignment, entitlement behavior, subscriptions, manual grants, checkout/orders, payments, QA coverage, or overall consistency.
- Incomplete demo data must not be preserved for its own sake, and existing demo records must not dictate system architecture. Courses `1-6` and course `9` are not protected assets or architectural dependencies.
- Approved database-write phases still require a user-created phpMyAdmin backup. Data changes must remain scoped, documented, and reversible where practical. Temporary QA data and fixtures must not be included in production deployment packages.
- Future reset/baseline scripts must explicitly preserve Root Admin unless the project owner gives a separate instruction. Do not assume all `role_id = 1` users are protected Root Admins.

Current completed local Phase 2 baseline:

- Additive Phase 2 schema and seed foundation exists locally.
- Read-only YounGo entitlement compatibility layer exists locally.
- Entitlement presentation exists on Course Details, My Courses, and Course Listing cards.
- `Home::lesson()` uses the YounGo entitlement hard gate.
- `Files::index()` uses the YounGo entitlement hard gate.
- `enroll_status()` remains unchanged.
- Controlled entitlement QA dataset and matrix were created, tested, corrected, and cleaned up.
- Phase 2H demo category/subcategory alignment was applied locally.

Not complete:

- Full authenticated browser lesson/file QA.
- Authorized PDF/video playback QA.
- MP4 range-request QA.
- Subscription purchasing.
- Manual grants.
- Checkout/orders.
- Coupons.
- Paymob.
- Role assignment docs/UX polish beyond the Phase 2V.1 toggle UI, if needed.
- Remaining web gates.
- API/mobile gates.

Revised Phase 2 dependency roadmap:

1. Phase 2I - Project rebaseline and documentation alignment
2. Phase 2J - Minimal capability enforcement for new Phase 2 admin pages
3. Phase 2K - Course access configuration in Course Add/Edit
4. Phase 2L - Subscription plan management
5. Phase 2M - Shared entitlement write service foundation completed locally
6. Phase 2N - Manual Grants dashboard/UI using the shared write service completed locally
7. Phase 2O - Learner-facing entitlement visibility/enforcement alignment completed locally
8. Phase 2P - My Courses / learner access and subscription account visibility completed locally
9. Phase 2Q - Curated QA learner baseline completed locally
10. Phase 2R - Read-only admin/user entitlement summaries completed locally
11. Phase 2S - YounGo CTA boundary fixes completed locally
12. Phase 2T - Course Data Rebuild planning
13. Phase 2U - Arabic/English localization decision and language data support; Phase 2U.3 localization translation schema foundation, Phase 2U.4 canonical Arabic UI phrase support, Phase 2U.5.2 translation model/helper foundation, Phase 2U.5.3 category/subcategory bilingual form support, Phase 2U.5.4 course add/edit bilingual form support, Phase 2U.5.5 section/lesson bilingual form support, Phase 2U.6.2 frontend language context helpers, Phase 2U.6.3 Arabic public route aliases, Phase 2U.6.4 translation-aware frontend content shaping, and Phase 2U.6.5 language switcher/RTL shell rendering completed locally
14. Phase 2V.0 - Roles & Permissions Audit completed locally
15. Phase 2V.1 - Role Assignment Management completed locally
16. Phase 2U.6.6 - Frontend phrase conversion/inventory
17. Phase 2U.6.7 - Controlled frontend localization QA and diagnostics
18. Phase 2U.7 - Bilingual QA and Arabic phrase polish QA before public Arabic launch
19. Phase 2T - Course Data Rebuild after bilingual infrastructure is ready
20. Phase 2W - Checkout/order/coupon planning
21. Phase 2X - Paymob integration after checkout/order/coupon foundation
22. Phase 2V.2 - Role assignment docs/UX polish or QA follow-up, if needed and not blocking localization

Dependency rules:

- New Phase 2 admin pages must use minimal capability enforcement before being introduced.
- Full role-management UI is not required before every feature, but new pages must not rely only on broad `role_id == 1` checks.
- Course access settings must be manageable before course purchase checkout is built.
- Subscription plans must be manageable before subscription checkout.
- Manual grants and checkout must use `Youngo_entitlement_write_model`.
- Access issuance must be auditable and idempotent.
- Coupons must operate through formal orders and must never directly grant access.
- Paymob must be added only after the internal order lifecycle and access issuance are stable.
- Do not route YounGo subscription-only courses into legacy checkout/cart/coupon behavior as a shortcut while checkout/order issuance is not implemented.
- No broad learner/commercial rollout should be considered ready before learner account visibility, checkout/order issuance, payment, and final pricing are approved.
- The curated demo baseline should be rebuilt after the management flows exist, using the actual system flows wherever possible.
- Demo data serves the system; the system must not be designed around the current demo data.

The current Phase 2D multi-role/capability compatibility planning reference is:

```text
docs/planning/youngo_phase_2_multi_role_capability_compatibility_plan.md
```

It documents the additive role/capability direction for preserving existing Academy LMS role, permission, Root Admin, restricted admin, and instructor assignment behavior. It is a planning reference by itself; current implementation status is tracked below, including the completed Phase 2V.1 role assignment toggle UI and permission bridge.

The current Phase 2E schema migration execution planning reference is:

```text
docs/planning/youngo_phase_2_schema_migration_execution_plan.md
```

It documents the local-first migration execution direction for reviewable SQL artifacts, preflight, idempotency, rollback, seed safety, local apply validation, and server deployment safeguards. The current result is local schema-only readiness; it does not mean server schema changes or Phase 2 subscription, hard access gate entitlement, coupon, manual grant, checkout, Paymob, or multi-role behavior have been implemented.

Phase 2E local status:

- The approved Phase 2E schema artifacts were applied to the local database only after manual backup confirmation.
- Local backup: `D:\Work\YounGo\backups\youngo_school_before_phase_2e_apply_2026_07_14.sql`
- Local DB: `youngo_school`
- Server: MariaDB `10.4.32`
- Local schema-only validation passed. No server migration has been performed, and the schema apply did not implement Phase 2 product behavior.

---

## 2. Approved Project Architecture

### Academy LMS = Core Engine

The legacy system remains responsible for:

- Authentication.
- Roles and permissions.
- Course storage.
- Category storage.
- Section and lesson management.
- Enrollment.
- Cart and wishlist.
- Payments.
- Reviews.
- Progress tracking.
- Messaging.
- Reports.
- Instructor approval.
- Student access control.

### YounGo = Product Experience Layer

YounGo is responsible for:

- Visual identity.
- Frontend experience.
- Parent-friendly messaging.
- Kids-learning presentation.
- Better navigation.
- Homepage management.
- Course discovery.
- Course detail presentation.
- Student and instructor UX improvements.
- Selective backend enhancements.
- Media and content presentation.

### Adapters and Extensions

A small integration layer should be added only when needed to connect the two systems:

- YounGo-specific views.
- YounGo CSS and JavaScript.
- Theme configuration.
- Data adapters.
- Media pickers.
- Content selectors.
- Small backward-compatible controller, model, and helper changes.
- Deployment and data-seeding tools.

---

## 3. Decision-Making Rule

Every new feature must be evaluated in the following order:

### Level 1: Reuse As-Is

Use the existing functionality without modification when it is already suitable.

Examples:

- Course routes.
- Login logic.
- Enrollment.
- Payment endpoints.
- Wishlist.
- Reviews.
- Course creation controllers.
- Section and lesson CRUD.

### Level 2: Adapt Presentation

Keep the existing logic and change only how it is presented.

Examples:

- Courses listing.
- Course details.
- Login and sign-up.
- My Courses.
- Student profile.
- Cart page.
- Lesson player styling.

### Level 3: Extend Carefully

Add a small change to the legacy system when it avoids significant duplication.

Examples:

- Passing course-picker data to the admin view.
- Adding YounGo theme configuration.
- Adding a helper that returns prepared data for YounGo views.
- Improving media selection support.
- Standardizing duration formatting.

### Level 4: Rebuild Only When Missing

Rebuild a feature only when it does not exist at all or when the legacy implementation is unusable.

---

## 4. Work Completed So Far

### Public Experience

| Component | Status |
|---|---|
| Homepage | Completed |
| Courses Listing | Completed |
| Course Details | Completed |
| Login | Completed |
| Sign Up | Completed |
| Header / Footer | Completed |
| Homepage Admin Manager | Completed |
| Homepage Categories/Courses Integration | Completed |
| Demo Course Content | Completed Locally |

### LMS Data Enrichment

The following have been prepared:

- 6 categories.
- 6 courses.
- 18 sections.
- 36 lessons.
- Course thumbnails.
- Category thumbnails.
- Outcomes.
- Requirements.
- FAQs.
- Reviews.
- Instructor presentation data.

### Existing LMS Features Reused

The current system has been reused for:

- Course routes.
- Course filtering.
- Search and pagination.
- Wishlist.
- Cart.
- Free enrollment.
- Buy now.
- Course preview.
- Reviews.
- Curriculum.
- Instructor data.
- Related courses.

Important Phase 2 note:

- Cart, buy-now, free-enrollment, coupon, purchase, payment, invoice, and enrolment behavior listed here reflects existing Academy LMS functionality and Phase 1 compatibility.
- The Phase 2 target model adds subscriptions, manual grants, direct checkout, expanded coupon scope, and a multi-role/capability layer beside the existing LMS behavior.
- Do not remove or rebuild existing Academy LMS purchase, enrolment, cart/session, payment, lesson access, progress, invoice, or instructor behavior until a tested compatibility layer exists.

---

## Current Execution Status

This section records the current planning checkpoint after Phase PD-Freeze. It is additive status tracking only; the strategic reuse rules and roadmap in this master plan remain the source of truth.

### Completed

- [x] Master Plan v2 created and set as source of truth
- [x] Documentation alignment completed
- [x] YounGo homepage implemented
- [x] YounGo homepage CMS manager implemented
- [x] YounGo auth pages implemented
- [x] YounGo course listing implemented
- [x] YounGo course details implemented
- [x] YounGo theme config added
- [x] Admin course creation smoke test passed with YounGo theme
- [x] PD-1B temporary smoke-test course cleaned up
- [x] Git/deployment hygiene completed
- [x] Missing system media references restored
- [x] Deployment package manifest created
- [x] Curated media deployment package created
- [x] Sanitized DB export created
- [x] Restricted client admin created
- [x] Sanitized DB export R2 created after adding client admin
- [x] Deployment readiness freeze audit completed
- [x] Student Dashboard / My Courses baseline completed
- [x] Lesson Player polish completed
- [x] Cart / Wishlist / Checkout safe-state pages completed
- [x] Profile / Purchase History / Account pages completed

### Ready, Server-Side Pending

- [ ] Import DB R2 on server
- [ ] Upload/unpack media ZIP on server
- [ ] Configure server database.php
- [ ] Set writable folder permissions
- [ ] Confirm GD enabled on server
- [ ] Confirm payments disabled or sandbox-only
- [ ] Configure or defer SMTP
- [ ] Run server smoke test
- [ ] Confirm client admin login on server
- [ ] Confirm client admin can create course on server

### Pending Product Phases

- [x] Student Dashboard / My Courses - My Courses page completed and validated; broader student dashboard shell deferred
- [x] Lesson Player polish - Existing Academy LMS lesson player visually polished for YounGo; lesson access, progress, quiz, and content logic preserved
- [x] Cart / Wishlist / Checkout pages - YounGo cart and wishlist pages completed; checkout safe-state messaging added while preserving Academy LMS payment, coupon, and enrollment logic
- [x] Profile / Purchase History / Account pages - YounGo account, profile, credentials, photo, purchase history, and invoice views completed while preserving Academy LMS account, password, upload, payment, and invoice logic
- [ ] Phase 2 hybrid access, subscriptions, coupons, manual grants, direct checkout, and multi-role architecture implementation
- [ ] Instructor experience polish - deferred until the Phase 2 role/capability architecture is planned and compatible with existing instructor behavior
- [ ] Final QA and production security review

### Current Deployment Recommendation

Use DB R2 ZIP:

```text
D:\Work\YounGo\deployment_staging\youngo_demo_20260712_2333\database\youngo_demo_db_20260712_2333_r2.zip
```

Use media ZIP:

```text
D:\Work\YounGo\deployment_staging\youngo_demo_20260712_2333\youngo_demo_media_20260712_2333.zip
```

Do not use a fresh unfiltered local DB export unless course ID `9` is cleaned or intentionally included. The local DB currently contains active manual test course ID `9` (`Course title`) with no sections, no lessons, and no thumbnail; this course is intentionally excluded from DB R2.

These DB/media artifacts are Phase 1 deployment baseline artifacts only. They do not include Phase 2 subscription, entitlement, manual grant, expanded coupon scope, Paymob, or multi-role schema/features. Phase 2 deployment will require a new approved schema/export/migration and QA plan.

Client admin for the prepared deployment data:

```text
Email: client@gmail.com
User ID: 7
role_id: 1
permissions: ["course"]
```

### Next Product Phase

Recommended next product phase: Phase 2 architecture-driven implementation planning for hybrid access, subscriptions, coupons, manual grants, direct checkout, Paymob strategy, and multi-role/capability support.

Reason: Phase 1 created a stable YounGo experience on top of Academy LMS while preserving existing LMS behavior. Phase 2 touches business-critical access, payments, coupons, roles, and instructor permissions, so implementation must begin with the dedicated Phase 2 architecture plan.

---

## 4A. Phase 2 Architecture Direction

The active Phase 2 architecture document is:

```text
docs/planning/youngo_phase_2_hybrid_access_subscriptions_roles_architecture.md
```

The active Phase 2B database schema and migration planning document is:

```text
docs/planning/youngo_phase_2_database_schema_migration_plan.md
```

This Phase 2B document records the approved additive schema direction for hybrid access, subscriptions, coupon scope, manual grants, direct checkout orders, and multi-role/capability support. It remains the schema planning reference. Local Phase 2E schema readiness has been applied and validated locally only; server schema changes and Phase 2 product behavior are not implemented.

The active Phase 2C entitlement/access compatibility planning document is:

```text
docs/planning/youngo_phase_2_entitlement_access_compatibility_plan.md
```

This Phase 2C document records the approved compatibility-layer direction for `can_user_access_course(user_id, course_id)`, structured access state, access precedence, My Courses compatibility, lesson player compatibility, and future subscription/manual-grant/checkout readiness. It remains the planning reference for access work; the read-only helper/model, presentation integrations, controlled web hard gates, Manual Grants UI, and learner-facing access-surface alignment have now been implemented locally through Phase 2O.

Current entitlement implementation status:

- Read-only helper/model implemented:
  - `application/helpers/youngo_entitlement_helper.php`
  - `application/models/Youngo_entitlement_model.php`
- Read-only diagnostic implemented and passed:
  - `scripts/phase_2/youngo_phase_2f2_entitlement_diagnostic.php`
- Presentation-only integrations completed:
  - Course Details page
  - My Courses page
  - Course Listing cards
- Controlled hard-gate integrations completed locally:
  - `Home::lesson()`
  - `Files::index()`
- Later learner-facing access-surface integrations completed locally:
  - `Home::play_lesson()`
  - `Home::pdf_canvas()`
  - `go_course_playing_page()`
  - `lesson_mobile_web_view_get()`
  - `offline_video_for_mobile_app()`
  - course detail/course card CTA state
  - course review visibility/submission gate
- `enroll_status()` remains unchanged.
- Valid legacy enrolment, admin/root access, assigned instructor access, active `youngo_course_access`, and eligible active subscription access remain accepted by the entitlement path.
- Progress write endpoints, broader API/mobile gates, `Payment.php`, cart/payment/checkout/invoice behavior, and roles/permissions behavior are not replaced.
- My Courses learner visibility now uses centralized prepared access items for legacy enrolments and direct YounGo/manual course access; My Access remains visibility-only and does not implement checkout/renewal/payment/coupons.
- No subscription checkout, Paymob, coupon issuance, or real payment behavior has been implemented. Manual Grants dashboard/UI is implemented locally for admin-created grants only.
- Full file delivery compatibility is not claimed yet because authenticated browser file/PDF playback, authorized PDF streaming under a real session, and MP4/range streaming remain QA gaps.

The active Phase 2D multi-role/capability compatibility planning document is:

```text
docs/planning/youngo_phase_2_multi_role_capability_compatibility_plan.md
```

This Phase 2D document records the approved compatibility direction for adding YounGo roles and capabilities beside existing `users.role_id`, `users.is_instructor`, `permissions`, `has_permission()`, `check_permission()`, `is_root_admin()`, admin/user sessions, restricted admin behavior, and instructor assignment logic. It remains the compatibility reference for role work; Phase 2V.1 has since implemented controlled role assignment toggles and the legacy permission bridge.

The active Phase 2E schema migration execution planning document is:

```text
docs/planning/youngo_phase_2_schema_migration_execution_plan.md
```

This Phase 2E document records the approved execution direction for reviewable Phase 2 schema migration artifacts, local-only preflight/apply steps after explicit approval, avoiding `Updater.php` and `uploads/install.sql` in the first pass, and keeping server schema changes separately approved. The current result is local schema-only readiness; no server schema changes or Phase 2 behavior have been implemented.

Phase 2E local schema readiness status:

- Phase 2E schema was applied locally only to `youngo_school` on MariaDB `10.4.32`.
- Backup exists at `D:\Work\YounGo\backups\youngo_school_before_phase_2e_apply_2026_07_14.sql`.
- Local apply created 12 `youngo_%` tables, added the approved additive `course` and `coupons` columns, and seeded the approved inactive/non-purchasable reference data.
- Seed counts: `youngo_subscription_plans=3`, `youngo_roles=4`, `youngo_capabilities=18`, `youngo_role_capabilities=24`, `youngo_user_roles=0`.
- Root admin user `1` still has no permissions row, and `client@gmail.com` still has permissions `["course"]`.
- `users`, `permissions`, `enrol`, and `payment` legacy columns were not altered.
- Public HTTP smoke tests passed on `/`, `/home/courses`, `/home/course/scratch-coding-for-young-creators/1`, `/login`, `/sign_up`, `/home/shopping_cart`, and `/home/my_wishlist`.
- No server migration has been performed. This is schema-only local readiness; Phase 2 subscriptions, coupons, manual grants, direct checkout, Paymob, and broader multi-role behavior beyond the later Phase 2V.1 role assignment bridge are not implemented. Only the approved local `Home::lesson()` and `Files::index()` web hard gates have been integrated with the read-only entitlement state.

Phase 2 planned capabilities:

- Hybrid entitlement layer across course purchase, active subscription, manual grant, admin access, assigned instructor access, and legacy enrolment compatibility.
- Subscription plans: Monthly, 3 Months, and Yearly, with 3 Months as the main commercial focus.
- User subscriptions for one student/user account at a time.
- Course access configuration with subscription-only default, optional individual purchase, purchase-only exclusion from subscription access, lifetime purchase default, and optional time-limited purchase.
- Auditable manual grants for course purchase access and subscription access.
- Coupon/discount scope improvements for subscriptions, course purchases, or both.
- Direct checkout UX for YounGo course purchase and subscription flows while preserving legacy cart logic.
- Paymob payment planning with real payments disabled until implementation and QA approval.
- Multi-role/capability layer that supports Root Admin, Admin, Content Manager, Course Manager, Instructor, and User/Learner capability.
- Instructor assignment rules where Admin and Course Manager can assign existing instructors, but Course Manager cannot create or delete instructor accounts.

Phase 2 guardrails:

- Do not remove existing course purchase.
- Do not remove existing LMS enrolment/payment logic until a safe compatibility layer exists.
- Do not immediately remove `enrol` table logic.
- Do not immediately replace existing lesson access helpers such as `enroll_status()`.
- Do not remove cart code even though direct checkout is the target UX.
- Do not treat coupon/discount as direct access.
- Do not treat public free-course classification as the target YounGo business model.
- Do not enable real payments until implementation and QA are explicitly approved.
- Do not abruptly change existing `role_id` or `is_instructor` behavior.
- Protect Root Admin from casual modification or downgrade.

---

## 5. Role Audit Findings

### Admin

The admin can already:

- Create and edit courses.
- Add sections and lessons.
- Manage categories.
- Manage instructors and students.
- Manage enrollments.
- Review instructor applications.
- Activate courses.
- Manage payments and reports.
- Manage website settings.
- Manage themes.
- Use the YounGo Homepage Manager.

The admin interface is functional, but it still uses the Academy backend UI and has not yet been fully adapted to the YounGo UI.

### Instructor

The instructor is not stored as a separate database role. An instructor is a regular user with:

```text
role_id = 2
is_instructor = 1
```

An instructor can:

- Create courses.
- Add sections, lessons, and quizzes.
- Edit their own courses.
- Submit a course for publication.
- Track sales and payouts.
- Track student progress.
- Manage their profile.
- Use messages.

Courses created by an instructor receive a `pending` status and require admin activation before they appear publicly.

### Student

A student can:

- Register and log in.
- Browse courses.
- Search and filter.
- Use the wishlist.
- Use the cart.
- Enroll in free courses.
- Purchase paid courses.
- Open My Courses.
- Watch lessons.
- Track progress.
- Add reviews.
- Review purchase history and invoices.
- Use messages and profile features.

The problem was not missing functionality. Phase 1 has now added YounGo presentation for the main student/account paths, including My Courses, lesson player polish, cart/wishlist/checkout safe-state pages, profile, purchase history, and account pages. A broader student dashboard shell may still be expanded later.

Phase 2 target role direction:

- Accounts should support multiple roles/capabilities.
- A single account may be Course Manager plus Content Manager.
- Instructor may also be Course Manager or Content Manager if toggled.
- Learner/User capability should be automatic for all accounts for compatibility.
- Root Admin is a protected special case, not a normal toggle.
- Existing `role_id`, `permissions`, and `is_instructor` behavior must be treated as legacy compatibility until a new capability layer is implemented and tested.

---

## 6. Phase 1 Readiness Before Server Deployment

This section is retained as Phase 1 readiness history.

The local preparation work described here has been completed. The remaining work is server-side deployment and smoke testing.

### Phase PD-1 — Theme Compatibility

Status: completed.

The following YounGo theme configuration file has been added:

```text
assets/frontend/youngo/config/theme-config.json
```

Upload helpers depend on the active theme configuration. This had affected:

- Course thumbnail upload.
- Course image fallback.
- Media validation.
- Thumbnail dimensions.
- Placeholder images.

Completed local checks covered:

1. Review `default-new/config/theme-config.json`.
2. Identify the keys used by the system.
3. Create a YounGo-compatible theme configuration.
4. Test course thumbnail upload.
5. Test category thumbnail upload.
6. Test lesson media upload.

This is no longer a local blocker. Server smoke testing must still confirm uploads after DB R2 import and media ZIP upload.

### Phase PD-2 — Course Creation Readiness

Status: completed locally; server smoke test pending.

A complete local course-creation flow was tested using the current system:

1. Admin creates a course.
2. Admin selects a category.
3. Admin adds a title and description.
4. Admin uploads a thumbnail.
5. Admin adds outcomes and requirements.
6. Admin adds FAQs.
7. Admin creates sections.
8. Admin creates lessons.
9. Admin sets the course as free or paid.
10. Admin activates the course.
11. The course appears on `/home/courses`.
12. The course details page renders correctly.
13. The homepage can feature the course.

A new course-authoring system is not needed. The existing system already supports this flow. The server deployment checklist must still confirm the same flow after DB R2 import and media ZIP upload.

### Phase PD-3 — Client Test Account

The best account type for the first client test is:

#### Restricted Admin

Not Root Admin.

Recommended permissions:

- Courses.
- Categories.
- Sections.
- Lessons.
- Course media.
- Course activation.

The account should not receive access to:

- Payment settings.
- System settings.
- Themes.
- Add-ons.
- User deletion.
- Admin management.
- Database-sensitive functions.

This account has been created in the prepared Phase 1 DB R2 export. Server deployment must still confirm the client admin login and course-creation permissions after import.

#### Instructor Later

Instructor testing/polish is deferred until the Phase 2 role/capability architecture is implemented or approved as compatible with existing instructor behavior. Future testing should cover:

- Instructor course creation.
- Pending status.
- Admin approval.
- Sales and progress views.

---

## 7. Reusing the Legacy Backend

A complete backend redesign is not currently required.

### Components That Can Remain As-Is

- Course creation forms.
- Section and lesson forms.
- Category manager.
- User manager.
- Enrollments.
- Reports.
- Payment settings.
- Instructor applications.

### Components That Only Need Improvement

#### Branding Layer

Add:

- YounGo logo.
- Limited YounGo colors.
- Cleaner page titles.
- Client guidance messages.
- More readable field grouping.
- Help text.
- Course creation checklist.

#### Course Creation Assistant

Instead of building a new page builder, the Academy backend can include:

- Progress checklist:
  - Basic information.
  - Thumbnail.
  - Pricing.
  - Sections.
  - Lessons.
  - Publish status.

- Warnings:
  - No thumbnail.
  - No lessons.
  - Missing category.
  - Missing description.
  - Pending status.

This is a useful improvement to the legacy system and is preferable to replacing it.

---

## 8. Student Experience Roadmap

Phase 1 student-facing baseline pages have been developed with full reuse of existing LMS functionality. A broader student dashboard shell remains optional/deferred.

### Phase 1F — My Courses / Student Dashboard

Status: My Courses completed and validated; broader dashboard shell deferred.

Reused:

- Existing enrollment table.
- Existing progress helpers.
- Existing course links.
- Existing lesson access.
- Existing completion data.

The completed My Courses baseline builds only the YounGo presentation layer for:

- Continue learning.
- Progress percentage.
- In-progress courses.
- Completed courses.
- Empty states.
- Recommended next course.

### Phase 1G — Lesson Player

Status: completed.

The existing lesson player was reused instead of rebuilt from scratch.

Modified:

- Layout.
- Navigation.
- Progress display.
- Mobile experience.
- Lesson sidebar.
- Video, text, and PDF presentation.
- Previous and next controls.

Preserved:

- Lesson access logic.
- Watched duration.
- Completion.
- Certificate hooks.
- Enrollment checks.

### Phase 1H — Cart / Wishlist / Checkout

Status: completed as YounGo cart/wishlist pages plus checkout safe-state messaging.

Reused:

- Session cart.
- Wishlist JSON.
- Existing payment controllers.
- Existing gateway configuration.

YounGo views were built for the complete pages while preserving Academy LMS payment, coupon, cart/session, and enrolment logic.

### Phase 1I - Profile / Purchase History / Account

Status: completed.

Completed baseline pages include:

- Account overview.
- Profile and credentials.
- Photo upload flow.
- Purchase history.
- Invoice views.

Preserved:

- Existing Academy LMS account logic.
- Existing password/update behavior.
- Existing upload behavior.
- Existing payment and invoice logic.

---

## 9. Instructor Experience Roadmap

Instructor functionality already exists. A new instructor system is not required.

### Future Requirements

- Branding for the instructor dashboard.
- Better course list.
- Clear course statuses:
  - Draft.
  - Pending.
  - Active.
  - Private.
- Course creation checklist.
- Improved sections and lessons UX.
- Payout and sales presentation.
- Student progress visualization.

The priority is to reuse `/user/*` instead of building a separate instructor portal. Instructor experience polish is deferred until the Phase 2 role/capability architecture is implemented or approved as compatible with existing instructor behavior.

---

## 10. Admin Experience Roadmap

### Completed Component

- YounGo Homepage Manager.

### Components to Reuse As-Is

- Courses.
- Categories.
- Users.
- Enrollments.
- Payments.
- Reports.
- Settings.

### Proposed Improvements

- YounGo branding.
- Safer restricted-admin permissions.
- Course readiness indicators.
- Clearer media uploads.
- Better empty states.
- Client-friendly labels.
- Hide dangerous options from client test accounts.

---

## 11. Deployment Strategy

Deployment does not depend on Git alone.

Three parts are required:

### Code

- Repository.
- YounGo views.
- CSS and JavaScript.
- Theme configuration.
- Upload-related code.

### Database

- Users.
- Categories.
- Courses.
- Sections.
- Lessons.
- Reviews.
- Homepage content.
- Settings.
- Roles and permissions.

### Uploads

- Course thumbnails.
- Category thumbnails.
- User images.
- Lesson files.
- Captions.
- Resource files.
- System assets.

### Required Deliverables

- Deployment database export.
- Uploads manifest.
- Environment checklist.
- Rollback backup.
- Client credentials.
- Test script.

---

## 12. Server Readiness

The following must be reviewed before deployment:

### File Permissions

Folders such as:

```text
uploads/
uploads/thumbnails/
uploads/lesson_files/
uploads/resource_files/
uploads/system/
uploads/user_image/
```

must be writable.

### PHP Settings

- `upload_max_filesize`
- `post_max_size`
- `max_execution_time`
- `memory_limit`

### PHP Extensions

- MySQLi.
- GD.
- Fileinfo.
- mbstring.
- cURL.
- OpenSSL.
- Zip.
- JSON.

### Routing

- `.htaccess`.
- URL rewriting.
- `index_page = ''`.
- Correct domain and SSL configuration.

### SMTP

SMTP must either be configured or the first demo must not depend on it.

### Payments

Payments must be:

- Disabled.
- Or sandbox-only.

Real payments are not recommended during client testing.

### Security

The following settings must be reviewed before production:

- `csrf_protection = FALSE`
- Empty `encryption_key`
- `cookie_httponly = FALSE`

These are not blockers for an internal test version, but they are blockers before a real production launch.

---

## 13. What Must Not Be Done

Do not:

- Build a new course system.
- Build a new enrollment system.
- Build a new payment system.
- Build a separate instructor portal.
- Copy the entire `default-new` theme.
- Change routes without a clear reason.
- Create database tables for every YounGo feature.
- Store course data inside homepage JSON.
- Duplicate course logic inside views.
- Replace LMS functionality with static content.
- Remove existing course purchase while adding subscriptions.
- Remove cart code while adding direct checkout UX.
- Treat coupons or discounts as direct access.
- Treat public free-course classification as the target YounGo business model.
- Enable real payments before implementation and QA approval.
- Abruptly replace existing `role_id`, `permissions`, `is_instructor`, `enrol`, payment, invoice, or lesson access behavior.
- Create, activate, or make real commercial subscription plans purchasable before final prices are approved and the owner explicitly approves activation through the dashboard flow.
- Correct real or seeded subscription plan currency through manual row-level SQL unless explicitly approved; use the Subscription Plans dashboard flow after backup.
- Treat mixed legacy gateway currencies as a Subscription Plan Management blocker; gateway currency alignment belongs to the later payment/Paymob phase.

---

## 14. Current Plan in Execution Order

This execution order records the Phase 1/deployment baseline history. Phase 2 work must follow the Phase 2 architecture document before implementation.

### Phase One — Client Course Creation Readiness

Status: completed locally; server smoke test still pending.

1. Create YounGo `theme-config.json`.
2. Test course thumbnail upload.
3. Test section and lesson creation.
4. Test active course visibility.
5. Create a restricted client admin account.
6. Prepare a course creation checklist.

### Phase Two — Deployment Preparation

Status: completed as Phase 1 deployment baseline artifacts; server-side execution still pending.

1. Git audit.
2. Database export.
3. Uploads package.
4. Server PHP requirements.
5. Writable permissions.
6. SMTP configuration.
7. Payments disabled or configured for sandbox mode.
8. Security baseline.
9. Backup and rollback plan.

### Phase Three — Server Deployment

Status: pending server-side.

1. Upload code.
2. Import database.
3. Upload media.
4. Configure domain.
5. Configure URL rewriting.
6. Configure sessions.
7. Run smoke tests.
8. Create client credentials.
9. Run a supervised course-creation test.

### Phase Four — Client Testing

Status: pending server-side after deployment.

The client tests:

- Homepage.
- Courses listing.
- Course details.
- Login.
- Course creation.
- Thumbnail upload.
- Sections.
- Lessons.
- Course activation.
- Public visibility.

### Phase Five — Student Learning Experience

Status: Phase 1 baseline completed; broader dashboard shell remains optional/deferred.

- My Courses.
- Dashboard.
- Lesson player.
- Progress.
- Profile.
- Wishlist.
- Cart.
- Purchase history.

### Phase Six — Instructor Experience

Status: deferred until Phase 2 role/capability architecture is implemented or approved as compatible with existing instructor behavior.

- Instructor dashboard polish.
- Course-authoring polish.
- Course approval workflow.
- Sales and payouts.
- Student progress.

### Phase Seven - Phase 2 Hybrid Access And Roles

Status: partially implemented locally. Entitlement presentation/gates, capability foundation, course access settings, Subscription Plan Management, EGP system-currency alignment, the Shared Entitlement Write Service foundation, Manual Grants dashboard/UI, learner-facing entitlement visibility/enforcement alignment, My Courses / My Access learner visibility, and the curated QA learner baseline have been completed and QA-tested locally; checkout, Paymob, subscription purchase issuance, broader mobile/API entitlement UX, and broader commercial rollout remain pending.

- Hybrid entitlement compatibility layer: read-only helper/model implemented locally, diagnostics passed, and Course Details, My Courses, and Course Listing cards use the state for access presentation where currently integrated.
- `Home::lesson()`, `Files::index()`, `Home::play_lesson()`, `Home::pdf_canvas()`, `go_course_playing_page()`, `lesson_mobile_web_view_get()`, `offline_video_for_mobile_app()`, and the course review gate now use the entitlement state for scoped learner-facing access decisions while preserving legacy compatibility.
- Course Add/Edit now manage YounGo course access settings.
- Subscription Plan Management is implemented and QA-tested locally:
  - dedicated `Youngo_subscription_plans` controller;
  - dedicated `Youngo_subscription_model`;
  - admin list/form/detail views;
  - routes under `/admin/youngo/subscription-plans`;
  - YounGo navigation item gated by `manage_subscriptions`;
  - `scripts/phase_2/youngo_phase_2l_subscription_plan_diagnostic.php`.
- Phase 2L schema is applied locally:
  - `youngo_subscription_plans.archived_at`;
  - `youngo_subscription_plans.archived_by_user_id`;
  - `idx_ysp_archived_at`;
  - `youngo_subscription_plan_audit_log`;
  - `idx_yspal_plan_created`;
  - `idx_yspal_actor_created`;
  - no hard foreign keys.
- Phase 2L QA verified Root Admin access, seeded plan listing, temporary QA plan create/edit/status/archive/restore, audit events for create/update/activate/make-purchasable/hide/deactivate/archive/restore, invalid-form atomicity, cleanup restore, and no checkout/payment/order/coupon/manual grant/subscription issuance/enrol/access side effects.
- YounGo commercial currency is `EGP`; global `settings.system_currency` has been changed to `EGP` through the existing dashboard Payment Settings flow.
- Seeded Monthly, 3 Months, and Yearly subscription placeholders have been corrected through the Subscription Plans dashboard to store `EGP` and remain inactive/non-purchasable.
- Current local placeholder prices are not final commercial prices: Monthly `100.00 EGP`, 3 Months `250.00 EGP`, and Yearly `900.00 EGP`.
- The Phase 2L diagnostic now reports `settings.system_currency = EGP`, zero non-EGP subscription plans, and no currency blocker. This is subscription plan-definition currency readiness only; checkout/payment/subscription issuance are still not implemented.
- Shared Entitlement Write Service foundation is implemented and controlled service-QA tested locally:
  - `application/models/Youngo_entitlement_write_model.php`;
  - `database/phase_2/youngo_phase_2m_entitlement_write_schema_up.sql`;
  - `database/phase_2/youngo_phase_2m_entitlement_write_schema_down.sql`;
  - `scripts/phase_2/youngo_phase_2m_entitlement_write_diagnostic.php`.
- Phase 2M schema is applied locally:
  - `youngo_course_access.revoked_by_user_id`;
  - `youngo_course_access.revoke_note`;
  - index on `youngo_course_access.checkout_order_id`;
  - index on `youngo_course_access.revoked_by_user_id`;
  - `youngo_user_subscriptions.revoked_by_user_id`;
  - `youngo_user_subscriptions.revoke_note`;
  - index on `youngo_user_subscriptions.revoked_by_user_id`;
  - no hard foreign keys.
- The write service supports manual course grant, manual subscription grant, course/subscription revocation, linked manual grant revocation, duplicate active entitlement prevention, transactions/rollback, and safe non-writing checkout issuance stubs.
- Phase 2M controlled service QA verified Root Admin id `1` as actor only, receiver user `2`, course `1`, plan `1`, course grant/read-layer recognition/duplicate rejection/revocation/read-layer denial, subscription grant/read-layer recognition/duplicate rejection/revocation/read-layer denial, checkout stub non-writing behavior, cleanup DB restore, and no checkout/payment/order/coupon/enrol/progress side effects.
- Manual Grants dashboard/UI is implemented and authenticated UI-QA tested locally:
  - `application/controllers/Youngo_manual_grants.php`;
  - admin list/create/detail views;
  - routes under `/admin/youngo/manual-grants`;
  - YounGo navigation item gated by `grant_manual_access`;
  - admin session and `grant_manual_access` enforcement;
  - all grant/revoke writes delegated to `Youngo_entitlement_write_model`;
  - no delete path and POST-only revocation.
- Phase 2N authenticated UI QA verified Root Admin login through the normal web flow, navigation/list/create pages, manual course grant, read-layer active course recognition, duplicate course grant rejection, course revocation, read-layer revoked-course denial, manual subscription grant, read-layer active subscription recognition, duplicate subscription rejection, subscription revocation, cleanup DB restore from `D:\Work\YounGo\backups\youngo_school (14).sql`, and no checkout/payment/order/coupon/enrol/progress side effects.
- Phase 2O learner-facing entitlement visibility/enforcement alignment is implemented and controlled browser-QA tested locally:
  - course card CTA/status alignment;
  - course detail CTA/status alignment;
  - manual grant, subscription, and purchased access labels;
  - checkout-not-ready messaging for paid subscription-only courses;
  - prevention of legacy Buy Now/Add to cart exposure for paid subscription-only checkout-not-ready states;
  - `Home::play_lesson()`, `Home::pdf_canvas()`, `go_course_playing_page()`, `lesson_mobile_web_view_get()`, `offline_video_for_mobile_app()`, and course review visibility/submission gates aligned with the entitlement read layer.
- Phase 2O QA verified public/logged-out course listing/detail checks, paid subscription-only checkout-not-ready display, temporary manual course and subscription grants through the Manual Grants UI, duplicate rejection, revocation, protected-table stability, cleanup DB restore from `D:\Work\YounGo\backups\youngo_school (15).sql`, and final clean diagnostics. Later Phase 2Q work established a QA learner baseline for future learner-authenticated browser QA.
- Phase 2P My Courses / My Access learner visibility is implemented and controlled-QA tested locally:
  - read-only learner access methods in `Youngo_entitlement_model`: `get_learner_course_access_items($user_id)`, `get_learner_subscription_summary($user_id)`, and `get_learner_access_counts($user_id)`;
  - `Home::my_access()`;
  - My Courses uses prepared learner access items and includes active legacy enrolments plus active direct YounGo/manual course access rows;
  - My Courses intentionally does not auto-list every subscription-eligible course merely because a subscription is active;
  - My Access shows visibility-only subscription/access summaries and links to course browsing only;
  - profile menu includes My Access;
  - `reload_my_courses.php` is aligned with prepared access items;
  - `scripts/phase_2/youngo_phase_2p_learner_access_visibility_diagnostic.php` is read-only and passes.
- Phase 2P QA verified temporary manual course grant visibility, duplicate course grant rejection, course revocation, temporary manual subscription summary visibility, duplicate subscription rejection, subscription revocation, GET-only cart boundary, no checkout/payment/order/coupon/enrol/progress side effects, cleanup DB restore from `D:\Work\YounGo\backups\youngo_school (16).sql`, and final clean diagnostics.
- Phase 2Q curated QA learner baseline setup is completed locally:
  - pre-setup backup: `D:\Work\YounGo\backups\youngo_school (17).sql`;
  - curated post-setup backup: `D:\Work\YounGo\backups\youngo_school_qa_baseline_after_phase_2q2.sql`;
  - dedicated QA learner user `8`, `qa.learner@youngo.local`, name `YounGo QA Learner`, `role_id = 2`, active, `is_instructor = 0`;
  - password must never be documented, printed, committed, stored, or reused from Root Admin; the project owner must provide it when learner-authenticated QA is needed;
  - Root Admin was not modified, users `2`, `5`, `6`, and `7` were not modified, existing user `2` / course `6` enrolment remains, entitlement tables remain empty, and no checkout/payment/order/coupon/enrol/progress rows were created.
- Phase 2Q.3 learner-authenticated browser QA is completed locally for implemented Phase 2O/2P learner surfaces using QA learner user `8`:
  - baseline no-access My Courses and no-subscription My Access passed;
  - course `1` no-access state and course `9` checkout-not-ready state passed, with no legacy Buy Now/Add to cart exposure for course `9`;
  - temporary manual course grant visibility passed across My Courses, My Access summary, course listing/detail active access, normal learner lesson access, and review-area visibility without submitting a review;
  - duplicate course grant rejection and course grant revocation passed;
  - temporary manual subscription visibility passed across My Access, course listing/detail subscription access, and My Courses no catalog flooding;
  - duplicate subscription rejection and subscription revocation passed;
  - GET-only cart boundary passed;
  - restore from `D:\Work\YounGo\backups\youngo_school_qa_baseline_after_phase_2q2.sql` removed temporary QA rows and final diagnostics passed.
- Phase 2Q.3 temporary rows before restore were `watch_histories = 1`, `youngo_course_access = 1`, `youngo_user_subscriptions = 1`, and `youngo_manual_grants = 2`; after restore, `users = 6`, `course = 7`, `enrol = 1`, `payment = 0`, `watch_histories = 0`, `watched_duration = 0`, `youngo_course_access = 0`, `youngo_user_subscriptions = 0`, `youngo_manual_grants = 0`, `youngo_checkout_orders = 0`, `youngo_coupon_usages = 0`, `youngo_coupon_subscription_plans = 0`, `youngo_coupon_courses = 0`, `youngo_subscription_plans = 3`, and `youngo_subscription_plan_audit_log = 3`.
- Phase 2R read-only admin/user entitlement summaries are implemented and authenticated-QA tested locally:
  - read-only admin summary methods in `Youngo_entitlement_model`;
  - user edit YounGo entitlement summary card;
  - course edit YounGo course access summary card;
  - summary card visibility guarded by `grant_manual_access`;
  - Manual Grants filtered links by `user_id` and `course_id`;
  - `scripts/phase_2/youngo_phase_2r_admin_entitlement_summary_diagnostic.php`.
- Phase 2R summaries are read-only: they separate legacy enrolments from active YounGo course access, do not treat subscription eligibility as enrolment, show active user subscription status/count where applicable, show recent manual grants safely, and do not add grant/revoke controls outside Manual Grants.
- Phase 2R authenticated QA verified Root Admin admin rendering, baseline user 8/course 1 summaries, temporary manual course grant summary updates, duplicate course grant rejection, course grant revocation summary updates, temporary manual subscription summary updates, duplicate subscription rejection, subscription revocation summary updates, Manual Grants filtered links, GET-only cart boundary, restore from `D:\Work\YounGo\backups\youngo_school_qa_baseline_after_phase_2q2.sql`, and final clean diagnostics.
- Phase 2R included a QA-blocking user edit rendering fix: missing/empty social/payment key arrays are defaulted safely. Do not document or expose payment key values or credential data.
- Phase 2S YounGo CTA boundary fixes are implemented, reviewed, and manually validated locally:
  - `Home::get_enrolled_to_free_course()` blocks legacy free-enrol creation for YounGo-managed modes `subscription_only`, `subscription_and_purchase`, and `purchase_only`;
  - blocked YounGo-managed courses redirect safely back to course detail with access-managed messaging;
  - legacy free-course enrol behavior remains available for non-YounGo-controlled courses;
  - course detail, course cards, and wishlist views no longer expose legacy Enroll Now, Add to cart, or Buy Now CTAs for YounGo-managed no-access courses;
  - empty wishlist wording no longer directs users to free enrol/cart shortcuts;
  - `scripts/phase_2/youngo_phase_2s_route_cta_boundary_diagnostic.php` is read-only and passes.
- Phase 2S manual validation used QA learner user `8`: course `1` showed access-managed messaging and no legacy CTA, direct `/home/get_enrolled_to_free_course/1` redirected safely to course detail without creating a user 8 enrol row, global `enrol` remained `1`, and user 8 enrol rows remained `0`.
- Course `9` remains the paid subscription-only checkout-not-ready fixture and showed subscription checkout-not-ready messaging with no Buy Now/Add to cart. Course listing showed course `1` access-managed and course `9` subscription-not-ready.
- PDF-specific browser QA was skipped to avoid broadening scope; optional PDF/mobile/API QA remains future work.
- Current QA fixtures: QA learner user `8` as clean no-access learner; user `2` plus course `6` as legacy enrol compatibility fixture; course `1` as manual-grant / lesson-safe QA candidate; course `9` as paid subscription-only checkout-not-ready CTA candidate; plan `1` Monthly EGP as manual subscription QA candidate.
- Current local post-cleanup entitlement write state is empty: `youngo_course_access = 0`, `youngo_user_subscriptions = 0`, `youngo_manual_grants = 0`, `youngo_checkout_orders = 0`, `youngo_coupon_usages = 0`, `payment = 0`, `watch_histories = 0`, `watched_duration = 0`; `enrol = 1`.
- Legacy gateway currencies remain mixed (`paypal_currency`, `stripe_currency`, `razorpay_currency` still USD; others mixed) and are deferred to the payment/Paymob phase.
- `enroll_status()`, payment/cart/checkout/invoice behavior, roles/permissions behavior, progress write endpoints, and broader API/mobile gates are not replaced.
- Future learner-authenticated QA should use the Phase 2Q QA learner baseline and owner-provided QA learner password, then restore from the curated backup after temporary grants/subscriptions.
- Checkout/order issuance, coupon scope improvements, Paymob/payment integration, production subscription purchase flow, Arabic/localization, mobile/API entitlement alignment, and course data rebuild remain not implemented.
- Phase 2V.1 role assignment toggles now exist; broader role/capability administration UX and remaining instructor assignment UX remain later work.

---

## 15. Definition of Success

The system is ready for client testing when:

- The client logs in using a Restricted Admin account.
- The client creates a course without errors.
- The client uploads a thumbnail.
- The client adds a section.
- The client adds a lesson.
- The client activates the course.
- The course appears in the courses listing.
- The course details page displays the data and image correctly.
- There are no broken images.
- There are no skeleton pages in the client test path.
- The client cannot access dangerous settings.
- A backup exists before testing.
- A clear test guide is available.

---

## 16. Current Status After Revising the Plan

```text
Public Course Discovery: Ready

Course Details: Ready

Homepage CMS: Ready

Authentication Entry: Ready

Core LMS Authoring: Available; local course creation smoke test passed; server smoke test pending

Course Media Compatibility: YounGo theme config added; local upload compatibility checked; server smoke test pending

Server Deployment: Pending server-side DB R2 import, media ZIP upload, configuration, and smoke test

Student Account Experience: Phase 1 baseline pages completed; broader dashboard shell may still be expanded later

Instructor Experience: Functional with the legacy interface

Phase 2 Access/Subscription/Role Architecture: Read-only entitlement helper, presentation integrations, controlled `Home::lesson()`/`Files::index()` web hard gates, capability foundation, Course Add/Edit access settings, Subscription Plan Management, Phase 2L archive/audit schema, EGP system-currency alignment, seeded subscription placeholder EGP correction, Phase 2M Shared Entitlement Write Service foundation, Phase 2N Manual Grants dashboard/UI, Phase 2O learner-facing entitlement visibility/enforcement alignment, Phase 2P My Courses / My Access learner visibility, Phase 2Q curated QA learner baseline and learner-authenticated browser QA, Phase 2R read-only admin/user entitlement summaries, Phase 2S CTA boundary fixes, Phase 2U.3 localization translation schema foundation, Phase 2U.4 canonical Arabic UI phrase support, Phase 2U.5.2 translation model/helper foundation, Phase 2U.5.3 category/subcategory bilingual form support, Phase 2U.5.4 course add/edit bilingual form support, Phase 2U.5.5 section/lesson bilingual form support, Phase 2U.6.2 frontend language context helpers, Phase 2U.6.3 Arabic public route aliases, Phase 2U.6.4 translation-aware frontend content shaping, Phase 2U.6.5 language switcher/RTL shell rendering, Phase 2V.0 Roles & Permissions Audit, and Phase 2V.1 Role Assignment Management completed locally. Currency readiness is clean for subscription plan definitions, Manual Grants admin access foundation is complete, scoped learner access surfaces are aligned, learner account visibility exists for active legacy enrolments/direct YounGo course access plus visibility-only subscription summaries, learner-authenticated QA has passed for the implemented Phase 2O/2P surfaces using QA learner user 8, admin user/course edit summaries provide read-only entitlement visibility for admins with `grant_manual_access`, YounGo-managed no-access courses are protected from legacy free-enrol/cart/buy CTA shortcuts, canonical content IDs now have additive YounGo translation tables seeded with English rows, canonical Arabic UI phrase data now exists using `language.arabic` plus `application/language/arabic.json` while English remains default, `Youngo_translation_model` centralizes bilingual content reads/fallbacks/future upserts/slug helpers/missing-translation summaries using canonical `english`/`arabic` codes, category/subcategory dashboard add/edit forms collect optional Arabic and primary English translation fields while keeping canonical category fields synced from English, course add/edit forms now collect English canonical plus optional Arabic course content fields while preserving canonical course compatibility, section/lesson add/edit forms now collect English canonical plus optional Arabic section titles, lesson titles/summaries, and text lesson body content while preserving shared media/type/course/section fields, and safe public frontend course/category/section/lesson display data is now translation-aware for integrated YounGo surfaces. Phase 2U.6.2 added frontend language/URL helpers that normalize to `english`/`arabic`, preserve query strings, exclude protected route families, and provide html lang/dir values. Phase 2U.6.3 added safe public Arabic aliases while preserving English unprefixed canonical URLs and adding no `/en` routes. Phase 2U.6.4 added `application/helpers/youngo_frontend_content_helper.php`, uses `Youngo_translation_model` and the frontend language context helper, overlays only whitelisted display fields, stores original canonical values in safe `youngo_canonical_*` metadata, and preserves IDs, slugs/link identity, price, discount, currency, access modes, media, instructor, category, level, progress, entitlement/access fields, CTA state, wishlist state, checkout/payment fields, lesson type, duration, attachment, video URL, and player/PDF route behavior. Its fallback order is Arabic route = Arabic translation -> English translation -> canonical LMS field, and English route = English translation -> canonical LMS field. Phase 2U.6.5 added route-derived frontend `html lang`/`dir`, body language/direction metadata, a helper-generated `EN | عربي` switcher that preserves query strings and never generates `/en`, and minimal YounGo-scoped RTL header/nav CSS. Course shortcut remains English-oriented with Arabic completion deferred to full edit. Course `language_made_in` is course-content metadata with valid marker values `english`, `arabic`, and `arabic_translated`; translation tables still use only `english` and `arabic`, and `arabic_translated` must not be stored in `youngo_course_translations.language_code` or used as route/UI language. Phase 2U.5.4 also fixed a course edit HTTP 500 / partial blank page, aligned subscription-only versus purchase-mode pricing field UX, preserved existing edit price values when disabled fields are not posted, and normalized readable EGP display without changing DB currency/price values or payment/checkout/Paymob behavior. Phase 2V.1 added YounGo -> Role Assignments at `/admin/youngo/role-assignments`, guarded by `manage_roles`, with Root Admin, Admin, Content, Course, and Instructor toggles. The approved authority model is Root Admin = developer/system owner/highest authority, Admin = client/operational owner, and Content/Course/Instructor = scoped operational roles. Root Admin remains read-only and protected from everyone else; Admin can manage non-root roles but cannot modify Root Admin; Content/Course/Instructor cannot manage roles unless explicitly granted `manage_roles`; Admin is mutually exclusive with Content/Course/Instructor; and Content + Course is the practical Content & Course Manager state. Phase 2V.1 intentionally bridges legacy `users.role_id`, `users.is_instructor`, `permissions.permissions`, and `check_permission()` behavior with YounGo `youngo_roles`, `youngo_capabilities`, `youngo_role_capabilities`, `youngo_user_roles`, and the capability helper because legacy course/category pages still depend on `check_permission('course')` and `check_permission('category')`. The reversible seed files `database/phase_2/youngo_phase_2v1_admin_manage_roles_up.sql` and `database/phase_2/youngo_phase_2v1_admin_manage_roles_down.sql` map only YounGo `admin` to `manage_roles`; scoped roles do not receive `manage_roles`; down removes only `admin -> manage_roles`. Phase 2V.1 runtime QA used a temporary admin account, not Root Admin, with backup `D:\Work\YounGo\backups\youngo_school_before_phase_2v1_admin_manage_roles_alignment_2026_07_17_061234.sql`; QA verified Admin access to Role Assignments, non-root role updates, Root Admin read-only UI, `protected_root_admin` rejection for tampered Root Admin update, Course-only denial from Role Assignments, scoped roles without `manage_roles`, Admin mutual exclusion, restored temporary QA assignments, reapplied required `admin -> manage_roles`, and clean protected payment/checkout/entitlement/manual-grant/coupon rows. The diagnostic is `scripts/phase_2/youngo_phase_2v_role_assignment_diagnostic.php`. Checkout/order issuance, coupons, Paymob, user subscription purchase flow, frontend phrase conversion/polish, course data rebuild, My Courses/Wishlist AJAX language propagation, full RTL visual polish, lesson/player/PDF Arabic aliases, optional PDF/mobile/API entitlement QA, and broader mobile/API entitlement UX remain pending.

Phase 2U.5.5 Section/Lesson Bilingual Form Support is implemented, focused-reviewed, runtime-QA tested, restored, and committed in commit `f3368fc` (`Add YounGo section lesson bilingual form support`). Section add/edit now collect `english_title` and optional RTL `arabic_title`, sync canonical `section.title` from English, upsert English translations, and only create Arabic translations when Arabic title is non-empty. Lesson add/edit now collect `english_title`, optional `english_summary`, optional RTL `arabic_title`, optional RTL `arabic_summary`, and text lesson `english_text_content` / optional RTL `arabic_text_content`; canonical lesson title, summary, and text body sync from English, Arabic lesson translations are optional/non-empty only, and non-text media/shared fields remain shared. `Crud_model::sync_legacy_lesson_post_fields()` keeps legacy media handlers supplied with `title`, `summary`, and `text_description` from English fields before Academy Cloud/video/media handlers run. Runtime QA used backup `D:\Work\YounGo\backups\youngo_school_before_phase_2u5_5_section_lesson_bilingual_qa_2026_07_18_194034.sql`, restored successfully, removed temporary Course Manager access setup and temporary section/lesson data, matched pre/post counts, and kept protected payment/access/checkout/coupon rows clean. The diagnostic is `scripts/phase_2/youngo_phase_2u5_section_lesson_bilingual_forms_diagnostic.php`.

Phase 2U.6.3 Arabic Public Route Aliases is implemented, focused-reviewed, diagnostics-tested, committed, and pushed in commit `4b773b2` (`Add YounGo Arabic public route aliases`). English canonical frontend URLs remain unprefixed. Arabic canonical public frontend URLs use `/ar/...`, and `/en` must not become canonical. Added aliases are `/ar -> home/index`, `/ar/courses -> home/courses`, `/ar/courses/{page} -> home/courses`, `/ar/course/{slug}/{id} -> home/course/$1/$2`, `/ar/search -> home/search`, `/ar/search/{query} -> home/search/$1`, `/ar/my-courses -> home/my_courses`, `/ar/my-access -> home/my_access`, `/ar/wishlist -> home/my_wishlist`, `/ar/login -> login/index`, and `/ar/sign-up -> sign_up/index`. No `/en` routes, admin/dashboard aliases, checkout/payment/cart/coupon write aliases, API/cron aliases, frontend translated content rendering, RTL shell rendering, language switcher UI, content translation shaping, session/cookie/settings writes, or phrase writes were added. Lesson/player/PDF aliases remain deferred because `Home::lesson`, `Home::pdf_canvas`, `Home::play_lesson`, mobile lesson helpers, and offline video helpers are gated playback/progress/session surfaces. The diagnostic is `scripts/phase_2/youngo_phase_2u6_arabic_route_alias_diagnostic.php`; focused review tightened existing diagnostics so `routes.php` changes are tolerated only when the diff is Arabic-alias-only. Static route diagnostics and compatibility diagnostics passed; HTTP GET smoke was intentionally skipped.

Phase 2U.6.4 Translation-aware Frontend Content Shaping is implemented, focused-reviewed, runtime-QA tested, restored, committed, and pushed in commit `12fcdae` (`Add YounGo frontend content translation shaping`). It added `application/helpers/youngo_frontend_content_helper.php` and `scripts/phase_2/youngo_phase_2u6_frontend_content_translation_diagnostic.php`, and integrated shaping into `Home.php`, `common_helper.php`, YounGo course listing/detail/wishlist views, and Phase 2U.6 diagnostics. The helper uses `Youngo_translation_model` plus the frontend language context helper to shape home, courses, search, course detail, my courses, my access, and wishlist initial page data. Arabic fallback is Arabic translation -> English translation -> canonical LMS field; English fallback is English translation -> canonical LMS field. Shaped fields include course `title`, `short_description`, `description`, `outcomes`, `requirements`, `faqs`, `seo_title`, `meta_keywords`, and `meta_description`, category display names, section titles, lesson title/summary, and text lesson body only for non-player display paths. Canonical IDs, slugs/link identity, filters, price, discount, currency, access mode, media, instructor, category, level, progress, entitlement/access fields, CTA state, wishlist state, checkout/payment fields, lesson type, duration, attachment, video URL, and player/PDF route behavior remain unchanged.

Phase 2U.6.4 runtime QA used backup `D:\Work\YounGo\backups\youngo_school_before_phase_2u6_4_frontend_content_translation_qa_2026_07_18_215217.sql`. Temporary Arabic rows were inserted only into YounGo translation tables for course `1`, category `7`, section `1`, and lesson `1`; no canonical rows changed. Restore removed temporary rows and matched pre/post counts: course `8`, category `12`, section `18`, lesson `36`, course translations total `9` / English `8` / Arabic `1`, category translations total `12` / English `12` / Arabic `0`, section translations total `18` / English `18` / Arabic `0`, lesson translations total `36` / English `36` / Arabic `0`, `ci_sessions = 636`, `youngo_user_roles = 0`, `permissions = 2`, `enrol = 1`, and protected payment/watch/progress/entitlement/checkout/coupon rows `0`. QA confirmed `/`, `/home/courses`, and `/home/course/scratch-coding-for-young-creators/1` remain English/canonical; `/ar`, `/ar/courses`, and `/ar/course/scratch-coding-for-young-creators/1` display Arabic shaped values where translations exist and fall back safely; search Arabic aliases resolve; `/ar/wishlist`, `/ar/my-courses`, and `/ar/my-access` resolve without fatal/404 in unauthenticated safe GET checks; and excluded `/ar/admin`, `/ar/home/payment`, `/ar/home/checkout`, `/ar/home/shopping_cart`, `/ar/home/apply_coupon`, `/ar/api`, and `/ar/cron` render the app 404 page. Current working English routes for course/list/search/detail surfaces remain `/home/...`; direct `/courses`, `/course/...`, `/search`, and `/wishlist` are not current English routes.

The Phase 2U.6.4 diagnostic is `scripts/phase_2/youngo_phase_2u6_frontend_content_translation_diagnostic.php`. It checks helper functions, `Home.php` integration, `/ar` aliases, no `/en` routes, no new route changes, no payment/checkout/cart/coupon localization, no `arabic_translated` table-language usage, canonical IDs/access/media/progress preservation, read-only sample shaping, and route/language/section-lesson/role/course-category/translation/access diagnostic compatibility.

Phase 2U.6.5 Language Switcher and RTL Shell Rendering is implemented, focused-reviewed, runtime-QA tested, restored, committed, and pushed in commit `8321e91` (`Add YounGo language switcher and RTL shell`). It updated `application/helpers/youngo_frontend_language_helper.php`, `application/views/frontend/youngo/header.php`, `application/views/frontend/youngo/index.php`, `assets/frontend/youngo/css/youngo.css`, `scripts/phase_2/youngo_phase_2u5_section_lesson_bilingual_forms_diagnostic.php`, `scripts/phase_2/youngo_phase_2u6_arabic_route_alias_diagnostic.php`, `scripts/phase_2/youngo_phase_2u6_frontend_content_translation_diagnostic.php`, `scripts/phase_2/youngo_phase_2u6_frontend_language_context_diagnostic.php`, and `scripts/phase_2/youngo_phase_2u6_language_switcher_rtl_diagnostic.php`.

The YounGo frontend shell now uses route-derived language helper values. English routes render `html lang="en" dir="ltr"` and Arabic `/ar` routes render `html lang="ar" dir="rtl"`. The frontend body adds escaped `youngo-lang-english` / `youngo-lang-arabic`, `youngo-dir-ltr` / `youngo-dir-rtl`, `data-youngo-language`, and `data-youngo-dir` metadata while preserving existing body classes. The admin/backend shell remains unaffected.

The YounGo frontend header now includes an `EN | عربي` language switcher with active state and `aria-current`. Switcher URLs are helper-generated, preserve query strings, never generate `/en`, never show/link `arabic_translated`, and map the current working English route reality to Arabic aliases: `/ -> /ar`, `/home/courses -> /ar/courses`, `/ar/courses -> /home/courses`, `/home/course/{slug}/{id} -> /ar/course/{slug}/{id}`, and `/ar/course/{slug}/{id} -> /home/course/{slug}/{id}`. Focused review fixed a subdirectory URL risk by making `youngo_frontend_current_uri_string_with_query()` prefer CodeIgniter `$CI->uri->uri_string()` over raw `REQUEST_URI`, with `REQUEST_URI` as fallback only.

Minimal YounGo-scoped RTL CSS was added in `assets/frontend/youngo/css/youngo.css` for header/nav shell behavior. The language switcher remains readable/LTR, and full visual RTL polish was not attempted.

Phase 2U.6.5 runtime QA used backup `D:\Work\YounGo\backups\youngo_school_before_phase_2u6_5_language_switcher_rtl_qa_2026_07_18_224356.sql`. No temporary Arabic rows were needed. QA confirmed `/` resolved 200 with `lang="en"` and `dir="ltr"`; `/ar` resolved 200 with `lang="ar"` and `dir="rtl"`; body metadata was correct; switcher active state worked; `/` switched to `/ar`; `/ar` switched to `/`; `/home/courses` and `/ar/courses` resolved 200; `/home/courses?page=2` switched to `/ar/courses?page=2` and back; `/home/course/scratch-coding-for-young-creators/1` and `/ar/course/scratch-coding-for-young-creators/1` resolved 200 with slug/id preserved; `/home/search?query=Scratch` and `/ar/search?query=Scratch` resolved 200 with query preservation; `/ar/login` and `/ar/sign-up` resolved 200 using the YounGo shell with Arabic RTL metadata and switcher; `/ar/wishlist` resolved 200; and `/ar/my-courses` plus `/ar/my-access` kept normal unauthenticated refresh redirect behavior. Excluded `/ar/admin`, `/ar/home/payment`, `/ar/home/checkout`, `/ar/home/shopping_cart`, `/ar/home/apply_coupon`, `/ar/api`, and `/ar/cron` rendered the app 404 page. No write endpoints were called. Restore succeeded and pre/post counts matched exactly, including `ci_sessions = 636`, course `8`, category `12`, section `18`, lesson `36`, course translations total `9` / English `8` / Arabic `1`, category Arabic `0`, section Arabic `0`, lesson Arabic `0`, `enrol = 1`, and protected YounGo entitlement/payment/checkout/coupon/progress tables `0`.

The Phase 2U.6.5 diagnostic is `scripts/phase_2/youngo_phase_2u6_language_switcher_rtl_diagnostic.php`. It checks shell html lang/dir helper usage, English `en/ltr` and Arabic `ar/rtl` mapping, body metadata, switcher existence, helper-generated URLs, query preservation, no `/en` links/routes, no route additions, no session/cookie/settings language writes, no DB/phrase writes, no checkout/payment/cart/coupon localization, admin shell boundaries, and compatibility with content translation and Arabic route diagnostics. Known limitations: runtime QA used HTTP/HTML response inspection rather than pixel-level browser screenshot QA; My Courses/Wishlist AJAX language propagation remains deferred; phrase polish remains deferred; full RTL visual polish remains deferred; translation shaping currently uses per-row fallback reads that may need batching later; hreflang/canonical SEO remains deferred; lesson/player/PDF Arabic aliases remain deferred; and checkout/payment/cart/coupon localization remains deferred.

Payments: Not ready for real use

Production Security: Requires review
```

## Current Next Steps

1. Phase 2U.6.6 - Frontend phrase conversion/inventory.
2. Phase 2U.6.7 - Controlled frontend localization QA and diagnostics.
3. Phase 2U.7 - Bilingual QA and Arabic phrase polish QA before public Arabic launch.
4. Phase 2T - Course Data Rebuild after bilingual infrastructure is ready, unless the owner explicitly accepts an English-only interim rebuild.
5. Checkout/order/coupon planning.
6. Paymob integration and gateway currency alignment after checkout/order/coupon foundation.
7. Optional PDF/mobile/API entitlement QA or alignment, if approved.
8. Phase 2V.2 - Role assignment docs/UX polish or QA follow-up, if needed and not blocking localization.

Immediate sequencing rules:

- New Phase 2 admin pages must have minimal capability enforcement first.
- Course access configuration must precede course purchase checkout.
- Subscription plan management must precede subscription checkout.
- Subscription plan currency readiness must stay clean before checkout/Paymob. The Phase 2L diagnostic must report `settings.system_currency = EGP`, zero non-EGP subscription plans, and commercial readiness unblocked before real commercial subscription activation.
- The seeded plan prices are temporary local placeholders, not final commercial pricing. Do not activate or make plans purchasable until final prices are approved and the owner explicitly approves activation through the dashboard flow.
- Manual grants and checkout must use `Youngo_entitlement_write_model`; do not create real grants/access/subscriptions through manual SQL.
- Manual Grants UI already enforces `grant_manual_access`; future grant/access controllers must do the same before calling the write service.
- Manual grant revocation must preserve progress, enrolment, payment, watch history, subscription plan, user, and checkout/order rows.
- Checkout issuance methods are currently stubs and must not be treated as completed order/access issuance.
- Do not route YounGo-managed courses into legacy free enrol, Buy Now/Add to cart, legacy coupons, or legacy checkout as shortcuts to access; formal checkout/order/access issuance must be implemented first.
- Run `youngo_phase_2u5_translation_model_diagnostic.php`, `youngo_phase_2u4_arabic_phrase_diagnostic.php`, `youngo_phase_2u3_localization_schema_diagnostic.php`, `youngo_phase_2j_capability_diagnostic.php`, `youngo_phase_2l_subscription_plan_diagnostic.php`, `youngo_phase_2m_entitlement_write_diagnostic.php`, `youngo_phase_2p_learner_access_visibility_diagnostic.php`, `youngo_phase_2r_admin_entitlement_summary_diagnostic.php`, and `youngo_phase_2s_route_cta_boundary_diagnostic.php` before localization/subscription/manual-grant/entitlement/learner-access visibility/admin-summary/CTA-boundary work.
- Future learner-authenticated browser QA should use the Phase 2Q QA learner baseline and owner-provided QA learner password; do not print, store, commit, or reuse that password.
- The curated QA learner baseline backup is `D:\Work\YounGo\backups\youngo_school_qa_baseline_after_phase_2q2.sql`.

## Strategic Decision

From this point forward:

> Do not rebuild an existing feature unless it has been proven that reusing it is impractical.

Every new phase begins with the question:

> Where does this feature already exist inside Academy LMS, and how can it serve YounGo with the fewest changes and the least duplication?

This is the strongest, fastest, and lowest-risk plan for the project.
