# DEPLOY.BASELINE.MAIN.RUNTIME.CLEAN.1 Report

## A. Cleanup Context Used

Original working tree:

```text
D:\Work\YounGo\school
```

Current branch in original working tree:

```text
development
```

The cleanup began with the preferred temporary worktree approach:

```text
D:\Work\YounGo\school_main_runtime_clean_tmp
```

That worktree checkout repeatedly timed out and left a locked partial worktree. The partial worktree was removed, including its Git worktree metadata. The final cleanup commit was created with a temporary Git index based on `origin/main`, without switching the development checkout and without deleting development files.

Final cleanup method:

```text
temporary Git index from origin/main
```

## B. Remote State Before Cleanup

Remote:

```text
https://github.com/Ziyad-Ashraf/YounGo_School.git
```

Remote state at start:

- `origin/main` -> `440e992c02c7ff72cd4c640563b3873b23ec0880`
- `origin/development` -> `9a02862683b3662e256370a5e81b5b0dd5ed83f7`

Original development working tree was clean before cleanup.

## C. Removed-From-Main Paths

Only the approved clear dev/internal paths were removed from `main`:

- `docs/`
- `scripts/`
- `AGENTS.md`
- `YOUNGO_PROJECT_CONTEXT.md`

Total removed tracked files:

```text
365
```

No uncertain runtime paths were removed.

## D. Paths Intentionally Left On Main

The following were intentionally left on `main` due to confirmed or possible runtime use:

- `database/`
- `update/`
- `languages/`
- `composer.json`
- `php.ini`
- `.user.ini`
- `application/`
- `system/`
- `assets/`
- `index.php`
- `.htaccess`
- `uploads/` guard files

Notes:

- `composer.lock` was requested as a keep path, but it was not present on `origin/main`.
- Root `vendor/` was requested as a keep path, but it was not present on `origin/main`.
- Bundled vendor/library files under `application/libraries/` were left untouched.

## E. Verification That Only Allowed Paths Were Removed

Verification was performed by comparing `origin/main` before cleanup to the candidate cleanup commit before pushing.

Diff verification result:

- Every changed path matched one of:
  - `docs/`
  - `scripts/`
  - `AGENTS.md`
  - `YOUNGO_PROJECT_CONTEXT.md`
- No changed path outside those allowed removals was found.
- `database/`, `update/`, `languages/`, `composer.json`, `php.ini`, `.user.ini`, `application/`, `system/`, `assets/`, `index.php`, `.htaccess`, and `uploads/` all existed in the cleanup commit.

Diff stat:

```text
365 files changed, 103546 deletions(-)
```

## F. Main Cleanup Commit Hash

Cleanup commit:

```text
e9d2794dc6770e082bd33c690c0fb300b6414e6f
```

Commit message:

```text
Remove internal docs and diagnostics from production main
```

The commit has parent:

```text
440e992c02c7ff72cd4c640563b3873b23ec0880
```

## G. Main Push Result

Push result:

```text
440e992..e9d2794  main -> main
```

No force push was used.

Remote heads after push:

- `origin/main` -> `e9d2794dc6770e082bd33c690c0fb300b6414e6f`
- `origin/development` -> `9a02862683b3662e256370a5e81b5b0dd5ed83f7`

## H. Development Preservation Check

Back in the original development working tree, these paths still exist:

- `docs/`
- `scripts/`
- `AGENTS.md`
- `YOUNGO_PROJECT_CONTEXT.md`

Development branch remains:

```text
development
```

Development branch commit remains:

```text
9a02862683b3662e256370a5e81b5b0dd5ed83f7
```

The failed temporary worktree path was removed:

```text
D:\Work\YounGo\school_main_runtime_clean_tmp
```

## I. cPanel Untouched Confirmation

cPanel was not touched.

No deployment, cPanel link, cPanel pull, cPanel cleanup, server config change, or server file change was performed.

## J. DB Untouched Confirmation

The database was not touched.

No SQL was executed. No database import, export, migration, query, backup restore, or data change was performed.

## K. Remaining Risks

Remaining `main` cleanup decisions intentionally deferred:

- `database/` still exists on `main`.
- `update/` still exists on `main`.
- `languages/` still exists on `main`.
- `php.ini` and `.user.ini` still exist on `main`.
- `composer.json` still exists on `main`.
- vendor/docs/examples/tests under runtime library paths still exist where bundled inside `application/`.
- admin template demo assets still exist where bundled inside `assets/`.

These were left because the owner requested that uncertain runtime files remain in this phase.

cPanel update risk remains:

- Future Git pulls on cPanel must preserve server-only config files and runtime uploads.
- Never run `git clean -fdx` on cPanel.
- Future cleanup phases should continue to avoid database and cPanel operations unless explicitly approved.

## L. Recommended Next Phase

Recommended next phase:

```text
DEPLOY.BASELINE.MAIN.RUNTIME.POST_CLEAN.AUDIT.1
```

Suggested scope:

- Re-audit `origin/main` after cleanup.
- Confirm removed paths are absent from `main`.
- Confirm `development` still preserves docs/scripts/reports.
- Confirm no sensitive/runtime files were introduced.
- Decide whether any owner-decision paths should be addressed later.
