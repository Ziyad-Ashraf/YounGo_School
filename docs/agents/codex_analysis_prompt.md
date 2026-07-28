# Codex Analysis Prompt

## Purpose

This prompt is for Codex CLI / Codex agent analysis.

Use this prompt when asking Codex to inspect the YounGo repository before implementation.

This is an analysis-only prompt.

Codex must not modify files unless explicitly approved later.

---

## Prompt

Analyze this repository carefully.

Do not modify files.

Do not create files.

Do not delete files.

Do not run dependency-changing commands.

Do not commit or push.

This project is YounGo, a kids learning platform being built on top of an existing Academy LMS / PHP CodeIgniter CMS.

Before analyzing code, read these files in order:

```text
YOUNGO_PROJECT_CONTEXT.md
AGENTS.md
docs/planning/youngo_master_plan_v2.md
docs/planning/youngo_phase_2_hybrid_access_subscriptions_roles_architecture.md
docs/design/youngo_style_direction.md
docs/agents/implementation_rules.md
````

Also inspect available reference material under:

```text
docs/reference/
```

If any listed document or folder is missing, report it clearly.

Do not invent missing documentation.

---

## Important Project Direction

The current master plan and source of truth is:

[`youngo_master_plan_v2.md`](../planning/youngo_master_plan_v2.md)

All analysis and implementation recommendations must be checked against that plan before proposing changes to existing Academy LMS functionality.

For analysis involving access, subscriptions, course purchase, coupons, manual grants, checkout, payments, roles, permissions, or instructor assignment, also check:

[`youngo_phase_2_hybrid_access_subscriptions_roles_architecture.md`](../planning/youngo_phase_2_hybrid_access_subscriptions_roles_architecture.md)

YounGo should be built on top of the existing CodeIgniter CMS.

Do not rewrite the system from scratch.

Do not convert the project to Laravel or any other framework.

This is not Laravel.

Do not use Laravel assumptions such as:

```text
php artisan
Laravel migrations
Blade
Eloquent
Laravel routing
```

The approved product direction is:

```text
Create a new custom YounGo frontend/theme.
Keep the visual style fixed in the theme.
Make website content editable from the CMS.
Reuse existing LMS data where practical.
Add YounGo-specific CMS structures only where the existing CMS is too limited.
Avoid complex generic page builders.
Avoid focusing on old prebuilt themes as a product feature.
```

Academy LMS must be reused as the core system whenever possible. Existing working LMS functionality must not be rebuilt unless reuse is proven impractical.

Phase 2 adds subscriptions, manual grants, coupon scope improvements, direct checkout, Paymob planning, and a multi-role/capability model beside existing Academy LMS behavior. Do not recommend removing existing course purchase, enrolment, cart, payment, invoice, lesson access, `role_id`, `permissions`, or `is_instructor` behavior without a compatibility layer.

---

## What to Analyze

Analyze the current repository and report:

### 1. Existing CodeIgniter Structure

Identify the important existing:

```text
controllers
models
views
helpers
config files
asset folders
upload/media folders
```

Focus on files related to:

```text
frontend loading
theme selection
homepage rendering
course listing
course details
blog/news
custom pages
frontend settings
admin dashboard
admin navigation
media/uploads
SEO settings
```

---

### 2. Existing Frontend Loading Flow

Explain how the current frontend theme is selected and loaded.

Find the relevant controller/view/helper/database usage.

Identify whether creating:

```text
application/views/frontend/youngo/
assets/frontend/youngo/
```

is safe and consistent with the current system.

---

### 3. Existing CMS Content Capabilities

Inspect what the CMS can already manage.

Focus on:

```text
courses
categories
blogs
custom pages
frontend settings
homepage builder
FAQs
contact info
logos/images
reviews
SEO-related settings
```

Report which existing features can be reused for YounGo.

---

### 4. Existing CMS Limitations

Identify where the current CMS is too limited for the YounGo website direction.

Focus especially on:

```text
homepage section content
section ordering
section visibility
draft/published state
menu links
SEO metadata
homepage-specific structured content
kids-learning metadata such as age range or skills
```

Do not propose implementation yet unless it is directly supported by code findings.

---

### 5. YounGo Frontend Feasibility

Assess how to implement the approved Stitch/YounGo direction inside the existing CMS.

Analyze feasibility for:

```text
homepage
courses page
course details page
blog/news pages
contact page
generic CMS pages
header/footer/navigation
```

Mention likely existing data sources for each page.

---

### 6. YounGo Admin Feasibility

Assess how to add a focused YounGo website content-management area inside the existing admin dashboard.

Do not design the full implementation yet.

Only identify:

```text
where admin screens currently live
how admin navigation is built
how admin controllers are organized
how settings/forms are saved
what existing patterns should be followed
what risks exist
```

---

### 7. Database / Data Storage Findings

Inspect existing schema files or SQL dumps if present.

Identify existing tables relevant to:

```text
frontend settings
home pages
custom pages
courses
categories
blogs
FAQs
contact information
reviews
users/instructors
roles/permissions
enrolments/access checks
course purchases
coupons
payment records/invoices
cart/checkout
manual enrolment or grant-like behavior
media/uploads
```

Do not create a database plan yet.

Only report what exists and where gaps may exist.

---

### 8. Risk Report

Report risks clearly.

Include risks such as:

```text
old CodeIgniter compatibility
dependency issues
database setup missing
theme switching behavior
large controller files
hardcoded assumptions
unsafe edits
missing local runtime
Stitch prototype code not production-ready
```

---

## Output Format

Return your analysis in this structure:

```text
# Codex Repository Analysis Report

## 1. Missing Required Docs or Folders

## 2. Existing Project Structure

## 3. Frontend Loading Flow

## 4. Existing CMS Capabilities

## 5. Existing CMS Limitations

## 6. YounGo Frontend Feasibility

## 7. YounGo Admin Feasibility

## 8. Relevant Database/Tables Found

## 9. Recommended Planning Topics

## 10. Risks and Cautions

## 11. Questions Before Implementation
```

---

## Important Rules

Do not modify files.

Do not generate implementation code.

Do not create `docs/planning/` yet.

Do not create database tables.

Do not edit controllers, views, models, or assets.

Only analyze and report.

The goal is to help create accurate planning documents after analysis.
