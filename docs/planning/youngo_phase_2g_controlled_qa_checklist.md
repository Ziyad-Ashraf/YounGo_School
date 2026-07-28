# YounGo Phase 2G Controlled QA Checklist

## Local-Only Warning

This checklist is for local entitlement hard-gate QA only.

Do not apply the QA dataset to production or server environments. Do not export QA rows or QA media fixtures into production-like database dumps. Do not create users, alter passwords, create session rows, manipulate cookies, run migrations, or change source code as part of this QA.

The dataset artifacts are review-only until a later phase explicitly approves local DB fixture writes:

```text
database/phase_2/qa/youngo_phase_2g_qa_dataset_up.sql
database/phase_2/qa/youngo_phase_2g_qa_dataset_down.sql
```

## Baseline Recording Before Any Future Apply

Record these values before applying any QA dataset:

- `git status --short`
- Current branch and last commit
- Counts for `course`, `section`, `lesson`, `enrol`, `youngo_course_access`, `youngo_user_subscriptions`, `watch_histories`, and `watched_duration`
- Max IDs for `course`, `section`, `lesson`, `enrol`, `youngo_course_access`, and `youngo_user_subscriptions`
- Current state of users `2`, `5`, and `6`: `id`, `email`, `role_id`, `is_instructor`, `status`
- Current course creator/user_id assignments involving user `5`
- Current rows with marker `YOUNGO_QA_2G_LOCAL_ONLY`; expected before apply is zero
- File listing and hashes under `uploads/lesson_files` if media fixture files will be created later

Do not proceed if the working tree is dirty in source code or if existing QA marker rows are present. Run/review cleanup first.

## Manual Media Fixture Requirement

The SQL artifact references media files but does not create binary files.

Only after explicit approval for local file fixture writes, create:

```text
uploads/lesson_files/youngo_qa_2g_local_only_sample.pdf
uploads/lesson_files/youngo_qa_2g_local_only_sample.mp4
```

Requirements:

- PDF: small, harmless, non-sensitive local test PDF.
- MP4: small, harmless test video suitable for HTTP range-request testing.
- Record file size and hash before testing.
- Delete both files during cleanup.

If media fixtures are not approved or unavailable, mark PDF/MP4 streaming cases as blocked.

## Browser And Session Rules

Use `http://school.local`, not `localhost`.

Use normal browser login/session behavior only:

- Do not alter passwords.
- Do not create manual session rows in `ci_sessions`.
- Do not manipulate cookies.
- If credentials or authenticated browser sessions are unavailable, mark the affected case blocked.

Preferred identities:

- User `1`: admin/root.
- User `2`: learner with active subscription for subscription and purchase-only denial cases.
- User `5`: non-admin assigned instructor candidate and revoked subscription case.
- User `6`: entitlement learner for legacy enrol, course_access, expired subscription, and media cases.

## Test Case Matrix

| Case | User | QA course type | Access source | Expected lesson result | Expected file result |
|---|---:|---|---|---|---|
| TC-INSTRUCTOR-ACCESS | 5 | subscription-eligible | course creator/user_id assignment | allow | allow PDF |
| TC-LEGACY-VALID | 6 | purchase-only | valid `enrol` | allow | allow PDF |
| TC-LEGACY-EXPIRED | 6 | purchase-only | expired `enrol` | deny | deny empty/no stream |
| TC-COURSE-ACCESS-ACTIVE | 6 | subscription-eligible | active `youngo_course_access` | allow | allow PDF |
| TC-COURSE-ACCESS-EXPIRED | 6 | subscription-eligible | expired `youngo_course_access` | deny | deny empty/no stream |
| TC-COURSE-ACCESS-REVOKED | 6 | purchase-only | revoked `youngo_course_access` | deny, state source `course_purchase`, status `revoked` | deny empty/no stream |
| TC-SUBSCRIPTION-ACTIVE | 2 | subscription-eligible | active `youngo_user_subscriptions` | allow | allow PDF |
| TC-SUBSCRIPTION-EXPIRED | 6 | subscription-eligible | expired `youngo_user_subscriptions` | deny | deny empty/no stream |
| TC-SUBSCRIPTION-REVOKED | 5 | subscription-eligible | revoked `youngo_user_subscriptions` | deny | deny empty/no stream |
| TC-PURCHASE-ONLY-DENIAL | 2 | purchase-only | active subscription only | deny | deny empty/no stream |
| TC-MEDIA-STREAMING | 6 | subscription-eligible | active `youngo_course_access` | allow | allow PDF and MP4 range request |

## Phase 2G.8C Recorded Results

Phase 2G.8C reran the read-only entitlement-state matrix after cleanup/reapply of the fixed QA dataset.

Fixed local QA dataset state:

- QA courses: `32-42`
- QA sections: `43-53`
- QA lessons: `62-84`
- `enrol` rows: `8-9`
- `youngo_course_access` rows: `5-8`
- `youngo_user_subscriptions` rows: `4-6`
- PDF fixture exists locally: `uploads/lesson_files/youngo_qa_2g_local_only_sample.pdf`
- MP4 fixture does not exist; MP4/range QA remains blocked.

`TC-COURSE-ACCESS-REVOKED` isolation was confirmed fixed:

- `course_id=37`
- `youngo_access_mode=purchase_only`
- `youngo_subscription_excluded=1`
- `user_id=6`
- `has_access=false`
- `access_source=course_purchase`
- `status=revoked`

Entitlement-state matrix result:

| Case | Result |
|---|---|
| TC-INSTRUCTOR-ACCESS | passed: allow, `instructor/active` |
| TC-LEGACY-VALID | passed: allow, `legacy_enrol/active` |
| TC-LEGACY-EXPIRED | passed: deny, `legacy_enrol/expired` |
| TC-COURSE-ACCESS-ACTIVE | passed: allow, `course_purchase/active` |
| TC-COURSE-ACCESS-EXPIRED | passed: deny, `course_purchase/expired` |
| TC-COURSE-ACCESS-REVOKED | passed: deny, `course_purchase/revoked` |
| TC-SUBSCRIPTION-ACTIVE | passed: allow, `subscription/active` |
| TC-SUBSCRIPTION-EXPIRED | passed: deny, `subscription/expired` |
| TC-SUBSCRIPTION-REVOKED | passed: deny, `subscription/revoked` |
| TC-PURCHASE-ONLY-DENIAL | passed: deny, `none/none` |
| TC-MEDIA-STREAMING | passed access-state check: allow, `course_purchase/active`; MP4/range playback remains blocked because the MP4 fixture does not exist |

Non-authenticated denial checks passed:

- Logged-out `Home::lesson()` did not render protected content.
- Logged-out `Files::index()` did not stream the protected PDF/file.
- Fake request `user_id` did not grant file access.
- Lesson/course mismatch did not stream a file.
- Missing lesson did not fatal or stream a file.

Safety results:

- Denied tests did not change `watch_histories`.
- Denied tests did not change `watched_duration`.
- No source code changed during QA.
- No manual session rows were created.
- `ci_sessions` increased only due to normal HTTP requests.

Remaining gaps:

- Authenticated browser lesson playback.
- Authenticated browser file/PDF playback.
- Authorized PDF streaming under a real session.
- MP4/range streaming.
- Payment/checkout behavior remains unchanged and was not part of this QA.
- `Home::play_lesson()`, `Home::pdf_canvas()`, progress AJAX endpoints, and API/mobile gates remain future phases.

Recommendation:

- No source fix is needed from Phase 2G.8C.
- Clean up the local QA dataset next using the reviewed down SQL.
- Do not claim full media playback coverage until authenticated browser and MP4/range QA are performed.
- Do not plan additional gates before QA cleanup is complete.

Isolation rule:

- Each test case has its own QA course.
- No user/course pair should have more than one active access source.
- Inactive-state source checks must also avoid competing inactive sources that the helper can consider for the same access decision. User-level subscriptions can compete with course access on subscription-eligible courses, so direct revoked `youngo_course_access` validation uses a purchase-only/subscription-excluded QA course.
- Do not add extra access rows during manual testing.
- If a case is manually altered, clean it up or disable it before running another case for the same user/course.
- If the SQL artifacts are changed after the local dataset has already been applied, cleanup and reapply the dataset before retesting artifact-dependent expectations.

## Test Procedure

For each case:

1. Resolve the QA course id and text/PDF/MP4 lesson ids by marker and case name.
2. Record before counts:
   - `watch_histories`
   - `watched_duration`
   - `enrol`
   - `youngo_course_access`
   - `youngo_user_subscriptions`
3. Log in as the specified user through the normal browser flow.
4. Request the lesson page:
   - `/home/lesson/{slug}/{course_id}/{text_lesson_id}`
5. Request the PDF file URL with an appropriate referer:
   - `/files?course_id={course_id}&lesson_id={pdf_lesson_id}`
6. For `TC-MEDIA-STREAMING`, request the MP4 file URL:
   - `/files?course_id={course_id}&lesson_id={mp4_lesson_id}`
   - Include at least one request with a `Range` header.
7. Run direct-access negative checks where applicable:
   - fake `user_id` parameter
   - missing lesson id
   - lesson/course mismatch
8. Record after counts.
9. Log out or clear the browser session normally before changing users.

Expected mutation behavior:

- Denied lesson/file tests must not increase `watch_histories` or `watched_duration`.
- Authorized `Home::lesson()` may create/update `watch_histories`; record it and clean it up with the down artifact.
- Authorized `Files::index()` should not create progress rows.

## Page Smoke After Dataset Apply

After applying the dataset and after cleanup, smoke test:

- `/`
- `/home/courses`
- `/home/course/scratch-coding-for-young-creators/1`
- `/home/lesson/scratch-coding-for-young-creators/1/1`
- `/home/my_courses`
- `/login`
- `/sign_up`
- `/home/shopping_cart`

These pages should load without fatal errors or DB errors.

## Cleanup Verification

After QA, apply the down artifact only after explicit approval:

```text
database/phase_2/qa/youngo_phase_2g_qa_dataset_down.sql
```

Then verify:

- No `course` rows with `meta_keywords = 'YOUNGO_QA_2G_LOCAL_ONLY'`
- No `section` or `lesson` rows with titles starting `YOUNGO_QA_2G_LOCAL_ONLY`
- No `enrol` or `youngo_course_access` rows scoped to QA course ids
- No `youngo_user_subscriptions` rows with `source = 'YOUNGO_QA_2G_LOCAL_ONLY'`
- No `watch_histories` or `watched_duration` rows scoped to QA course ids
- Manual media files removed:
  - `uploads/lesson_files/youngo_qa_2g_local_only_sample.pdf`
  - `uploads/lesson_files/youngo_qa_2g_local_only_sample.mp4`
- `git status --short` shows no source-code changes

## Blocked Cases

Mark cases blocked, not failed, when:

- Safe credentials or browser sessions are unavailable.
- Media fixture file writes are not approved.
- The QA dataset has not been explicitly approved for local application.
- The local database state does not match the expected schema or user ids.

Do not compensate by altering users, passwords, sessions, source code, or production-like data.
