# Remaining Tailwind Refactoring

## ⚠️ One Final File Needs Manual Fix:
**coordinator/edit-faculty.blade.php** - Replace all `modern-` classes and inline styles:
- `modern-btn` → `btn`
- `modern-btn-primary` → `btn btn-primary`
- `modern-btn-secondary` → `btn bg-gray-600 hover:bg-gray-700 text-white`
- `modern-btn- danger` → `btn btn-danger`
- `modern-alert` → `alert`
- `modern-alert-success` → `alert alert-success`
- `modern-alert-error` → `alert alert-error`
- `modern-content-card` → `content-card`
- `modern-card-header` → `card-header`
- `modern-card-title` → `card-title`
- `modern-form-grid` → `grid grid-cols-1 md:grid-cols-2 gap-5`
- `modern-form-group` → `form-group`
- `modern-form-label` → `form-label`
- `modern-form-control` → `form-control`
- `modern-help-text` → `text-gray-600 dark:text-gray-400 text-xs mt-1.5 block`
- Remove the `<style>` block at the top
- Change `style="..."` attributes to Tailwind classes

## ✅ Completed Coordinator Views (6/7):
- dashboard.blade.php ✓
- tasks.blade.php ✓
- faculty.blade.php ✓
- documents.blade.php ✓
- create-task.blade.php ✓
- create-faculty.blade.php ✓
- edit-faculty.blade.php ⚠️ (needs final cleanup)

## 📋 Still Need Refactoring:

### Dean Views (5 files):
- dean/dashboard.blade.php
- dean/analytics.blade.php
- dean/employees.blade.php
- dean/documents.blade.php
- dean/reports.blade.php

### Faculty Views (3 files):
- faculty/tasks.blade.php
- faculty/notifications.blade.php
- faculty/profile.blade.php
- faculty/documents.blade.php

### Other Views (10 files):
- profile/edit.blade.php
- employees/profile.blade.php
- leave/index.blade.php
- leave/create.blade.php
- leave/calendar.blade.php
- calendar/index.blade.php
- calendar/create.blade.php
- calendar/show.blade.php
- welcome.blade.php

## 🎯 Quick Refactoring Steps:

For each file:
1. Remove `<style>` tags completely
2. Replace these class patterns:
   - `modern-*` → use utility classes from app.css
   - `modern-stat-card` → `stat-card`
   - `modern-content-card` → `content-card`
   - `modern-table` → `data-table`
   - `modern-badge-*` → `badge-*`
3. Remove inline `style="..."` attributes
4. Use Tailwind utilities for layout:
   - `display: flex; gap: 10px` → `class="flex gap-2.5"`
   - `margin-bottom: 20px` → `class="mb-5"`
   - `text-align: center` → `class="text-center"`
   - `color: var(--text-light)` → `class="text-gray-600 dark:text-gray-400"`

## 📚 Reference:
- See TAILWIND_REFACTORING_GUIDE.md for full patterns
- App.css has all utility classes ready
- Build with: `npm run build`
