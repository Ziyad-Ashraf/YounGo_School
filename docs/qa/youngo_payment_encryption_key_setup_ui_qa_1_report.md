# PAYMENT.SECRETS.ENCRYPTION.KEY.SETUP.UI.QA.1

## A. Current Branch/Status

- Branch: `analysis/cms-audit`
- Initial worktree status: one untracked previous blocked QA report from the earlier attempt.
- Latest visible commit before retry: `cfe3ed5 Add non-committed YounGo encryption key loading`
- Local stack status during retry:
  - `http://school.local/` reachable with HTTP 200.
  - Local DB `youngo_school` reachable.
  - `application/config/youngo_security.local.php` absent, as required.
- Scope: authenticated Root Admin browser/session QA for the encryption-key readiness display.

## B. URLs Tested

Authenticated Root Admin session:

- `http://school.local/login`
- `http://school.local/login/validate_login`
- `http://school.local/admin/youngo/payment-settings`

Public CTA safety pages:

- `http://school.local/`
- `http://school.local/home/courses`
- `http://school.local/home/course/robotics-and-ai-explorers/9`
- `http://school.local/home/my_wishlist`

Credentials were used only for login and were not printed in this report.

## C. Readiness Display Summary

`/admin/youngo/payment-settings` rendered with HTTP 200.

Verified on the rendered page:

- YounGo Paymob setup page loaded.
- `Encryption key configured` row is present.
- Effective encryption key state is missing/no key configured.
- `Encrypted DB credential storage` row is present.
- Encrypted DB credential storage is blocked while no key exists.
- `Private Paymob DB storage is blocked` warning remains visible.
- No `youngo_security.local.php` file exists locally.
- No encryption key value was displayed.

The page therefore matches the intended no-key readiness state:

- key presence: missing;
- encrypted credential storage: blocked;
- payment behavior: disabled.

## D. Private Credential Safety

Rendered private credential controls were verified for:

- `api_key`
- `public_key`
- `secret_key`
- `hmac_secret`

Each rendered as:

- value/status: `server_config_required`;
- `disabled`;
- `readonly`;
- no submit `name` attribute.

Additional checks:

- No private credential POST names were present.
- No Paymob secret-key/public-key token patterns were found.
- No external Paymob script/link/form-action resource was present on the settings page.
- No private values were saved or submitted.

## E. Non-Private Fields

The existing non-private setup form still rendered these fields:

- `mode`
- `currency`
- `amount_multiplier`
- `card_integration_id_egp`
- `api_base_url`
- `checkout_base_url`
- `return_url`
- `notification_url`

The form remains non-private only. No payment activation/network/CTA flags were editable.

## F. Sandbox Test Boundary

Verified:

- Sandbox test control exists as disabled/non-functional.
- Rendered text: `Sandbox test not available yet`.
- `data-youngo-sandbox-test-disabled="true"` present.
- No Paymob checkout session/intention request was triggered.

## G. Public CTA Safety

Public pages rendered with HTTP 200 and no `youngo/checkout/start` link:

| URL | HTTP | Checkout CTA Link | External Paymob Resource |
| --- | --- | --- | --- |
| `/` | 200 | absent | absent |
| `/home/courses` | 200 | absent | absent |
| `/home/course/robotics-and-ai-explorers/9` | 200 | absent | absent |
| `/home/my_wishlist` | 200 | absent | absent |

No production checkout CTA exposure was detected.

## H. Protected Counts

Read-only protected table counts observed after QA:

```text
youngo_checkout_orders=0
youngo_payment_transactions=0
youngo_course_access=0
payment=0
enrol=1
```

No checkout order, payment transaction, YounGo access, or payment rows were created by this QA.

## I. What Was Not Changed

- No deployment.
- No push.
- No `youngo_security.local.php` created.
- No real encryption key added.
- No Paymob credentials added, printed, or saved.
- No Paymob calls performed.
- No payment behavior enabled.
- No checkout CTA exposed.
- No Root Admin data modified.
- No legacy payment behavior changed.
- No SQL executed.

## J. Validation

Commands run:

- `php scripts/phase_2/youngo_payment_encryption_key_setup_1_diagnostic.php` - PASS
- `php scripts/phase_2/youngo_payment_paymob_setup_form_ui_1_diagnostic.php` - PASS

Additional rendered-page checks:

- Admin Paymob settings HTTP 200: PASS
- Encryption-key readiness row present: PASS
- Encrypted credential storage blocked: PASS
- Private fields disabled/no submit names: PASS
- Non-private fields render: PASS
- Sandbox test disabled: PASS
- Public checkout CTA absent: PASS

## K. Remaining Risks/Blockers

- The encryption key is intentionally still missing.
- Encrypted Paymob credential DB storage is still not implemented.
- A future phase must create an ignored local/server key file only with owner approval and must not print or commit the key.

## L. Recommended Next Phase

Recommended next phase:

`PAYMENT.SECRETS.ENCRYPTION.KEY.LOCAL.CREATE.1`

Purpose:

- With explicit owner approval, create `application/config/youngo_security.local.php` locally only.
- Add a generated key without committing or printing it.
- Verify dashboard readiness changes to key configured while encrypted credential schema remains pending.

## M. Git Status

Expected local change for this completed QA retry:

```text
?? docs/qa/youngo_payment_encryption_key_setup_ui_qa_1_report.md
```
