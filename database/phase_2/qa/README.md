# YounGo Phase 2G QA Artifacts

These files are local-only, reviewable QA artifacts for entitlement hard-gate testing.

Do not apply them to production or export their resulting rows to production-like data dumps.

Files:

- `youngo_phase_2g_qa_dataset_up.sql`: creates temporary local QA rows only after explicit approval.
- `youngo_phase_2g_qa_dataset_down.sql`: removes only QA rows created with the `YOUNGO_QA_2G_LOCAL_ONLY` marker.

These artifacts do not create users, do not change passwords, do not create sessions, and do not include binary media fixtures. Manual PDF/MP4 fixture files are documented in the checklist and must be created only after separate approval.
