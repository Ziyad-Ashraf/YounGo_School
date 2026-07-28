# PAYMENT.PAYMOB.CLIENT.HANDOFF.1 - Client Paymob Account Setup Request Checklist

Date: 2026-07-23

Purpose: provide a clear internal/client checklist for the Paymob sandbox account values needed before YounGo sandbox payment testing can continue.

Scope: documentation only. No deployment, push, DB modification, SQL execution, Paymob value entry, private value output, payment enablement, Paymob request, checkout CTA exposure, Root Admin modification, or legacy payment gateway change was performed.

## Current Blocked State

Sandbox Paymob execution cannot continue until the client/owner creates or confirms the Paymob sandbox account setup and provides the required sandbox values through a secure private channel.

Current YounGo payment boundaries:

- Production defaults remain disabled.
- Paymob setup is managed separately at `/admin/youngo/payment-settings`.
- The legacy payment gateway page only links to the YounGo Paymob setup page.
- The legacy payment gateway page must not be used for YounGo Paymob configuration.
- YounGo Paymob must not be added as a legacy `payment_gateways` row.
- Private Paymob values are not saved to DB in the current phase.
- Sandbox network testing remains blocked until an explicit approved QA phase.

## Client-Facing Checklist

Please prepare the following in Paymob before sandbox payment testing can continue.

### 1. Paymob Account

- Create or activate a Paymob account.
- Confirm sandbox/testing access is available.
- Confirm the account is suitable for EGP card payment testing.

### 2. EGP Card Integration

- Create or confirm an EGP card integration in the Paymob dashboard.
- Provide the field name only listed below through the approved secure channel:
  - `card_integration_id_egp`

Do not send this value in screenshots, public chat, Git, or project reports.

### 3. Return URL Requirements

YounGo needs a return URL for learner browser return after Paymob checkout.

Important:

- Return URL is for user experience only.
- Return URL must not be treated as proof of payment.
- Payment status will be verified through Paymob notification/webhook and HMAC validation.

Field name:

- `return_url`

Expected shape:

```text
<base-url>/youngo/checkout/return/{order_reference}
```

The final base URL depends on whether testing runs locally with a tunnel, on staging, or on another approved sandbox URL.

### 4. Notification/Webhook URL Requirements

YounGo needs a Paymob payment notification URL for server-to-server payment status delivery.

Field name in the YounGo dashboard:

- `notification_url`

Equivalent server-config alias:

- `webhook_url`

Expected shape:

```text
<public-https-base-url>/payment/paymob/webhook
```

Important:

- Local `school.local` is not publicly reachable by Paymob.
- Real webhook testing requires a public HTTPS tunnel or approved staging/sandbox URL.
- HMAC verification remains required.

### 5. Dashboard Non-Private Values

The following values are entered in the YounGo dashboard by Root Admin at:

```text
/admin/youngo/payment-settings
```

Fields:

- `mode`
- `currency`
- `amount_multiplier`
- `card_integration_id_egp`
- `api_base_url`
- `checkout_base_url`
- `return_url`
- `notification_url`

Expected fixed values for the current sandbox phase:

- `mode`: sandbox
- `currency`: EGP
- `amount_multiplier`: 100

The remaining URL/integration fields must come from the Paymob sandbox setup and the approved YounGo test URL strategy.

### 6. Private Values

The following private Paymob values must be provided only through a secure private channel:

- `public_key`
- `secret_key`
- `hmac_secret`

Do not send private values in:

- screenshots;
- reports;
- Git commits;
- Git branches;
- SQL files;
- public chat;
- issue comments;
- shared documents without access control;
- browser screenshots;
- diagnostic output.

Private values are currently placed only in ignored local/server config by a developer/server admin.

## Internal Developer Checklist

Before running any sandbox Paymob QA:

1. Confirm the worktree is clean.
2. Confirm `application/config/youngo_paymob.local.php` is ignored by Git.
3. Enter non-private values in `/admin/youngo/payment-settings`.
4. Place private values only in ignored local/server config.
5. Confirm private values are detected only as redacted presence states.
6. Confirm readiness page shows the required dashboard fields and server-config private presence.
7. Confirm production defaults remain disabled.
8. Confirm no public checkout CTA is exposed unless a later local CTA QA phase explicitly enables it.
9. Run `PAYMENT.PAYMOB.SANDBOX.CONFIG.INPUT.QA.1` only after required values exist.
10. Do not run sandbox Intention/network QA until a later explicit execution phase approves it.

## Internal Safety Checks

Required before `PAYMENT.PAYMOB.SANDBOX.CONFIG.INPUT.QA.1`:

- No Paymob values in Git.
- No private values in DB.
- No private values in reports.
- No private values in diagnostics.
- No `encryption_key` change.
- No Paymob legacy gateway row.
- No legacy `payment_gateways` dependency for YounGo Paymob.
- No legacy `payment` or `enrol` writes.
- No production payment enablement.
- No production checkout CTA exposure.

## Recommended Next Step

Recommended next phase:

```text
PAYMENT.PAYMOB.SANDBOX.CONFIG.INPUT.QA.1
```

Purpose:

- confirm the non-private dashboard values can be entered safely;
- confirm ignored server-config private values are present only as redacted status;
- confirm readiness changes without printing private values;
- keep sandbox execution disabled until the next approved network execution phase.

