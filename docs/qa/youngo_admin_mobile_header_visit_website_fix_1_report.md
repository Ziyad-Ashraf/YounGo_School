# ADMIN.MOBILE.HEADER.VISIT.WEBSITE.FIX.1 Report

## A. Result

Implemented the backend/admin mobile header fix. The existing desktop `Visit website` button remains unchanged, and the existing Quick actions dropdown now includes a `Visit website` action that is visible and usable on mobile and desktop.

## B. Folder used

`D:\Work\YounGo\school`

## C. Files changed

- `application/views/backend/header.php`
- `assets/backend/css/main.css`
- `assets/backend/css/rtl.css`
- `docs/qa/youngo_admin_mobile_header_visit_website_fix_1_report.md`

## D. Desktop header result

The desktop `Visit website` button remains the existing text button beside the dashboard logo/system name area. The Quick actions dropdown now also includes a `Visit website` copy on desktop.

Authenticated desktop QA at 1365px confirmed:

- Desktop Visit website button visible.
- Quick actions Visit website item visible when the apps/Quick actions menu is opened.
- Link target remains `/home`.
- No horizontal overflow.
- No PHP warnings/notices.

## E. Mobile header result

Removed the standalone mobile Visit icon from the header because it looked awkward in the compact top bar.

Added `Visit website` as a normal item inside the existing Quick actions dropdown. Mobile CSS keeps hidden dropdown menus from causing horizontal overflow and preserves compact header spacing around language, apps, help, notifications, user profile, and sidebar menu controls.

## F. Visit website link result

The Quick actions Visit website item uses the same link source as desktop:

`site_url('home')`

Authenticated click QA at 390px confirmed the Quick actions item navigates from `/admin/dashboard` to `/home`.

## G. RTL/Arabic result

RTL-specific spacing was added in `assets/backend/css/rtl.css`. Authenticated RTL mobile QA confirmed the Quick actions dropdown stays inside the viewport and the Visit website item is visible.

English/LTR mobile QA also passed after switching the dashboard session language to English.

## H. QA result

Authenticated browser QA was run locally on `/admin/dashboard`.

Mobile widths checked:

- 360px
- 390px
- 430px

Results:

- Visit website visible inside Quick actions on mobile.
- Standalone mobile Visit icon removed.
- Existing desktop Visit button still visible at desktop width.
- Visit website also visible inside Quick actions at desktop width.
- Visit link points to `/home`.
- Mobile click opens the public website.
- No horizontal overflow after the dropdown overflow guard.
- No broken header overlap detected.
- No PHP warnings/notices detected.

Safety:

- `php -l application/views/backend/header.php` passed.

## I. Remaining issues

No remaining issues found for this scoped fix.

## J. Files to upload manually

- `application/views/backend/header.php`
- `assets/backend/css/main.css`
- `assets/backend/css/rtl.css`
- `docs/qa/youngo_admin_mobile_header_visit_website_fix_1_report.md`
