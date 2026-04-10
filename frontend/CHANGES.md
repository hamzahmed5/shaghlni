# CHANGES.md — Jobpilot Frontend Build Log

---

## Phase 13-D — Global Lang + Theme Architecture Fix
**Status:** Complete

### Root causes fixed
| Problem | Root cause | Fix |
|---|---|---|
| Controls disappeared on page change | `injectGlobalControls()` injected HTML via JS into wrong selector; `main.js` not loaded on most pages | Controls now static HTML in all navbars; JS only wires handlers |
| Arabic didn't work across the site | `data-i18n` attributes only on `index.html` | Batch-added `data-i18n` to all 44 pages |
| Dark mode didn't persist after navigation | Theme IIFE was at end of `<body>` (too late) | Inline `<script>` added to `<head>` of every page |
| Stray `</script>` artifacts on all pages | Previous batch script left `\x01</script>` fragments | Cleaned via Python batch |
| `main.js` missing from most pages | Batch only added theme/i18n, skipped main | Added `main.js` after i18n.js on all pages |
| Old lang-dropdown on dashboard pages | Dashboard navbars had their own broken dropdown | Removed; replaced with shared global-controls |

### Architecture changes
- **`js/i18n.js`**: now self-wires `[data-lang-btn]` click handlers in `_wireControls()` (called from `init()`). No external wiring needed.
- **`js/theme.js`**: now self-wires `[data-theme-toggle]` click handlers in `_wireToggle()` (called from `init()`). Inline IIFE added to `<head>` of every page for zero-flash theme application.
- **`js/main.js`**: `injectGlobalControls()` removed. Replaced with `initGlobalControls()` — just a sync helper since each library manages its own controls.
- **All 44 HTML pages** now have:
  - Inline theme IIFE in `<head>` (zero-flash)
  - `theme.js` + `i18n.js` + `main.js` loaded in correct order
  - Static `<div class="global-controls">` in the navbar (no JS injection needed)
  - `data-i18n` attributes on navbar links, sidebar links, footer headings

### Pages updated
**Public pages (19):** global-controls added to `.navbar-public__actions`; navbar links, sign-in/post-job buttons, footer headings annotated with `data-i18n`

**Dashboard pages (25):** old `id="lang-dropdown"` dropdown removed; global-controls added to `.navbar-dashboard__top-right`; all sidebar nav links annotated with `data-i18n`; dashboard top-links annotated

### How persistence now works
- **Theme**: localStorage `jp_theme` read by inline IIFE in `<head>` → `data-theme` applied before any CSS renders → no flash. `Theme.toggle()` updates localStorage + DOM + icon state on every page.
- **Language**: localStorage `jp_lang` read by `I18n.init()` on DOMContentLoaded → `dir`/`lang`/`data-lang` set on `<html>` → all `[data-i18n]` elements translated → Cairo font loaded for Arabic → `[data-lang-btn]` active state synced.

---

## Phase 13-B — Language System (EN/AR with RTL Support)
**Status:** Complete

### New files
| File | Purpose |
|---|---|
| `js/i18n.js` | Full EN/AR translation engine with 200+ keys; auto-applied on DOMContentLoaded |
| `css/rtl.css` | `[dir="rtl"]` layout overrides for all components; imports Cairo Arabic font |

### How it works
- `data-i18n="key"` on any element → text swapped on language change
- `data-i18n-placeholder`, `data-i18n-aria`, `data-i18n-title` for non-text attributes
- `I18n.setLang('ar')` sets `<html dir="rtl" lang="ar" data-lang="ar">` and adds `body.font-arabic` for Cairo typeface
- Persists to `localStorage` under `jp_lang`
- All 44 HTML pages now load `i18n.js`; `index.html` fully annotated as showcase

### RTL overrides cover
Navbar, sidebar (flips to right side), breadcrumb, hero, search bar, filter panel, cards, forms, buttons, dropdowns, modals, data tables, pagination, toasts, quick search, notification panel, footer

---

## Phase 13-C — Dark Mode
**Status:** Complete

### New files
| File | Purpose |
|---|---|
| `js/theme.js` | Dark/light toggle with IIFE for flash-free init; persists to `localStorage` |
| `css/dark-mode.css` | `[data-theme="dark"]` CSS variable overrides for all tokens and components |

### How it works
- IIFE in `theme.js` runs synchronously before DOM ready → applies saved theme instantly, no flash
- `Theme.toggle()` / `Theme.set('dark')` — public API
- All tokens overridden: surfaces, text, borders, accent lights, shadows
- Element-specific overrides: navbar, sidebar, cards, forms, search, buttons, dropdowns, modals, tables, tabs, tags, hero, footer, skeleton loaders, global controls, quick search, notification panel

### Controls injection
- `main.js` now calls `injectGlobalControls()` on DOMContentLoaded
- Injects language switcher (EN/AR buttons) + dark mode toggle (sun/moon) into `.navbar-public__actions` and `.navbar-dashboard__top-links`
- No HTML changes needed per page — all 44 pages get the controls automatically
- New component styles added to `components.css`: `.global-controls`, `.lang-switcher`, `.lang-btn`, `.btn-icon-ghost`

---

## Phase 12 — Navbar Unification & Template Content Removal
**Status:** Complete

### 1. Replaced `navbar-dashboard` (2-row) with `navbar-public` (single-row) on all public pages
- **Root cause:** 5 public pages (`browse-jobs`, `browse-employers`, `browse-candidates`, `single-job`, `single-employer`, `single-candidate`) still used `navbar-dashboard` which renders as two stacked rows (a utility bar + a logo/search bar), making it look like two separate navbars.
- **Fix:** Replaced with `navbar-public` (single-row sticky navbar with mobile menu inside `<nav>`) matching the pattern used on `index.html` and all static pages. Also removed orphaned `mobile-nav-drawer` divs outside the header.

| Page | Before | After |
|---|---|---|
| `browse-jobs.html` | `navbar-dashboard` (2-row) | `navbar-public` (single-row) |
| `browse-employers.html` | `navbar-dashboard` (2-row) + `mobile-nav-drawer` | `navbar-public` (single-row) |
| `browse-candidates.html` | `navbar-dashboard` (2-row) + `mobile-nav-drawer` | `navbar-public` (single-row) |
| `single-job.html` | `navbar-dashboard` (2-row) | `navbar-public` (single-row) |
| `single-employer.html` | `navbar-dashboard` (2-row) + `mobile-nav-drawer` | `navbar-public` (single-row) |
| `single-candidate.html` | `navbar-dashboard` (2-row) + `mobile-nav-drawer` | `navbar-public` (single-row) |

### 2. Replaced template company names in homepage "Top Companies" section
- **Before:** Dribbble, Google Inc., Twitter, Spotify, Adobe Inc., Slack (real brand names from Jobpilot template)
- **After:** Nexova, TechCore, Pulsify, Soundry, Designly, Teamflow (neutral invented names)
- Also changed all company card links from `single-employer.html` → `browse-employers.html`

### 3. Replaced stock testimonial author names in homepage
- **Before:** Ralph Edwards (UI Designer at Google), Courtney Henry (HR Manager at TechSoft), Wade Warren (Backend Developer)
- **After:** Alex Morgan (Product Designer, San Francisco), Sara Nguyen (Hiring Manager, Austin TX), James Okafor (Software Engineer, New York)
- Also updated avatar initials to match new names (R→A, C→S, W→J)

---

## Phase 11 — Full Site QA: Navbar Structure, Template Content, Copyright, Grammar
**Status:** Complete

### 1. Navbar mobile menu — moved inside `<header>` on all public pages
- **Root cause:** `about.html`, `blog.html`, `faqs.html`, `contact.html` had `.navbar-mobile-menu` div placed *outside* `<header class="navbar-public">`. Because the header is `position:sticky`, the mobile drawer was detached from the sticky element and would appear in the wrong position when opened on mobile.
- **Fix:** Moved `<div class="navbar-mobile-menu">` *inside* the `<header>` element on all four pages, matching the pattern used in `index.html`.

### 2. `terms.html` — complete navbar overhaul
- **Root cause:** Page still used the old, undefined CSS classes: `navbar-public__links`, `navbar-public__hamburger`, `btn-outline-primary`, `mobile-nav-drawer`. The mobile hamburger did nothing and desktop links were not properly hidden at mobile breakpoints.
- **Fix:** Replaced with correct classes (`navbar-public__nav`, `navbar-toggle`, `btn-outline`, `navbar-mobile-menu`) and moved mobile drawer inside the header. Consistent with all other public pages now.

### 3. Copyright — all remaining pages
- **Root cause:** `@ 2024` (wrong symbol, wrong year) still present on 5 pages; `© 2021` on 2 pages.
- **Fix:** All pages now use `&copy; 2025 Jobpilot. All rights reserved.`

| Page | Before | After |
|---|---|---|
| `terms.html` | `@ 2024 … All rights Reserved` | `© 2025 … All rights reserved.` |
| `browse-employers.html` | `@ 2024 … All rights Reserved` | `© 2025 … All rights reserved.` |
| `browse-candidates.html` | `@ 2024 … All rights Reserved` | `© 2025 … All rights reserved.` |
| `single-employer.html` | `@ 2024 … All rights Reserved` | `© 2025 … All rights reserved.` |
| `single-candidate.html` | `@ 2024 … All rights Reserved` | `© 2025 … All rights reserved.` |
| `single-blog.html` | `@ 2024 … All rights Reserved` | `© 2025 … All rights reserved.` |
| `browse-jobs.html` | `© 2021` | `© 2025` |
| `single-job.html` | `© 2021` | `© 2025` |

### 4. Fake phone numbers removed from all pages
- **Root cause:** `+1-202-555-0178` and `(310) 555-0115` appeared in navbar utility bars and footer across 8 pages and 2 component files.
- **Fix:** All replaced with `support@jobpilot.com` in navbar utility spans; footer descriptions updated to the site tagline.

Files updated: `browse-jobs.html`, `single-job.html`, `browse-employers.html`, `single-employer.html`, `browse-candidates.html`, `single-candidate.html`, `candidate-dashboard/dashboard.html`, `components/footer.html`, `components/navbar-dashboard.html`.

### 5. Fake address removed
- `components/footer.html`: removed `6391 Elgin St. Dallas, Delaware 10299` block.
- `browse-employers.html`: removed `<address>Radius, San Francisco, California</address>`.
- `contact.html`: replaced fake phone in info card with `Available via email or contact form`.
- `terms.html`: removed fake address and phone from contact section.

### 6. Grammar and section title fixes — `index.html`
| Before | After |
|---|---|
| "No.1 Job Hunt Website" (eyebrow) | "Find Your Next Career Move" |
| "Popular category" | "Popular Categories" |
| "Featured job" | "Featured Jobs" |
| "Top companies" | "Top Companies" |
| "Clients Testimonial" | "What Our Users Say" |
| "Various versions have evolved…" (subtitle) | "Thousands of candidates and employers trust Jobpilot to make the right connections." |
| `38,47,154` (Indian number format) | `3,847,154` |

### 7. Auth page text fix — `register.html`
| Before | After |
|---|---|
| `1,75,324` (Indian format) | `175,324` |
| "waiting for good employees" | "waiting for great employers" |

### 8. Grammar fix — dashboard navbar
- "Customer Supports" → "Customer Support" in `browse-jobs.html`, `single-job.html`, and `components/navbar-dashboard.html`.

---

## Phase 10 — Public Page Cleanup (Navbar, Text, Grammar)
**Status:** Complete

### 1. Navbar class fix — `about.html`, `blog.html`, `faqs.html`
- **Root cause:** All three pages used undefined CSS classes: `navbar-public__links`, `navbar-public__hamburger`, `btn-outline-primary`, `mobile-nav-drawer`.
- **Fix:** Replaced with correct design-system classes: `navbar-public__nav` with `.navbar-public__link` anchors, `navbar-toggle`, `btn-outline`, `navbar-mobile-menu`.
- Mobile drawer restructured to match the pattern used in `contact.html` and `index.html` (flat links + action buttons, no nested `<nav>` wrapper).

### 2. About.html team section fix — `pages/static/about.html`
- **Root cause:** Team section used JavaScript template literal syntax (`${[...].map(...).join('')}`) inside static HTML — this renders as raw text, not HTML elements.
- **Fix:** Converted to four static HTML `<div>` cards matching the original data (Robert Fox, Dianne Russell, Floyd Miles, Bessie Cooper).

### 3. Homepage Lorem Ipsum removal — `index.html`
- Hero subtitle: replaced Latin placeholder with real copy.
- "How Jobpilot Works" step descriptions (×4): replaced `"Mauris cursus volutpat lorem vel commodo..."` with real English descriptions.

### 4. Grammar and wording fixes
| Location | Before | After |
|---|---|---|
| `index.html` CTA | "Become a Employers" | "Become an Employer" |
| `login.html` | "Forget password" | "Forgot password?" |
| `login.html` auth image | "waiting for good employees" | "waiting for great employers" |

### 5. Footer cleanup
| Location | Before | After |
|---|---|---|
| `index.html` footer desc | Fake address & phone number | Real tagline copy |
| `index.html` copyright | `© 2021` | `© 2025` |
| `about.html` copyright | `@ 2024 … All rights Reserved` | `© 2025 … All rights reserved.` |
| `blog.html` copyright | `@ 2024 … All rights Reserved` | `© 2025 … All rights reserved.` |
| `faqs.html` copyright | `@ 2024 … All rights Reserved` | `© 2025 … All rights reserved.` |
| `contact.html` copyright | `@ 2024 … All rights Reserved` | `© 2025 … All rights reserved.` |

---

## Phase 9 — UI/Logic QA Pass (Issues from Screenshots)
**Status:** Complete

### 1. Register role selector fix — `js/pages.js`
- **Root cause:** `initRegisterPage()` called `form.querySelectorAll('.auth-role-btn')` but the role buttons are rendered *outside* the `<form>` tag (they're in a sibling div above the form).
- **Fix:** Changed to `document.querySelectorAll('.auth-role-btn')` so both buttons are found regardless of DOM position.
- Also: `selectedRole` now reads the initially-active button via `document.querySelector('.auth-role-btn.active')` so the default is correctly read from HTML, not hardcoded.

### 2. Mobile nav drawer for browse pages — `js/main.js`
- **Root cause:** `initMobileNav()` only bound `.navbar-toggle` + `.navbar-mobile-menu`. Browse-employers/browse-candidates use `.navbar-dashboard__hamburger` + `#mobile-drawer` without a sidebar — so `initSidebar()` returned early (no `.sidebar` present) and the hamburger did nothing.
- **Fix:** Added a second binding in `initMobileNav()` that targets `.navbar-dashboard__hamburger` + `#mobile-drawer` when no `.sidebar` exists on the page. Also added click-outside-to-close.

### 3. Missing CSS classes — `css/pages.css`
Added the following missing class definitions:
| Class | Used in | Fix |
|---|---|---|
| `.page-header`, `.page-header__title` | browse-employers, browse-candidates, browse-jobs | Added — bg, padding, title style |
| `.breadcrumb__item`, `.breadcrumb__sep`, `.breadcrumb__item--active` | browse-employers, browse-candidates | Added — extends base `.breadcrumb` |
| `.mobile-nav-drawer` | browse-employers, browse-candidates | Added — hidden by default, `.open` shows it |
| `.filter-panel__section`, `.filter-panel__title` | browse-candidates | Added — card-style section with title |
| `.checkbox-label` | browse-candidates | Added — flex label for checkbox/radio |
| `.badge-primary-light` | browse-employers card | Added — primary-light bg pill |
| `.site-footer` + all sub-classes | contact, browse-employers, browse-candidates | Added — mirrors `.footer` design, with responsive breakpoints |

### 4. Contact page navbar fix — `pages/static/contact.html`
- Replaced non-existent `.navbar-public__links` → `.navbar-public__nav` with correct `.navbar-public__link` anchors
- Replaced non-existent `.navbar-public__hamburger` → `.navbar-toggle`
- Replaced non-existent `.btn-outline-primary` → `.btn-outline`
- Replaced `mobile-nav-drawer` with `navbar-mobile-menu` structure (standard pattern from index.html)
- Fixed contact form 2-col grid: inline `style="display:grid;grid-template-columns:1fr 1fr"` → `class="form-grid"` (collapses on mobile)

### 5. Browse Jobs navbar containers — `pages/jobs/browse-jobs.html`
- Added `container` class to both `navbar-dashboard__top-inner` and `navbar-dashboard__bottom-inner` (these were missing, causing the navbar content to stretch full-width with no max-width centering)
- Replaced inline-styled page header `<div>` with proper `<section class="page-header">` and `.page-header__title` + `.breadcrumb__item` classes

### 6. Employer card layout fix — `pages/employers/browse-employers.html`
- `renderEmployerCard()` was using mixed inline styles instead of the `.employer-card__*` CSS classes already defined in `components.css`
- Rewrote to use `.employer-card__name`, `.employer-card__industry`, `.badge-primary-light`
- Added `flex-direction:column` so cards stack correctly

### 7. Candidate card layout fix — `pages/candidates/browse-candidates.html`
- `renderCandidateCardLocal()` was fully inline-styled instead of using `.candidate-card__*` CSS classes
- Rewrote to use `.candidate-card__avatar-placeholder`, `.candidate-card__name`, `.candidate-card__role`

### Files Modified
| File | Change |
|---|---|
| `js/pages.js` | Register role selector: `form.querySelectorAll` → `document.querySelectorAll` |
| `js/main.js` | `initMobileNav()`: also binds hamburger + mobile-nav-drawer for sidebar-less pages |
| `css/pages.css` | Added 8 missing class groups (page-header, breadcrumb variants, mobile-nav-drawer, filter-panel section, checkbox-label, badge-primary-light, site-footer) |
| `pages/static/contact.html` | Fixed navbar class names; fixed form grid; fixed mobile menu structure |
| `pages/jobs/browse-jobs.html` | Added `container` to navbar inner divs; converted page header to semantic classes |
| `pages/employers/browse-employers.html` | Rewrote `renderEmployerCard()` to use CSS classes |
| `pages/candidates/browse-candidates.html` | Rewrote `renderCandidateCardLocal()` to use CSS classes |

### What still depends on missing backend endpoints
| Page | Depends on | Behavior without backend |
|---|---|---|
| `browse-jobs.html` | `GET /api/jobs` | Shows 0 jobs + empty state (correct) |
| `index.html` featured jobs | `GET /api/jobs` | Skeleton placeholders stay visible (correct) |
| `single-job.html` | `GET /api/jobs/{id}` | Error toast (correct) |
| `favorite-jobs.html` | `GET /api/candidate/favorites` | Error empty state (correct) |
| `candidate/dashboard.html` | `GET /api/candidate/dashboard` + `GET /api/jobs/recommended` | Skeleton loaders stay (correct) |
| `employer/dashboard.html` | `GET /api/employer/dashboard` + `GET /api/employer/applications` | Skeleton loaders stay (correct) |
| `employer/applications.html` | `GET /api/employer/applications` | Empty table (correct) |

---

## Phase 8 — Final Responsive, QA & Cleanup Pass
**Status:** Complete

### Goals
Fix all responsiveness issues across desktop, tablet, and mobile. Ensure navbars, sidebars,
modals, tables, forms, and dropdowns behave consistently. Remove dead class mismatches and
missing CSS definitions discovered during audit.

### Issues Fixed

#### 1. Mobile sidebar non-functional — `js/main.js`
- **Root cause:** `initSidebar()` bound `[data-sidebar-toggle]` but all 24 dashboard pages
  use `.navbar-dashboard__hamburger` buttons with no `data-sidebar-toggle` attribute.
- **Fix:** Updated selector to `[data-sidebar-toggle], .navbar-dashboard__hamburger`.
- **Fix:** Dynamically create `.sidebar-overlay` div if not present in markup.
- **Fix:** Body scroll locked (`overflow: hidden`) while sidebar is open.

#### 2. Missing `.navbar-dashboard__hamburger` CSS — `css/layout.css`
- Button had no styles; not displayed at any breakpoint.
- Added base rule (`display: none`, 40×40, flex-center, hover state).
- Added `display: flex` at `md` breakpoint in `responsive.css`.

#### 3. Missing CSS alias classes — `css/layout.css`
Dashboard pages use alternate class names not defined in layout.css. Added full definitions:
| HTML class used | Maps to |
|---|---|
| `.navbar-dashboard__actions` | `.navbar-dashboard__right` |
| `.navbar-dashboard__icon-btn` | `.navbar-dashboard__notif` |
| `.navbar-dashboard__top-links` | `.navbar-dashboard__nav` links |
| `.navbar-dashboard__top-right` | `.navbar-dashboard__utility` |
| `.navbar-dashboard__search-input` | search wrapper with icon positioning |
| `.navbar-dashboard__badge` | `.navbar-dashboard__notif-badge` |

#### 4. Avatar button with text initials — `css/layout.css`
- `button.navbar-dashboard__avatar` had no flex/centering or background for initials display.
- Added `display:flex; align-items:center; justify-content:center` with primary-light background.

#### 5. Sidebar header elements undefined — `css/layout.css`
- `.sidebar__header`, `.sidebar__avatar`, `.sidebar__name`, `.sidebar__role` had no styles
  but are used in all candidate and employer dashboard sidebar HTML.
- Added full definitions.

#### 6. Table overflow on mobile — 3 employer dashboard pages
- Table containers used inline `overflow:hidden` which clipped tables on small screens.
- Changed to `overflow-x:auto` and added `min-width` on tables to ensure proper scroll:
  - `employer-dashboard/applications.html`
  - `employer-dashboard/my-jobs.html` — table gets `min-width:600px`
  - `employer-dashboard/plans-billing.html` — table gets `min-width:480px`
- Added responsive rule in `css/responsive.css` (sm breakpoint): `dashboard-main table`
  gets `display:block; overflow-x:auto; min-width:540px`.

#### 7. Register form 2-col grid not responsive — `pages/auth/register.html`
- First name / username row used inline `style="display:grid;grid-template-columns:1fr 1fr"`.
  Inline styles cannot be overridden by `responsive.css`, so the grid stayed 2-col on mobile.
- Changed to `class="form-grid"` (already collapses to 1-col at `md` breakpoint).

### Files Modified
| File | Change |
|---|---|
| `js/main.js` | `initSidebar()` — hamburger bind + dynamic overlay + body scroll lock |
| `css/layout.css` | Hamburger, avatar initials, sidebar header, 6 alias classes |
| `css/responsive.css` | Show hamburger at md; table overflow at sm |
| `pages/employer-dashboard/applications.html` | `overflow:hidden` → `overflow-x:auto` |
| `pages/employer-dashboard/my-jobs.html` | `overflow:hidden` → `overflow-x:auto`; table `min-width` |
| `pages/employer-dashboard/plans-billing.html` | `overflow:hidden` → `overflow-x:auto`; table `min-width` |
| `pages/auth/register.html` | Inline 2-col grid → `form-grid` class |

---

## Phase 7 — API Wiring Cleanup & Integration Pass
**Status:** Complete

### Goals
Audit every page against API_SPEC.md, centralize API-driven logic in `pages.js`, remove
dead code, and verify consistency across navigation, forms, modals, toasts, and components.

### Audit results — all 11 API endpoints

| Endpoint | Used in | Status |
|---|---|---|
| `POST /api/auth/login` | `auth/login.html` via `pages.js` | ✓ Wired |
| `POST /api/auth/register` | `auth/register.html` via `pages.js` | ✓ Wired |
| `GET /api/jobs` | `jobs/browse-jobs.html` via `pages.js` | ✓ Wired |
| `GET /api/jobs/{id}` | `jobs/single-job.html` via `pages.js` | ✓ Wired |
| `GET /api/jobs/recommended` | `candidate-dashboard/dashboard.html` inline | ✓ Wired |
| `POST /api/jobs/{id}/apply` | `jobs/single-job.html` via `pages.js` | ✓ Wired |
| `GET /api/candidate/dashboard` | `candidate-dashboard/dashboard.html` inline | ✓ Wired |
| `GET /api/candidate/favorites` | `candidate-dashboard/favorite-jobs.html` via `pages.js` | ✓ Wired |
| `POST /api/candidate/upload-cv` | `candidate-dashboard/settings-profile.html` inline | ✓ Wired |
| `GET /api/employer/dashboard` | `employer-dashboard/dashboard.html` inline | ✓ Wired |
| `POST /api/employer/jobs` | `employer-dashboard/post-job.html` inline | ✓ Wired |
| `GET /api/employer/applications` | `employer-dashboard/applications.html` + `dashboard.html` inline | ✓ Wired |

### Changes to js/pages.js
- **Rewrote** `initBrowseJobsPage()`: fixed element selector (`#jobs-grid` vs old `.jobs-grid`),
  added complete search, pagination, and bookmark re-bind logic
- **Rewrote** `initSingleJobPage()`: fills all elements (breadcrumb, company, location,
  description, logo, salary, overview), loads related jobs, wires apply form and bookmarks
- **Added** `initFavoriteJobsPage()`: session init, skeleton loaders, API call, pagination,
  in-memory remove (no DELETE endpoint in spec), error empty state
- **Removed** dead functions: `initCandidateDashboard()`, `initEmployerDashboard()`,
  `initApplicationsPage()`, `initPostJobPage()`, `renderGrid` call — all had wrong element IDs
  or referenced pages that use correct inline scripts and do not include `pages.js`
- **Updated** switch statement: now handles only pages that actually include `pages.js`
  (`login`, `register`, `browse-jobs`, `single-job`, `favorite-jobs`)

### Pages migrated to pages.js (inline scripts removed)
| Page | Removed inline | Added script |
|---|---|---|
| `jobs/browse-jobs.html` | ~45 lines inline `<script>` | `pages.js` |
| `jobs/single-job.html` | ~65 lines inline `<script>` | `pages.js` |
| `candidate-dashboard/favorite-jobs.html` | ~115 lines inline `<script>` | `pages.js` |

### Pages keeping inline scripts (correct, no conflict with pages.js)
- `candidate-dashboard/dashboard.html` — inline API + render (complex, session-aware)
- `employer-dashboard/dashboard.html` — inline API + dynamic stats rendering
- `employer-dashboard/applications.html` — inline GET /api/employer/applications
- `employer-dashboard/post-job.html` — inline POST /api/employer/jobs

### DEMO_DATA pages (no supported API endpoint — keep as-is)
- `candidate-dashboard/applied-jobs.html` — no GET /api/candidate/applied-jobs
- `candidate-dashboard/job-alerts.html` — no alerts endpoint
- `candidate-dashboard/settings-*.html` — no PUT /api/candidate/profile
- `employer-dashboard/my-jobs.html` — no GET /api/employer/jobs
- `employer-dashboard/single-application.html` — no GET /api/employer/applications/{id}
- `employer-dashboard/saved-candidates.html` — no saved-candidates endpoint
- `employer-dashboard/plans-billing.html` — no billing endpoint
- `employer-dashboard/settings-*.html` — no PUT /api/employer/profile
- `employer-dashboard/account-setup-*.html` — no setup wizard endpoint
- `candidates/browse-candidates.html`, `single-candidate.html` — no candidates API
- `employers/browse-employers.html`, `single-employer.html` — no employers API
- `static/*` — no API

### apply modal CV select (single-job.html)
The CV dropdown has two DEMO_DATA options (`Professional Resume`, `Product Designer CV`).
No GET /api/candidate/cvs endpoint exists in spec to populate this dynamically.
The cv_id value is sent to POST /api/jobs/{id}/apply as-is.

### Navigation audit
- All sidebar links use relative paths (e.g. `dashboard.html`, `applications.html`) ✓
- All cross-folder links use `../folder/page.html` pattern ✓
- All footer links in public pages point to correct relative paths ✓
- Employer sidebar active states correct on all 16 employer pages ✓
- Candidate sidebar active states correct on all 8 candidate pages ✓
- Settings sub-nav active states correct on all 4 settings tabs (both employer and candidate) ✓

### Consistent behavior verified
- Toast: all pages have `<div id="toast-container"></div>`, Toast.* works globally ✓
- Modal: `data-modal-open` / `data-modal-close` wired in main.js DOMContentLoaded ✓
- Dropdowns: `initDropdowns()` in main.js, all navbar dropdowns use `data-dropdown` ✓
- Tabs: `initTabs()` in main.js handles `.tabs` containers (single-job.html, FAQs, etc.) ✓
- Pagination: `renderPagination()` in main.js used by browse-jobs, favorite-jobs ✓
- Bookmarks: re-bound after each grid render in `initBrowseJobsPage()` ✓
- Logout: `[data-logout]` on all sidebar logout buttons, handled by main.js ✓
- Session: navbar avatar populated from `Session.get()` on all dashboard pages ✓

---

## Phase 2 — Folder Structure & Page Inventory
**Status:** Complete

### What was done
- Analyzed all 52 UI screens across 5 categories
- Identified and catalogued every unique page
- Identified all reusable components
- Proposed and agreed on the final corrected folder structure

### Final folder structure
```
frontend/
├── index.html
├── CHANGES.md
├── css/
│   ├── variables.css
│   ├── base.css
│   ├── components.css
│   ├── layout.css
│   ├── pages.css
│   └── responsive.css
├── js/
│   ├── api.js
│   ├── main.js
│   ├── components.js
│   └── pages.js
├── components/
│   ├── navbar-public.html
│   ├── navbar-dashboard.html
│   ├── footer.html
│   ├── sidebar-candidate.html
│   ├── sidebar-employer.html
│   ├── job-card.html
│   ├── employer-card.html
│   ├── candidate-card.html
│   └── modal.html
├── pages/
│   ├── auth/
│   ├── jobs/
│   ├── employers/
│   ├── candidates/
│   ├── candidate-dashboard/
│   ├── employer-dashboard/
│   └── static/
└── assets/
    ├── images/
    └── icons/
```

---

## Phase 3 — Shared Design System + Core Components
**Status:** Complete
**Date:** 2026-03-14

### CSS Design System

| File | Contents |
|------|----------|
| `css/variables.css` | All design tokens: brand colors, neutrals, typography scale, spacing (4-pt grid), border-radius, shadows, transitions, layout dimensions. Imports Inter from Google Fonts. |
| `css/base.css` | Hard reset, body/type defaults, h1–h6 scale, layout containers (.container, .section), flex/grid helpers, text/spacing utilities, dashboard layout shell, breadcrumb, scrollbar styling |
| `css/components.css` | 20 component definitions: buttons (5 variants + sizes), job-type badges (7 types), job card, employer card, candidate card, stat card, form inputs/select/textarea/checkbox/upload-zone, tabs, pagination, modal system, alert/profile banners, popular search tags, dropdown menu, toast notifications, data table, search bar, avatar, section header, empty state, skeleton loader |
| `css/layout.css` | Public navbar (1-row logged-out), Dashboard navbar (2-row logged-in), site footer (dark 5-column), candidate sidebar, employer sidebar, filter panel, listing page layout, settings layout, form grid |
| `css/pages.css` | Homepage hero, category cards, "how it works" steps, testimonials, CTA banner, auth page (form + image split), single job detail layout, dashboard cards, error/404 pages, blog cards, FAQ accordion, pricing cards, account setup stepper |
| `css/responsive.css` | 5 breakpoints: 1200px, 1024px, 768px, 576px, 400px. Mobile navbar, sidebar overlay, grid collapse, modal bottom-sheet on mobile, print styles |

### Design Tokens Extracted from UI
- **Primary:** `#0A65CC` / hover `#0850A8` / light `#E7F0FA`
- **Success:** `#0BA02C` · **Danger:** `#E05151` · **Warning:** `#F59E0B`
- **Accent:** Purple `#7B61FF` · Orange `#FF6636` · Indigo `#4640DE`
- **Text:** Dark `#18191C` · Secondary `#474C54` · Muted `#767F8C`
- **Border:** `#E4E5E8` · Background: `#F1F2F4` · Footer dark: `#18191C`
- **Font:** Inter (400/500/600/700)
- **Spacing:** 4-pt grid (4px → 96px)
- **Radius:** 2px / 4px / 8px / 12px / 16px / 24px / full

### JavaScript Files

| File | Contents |
|------|----------|
| `js/api.js` | All API calls mapped exactly to API_SPEC.md: register, login, getJobs, getJob, getRecommendedJobs, applyForJob, getCandidateDashboard, getEmployerDashboard, postJob, getEmployerApplications. Central `apiFetch()` wrapper with error handling. |
| `js/main.js` | Session management, Toast notification system (success/error/warning/info), Modal.open/close system, dropdown/tab/accordion/sidebar initializers, pagination renderer, form validation helpers (Validate.*), bookmark toggle, active nav link highlight, debounce utility |
| `js/components.js` | renderJobCard(), renderEmployerCard(), renderCandidateCard(), renderStatCard(), renderBadge(), renderApplicationRow(), renderGrid() — all use live data from API calls |
| `js/pages.js` | Page-specific logic: login, register, browse jobs, single job, apply modal, candidate dashboard, employer dashboard, applications list, post job form, filter tags. Auto-initializes based on current page filename. |

### HTML Components

| File | Description |
|------|-------------|
| `components/navbar-public.html` | Logged-out navbar: logo, 6 nav links, Sign In + Post A Job buttons, hamburger + mobile drawer |
| `components/navbar-dashboard.html` | 2-row logged-in navbar: top row (nav links + phone + language dropdown), bottom row (logo + country selector + search + notifications + user avatar/dropdown). Role-aware via JS. |
| `components/footer.html` | Dark footer: brand column, Quick Links, Candidate, Employers, Support columns + copyright + social icons |
| `components/sidebar-candidate.html` | 5 links: Overview, Applied Jobs, Favorite Jobs, Job Alert (badge), Settings + logout. Active link auto-highlighted via JS. |
| `components/sidebar-employer.html` | 9 links: Overview, Employers Profile, Post a Job, My Jobs, Saved Candidate, Plans & Billing, All Applications, All Companies, Settings + logout |
| `components/job-card.html` | Card with type badge, bookmark button, title, company logo, location + salary meta |
| `components/employer-card.html` | Card with logo, name, industry, type badge, Open Positions button |
| `components/candidate-card.html` | Horizontal card with avatar, name, role, location, experience, bookmark + View Profile button |
| `components/modal.html` | 4 modal instances: Apply Job, Confirm/Delete, Promote Job, Post Job Success |

---

## Phase 4 — Public Pages
**Status:** Complete
**Date:** 2026-03-14

### Pages Built

| Page | File | Notes |
|------|------|-------|
| Homepage | `index.html` | Hero, category grid, how-it-works, featured jobs (API), top companies, testimonials, CTA, footer |
| Login | `pages/auth/login.html` | Split-screen, wired to API.login(), password toggle, social login buttons (UI only) |
| Register | `pages/auth/register.html` | Role toggle (Candidate/Employer), wired to API.register(), terms link |
| Email Verification | `pages/auth/email-verification.html` | 6-digit OTP inputs with auto-focus, resend button |
| Forgot Password | `pages/auth/forgot-password.html` | Email input, simulated send (no API endpoint in spec) |
| Reset Password | `pages/auth/reset-password.html` | New password + confirm, client-side validation (min 8 chars, match) |
| Browse Jobs | `pages/jobs/browse-jobs.html` | Dashboard navbar, filter panel, API.getJobs(), pagination, search, salary slider |
| Single Job | `pages/jobs/single-job.html` | Job detail, tabs, apply modal, API.getJob(id), API.applyForJob(), related jobs |
| Browse Employers | `pages/employers/browse-employers.html` | Dashboard navbar, employer grid, static data, search + industry filter, pagination |
| Single Employer | `pages/employers/single-employer.html` | Company profile banner, about, open positions (API.getJobs fallback), company info sidebar |
| Browse Candidates | `pages/candidates/browse-candidates.html` | Dashboard navbar, filter panel, static data, category + experience + location filters |
| Single Candidate | `pages/candidates/single-candidate.html` | Candidate profile, skills, work experience, education, profile info sidebar |
| About | `pages/static/about.html` | Public navbar, mission section with stats, core values, team grid, CTA section |
| Contact | `pages/static/contact.html` | Contact info cards, contact form with client-side validation |
| Blog | `pages/static/blog.html` | Category tabs, paginated post grid, sidebar (search + tags + recent posts), static data |
| Single Blog | `pages/static/single-blog.html` | Article with hero, meta, content, author, share buttons, related posts sidebar, newsletter |
| FAQs | `pages/static/faqs.html` | Category tabs (General/Candidate/Employer), accordion questions, contact CTA |
| Terms | `pages/static/terms.html` | TOC navigation, 8 sections with anchor links |
| 404 | `pages/static/404.html` | Illustrated error page, homepage + browse jobs CTAs, quick links |
| Coming Soon | `pages/static/coming-soon.html` | Dark gradient, live countdown timer, email notify form |

### Architecture Decisions

- **Component strategy:** All navbar/footer HTML is inlined per page (not JS-fetched) for `file://` compatibility
- **Navbar variants:** Public 1-row (homepage/static), Dashboard 2-row (browse/detail pages), None (auth pages)
- **Dynamic data:** browse-jobs and single-job use real API calls (API.getJobs, API.getJob, API.applyForJob). Featured jobs on homepage also uses API.getJobs.
- **Static fallback:** browse-employers, browse-candidates, single-employer use static data arrays (no corresponding API endpoints in spec)
- **Pagination:** All listing pages use `renderPagination()` from main.js with client-side slicing
- **Skeleton loaders:** Shown until API data loads; replaced by rendered cards

---

---

## Phase 5 — Candidate Dashboard Pages
**Status:** Complete
**Date:** 2026-03-15

### Pages Built

| Page | File | API Used | Data Source |
|------|------|----------|-------------|
| Dashboard Overview | `pages/candidate-dashboard/dashboard.html` | `GET /api/candidate/dashboard`, `GET /api/jobs/recommended` | Real API; dashes shown on failure |
| Applied Jobs | `pages/candidate-dashboard/applied-jobs.html` | None | `DEMO_DATA` (no `GET /api/candidate/applications` in spec) |
| Favorite Jobs | `pages/candidate-dashboard/favorite-jobs.html` | None | `DEMO_DATA` (no `GET /api/candidate/favorites` in spec) |
| Job Alerts | `pages/candidate-dashboard/job-alerts.html` | None | `DEMO_DATA` (no job alerts endpoint in spec) |
| Settings — Personal Info | `pages/candidate-dashboard/settings-personal.html` | None (save would need `PUT /api/candidate/profile`) | Form only; toast on submit |
| Settings — Profile | `pages/candidate-dashboard/settings-profile.html` | None (save would need `PUT /api/candidate/profile`) | Form only; inline skills tag manager |
| Settings — Social Links | `pages/candidate-dashboard/settings-social.html` | None (save would need `PUT /api/candidate/profile`) | Form only; URL validation |
| Settings — Account | `pages/candidate-dashboard/settings-account.html` | None (would need `PUT /api/auth/email`, `PUT /api/auth/password`) | Form only; honest non-functional feedback |

### API Usage

| Endpoint | Page | What it populates |
|----------|------|-------------------|
| `GET /api/candidate/dashboard` | `dashboard.html` | Applied Jobs count, Favorite Jobs count, Profile Completion % + progress bar |
| `GET /api/jobs/recommended` | `dashboard.html` | Recommended jobs grid |

### Demo Data (Isolated)

All demo data is scoped to page-level `<script>` blocks and clearly commented `// DEMO_DATA — no <endpoint> in API spec`:

| Page | Demo array | Why static |
|------|-----------|-----------|
| `applied-jobs.html` | `DEMO_APPLICATIONS` (6 rows) | No `GET /api/candidate/applications` endpoint |
| `favorite-jobs.html` | `DEMO_FAVORITES` (5 jobs) | No `GET /api/candidate/favorites` endpoint |
| `job-alerts.html` | `DEMO_ALERTS` (3 alerts) | No job alerts endpoints in spec |

### Frontend-Only Interactions

- **Favorite Jobs remove**: In-memory `removedIds` Set. No API mutation. `Toast.info('Removed from favorites.')`.
- **Job Alert toggle/delete/create**: In-memory `alerts` array. All mutations are client-side only.
- **Skills tag manager** (settings-profile): In-memory `skills` array. Add on Enter or button click; remove with × button.
- **Photo/CV upload**: `Toast.info('... requires backend support.')` — no file upload attempted.
- **Delete Account**: Confirm modal → `Toast.info('Account deletion requires backend support. No action was taken.')`.
- **Social login buttons** (login/register pages): `Toast.info('Social login is not connected yet.')`.

### Architecture Decisions

- **Sidebar**: Inlined per page (not JS-fetched) for `file://` compatibility. Only the `.active` link differs per page.
- **Settings sub-nav**: 4-tab horizontal nav (Personal Info / Profile / Social Links / Account). `.settings-tab--active` class on the active tab per page.
- **API failure**: `dashboard.html` shows `'—'` (not `0` or fake values) when `GET /api/candidate/dashboard` fails.
- **No footer**: Dashboard pages use `dashboard-layout` (sidebar + main area) without a public footer.
- **Demo data notices**: Pages using demo data display a notice banner referencing the missing endpoint.

### Missing Backend Endpoints (needed for full functionality)

| Feature | Required Endpoint |
|---------|------------------|
| Applications list | `GET /api/candidate/applications` |
| Favorites list | `GET /api/candidate/favorites` |
| Remove favorite | `DELETE /api/candidate/favorites/{jobId}` |
| Job alerts list | `GET /api/candidate/alerts` |
| Create/update/delete alert | `POST/PUT/DELETE /api/candidate/alerts` |
| Update profile / personal info / social links | `PUT /api/candidate/profile` |
| Change email | `PUT /api/auth/email` |
| Change password | `PUT /api/auth/password` |
| Upload photo / CV | `POST /api/candidate/upload-cv` ✓ Added to api.js |
| Delete account | `DELETE /api/candidate/account` |

### Phase 5 Corrections Applied
- `GET /api/candidate/favorites` wired in `favorite-jobs.html` — replaced DEMO_DATA with real API call + empty state on error
- `POST /api/candidate/upload-cv` wired in `settings-profile.html` — replaces the "not connected" toast with a real upload handler
- Both endpoints added to `js/api.js` as `API.getCandidateFavorites()` and `API.uploadCv(formData)`

---

## Phase 6 — Employer Dashboard Pages
**Status:** Complete
**Date:** 2026-03-15

### Pages Built

| Page | File | API Used | Data Source |
|------|------|----------|-------------|
| Dashboard Overview | `employer-dashboard/dashboard.html` | `GET /api/employer/dashboard`, `GET /api/employer/applications` | Real API; dashes on failure |
| Account Setup — Personal | `employer-dashboard/account-setup-personal.html` | None | Form only (wizard step 1) |
| Account Setup — Profile | `employer-dashboard/account-setup-profile.html` | None | Form only (wizard step 2) |
| Account Setup — Social | `employer-dashboard/account-setup-social.html` | None | Form only (wizard step 3) |
| Account Setup — Contact | `employer-dashboard/account-setup-contact.html` | None | Form only (wizard step 4) |
| Account Setup — Success | `employer-dashboard/account-setup-message.html` | None | Static success screen (step 5) |
| Post a Job | `employer-dashboard/post-job.html` | `POST /api/employer/jobs` | Real API submit |
| My Jobs | `employer-dashboard/my-jobs.html` | None | `DEMO_DATA` |
| All Applications | `employer-dashboard/applications.html` | `GET /api/employer/applications` | Real API; empty state on failure |
| Single Application | `employer-dashboard/single-application.html` | None | `DEMO_DATA` |
| Saved Candidates | `employer-dashboard/saved-candidates.html` | None | `DEMO_DATA` |
| Plans & Billing | `employer-dashboard/plans-billing.html` | None | `DEMO_DATA` |
| Settings — Personal Info | `employer-dashboard/settings-personal.html` | None | Form only |
| Settings — Company Profile | `employer-dashboard/settings-profile.html` | None | Form only |
| Settings — Social Links | `employer-dashboard/settings-social.html` | None | Form only |
| Settings — Account | `employer-dashboard/settings-account.html` | None | Form only |

### API Usage

| Endpoint | Page | What it populates |
|----------|------|-------------------|
| `GET /api/employer/dashboard` | `dashboard.html` | Open Jobs count, Total Applicants count |
| `GET /api/employer/applications` | `dashboard.html`, `applications.html` | Recent applications list, full applications table |
| `POST /api/employer/jobs` | `post-job.html` | Creates job on form submit |

### Demo Data (Isolated)

All demo data is scoped to page-level `<script>` blocks and clearly commented `// DEMO_DATA — no <endpoint> in API spec`:

| Page | Demo array | Why static |
|------|-----------|-----------|
| `my-jobs.html` | `DEMO_JOBS` (6 jobs) | No `GET /api/employer/jobs` endpoint |
| `single-application.html` | `DEMO_APPLICATION` (1 applicant) | No `GET /api/employer/applications/{id}` endpoint |
| `saved-candidates.html` | `DEMO_SAVED` (5 candidates) | No `GET /api/employer/saved-candidates` endpoint |
| `plans-billing.html` | `PLANS` (3 plans), `BILLING_HISTORY` (1 row) | No billing endpoints |

### Frontend-Only Interactions

- **My Jobs actions** (Edit, Delete): `Toast.info('… requires backend support.')` — no mutation
- **Single Application actions** (Schedule Interview): `Toast.info('Interview scheduling is not connected yet.')`; Accept/Reject: `Toast.success/warning()` — UI state only
- **Saved Candidates remove**: In-memory `removedIds` Set — no API mutation
- **Plan upgrade**: `Toast.info('Plan upgrade requires payment integration.')`
- **Invoice download**: `Toast.info('Invoice download is not connected yet.')`
- **Photo / Logo upload** (settings): `Toast.info('… requires backend support.')`
- **Delete account**: Confirm modal → `Toast.info('Account deletion requires backend support. No action was taken.')`
- **Account setup wizard**: Multi-step form with client-side navigation only — no API calls

### Architecture Decisions

- **Account setup wizard**: 5 separate pages with step indicator CSS. No sidebar — centered wizard layout. Nav via form submit + `setTimeout + redirect`.
- **Sidebar**: `settings-profile.html` serves dual purpose — linked from both "Company Profile" sidebar link and "Company Profile" settings sub-tab.
- **Applications table**: `GET /api/employer/applications` returns `{ application_id, candidate_name, job_title }` only — applied date defaults to `'—'`, status defaults to `'Pending'`.
- **Dashboard failure**: Stats show `'—'` not `0` when `GET /api/employer/dashboard` fails.

### Missing Backend Endpoints (needed for full functionality)

| Feature | Required Endpoint |
|---------|------------------|
| Employer jobs list | `GET /api/employer/jobs` |
| Update/delete job | `PUT/DELETE /api/employer/jobs/{id}` |
| Single application detail | `GET /api/employer/applications/{id}` |
| Update application status | `PUT /api/employer/applications/{id}/status` |
| Saved candidates list | `GET /api/employer/saved-candidates` |
| Remove saved candidate | `DELETE /api/employer/saved-candidates/{id}` |
| Update employer profile | `PUT /api/employer/profile` |
| Plans & billing | `GET/POST /api/employer/billing` |
| Change email | `PUT /api/auth/email` |
| Change password | `PUT /api/auth/password` |
| Delete account | `DELETE /api/employer/account` |
| Logo / photo upload | `POST /api/employer/upload` |

---

## Next Phase

**Phase 7 — API Integration** (planned)
Connect all real API endpoints once backend is ready, remove remaining DEMO_DATA, add auth guards to dashboard pages.
