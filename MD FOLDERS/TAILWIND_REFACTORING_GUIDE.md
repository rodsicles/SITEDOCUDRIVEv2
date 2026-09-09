# Tailwind CSS Refactoring Guide

## ✅ Completed Refactorings

The following files have been fully refactored to use TailwindCSS:

1. **resources/css/app.css** - Updated with custom Tailwind theme and utility classes
2. **resources/views/layouts/dashboard.blade.php** - Main layout with sidebar and nav
3. **resources/views/auth/login.blade.php** - Login page
4. **resources/views/faculty/dashboard.blade.php** - Faculty dashboard with stats and tables

## 🎨 Custom Tailwind Configuration

### Theme Colors (in app.css)
- Primary: `#028a0f` → Use `bg-[#028a0f]` or `text-[#028a0f]`
- Primary Dark: `#026a0c` → Use `bg-[#026a0c]`
- Primary Light: `rgba(2, 138, 15, 0.65)` → Use `bg-[rgba(2,138,15,0.65)]`

### Dark Mode
- Automatically supported via `dark:` prefix
- Toggle controlled by `data-theme="dark"` attribute
- Example: `bg-white dark:bg-[#2a2a2a]`

## 📦 Utility Classes Available

### Layout Classes
```html
<!-- Card Structure -->
<div class="content-card">
    <div class="card-header">
        <h3 class="card-title">Title</h3>
    </div>
    <!-- Content -->
</div>

<!-- Stats Grid -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-icon"></i></div>
        <div class="stat-value">123</div>
        <div class="stat-label">Label</div>
    </div>
</div>
```

### Button Classes
```html
<button class="btn btn-primary">Primary Button</button>
<button class="btn btn-success">Success Button</button>
<button class="btn btn-danger">Danger Button</button>
<button class="btn btn-warning">Warning Button</button>
```

### Table Classes
```html
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
```

### Badge Classes
```html
<span class="badge-success">Success</span>
<span class="badge-warning">Warning</span>
<span class="badge-danger">Danger</span>
<span class="badge-info">Info</span>
```

### Form Classes
```html
<div class="form-group">
    <label class="form-label">Label</label>
    <input type="text" class="form-control">
</div>
```

### Alert Classes
```html
<div class="alert alert-success">Success message</div>
<div class="alert alert-error">Error message</div>
```

## 🔄 Refactoring Pattern for Remaining Files

### Step 1: Remove `<style>` Tags
Delete all `<style>` blocks containing CSS custom properties and inline styles.

### Step 2: Replace Common Patterns

#### Before (Old CSS Classes):
```html
<style>
    .modern-stat-card {
        background: var(--white);
        border-radius: 12px;
        padding: 1.5rem;
        ...
    }
</style>

<div class="modern-stats-grid">
    <div class="modern-stat-card">
        <div class="modern-stat-icon">...</div>
        <div class="modern-stat-value">...</div>
        <div class="modern-stat-label">...</div>
    </div>
</div>
```

#### After (Tailwind Classes):
```html
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon">...</div>
        <div class="stat-value">...</div>
        <div class="stat-label">...</div>
    </div>
</div>
```

### Step 3: Convert Inline Styles

#### Inline Style Patterns:
```html
<!-- BEFORE -->
<div style="display: flex; gap: 10px;">
<span style="color: var(--text-light);">Text</span>
<button style="background: none; border: none;">Button</button>

<!-- AFTER -->
<div class="flex gap-2">
<span class="text-gray-600 dark:text-gray-400">Text</span>
<button class="bg-transparent border-none">Button</button>
```

### Step 4: Replace Color Variables

| Old Variable | Tailwind Class |
|-------------|----------------|
| `var(--text-dark)` | `text-gray-800 dark:text-gray-200` |
| `var(--text-light)` | `text-gray-600 dark:text-gray-400` |
| `var(--white)` | `bg-white dark:bg-[#2a2a2a]` |
| `var(--bg-light)` | `bg-gray-50 dark:bg-gray-800` |
| `var(--border-color)` | `border-gray-200 dark:border-gray-700` |
| `var(--primary-color)` | `bg-[#028a0f] dark:bg-[#02b815]` |

## 📝 Files Needing Refactoring

Apply the patterns above to these remaining files:

### Coordinator Views
- `resources/views/coordinator/dashboard.blade.php`
- `resources/views/coordinator/tasks.blade.php`
- `resources/views/coordinator/faculty.blade.php`
- `resources/views/coordinator/documents.blade.php`
- `resources/views/coordinator/create-task.blade.php`
- `resources/views/coordinator/create-faculty.blade.php`
- `resources/views/coordinator/edit-faculty.blade.php`

### Dean Views
- `resources/views/dean/dashboard.blade.php`
- `resources/views/dean/analytics.blade.php`
- `resources/views/dean/employees.blade.php`
- `resources/views/dean/documents.blade.php`
- `resources/views/dean/reports.blade.php`

### Faculty Views
- `resources/views/faculty/tasks.blade.php`
- `resources/views/faculty/notifications.blade.php`
- `resources/views/faculty/profile.blade.php`
- `resources/views/faculty/documents.blade.php`

### Other Views
- `resources/views/profile/edit.blade.php`
- `resources/views/employees/profile.blade.php`
- `resources/views/leave/index.blade.php`
- `resources/views/leave/create.blade.php`
- `resources/views/leave/calendar.blade.php`
- `resources/views/calendar/index.blade.php`
- `resources/views/calendar/create.blade.php`
- `resources/views/calendar/show.blade.php`
- `resources/views/welcome.blade.php`

## 🛠️ Refactoring Checklist

For each file:
- [ ] Remove all `<style>` tags
- [ ] Replace `modern-stat-card` → `stat-card`
- [ ] Replace `modern-content-card` → `content-card`
- [ ] Replace `modern-card-header` → `card-header`
- [ ] Replace `modern-card-title` → `card-title`
- [ ] Replace `modern-table` → `data-table`
- [ ] Replace `modern-badge-*` → `badge-*`
- [ ] Replace `modern-action-btn` → `btn btn-primary`
- [ ] Remove inline `style=""` attributes
- [ ] Replace with Tailwind utility classes
- [ ] Test dark mode compatibility

## 🎯 Quick Reference: Common Conversions

### Spacing
- `padding: 20px` → `p-5`
- `margin-bottom: 20px` → `mb-5`
- `gap: 10px` → `gap-2`

### Layout
- `display: flex` → `flex`
- `justify-content: space-between` → `justify-between`
- `align-items: center` → `items-center`
- `grid-template-columns: repeat(4, 1fr)` → `grid-cols-4`

### Typography
- `font-size: 18px` → `text-lg`
- `font-weight: 600` → `font-semibold`
- `text-align: center` → `text-center`

### Borders & Radius
- `border-radius: 12px` → `rounded-xl`
- `border: 1px solid` → `border`
- `border-bottom: 2px solid` → `border-b-2`

### Effects
- `box-shadow: ...` → `shadow-md`
- `transition: all 0.3s` → `transition-all`
- `hover:transform: translateY(-2px)` → `hover:-translate-y-0.5`

## ✨ Testing

After refactoring:
1. Test light and dark modes
2. Test responsive breakpoints
3. Verify all colors match original design
4. Check hover/focus states
5. Validate font size adjustments work

## 💡 Tips

1. Use browser DevTools to verify CSS is being applied
2. Run `npm run dev` to watch for changes
3. Use the utility classes defined in `app.css` for consistency
4. Keep the color scheme consistent (`#028a0f` green theme)
5. Always include dark mode variants with `dark:` prefix

---

**Note**: The main layout, login page, and faculty dashboard serve as complete examples of the refactoring pattern. Use them as reference for the remaining files.
