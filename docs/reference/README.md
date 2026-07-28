# YounGo Reference Folder

## 1. Purpose

This folder contains reference material only.

It is used to help developers and agents understand:

- The original CMS documentation
- The approved Stitch design outputs

This folder is not the project plan.

This folder is not the design system.

This folder is not the agent instruction source.

This folder is not the implementation source of truth.

The current master plan and source of truth for project priorities, architecture, reuse decisions, deployment preparation, and implementation sequencing is:

[`youngo_master_plan_v2.md`](../planning/youngo_master_plan_v2.md)

Reference material should be used to understand Academy LMS and existing design evidence. It must not override the master plan, and working Academy LMS functionality should not be rebuilt unless reuse is proven impractical.

---

## 2. Folder Structure

Use this structure:

```text
docs/reference/
  README.md

  cms_documentation/
    original CMS documentation files

  stitch_outputs/
    html/
      Stitch exported HTML files

    images/
      Stitch exported screenshots or approved design images
````

---

## 3. `cms_documentation/`

Use this folder for old CMS documentation.

Examples:

```text
developer_manual.md
admin_usage_guide.md
custom_field_feature.md
original_vendor_docs.pdf
```

Purpose:

```text
Understand how the existing Academy LMS / CodeIgniter CMS works.
```

Important note:

Old CMS documentation is reference material only. The live code may be different, so agents should verify important details from the actual repository before implementation.

---

## 4. `stitch_outputs/html/`

Use this folder for Stitch HTML exports.

Examples:

```text
youngo_homepage_code.html
youngo_course_details_code.html
youngo_admin_homepage_manager_code.html
```

Purpose:

```text
Preserve the Stitch-generated HTML as a visual and structural reference.
```

Important note:

Stitch HTML is not production-ready code.

It may include:

* Tailwind CDN
* Temporary remote images
* Static placeholder content
* Prototype-only structure

It should be converted into maintainable CodeIgniter views, local CSS/JS, and CMS-managed content before implementation.

---

## 5. `stitch_outputs/images/`

Use this folder for approved Stitch screenshots/images.

Examples:

```text
youngo_homepage.png
youngo_course_details.png
youngo_admin_homepage_manager.png
```

Purpose:

```text
Preserve the approved visual direction.
```

These images help developers and agents compare the real implementation against the accepted design.

---

## 6. Source of Truth

Reference files support understanding, but they do not decide what to build.

Use this priority:

```text
1. Latest explicit user instruction
2. docs/planning/youngo_master_plan_v2.md
3. YOUNGO_PROJECT_CONTEXT.md
4. docs/planning/
5. docs/agents/implementation_rules.md
6. docs/design/youngo_style_direction.md
7. docs/reference/
8. Existing codebase behavior
```

---

## 7. Final Rule

Keep this folder simple.

Only store:

```text
CMS documentation
Stitch HTML outputs
Stitch image outputs
```

Do not put plans, prompts, implementation rules, or active development tasks here.
