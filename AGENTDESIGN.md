# Agent Design System - Development Guidelines

**Last Updated:** 2026-03-20
**Current Version:** Phase 10 - Compact Folder & Toggle Implementation

---

## 🎯 CORE DESIGN PRINCIPLES

### Zero-Exception Rules ✅
1. **Global CSS Only** - ALL styling must go in `resources/css/app.css`, NEVER inline styles
2. **NO Rounded Corners** - `border-radius: 0` globally enforced at layer base
3. **NO Hover Effects** - Zero transitions, animations, or visual feedback on interaction
4. **Flat Design** - Use borders for definition, NO shadows or depth
5. **All Roles Identical** - Faculty, Dean, Coordinator must have EXACT same design
6. **No Borders on Buttons** - Use `border-0` on all button elements
7. **Minimize Classes** - Use Tailwind @apply classes efficiently, avoid multiplying CSS

---

## 🎨 COLOR PALETTE

### Primary Colors
| Name | Value | Usage |
|------|-------|-------|
| Primary Green | `#028a0f` | Buttons, badges, accents, primary actions |
| Dark Green | `#026a0c` | Hover states (reference only, no hover) |
| Light Green | `rgba(2, 138, 15, 0.1)` | Backgrounds, subtle accents |

### Neutral Colors (Light Mode)
| Element | Light | Dark |
|---------|-------|------|
| Text | `#2c3e50` | `#e5e7eb` |
| Borders | `#e0e0e0` | `#374151` |
| Background | `#f8f9fa` | `#1f1f1f` |
| Surface | `#ffffff` | `#2a2a2a` |

---

## 📦 COMPONENT PATTERNS

### 1. Buttons
```html
<!-- Primary Button -->
<button class="btn btn-primary">Text</button>

<!-- Success Button -->
<button class="btn btn-success">Text</button>

<!-- Important: Always use border-0 -->
<button class="btn btn-primary border-0">Text</button>
```

**CSS Classes:**
- `.btn` - Base button styles
- `.btn-primary` - Primary action
- `.btn-success` - Green success button
- `.btn-danger` - Red danger button
- `border-0` - Remove default button borders

---

### 2. Cards (Content Containers)
```html
<div class="content-card">
    <div class="card-header">
        <h3 class="card-title">Title</h3>
    </div>
    <!-- Card Content -->
</div>
```

**CSS Classes:**
- `.content-card` - White/dark background with border
- `.card-header` - Title section with border-bottom
- `.card-title` - Title styling

---

### 3. Tables
```html
<div class="content-card">
    <table class="data-table">
        <thead>
            <tr>
                <th>Column</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Data</td>
            </tr>
        </tbody>
    </table>
</div>
```

**CSS Classes:**
- `.data-table` - Consistent table styling
- No striping, clean rows with borders

---

### 4. Badges
```html
<span class="badge badge-success">Active</span>
<span class="badge badge-warning">Pending</span>
<span class="badge badge-danger">Inactive</span>
<span class="badge badge-info">Info</span>
<span class="badge badge-neutral">Neutral</span>
```

**CSS Classes:**
- `.badge` - Base badge
- `.badge-success` - Green badge
- `.badge-warning` - Yellow badge
- `.badge-danger` - Red badge
- `.badge-info` - Blue badge
- `.badge-neutral` - Gray badge

---

### 5. Horizontal Stats
```html
<div class="stats-grid-horizontal">
    <div class="stat-item-horizontal">
        <div class="stat-icon-horizontal">
            <i class="fas fa-icon"></i>
        </div>
        <div class="stat-content-horizontal">
            <div class="stat-number-label"><strong>{{ $value }}</strong> Label</div>
            <div class="stat-description">Optional description</div>
        </div>
    </div>
</div>
```

**Features:**
- Single-row horizontal layout
- 40x40px green icon box
- Inline number + label with optional description below
- Applies to all dashboards (Faculty, Dean, Coordinator)

---

### 6. Horizontal Folder Section
```html
{{-- In views/partials/folder-section.blade.php --}}
<div class="content-card mb-6">
    {{-- Header with Title and New Folder Button --}}
    <div class="folder-header-new px-6 py-4 flex items-center justify-between border-b border-gray-200 dark:border-gray-700">
        <h3 class="folder-title-new"><i class="fas fa-folder-tree mr-2"></i> My Folders</h3>
        <button onclick="openCreateFolderModal()" class="btn btn-success">
            <i class="fas fa-plus mr-1"></i> New Folder
        </button>
    </div>

    @if($folders->count() > 0)
    <div class="folder-container-new px-6 py-4 flex gap-3 flex-wrap">
        <!-- Folder cards displayed horizontally -->
    </div>
    @else
    <div class="empty-state p-12 text-center">
        <!-- Empty state with create button -->
    </div>
    @endif
</div>
```

**CSS Classes:**
- `.folder-header-new` - Header with flex layout
- `.folder-title-new` - Folder section title
- `.folder-container-new` - Horizontal flex container
- `.folder-card-new` - Individual folder card
- `.folder-card-link-new` - Folder link styling
- `.folder-icon-new` - Folder icon container
- `.folder-info-new` - Folder name/count info
- `.folder-name-new` - Folder name text
- `.folder-count-new` - File count display
- `.folder-actions-new` - Action buttons container
- `.folder-action-btn` - Edit/Delete button

**Folder Cards Layout:**
- Icon (left) | Name + Count (center) | Actions (right)
- Uncategorized folder always first
- All roles use identical design

---

### 7. Toggle Sections
```html
<!-- In dashboard cards -->
<div class="card-header">
    <div class="flex justify-between items-center w-full">
        <h3 class="card-title">Recent Tasks</h3>
        <div class="flex gap-3 items-center">
            <span class="badge badge-info">Last 10</span>
            <button type="button" onclick="toggleRecentTasks()"
                class="bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 px-4 py-2 text-sm font-medium cursor-pointer border-0">
                <i id="recentTasksIcon" class="fas fa-chevron-up"></i>
                <span id="recentTasksText">Hide</span>
            </button>
        </div>
    </div>
</div>
<div id="recentTasksContent" class="overflow-x-auto">
    <!-- Table content -->
</div>
```

**JavaScript Toggle Function:**
```javascript
function toggleRecentTasks() {
    const content = document.getElementById('recentTasksContent');
    const icon = document.getElementById('recentTasksIcon');
    const text = document.getElementById('recentTasksText');

    if (content.style.display === 'none') {
        content.style.display = 'block';
        icon.classList.remove('fa-chevron-down');
        icon.classList.add('fa-chevron-up');
        text.textContent = 'Hide';
    } else {
        content.style.display = 'none';
        icon.classList.remove('fa-chevron-up');
        icon.classList.add('fa-chevron-down');
        text.textContent = 'Show';
    }
}
```

**Current Implementation Status:**
- ✅ Faculty Dashboard: Recent Tasks, Recent Activities
- ✅ Dean Dashboard: Recent Activities
- ✅ Coordinator Dashboard: Recent Tasks, Recent Activities

---

## 📂 KEY FILES

| File | Purpose |
|------|---------|
| `resources/css/app.css` | Global CSS (ONLY place for styles) |
| `resources/views/partials/folder-section.blade.php` | Reusable folder component |
| `resources/views/faculty/dashboard.blade.php` | Faculty dashboard |
| `resources/views/dean/dashboard.blade.php` | Dean dashboard |
| `resources/views/coordinator/dashboard.blade.php` | Coordinator dashboard |
| `resources/views/faculty/documents.blade.php` | Faculty documents page |
| `resources/views/dean/documents.blade.php` | Dean documents page |
| `resources/views/coordinator/documents.blade.php` | Coordinator documents page |

---

## ✅ IMPLEMENTATION CHECKLIST

### When Adding New Features:
- [ ] Define CSS classes in `resources/css/app.css` only
- [ ] Use Tailwind @apply for styling
- [ ] Ensure `border-radius: 0` (globally enforced)
- [ ] NO inline styles
- [ ] NO hover effects or animations
- [ ] Use flat design principles
- [ ] Test on all three roles (Faculty, Dean, Coordinator)
- [ ] Ensure consistent appearance across roles
- [ ] Use existing color palette
- [ ] Follow component patterns above

### Before Deployment:
1. Update CSS only in `resources/css/app.css`
2. Run `npm run build` to compile Tailwind
3. Test in both light and dark modes
4. Verify all three roles render identically
5. Check responsive behavior
6. Commit with descriptive message
7. Push to GitHub

---

## 🚀 TAILWIND CONFIGURATION

### Enforced Global Rules (app.css layer base)
```css
* {
    border-radius: 0 !important;
    transition: none !important;
    animation: none !important;
}

*:hover {
    transform: none !important;
    box-shadow: none !important;
    filter: none !important;
    opacity: 1 !important;
}

*:focus {
    box-shadow: none !important;
    transform: none !important;
}
```

---

## 📝 TYPOGRAPHY

### Font Stack
```css
--font-sans: 'Segoe UI', Tahoma, Geneva, Verdana, ui-sans-serif, system-ui, sans-serif;
```

### Text Sizes (Tailwind)
- `text-xs` - Small labels, counts (12px)
- `text-sm` - Body text (14px)
- `text-base` - Regular text (16px)
- `text-lg` - Section headers (18px)

### Font Weights
- Regular: `font-normal`
- Medium: `font-medium`
- Bold: `font-bold` or `font-semibold`

---

## 📐 SPACING GUIDE

### Padding (Tailwind Classes)
- `p-1` - 0.25rem (4px)
- `p-2` - 0.5rem (8px)
- `p-3` - 0.75rem (12px)
- `p-4` - 1rem (16px)
- `p-6` - 1.5rem (24px)

### Gaps (Between Elements)
- `gap-1` - 0.25rem (4px)
- `gap-2` - 0.5rem (8px)
- `gap-3` - 0.75rem (12px)
- `gap-4` - 1rem (16px)

### Margins
- `mb-3` - margin-bottom 0.75rem
- `mb-6` - margin-bottom 1.5rem

---

## 🌙 Dark Mode Support

All components must support both light and dark modes using Tailwind's dark mode classes:

```html
<!-- Example -->
<div class="bg-white dark:bg-[#2a2a2a] border border-gray-200 dark:border-gray-700">
    <h3 class="text-gray-800 dark:text-gray-200">Title</h3>
</div>
```

**Standard Dark Mode Colors:**
- Background: `dark:bg-[#2a2a2a]` or `dark:bg-[#1f1f1f]`
- Border: `dark:border-gray-700`
- Text: `dark:text-gray-200` or `dark:text-gray-400`

---

## ❌ WHAT NOT TO DO

1. ❌ Do NOT add inline `style` attributes
2. ❌ Do NOT use `border-radius` anywhere
3. ❌ Do NOT add hover effects or transitions
4. ❌ Do NOT use box-shadows
5. ❌ Do NOT add animations
6. ❌ Do NOT create component-specific CSS files
7. ❌ Do NOT vary design between roles
8. ❌ Do NOT add rounded buttons (`rounded-md`, `rounded-lg`)
9. ❌ Do NOT skip dark mode support
10. ❌ Do NOT use `px-6` on small containers (use `px-3` or `px-4`)

---

## 📚 TEMPLATE SNIPPETS

### Empty State Template
```html
<div class="empty-state p-12 text-center">
    <div class="empty-state-icon mb-3 text-6xl text-gray-300 dark:text-gray-600">
        <i class="fas fa-icon"></i>
    </div>
    <div class="empty-state-text mb-6 text-gray-600 dark:text-gray-400">
        Description text here
    </div>
    <button onclick="someAction()" class="btn btn-success">
        <i class="fas fa-action mr-1"></i> Action Label
    </button>
</div>
```

### Form Group Template
```html
<div class="form-group mb-4">
    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
        Label
    </label>
    <input type="text" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-[#1f1f1f] text-gray-900 dark:text-gray-100">
</div>
```

---

## 🔄 CONSISTENCY CHECKLIST

Before committing any changes:

- [ ] All styling in `resources/css/app.css`
- [ ] No rounded corners anywhere
- [ ] No hover effects or animations
- [ ] Buttons have `border-0`
- [ ] Dark mode classes applied
- [ ] All roles get identical styling
- [ ] CSS classes use naming convention: `.component-section-element`
- [ ] No inline styles
- [ ] Colors from palette only
- [ ] Spacing from standard scales
- [ ] Run `npm run build` successful
- [ ] Tested in light and dark modes

---

## 📞 Questions?

Refer to:
1. `MEMORY.md` - Quick reference and latest updates
2. Color system section above - for palette questions
3. Component patterns section - for implementation examples
4. Key files section - for file locations

---

**Remember:** Consistency is key. Every role, every feature, every page should follow these guidelines WITHOUT exception.
