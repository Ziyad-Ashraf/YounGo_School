# FRONTEND.QUICK.CLIENT.FIXES.2 Report

## A. Result

Implemented the quick client-facing fixes locally for signup phone validation, email/phone login, Website Settings WhatsApp contact data, frontend WhatsApp rendering, and scoped mobile CSS polish.

## B. Folder Used

`D:\Work\YounGo\school`

## C. Files Changed

- `application/controllers/Login.php`
- `application/helpers/common_helper.php`
- `application/models/Crud_model.php`
- `application/models/User_model.php`
- `application/views/backend/admin/frontend_settings.php`
- `application/views/frontend/youngo/contact_us.php`
- `application/views/frontend/youngo/footer.php`
- `application/views/frontend/youngo/index.php`
- `application/views/frontend/youngo/login.php`
- `application/views/frontend/youngo/sign_up.php`
- `assets/frontend/youngo/css/youngo.css`
- `docs/qa/youngo_frontend_quick_client_fixes_2_report.md`

## D. Website Settings WhatsApp Field Result

Added `whatsapp_number` inside the existing `frontend_settings.contact_info` JSON setting.

The field appears in Website Settings > Contact information near Phone Number, saves through `Crud_model::update_contact_info()`, and was verified to persist after reload.

## E. Contact Us WhatsApp Result

Active route/view confirmed: `/contact` and `/home/contact_us` route to `Home::contact_us()` and render `application/views/frontend/youngo/contact_us.php`.

The contact page now reads contact details from `frontend_settings.contact_info`. WhatsApp is shown only when configured and links to `https://wa.me/` with spaces, plus signs, dashes, brackets, and other non-digits removed for the link.

## F. Floating WhatsApp Button Result

Added a shared floating WhatsApp button in the YounGo footer.

It renders only when `contact_info.whatsapp_number` is present, opens the normalized `wa.me` URL in a new tab, uses Font Awesome's WhatsApp icon, and has mobile spacing so it is less likely to cover bottom CTAs.

## G. Signup Phone-Required Result

The signup form now shows Phone number as a normal required field.

Server-side registration now rejects missing phone and stores submitted phone in `users.phone`. Existing instructor application signup still uses the same posted phone value.

## H. Email/Phone Login Result

Login label changed to `Email or phone number`.

Email login continues through the existing `users.email + password + status` path. Phone login normalizes entered and saved phone values by removing non-digits, then uses the same password hash check and existing session setup. Duplicate active phone matches fail safely with a message asking the user to log in by email.

## I. Mobile Fixes Summary

Added scoped responsive CSS for:

- horizontal overflow prevention;
- mobile-safe cards, panels, and media;
- single-column action groups on small screens;
- cart row wrapping;
- course/subscription/contact/checkout panel width constraints;
- floating WhatsApp spacing on mobile.

No checkout, payment, Paymob, Instapay, cart, coupon, or entitlement business logic was changed.

## J. Browser Test Result

The in-app browser target was unavailable in this session, so pixel-level browser QA could not be completed.

Local HTTP/runtime checks passed on `http://127.0.0.1:8090`:

- admin Contact information field appears;
- WhatsApp setting saves and persists;
- Contact page includes WhatsApp display and normalized link;
- homepage includes the floating WhatsApp button;
- signup without phone creates no user row;
- signup with phone creates a temporary QA user with phone saved;
- email login works for the temporary active QA user;
- phone login works for the temporary active QA user;
- wrong phone/password fails via login refresh;
- duplicate phone login fails safely;
- temporary QA users were deleted;
- public mobile-smoke route responses returned expected YounGo pages for homepage, course listing, course detail, subscriptions, shopping cart, login, signup, contact, and wishlist.

Checkout start route returned the expected unauthenticated refresh-style response and was not modified.

## K. DB Changes, If Any

No schema changes.

The existing `frontend_settings.contact_info` JSON was updated through the admin settings flow to include `whatsapp_number`. Existing documented contact placeholder values were restored after QA, with the new WhatsApp test value kept for local verification.

Temporary `quickfix2.%@youngo.local` signup QA users were created only for testing and deleted. Final temporary QA user count is `0`.

## L. Safety Result

PHP lint passed for every changed PHP file.

`scripts/phase_2/youngo_phase_2s_route_cta_boundary_diagnostic.php` passed, with current local data warnings unrelated to this sprint.

`scripts/phase_2/youngo_phase_2u6_language_switcher_rtl_diagnostic.php` was attempted but failed on unrelated localization expectations and because this sprint intentionally changed admin/frontend shell/helper files. This quick fix did not attempt to repair localization diagnostics.

## M. Remaining Issues

- In-app browser/pixel-level mobile visual QA was unavailable.
- The local app still logs the existing PHP 8.4 deprecation warning for `E_STRICT` in `index.php`.
- The local WhatsApp number is a test setting value and should be replaced by the client-approved WhatsApp number before production upload.
