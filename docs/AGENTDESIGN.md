# SITE DocuDrive — Agent Design System

**Follow this file for UI work.** Behavior, authorization, and search live in `docs/SYSTEM_GUIDE.md`. Local install is `docs/SETUP.md`. Agent workflow is `docs/AGENTS.md`.

This file replaces the archived lime-era design notes. Do not follow `docs/archive/legacy/AGENTDESIGN.md`.

Source of visual truth is compiled CSS and Blade, not screenshots: `resources/css/app.css` plus existing feature CSS. If this document and the CSS disagree, record the discrepancy; do not silently “fix” the whole stylesheet unless that is the task.

## How this folder works


| File                   | Follow for                                     |
| ---------------------- | ---------------------------------------------- |
| `docs/AGENTDESIGN.md`  | Colors, chrome, components, what not to invent |
| `docs/SYSTEM_GUIDE.md` | Roles, documents, search, requests, operations |
| `docs/AGENTS.md`       | How to read the guide and change the app       |
| `docs/SETUP.md`        | Clone, migrate, build, test commands           |


Root `AGENTS.md` and `README.md` only point here so Cursor and GitHub still find an entry file.

## Design principles (current)

1. **Institutional dark green, not lime.** New UI uses `--site-`* tokens (`#0d5c3b` family). Do not add new `#028a0f` / `#02b815` lime, gradients, or glass chrome.
2. **Square controls.** Global `border-radius: 0`. Do not add `rounded-`* to “modernize” a screen.
3. **Restrained motion.** Global rules suppress transitions, hover transforms, and decorative shadows. Preserve a **visible keyboard focus** outline where the UI already does (dialogs, destructive confirms). Compact error dialog may use a slight shadow; do not copy that onto every card.
4. **Shared chrome, role-specific data.** Dean, Coordinator, Faculty, and Secretary share the same shell language. Do not invent a different visual system per role. Content, actions, and authorization still differ by role — see the system guide.
5. **Tokens first, then shared classes.** Prefer `var(--site-primary)` and existing `.btn`, `.content-card`, `.data-table`, `.ui-dialog`, `.profile-panel`. Avoid one-off hex and inline `style` attributes.
6. **Feature CSS is allowed where it already exists.** `resources/css/teacher-loads.css` is current. Do not treat “everything must be only in `app.css`” as a rule. Do not create a new CSS file for a small tweak if a shared class already covers it.
7. **Keep approved flows.** Guided upload destination selection, login branding layout, and Teacher’s Load workspace width are product constraints, not styling toys.

Known mix in `app.css`: `--site-primary` is `#0d5c3b`, but many older rules (including `.btn-primary`) still hard-code `#028a0f`. **Do not proliferate lime.** Prefer tokens on new or touched rules. Do not rewrite every leftover lime selector unless the user asked for a color migration.

## Color tokens

Defined in `resources/css/app.css`:


| Token                     | Value     | Use                         |
| ------------------------- | --------- | --------------------------- |
| `--site-primary`          | `#0d5c3b` | Primary actions and accents |
| `--site-primary-strong`   | `#08472e` | Strong / pressed            |
| `--site-primary-hover`    | `#0a5135` | Interactive                 |
| `--site-sidebar`          | `#0d4f32` | Sidebar                     |
| `--site-sidebar-strong`   | `#083d27` | Sidebar emphasis            |
| `--site-heading`          | `#0b4931` | Headings                    |
| `--site-text`             | `#16241d` | Body                        |
| `--site-muted`            | `#68766f` | Secondary text              |
| `--site-surface`          | `#ffffff` | Cards / panels              |
| `--site-surface-subtle`   | `#f7faf8` | Page workspace              |
| `--site-surface-selected` | `#edf7f1` | Selection                   |
| `--site-border`           | `#d9e2dd` | Dividers                    |
| `--site-border-strong`    | `#9fb8aa` | Stronger edges              |


Status colors (success / warning / danger / info) stay semantic. Do not recode errors as brand green.

Font stack: Segoe UI, Tahoma, Geneva, Verdana, system sans-serif.

## Shell and layout

- Layout: `resources/views/layouts/dashboard.blade.php`
- Role sidebars: `partials/dean-sidebar.blade.php`, `coordinator-sidebar.blade.php`, `faculty-sidebar.blade.php`, `secretary-sidebar.blade.php`
- Account/photo: `partials/sidebar-account.blade.php`

Dark green grouped sidebar, account controls at the bottom, consistent page title and utilities. Workspace is pale green-gray, content on white. Use compact statistic strips and readable tables — not a card around every number.

Documents: breadcrumbs, folder choice, consolidated toolbar, file list. Search scopes must stay obvious.

Login: split identity / sign-in. School branding stays in the identity panel; “Employee portal” in the header. Do not restyle login unless the user asked.

Profile: `profile-panel` tabs (details / password), not the old uneven password grid. File: `resources/views/profile/edit.blade.php`.

## Shared components

Use these instead of inventing parallel widgets.

**Buttons**

```html
<button type="button" class="btn btn-primary">Save</button>
<button type="button" class="btn btn-secondary">Cancel</button>
<button type="button" class="btn btn-danger">Delete</button>
```

`.btn` has no border. Variants include `btn-primary`, `btn-secondary`, `btn-success`, `btn-warning`, `btn-danger`. Document row actions use `btn-action-view` / `btn-action-download` where those classes already wrap the control.

**Cards and tables**

```html
<div class="content-card">
    <div class="card-header">
        <h3 class="card-title">Title</h3>
    </div>
    <table class="data-table">…</table>
</div>
```

**Badges:** `badge` plus `badge-success` | `badge-warning` | `badge-danger` | `badge-info`.

**Forms:** labels aligned with controls, validation next to the field, preserve `old()` values on recoverable failure. Distinguish read-only account facts from editable inputs.

**Modals:** `dialog.ui-dialog` with `ui-dialog__head`, `ui-dialog__body`, `ui-dialog__foot`, visible close. Bounded scroll, restrained backdrop. Teacher’s Load keeps its wider workspace.

**Compact errors:** `partials/ui-error-dialog.blade.php` + `resources/js/site-request.js`. Max width ~420px, ~1.05rem title, ~.875rem body, two actions. Do not use that width for upload, Teacher’s Load, or other complex forms.

**Empty states:** prefer `partials/ui/empty-state.blade.php` when the screen already uses it.

## Documents, upload, Teacher’s Load

- Explorer / tree: `partials/folder-tree.blade.php`, `folder-explorer.blade.php`, `documents-filter-panel.blade.php`
- Guided upload: `partials/document-upload-dialog.blade.php` — keep destination-then-files.
- Do not restyle `partials/folder-tree-upload-form.blade.php` unless the user named that file.
- Teacher’s Load: `resources/css/teacher-loads.css`, `resources/js/teacher-loads.js`, `resources/views/teacher-loads/`



## Typography and spacing

Use the existing Tailwind scale already in the screens: `text-xs` / `text-sm` / `text-base` / `text-lg`; `font-normal` / `font-medium` / `font-semibold`. Typical padding `p-3`–`p-6`, gaps `gap-2`–`gap-4`. Match the neighboring page instead of introducing a new scale.

## Dark mode

Dark mode is a **neutral charcoal workspace** with the same green sidebar as light. It is not a green-tinted copy of the light palette.

`html[data-theme="dark"]` / `.dark` remaps `--site-*` surfaces, text, and borders. Light `:root` / `@theme` values stay unchanged.

| Token | Dark value | Role |
|---|---|---|
| `--site-sidebar` / `--site-sidebar-strong` | `#083d27` / `#062f1e` | Brand chrome only |
| `--site-surface-subtle` | `#121416` | Page canvas |
| `--site-surface` | `#1c1f24` | Cards, top bar, dialogs |
| `--site-surface-selected` | `#24352c` | Selection |
| `--site-text` / `--site-heading` | `#e8eaed` / `#f3f5f6` | Neutral type |
| `--site-muted` / `--site-border` | `#9aa3ab` / `#2c3036` | Secondary / edges |
| `--site-primary` | `#3d9b6e` | Buttons, active, charts, focus |

Do not paint the workspace or card fills with brand green. Do not add gradients or glass. If a panel is still white or mint in dark, add a dark token override rather than a new palette.

## Key files


| File                                                        | Role                             |
| ----------------------------------------------------------- | -------------------------------- |
| `resources/css/app.css`                                     | Tokens, shell, shared components |
| `resources/css/teacher-loads.css`                           | Teacher’s Load only              |
| `resources/views/layouts/dashboard.blade.php`               | Authenticated chrome             |
| `resources/views/auth/login.blade.php`                      | Login branding split             |
| `resources/views/profile/edit.blade.php`                    | Profile panel                    |
| `resources/views/partials/document-upload-dialog.blade.php` | Guided upload                    |
| `resources/views/partials/ui-error-dialog.blade.php`        | Compact errors                   |
| `resources/js/site-request.js`                              | Shared fetch / error mapping     |


After CSS or Blade visual changes: `npm run build`, then hard-refresh (Ctrl+F5).

## Do not

- Follow the archived lime palette or “global CSS only / no feature CSS / no shadows ever” rules.
- Add rounded corners, hover lifts, or animation for decoration.
- Copy CDN Font Awesome / Chart.js / FullCalendar; they are already local packages.
- Infer permission from sidebar styling.
- Change Coordinator extra-program or private-folder behavior as a “UI cleanup”.
- Treat leftover `#028a0f` in `app.css` as the target color for new work.
- Paint dark-mode page/card fills with brand green. Dark surfaces stay charcoal; green is accent and sidebar only.



## Checklist (UI change)

- [ ] Tokens or existing shared classes, not a new hex
- [ ] Square corners, no decorative motion
- [ ] Same chrome language as sibling screens
- [ ] Role differences are data/actions, not a new theme
- [ ] Upload / login / Teacher’s Load constraints untouched unless requested
- [ ] `npm run build` after CSS/Blade asset changes
- [ ] Update this file and the system-guide UI section if you change tokens or shared patterns