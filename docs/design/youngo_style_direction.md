# YounGo Style Direction

## 1. Purpose

This document defines the visual identity, UI style, and UX design direction for YounGo.

It should be used as the main design reference for the public website and the admin dashboard UI.

This file does not define implementation steps, database structure, controller methods, development phases, or technical tasks.

The current master plan for project priorities, architecture, reuse decisions, deployment preparation, and implementation sequencing is:

[`youngo_master_plan_v2.md`](../planning/youngo_master_plan_v2.md)

Design work should support that plan and should not imply rebuilding existing Academy LMS functionality where reuse is practical.

---

## 2. Brand Summary

YounGo is a modern learning platform for kids.

The design should feel:

- Safe
- Friendly
- Creative
- Premium
- Trustworthy
- Modern
- Parent-friendly
- Kid-appropriate
- Education-focused

The platform should appeal to both parents and children.

Parents should feel that YounGo is safe, structured, and professional.

Children should feel that YounGo is fun, inspiring, and easy to explore.

The visual tone should be:

> A premium kids learning platform that feels friendly, safe, and modern without becoming childish or messy.

---

## 3. Audience

### Primary Audience

Parents who are looking for safe, useful, and enjoyable learning experiences for their children.

### Secondary Audience

Children who will browse, learn, and interact with the platform.

### Internal Audience

Admins, teachers, and content managers who need a clean dashboard experience.

---

## 4. Visual Personality

YounGo should visually communicate:

- Learning through curiosity
- Creativity and imagination
- Safety and trust
- Friendly technology
- Positive childhood development
- Simple access to educational content

The design should avoid:

- Overly corporate LMS styling
- Heavy dark interfaces
- Sharp and harsh layouts
- Overly childish cartoon visuals
- Messy colors
- Too many decorative effects
- Complex visual noise

---

## 5. Logo Usage

The YounGo logo should be treated as the main brand anchor.

Logo usage should follow these principles:

- Use the full logo in the website header.
- Use the logo in the admin sidebar/header.
- Keep enough whitespace around the logo.
- Do not distort, stretch, recolor, or place the logo on visually noisy backgrounds.
- Prefer white or very light backgrounds behind the full logo.
- Use the logo colors as the main source for the purple/magenta identity.

---

## 6. Color Direction

The color palette is inspired by the YounGo logo and Stitch visual direction.

### Primary Colors

| Role | Color | Usage |
|---|---:|---|
| Deep Purple | `#420F79` | Main CTAs, active states, strong brand moments |
| Brand Purple | `#5A2D91` | Brand blocks, highlights, emphasis |
| Secondary Purple | `#7E42AB` | Secondary actions, hover states, supporting accents |
| Soft Lavender | `#F3F3FC` | Section backgrounds, soft dashboard surfaces |
| Light Background | `#FAF8FF` | Main website background |
| White | `#FFFFFF` | Cards, content blocks, clean surfaces |

### Accent Colors

| Role | Color | Usage |
|---|---:|---|
| Magenta Accent | `#B93D98` | Limited highlight moments and energetic accents |
| Warm Yellow/Orange | `#F2BD76` | Kid-friendly badges, icons, soft emphasis |

### Text and Borders

| Role | Color | Usage |
|---|---:|---|
| Main Text | `#191B22` | Headings and primary text |
| Secondary Text | `#4B4451` | Descriptions and metadata |
| Border / Divider | `#CDC3D3` | Card borders, dashboard dividers |
| Soft Neutral | `#E7E7F0` | Muted cards and inactive states |

### Color Rules

Use purple as the core identity.

Use magenta and warm yellow/orange sparingly.

Keep the overall interface light, soft, and clean.

Do not create a rainbow-style kids interface. The design should remain premium and controlled.

---

## 7. Typography

Primary font:

```css
Plus Jakarta Sans
````

Fallback:

```css
'Plus Jakarta Sans', Arial, sans-serif
```

Typography should feel modern, rounded, readable, and friendly.

### Suggested Type Scale

| Style          | Size | Weight | Usage                                |
| -------------- | ---: | -----: | ------------------------------------ |
| Display        | 48px |    800 | Homepage hero headings               |
| Large Heading  | 32px |    700 | Page headings and major sections     |
| Medium Heading | 24px |    700 | Card groups and section subtitles    |
| Body Large     | 18px |    400 | Hero descriptions and important text |
| Body           | 16px |    400 | General content                      |
| Label          | 14px |    600 | Buttons, tabs, form labels           |
| Small Label    | 12px |    600 | Badges, metadata, status labels      |

### Typography Rules

* Use strong, clear headings.
* Keep body text readable and calm.
* Avoid decorative or childish fonts.
* Avoid overly compressed text blocks.
* Use short section descriptions where possible.

---

## 8. Layout Direction

The design should use clean, spacious layouts.

### Website Layout

The public website should feel open, friendly, and premium.

Use:

* Wide sections
* Clear hero areas
* Card-based content
* Soft backgrounds
* Large section spacing
* Simple navigation
* Clear call-to-action placement

### Dashboard Layout

The dashboard should feel practical, calm, and organized.

Use:

* Sidebar navigation
* Clean top header
* Large content cards
* Clear page titles
* Simple forms
* Clear action buttons
* Light surfaces and borders

The dashboard should be less playful than the public website.

---

## 9. Spacing

Use an 8px spacing system.

Recommended spacing:

| Token           |     Value | Usage                      |
| --------------- | --------: | -------------------------- |
| Base Unit       |       8px | Foundation spacing         |
| Small Stack     |       8px | Tight groups               |
| Medium Stack    |      16px | Standard form/card spacing |
| Large Stack     |      32px | Major grouped elements     |
| Section Spacing | 64px–96px | Website sections           |
| Desktop Margin  |      40px | Page horizontal padding    |
| Mobile Margin   |      20px | Mobile horizontal padding  |
| Container Width |    1280px | Max website content width  |

Whitespace is important. The design should not feel crowded.

---

## 10. Shape Direction

YounGo should use a rounded, soft shape language.

Recommended radius:

| Element        |    Radius |
| -------------- | --------: |
| Small controls |       8px |
| Buttons        |      12px |
| Inputs         |      12px |
| Cards          | 16px–24px |
| Hero images    | 24px–32px |
| Badges / Pills |     999px |

Avoid sharp corners.

Rounded shapes help the platform feel safe, friendly, and kid-appropriate.

---

## 11. Elevation and Shadows

Use soft elevation.

Cards should usually have:

* Light background
* Thin border
* Very soft shadow
* Rounded corners

Recommended shadow direction:

```css
box-shadow: 0 12px 30px rgba(90, 45, 145, 0.08);
```

Avoid heavy black shadows.

Avoid dramatic depth effects.

---

## 12. Public Website UI Direction

The public website should feel polished, friendly, and client-ready.

### Header

The website header should be:

* Clean
* Light
* Simple
* Easy to scan
* Logo-led
* CTA-focused

Suggested navigation style:

* Logo on the left
* Main navigation in the center or left/middle
* Login and main CTA on the right
* Purple active state
* Rounded CTA buttons

### Hero Sections

Hero sections should feel inspirational and safe.

Use:

* Strong headline
* Clear supporting text
* Primary CTA
* Secondary CTA
* Friendly education-related visual
* Soft background accents

Hero copy should speak to parents and children together.

### Cards

Cards should be used for:

* Courses
* Categories
* Benefits
* Testimonials
* Blog posts
* FAQ items

Card style:

* White or soft lavender background
* Rounded corners
* Soft shadow
* Clear title
* Short description
* Friendly icon or image

### Course Cards

Course cards should feel fun and informative.

They may include:

* Course image or visual block
* Age range badge
* Course title
* Short description
* Rating
* View details action

### Buttons

Primary buttons:

* Deep purple background
* White text
* Rounded corners
* Strong contrast

Secondary buttons:

* White or transparent background
* Purple border or text
* Rounded corners

CTA buttons should be clear and action-oriented.

### Badges and Chips

Badges should be soft and rounded.

Use them for:

* Age range
* Course category
* Skill level
* Duration
* Status labels

Badges should add clarity, not visual clutter.

---

## 13. Course Details UI Direction

The course details page should help parents quickly understand the course value.

The page should feel:

* Trustworthy
* Structured
* Easy to scan
* Clear about learning outcomes
* Friendly for kids learning

Visual priorities:

* Course title must be prominent.
* Age range should be visible.
* CTA/enrollment area should be clear.
* Learning outcomes should be card-based or bullet-based.
* Curriculum should be organized and not overwhelming.
* Reviews should feel parent/student focused.
* Related courses should appear near the bottom.

The design should avoid looking like a generic adult course marketplace.

---

## 13A. Subscription, Account, and Payment State UX

Phase 2 may introduce subscription status, expiry warnings, purchase-only courses, locked lessons, direct checkout, coupons, and manual-grant access states.

Design direction for those states:

* Keep messages parent-friendly and calm.
* Make access status clear without sounding punitive.
* Show expiry dates and warnings plainly.
* Use clear labels for subscription access, individual purchase access, and locked access.
* Keep payment and invoice UI trustworthy, simple, and under the student account identity.
* Do not expose backend access-model complexity in public-facing copy.

Technical access, payment, coupon, and role architecture belongs in planning documents, not this design file.

---

## 14. Admin Dashboard UI Direction

The admin dashboard should be clean and content-focused.

It should feel like a modern SaaS admin panel.

Visual tone:

* Calm
* Organized
* Professional
* Easy for non-technical admins
* Related to the YounGo brand, but not overly playful

### Dashboard Visual Rules

Use:

* Light lavender/gray background
* White content cards
* Purple active states
* Rounded buttons and cards
* Clear status badges
* Simple icons
* Large clickable rows/cards
* Consistent spacing

Avoid:

* Busy layouts
* Too many colors
* Dense tables when cards are clearer
* Complex builder-like interfaces
* Kid-heavy visuals inside admin screens

---

## 15. Form UI Direction

Forms should be simple and readable.

Use:

* Clear labels
* Helpful placeholder text
* Large input fields
* Rounded corners
* Visible focus state
* Simple validation messages
* Clear save/cancel actions

Form screens should not feel technical.

---

## 16. Interaction Style

Interactions should feel smooth and lightweight.

Recommended:

* Subtle hover states
* Soft card elevation on hover
* Clear active navigation states
* Visible focus states
* Simple accordion behavior for FAQ/curriculum
* Simple toggles in dashboard UI

Avoid:

* Heavy animations
* Distracting transitions
* Overly playful motion
* Complex drag-and-drop visuals unless needed for the dashboard design

---

## 17. Responsive Direction

The website must work well on mobile.

Mobile behavior:

* Header collapses cleanly.
* Hero becomes single-column.
* Course cards stack vertically.
* Dashboard tables or section rows should become stacked cards where possible.
* CTAs remain visible and easy to tap.
* Text remains readable without zooming.

Mobile should feel intentionally designed, not just compressed.

---

## 18. Accessibility Direction

Minimum accessibility expectations:

* Good color contrast
* Clear button labels
* Visible focus states
* Meaningful image alt text
* Form labels for all inputs
* Text should not rely on color alone
* Status indicators should include text
* Tap targets should be comfortable on mobile

---

## 19. Overall Design Summary

The approved YounGo design direction is:

```text
A modern, premium, soft, purple-led kids learning platform.
Friendly enough for children.
Trustworthy enough for parents.
Clean enough for teachers and admins.
Simple enough to implement inside the existing CMS.
```

This file should remain focused on design and UX direction only.

Technical implementation, database planning, agent behavior, file paths, and development phases should be documented separately.

````

