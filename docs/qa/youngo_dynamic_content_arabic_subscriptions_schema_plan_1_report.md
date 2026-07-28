# DYNAMIC.CONTENT.ARABIC.SUBSCRIPTIONS.SCHEMA.PLAN.1 Report

## A. Current Branch/Status

- Branch at start: `analysis/cms-audit`
- Worktree at start: clean
- Latest commit at start: `1823030 Plan Arabic dynamic frontend content localization`
- This phase is planning-only. No deployment, push, DB write, SQL execution, subscription plan data edit, public behavior change, payment/Paymob change, checkout CTA exposure, Root Admin change, or `arabic_translated` UI usage was performed.

## B. Files Inspected

- `docs/qa/youngo_language_frontend_dynamic_content_ar_copy_plan_1_report.md`
- `docs/qa/youngo_subscriptions_page_dynamic_ui_1_report.md`
- `docs/qa/youngo_language_frontend_arabic_visual_copy_polish_1_report.md`
- `application/models/Youngo_subscription_model.php`
- `application/controllers/Home.php`
- `application/controllers/Youngo_subscription_plans.php`
- `application/views/frontend/youngo/subscriptions.php`
- `application/views/backend/admin/youngo_subscription_plans.php`
- `application/views/backend/admin/youngo_subscription_plan_form.php`
- `application/views/backend/admin/youngo_subscription_plan_view.php`
- `application/config/routes.php`
- `scripts/phase_2/`

## C. Current Subscription Schema/Admin Summary

Current table: `youngo_subscription_plans`.

Detected fields:

- `id`
- `name`
- `slug`
- `duration_days`
- `price`
- `currency`
- `is_active`
- `is_purchasable`
- `is_featured`
- `sort_order`
- `created_at`
- `updated_at`
- `archived_at`
- `archived_by_user_id`

Current local counts from the read-only diagnostic:

- Total subscription plan rows: `3`
- Public-eligible plan rows under current filters: `3`
- `youngo_subscription_plan_translations` table exists: `false`

Admin surface:

- Controller: `Youngo_subscription_plans`
- Routes:
  - `/admin/youngo/subscription-plans`
  - `/admin/youngo/subscription-plans/create`
  - `/admin/youngo/subscription-plans/{id}`
  - `/admin/youngo/subscription-plans/{id}/edit`
  - `/admin/youngo/subscription-plans/{id}/status`
  - `/admin/youngo/subscription-plans/{id}/archive`
  - `/admin/youngo/subscription-plans/{id}/restore`
- Guard: admin session plus `manage_subscriptions` capability.
- Create/edit form fields: `name`, `slug`, `duration_days`, `price`, `sort_order`, `is_active`, `is_purchasable`, `is_featured`.
- No bilingual subscription plan fields are currently present in the admin form.

Model behavior:

- `Youngo_subscription_model::get_public_subscription_plans($language = null)` exists and returns normalized public rows.
- Public filters require active, purchasable, `EGP`, positive price, positive duration, and non-archived rows where archive fields exist.
- Public sorting is featured first, then `sort_order`, then `id`.
- The normalized public shape already includes `name`, `slug`, `duration_days`, `duration_label`, `price`, `price_display`, `currency`, `featured`, and `short_description`.
- The model currently checks hypothetical bilingual base-table fields such as `arabic_name`/`name_ar`, but the actual schema has no such fields, so public plan names still fall back to canonical English `name`.

Slug behavior:

- `youngo_subscription_plans.slug` is currently the canonical operational identifier.
- Slug format is constrained to lowercase letters, numbers, and hyphens.
- Slug changes are blocked once a plan has dependent subscriptions, orders, coupons, or manual grants.
- Translated public slugs should not be introduced in the first subscription localization pass.

Public page behavior:

- Public routes already exist:
  - `/subscriptions`
  - `/en/subscriptions`
  - `/ar/subscriptions`
- `Home::subscriptions()` resolves the frontend URI language and calls `get_public_subscription_plans($youngo_frontend_language)`.
- `application/views/frontend/youngo/subscriptions.php` loops dynamic model rows.
- Static labels use the frontend phrase helper.
- Public CTA remains safe contact/coming-soon messaging only.

## D. Translation Schema Recommendation

Add an additive table:

`youngo_subscription_plan_translations`

Recommended fields:

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

Recommended constraints/indexes:

- Primary key: `id`
- Unique key: `plan_id`, `language_code`
- Index: `plan_id`
- Index: `language_code`

Rules:

- Supported `language_code` values: `english`, `arabic`.
- Reject `arabic_translated` for subscription plan translations.
- Do not duplicate `price`, `duration_days`, `currency`, `slug`, `is_active`, `is_purchasable`, `is_featured`, `sort_order`, `archived_at`, or payment-related fields.
- Do not add checkout/order/payment fields to this table.
- Do not add hard foreign keys unless later project migration policy changes; prior Phase 2 schema additions generally avoided hard FKs for compatibility.
- Keep the table content-only and operationally passive.

`badge_label` is optional but useful if the owner wants localized labels such as "Most popular" that differ by plan. If no per-plan badge copy is needed, it can be deferred and static featured labels can remain phrase-backed.

## E. Fallback Behavior Recommendation

Recommended public fallback order:

- Arabic/default unprefixed URLs: use `arabic` translation if available and non-empty; otherwise fall back to base plan fields.
- `/ar` compatibility URLs: same Arabic behavior.
- `/en` URLs: use `english` translation if available and non-empty; otherwise fall back to base plan fields.
- Missing `short_description`: fall back to translated `description`, then base description fields if introduced later, then safe empty-state/fallback copy in the view.
- Slugs remain from `youngo_subscription_plans.slug` in all languages.

The base `youngo_subscription_plans.name` should remain the compatibility/operational fallback. It should not be silently rewritten as part of translation wiring.

## F. Admin UI Recommendation

Add bilingual content fields to the existing subscription plan create/edit form after the schema is approved:

- English plan name
- English short description
- English description
- Arabic plan name, RTL
- Arabic short description, RTL
- Arabic description, RTL
- Optional English/Arabic badge label if `badge_label` is included

Preservation rules:

- Keep the existing base `name` field behavior for compatibility, or map it from the English plan name during save.
- Keep `slug`, `duration_days`, `price`, `currency`, status flags, featured flag, sort order, archive fields, and dependencies as shared operational fields.
- Arabic translation fields should be optional.
- English translation should be upserted from current/base English fields for existing records in the implementation/seed phase, not in this planning phase.
- Empty Arabic fields should not create blank Arabic translation rows unless the model needs an explicit empty marker; the safer default is no row until Arabic content is provided.
- Saving translations must not activate plans, make plans purchasable, unlock slug changes, create checkout/order/payment rows, or alter entitlement state.

Validation rules:

- Language code must be fixed to `english` or `arabic`.
- Reject `arabic_translated`.
- Enforce scalar strings and max lengths.
- Escape output in views.
- Preserve the existing `manage_subscriptions` guard.

## G. Public Wiring Recommendation

Recommended model changes:

- Add translation-table schema detection to `Youngo_subscription_model`.
- Add a helper such as `normalize_subscription_plan_language($language)` returning only `english` or `arabic`.
- Add a bulk translation loader for selected public plan IDs to avoid one query per plan.
- Apply translation values inside `get_public_subscription_plans($language)` while keeping the current normalized public row shape.
- Keep all eligibility filters on `youngo_subscription_plans`, not the translation table.

Recommended public view changes:

- Minimal or no structural view change should be needed if the model continues returning `name` and `short_description`.
- Static labels should continue to use `youngo_frontend_phrase()`.
- Dynamic plan names/descriptions should come from `youngo_subscription_plan_translations` via the model.
- The page should continue showing contact/coming-soon CTA only.

Recommended route behavior:

- `/subscriptions`: Arabic/default dynamic content.
- `/en/subscriptions`: English dynamic content.
- `/ar/subscriptions`: Arabic compatibility behavior.
- Do not introduce translated plan slugs in this phase.

## H. Payment/CTA Safety

- The proposed translation table has no payment, checkout, order, enrolment, access-grant, coupon, gateway, or Paymob fields.
- Public subscription eligibility remains controlled by the base plan table's active/purchasable/currency/duration/price/archive filters.
- No checkout CTA should be added during schema/model/admin/public translation wiring.
- Current public subscription CTA should remain contact/coming-soon until the separate checkout/payment phase is approved.
- Admin translation edits must not change commercial readiness, payment settings, Paymob settings, or Root Admin permissions.

## I. Implementation Phases

Recommended order:

1. `DYNAMIC.CONTENT.ARABIC.SUBSCRIPTIONS.SCHEMA.1`
2. `DYNAMIC.CONTENT.ARABIC.SUBSCRIPTIONS.MODEL.1`
3. `DYNAMIC.CONTENT.ARABIC.SUBSCRIPTIONS.ADMIN.UI.1`
4. `DYNAMIC.CONTENT.ARABIC.SUBSCRIPTIONS.PUBLIC.WIRE.1`
5. `DYNAMIC.CONTENT.ARABIC.SUBSCRIPTIONS.QA.1`

Suggested phase notes:

- Schema phase: create up/down SQL, backup first, apply locally only after approval, no translation data edits beyond optional English seed if explicitly scoped.
- Model phase: add translation read/upsert helpers and fallback behavior without changing public CTA/payment behavior.
- Admin UI phase: add bilingual fields and save translation rows through the model.
- Public wire phase: use translation table values for dynamic plan display.
- QA phase: use temporary or owner-approved translated copy, verify Arabic/default, `/en`, `/ar`, Edit Phrase/static label separation, and payment/CTA safety.

## J. Risks/Blockers

- Public plan names remain English until translation rows are created and the model is wired to read them.
- Existing base plan `name` remains the compatibility fallback, so incomplete Arabic translations will still expose English dynamic content.
- A decision is needed on whether `badge_label` is truly per-plan content or should stay as static phrase-backed UI copy.
- Translated slugs are intentionally deferred because slugs can become operational identifiers and may interact with future checkout/order URLs.
- Admin UX must make clear that changing translation copy is not the same as activating or commercializing a subscription plan.
- Later QA must verify no regression to the safe no-checkout public subscription CTA boundary.

## K. Diagnostic Result

Created and ran:

`scripts/phase_2/youngo_dynamic_content_arabic_subscriptions_schema_plan_1_diagnostic.php`

Result:

- Required files detected.
- `youngo_subscription_plans` table detected.
- `youngo_subscription_plan_translations` table not present locally.
- Total plan rows: `3`.
- Public-eligible plan rows: `3`.
- Public routes for `/subscriptions`, `/en/subscriptions`, and `/ar/subscriptions` detected.
- Admin subscription plan routes and admin form targets detected.
- Current public model/view wiring detected.
- No protected payment/checkout/access files were changed.
- No Paymob/checkout links were detected in the public subscriptions view.
- Diagnostic passed without DB writes.

## L. Git Status

Pending files after this planning phase:

- `docs/qa/youngo_dynamic_content_arabic_subscriptions_schema_plan_1_report.md`
- `scripts/phase_2/youngo_dynamic_content_arabic_subscriptions_schema_plan_1_diagnostic.php`
