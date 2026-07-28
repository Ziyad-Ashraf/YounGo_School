# Antigravity Analysis Prompt

## Purpose

This prompt is for Antigravity repository analysis.

Use this prompt when asking Antigravity to inspect the YounGo repository and produce a grounded project analysis before implementation.

This is an analysis-only prompt.

Antigravity must not modify files unless explicitly approved later.

---

## Prompt

Analyze the YounGo repository.

Do not modify files.

Do not create files.

Do not delete files.

Do not commit.

Do not push.

Do not run dependency-changing commands.

Do not start implementation.

This project is YounGo, a kids learning platform being built on top of an existing Academy LMS / PHP CodeIgniter CMS.

Start by reading these files:

```text
YOUNGO_PROJECT_CONTEXT.md
AGENTS.md
docs/planning/youngo_master_plan_v2.md
docs/planning/youngo_phase_2_hybrid_access_subscriptions_roles_architecture.md
docs/design/youngo_style_direction.md
docs/agents/implementation_rules.md
````

Then inspect supporting references under:

```text
docs/reference/
```

If any required file or folder is missing, report it clearly before continuing.

---

## Project Boundaries

The current master plan and source of truth is:

[`youngo_master_plan_v2.md`](../planning/youngo_master_plan_v2.md)

All analysis and implementation recommendations must be checked against that plan before proposing changes to existing Academy LMS functionality.

For analysis involving access, subscriptions, course purchase, coupons, manual grants, checkout, payments, roles, permissions, or instructor assignment, also check:

[`youngo_phase_2_hybrid_access_subscriptions_roles_architecture.md`](../planning/youngo_phase_2_hybrid_access_subscriptions_roles_architecture.md)

The existing CMS is the foundation.

Do not replace the CMS.

Do not rewrite the project from scratch.

Do not convert it to Laravel, React, Next.js, or another framework unless a future planning document explicitly says so.

This is a CodeIgniter MVC project.

Do not assume:

```text
Laravel
Blade
Eloquent
php artisan
Laravel migrations
modern framework routing
```

---

## Approved Direction

The approved YounGo direction is:

```text
Create a new custom YounGo frontend/theme.
Keep the visual design fixed.
Make website content manageable from the CMS.
Reuse existing LMS/CMS data where practical.
Create YounGo-specific CMS structures only where existing structures are too limited.
Avoid complex generic page builders.
Avoid focusing on old prebuilt themes as a product feature.
```

Academy LMS must be reused as the core system whenever possible. Existing working LMS functionality must not be rebuilt unless reuse is proven impractical.

Phase 2 adds subscriptions, manual grants, coupon scope improvements, direct checkout, Paymob planning, and a multi-role/capability model beside existing Academy LMS behavior. Do not recommend removing existing course purchase, enrolment, cart, payment, invoice, lesson access, `role_id`, `permissions`, or `is_instructor` behavior without a compatibility layer.

The platform is for kids learning.

The public website should feel friendly, safe, modern, premium, and trustworthy for parents.

The admin dashboard should feel clean, practical, and content-focused.

---

## Analysis Goals

Analyze the repository and answer the following.

### 1. Existing Architecture

Identify the existing project architecture.

Focus on:

```text
CodeIgniter controllers
models
views
helpers
config
routes
frontend themes
backend/admin views
assets
uploads
database/schema files
```

Explain how these pieces currently work together.

---

### 2. Frontend Theme System

Determine how the active frontend theme is selected.

Find the relevant code responsible for loading frontend views.

Assess whether a new theme folder like this is safe:

```text
application/views/frontend/youngo/
assets/frontend/youngo/
```

Mention any required settings or database values that would affect theme activation.

---

### 3. Existing Website/CMS Features

Identify existing features that can support YounGo, including:

```text
courses
course details
categories
lessons
instructors
blogs/news
custom pages
frontend settings
homepage builder
logos/images
contact information
FAQs
reviews
SEO settings
media/uploads
```

Explain what can be reused.

---

### 4. Existing Gaps for YounGo

Identify gaps between the current CMS and the approved YounGo direction.

Focus on:

```text
structured homepage content
homepage section order
section visibility
draft/publish behavior
homepage preview
menu management
SEO management
kids course metadata
age range
skills gained
parent-focused course content
```

Do not create a final plan yet.

Only identify likely gaps.

---

### 5. Admin Dashboard Extension Points

Find where admin dashboard pages, forms, and navigation are defined.

Identify how new YounGo admin screens could fit into existing conventions.

Focus on:

```text
admin controller methods
backend/admin views
sidebar/navigation view
form submit patterns
flash messages
permissions/session checks
settings update patterns
file upload patterns
existing access/enrolment checks
existing purchase/payment/invoice flow
existing coupon and cart/checkout flow
existing role_id, permissions, and is_instructor behavior
```

Do not implement anything.

Only report findings.

---

### 6. Visual Implementation Feasibility

Use the approved design direction from:

```text
docs/design/youngo_style_direction.md
```

Assess whether the Stitch-inspired homepage, course details page, and admin content manager can be implemented using the existing system.

Report any likely obstacles.

Do not paste Stitch HTML directly into the project.

Treat Stitch output as design reference only.

---

### 7. Testing Feasibility

If Playwright MCP or browser inspection is available, note how it could later be used to test:

```text
homepage render
course details render
responsive layout
admin content manager render
CMS edit-to-frontend flow
navigation links
console/layout issues
```

Do not run full testing unless the user explicitly asks.

---

## Output Format

Return the report in this format:

```text
# Antigravity Repository Analysis Report

## 1. Missing Required Docs or Folders

## 2. Architecture Summary

## 3. Frontend Theme Loading Findings

## 4. Existing CMS Features to Reuse

## 5. Existing Gaps for YounGo

## 6. Admin Dashboard Extension Points

## 7. Database and Content Storage Findings

## 8. YounGo Design Feasibility

## 9. Testing Feasibility

## 10. Risks and Constraints

## 11. Recommended Planning Documents

## 12. Clarifying Questions Before Implementation
```

---

## Hard Rules

Do not modify files.

Do not create files.

Do not create `docs/planning/`.

Do not write implementation code.

Do not install dependencies.

Do not run destructive commands.

Do not commit or push.

Only analyze the repository and produce a clear report.

The purpose of this analysis is to help create accurate planning documents next.
