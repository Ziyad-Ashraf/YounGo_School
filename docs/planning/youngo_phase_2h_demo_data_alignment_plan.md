# YounGo Phase 2H Demo Data Alignment Plan

## Executive Summary

Phase 2H creates reviewable local/demo-only artifacts to align existing demo course/category data with the current Academy LMS course form contract and the YounGo Phase 2 access model.

Phase 2H.2 was applied successfully to the local database only after the user manually created a phpMyAdmin backup:

```text
D:\Work\YounGo\backups\youngo_school (4).sql
```

Applied artifact:

```text
database/phase_2/demo_alignment/youngo_phase_2h_demo_data_alignment_up.sql
```

No source code, users, passwords, sessions, enrol rows, payment rows, `youngo_course_access` rows, `youngo_user_subscriptions` rows, or QA dataset artifacts were modified.

The alignment target is intentionally narrow:

- Add missing subcategories under parent categories `2`, `3`, `4`, and `6`.
- Update demo courses `1-6` so each points to a valid subcategory under its existing parent category.
- Keep course `9`, pricing/free-course flags, and YounGo access fields unchanged for now.

Marker:

```text
YOUNGO_DEMO_ALIGNMENT_2H
```

## Current Findings

Read-only inspection confirmed:

- `category` has no `status` column.
- Existing parent categories:
  - `1` Coding for Kids
  - `2` Science Explorers
  - `3` Creative Arts
  - `4` Reading & Storytelling
  - `5` Math Adventures
  - `6` Life Skills
- Existing subcategories:
  - `7` Scratch Basics under parent `1`
  - `8` Math 1 under parent `5`
- Categories `2`, `3`, `4`, and `6` currently have no subcategories.
- Courses `1-6` have valid `category_id` values but `sub_category_id=0`.
- Courses `1-6` are active, have thumbnails, have sections and text lessons, and are assigned to creator/user `1`.
- Courses `1-6` are legacy free courses with `is_free_course=1`, `price=0`, and `discounted_price=0`.
- Courses `1-6` are also configured with `youngo_access_mode=subscription_only`, `youngo_allow_individual_purchase=0`, and `youngo_subscription_excluded=0`.
- Course `9` is active and has a valid category/subcategory relationship, but has no thumbnail, sections, or lessons.

## Course Form Compatibility

The current admin add/edit course forms require `sub_category_id`.

The existing model flow stores:

- `course.sub_category_id` from the submitted form value.
- `course.category_id` from the selected subcategory's parent category.

This means demo courses with `sub_category_id=0` are inconsistent with the current course form model and admin list/category filtering assumptions.

## Intended Changes

The UP artifact created marked demo subcategories locally:

| Parent category | New subcategory |
|---:|---|
| `2` Science Explorers | `9` Science Foundations |
| `3` Creative Arts | `10` Creative Foundations |
| `4` Reading & Storytelling | `11` Reading Foundations |
| `6` Life Skills | `12` Life Skills Foundations |

The UP artifact then aligned courses:

| Course | Existing parent category | Applied subcategory |
|---:|---|---|
| `1` | Coding for Kids (`1`) | Scratch Basics (`7`) |
| `2` | Science Explorers (`2`) | Science Foundations (`9`) |
| `3` | Creative Arts (`3`) | Creative Foundations (`10`) |
| `4` | Math Adventures (`5`) | Math 1 (`8`) |
| `5` | Reading & Storytelling (`4`) | Reading Foundations (`11`) |
| `6` | Life Skills (`6`) | Life Skills Foundations (`12`) |

The artifact preserves parent/category relationship consistency: each selected subcategory belongs to the course's existing `category_id`.

## Phase 2H.2 Local Apply Result

Local apply completed successfully.

Confirmed unchanged:

- Course `9`
- users/passwords
- `enrol`
- `payment`
- `youngo_course_access`
- `youngo_user_subscriptions`
- source code

Validation passed:

- PHP lint:
  - `application/controllers/Admin.php`
  - `application/models/Crud_model.php`
  - `application/controllers/Home.php`
  - `application/controllers/Files.php`
- HTTP smoke:
  - `/`
  - `/home/courses`
  - `/home/course/scratch-coding-for-young-creators/1`
  - `/login`
  - `/sign_up`
  - `/home/shopping_cart`
- Read-only form compatibility check:
  - Categories `2`, `3`, `4`, and `6` now each have selectable subcategories.

This was not applied to production/server.

## Deliberately Deferred

Course `9` is not modified by the active SQL.

Future options for course `9`:

- Deactivate/archive it as incomplete manual test data.
- Complete it with sections, lessons, and a thumbnail in a future demo-content phase.
- Leave it as a manual local test artifact.

Courses `1-6` keep existing `is_free_course`, `price`, and `discounted_price` values.

The free-course versus YounGo `subscription_only` alignment is deferred because changing legacy free-course flags could affect Academy LMS free-enrol behavior and should be planned separately.

YounGo access fields are also unchanged in this phase.

The Phase 2G QA dataset remains separate and should not be reused for demo course alignment.

Media/video/PDF demo content remains future work.

## Rollback Approach

The DOWN artifact:

- Reverts courses `1-6` back to `sub_category_id=0` only when they still point to the expected aligned subcategory.
- Deletes only Phase 2H-created subcategories with `YOUNGO_DEMO_ALIGNMENT_2H` marker codes.
- Does not delete existing subcategories `7` or `8`.
- Does not delete parent categories.
- Does not delete demo courses.
- Preserves marked subcategories if they are still referenced by any course, so cleanup cannot silently break a later manual assignment.

## Validation After Future Apply

After applying the UP artifact in a later approved phase:

1. Confirm courses `1-6` each have `sub_category_id > 0`.
2. Confirm each course subcategory parent equals the course `category_id`.
3. Confirm parent categories `2`, `3`, `4`, and `6` each have at least one subcategory.
4. Confirm course `9` is unchanged.
5. Confirm no users, passwords, or sessions changed.
6. Confirm no Phase 2G QA marker rows were created.
7. Smoke test:
   - `/`
   - `/home/courses`
   - `/home/course/scratch-coding-for-young-creators/1`
   - `/login`
   - `/sign_up`
8. Check admin course add/edit category dropdowns under a safe admin session if credentials are available.

After applying the DOWN artifact in a later approved rollback:

1. Confirm courses `1-6` are back to `sub_category_id=0`.
2. Confirm no `category.code` rows remain for `YOUNGO_DEMO_ALIGNMENT_2H`, unless preserved because a course references them.
3. Confirm existing categories `1-8`, demo courses `1-6`, and course `9` still exist.

## Risks

- Aligning subcategories may change how demo courses appear under category/subcategory filters. This is intended but should be smoke-tested.
- Reverting courses `1` and `4` back to `sub_category_id=0` would restore the previous incomplete state, so rollback should be used only when needed.
- The existing `subscription_only` plus `is_free_course=1` combination on courses `1-6` remains a business-model inconsistency. It is not fixed here.
- Course `9` remains incomplete until a separate decision is made.

## Recommendation

Review the artifacts first. If approved, apply locally only after recording a DB backup and baseline counts.

Do not combine this with Phase 2G QA dataset changes. Keep demo-data alignment and entitlement QA fixtures separate.
