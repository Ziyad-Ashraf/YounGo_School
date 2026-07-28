# YounGo Phase 2H Demo Data Alignment Artifacts

These files are local/demo-only, reviewable artifacts for aligning the existing demo courses and categories with the current Academy LMS course forms and the YounGo Phase 2 access model.

There is currently no real production/client data in this local system. The existing rows are demo/local/test data. They can be deliberately aligned when that improves compatibility with the current forms, roles, access rules, and QA workflows.

Do not apply these SQL files until explicitly approved.

Purpose:

- Add missing demo subcategories under existing parent categories that currently have none.
- Point demo courses `1-6` at valid subcategories, matching the course add/edit form contract.
- Preserve existing course/category parent relationships.
- Keep the Phase 2G QA dataset separate.

Files:

- `youngo_phase_2h_demo_data_alignment_up.sql`: creates marked demo subcategories and updates only demo course subcategory assignments.
- `youngo_phase_2h_demo_data_alignment_down.sql`: reverts only the Phase 2H demo alignment changes.

Safety boundaries:

- Marker: `YOUNGO_DEMO_ALIGNMENT_2H`.
- No users are created or modified.
- No passwords are changed.
- No session rows or cookies are created.
- No source code is changed.
- No QA marker rows are created or modified.
- Course `9` is documented as an incomplete manual/demo course and is not modified by the active SQL.
