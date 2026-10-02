# Employee Dashboard — Knowledge Base
### St. Paul University Philippines · SITE Department

> **Purpose**: Single source of truth for design consistency, system memory, and future development reference.
> All developers and AI agents working on this project MUST read and follow this document.

**Last Updated**: 2026-04-07
**Current Phase**: Phase 11 — Document Analytics & Login Page Polish
**Stack**: Laravel 11 · Blade · Tailwind CSS · MySQL · Vite

---

## Table of Contents

1. [Project Overview](#1-project-overview)
2. [Technology Stack](#2-technology-stack)
3. [Access & Credentials](#3-access--credentials)
4. [UI/UX Design Rules — Do's & Don'ts](#4-uiux-design-rules--dos--donts)
5. [Color Palette & Usage](#5-color-palette--usage)
6. [Layout Structure & Design System](#6-layout-structure--design-system)
7. [Component Behavior Rules](#7-component-behavior-rules)
8. [CSS Class Reference](#8-css-class-reference)
9. [Key File Locations](#9-key-file-locations)
10. [Features Summary by Role](#10-features-summary-by-role)
11. [Database Structure](#11-database-structure)
12. [Phase Implementation Log](#12-phase-implementation-log)
13. [Pre-Commit Quality Checklist](#13-pre-commit-quality-checklist)
14. [Future Update Notes](#14-future-update-notes)

---

## 1. Project Overview

The **Employee Dashboard with Data Analytics** is an internal web application for SITE (School of Information Technology and Engineering) employees at St. Paul University Philippines, Tuguegarao City.

It manages documents, tasks, leave requests, calendar events, and performance data across three user roles: Dean, Program Coordinator, and Faculty Employee. The system enforces a **flat, minimalist, no-animation design** throughout all pages and roles.

**Local URL**: `http://127.0.0.1:8000`
**Database**: `employee_dashboard` (MySQL via WAMP)

---

## 2. Technology Stack

| Layer | Technology |
|-------|-----------|
| Backend | Laravel 11 (PHP) |
| Frontend | Blade Templates |
| CSS Framework | Tailwind CSS v4 (via Vite) |
| Build Tool | Vite |
| Database | MySQL (WAMP) |
| Icons | Font Awesome 6 (local NPM) |
| Charts | Chart.js (lazy-loaded, Dean only) |
| Calendar | FullCalendar v6 (lazy-loaded) |
| JS | Vanilla JavaScript |

> **Note**: Font Awesome, Chart.js, and FullCalendar are all installed locally via NPM — no CDN dependencies.

---

## 3. Access & Credentials

| Role | Username | Password |
|------|----------|----------|
| Dean | `dean` | `password123` |
| Program Coordinator | `coordinator` | `password123` |
| Faculty Employee | `faculty` | `password123` |

> Default credentials for development/demo. Change in production.

---

## 4. UI/UX Design Rules — Do's & Don'ts

These rules are **ZERO-EXCEPTION mandatory**. Every page, every component, every role must follow them without exception.

---

### ✅ DO's

| Rule | Reason |
|------|--------|
| Put ALL styles in `resources/css/app.css` | Single source of CSS truth |
| Use Tailwind `@apply` for all classes | Consistent utility approach |
| Use borders to define elements | Flat design without depth |
| Use `border-radius: 0` everywhere | Sharp rectangular design |
| Support both light and dark mode | All components must have `dark:` classes |
| Apply identical design across all three roles | No role-specific visual variations |
| Use `flex` for layout | Responsive, predictable layout |
| Use existing CSS classes before creating new ones | Avoid CSS bloat |
| Name new classes: `.component-element` | Predictable naming convention |
| Run `npm run build` after every CSS change | Compile Tailwind correctly |
| Use green `#028a0f` for all primary actions | Consistent brand color |
| Add `border-0` on all button elements | Remove browser default borders |

---

### ❌ DON'Ts

| Rule | What to avoid |
|------|--------------|
| NO inline `style=""` attributes | Use global CSS classes |
| NO `border-radius`, NO `rounded-*` classes | No rounded corners anywhere |
| NO hover effects | No background change, no transform on `:hover` |
| NO transitions or animations | `transition: none` is globally enforced |
| NO box-shadows on cards or elements | Flat design only |
| NO `transform`, `scale`, `translate` | Static layout only |
| NO component-specific CSS blocks inside blade files | Global CSS only |
| NO role-specific design differences | All roles must look identical |
| NO CDN links for libraries | All assets are local NPM |
| NO new CSS files | Only `app.css` for global styles |
| NO shadows on hover | Globally overridden with `!important` |

---

### Design Philosophy

The system uses a **strict flat, corporate-minimalist aesthetic**:
- Elements are defined by borders, not shadows
- Hierarchy is created through spacing, weight, and size — not depth
- All three roles share 100% identical visual components
- The design should feel like a clean internal enterprise tool

---

## 5. Color Palette & Usage

### Primary Brand Colors

| Name | Hex | Usage |
|------|-----|-------|
| Primary Green | `#028a0f` | Buttons, badges, sidebar active, accents, icons |
| Dark Green | `#026a0c` | Reference only (login right panel background) |
| Light Green | `rgba(2, 138, 15, 0.06)` | Active folder background tint |
| Green Dark Mode | `#02b815` | Dark mode version of primary green |

### Neutral Colors — Light Mode

| Element | Value |
|---------|-------|
| Body Text | `#2c3e50` |
| Light Text / Subtitles | `#6c757d` |
| Faint Text | `#9ca3af` |
| Border | `#e0e0e0` |
| Page Background | `#f8f9fa` |
| Card Surface | `#ffffff` |

### Neutral Colors — Dark Mode

| Element | Value |
|---------|-------|
| Body Text | `#e5e7eb` |
| Light Text | `#9ca3af` |
| Border | `#374151` |
| Page Background | `#1f1f1f` |
| Card Surface | `#2a2a2a` |
| Deep Surface | `#1e1e1e` |

### Status / Badge Colors

| Status | Background | Text | Usage |
|--------|-----------|------|-------|
| Success | `bg-green-100` | `text-green-800` | Active, Completed, Approved |
| Warning | `bg-orange-100` | `text-orange-800` | Pending, In Progress |
| Danger | `bg-red-100` | `text-red-800` | Rejected, Overdue |
| Info | `bg-blue-100` | `text-blue-800` | Neutral labels, counts |
| Neutral | `bg-gray-100` | `text-gray-700` | Activity type chips |

### Document Category Badges
All document category badges use:
- Background: `#028a0f` (solid green)
- Text: `#ffffff` (white)
- Class: `.doc-category-badge`

---

## 6. Layout Structure & Design System

### Global CSS Enforcement (app.css layer base)

The following rules are globally applied at the CSS layer level and **cannot be overridden**:

```
border-radius: 0 !important           — no rounded corners anywhere
transition: none !important           — no animations
animation: none !important            — no keyframes
transform: none !important (on hover) — no movement
box-shadow: none !important (on hover)— no shadow on interaction
```

### Dark Mode Configuration

Dark mode is triggered via `.dark` class on `<html>` or `<body>`:

```
@variant dark (&:is(.dark, .dark *));
```

All components must include `dark:` Tailwind variants.

### Font Stack

```
'Segoe UI', Tahoma, Geneva, Verdana, ui-sans-serif, system-ui, sans-serif
```

### Typography Scale

| Size | Tailwind | Pixels | Use |
|------|----------|--------|-----|
| XS | `text-xs` | 12px | Counts, labels, badges |
| SM | `text-sm` | 14px | Body text, table data |
| Base | `text-base` | 16px | Regular content |
| LG | `text-lg` | 18px | Section titles |
| XL | `text-xl` | 20px | Page headings |

### Spacing Scale

| Role | Tailwind | Value |
|------|----------|-------|
| Tight element gap | `gap-1` | 4px |
| Normal card gap | `gap-3` | 12px |
| Section gap | `gap-6` | 24px |
| Small padding | `p-3` | 12px |
| Standard padding | `p-4` | 16px |
| Card padding | `p-6` | 24px |
| Card bottom margin | `mb-6` | 24px |

### Page Layout Structure

```
┌─────────────────────────────────────────────────┐
│  Top Bar (dark green) — notifications, user menu │
├──────────────┬──────────────────────────────────-┤
│              │  Page Title + Subtitle             │
│  Sidebar     ├──────────────────────────────────-┤
│  (160px)     │  Stats Bar (horizontal flex)       │
│              ├──────────────────────────────────-┤
│  - Dashboard │  Documents Analytics Card           │
│  - My Tasks  ├──────────────────────────────────-┤
│  - Leave     │  Announcements Widget              │
│  - Calendar  ├──────────────────────────────────-┤
│  - Docs      │  Main Content Cards                │
│              │  (Tables, Lists, Charts)           │
└──────────────┴──────────────────────────────────-┘
```

---

## 7. Component Behavior Rules

### 7.1 Buttons

- All buttons use `.btn` base class
- Color variants: `.btn-primary`, `.btn-success`, `.btn-danger`, `.btn-warning`, `.btn-secondary`
- Always add `border-0` to remove browser defaults
- No hover color change, no transform, no shadow

```html
<button class="btn btn-success border-0">
    <i class="fas fa-plus mr-1"></i> New Folder
</button>
```

---

### 7.2 Content Cards

Wrap all sections in `.content-card`. Always use `.card-header` + `.card-title` for headings.

```html
<div class="content-card">
    <div class="card-header">
        <h3 class="card-title">Section Title</h3>
        <span class="badge badge-info">Label</span>
    </div>
    <!-- content -->
</div>
```

---

### 7.3 Data Tables

No row striping. No hover background change. Clean bordered rows only.

```html
<table class="data-table">
    <thead>
        <tr>
            <th>Column Name</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>Row data</td>
        </tr>
    </tbody>
</table>
```

---

### 7.4 Horizontal Stats Bar

Used on all three role dashboards. Single row compact stats.

```html
<div class="stats-grid-horizontal">
    <div class="stat-item-horizontal">
        <div class="stat-icon-horizontal">
            <i class="fas fa-users"></i>
        </div>
        <div class="stat-content-horizontal">
            <div class="stat-number-label">
                <strong>{{ $value }}</strong> Label Text
            </div>
            <div class="stat-description">Optional sub-text</div>
        </div>
    </div>
</div>
```

- Icon: Green 40×40px box, white icon
- Number is `<strong>`, label inline
- All dashboards (Faculty, Dean, Coordinator) use the same pattern

---

### 7.5 Folder Cards (My Folders Section)

The folder section uses the shared partial: `resources/views/partials/folder-section.blade.php`

```html
<div class="folder-card-new {{ request('folder') == $folder->folder_id ? 'folder-card-active' : '' }}">
    <a href="{{ link }}" class="folder-card-link-new">
        <div class="folder-icon-new" style="background-color: {{ $folder->color }}; color: white;">
            <i class="fas fa-folder"></i>
        </div>
        <div class="folder-info-new">
            <div class="folder-name-new">{{ $folder->folder_name }}</div>
            <div class="folder-count-new">{{ $folder->documents_count }} Files</div>
        </div>
    </a>
    <div class="folder-actions-new">
        <!-- Edit / Delete buttons -->
    </div>
</div>
```

**Active Folder Indicator**:
- Class: `.folder-card-active`
- Style: `border-left: 3px solid #028a0f` + light green background tint
- Applied via Blade conditional when `?folder=` URL param matches folder ID
- Uncategorized folder: checked against `?folder=uncategorized`

**Rules**:
- Uncategorized folder is always first in the list
- Folder icon background color comes from `$folder->color` (user-defined)
- Uncategorized folder icon is gray `#6c757d`
- All roles use this exact shared partial

---

### 7.6 Toggle Sections (Show/Hide)

Used for Recent Tasks and Recent Activities cards on dashboards.

```html
<div class="card-header">
    <div class="flex justify-between items-center w-full">
        <h3 class="card-title">Recent Tasks</h3>
        <button type="button" onclick="toggleSection()"
            class="bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 px-4 py-2 text-sm font-medium cursor-pointer border-0">
            <i id="sectionIcon" class="fas fa-chevron-up"></i>
            <span id="sectionText">Hide</span>
        </button>
    </div>
</div>
<div id="sectionContent">
    <!-- Content -->
</div>
```

Toggle behavior: Click toggles `display: none / block` and swaps chevron icon + text.

**Current Status**:
- ✅ Faculty: Recent Tasks, Recent Activities
- ✅ Dean: Recent Activities
- ✅ Coordinator: Recent Tasks, Recent Activities

---

### 7.7 Document Analytics Card (Dean & Coordinator Only)

Displays on Dean and Coordinator dashboards. Text/number stats only — no graphs.

```html
<div class="content-card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-file-alt mr-2"></i>Document Analytics</h3>
        <span class="badge badge-success">{{ date('F Y') }}</span>
    </div>
    <div class="doc-analytics-grid">
        <div class="doc-analytics-row">
            <div class="doc-analytics-item">
                <div class="doc-analytics-label">Metric Label</div>
                <div class="doc-analytics-value">Value <span class="doc-analytics-sub">(count)</span></div>
            </div>
        </div>
    </div>
</div>
```

**Dean Analytics** (system-wide):
- Total Documents
- Uploaded This Month
- Total Folders
- Top Document Type
- Most Used Folder
- Most Active Faculty

**Coordinator Analytics** (dept-scoped):
- My Documents
- Dept. Documents
- Uploaded This Month
- Top Document Type
- Most Used Folder
- Most Active Faculty

CSS classes: `.doc-analytics-grid`, `.doc-analytics-row`, `.doc-analytics-item`, `.doc-analytics-label`, `.doc-analytics-value`, `.doc-analytics-sub`

Data is cached for 5 minutes per user via Laravel `Cache::remember()`.

---

### 7.8 Empty States

Used when no data is available in a section.

```html
<div class="empty-state p-12 text-center">
    <div class="empty-state-icon mb-3 text-6xl text-gray-300 dark:text-gray-600">
        <i class="fas fa-folder-open"></i>
    </div>
    <div class="empty-state-text mb-6 text-gray-600 dark:text-gray-400">
        No items yet. Create one to get started.
    </div>
    <button onclick="openModal()" class="btn btn-success border-0">
        <i class="fas fa-plus mr-1"></i> Create First Item
    </button>
</div>
```

---

### 7.9 Badges

```html
<span class="badge badge-success">Active</span>
<span class="badge badge-warning">In Progress</span>
<span class="badge badge-danger">Rejected</span>
<span class="badge badge-info">View All</span>
```

For document categories:
```html
<span class="doc-category-badge">Research Papers</span>
```

---

### 7.10 Login Page — Branding Section

The login page uses a split layout: left (branding, 70%) + right (green login panel, 30%).

**Branding block** (`role-selection-branding`):
- Logo: 70×70px, center-aligned with text
- University name: `1.3rem`, `font-weight: 700`, color `#2c3e50`
- Location: `0.95rem`, color `#6c757d`
- Items are `align-items: center` in flex row

**Footer motto** (`role-selection-footer-text`):
- Text: "Cantas Christi Urget Nos"
- Size: `1.05rem`, `font-weight: 500`
- Color: `#2c3e50` (light mode), `#e5e7eb` (dark mode)

**Description** (`role-selection-description`):
- Text: "Manage documents, reports and credentials of SITE employees"
- Size: `1.15rem`

---

## 8. CSS Class Reference

> All classes live exclusively in `resources/css/app.css`.

### Layout & Cards

| Class | Purpose |
|-------|---------|
| `.content-card` | White/dark card with border, margin-bottom |
| `.card-header` | Flex row with border-bottom for card titles |
| `.card-title` | Section heading text style |

### Buttons

| Class | Purpose |
|-------|---------|
| `.btn` | Base button (padding, font, display) |
| `.btn-primary` | Green primary action |
| `.btn-success` | Green success action |
| `.btn-danger` | Red destructive action |
| `.btn-warning` | Orange warning action |
| `.btn-secondary` | Gray secondary action |

### Tables

| Class | Purpose |
|-------|---------|
| `.data-table` | Full-width table with clean borders |

### Badges

| Class | Purpose |
|-------|---------|
| `.badge-success` | Green background badge |
| `.badge-warning` | Orange background badge |
| `.badge-danger` | Red background badge |
| `.badge-info` | Blue background badge |
| `.doc-category-badge` | Solid green document type badge |

### Stats

| Class | Purpose |
|-------|---------|
| `.stats-grid-horizontal` | Horizontal flex container for stats |
| `.stat-item-horizontal` | Single stat item with border |
| `.stat-icon-horizontal` | 40×40 green icon box |
| `.stat-content-horizontal` | Text wrapper |
| `.stat-number-label` | Inline number + label |
| `.stat-description` | Small gray sub-description |

### Folders

| Class | Purpose |
|-------|---------|
| `.folder-header-new` | Folder section header with title and button |
| `.folder-container-new` | Flex wrapping container for cards |
| `.folder-card-new` | Individual folder card (horizontal) |
| `.folder-card-active` | Active/selected folder — green left border + tint |
| `.folder-card-link-new` | Link inside folder card |
| `.folder-icon-new` | 40×40 icon box |
| `.folder-info-new` | Name and count column |
| `.folder-name-new` | Bold folder name |
| `.folder-count-new` | "X Files" text |
| `.folder-actions-new` | Edit/Delete container |
| `.folder-action-btn` | Individual edit/delete button |

### Documents

| Class | Purpose |
|-------|---------|
| `.documents-filter` | Filter row container |
| `.documents-icon` | File type icon container |
| `.doc-category-badge` | Green solid category badge |
| `.doc-action-btns` | Action button group |

### Document Analytics

| Class | Purpose |
|-------|---------|
| `.doc-analytics-grid` | Outer container for analytics rows |
| `.doc-analytics-row` | Horizontal row of metric items |
| `.doc-analytics-item` | Individual metric cell |
| `.doc-analytics-label` | Small uppercase metric name |
| `.doc-analytics-value` | Bold metric value |
| `.doc-analytics-sub` | Faint count in parentheses |

### Forms & Modals

| Class | Purpose |
|-------|---------|
| `.form-group` | Field wrapper with margin |
| `.form-label` | Bold label text |
| `.form-control` | Input/select/textarea styling |
| `.modal-overlay` | Full-screen modal backdrop |
| `.modal-card` | Modal content container |
| `.modal-header` | Modal title row |
| `.modal-body` | Scrollable body content |
| `.modal-footer` | Action buttons row |

### Sidebar

| Class | Purpose |
|-------|---------|
| `.sidebar` | Sidebar container (scrollable, full height) |
| `.menu-item` | Navigation link item |
| `.menu-item.active` | Active page highlight (solid green) |

### Empty States

| Class | Purpose |
|-------|---------|
| `.empty-state` | Centered empty state wrapper |
| `.empty-state-icon` | Large icon |
| `.empty-state-text` | Description text |

### Login Page

| Class | Purpose |
|-------|---------|
| `.role-selection-branding` | Logo + university info row |
| `.role-selection-logo` | University logo image |
| `.role-selection-university-name` | University name heading |
| `.role-selection-university-location` | Location text |
| `.role-selection-description` | System tagline text |
| `.role-selection-footer-text` | "Cantas Christi Urget Nos" motto |

---

## 9. Key File Locations

### Core Style Files

| File | Purpose |
|------|---------|
| `resources/css/app.css` | **ALL global CSS — only place for styles** |

### Controllers

| File | Role |
|------|------|
| `app/Http/Controllers/AuthController.php` | Login/logout |
| `app/Http/Controllers/DeanController.php` | Dean routes |
| `app/Http/Controllers/CoordinatorController.php` | Coordinator routes |
| `app/Http/Controllers/FacultyController.php` | Faculty routes |
| `app/Http/Controllers/LeaveController.php` | Leave management |
| `app/Http/Controllers/CalendarController.php` | Calendar & events |
| `app/Http/Controllers/FolderController.php` | Folder CRUD |

### Services

| File | Purpose |
|------|---------|
| `app/Services/DashboardService.php` | All dashboard stats + analytics |
| `app/Services/DocumentService.php` | Document operations |
| `app/Services/TaskService.php` | Task management |
| `app/Services/EmployeeService.php` | Employee profile management |

### Blade Views

| File | Purpose |
|------|---------|
| `resources/views/auth/login.blade.php` | Login + role selection page |
| `resources/views/layouts/dashboard.blade.php` | Master layout |
| `resources/views/partials/folder-section.blade.php` | Shared folder UI (all roles) |
| `resources/views/partials/announcement-widget.blade.php` | Announcement feed |
| `resources/views/partials/{role}-sidebar.blade.php` | Role sidebars |
| `resources/views/faculty/dashboard.blade.php` | Faculty dashboard |
| `resources/views/dean/dashboard.blade.php` | Dean dashboard |
| `resources/views/coordinator/dashboard.blade.php` | Coordinator dashboard |
| `resources/views/{role}/documents.blade.php` | Documents page (per role) |

---

## 10. Features Summary by Role

### 👨‍💼 Dean

| Feature | Description |
|---------|-------------|
| Dashboard Analytics | System-wide stats, top performers, activity log |
| Document Analytics | System-wide: total docs, monthly uploads, top type, most used folder, most active faculty |
| Document Management | View all documents, filter by type, download |
| Folder Management | Personal folders with active folder indicator |
| Employee List | View all SITE employees |
| Performance Reports | View all evaluations and ratings |
| Leave Management | View and approve/reject all leave requests |
| Calendar & Events | View all events, delete any event |
| Announcements | View department announcements |
| Activity Log | See last 10 system activities |
| Dark Mode | Full dark mode support |

---

### 👩‍💻 Program Coordinator

| Feature | Description |
|---------|-------------|
| Dashboard Analytics | Faculty count, dept tasks, leave stats |
| Document Analytics | Dept-scoped: my docs, dept total, monthly uploads, top type, most used folder, most active faculty |
| Document Management | Upload, categorize, tag, favorite, view recent |
| Folder Management | Personal folders with active folder indicator |
| Task Management | Create and assign tasks to faculty |
| Faculty Management | **Exclusive**: Create faculty accounts, edit info, reset passwords |
| Leave Management | Approve/reject faculty leave requests |
| Calendar & Events | Create/manage events, invite attendees |
| Announcements | View department announcements |
| Activity Log | See last 10 activities |

---

### 👨‍🏫 Faculty Employee

| Feature | Description |
|---------|-------------|
| Dashboard | Personal task stats, recent tasks, activity log |
| Document Management | Upload, categorize, tag, favorite, filter by type |
| Folder Management | Personal folders with active folder indicator |
| Task Management | View assigned tasks, update status (Pending → In Progress → Completed) |
| Leave Requests | File leave, view balance (15 sick + 15 vacation days/year) |
| Calendar | View shared events, create personal events |
| Notifications | Receive alerts for tasks, leave, events |
| Profile | View personal info, performance history |

---

### 🔐 Role-Based Access Control

| Permission | Dean | Coordinator | Faculty |
|-----------|------|-------------|---------|
| View all documents | ✅ | Dept only | Own only |
| Create faculty accounts | ❌ | ✅ | ❌ |
| Approve leave requests | ✅ All | ✅ Faculty only | ❌ |
| Delete any calendar event | ✅ | ❌ | ❌ |
| View all employees | ✅ | ✅ | ❌ |
| View performance reports | ✅ All | ❌ | Own only |
| Document analytics | ✅ System | ✅ Dept | ❌ |

---

## 11. Database Structure

### Core Tables

| Table | Purpose |
|-------|---------|
| `roles` | User role definitions (Dean, Coordinator, Faculty) |
| `users` | Authentication (username, email, password, role_id) |
| `employees` | Employee profiles (full_name, department, employee_no) |
| `tasks` | Task assignments (assigned_by, assigned_to, status, due_date) |
| `documents` | File records (document_title, document_type, category, tags, folder_id) |
| `folders` | User-created folders (user_id, folder_name, color) |
| `performance_reports` | Evaluations (employee_id, rating, remarks, report_date) |
| `notifications` | User alerts (user_id, message, is_read) |
| `dashboard_logs` | Activity log (user_id, activity, activity_type, log_date) |
| `leave_requests` | Leave filings (user_id, leave_type, start_date, end_date, status) |
| `leave_balances` | Annual leave balance per user (sick: 15, vacation: 15) |
| `calendar_events` | Events (title, type, date_start, date_end, visibility) |
| `event_attendees` | Invitations and RSVP responses |
| `document_favorites` | Favorited documents per user |
| `document_views` | Document view tracking per user |
| `announcements` | System announcements (title, body, visible_to, active) |
| `cache` | Laravel cache table |
| `jobs` | Queue jobs |

### Document Categories (Predefined)

- Policies
- Forms
- Reports
- Memos
- Research Papers
- Other

### Document Types

- `pdf`
- `image`

---

## 12. Phase Implementation Log

| Phase | Description | Status |
|-------|-------------|--------|
| Phase 1 | Initial system — RBAC, tasks, documents, notifications | ✅ Complete |
| Phase 2 | Faculty creation by coordinator, performance reports | ✅ Complete |
| Phase 3 | Activity logging, dashboard analytics | ✅ Complete |
| Phase 4 | Document categories, tags, favorites, recent views | ✅ Complete |
| Phase 5 | Leave management system | ✅ Complete |
| Phase 6 | Shared calendar & events system | ✅ Complete |
| Phase 7 | Global document table standardization (all roles) | ✅ Complete |
| Phase 8 | Minimalist horizontal stats (all dashboards) | ✅ Complete |
| Phase 9 | Compact horizontal folder cards (all roles) | ✅ Complete |
| Phase 10 | Toggle show/hide for Recent Tasks & Activities | ✅ Complete |
| Phase 11 | Document analytics on Dean & Coordinator dashboards · Active folder indicator · Login page polish | ✅ Complete |

### Phase 11 Changes (2026-04-07)

**Document Analytics Card** (Dean & Coordinator dashboards):
- A new `Document Analytics` card between the stats bar and announcements
- Dean sees system-wide data; Coordinator sees department-scoped data
- 6 metrics in a 3×2 flat grid (no graphs)
- Data cached in `DashboardService` for 5 minutes

**Active Folder Indicator**:
- Selected folder gets green left border (`border-left: 3px solid #028a0f`)
- Light green background tint on active card
- Applied via `.folder-card-active` CSS class
- Blade conditional checks `request('folder')` vs folder ID / `'uncategorized'`

**Login Page Polish**:
- Logo size: 60px → 70px
- University name: `1.125rem` → `1.3rem`, weight 600 → 700
- Location text: `0.875rem` → `0.95rem`
- Branding alignment: `flex-start` → `center`
- Description text: `1rem` → `1.15rem`
- Motto "Cantas Christi Urget Nos": size `0.875rem` → `1.05rem`, color `#d1d5db` → `#2c3e50`

---

## 13. Pre-Commit Quality Checklist

Run through this before every commit:

### CSS
- [ ] All new styles added to `resources/css/app.css` only
- [ ] No inline `style=""` attributes in blade files
- [ ] No `rounded-corners*` Tailwind classes used
- [ ] No `transition-*` or `animate-*` classes used
- [ ] Dark mode `dark:` variants added for all new elements
- [ ] New CSS classes follow `.component-element` naming
- [ ] `npm run build` ran successfully with no errors

### Design Consistency
- [ ] Same design applied to all relevant roles (Faculty/Dean/Coordinator)
- [ ] Colors are from the approved palette only
- [ ] No shadows on any element
- [ ] No hover state changes

### Blade / Logic
- [ ] No business logic in blade files
- [ ] All data comes from controller via compact/merge
- [ ] Shared components use partials (not copy-pasted per role)

### Git
- [ ] Commit message is descriptive and uses `feat:`, `fix:`, `docs:`, `refactor:` prefixes
- [ ] Only modified files are staged (no accidental includes)

---

## 14. Future Update Notes

This section tracks proposed enhancements and known limitations for future development.

### Proposed Features

| Feature | Priority | Notes |
|---------|----------|-------|
| Email notifications | Medium | Leave approvals, event reminders via SMTP |
| Document search by category/tag | Medium | Filter bar on documents page |
| Export calendar to iCal/Google | Low | Standard .ics format |
| Recurring calendar events | Low | Weekly/monthly patterns |
| Leave history report (PDF) | Medium | dompdf already in composer |
| Document preview in-browser | Medium | PDF.js or iframe viewer |
| Bulk document actions | Low | Select multiple, bulk delete/move |
| Admin panel for Dean | High | Manage users, reset passwords, system settings |
| File versioning / revision history | Low | Track document updates |
| Department filter on Dean analytics | Medium | Filter document analytics by department |

### Known Design Notes

- The `IMPLEMENTATION_COMPLETE.md` and `QUICK_START.md` files mention animations and hover effects — these were present in early phases but **have been fully removed**. The current design is animation-free.
- `border-radius` is globally set to `0 !important` and cannot be overridden without modifying the `@layer base` block in `app.css`.
- Chart.js is only used on the Dean dashboard (System Usage bar chart). It is lazy-loaded to avoid blocking page render.
- FullCalendar is lazy-loaded on the Calendar page only.

### Maintenance Notes

- After any CSS change: always run `npm run build`
- Dashboard analytics use `Cache::remember()` with 5-minute TTL — clear cache with `php artisan cache:clear` if data appears stale
- Leave balances reset annually (year-based). No automatic reset cron yet — manual seeder required each year.
- Git user configured: `rodwinvicquerra` / `rodwinvicquerra@spup.edu.ph`
- Remote repository: `https://github.com/rodwinvicquerra/EMPLOYEEDASHBOARDV7.git`

---

> **This document is the single source of truth.**
> Update it whenever a new phase is completed or a design decision is made.
> Do not split this into multiple files — keep everything here for easy scanning.

---

*Employee Dashboard Knowledge Base · St. Paul University Philippines · SITE Department*
*Last Updated: 2026-04-07 · Phase 11*
