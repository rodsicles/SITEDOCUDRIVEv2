# SITE DocuDrive — Latest Color & UI Update

**Date:** 13 September 2026  
**Commit:** `6cbb7db` (`main` on SITEDOCUDRIVEv3 and SITEDOCUDRIVEv2)  
**Goal:** One institutional look for authenticated screens. Dark SITE green, square corners, no lime, no gradients, no glass.

`AGENTDESIGN.md` still lists the old primary `#028a0f`. This file is the current color/UI source of truth.

---

## Color tokens

Defined in `resources/css/app.css` (`@theme` / `--site-*`).

| Token | Value | Use |
|---|---|---|
| `--site-primary` | `#0d5c3b` | Buttons, badges, active chips, icons, focus |
| `--site-primary-strong` | `#08472e` | Stronger fill / pressed |
| `--site-primary-hover` | `#0a5135` | Hover (where hover still exists) |
| `--site-sidebar` | `#0d4f32` | Sidebar background |
| `--site-sidebar-strong` | `#083d27` | Sidebar emphasis |
| `--site-heading` | `#0b4931` | Titles and section labels |
| `--site-text` | `#16241d` | Body text |
| `--site-muted` | `#68766f` | Hints, counts, secondary copy |
| `--site-surface` | `#ffffff` | Cards, tables, modals |
| `--site-surface-subtle` | `#f7faf8` | Page background, inset bars |
| `--site-surface-selected` | `#edf7f1` | Selected row / chip |
| `--site-border` | `#d9e2dd` | Default borders |
| `--site-border-strong` | `#9fb8aa` | Modal / emphasis borders |

**Do not use** lime `#028a0f`, `#22c55e`, or `linear-gradient` greens on product UI.

Semantic colors stay for status only (success / warning / danger / info). They are not the brand green.

---

## Visual rules

1. **Primary is `#0d5c3b`.** Solid fills only.
2. **No green gradients** on buttons, badges, headers, progress bars, or chips.
3. **No glass** (no blur overlays as chrome).
4. **Square corners.** No new `border-radius` on product chrome.
5. **White cards on a pale green-gray page** (`--site-surface-subtle`).
6. **Same language for Faculty, Coordinator, Dean, Secretary.** Role differences are navigation and permissions, not a second theme.
7. Put new styling in `resources/css/app.css`. Prefer `--site-*` tokens over one-off hex.

---

## What this update changed

### Shell
- Authenticated workspace: white cards, dark-green sidebar, 3px primary top accent on document workspaces.
- Sidebars: grouped sections (Resources, Communication, Core Functions, Management).

### Documents
- One breadcrumb for the full path.
- One chip row for the current level (children, or siblings on a leaf such as TG / LB).
- One toolbar on a leaf: folder name, count, search, filters, sort, upload. No “Current location” duplicate.
- Compact empty leaf: “This folder is empty.”
- Custom Folders still use the card grid (rename / delete).
- Category chips and document badges use `#0d5c3b`.

### Faculty reports
- Communication → **Reports** (next to Announcements).
- `/faculty/reports` with Submit report modal (title, category, PDF/Word).
- Employee profile “Submitted reports” is a compact record, not a large empty banner.

### Profile
- `/profile/edit` is a personnel record, not a full-bleed form.
- Identity row (photo, name, role, camera).
- **Personal details** two-column grid; **Account security** with current password, then new + confirm.
- Department is a select (`Engineering` / `Information Technology`).
- On this route, the **Edit Profile** title lines up with **Personal details**.

### Dean / coordinator review
- Exam questionnaires and teaching guides: solid `#0d5c3b`, no green gradients.
- Employee profile **Submitted documents**: compact list (recent uploads, search, folder browse), dark-green chips instead of lime pills.

### Other screens in the same pass
- Announcements feed, analytics layouts, activity log filters, employee create/profile, 403, user guide (gradients stripped).
- Global search: denser list, USER/EMPLOYEE dedupe.

---

## Layout notes

| Surface | Width / structure |
|---|---|
| Most authenticated pages | Workspace max ~`100rem` |
| `/profile/edit` | Constrained **58rem** column; title and fields share the same left edge |
| Documents workspace | Full workspace card; chips + toolbar, not a stacked folder tree |
| Modals | Overlay `z-index: 2000`, solid dim, no blur |

---

## Database (related to this push)

| Migration | Status (local at ship) |
|---|---|
| `2026_09_10_000002_add_event_letters_category` | Ran |
| `2026_09_10_000001_ensure_it_dean_account` | Still pending on purpose (resets `dean` login) |

Laravel Cloud: UI needs no extra artisan commands after redeploy. Run the Event Letters migration on production only if that category is missing. Do not run a blanket `migrate --force` unless you also want the dean-account migration.

---

## Files to treat as the UI system

- Tokens and chrome: `resources/css/app.css`
- Shell: `resources/views/layouts/dashboard.blade.php`
- Documents: `resources/views/partials/folder-tree.blade.php`, `folder-explorer.blade.php`, `documents-filter-panel.blade.php`
- Profile: `resources/views/profile/edit.blade.php`
- Faculty reports: `resources/views/faculty/reports.blade.php`, `partials/faculty-sidebar.blade.php`

After CSS/Blade changes: `npm run build`, then hard-refresh (**Ctrl+F5**).
