# DYNAMIC.CONTENT.ARABIC.SUBSCRIPTIONS.SCHEMA.1 Report

## A. Current Branch/Status

- Branch at start: `analysis/cms-audit`
- Worktree at start: clean
- Latest commit at start: `77bbc45 Plan subscription plan translation schema`
- This phase added additive local schema and model foundation only.
- No deployment, push, subscription plan content edit, public subscription page behavior change, payment/Paymob change, checkout CTA exposure, Root Admin change, `arabic_translated` UI usage, or operational slug change was performed.

## B. Backup Created

Fresh local DB backup created before schema apply:

- Path: `D:\Work\YounGo\backups\youngo_school_before_dynamic_content_arabic_subscriptions_schema_1_2026_07_26_064903.sql`
- Size: `951213` bytes
- SHA256: `CEA03A1163D4E79C6F5FD438D4EFB8F6290264A57878401D68DD0DD51C0D0A6A`

The XAMPP `mysqldump` path was not used directly after local config parsing issues; the backup was created with a temporary PHP `mysqli` dump helper outside the repo. The temporary helper was deleted.

## C. Files Inspected

- `docs/qa/youngo_dynamic_content_arabic_subscriptions_schema_plan_1_report.md`
- `docs/qa/youngo_language_frontend_dynamic_content_ar_copy_plan_1_report.md`
- `application/models/Youngo_subscription_model.php`
- `application/controllers/Youngo_subscription_plans.php`
- `application/controllers/Home.php`
- `application/views/backend/admin/youngo_subscription_plans.php`
- `application/views/backend/admin/youngo_subscription_plan_form.php`
- `application/views/backend/admin/youngo_subscription_plan_view.php`
- `application/views/frontend/youngo/subscriptions.php`
- `application/config/routes.php`
- `database/phase_2/youngo_phase_2u3_localization_schema_up.sql`
- `database/phase_2/youngo_phase_2l_subscription_plan_management_schema_up.sql`
- `scripts/phase_2/`

## D. Files Changed

- `application/models/Youngo_subscription_model.php`
- `scripts/phase_2/dynamic_content_arabic_subscriptions_schema_1_up.sql`
- `scripts/phase_2/dynamic_content_arabic_subscriptions_schema_1_down.sql`
- `scripts/phase_2/youngo_dynamic_content_arabic_subscriptions_schema_1_diagnostic.php`
- `docs/qa/youngo_dynamic_content_arabic_subscriptions_schema_1_report.md`

## E. Schema Summary

Applied local additive table:

`youngo_subscription_plan_translations`

Fields:

- `id`
- `plan_id`
- `language_code`
- `name`
- `short_description`
- `description`
- `badge_label`
- `created_by_user_id`
- `updated_by_user_id`
- `created_at`
- `updated_at`

Indexes:

- Primary key: `id`
- Unique key: `uniq_yspt_plan_language` on `plan_id`, `language_code`
- Index: `idx_yspt_plan_id`
- Index: `idx_yspt_language_code`

Excluded by design:

- No `slug`
- No `price`
- No `currency`
- No `duration` or `duration_days`
- No active/purchasable/archive fields
- No payment, Paymob, checkout, order, enrolment, grant, or access fields

The table uses `utf8mb4` / `utf8mb4_unicode_ci`, matching recent metadata schema style.

## F. Model Foundation Summary

Updated `Youngo_subscription_model` with:

- `$translation_table = 'youngo_subscription_plan_translations'`
- `subscription_translation_table_exists()`
- `normalize_subscription_translation_language($language)`
- `get_plan_translations($plan_id)`
- `get_plan_translation($plan_id, $language)`
- `save_plan_translation_foundation($plan_id, $language, $input, $actor_user_id = null)`
- internal `validate_plan_translation_data($input)`

Behavior:

- Accepts `english`/`en` and `arabic`/`ar`.
- Rejects `arabic_translated` by returning no canonical translation language.
- Returns translation rows keyed by canonical language code.
- Save foundation validates content-only fields and rejects operational/payment fields such as slug, price, currency, duration, Paymob, checkout, order, enrolment, and access/grant fields.
- Not wired to admin UI yet.
- `get_public_subscription_plans()` was intentionally not changed to read translation rows in this phase, so public output remains unchanged.

## G. Diagnostic Result

Created and ran:

`scripts/phase_2/youngo_dynamic_content_arabic_subscriptions_schema_1_diagnostic.php`

Result: PASS.

Verified:

- Translation table exists.
- Expected columns exist.
- Unique `plan_id + language_code` key exists.
- No payment/slug/price/duration/currency/access fields exist in the translation table.
- Model foundation methods exist.
- Translation language normalizer accepts only canonical English/Arabic forms and does not accept `arabic_translated`.
- Public subscriptions method is not reading the translation table yet.
- Temporary Arabic diagnostic translation row inserted, read back, and deleted.
- Public subscriptions view still uses contact/coming-soon CTA only.
- No Paymob/checkout links detected in the public subscriptions view.
- No payment/checkout/access protected files changed.

## H. DB Impact/Cleanup

Permanent local DB change:

- Created additive table `youngo_subscription_plan_translations`.

Temporary DB activity:

- Inserted one temporary diagnostic row for `plan_id = 1`, `language_code = arabic`.
- Deleted the temporary row during the same diagnostic run.

Post-diagnostic cleanup:

- Temporary diagnostic rows remaining: `0`
- Real subscription plan rows were not edited.
- No translation content rows were intentionally seeded in this phase.

Rollback artifact:

- `scripts/phase_2/dynamic_content_arabic_subscriptions_schema_1_down.sql`

Primary rollback remains restoring the backup if any later content/QA data is added. For this schema-only phase, the down SQL drops only the additive translation table.

## I. Public/Payment Behavior Safety

- `Home::subscriptions()` behavior was not changed.
- `application/views/frontend/youngo/subscriptions.php` behavior was not changed.
- `get_public_subscription_plans()` output remains based on current base-table behavior and does not read the new translation table yet.
- Operational slugs remain in `youngo_subscription_plans`.
- No public checkout CTA was added.
- No Paymob, payment, checkout, order, enrolment, access, coupon, manual grant, or Root Admin behavior was changed.

## J. Remaining Risks/Blockers

- Public Arabic/default subscription plan names remain English until the model/public wiring phase reads translation rows and actual Arabic content is entered.
- Admin UI still has no bilingual subscription plan fields.
- No English/Arabic translation rows were seeded for existing plans yet.
- A later phase must decide whether `badge_label` is real per-plan content or can stay static phrase-backed UI copy.
- Translated slugs remain deferred and should not be added until checkout/order/SEO routing is reviewed.

## K. Recommended Next Phase

Recommended next phase:

`DYNAMIC.CONTENT.ARABIC.SUBSCRIPTIONS.MODEL.1`

Suggested scope:

- Add translation fallback read behavior into the public subscription model path.
- Keep public output shape stable.
- Do not change admin UI or seed real content unless explicitly included.
- Preserve contact/coming-soon CTA and no-checkout boundary.

## L. Git Status

Pending files after this phase:

- `application/models/Youngo_subscription_model.php`
- `docs/qa/youngo_dynamic_content_arabic_subscriptions_schema_1_report.md`
- `scripts/phase_2/dynamic_content_arabic_subscriptions_schema_1_down.sql`
- `scripts/phase_2/dynamic_content_arabic_subscriptions_schema_1_up.sql`
- `scripts/phase_2/youngo_dynamic_content_arabic_subscriptions_schema_1_diagnostic.php`
