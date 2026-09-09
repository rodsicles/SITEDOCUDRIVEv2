# Performance Optimization Guide for Laravel Cloud Deployment

## Current Issues

### External CDN Dependencies (~970KB)
- Font Awesome: ~70KB on every page
- Chart.js: ~200KB on dean dashboard
- FullCalendar: ~700KB on calendar pages

### Load Times (Pre-Optimization)
- Dashboard: 2-3 seconds
- Dean Dashboard: 3-5 seconds
- Calendar Pages: 4-6 seconds

## Optimization Steps

### 1. Install Font Awesome Locally (HIGH PRIORITY)

```bash
# Install Font Awesome via NPM
npm install @fortawesome/fontawesome-free --save

# Or download and place in public/fonts/fontawesome
```

**Update resources/css/app.css:**
```css
@import '@fortawesome/fontawesome-free/css/all.min.css';
```

**Remove from layouts/dashboard.blade.php and auth/login.blade.php:**
```html
<!-- DELETE THIS LINE -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
```

**Savings:** ~70KB from CDN, 300-500ms faster load time

---

### 2. Install Chart.js via NPM (MEDIUM PRIORITY)

```bash
npm install chart.js --save
```

**Create resources/js/chart-loader.js:**
```javascript
import Chart from 'chart.js/auto';
window.Chart = Chart;
```

**Update resources/js/app.js:**
```javascript
// Only load Chart.js if needed
if (document.getElementById('systemUsageChart')) {
    import('./chart-loader.js');
}
```

**Remove from dean/dashboard.blade.php:**
```html
<!-- DELETE THIS LINE -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
```

**Savings:** ~200KB from CDN, 500-800ms faster load time

---

### 3. Install FullCalendar via NPM (HIGH PRIORITY)

```bash
npm install @fullcalendar/core @fullcalendar/daygrid @fullcalendar/interaction --save
```

**Create resources/js/calendar-loader.js:**
```javascript
import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction';

window.Calendar = Calendar;
window.dayGridPlugin = dayGridPlugin;
window.interactionPlugin = interactionPlugin;
```

**Update vite.config.js:**
```javascript
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/calendar-loader.js'  // Add this
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
    build: {
        rollupOptions: {
            output: {
                manualChunks: {
                    'fullcalendar': ['@fullcalendar/core', '@fullcalendar/daygrid', '@fullcalendar/interaction']
                }
            }
        }
    }
});
```

**Remove from calendar pages:**
```html
<!-- DELETE THESE LINES -->
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
```

**Savings:** ~700KB from CDN, 1-2 seconds faster load time

---

### 4. Enable Browser Caching (.htaccess or nginx)

**For Apache (.htaccess in public folder):**
```apache
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType text/css "access plus 1 year"
    ExpiresByType application/javascript "access plus 1 year"
    ExpiresByType image/png "access plus 1 year"
    ExpiresByType image/jpg "access plus 1 year"
    ExpiresByType image/jpeg "access plus 1 year"
    ExpiresByType image/gif "access plus 1 year"
    ExpiresByType image/svg+xml "access plus 1 year"
    ExpiresByType font/woff2 "access plus 1 year"
</IfModule>
```

---

### 5. Optimize Database Queries (CRITICAL)

**Add eager loading in controllers:**

```php
// BAD - N+1 Query Problem
$facultyMembers = User::where('role_id', 3)->paginate(10);
// Each faculty loads employee separately = 11 queries

// GOOD - Eager Loading
$facultyMembers = User::where('role_id', 3)
    ->with('employee', 'role')
    ->paginate(10);
// Only 2 queries total
```

**Add indexes to frequently queried columns:**

```bash
php artisan make:migration add_indexes_for_performance
```

```php
// database/migrations/xxxx_add_indexes_for_performance.php
public function up()
{
    Schema::table('users', function (Blueprint $table) {
        $table->index('role_id');
        $table->index('status');
    });

    Schema::table('employees', function (Blueprint $table) {
        $table->index('department');
    });

    Schema::table('tasks', function (Blueprint $table) {
        $table->index(['assigned_to', 'status']);
    });

    Schema::table('leave_requests', function (Blueprint $table) {
        $table->index(['employee_id', 'status']);
    });

    Schema::table('activity_logs', function (Blueprint $table) {
        $table->index('log_date');
        $table->index('user_id');
    });
}
```

---

### 6. Add View Caching (for production)

```bash
# Cache views in production
php artisan view:cache

# Cache routes
php artisan route:cache

# Cache config
php artisan config:cache
```

---

### 7. Add Preload Headers

**In AppServiceProvider.php:**
```php
public function boot()
{
    if (app()->environment('production')) {
        \URL::forceScheme('https');

        // Preload critical assets
        header('Link: </build/assets/app.css>; rel=preload; as=style', false);
        header('Link: </build/assets/app.js>; rel=preload; as=script', false);
    }
}
```

---

### 8. Lazy Load Images

**Add to app.css:**
```css
img[loading="lazy"] {
    opacity: 0;
    transition: opacity 0.3s;
}

img[loading="lazy"].loaded {
    opacity: 1;
}
```

**Update image tags:**
```html
<img src="{{ asset('images/site-logo.png') }}"
     alt="SITE Logo"
     loading="lazy"
     class="w-16 h-16 mb-2 object-contain">
```

---

## Implementation Priority

### Phase 1: Quick Wins (1-2 hours)
1. ✅ Install Font Awesome locally
2. ✅ Enable browser caching
3. ✅ Add view caching commands

**Expected Improvement:** 40-50% faster load times

---

### Phase 2: Major Performance (3-4 hours)
1. ✅ Install Chart.js locally
2. ✅ Install FullCalendar locally
3. ✅ Add database indexes

**Expected Improvement:** 60-70% faster load times

---

### Phase 3: Advanced Optimization (optional)
1. Add service worker for offline support
2. Implement Redis caching
3. Add lazy loading for all images
4. Optimize database queries with eager loading

**Expected Improvement:** 80-90% faster load times

---

## Deployment Commands for Laravel Cloud

```bash
# Install dependencies
npm install

# Build optimized assets
npm run build

# Clear all caches
php artisan optimize:clear

# Cache everything
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run migrations (if new indexes added)
php artisan migrate

# Deploy to Laravel Cloud
# (Laravel Cloud will automatically use optimized assets)
```

---

## Performance Testing

**Before optimization:**
```bash
# Run this to test
curl -w "@curl-format.txt" -o /dev/null -s https://your-app.laravel.app
```

**Expected Results:**
- Time to first byte: < 200ms
- Page load: < 1 second
- Total size: < 300KB (down from 1.1MB)

---

## Expected Results After All Optimizations

| Metric | Before | After | Improvement |
|--------|--------|-------|-------------|
| Dashboard Load | 2-3s | 0.5-1s | 66% faster |
| Dean Dashboard | 3-5s | 1-2s | 60% faster |
| Calendar Pages | 4-6s | 1.5-2.5s | 50% faster |
| Page Navigation | 1-2s | 0.3-0.5s | 75% faster |
| Total Asset Size | 1.1MB | 280KB | 75% smaller |
| CDN Requests | 3-5 | 0 | 100% fewer |

---

## Monitoring Performance

**Add to layouts/dashboard.blade.php (bottom):**
```html
@if(app()->environment('production'))
<script>
    // Monitor page load performance
    window.addEventListener('load', function() {
        const perfData = performance.getEntriesByType('navigation')[0];
        console.log('Page Load Time:', perfData.loadEventEnd - perfData.fetchStart, 'ms');

        // Send to analytics if needed
        if (perfData.loadEventEnd - perfData.fetchStart > 3000) {
            console.warn('Slow page load detected!');
        }
    });
</script>
@endif
```
