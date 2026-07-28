# YounGo Homepage Content Schema

## Master Plan Reference

The current source of truth for project priorities, architecture, reuse decisions, deployment preparation, and implementation sequencing is:

[`youngo_master_plan_v2.md`](./youngo_master_plan_v2.md)

All implementation work must be reviewed against this plan before modifying or rebuilding existing Academy LMS functionality.

This schema document is a supporting planning reference for homepage content structure. It does not override the master plan, and any future homepage CMS changes must first confirm whether existing Academy LMS/CMS behavior can be reused.

## 1. Purpose

This document defines the planned homepage content/storage shape for the YounGo homepage before coding the CMS homepage manager.

It is a planning-support document only.

It exists to make the homepage content structure clear before implementation begins, so that the admin content manager, frontend rendering, safe defaults, fallback behavior, and CMS-managed fields can be built consistently.

This document does not create a database structure.

This document does not contain implementation code.

This document does not approve SQL, migrations, controllers, models, views, CSS, or JavaScript.

---

## 2. Relationship to `phase_1.md` and the Master Plan

This document supports:

```text
docs/planning/phase_1.md
```

`phase_1.md` is an earlier Phase 1 planning reference. The current source of truth is:

```text
docs/planning/youngo_master_plan_v2.md
```

This file adds more detail only for the homepage content/storage shape considered for the minimum CMS homepage content manager.

If this document conflicts with the master plan, the master plan supersedes it. If it conflicts with current implementation reality, review the conflict before implementation.

---

## 3. Storage Decision Status: Provisional

The current Phase 1 storage direction is provisional.

The likely low-risk Phase 1 option is to store YounGo homepage content as JSON inside the existing `frontend_settings` mechanism.

This is not a final database decision.

Before implementation, the actual schema and storage behavior must be reviewed, especially:

```text
uploads/install.sql
```

No SQL should be created from this document.

No new database table should be finalized from this document.

No database change should be made without explicit approval.

---

## 4. Proposed `frontend_settings` Key

The proposed provisional key is:

```text
youngo_homepage_content
```

Expected purpose:

```text
Store the CMS-managed content structure for the YounGo homepage.
```

This key should hold a JSON value representing homepage sections, content fields, visibility, order, and active/published state.

The final key name and storage location must be confirmed during schema review before coding.

---

## 5. Top-Level JSON Shape

The proposed top-level content shape is:

```json
{
  "version": 1,
  "homepage_key": "youngo_homepage",
  "is_active": true,
  "is_published": true,
  "last_updated_at": null,
  "sections_order": [
    "hero",
    "featured_categories",
    "featured_courses",
    "why_choose",
    "about_teaser",
    "testimonials",
    "faq_preview",
    "blog_preview",
    "final_cta"
  ],
  "sections": {
    "hero": {},
    "featured_categories": {},
    "featured_courses": {},
    "why_choose": {},
    "about_teaser": {},
    "testimonials": {},
    "faq_preview": {},
    "blog_preview": {},
    "final_cta": {}
  },
  "additional_sections": []
}
```

This shape is intended to separate:

- Overall homepage state
- Section order
- Section-specific content
- Controlled additional sections
- Section visibility
- Section fallback behavior

The final stored shape may be adjusted after schema review, but it should remain simple and content-focused.

---

## 6. Shared Section Fields

Each homepage section should support a shared set of fields.

Recommended shared fields:

```json
{
  "key": "section_key",
  "label": "Readable Section Name",
  "is_visible": true,
  "is_published": true,
  "sort_order": 10,
  "source_type": "manual",
  "content": {},
  "fallback": {
    "use_default_if_empty": true
  }
}
```

### Shared Field Meanings

| Field | Purpose |
|---|---|
| `key` | Stable section identifier used by the frontend and CMS manager |
| `label` | Human-readable admin label |
| `is_visible` | Controls whether the section appears on the homepage |
| `is_published` | Controls whether the section is allowed to appear publicly |
| `sort_order` | Numeric ordering value for the homepage sections |
| `source_type` | Defines whether content is manual, existing CMS-driven, mixed, or automatic |
| `content` | Section-specific editable content |
| `fallback` | Section-specific fallback rules if content is missing |

Shared fields should not include visual styling controls such as colors, spacing, fonts, layout type, border radius, or animation settings.

### Allowed `source_type` Values

Only these `source_type` values should be used:

```text
manual
existing_cms
mixed
auto
```

| Value | Meaning |
|---|---|
| `manual` | Content is entered directly in the YounGo homepage manager |
| `existing_cms` | Content is sourced from existing LMS/CMS data |
| `mixed` | Section uses both admin-entered content and existing LMS/CMS data |
| `auto` | Section uses automatic/default selection logic from existing data where practical |

Do not introduce new stored `source_type` values without approval.

Examples of values that should not be used as final stored values:

```text
manual_or_existing_categories
manual_or_existing_courses
existing_website_faqs_or_manual
latest_blogs_or_manual
manual_or_existing_reviews
```

Those meanings should be represented using one of the approved values: `manual`, `existing_cms`, `mixed`, or `auto`.

---

## 7. Section Keys

The approved homepage section keys for Phase 1 are:

```text
hero
featured_categories
featured_courses
why_choose
about_teaser
testimonials
faq_preview
blog_preview
final_cta
```

These are the approved fixed homepage section keys.

These keys should remain stable because they may be used by:

- CMS forms
- Frontend rendering logic
- Safe defaults
- Playwright checks
- Future content validation

Section names shown to admins can be friendly, but the internal keys should remain consistent.

Additional homepage sections are handled separately through the `additional_sections` array. Additional sections must not create new fixed section keys.

---

## 8. Content Fields Per Section

## 8.1 `hero`

Purpose:

The main homepage introduction section.

Recommended content fields:

```json
{
  "eyebrow": "Safe, creative learning for kids",
  "title": "Fun learning experiences designed for curious young minds",
  "subtitle": "A friendly learning platform where kids explore useful skills in a safe and structured way.",
  "primary_cta": {
    "label": "Explore Courses",
    "url": "/home/courses"
  },
  "secondary_cta": {
    "label": "Learn More",
    "url": "/home/about_us"
  },
  "image": {
    "url": "",
    "alt": "Kids learning with YounGo"
  },
  "trust_items": [
    {
      "label": "Parent-trusted",
      "value": "Safe learning"
    },
    {
      "label": "Kid-friendly",
      "value": "Creative courses"
    }
  ]
}
```

Recommended `source_type`:

```text
manual
```

CMS-managed fields:

- Eyebrow text
- Title
- Subtitle
- CTA labels
- CTA links
- Hero image
- Hero image alt text
- Trust item labels and values

CMS should not manage:

- Hero layout
- Colors
- Typography
- Spacing
- Background styling

---

## 8.2 `featured_categories`

Purpose:

Show important course categories for quick discovery.

Recommended content fields:

```json
{
  "title": "Explore learning paths",
  "subtitle": "Choose a topic your child will enjoy.",
  "category_ids": [],
  "limit": 6,
  "cta": {
    "label": "View All Categories",
    "url": "/home/categories"
  }
}
```

Recommended `source_type`:

```text
mixed
```

CMS-managed fields:

- Section title
- Section subtitle
- Selected category IDs where manual selection is supported
- Display limit
- CTA label
- CTA link
- Visibility

Existing CMS data to reuse:

- Existing course categories
- Existing category names
- Existing category slugs/URLs
- Existing category images/icons if available

Fallback:

If no categories are manually selected, the frontend may use safe default category logic, such as active/popular categories, if supported by existing CMS data.

If the section is fully automatic in a later approved version, `source_type` may be set to `auto`.

---

## 8.3 `featured_courses`

Purpose:

Show selected or automatically sourced courses on the homepage.

Recommended content fields:

```json
{
  "title": "Featured courses for young learners",
  "subtitle": "Start with our most engaging learning experiences.",
  "course_ids": [],
  "category_ids": [],
  "limit": 6,
  "cta": {
    "label": "View All Courses",
    "url": "/home/courses"
  }
}
```

Recommended `source_type`:

```text
mixed
```

CMS-managed fields:

- Section title
- Section subtitle
- Selected course IDs where manual selection is supported
- Optional category filters
- Display limit
- CTA label
- CTA link
- Visibility

Existing LMS data to reuse:

- Course title
- Course thumbnail
- Course price/free status
- Course rating
- Instructor
- Course URL
- Category/subcategory
- Enrollment or popularity indicators where available

Fallback:

If no courses are manually selected, the frontend may use existing LMS logic such as featured, latest, top, or active courses, depending on what the existing CMS supports.

If the section is fully automatic in a later approved version, `source_type` may be set to `auto`.

---

## 8.4 `why_choose`

Purpose:

Explain why parents and kids should trust YounGo.

Recommended content fields:

```json
{
  "title": "Why families choose YounGo",
  "subtitle": "A learning experience designed to feel safe, useful, and inspiring.",
  "items": [
    {
      "icon_key": "safety",
      "title": "Safe for kids",
      "description": "A friendly learning environment designed with children and parents in mind."
    },
    {
      "icon_key": "creativity",
      "title": "Creative learning",
      "description": "Courses that help kids explore, build, and think with confidence."
    },
    {
      "icon_key": "structure",
      "title": "Structured progress",
      "description": "Clear course journeys that make learning easier to follow."
    }
  ]
}
```

Recommended `source_type`:

```text
manual
```

CMS-managed fields:

- Section title
- Section subtitle
- Item titles
- Item descriptions
- Optional icon key from an approved fixed icon set
- Visibility
- Item order

CMS should not allow arbitrary icon uploads or custom styling unless approved later.

---

## 8.5 `about_teaser`

Purpose:

Introduce YounGo briefly and link to a fuller about page.

Recommended content fields:

```json
{
  "title": "Learning that feels friendly, safe, and exciting",
  "body": "YounGo helps children explore new skills through a modern learning experience built for curiosity, confidence, and growth.",
  "image": {
    "url": "",
    "alt": "About YounGo"
  },
  "cta": {
    "label": "About YounGo",
    "url": "/home/about_us"
  },
  "stats": [
    {
      "label": "Learning paths",
      "value": "Multiple"
    },
    {
      "label": "Designed for",
      "value": "Kids"
    }
  ]
}
```

Recommended `source_type`:

```text
mixed
```

CMS-managed fields:

- Title
- Body text
- Image
- Image alt text
- CTA label
- CTA link
- Optional stats labels and values
- Visibility

Existing CMS data to reuse:

- Existing about/custom page URL if available
- Existing uploaded images if practical

If this section is managed fully from the YounGo homepage manager, `source_type` may be set to `manual`.

---

## 8.6 `testimonials`

Purpose:

Show parent/student trust signals and positive feedback.

Recommended content fields:

```json
{
  "title": "Loved by young learners and trusted by parents",
  "subtitle": "Real feedback from families and learners.",
  "items": [
    {
      "name": "Parent Name",
      "role": "Parent",
      "quote": "YounGo made learning feel exciting and easy to follow.",
      "rating": 5,
      "image": {
        "url": "",
        "alt": "Parent testimonial"
      }
    }
  ],
  "limit": 3
}
```

Recommended `source_type`:

```text
mixed
```

CMS-managed fields:

- Section title
- Section subtitle
- Manual testimonial name
- Manual testimonial role
- Manual testimonial quote
- Rating value
- Optional image
- Display limit
- Visibility
- Testimonial order

Existing CMS data to reuse where practical:

- Course reviews
- Ratings
- User names where appropriate and safe
- Existing rating table/data if suitable

Privacy note:

Only approved public-facing review/testimonial content should appear on the homepage.

Fallback:

If no approved testimonials or reviews exist, the section may be hidden or use safe default placeholder content only if approved.

If testimonials are entered only through the YounGo homepage manager, `source_type` may be set to `manual`.

If testimonials are sourced only from existing reviews/ratings, `source_type` may be set to `existing_cms`.

---

## 8.7 `faq_preview`

Purpose:

Show a short preview of frequently asked questions.

Recommended content fields:

```json
{
  "title": "Questions parents often ask",
  "subtitle": "Quick answers before your child starts learning.",
  "faq_items": [],
  "limit": 4,
  "cta": {
    "label": "View All FAQs",
    "url": "/home/faq"
  }
}
```

Recommended `source_type`:

```text
mixed
```

CMS-managed fields:

- Section title
- Section subtitle
- Selected FAQ items where manual selection is supported
- Display limit
- CTA label
- CTA link
- Visibility

Existing CMS data to reuse:

- Existing website FAQs stored in CMS/frontend settings where available

Fallback:

If no manual FAQ selection is set, the frontend may use existing website FAQs.

If no FAQ data exists, the section may be hidden or display safe default FAQ content if approved.

If this section uses only existing CMS FAQs, `source_type` may be set to `existing_cms`.

---

## 8.8 `blog_preview`

Purpose:

Show recent or selected blog/news content.

Recommended content fields:

```json
{
  "title": "Latest from YounGo",
  "subtitle": "Tips, updates, and learning ideas for families.",
  "blog_ids": [],
  "category_ids": [],
  "limit": 3,
  "cta": {
    "label": "Read More Articles",
    "url": "/blog"
  }
}
```

Recommended `source_type`:

```text
mixed
```

CMS-managed fields:

- Section title
- Section subtitle
- Selected blog IDs where manual selection is supported
- Optional blog category filters
- Display limit
- CTA label
- CTA link
- Visibility

Existing CMS data to reuse:

- Blog title
- Blog image
- Blog excerpt/short description
- Blog URL
- Blog category
- Publish status/date where available

Fallback:

If no blogs are manually selected, the frontend may show latest published blogs where supported.

If no blog content exists, the section may be hidden.

If this section uses only latest published blog logic, `source_type` may be set to `auto`.

If it uses only existing CMS blog records selected by the admin, `source_type` may be set to `existing_cms`.

---

## 8.9 `final_cta`

Purpose:

Close the homepage with a clear call to action.

Recommended content fields:

```json
{
  "title": "Ready to start your child’s learning journey?",
  "subtitle": "Explore safe, creative, and engaging courses built for young learners.",
  "primary_cta": {
    "label": "Explore Courses",
    "url": "/home/courses"
  },
  "secondary_cta": {
    "label": "Contact Us",
    "url": "/home/contact_us"
  },
  "image": {
    "url": "",
    "alt": "Start learning with YounGo"
  }
}
```

Recommended `source_type`:

```text
manual
```

CMS-managed fields:

- Title
- Subtitle
- CTA labels
- CTA links
- Optional image
- Image alt text
- Visibility

CMS should not manage:

- CTA styling
- Section background styling
- Layout controls

---

## 9. Additional Sections

`additional_sections` allows admins to add extra homepage sections using controlled predefined section types.

This is not a generic page builder.

The YounGo theme remains responsible for visual styling, layout, colors, typography, spacing, and rendering templates. The CMS should manage only content, visibility, ordering, and publish state.

Recommended top-level field:

```json
{
  "additional_sections": []
}
```

Each additional section should use this shared shape:

```json
{
  "id": "additional_section_unique_id",
  "type": "text_image",
  "label": "Readable Section Name",
  "is_visible": true,
  "is_published": true,
  "sort_order": 100,
  "source_type": "manual",
  "content": {},
  "fallback": {
    "use_default_if_empty": false
  }
}
```

### 9.1 Allowed Additional Section Types

Only these `type` values are allowed:

```text
text_image
feature_cards
cta_band
testimonial_block
faq_block
course_highlight
category_highlight
```

Do not introduce other `type` values without approval.

Unknown section types should be ignored safely by the frontend.

### 9.2 `text_image`

Purpose:

Add a controlled content section with text, optional image, and one CTA.

Recommended content fields:

```json
{
  "title": "Section title",
  "subtitle": "Short supporting text",
  "body": "Longer section body text.",
  "image": {
    "url": "",
    "alt": "Section image"
  },
  "cta": {
    "label": "Learn More",
    "url": "/home/about_us"
  }
}
```

### 9.3 `feature_cards`

Purpose:

Add a controlled group of feature or benefit cards.

Recommended content fields:

```json
{
  "title": "Section title",
  "subtitle": "Short supporting text",
  "items": [
    {
      "icon_key": "safety",
      "title": "Feature title",
      "description": "Feature description."
    }
  ]
}
```

### 9.4 `cta_band`

Purpose:

Add a controlled call-to-action band.

Recommended content fields:

```json
{
  "title": "CTA title",
  "subtitle": "CTA supporting text",
  "primary_cta": {
    "label": "Primary Action",
    "url": "/home/courses"
  },
  "secondary_cta": {
    "label": "Secondary Action",
    "url": "/home/contact_us"
  }
}
```

### 9.5 `testimonial_block`

Purpose:

Add a controlled testimonial section.

Recommended content fields:

```json
{
  "title": "Section title",
  "subtitle": "Short supporting text",
  "items": [
    {
      "name": "Parent Name",
      "role": "Parent",
      "quote": "Testimonial quote.",
      "rating": 5,
      "image": {
        "url": "",
        "alt": "Testimonial image"
      }
    }
  ]
}
```

### 9.6 `faq_block`

Purpose:

Add a controlled FAQ section.

Recommended content fields:

```json
{
  "title": "Section title",
  "subtitle": "Short supporting text",
  "items": [
    {
      "question": "Question text?",
      "answer": "Answer text."
    }
  ],
  "cta": {
    "label": "View All FAQs",
    "url": "/home/faq"
  }
}
```

### 9.7 `course_highlight`

Purpose:

Add a controlled course highlight section using existing LMS course records.

Recommended content fields:

```json
{
  "title": "Section title",
  "subtitle": "Short supporting text",
  "course_ids": [],
  "limit": 3,
  "cta": {
    "label": "View All Courses",
    "url": "/home/courses"
  }
}
```

Recommended `source_type`:

```text
mixed
```

The JSON should store selected course IDs or source rules, not duplicate full course records.

### 9.8 `category_highlight`

Purpose:

Add a controlled category highlight section using existing LMS category records.

Recommended content fields:

```json
{
  "title": "Section title",
  "subtitle": "Short supporting text",
  "category_ids": [],
  "limit": 4,
  "cta": {
    "label": "View All Categories",
    "url": "/home/courses"
  }
}
```

Recommended `source_type`:

```text
mixed
```

The JSON should store selected category IDs or source rules, not duplicate full category records.

### 9.9 Additional Section Rules

Admins may:

- Add additional sections.
- Remove additional sections.
- Reorder additional sections with fixed sections through `sort_order`.
- Choose only from the approved additional section `type` values.
- Manage content, visibility, publish state, source type, and ordering.

Admins must not manage:

- Custom CSS
- Arbitrary HTML
- Free layout structure
- Colors
- Fonts
- Spacing
- CSS classes
- Theme customization

Each additional section must render through a fixed YounGo frontend/theme template for its approved section type.

Each additional section should respect:

- `is_visible`
- `is_published`
- `sort_order`
- `source_type`

Invalid or incomplete additional sections should not break the homepage.

Unknown additional section types should be skipped safely.

---

## 10. Visibility Handling

Each section should support visibility control.

Recommended fields:

```json
{
  "is_visible": true,
  "is_published": true
}
```

Expected behavior:

- If `is_visible` is `false`, the section should not appear on the homepage.
- If `is_published` is `false`, the section should not appear publicly.
- If the overall homepage `is_published` is `false`, the frontend should use safe fallback behavior.
- Hidden sections should remain editable in the admin manager unless explicitly removed later.

Visibility should be content management only.

Visibility should not expose styling or layout controls.

---

## 11. Sort / Order Handling

Homepage section order should be controlled by a top-level order list and/or section-level `sort_order`.

Recommended top-level field:

```json
{
  "sections_order": [
    "hero",
    "featured_categories",
    "featured_courses",
    "why_choose",
    "about_teaser",
    "testimonials",
    "faq_preview",
    "blog_preview",
    "final_cta"
  ]
}
```

Recommended section-level field:

```json
{
  "sort_order": 10
}
```

Expected behavior:

- `sections_order` provides the preferred order.
- `sort_order` can help admin sorting and fallback ordering.
- Fixed sections and valid `additional_sections` should be rendered together according to `sort_order`.
- Unknown section keys should be ignored safely.
- Missing known section keys should use safe defaults or be skipped according to fallback rules.
- Duplicate section keys should be treated as invalid during validation.
- Unknown additional section types should be ignored safely.

The admin should manage order only at the section level, not visual layout structure.

---

## 12. Active / Published State

The homepage JSON should support top-level state.

Recommended fields:

```json
{
  "is_active": true,
  "is_published": true
}
```

Expected meaning:

| Field | Meaning |
|---|---|
| `is_active` | This content set is the active YounGo homepage content set |
| `is_published` | This content set is allowed to appear publicly |

Phase 1 should keep this simple.

Do not introduce multi-version publishing, scheduling, revisions, or complex workflow unless explicitly approved later.

---

## 13. Safe Defaults

The frontend should have safe defaults for all homepage sections.

Safe defaults are important because:

- The JSON key may not exist yet.
- The JSON may be empty.
- Some sections may be incomplete.
- The admin may hide sections.
- Existing CMS data may not be available.
- The theme should not break if content is missing.
- `additional_sections` may be missing, invalid, or empty.

Safe defaults should include:

- Default section titles
- Default short descriptions
- Default CTA labels and URLs
- Default section order
- Default visibility values
- Safe image fallback behavior
- Empty-state handling for lists
- An empty array default for missing `additional_sections`

Safe defaults should not become a replacement for CMS content management.

They are only a protection layer.

---

## 14. Fallback Behavior

Recommended fallback rules:

1. If `youngo_homepage_content` does not exist, use default YounGo homepage content.
2. If JSON exists but is invalid, use default YounGo homepage content and avoid breaking the page.
3. If a known section is missing, use the section default or skip the section depending on the section type.
4. If a section is hidden, do not render it.
5. If selected course IDs are missing or invalid, fall back to existing course listing logic where practical.
6. If selected category IDs are missing or invalid, fall back to existing category logic where practical.
7. If blog content is unavailable, hide the blog preview section or show safe defaults only if approved.
8. If FAQ content is unavailable, hide the FAQ preview section or show safe defaults only if approved.
9. If testimonial/review content is unavailable, hide the testimonials section or show safe defaults only if approved.
10. If images are missing, use approved local fallback images or render the section without breaking layout.
11. If `additional_sections` is missing, treat it as an empty array.
12. If `additional_sections` is invalid, ignore it safely and continue rendering valid fixed sections.
13. If an additional section has an unknown `type`, skip that section safely.
14. If an additional section is incomplete or invalid, skip it or use its fixed-template fallback without breaking the homepage.

Fallback behavior must protect the public homepage and existing CMS behavior.

---

## 15. Reuse of Existing CMS Data

Phase 1 should reuse existing LMS/CMS data where practical instead of duplicating content.

## 15.1 Courses

Reuse existing course data for:

- Featured courses
- Course cards
- Course details links
- Thumbnails
- Prices/free labels
- Ratings
- Instructor names
- Categories
- Course status where available

Avoid duplicating course title, price, instructor, rating, or thumbnail inside the homepage JSON unless there is a specific approved reason.

The JSON should store selected course IDs or source rules, not duplicate full course records.

Recommended `source_type` values for course-driven sections:

```text
mixed
existing_cms
auto
```

---

## 15.2 Categories

Reuse existing category data for:

- Featured category cards
- Category names
- Category links
- Category images/icons where available
- Course counts where available

The JSON should store selected category IDs or source rules, not duplicate full category records.

Recommended `source_type` values for category-driven sections:

```text
mixed
existing_cms
auto
```

---

## 15.3 Blogs

Reuse existing blog data for:

- Blog preview cards
- Blog titles
- Blog images
- Blog excerpts
- Blog links
- Blog publish data where available

The JSON should store selected blog IDs or source rules, not duplicate full blog records.

Recommended `source_type` values for blog-driven sections:

```text
mixed
existing_cms
auto
```

---

## 15.4 FAQs

Reuse existing website FAQ data where available.

Possible source:

```text
frontend_settings website_faqs
```

The homepage JSON may store:

- Section title
- Section subtitle
- Display limit
- Selected FAQ identifiers if supported
- CTA label/link

It should not duplicate all FAQ content unless required by existing CMS limitations and approved during implementation planning.

Recommended `source_type` values for FAQ-driven sections:

```text
mixed
existing_cms
```

---

## 15.5 Contact Info

Reuse existing CMS contact information where practical.

Possible use cases:

- Contact CTA links
- Footer/contact section links
- Email/phone/address references
- Contact page destination

The homepage JSON should not duplicate contact information unless necessary.

---

## 15.6 Reviews / Ratings

Reuse existing ratings and reviews where practical.

Possible use cases:

- Testimonials section
- Course rating display
- Trust indicators

Important note:

Reviews and ratings should only be shown publicly if they are suitable, approved, and safe for public display.

If existing reviews are not suitable for homepage testimonials, manual testimonials may be used as a Phase 1 content option.

Recommended `source_type` values for review/testimonial-driven sections:

```text
manual
mixed
existing_cms
```

---

## 16. What Is Excluded

This document does not approve or define:

- SQL
- New database tables
- Database migrations
- PHP implementation
- Controller methods
- Model methods
- View files
- CSS
- JavaScript
- Frontend theme activation
- Admin dashboard redesign
- Generic page builder behavior
- Free layout builder behavior
- Arbitrary HTML from admin-managed homepage content
- Visual style controls
- Drag-and-drop visual layout builder
- Theme marketplace features
- Editing colors from the CMS
- Editing fonts from the CMS
- Editing spacing from the CMS
- Editing page layout from the CMS
- Custom CSS classes from the CMS
- Theme customization from the CMS
- Rewriting existing LMS/CMS behavior

The CMS homepage manager should manage content, not styling.

---

## 17. Questions Before Coding

Before coding the CMS homepage manager, confirm:

1. Is `youngo_homepage_content` the approved `frontend_settings` key name?
2. How exactly does the existing `frontend_settings` storage work in the current codebase?
3. Does the current CMS already provide a safe helper/model method for reading and updating JSON settings?
4. What is the exact schema in `uploads/install.sql` for `frontend_settings`?
5. Are there size limitations or formatting risks for storing this JSON in the existing field?
6. Should the first implementation support draft/published states, or only a simple published state?
7. Should manual selection be supported for courses, categories, blogs, and FAQs in Phase 1?
8. Should testimonials be manual, sourced from reviews, or mixed in Phase 1?
9. What image upload mechanism should be reused for homepage images?
10. Should the CMS manager support section ordering in Phase 1, or use fixed order with visibility toggles first?
11. What exact frontend routes should CTA defaults use in this CodeIgniter project?
12. What fallback content is acceptable if no CMS content exists yet?
13. Should missing sections be automatically created with defaults when the admin first opens the manager?
14. Should invalid JSON be reset, ignored, or preserved with an error message?
15. What minimum Playwright checks should confirm CMS edit-to-frontend behavior?
16. Should Phase 1C expose all approved additional section types immediately, or start with a smaller UI subset while keeping the JSON shape ready?
17. Should fixed sections and additional sections share one global `sort_order` range in the first implementation?

These questions should be answered or explicitly deferred before implementation.

---

## 18. Approval Requirement Before Implementation

This document defines the planned content shape only.

Before implementation starts, approval is still required for:

- Final storage location
- Final key name
- Final JSON shape
- Admin manager scope
- Read/write behavior
- Image handling behavior
- Existing CMS data reuse strategy
- Fallback behavior
- Any database change
- Any SQL
- Any PHP, controller, model, view, CSS, or JavaScript work

No implementation should begin from this document without explicit approval.
