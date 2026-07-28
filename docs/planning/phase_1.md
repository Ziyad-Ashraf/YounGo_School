# Phase 1 Plan - YounGo Frontend and CMS Content Foundation

## Current Status

This document is an earlier Phase 1 planning reference. The current master plan and source of truth for project priorities, architecture, reuse decisions, deployment preparation, and implementation sequencing is:

[`youngo_master_plan_v2.md`](./youngo_master_plan_v2.md)

All implementation work must be reviewed against the master plan before modifying or rebuilding existing Academy LMS functionality.

Where this document conflicts with the master plan, the master plan supersedes it. In particular, the master plan records completed public frontend/homepage work and shifts the current execution priority toward Academy LMS reuse, course-creation readiness, theme media compatibility, deployment preparation, and client testing.

Academy LMS must be reused as the core system whenever possible. Existing working LMS functionality must not be rebuilt unless reuse is proven impractical.

## 1. Purpose of This Plan

Phase 1 should create the first client-visible YounGo experience without replacing the existing Academy LMS / CodeIgniter CMS. The purpose is to establish a dedicated YounGo frontend theme and the minimum admin content-management layer needed for the homepage to be editable from the dashboard.

This phase is intentionally practical. It should produce a polished public website foundation quickly, while preserving existing LMS course, category, blog, login, cart, wishlist, and admin behavior.

## 2. Roadmap Context

Phase 1 sits after the initial direction-setting and planning work, and before broader CMS expansion. The current roadmap context is:

```text
Design lock -> repo/reference docs -> CLI/MCP setup -> analysis/planning -> Phase 1 implementation -> CMS expansion -> Playwright testing/polish
```

This plan covers the analysis/planning output for the first implementation phase. It should not be treated as a full product roadmap or final database design.

## 3. Phase 1 Goal

- Add a dedicated YounGo frontend theme foundation that follows the approved soft purple, rounded, parent-trust visual direction.
- Implement the homepage in maintainable CodeIgniter views and local YounGo assets, using Stitch outputs only as visual references.
- Reuse existing LMS data for courses, categories, blogs, FAQs, contact info, ratings, and course details where practical.
- Add a focused admin-side homepage content manager for key homepage sections, content, visibility, ordering, and status in a simple form-based workflow.
- Keep the old `default-new` frontend and existing CMS dashboard functionality intact.
- Validate the homepage, course listing/detail, navigation, and admin content flow with basic browser checks once runtime setup is confirmed.

## 4. Inputs Read

- `YOUNGO_PROJECT_CONTEXT.md`
- `AGENTS.md`
- `docs/design/youngo_style_direction.md`
- `docs/agents/implementation_rules.md`
- `docs/agents/codex_analysis_prompt.md`
- `docs/reference/README.md`
- `docs/reference/cms_documentation/`
- `docs/reference/stitch_outputs/html/`
- `docs/reference/stitch_outputs/images/`
- Relevant CodeIgniter controllers, models, views, helpers, routes, assets, admin navigation, frontend settings, homepage builder, and schema locations.

## 5. Repository Findings Summary

The application is a PHP CodeIgniter MVC project. Important paths are:

- `application/controllers/`
- `application/models/`
- `application/views/`
- `application/config/`
- `assets/`
- `uploads/`

Frontend theme loading is centralized around `get_frontend_settings('theme')`. Public routes in `Home.php`, `Blog.php`, `Login.php`, `Page.php`, and related controllers commonly load:

```text
frontend/{active_theme}/index
```

The current frontend theme is `default-new`, with corresponding files under:

```text
application/views/frontend/default-new/
assets/frontend/default-new/
```

The frontend index view includes shared SEO, header, footer, assets, modals, scripts, and then includes the active `$page_name`. This means a new `application/views/frontend/youngo/index.php` can follow the same loading contract while using YounGo-specific partials and assets.

The default homepage flow uses `Home.php` to read the active row from `home_pages`. If the active home page is permanent, it chooses a file name from `html_file_names`; otherwise it uses `home_builder`. The current `home.php` view also includes the selected `get_frontend_settings('home_page')` file. The existing homepage builder stores records in `home_pages` and can write generated component files under `application/views/components/builder/`.

Existing reusable CMS/LMS content includes:

- Courses from `course`
- Categories and subcategories through existing course/category model methods
- Course details, lessons, curriculum, instructor, reviews, FAQs, related courses, and custom course fields
- Blogs and blog categories
- Custom pages
- Website FAQs stored in `frontend_settings` as JSON under `website_faqs`
- Contact information stored in `frontend_settings` as JSON under `contact_info`
- Logos, favicon, banner images, social links, and general frontend settings
- Reviews/ratings through the existing `rating` table

The admin dashboard uses `application/views/backend/index.php`, which includes role-based navigation and the active backend view by `$page_name`. Admin screens are mostly in `application/views/backend/admin/`, and `Admin.php` is a large controller that already manages settings, courses, blogs, custom pages, themes, and the homepage builder.

Schema/storage findings are provisional. A schema file exists at `uploads/install.sql`, and implementation planning must verify that file and any other actual schema locations before finalizing storage decisions. Database changes should therefore be planned cautiously and require approval before implementation.

## 6. Phase 1 Scope

Phase 1 includes the following work.

New YounGo frontend/theme structure:

- Create a separate YounGo frontend view theme under `application/views/frontend/youngo/`.
- Create matching YounGo frontend assets under `assets/frontend/youngo/`.
- Keep `default-new` available as a reference and fallback.
- Follow the existing `frontend/{theme}/index` loading pattern.
- Build the YounGo theme side-by-side first. Do not switch the active frontend theme in production until preview/review is approved.

Homepage implementation direction:

- Build a YounGo homepage using maintainable CodeIgniter views and section partials.
- Convert the Stitch homepage direction into local CSS, simple JavaScript, local/CMS images, and CMS-driven content.
- Include hero, featured categories, featured courses, why choose YounGo, about teaser, testimonials, FAQ preview, blog preview, and final CTA.
- Keep the visual layout fixed in the theme; admin controls should manage content, not visual styling.

Course listing/details direction:

- Restyle course listing and course details views inside the YounGo theme.
- Reuse the existing `Home::courses()` filtering, pagination, and course data flow.
- Reuse `Home::course()` and existing course data for title, description, thumbnail, instructor, curriculum, reviews, price/enrollment action, related courses, and additional course fields.
- Shape the course details page for parents: clear outcomes, age/skill metadata where available, curriculum, instructor trust, reviews, and related courses.

Header/footer/navigation direction:

- Implement a YounGo header and footer as theme partials.
- Reuse existing login, sign up, cart, wishlist, profile, courses, blog, custom pages, FAQ, and contact URLs.
- Keep navigation simple and parent-friendly.
- Use CMS-managed custom pages/menu-adjacent content where practical, but do not build a full navigation builder in Phase 1 unless approved separately.

Use of existing LMS data:

- Use active courses and categories for public course discovery.
- Use existing blogs for the blog preview.
- Use existing website FAQs and contact info where they fit.
- Use existing ratings/reviews if enough data exists; otherwise allow CMS-managed testimonial entries through the Phase 1 homepage content data.

Minimum CMS homepage content management foundation:

- Add a YounGo-focused homepage content manager in the existing admin dashboard.
- Support editing key text, image references/uploads, CTA labels/URLs, selected featured categories/courses, testimonials, FAQ preview selection/content, blog preview behavior, final CTA, section visibility, section ordering, and publish/status where feasible.
- Keep the admin UI simple, form-based, and content-focused.

Admin-side content editing approach:

- Follow existing backend page patterns: controller method, `$page_name`, backend admin view, form posts, flash messages, redirects, permission checks, and existing upload conventions.
- Add a navigation entry in the admin sidebar under an appropriate website/content/settings area.
- Avoid embedding complex builder behavior into the first YounGo manager.

Basic preview/testing considerations:

- Provide a preview path or clear way to view the YounGo homepage after saving content.
- Verify homepage load, course list, course details, blog preview links, FAQ/contact content, admin content screen, and responsive layout through browser or Playwright checks when the local runtime is available.

## 7. Internal Phase 1 Milestones

Phase 1 should be implemented in internal milestones so the work stays reviewable.

Phase 1A: YounGo theme skeleton

- Create the side-by-side `youngo` frontend theme folder and asset folder.
- Add the base layout, includes, header/footer placeholders, and required page-view contract.
- Confirm the theme can exist without disrupting `default-new`.

Phase 1B: Homepage visual implementation

- Convert the approved homepage direction into YounGo views and local assets.
- Build fixed visual sections for hero, categories, courses, trust/benefits, about teaser, testimonials, FAQ preview, blog preview, and final CTA.
- Use static safe defaults until CMS content is connected.

Phase 1C: CMS homepage content manager

- Add the minimal admin content-management workflow for homepage sections.
- Support section content, visibility, ordering, and at least one clear active/published state.
- Document the JSON shape or approved storage shape before coding this milestone.

Phase 1D: Course listing/details styling

- Apply the YounGo visual direction to course listing and course details pages.
- Reuse existing LMS data and actions.
- Keep enrollment, cart, wishlist, reviews, curriculum, and related-course behavior intact.

Phase 1E: Browser/Playwright checks

- Verify homepage, course listing, course details, navigation, admin content editing, save-to-frontend behavior, and responsive layout.
- Record any environment limitations if local database/runtime setup is incomplete.

## 8. Phase 1 Non-Scope

Phase 1 intentionally excludes:

- A full generic page builder.
- A theme marketplace or focus on old prebuilt themes as a product feature.
- Full redesign or replacement of the old admin dashboard.
- Rewriting LMS core controllers, models, course logic, checkout, cart, wishlist, enrollment, authentication, or lesson playback.
- A full advanced SEO management system, beyond minimal title/description fields if needed for the homepage.
- Complex drag-and-drop building. Simple section ordering is acceptable if implemented as numeric order fields or a lightweight sortable list.
- Mobile app or API changes.
- Broad dependency changes or dependency installation.
- Database structure changes without separate approval.
- Removing existing `default-new` theme files or old homepage builder functionality.
- Switching the active production frontend theme before preview/review approval.

## 9. Recommended File/Folder Changes

These are likely implementation targets for Phase 1. This plan does not include implementation code.

Frontend theme files:

- Add `application/views/frontend/youngo/index.php`
- Add `application/views/frontend/youngo/header.php`
- Add `application/views/frontend/youngo/footer.php`
- Add `application/views/frontend/youngo/includes_top.php`
- Add `application/views/frontend/youngo/includes_bottom.php`
- Add `application/views/frontend/youngo/home.php`
- Add homepage section partials under a YounGo-only folder, such as `application/views/frontend/youngo/partials/home/`
- Add or adapt YounGo views for `courses_page.php`, course card/list partials, `course_page.php`, `blogs.php`, `blog_details.php`, `custom_page_viewer.php`, `website_faq.php`, `contact_us.php`, and basic account-facing pages only where needed to keep navigation coherent.

Frontend assets:

- Add `assets/frontend/youngo/css/`
- Add `assets/frontend/youngo/js/`
- Add `assets/frontend/youngo/images/`
- Add `assets/frontend/youngo/preview.png` if the existing theme settings UI needs a theme preview.
- Prefer local CSS and minimal JavaScript. Avoid relying on prototype CDN code from Stitch exports.

Backend/admin files:

- Add a YounGo homepage manager view under `application/views/backend/admin/`, for example `youngo_homepage.php`.
- Add focused modal/partial views only if the existing backend form pattern needs them.
- Update `application/views/backend/admin/navigation.php` to expose the YounGo homepage manager after implementation approval.

Controller/model/helper areas:

- Add a small, focused admin controller method or carefully scoped methods in `Admin.php` only if consistent with the existing project pattern.
- Prefer moving reusable data access into `Crud_model.php` or a YounGo-specific model only if the implementation grows beyond simple settings reads/writes.
- Add helper methods for reading YounGo homepage content only if this reduces repeated JSON parsing and fallback handling.
- Avoid changing unrelated public flows in `Home.php`; add only the minimum needed to load YounGo homepage content when the YounGo theme is active.

Reference docs only if needed:

- Keep Stitch and CMS docs in `docs/reference/`.
- Do not copy prototype HTML into production views.
- Do not move planning details into design or reference documents.

## 10. CMS Content Management Plan

Minimum CMS-managed homepage content should include:

- Hero: eyebrow/label, headline, supporting text, primary CTA label/link, secondary CTA label/link, hero image, optional trust/stat badges.
- Featured categories: selected category IDs or auto mode, section title/subtitle, count/limit, visibility.
- Featured courses: selected course IDs or auto mode, section title/subtitle, count/limit, visibility.
- Why choose YounGo: section title/subtitle and a small set of benefit cards with title, description, icon key or image.
- About teaser: title, body copy, image, CTA label/link.
- Testimonials: quote, name, role/context, rating, image/avatar where available, status.
- FAQ preview: selected existing website FAQs or YounGo-specific homepage FAQ entries, section title/subtitle, link to full FAQ page.
- Blog preview: latest active blogs by default, with optional section title/subtitle and count.
- Final CTA: headline, supporting text, CTA label/link, optional background image.
- Section visibility: per-section enable/disable.
- Section ordering: simple sort order for homepage sections.
- Draft/publish or status handling: at minimum, support an active/published status for the YounGo homepage content set; full draft/revision handling can be deferred.

Existing `frontend_settings` can already store simple key/value content and JSON blobs. It currently stores items such as `website_faqs`, `contact_info`, banner text, blog settings, logos, and section toggles. This makes it a viable short-term storage mechanism for a single YounGo homepage configuration if Phase 1 needs the smallest database impact.

Existing `custom_page` can handle simple pages, but it is not a good fit for structured homepage sections, selected courses/categories, section ordering, or repeatable testimonials/benefits.

Existing `home_pages` and the current homepage builder support activating home pages and generated builder components, but the builder is broader and more implementation-heavy than the approved YounGo direction. It also focuses on editable page structure rather than a fixed YounGo theme with controlled content fields.

Recommendation: reuse existing CMS data for courses, categories, blogs, FAQs, contact info, and reviews. For YounGo-specific homepage content, start with either a single `frontend_settings` JSON key or a small dedicated YounGo content structure after approval. Do not extend the old generic builder as the main Phase 1 solution unless the implementation team explicitly decides to accept its complexity.

## 11. Data Storage Recommendation

Use a cautious and provisional storage path. The final storage decision should not be treated as settled until the actual available schema files, including `uploads/install.sql`, have been reviewed.

Preferred Phase 1 low-risk option:

- Store the YounGo homepage configuration as JSON in `frontend_settings`, using a clearly named key such as `youngo_homepage_content`.
- Store section order, visibility, content fields, selected category IDs, selected course IDs, testimonial entries, CTA fields, and status in that JSON payload.
- Reuse existing media upload conventions, likely storing uploaded filenames/paths in the JSON value while keeping files under an approved upload location.
- Document the exact JSON shape before coding begins, including section keys, required fields, optional fields, defaults, active/published state, and migration/fallback behavior.

Tradeoffs of the JSON-in-`frontend_settings` approach:

- Pros: minimal database impact, consistent with existing FAQ/contact/frontend setting patterns, quick to implement, easy to roll back.
- Cons: less queryable, less suitable for many versions, less clean if homepage content becomes large, and weaker for complex draft/revision workflows.

Dedicated table option for later or if approved before implementation:

- A small YounGo-specific table or tables may be justified if the team needs structured rows for sections, repeatable content, ordering, status, revisions, or multi-page content beyond the homepage.
- Existing `frontend_settings`, `custom_page`, and `home_pages` are not ideal for long-term structured YounGo homepage management because they either store broad settings, unstructured page content, or generic builder output.

Actual database changes require explicit approval before implementation. Phase 1 should not invent final SQL in advance of a confirmed local database/schema review.

## 12. Implementation Sequence

1. Verify current theme loading and the active `frontend_settings.theme` value in the target environment.
2. Verify actual schema files, including `uploads/install.sql`, before finalizing homepage content storage.
3. Document the agreed YounGo homepage content storage shape before coding the CMS manager.
4. Create the YounGo theme skeleton under `application/views/frontend/youngo/`.
5. Create the local YounGo asset structure under `assets/frontend/youngo/`.
6. Build the YounGo shared layout: index, includes, header, footer, navigation, SEO handoff, scripts, and fallback content handling.
7. Convert the approved homepage visual direction into maintainable CodeIgniter views and partials.
8. Connect existing categories, courses, blogs, FAQs, contact info, ratings, and custom pages where practical.
9. Add the minimum admin homepage content manager in the existing dashboard pattern.
10. Add storage/read helpers for the YounGo homepage content configuration, with safe defaults if content has not been saved yet.
11. Connect CMS-managed content to the YounGo homepage sections.
12. Style the course listing and course details pages in the YounGo visual direction using existing LMS data and actions.
13. Style the supporting public pages needed for Phase 1 navigation, especially blog preview targets, FAQ, contact, and custom pages.
14. Add simple section visibility/order handling and at least one clear active/published state.
15. Run basic browser or Playwright checks for homepage, course listing, course details, navigation, admin content editing, save-to-frontend behavior, and responsive layout.
16. Keep the YounGo theme side-by-side until preview/review is approved; only then consider switching the active frontend theme.
17. Document any deferred items and known runtime/setup limitations.

## 13. Risks and Cautions

- The project is an older CodeIgniter CMS, so changes must follow existing conventions and avoid dependency churn.
- `Admin.php` is very large; adding more logic there increases maintenance risk unless changes are tightly scoped.
- The local database setup/import is not confirmed, so runtime testing may be blocked until environment setup is available.
- Storage decisions can be wrong if they are made before reviewing `uploads/install.sql` and any other actual schema sources.
- The current homepage builder is powerful but complex and not aligned with a simple fixed-theme CMS content manager.
- Stitch HTML is prototype output with remote assets and utility classes; it should guide layout and hierarchy, not be pasted into production.
- Theme activation relies on installed theme discovery and `frontend_settings.theme`; a new theme must fit that mechanism.
- Existing frontend pages assume many partials exist in the active theme. A YounGo theme must either implement required views or intentionally delegate/fallback where safe.
- Course thumbnail naming and media handling can depend on the active theme name, so course images should be checked carefully after switching to `youngo`.
- Existing `frontend_settings` JSON values may be missing or malformed in some environments; YounGo reads need defaults.
- Removing or changing old builder behavior could break current site behavior and should be avoided in Phase 1.
- Activating the YounGo theme too early could expose incomplete page coverage; build and review it side-by-side first.

## 14. Acceptance Criteria

- A dedicated YounGo theme exists at `application/views/frontend/youngo/`.
- Dedicated YounGo frontend assets exist at `assets/frontend/youngo/`.
- The YounGo theme is built side-by-side and is not switched active in production until preview/review approval.
- The homepage renders in the approved YounGo visual direction: soft purple-led, rounded, friendly for kids, and trustworthy for parents.
- The course details page follows the YounGo visual direction and uses existing LMS course data.
- Course listing remains usable and reuses the existing course filtering/listing flow where practical.
- Homepage key sections are manageable from the CMS at least for hero, featured categories, featured courses, why choose YounGo, about teaser, testimonials, FAQ preview, blog preview, and final CTA.
- Section visibility and ordering are manageable or implemented in an agreed minimal form.
- Homepage content has at least one clear active/published state.
- A full draft/revision workflow may be deferred to later phases.
- Existing course, category, blog, FAQ, contact, rating, and custom page data is reused where practical.
- Existing LMS core functionality is not intentionally broken.
- The old `default-new` theme and old homepage builder remain available.
- Basic browser checks pass for homepage, course listing, course details, navigation, admin content manager rendering, save-to-frontend content flow, and responsive layout, if local runtime setup is available.

## 15. Questions Before Implementation

- After reviewing `uploads/install.sql` and any other schema sources, should Phase 1 store YounGo homepage content as a single `frontend_settings` JSON value, or is a small dedicated YounGo table approved before implementation begins?
- What exact JSON shape or storage shape should be approved before CMS manager coding starts?
- Should the YounGo theme become the active frontend theme after preview/review approval, or should it remain available for manual activation first?
- Which homepage sections are mandatory for the first client preview, and which can be hidden by default?
- Should featured courses/categories be manually selected, automatic, or both?
- Are YounGo-specific course metadata fields such as age range, skill type, or child level required in Phase 1, or can they be deferred?
- What final YounGo logo and image assets should replace the remote/prototype Stitch images?
- Is local database/runtime setup available for browser testing, or should Phase 1 implementation initially rely on static code review?
- Which admin permission should control access to the YounGo homepage manager: existing settings permission, theme permission, or a new website-content permission?

## 16. Final Recommendation

Start implementation with a dedicated side-by-side `youngo` theme. Use a minimal YounGo homepage content manager, with `frontend_settings` JSON as the provisional low-risk Phase 1 storage option only after schema review and JSON-shape documentation. This gives the client a polished website quickly, keeps the visual design fixed, reuses existing LMS/CMS data, and avoids turning Phase 1 into a generic page-builder project.
