# PAYMENT.PAYMOB.LEGACY.PAGE.LINK.1 - Safe YounGo Paymob Link From Legacy Payment Settings

Date: 2026-07-23

Scope: add a safe informational bridge from the existing legacy payment settings page to the dedicated YounGo Paymob settings page. No deployment, push, DB modification, SQL execution, real Paymob value entry, private value output/save, payment enablement, Paymob request, checkout CTA exposure, Root Admin data modification, legacy Paymob gateway row, legacy `payment_gateways` use for YounGo Paymob, or legacy `payment`/`enrol` write was performed.

## A. Current Branch/Status

Start command results:

```text
git branch --show-current
analysis/cms-audit

git status --short
<clean>

git log --oneline -10
1419db8 Plan YounGo Paymob sandbox config input
a0eca3b QA YounGo Paymob setup checklist page
8fed0cc Add YounGo Paymob setup checklist page
d222ea1 QA YounGo Paymob readiness page gates
9c2891b Align YounGo Paymob setup readiness gates
a7ff09a Audit YounGo payment work against legacy system
769af57 QA blocked YounGo Paymob sandbox readiness
125023c Add controlled YounGo Paymob sandbox intention flow
207fa95 Plan hybrid YounGo Paymob sandbox intention flow
81e6197 Add hybrid YounGo Paymob private config readiness
```

The expected branch and clean starting worktree were confirmed.

## B. Files Inspected

Required project guidance:

- `YOUNGO_PROJECT_CONTEXT.md`
- `docs/design/youngo_style_direction.md`
- `docs/planning/youngo_master_plan_v2.md`
- `docs/planning/youngo_phase_2_hybrid_access_subscriptions_roles_architecture.md`
- `docs/agents/implementation_rules.md`
- `docs/reference/README.md`

Legacy/admin files:

- `application/views/backend/admin/payment_settings.php`
- `application/views/backend/admin/payment_gateway.php` - not present in this codebase
- `application/views/backend/admin/navigation.php`
- `application/controllers/Admin.php`
- `application/controllers/Youngo_payment_settings.php`
- `application/views/backend/admin/youngo_payment_settings.php`
- `application/config/routes.php`

## C. Current Legacy Payment Settings Page

The active legacy payment settings page is:

```text
application/views/backend/admin/payment_settings.php
```

It is loaded by:

```text
Admin::payment_settings()
```

The controller keeps the existing Academy permission pattern:

```text
admin_login + check_permission('settings')
```

The view manages:

- system currency settings;
- existing legacy gateway cards from `$payment_gateways`;
- existing warning that active gateway currencies should match system currency.

`application/views/backend/admin/payment_gateway.php` was requested for inspection but is not present in this local codebase.

## D. Files Changed

Updated:

- `application/views/backend/admin/payment_settings.php`

Created:

- `scripts/phase_2/youngo_payment_legacy_page_link_1_diagnostic.php`
- `docs/qa/youngo_payment_legacy_page_link_1_report.md`

## E. Legacy Page Link Summary

Added a small informational card in the right column of the legacy payment settings page.

Card title:

```text
YounGo Paymob Setup
```

Target:

```text
/admin/youngo/payment-settings
```

The card explains:

- YounGo Paymob uses a dedicated checkout and readiness flow;
- it is separate from the legacy Academy payment gateways on the page;
- the link is for sandbox setup, readiness checks, and redacted configuration status;
- the link does not add Paymob to legacy gateway rows or enable legacy payment behavior.

The existing YounGo payment settings route remains:

```text
$route['admin/youngo/payment-settings'] = 'youngo_payment_settings/index';
```

## F. Safety Summary

Confirmed by implementation scope and diagnostic:

- no Paymob gateway row was added to legacy `payment_gateways`;
- no legacy gateway loop behavior was changed;
- no `Admin::payment_settings()` save behavior was changed;
- no YounGo Paymob dependency on legacy `payment_gateways` was added;
- no Paymob network call code was added to the linked paths;
- no DB writes were added;
- no private values were introduced;
- no checkout CTAs were exposed;
- access remains within the current dashboard payment settings permission pattern, and the target YounGo page remains Root-only through `Youngo_payment_settings`.

## G. Diagnostic Result

Created:

```text
scripts/phase_2/youngo_payment_legacy_page_link_1_diagnostic.php
```

Diagnostic result:

```text
php scripts/phase_2/youngo_payment_legacy_page_link_1_diagnostic.php
ok: true
failed_checks: []
```

The diagnostic verifies:

- legacy payment settings view exists;
- `payment_gateway.php` absence is documented;
- YounGo Paymob link card exists;
- target points to `/admin/youngo/payment-settings`;
- copy states the separate checkout/readiness flow and legacy-gateway boundary;
- YounGo payment settings route still exists;
- legacy payment settings controller/page markers still exist;
- legacy gateway loop remains present;
- no Paymob legacy gateway row/code was added;
- no `payment_gateways` dependency was added for YounGo Paymob;
- no Paymob call patterns were added in the link paths;
- diagnostic is static-only and performs no DB writes.

## H. What Was Not Changed

- No deployment.
- No push.
- No DB modification.
- No SQL execution.
- No real Paymob values.
- No private values printed.
- No private values saved to DB.
- No payment behavior enabled.
- No Paymob request performed.
- No checkout CTA exposed.
- No Root Admin data modified.
- No Paymob legacy gateway row added.
- No legacy `payment_gateways` use for YounGo Paymob.
- No legacy `payment` or `enrol` writes.

## I. Remaining Risks/Blockers

- This is only a navigation bridge. It does not make Paymob setup complete.
- YounGo Paymob sandbox execution remains gated by hybrid readiness and future explicit QA approval.
- Real Paymob sandbox values are still owner-provided inputs and must stay outside Git/reports.
- The target YounGo page remains Root-only; non-root admins with legacy settings permission may see the card but should be blocked if they follow the link.

## J. Recommended Next Phase

Recommended next phase:

```text
PAYMENT.PAYMOB.SANDBOX.CONFIG.INPUT.QA.1
```

Purpose:

- verify safe non-private dashboard config input;
- verify private ignored server-config presence only;
- keep sandbox execution disabled until explicitly approved.

## K. Validation

Ran:

```text
php -l application/views/backend/admin/payment_settings.php
php -l scripts/phase_2/youngo_payment_legacy_page_link_1_diagnostic.php
php scripts/phase_2/youngo_payment_legacy_page_link_1_diagnostic.php
git diff --check
git status --short
```

Result:

```text
PHP lint passed.
Diagnostic ok: true.
failed_checks: []
git diff --check: passed; emitted only the LF-to-CRLF working-copy warning for application/views/backend/admin/payment_settings.php
```

## L. Git Status

Final status after this phase:

```text
 M application/views/backend/admin/payment_settings.php
?? docs/qa/youngo_payment_legacy_page_link_1_report.md
?? scripts/phase_2/youngo_payment_legacy_page_link_1_diagnostic.php
```
