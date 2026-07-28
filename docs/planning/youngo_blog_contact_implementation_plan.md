# YounGo Blog and Contact Implementation Plan

DEMO.4B.2 planning report. This plan uses the read-only audit in `docs/qa/youngo_blog_contact_existing_system_audit.md` as its source input.

## 1. Executive Summary

The fastest demo-safe path is to reuse the existing Academy LMS Blog and Contact systems and add YounGo theme views around them. The client demo should stop exposing the current YounGo skeleton placeholder on Blog and Contact routes, but this phase should not create blog data, submit contact forms, add payment behavior, or rebuild CMS features that already exist.

Recommended Blog path: reuse `Blog.php`, existing blog tables, admin screens, and default-new view behavior; create YounGo blog views that render real posts when available and show a polished empty state when local data is empty.

Recommended Contact path: reuse `Home::contact_us` and `frontend_settings.contact_info`; create a YounGo contact page that displays contact details and uses a static or disabled message block for the demo. A live POST form should be deferred unless the contact submit flow is explicitly hardened and QA-tested.

## 2. Blog Implementation Recommendation

Use the existing Blog controller and data model, then add YounGo theme views.

The current reusable Blog controller paths are:

- `Blog::index()` for `/blog`.
- `Blog::blogs()` for `/blogs` and `/blogs/{category_slug}`.
- `Blog::categories()` for `/blog/categories`.
- `Blog::details($slug, $id)` for `/blog/details/{slug}/{id}`.

The implementation should create YounGo views that are compatible with the controller data already being passed today:

- `application/views/frontend/youngo/blogs.php`.
- `application/views/frontend/youngo/blog_details.php`.
- Optional partials such as `application/views/frontend/youngo/blog_card.php` or a YounGo blog sidebar partial if that keeps the view maintainable.
- Optional `application/views/frontend/youngo/blog_categories.php` only if the implementation chooses a dedicated category view instead of branching inside `blogs.php`.

The existing `/blog`, `/blogs`, and `/blog/categories` paths should resolve to YounGo views automatically once the active `youngo` theme has the matching view files. Because the current `Blog::categories()` flow uses `page_name = blogs` with an `included_page` value, the least invasive implementation is for `youngo/blogs.php` to understand the existing `included_page` variants and render the correct listing, category, latest, or fallback state.

Local blog content is currently empty (`blogs = 0`, `blog_category = 0`, `blog_comments = 0`), so the view must not require seeded data for the demo. When posts exist, render real posts, thumbnails, titles, publish metadata, excerpts, category labels, and detail links from the existing data. When no posts exist, render a polished YounGo empty state.

## 3. Contact Implementation Recommendation

Use the existing `Home::contact_us` action and existing contact settings, then add a YounGo theme view.

The implementation should create:

- `application/views/frontend/youngo/contact_us.php`.

The existing `/home/contact_us` URL should begin rendering a real YounGo page once this view exists. A clean `/contact` alias can be added in the implementation phase if the demo navigation should use a shorter public URL. Arabic `/ar/contact` should only be added after the YounGo contact view exists and has been smoke-tested in the English route.

For the fast client demo, the page should display contact details from existing settings and avoid a live POST form. A visual-only message block or clear "contact us by email" CTA is safer than enabling form submission before CSRF, validation, captcha, admin inbox, and mail behavior are fully tested.

## 4. Existing Reusable System Pieces

Reusable Blog pieces:

- Controller: `application/controllers/Blog.php`.
- Tables: `blogs`, `blog_category`, `blog_comments`.
- Admin surfaces for blog, category, and blog settings.
- Existing public routes by controller convention and configured `/blogs` route.
- Default-new frontend views that can be used as behavior references.
- Existing YounGo homepage blog preview content and assets for empty-state fallback.

Reusable Contact pieces:

- Controller/action: `Home::contact_us`.
- Table: `contact`.
- Setting source: `frontend_settings.contact_info`.
- Admin contact inbox and reply flow.
- Existing SMTP mail support for admin replies.
- Existing contact validation logic, captcha settings, and flash-message flow for a future live form.

Reusable YounGo frontend pieces:

- Active theme shell in `application/views/frontend/youngo`.
- Existing YounGo homepage `blog_preview` section data.
- Existing image assets such as `assets/frontend/youngo/images/blog-family.webp`, `blog-coding.webp`, and `blog-science.webp`.
- DEMO.4A route-aware language helper and local YounGo phrase map.

## 5. New YounGo Views Needed

Required Blog views:

- `application/views/frontend/youngo/blogs.php`: main blog listing, category/list variants, and zero-post empty state.
- `application/views/frontend/youngo/blog_details.php`: single post detail view when real posts exist.

Optional Blog views or partials:

- `application/views/frontend/youngo/blog_categories.php`: only if implementation prefers a dedicated category view.
- `application/views/frontend/youngo/blog_card.php`: reusable card markup for real posts and fallback cards.
- `application/views/frontend/youngo/blog_sidebar.php`: only if categories/latest posts are useful and data exists.

Required Contact view:

- `application/views/frontend/youngo/contact_us.php`: contact details, social links, working hours, and static/display-only message area.

## 6. Route Changes Needed

No route changes are made in this planning phase.

Future route policy:

- English canonical frontend routes remain unprefixed/current routes.
- Arabic routes use `/ar/...`.
- Do not add `/en`.

Recommended future route changes:

- Keep `/blog` as the main English Blog route through `Blog::index()`.
- Keep `/blogs` and `/blogs/{category_slug}` as compatibility/listing routes.
- Keep `/blog/categories` if the page has a useful category state.
- Keep `/blog/details/{slug}/{id}` for real blog posts.
- Add `/contact` as a clean alias to `home/contact_us` if demo navigation should use `/contact`.
- Add `/ar/blog` only after the YounGo Blog view exists.
- Add `/ar/contact` only after the YounGo Contact view exists.

Arabic blog detail/category routes can be deferred until there is real blog data and a detail page QA path.

## 7. Header/Footer Link Changes Needed

After the views exist, update YounGo navigation links so no demo link reaches the skeleton page.

Header plan:

- English Blog link points to the working `/blog` route.
- English Contact link points to the chosen working contact route, preferably `/contact` if that alias is added, otherwise `/home/contact_us`.
- Arabic Blog link points to `/ar/blog`.
- Arabic Contact link points to `/ar/contact`.
- Route-aware helpers should preserve the current language context where possible.
- No header link should point to `/en`.

Footer plan:

- Apply the same route choices as the header.
- Ensure any footer Blog or Contact links use the same route-aware helper behavior.
- Remove or avoid links to route variants that still render skeletons or app 404 pages.

## 8. Phrase and Local-Map Needs

Use the DEMO.4A route-aware phrase helper for UI labels. For demo-critical labels, prefer adding missing keys to the local YounGo frontend phrase map during implementation instead of relying on uncertain DB phrase rows.

Candidate keys:

- `blog`
- `blogs`
- `latest_articles`
- `helpful_notes_for_families`
- `no_blog_posts_yet`
- `contact_us`
- `get_in_touch`
- `send_us_a_message`
- `email`
- `phone`
- `address`
- `working_hours`
- `follow_us`
- `message`
- `name`
- `subject`
- `read_more`
- `coming_soon`
- `contact_us_by_email`
- `browse_courses`

No phrase seeding is part of this planning phase.

## 9. Contact Form Decision

Choose Option A for the fast client demo.

Option A: create a static contact page that displays contact details, working hours, social links, and either a disabled visual message block or a "contact us by email" CTA.

Do not enable a live public form for the fast demo unless a separate QA step verifies:

- The action is POST-only or otherwise guarded safely.
- Validation works for required fields and email.
- Captcha behavior is understood and configured.
- Admin inbox receives the message.
- Mail sending behavior is safe in the local/demo environment.
- Success and failure phrases render cleanly in English and Arabic.
- Test contact rows are cleaned up through a backup/restore or explicitly approved cleanup process.

The audit found the existing contact submit path is real, but it is not the safest fast-demo choice because CSRF is globally disabled and the current submit flow needs dedicated browser QA before exposing it.

## 10. Arabic/English Behavior

English behavior:

- Keep current unprefixed public routes.
- Use existing Academy Blog and Contact data.
- Fall back to YounGo empty-state content when there are no posts.

Arabic behavior:

- Use `/ar/blog` and `/ar/contact` only after the YounGo views exist.
- Do not add `/en`.
- Use route-aware phrase helpers for Arabic UI labels.
- Existing blog/contact content settings may be English-only; clean English fallback is acceptable for the demo where Arabic-specific content does not exist.
- Blog records do not currently have a YounGo bilingual blog translation model. Do not invent one for this demo step.
- Contact settings do not currently have separate Arabic fields. Do not add schema for that in this demo step.

## 11. Empty Blog-State Design

The zero-post Blog page should feel intentional, not broken.

Recommended content layout:

- Page title: `Helpful notes for families`.
- Short parent-facing subtitle about tips, updates, and learning ideas.
- Three polished fallback cards based on existing homepage preview content:
  - `Simple ways to keep kids engaged`
  - `Creative coding starts with curiosity`
  - `Safe science activities at home`
- Use existing YounGo images from the homepage preview.
- Avoid fake author/date metadata if no real post exists.
- Avoid detail links for fallback cards unless they point to a real route.
- Include a modest CTA to browse courses or contact the team.

When real posts are later added through the admin dashboard, the same view should switch to real blog cards automatically.

## 12. Contact Page Content Layout

Recommended contact layout:

- YounGo page header with `Contact us` / `Get in touch`.
- Contact detail blocks:
  - email
  - phone
  - address
  - working hours
- Social links from `frontend_settings.contact_info` where configured.
- A static message area or disabled form-style block for visual completeness.
- A clear email CTA using the configured contact email.
- Optional map/location area only if a valid map embed or location setting exists. Do not fabricate a map.

Known usable setting fields from the audit:

- Email
- Phone
- Address
- Office hours
- Social links: Facebook, Twitter, LinkedIn where configured

Fallback setting sources can include core `settings` values such as system name, system email, phone, and address if the frontend contact settings are incomplete.

## 13. Risks and Blockers

- Blog data is currently empty, so the demo depends on a strong empty state unless content is later added through admin tools.
- The existing Blog detail route should be QA-tested with a real post before linking fallback cards to detail pages.
- `Blog::categories()` may require the YounGo `blogs.php` view to understand existing `included_page` values.
- `/contact`, `/contact-us`, `/ar/blog`, and `/ar/contact` are not currently configured.
- Contact submit is a real write path and should stay disabled for the fast demo unless explicitly QA-tested.
- Contact settings are likely English-only.
- Arabic blog/contact content fallback is acceptable for demo, but it is not a full bilingual content solution.
- DB Arabic phrases have known quality/corruption concerns, so demo-critical phrases should use the local YounGo phrase map.

## 14. Fast Implementation Checklist

1. Create `application/views/frontend/youngo/blogs.php` with real-post rendering and zero-post fallback.
2. Create `application/views/frontend/youngo/blog_details.php` for real post detail pages.
3. Create `application/views/frontend/youngo/contact_us.php` using existing contact settings.
4. Add only necessary local YounGo phrase-map keys.
5. Add route-aware helper mappings for Blog and Contact.
6. Add `/contact` alias only if the demo needs a clean contact URL.
7. Add `/ar/blog` and `/ar/contact` only after English views render correctly.
8. Update YounGo header/footer links to route-aware Blog and Contact URLs.
9. Leave contact form posting disabled or visual-only for the demo.
10. Run GET-only browser QA for English and Arabic routes.

## 15. QA Checklist

GET-only route checks:

- `/blog`
- `/blogs`
- `/blog/categories`
- `/home/contact_us`
- `/contact` if alias is added
- `/ar/blog` if alias is added
- `/ar/contact` if alias is added

Visual checks:

- No `YOUNGO THEME SKELETON` copy appears.
- Blog empty state is polished when `blogs = 0`.
- Real post rendering works if posts are later added through admin.
- Contact details render from existing settings.
- Arabic shell direction and labels render correctly.
- Header/footer links do not point to missing or skeleton routes.
- No `/en` links appear.

Safety checks:

- No contact form submission occurs during static demo QA.
- No DB rows are inserted by GET requests.
- No payment, Paymob, checkout, order, cart, coupon, or subscription purchase files are changed.
- No Root Admin credentials or user fields are changed.

Optional live-contact QA, only if separately approved:

- Backup database first.
- Submit through browser once.
- Verify `contact` table/admin inbox behavior.
- Verify captcha and validation behavior.
- Verify mail behavior is safe.
- Restore cleanup after the test.

## 16. What Is Explicitly Deferred

- Building pages in this planning phase.
- Creating or seeding blog posts through SQL.
- Creating Arabic blog/content storage.
- Creating Arabic contact setting fields.
- Enabling a live contact form for the fast demo.
- Blog comment UI and comment submission.
- `/ar/blog/details/{slug}/{id}` and `/ar/blog/categories` unless real blog detail/category QA is needed.
- `/en` routes.
- Paymob.
- Payment, checkout, cart, coupon, order, or subscription purchase work.
- Admin dashboard redesign.
- Database schema changes.
- Root Admin credential or user-field changes.

