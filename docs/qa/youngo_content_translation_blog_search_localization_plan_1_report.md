# CONTENT.TRANSLATION.BLOG.SEARCH.LOCALIZATION.PLAN.1 Report

Phase: `CONTENT.TRANSLATION.BLOG.SEARCH.LOCALIZATION.PLAN.1 - Plan Localized Blog Search Behavior`

Scope: planning only. No deploy, push, DB write, content edit, schema creation, route/slug change, payment/Paymob change, checkout CTA exposure, Arabic pack import, or Root Admin change was performed.

## A. Current Branch/Status

Starting branch:

```text
analysis/cms-audit
```

Starting worktree:

```text
clean
```

Latest commit at start:

```text
b955998 QA YounGo blog category bilingual admin UI
```

## B. Files Inspected

Reports read:

- `docs/qa/youngo_content_translation_blog_category_admin_ui_qa_1_report.md`
- `docs/qa/youngo_content_translation_blog_category_schema_admin_wire_1_report.md`
- `docs/qa/youngo_content_translation_blog_detail_wire_1_report.md`
- `docs/qa/youngo_content_translation_reuse_audit_1_report.md`

Source inspected:

- `application/controllers/Blog.php`
- `application/models/Crud_model.php`
- `application/views/frontend/youngo/blogs.php`
- `application/views/frontend/youngo/blog_details.php`
- `application/views/frontend/default-new/blog_sidebar.php`
- `application/views/frontend/default-new/blogs_all.php`
- `application/helpers/youngo_frontend_language_helper.php`
- `application/config/routes.php`
- `scripts/phase_2/`

## C. Current Blog Search/Filter Summary

Current search controller path:

- Method: `Blog::blogs($param1 = '')`
- Legacy route: `/blogs`
- Query parameter: `?search={term}`
- Fields searched: canonical `blogs.title` and canonical `blogs.description`
- Query shape: direct `LIKE title`, `OR LIKE description`, then `status = 1`
- Pagination base URL: `site_url('blogs/')`
- View state: passes `$search_string` and `$total_rows` to the frontend view.

Current category filter controller path:

- Method: `Blog::blogs($param1 = '')`
- Query parameter: `?category={canonical_blog_category_slug}`
- Category lookup: `Crud_model::get_blog_category_by_slug($_GET['category'])`
- Filter field: canonical `blogs.blog_category_id`
- Slugs: canonical `blog_category.slug`
- Pagination base URL: `site_url('blogs/')`

YounGo frontend view behavior:

- Active YounGo view: `application/views/frontend/youngo/blogs.php`
- It renders localized Blog cards after the controller query has already selected Blog rows.
- It displays a search result heading when `$search_string` is set.
- It does not currently render a visible search input.
- YounGo Blog/category links use `youngo_frontend_blog_url()`, which maps Blog-like paths to Arabic/default `home/blog` or English `en/home/blog`.

Legacy/default frontend behavior:

- `application/views/frontend/default-new/blog_sidebar.php` includes a visible search form posting GET `search` to `site_url('blogs')`.
- Default Blog category links use `blogs?category={canonical_slug}`.

Important route note:

- Search/filter logic currently lives in `Blog::blogs()` and the legacy `/blogs?...` route.
- YounGo public Blog aliases such as `/home/blog`, `/en/home/blog`, and `/ar/home/blog` currently route to `Blog::index()`, not `Blog::blogs()`.
- A future search/filter implementation should preserve existing routes/slugs, but it must explicitly decide whether YounGo search UI should submit to `/blogs?...` or whether the alias/index path should delegate to the filtered listing path without route churn.

## D. Available Localized Sources

Current DB inventory:

```text
blogs rows: 4
youngo_blog_translations english rows: 4
youngo_blog_translations arabic rows: 4
youngo_blog_translations invalid language rows: 0

blog_category rows: 3
youngo_blog_category_translations english rows: 3
youngo_blog_category_translations arabic rows: 3
youngo_blog_category_translations invalid language rows: 0
```

Available Blog post fields:

- Canonical `blogs.title`
- Canonical `blogs.description`
- Canonical `blogs.keywords`
- Translated `youngo_blog_translations.title`
- Translated `youngo_blog_translations.excerpt`
- Translated `youngo_blog_translations.description`
- Translated `youngo_blog_translations.slug` for display/metadata only; public detail route still resolves by Blog ID.

Available Blog category fields:

- Canonical `blog_category.title`
- Canonical `blog_category.subtitle`
- Canonical `blog_category.slug`
- Translated `youngo_blog_category_translations.title`
- Translated `youngo_blog_category_translations.subtitle`
- Translated `youngo_blog_category_translations.display_slug` for display/metadata only; filters must keep canonical `blog_category.slug`.

Already wired localized display:

- Blog listing/cards use `youngo_blog_translations`.
- Blog detail uses `youngo_blog_translations`.
- Blog category labels on listing/category/detail use `youngo_blog_category_translations`.

Not wired:

- Blog search still queries canonical Blog fields only.
- Blog category label search is not wired.
- YounGo public view has no visible search form.

## E. Options Compared

| Option | Summary | Pros | Cons | Assessment |
|---|---|---|---|---|
| A. Keep search canonical only for demo | Leave `/blogs?search=` searching `blogs.title` and `blogs.description`. | Lowest implementation risk; no DB/query changes. | Arabic users cannot reliably find Arabic translated Blog content; search may look broken after localized display work. | Acceptable only as a temporary no-change state. |
| B. Search current UI language translation rows first, fallback canonical | For Arabic/default and `/ar`, search Arabic Blog/category translation fields first; for `/en`, search English rows first. Use canonical fallback only for entities with missing/blank current-language translation fields. | Best language-specific UX with low false positives; preserves canonical IDs/slugs; reuses existing tables. | Needs careful query helper/pagination implementation; fallback logic must avoid broad cross-language OR matches. | Recommended first implementation. |
| C. Search both canonical and translated rows | OR together canonical and all translated fields across languages. | Simple conceptually and can find more results. | Arabic searches can return English-only matches; English searches can return Arabic-only matches; duplicate IDs and ranking ambiguity. | Not recommended for public UX. |
| D. Add indexed/search-helper approach later | Build a dedicated search helper or indexed content layer after behavior is proven. | Better long-term performance/ranking and easier diagnostics. | Overkill for current 4 Blog rows; may require schema or indexing decisions later. | Defer until volume or ranking needs justify it. |

## F. Recommended Approach

Recommended future implementation: **Option B, with a narrow reusable search helper.**

Behavior:

- Arabic/default: search Arabic Blog translation fields and Arabic Blog category translation labels first.
- `/ar`: same as Arabic/default.
- `/en`: search English Blog translation fields and English Blog category translation labels first.
- Canonical fallback should apply only where the current-language translated row/field is missing or blank.
- Result rows should remain canonical `blogs` rows by `blog_id`, then the existing display overlay should localize cards.
- Category filtering should continue using canonical `?category={blog_category.slug}`.
- Detail links should continue using canonical Blog ID route behavior.
- `arabic_translated` must not be accepted or queried as a UI/content language.

Suggested searchable fields for the first implementation:

- Current-language `youngo_blog_translations.title`
- Current-language `youngo_blog_translations.excerpt`
- Current-language `youngo_blog_translations.description`
- Current-language `youngo_blog_category_translations.title`
- Current-language `youngo_blog_category_translations.subtitle`
- Fallback canonical `blogs.title` and `blogs.description` only when no current-language Blog translation row/field is available.
- Fallback canonical `blog_category.title` and `blog_category.subtitle` only when no current-language category translation row/field is available.

Implementation shape:

- Add a focused `Crud_model` Blog search helper that returns canonical Blog IDs or a paginated Blog query.
- Use `DISTINCT blogs.blog_id` or a two-step ID lookup to prevent duplicates from joins.
- Preserve `status = 1`.
- Keep sort order stable with current behavior, likely `added_date ASC` unless a deliberate relevance sort is planned.
- Keep all public URLs and filters on canonical slugs/IDs.

## G. Suggested Implementation Phases

Recommended next implementation phase:

```text
CONTENT.TRANSLATION.BLOG.SEARCH.WIRE.1
```

Scope:

- Add a reusable Blog search helper.
- Search current frontend language translations before canonical fallback.
- Include translated Blog category title/subtitle as search sources.
- Keep query parameters and canonical slugs unchanged.
- Do not add schema.
- Add a diagnostic that tests Arabic and English search terms against current demo content.

Recommended QA phase:

```text
CONTENT.TRANSLATION.BLOG.SEARCH.QA.1
```

Scope:

- Browser/HTTP QA for `/blogs?search=...`, Arabic/default equivalent, `/en`, and `/ar` if exposed.
- Verify Arabic terms find Arabic translated posts/categories.
- Verify English terms find English posts/categories.
- Verify category filters still use canonical slugs.
- Verify no duplicate result cards.
- Verify no payment/checkout/Paymob CTA exposure.

Optional later phase:

```text
CONTENT.TRANSLATION.BLOG.SEARCH.INDEX.PLAN.1
```

Use only if Blog volume grows enough that joins/LIKE queries become slow or ranking requirements become more sophisticated.

## H. Risks/Blockers

- The active YounGo view currently has no visible search form, so search may only be reachable through legacy `/blogs?search=...` or manually entered URLs.
- YounGo Blog helper aliases currently favor `home/blog` / `en/home/blog`, while existing search/filter logic is in `/blogs`; future wiring must avoid silently submitting search to `Blog::index()`.
- Existing canonical search query has ungrouped `LIKE` / `OR LIKE` / `status` conditions. A future implementation should group search conditions so status filtering remains correct.
- Searching both canonical and translated fields for all languages would create cross-language false positives.
- No full-text index exists for Blog translations or Blog category translations; this is acceptable for current small demo volume but may not scale.
- Translated slugs/display slugs must remain display metadata only unless a separate route/SEO phase explicitly changes route behavior.

## I. Diagnostic Result

Added read-only diagnostic:

```text
scripts/phase_2/youngo_content_translation_blog_search_localization_plan_1_diagnostic.php
```

Validation result:

```text
php -l scripts/phase_2/youngo_content_translation_blog_search_localization_plan_1_diagnostic.php
No syntax errors detected

php scripts/phase_2/youngo_content_translation_blog_search_localization_plan_1_diagnostic.php
CONTENT.TRANSLATION.BLOG.SEARCH.LOCALIZATION.PLAN.1 diagnostic passed.
```

The diagnostic verified:

- Current search path and query parameters are detected.
- Canonical Blog search fields are detected.
- Canonical category filter by slug is detected.
- YounGo view renders search-result state but has no visible search input.
- Legacy/default sidebar search form posts to `/blogs`.
- `youngo_blog_translations` exists with English and Arabic rows.
- `youngo_blog_category_translations` exists with English and Arabic rows.
- Invalid language rows are absent.
- Existing Blog/category display localization sources are present.
- No DB writes were executed.
- No payment/Paymob/checkout/coupon/cart file changes were detected.

## J. Git Status

Expected final git status for this planning phase:

```text
?? docs/qa/youngo_content_translation_blog_search_localization_plan_1_report.md
?? scripts/phase_2/youngo_content_translation_blog_search_localization_plan_1_diagnostic.php
```

No commit, push, or deploy performed.
