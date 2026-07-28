# YounGo Phase 2D Multi-Role / Capability Compatibility Plan

## 1. Executive Summary

This document records the approved Phase 2D plan for adding YounGo multi-role / capability support while preserving existing Academy LMS role, instructor, permission, admin, and user behavior.

It is a planning document only. It does not implement role changes, capability checks, database schema, migrations, SQL, source code, controllers, models, helpers, views, admin screens, or account role toggles.

Phase 2D should add a YounGo multi-role/capability layer beside the existing Academy LMS permission model. The safest direction is additive:

- Keep `users.role_id`.
- Keep `users.is_instructor`.
- Keep the `permissions` table.
- Keep `has_permission()`.
- Keep `check_permission()`.
- Keep `is_root_admin()`.
- Keep current admin and user session behavior.
- Keep current instructor assignment behavior.

YounGo capability checks should be used only for new Phase 2 pages and features first. Existing legacy permission gates should not be replaced initially.

---

## 2. Current Role / Permission Flow Map

Current Academy LMS behavior:

- Admin versus user is controlled by `users.role_id`.
- `role_id = 1` logs into the admin area through `admin_login`.
- `role_id = 2` logs into the user/learner area through `user_login`.
- Instructor is not a separate database role.
- Instructor status is controlled by `users.is_instructor = 1`.
- Instructor authoring routes are mainly under `application/controllers/User.php`.
- Root Admin is inferred by no matching row in `permissions` for that admin id.
- Restricted admins use JSON module keys in `permissions.permissions`.
- Admin navigation and controller access use `has_permission()` and `check_permission()`.
- Course instructor assignment uses `course.creator`, comma-separated `course.user_id`, and `course.multi_instructor`.
- `Crud_model::is_course_instructor()` is the key compatibility check for assigned course instructors.

---

## 3. Existing Role Decision Points

Important current files and functions:

```text
application/helpers/common_helper.php
application/helpers/user_helper.php
application/controllers/Admin.php
application/controllers/User.php
application/controllers/Home.php
application/models/User_model.php
application/models/Crud_model.php
application/views/backend/admin/navigation.php
application/views/backend/admin/admin_permission.php
application/views/backend/admin/admins.php
application/views/backend/header.php
```

Important current checks:

- `has_permission($permission_for, $admin_id = '')`
- `check_permission($permission_for)`
- `is_root_admin($admin_id = '')`
- `get_user_role()`
- `User_model::add_user()`
- `User_model::assign_permission()`
- `User_model::get_admins()`
- `User_model::get_instructor_list()`
- `Crud_model::is_course_instructor()`
- `Crud_model::get_courses_by_instructor_id()`
- `User::instructor_authorization()`
- `User::is_the_course_belongs_to_current_instructor()`

Legacy permission module keys currently include:

```text
category
course
user
instructor
student
enrolment
revenue
messaging
blog
addon
theme
settings
coupon
academy_cloud
newsletter
contact
```

---

## 4. Current Risks

Current compatibility risks:

- Root Admin is inferred by absence of a `permissions` row.
- Admin users and `permissions` rows are not necessarily one-to-one.
- `users.role_id` supports only Admin/User.
- `users.is_instructor` is a flag, not a role.
- New admin creation currently sets `role_id = 1` and `is_instructor = 1`.
- Legacy permission keys are broad and do not map cleanly to YounGo role bundles.
- Some legacy permission names may be inconsistent across navigation and controllers.
- Instructor assignment uses comma-separated `course.user_id`.
- Existing restricted client admin uses `role_id = 1` with `permissions = ["course"]`.
- The restricted client admin must remain course-limited and must not be broadened accidentally.
- Replacing `has_permission()` too early could cause backend lockout, privilege escalation, or broken admin navigation.

---

## 5. Recommended Multi-Role / Capability Model

Add a YounGo capability layer beside the legacy model.

Target behavior:

- Accounts may hold multiple YounGo roles.
- Learner/User capability is automatic for all accounts.
- Learner/User should not be stored as a normal toggle.
- Root Admin is protected and is not a normal toggle.
- Instructor can also have Course Manager or Content Manager roles if toggled.
- Admin can grant manual access, including to themselves.
- Existing Academy LMS role and permission behavior remains available during compatibility.

Recommended future helper/model names:

```text
youngo_is_root_admin($user_id)
youngo_user_has_role($user_id, $role_key)
youngo_user_has_capability($user_id, $capability_key, $context = [])
youngo_require_capability($capability_key, $context = [])
youngo_get_user_capabilities($user_id)
youngo_get_assignable_roles($actor_user_id, $target_user_id)
```

These functions should be introduced read-only first and used only by new Phase 2 features until regression testing approves wider use.

---

## 6. Recommended Tables and MVP / Later Split

Phase 2B already defines the additive schema direction. Phase 2D confirms the role/capability subset.

Required for MVP:

### `youngo_roles`

Purpose:

- Stores YounGo role bundles without replacing the legacy `role` table.

Initial role keys:

- `admin`
- `content_manager`
- `course_manager`
- `instructor`

### `youngo_capabilities`

Purpose:

- Stores atomic capability keys for the YounGo compatibility layer.

### `youngo_role_capabilities`

Purpose:

- Maps YounGo role bundles to capabilities.

### `youngo_user_roles`

Purpose:

- Allows one account to hold multiple YounGo roles.

Useful later:

### `youngo_user_capabilities`

Purpose:

- Allows direct per-user capability overrides when role bundles are not enough.

Defer unless role bundles are insufficient.

### `youngo_course_instructor_assignments`

Purpose:

- Normalizes instructor-course assignment if the existing comma-separated `course.user_id` behavior becomes too risky.

Defer for MVP. Keep `course.creator`, `course.user_id`, `course.multi_instructor`, and `Crud_model::is_course_instructor()` for compatibility.

Do not alter initially:

- `users`
- `role`
- `permissions`
- existing session behavior
- existing instructor assignment fields

---

## 7. Role Definitions and Capability Matrix

Recommended capability keys:

```text
manage_homepage_content
manage_static_content
manage_media
manage_courses
manage_lessons
publish_courses
manage_course_categories
assign_existing_instructors
manage_assigned_course_lessons
grant_manual_access
view_payments
manage_users
manage_instructors
manage_system_settings
manage_roles
manage_subscriptions
manage_coupons
view_reports
```

### Root Admin

Root Admin is protected and not a normal role toggle.

Allowed:

- Everything.

Forbidden:

- Casual downgrade.
- Casual deletion.
- Normal role toggle modification.

### Admin

Purpose:

- Broad operational admin role without protected root power.

Allowed:

- Grant manual access, including to themselves.
- Manage ordinary users if assigned `manage_users`.
- Manage instructors if assigned `manage_instructors`.
- Manage subscriptions and coupons when those Phase 2 features exist.
- View payments/reports if assigned.

Forbidden:

- Modify or downgrade Root Admin.
- Touch core system settings unless explicitly allowed later.
- Bypass root protections.

### Content Manager

Purpose:

- Manage YounGo website content and content-related media.

Allowed capabilities:

- `manage_homepage_content`
- `manage_static_content`
- `manage_media`

Forbidden:

- Payments.
- Users.
- Instructor account management.
- System settings.
- Role management.
- Manual grants unless separately approved later.

### Course Manager

Purpose:

- Manage course content and course publishing.

Allowed capabilities:

- `manage_courses`
- `manage_lessons`
- `publish_courses`
- `manage_course_categories`
- `assign_existing_instructors`

Forbidden:

- Payments.
- System settings.
- Role management.
- Creating instructor accounts.
- Deleting instructor accounts.
- Modifying Root Admin.

### Instructor

Purpose:

- Manage lessons only for assigned courses.

Allowed capabilities:

- `manage_assigned_course_lessons`

Forbidden:

- All-course access unless also Course Manager.
- Payments.
- Users.
- System settings.
- Role management.

### User / Learner

Purpose:

- Student account.

Rules:

- Automatic for all accounts.
- Parent may operate the account, but the account belongs to the student.
- Learner access comes through subscription, course purchase, manual grant, or legacy enrolment compatibility.
- Learner should not be a normal stored toggle.

---

## 8. Compatibility With Existing Permissions

Recommended approach: hybrid gradual migration.

Initial behavior:

- Keep existing `has_permission()` as the primary gate for legacy admin pages.
- Keep existing `check_permission()` as the primary redirect/enforcement function for legacy admin pages.
- Keep existing `permissions` JSON module behavior.
- Add YounGo capability checks only for new Phase 2 pages/features.
- Do not wrap or replace `has_permission()` initially.

Conceptual legacy-to-YounGo mapping for later analysis:

| Legacy permission | Possible YounGo capability |
| --- | --- |
| `course` | `manage_courses`, `manage_lessons`, `publish_courses` |
| `category` | `manage_course_categories` |
| `coupon` | `manage_coupons` |
| `enrolment` | legacy enrolment management; not the same as manual grants |
| `revenue` | `view_payments` |
| `student` | `manage_users` |
| `user` | broad user area; must be reviewed before mapping |
| `instructor` | `manage_instructors` |
| `blog` | `manage_static_content` or content-specific capability |
| `contact` | `manage_static_content` |
| `newsletter` | content/marketing capability if needed |
| `settings` | root/system-level unless explicitly approved |
| `theme` | root/system-level unless explicitly approved |
| `addon` | root/system-level unless explicitly approved |
| `academy_cloud` | root/system-level unless explicitly approved |

Do not automatically translate every existing permission into broad YounGo power.

---

## 9. Root Admin Protection Design

Compatibility-phase root detection:

- Treat existing `is_root_admin($admin_id)` as authoritative.
- Defensively require admin context when evaluating root behavior.
- Do not create normal role toggles for Root Admin.

Protection rules:

- Normal Admin cannot edit Root Admin.
- Normal Admin cannot downgrade Root Admin.
- Normal Admin cannot delete Root Admin.
- Normal Admin cannot disable Root Admin.
- Normal Admin cannot assign or revoke Root Admin roles/capabilities.
- Root Admin should appear as a protected badge, not editable checkboxes.
- Do not add a `permissions` row for Root Admin during backfill.
- Enforce protection server-side, not only in UI.

Later audit should record attempted and successful root/admin changes.

---

## 10. Admin Behavior Design

Admin should have broad operational capabilities without protected root power.

Rules:

- Admin can grant manual access, including to themselves.
- Admin can manage ordinary users and instructors only when assigned those capabilities.
- Admin cannot modify or downgrade Root Admin.
- Admin cannot touch core system settings unless explicitly approved later or acting as Root Admin.
- Admin should continue using `role_id = 1` for legacy admin login compatibility.
- Restricted admins must remain restricted by existing `permissions`.

Backfill rules:

- Do not backfill all `role_id = 1` users blindly into broad YounGo Admin power.
- Review existing `permissions` rows before assigning YounGo roles.
- Preserve the known restricted client admin as course-limited.

---

## 11. Content Manager Behavior Design

Content Manager manages content and content media only.

Allowed:

- Homepage content.
- Static pages.
- Blog/news content if available.
- FAQ/testimonials/content sections if available.
- Content-related images and media.

Forbidden:

- Payments.
- Users management.
- Instructor account management.
- System settings.
- Role management.
- Manual grants unless later explicitly approved.

Implementation note:

- Reused legacy content screens must be checked for hidden settings, user, payment, or system operations before being exposed to Content Manager.

---

## 12. Course Manager Behavior Design

Course Manager manages course content and course assignment.

Allowed:

- Create courses.
- Edit courses.
- Publish courses.
- Manage sections and lessons.
- Manage course categories where required.
- Assign existing active instructors to courses.

Forbidden:

- Create instructor accounts.
- Delete instructor accounts.
- Manage payments.
- Manage system settings.
- Manage roles.
- Modify Root Admin.

Restricted client admin compatibility:

- Existing restricted client admin with `permissions = ["course"]` maps closest to Course Manager.
- It must remain course-limited.
- It must not gain broad Admin privileges during backfill.

Important mitigation:

- Do not reuse instructor account creation/deletion screens for Course Manager.
- Course Manager can only assign existing active instructors to courses.

---

## 13. Instructor Behavior Design

Instructor remains compatible with `users.is_instructor = 1`.

Rules:

- Instructor can add/manage lessons only for assigned courses.
- Instructor assignment continues to use existing course assignment behavior in MVP.
- Instructor does not need approval for lessons on assigned courses.
- Instructor cannot access all courses unless also Course Manager.
- Instructor can also receive Course Manager or Content Manager roles if toggled.
- Instructor authoring should remain compatible with existing `User.php` / `user_login` behavior during the compatibility phase.

Do not move instructors into admin-session behavior in the first implementation pass.

---

## 14. Account Role-Toggle UI Strategy

Later UI should add role toggles to account management after schema and read helpers exist.

Recommended UI behavior:

- Show assignable YounGo roles beside each account.
- Allow toggles for Admin, Content Manager, Course Manager, and Instructor.
- Do not show Learner/User as a normal toggle.
- Show Root Admin as a protected badge only.

Who can toggle roles:

- Root Admin can manage non-root role assignments.
- Normal Admin can manage role assignments only if explicitly given role-management capability later.
- Normal Admin still cannot modify Root Admin.

Safety rules:

- Prevent self-lockout.
- Prevent removing the last protected root/admin path.
- Require server-side checks.
- Require audit records for role changes.

---

## 15. Instructor Assignment Strategy

MVP instructor assignment should keep existing fields and helpers:

- `course.creator`
- `course.user_id`
- `course.multi_instructor`
- `Crud_model::is_course_instructor()`

Admin and Course Manager can assign existing instructors only.

Course Manager assignment rules:

- Select from active users where `is_instructor = 1`.
- Do not create instructor accounts.
- Do not delete instructor accounts.
- Do not assign non-instructors unless Admin or Root Admin first marks the account as instructor through an approved account-management flow.

Later normalization:

- Add `youngo_course_instructor_assignments` only if existing comma-separated `course.user_id` becomes too risky for reporting, auditing, or entitlement checks.
- The entitlement layer should continue using existing instructor detection until a tested migration exists.

---

## 16. Migration / Backfill Strategy

Safe future sequence:

1. Back up database and uploads.
2. Verify production/staging data, not only local DB.
3. Create YounGo role/capability tables.
4. Seed YounGo roles.
5. Seed YounGo capabilities.
6. Seed role-capability mappings.
7. Detect protected Root Admin accounts before assigning any YounGo roles.
8. Keep Root Admin protected and independent of normal role toggles.
9. Backfill existing `is_instructor = 1` users to YounGo Instructor only after review.
10. Backfill restricted admins conservatively based on their existing permission rows.
11. Preserve restricted client admin as course-limited.
12. Keep Learner/User implicit for every account.
13. Do not replace legacy `permissions` gates.
14. Add read-only diagnostics before enabling role writes.
15. Enable new capability checks only on new Phase 2 pages/features.

Rollback direction:

- Before use, new YounGo role/capability tables can be removed only after backup confirmation.
- After test data exists, export all YounGo role/capability/audit tables before rollback.
- Never rollback by altering `users.role_id`, `users.is_instructor`, or `permissions` without explicit backup and approval.

---

## 17. Audit Requirements

Future audit should record:

- Role assignment.
- Role revocation.
- Direct capability grant/revoke if later supported.
- Root/admin changes.
- Failed attempts to modify protected Root Admin.
- Instructor assignment changes.
- Legacy permission changes.
- Manual grant creation/revocation.

Recommended audit fields:

- Actor user id.
- Target user id.
- Action type.
- Old value.
- New value.
- Reason/note if provided.
- Created timestamp.
- IP/session metadata where practical.

---

## 18. Implementation Sequence Recommendation

Recommended later implementation sequence:

1. Add approved schema/migration for YounGo roles/capabilities.
2. Seed roles and capabilities.
3. Add read-only YounGo capability model/helper.
4. Add Root Admin, Admin, and Instructor compatibility mapping inside the helper.
5. Add diagnostics to compare legacy permissions and YounGo capabilities.
6. Use YounGo capability checks only for new Phase 2 pages/features.
7. Add manual grant permission checks using `grant_manual_access`.
8. Add account role toggles after helper behavior is validated.
9. Add Course Manager instructor assignment controls.
10. Regression test legacy admin, instructor, student, purchase, and lesson flows.
11. Only later evaluate wrapping or replacing legacy permission gates.

Do not replace `has_permission()` or `check_permission()` in the first implementation pass.

---

## 19. Regression / Validation Plan

Required validation scenarios:

- Root Admin still has full legacy access.
- Root Admin cannot be downgraded by Normal Admin.
- Restricted client admin remains course-only.
- Normal student cannot access backend.
- Learner access still works for all accounts.
- Instructor can access assigned course authoring only.
- Instructor cannot access unassigned courses.
- Instructor plus Course Manager can manage broader course areas as intended.
- Content Manager can manage content/media.
- Content Manager cannot access users, payments, roles, or settings.
- Course Manager can manage courses, lessons, categories, and publishing.
- Course Manager can assign existing active instructors.
- Course Manager cannot create instructor accounts.
- Course Manager cannot delete instructor accounts.
- Course Manager cannot access payments or system settings.
- Admin can grant manual access, including self-grant.
- Admin cannot modify Root Admin role/status.
- Existing `has_permission()` navigation still behaves as before.
- Existing `enroll_status()` and lesson access remain unchanged.

---

## 20. Files Likely Touched Later

Likely later implementation areas:

```text
application/helpers/common_helper.php
application/helpers/user_helper.php
application/models/User_model.php
application/models/Crud_model.php
application/controllers/Admin.php
application/controllers/User.php
application/views/backend/admin/navigation.php
application/views/backend/admin/admin_permission.php
application/views/backend/admin/admins.php
application/views/backend/header.php
```

Possible new YounGo-scoped implementation files later:

```text
application/helpers/youngo_capability_helper.php
application/models/Youngo_capability_model.php
```

Do not touch these files until implementation is explicitly approved.

---

## 21. Risks And Mitigations

### Risk: Restricted admins get broadened

Mitigation:

- Do not backfill `role_id = 1` users blindly into broad YounGo Admin power.
- Review existing `permissions` rows first.

### Risk: Root Admin lockout or downgrade

Mitigation:

- Keep existing `is_root_admin()` behavior authoritative during compatibility.
- Treat Root Admin as protected and non-toggleable.
- Enforce protection server-side.

### Risk: Course Manager creates/deletes instructor accounts through reused screens

Mitigation:

- Do not reuse instructor account creation/deletion screens for Course Manager.
- Course Manager can only assign existing active instructors to courses.

### Risk: Content Manager gains operational access

Mitigation:

- Do not map Content Manager to broad legacy `user`, `settings`, `revenue`, or `instructor` permissions.
- Use YounGo-specific capability checks on new content tools.

### Risk: Instructor assignment ambiguity from comma-separated `course.user_id`

Mitigation:

- Keep existing helper behavior for MVP.
- Normalize only later if reporting, auditing, or entitlement checks require it.

### Risk: Legacy admin navigation breaks

Mitigation:

- Keep `has_permission()` and `check_permission()` unchanged initially.
- Use YounGo capability checks only for new Phase 2 pages/features.

---

## 22. Open Questions / Implementation-Time Decisions

No owner decision is required before documenting Phase 2D.

Implementation-time decisions to defer:

- Whether Normal Admin can ever receive `manage_system_settings`.
- Whether role toggles live on the existing admin edit page or a new YounGo account roles panel.
- Whether `youngo_user_capabilities` is needed for MVP.
- Whether to normalize instructor assignments before or after Phase 2 MVP.
- Whether legacy permission mapping should stay read-only diagnostics or eventually replace selected legacy gates.

---

## 23. Final Recommendation

Proceed later with an additive YounGo multi-role/capability layer that reads beside legacy roles and permissions. Keep legacy Academy LMS permission gates as the operational authority for existing pages at first. Protect Root Admin, preserve restricted admin behavior, keep instructors compatible with `users.is_instructor`, and introduce YounGo capability checks only on new Phase 2 features until regression testing proves the bridge is safe.
