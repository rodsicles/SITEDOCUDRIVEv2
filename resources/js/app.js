import './bootstrap';
import './request-guard';
import Swal from 'sweetalert2';
window.Swal = Swal;

// Page ready - instant load, no slide-down animations
document.addEventListener('DOMContentLoaded', function() {
    // Auto-hide alerts after 5 seconds (instant remove — no fade animation)
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => alert.remove(), 5000);
    });

    // Sidebar menu active state
    const currentPath = window.location.pathname;
    const menuItems = document.querySelectorAll('.menu-item');
    menuItems.forEach(item => {
        if (item.getAttribute('href') === currentPath) {
            item.classList.add('active');
        }
        if (item.classList.contains('active')) {
            item.setAttribute('aria-current', 'page');
        }
    });

    // ── Sidebar section toggles ────────────────────────────────────────
    // Each .sidebar-section-label[data-target] toggles only its own group.
    // Other open sections stay open until their header is clicked again.
    // The section that contains the active .menu-item auto-opens on page load.

    function setSidebarGroupOpen(groupEl, toggleEl, open) {
        groupEl.classList.toggle('open', open);
        if (toggleEl) {
            toggleEl.setAttribute('aria-expanded', open ? 'true' : 'false');
            toggleEl.setAttribute('aria-controls', groupEl.id);
        }
        const chevron = toggleEl ? toggleEl.querySelector('.sidebar-chevron') : null;
        if (chevron) {
            chevron.classList.toggle('fa-chevron-down', !open);
            chevron.classList.toggle('fa-chevron-up', open);
        }
    }

    const sidebarToggles = document.querySelectorAll('.sidebar-section-label[data-target]');
    const sidebarStateKey = 'docudrive-sidebar-open:' + (document.body.dataset.userRole || 'user');
    let storedOpen = [];
    try {
        storedOpen = JSON.parse(sessionStorage.getItem(sidebarStateKey) || '[]');
    } catch (e) {
        storedOpen = [];
    }

    function persistSidebarState() {
        const openIds = Array.from(document.querySelectorAll('.sidebar-group-items.open')).map((el) => el.id);
        try {
            sessionStorage.setItem(sidebarStateKey, JSON.stringify(openIds));
        } catch (e) { /* ignore */ }
    }

    sidebarToggles.forEach(function(toggle) {
        toggle.addEventListener('click', function() {
            const targetId = this.dataset.target;
            const groupItems = document.getElementById(targetId);
            if (!groupItems) return;

            setSidebarGroupOpen(groupItems, this, !groupItems.classList.contains('open'));
            persistSidebarState();
        });
    });

    storedOpen.forEach(function (id) {
        const groupItems = document.getElementById(id);
        const toggle = document.querySelector('.sidebar-section-label[data-target="' + id + '"]');
        if (groupItems && toggle) {
            setSidebarGroupOpen(groupItems, toggle, true);
        }
    });

    // Auto-open the group that has the currently active menu item
    const activeMenuItem = document.querySelector('.sidebar-group-items .menu-item.active');
    if (activeMenuItem) {
        const parentGroup = activeMenuItem.closest('.sidebar-group-items');
        if (parentGroup) {
            const toggle = document.querySelector('.sidebar-section-label[data-target="' + parentGroup.id + '"]');
            setSidebarGroupOpen(parentGroup, toggle, true);
            persistSidebarState();
        }
    }
    // ──────────────────────────────────────────────────────────────────

    // Form submit guard (skip forms with custom AJAX submit or data-request-guard)
    const forms = document.querySelectorAll('form:not([data-request-guard]):not([data-custom-submit])');
    forms.forEach(form => {
        form.addEventListener('submit', function () {
            if (window.requestGuard && !window.requestGuard.canProceed(form.action || 'form')) {
                return;
            }
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn && !submitBtn.disabled) {
                submitBtn.disabled = true;
                if (!submitBtn.dataset.originalHtml) {
                    submitBtn.dataset.originalHtml = submitBtn.innerHTML;
                }
                submitBtn.innerHTML = '<span class="loading"></span> Processing...';
            }
        });
    });

    // Keep document navigation grounded in the current folder after a normal,
    // server-rendered navigation. This preserves Laravel's existing routing and
    // authorization while making deep folder navigation feel continuous.
    const currentFolderSection = document.getElementById('folder-current-section');
    if (currentFolderSection && sessionStorage.getItem('docudrive-focus-folder') === '1') {
        sessionStorage.removeItem('docudrive-focus-folder');
        currentFolderSection.scrollIntoView({ block: 'start' });
        currentFolderSection.focus({ preventScroll: true });
    }

    document.addEventListener('click', function (event) {
        const link = event.target.closest('.folder-card-link-new, .breadcrumb-link');
        if (link && link.href) {
            sessionStorage.setItem('docudrive-focus-folder', '1');
        }
    });
});

// ============================================
// Lazy Loading for Heavy Libraries
// ============================================

// Lazy load Chart.js only if chart element exists
if (document.getElementById('systemUsageChart')) {
    import('./chart-loader.js').catch(err => {
        console.error('Failed to load Chart.js:', err);
    });
}

// Lazy load FullCalendar only if calendar element exists
if (document.getElementById('calendar')) {
    import('./calendar-loader.js').catch(err => {
        console.error('Failed to load FullCalendar:', err);
    });
}
