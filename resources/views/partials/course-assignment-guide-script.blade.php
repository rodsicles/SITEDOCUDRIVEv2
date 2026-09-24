{{-- Shared Course Assignment Guide filter behaviour. Include once per page. --}}
<script>
window.CourseAssignmentGuide = window.CourseAssignmentGuide || (function () {
    function applyFilter(root) {
        const grid = root.querySelector('[data-guide-grid]');
        const empty = root.querySelector('[data-guide-empty]');
        const searchEl = root.querySelector('[data-guide-search]');
        const termEl = root.querySelector('[data-guide-term]');
        const unlockEl = root.querySelector('[data-guide-unlock]');
        if (!grid) return;

        const q = (searchEl?.value || '').trim().toLowerCase();
        const term = termEl?.value || '';
        const unlock = !!(unlockEl && unlockEl.checked);
        const yearBtn = root.querySelector('.course-guide-chip.is-active');
        const year = yearBtn ? (yearBtn.getAttribute('data-guide-year') || '') : '';

        let visible = 0;
        grid.querySelectorAll('.course-checkbox-item').forEach(function (item) {
            const textOk = q === '' || item.textContent.toLowerCase().includes(q);
            const itemSem = item.getAttribute('data-semester') || '';
            const itemYear = item.getAttribute('data-year') || '';
            const termOk = unlock || !term || itemSem === term || itemSem === '';
            const yearOk = year === '' || itemYear === '' || itemYear === year;
            const show = textOk && termOk && yearOk;
            item.classList.toggle('course-hidden', !show);
            if (show) visible++;
        });

        if (empty) {
            empty.classList.toggle('visible', visible === 0 && grid.querySelectorAll('.course-checkbox-item').length > 0);
        }
        updateCount(root);
    }

    function updateCount(root) {
        const grid = root.querySelector('[data-guide-grid]');
        const countEl = root.querySelector('[data-guide-count]');
        if (!grid || !countEl) return;
        const n = grid.querySelectorAll('input[type="checkbox"]:checked').length;
        countEl.textContent = n + ' selected';
    }

    function bindRoot(root) {
        if (!root || root.dataset.guideBound === '1') return;
        root.dataset.guideBound = '1';

        const defaultTerm = root.getAttribute('data-default-term');
        const termEl = root.querySelector('[data-guide-term]');
        if (termEl && defaultTerm) termEl.value = defaultTerm;

        root.querySelector('[data-guide-search]')?.addEventListener('input', function () {
            applyFilter(root);
        });
        termEl?.addEventListener('change', function () {
            applyFilter(root);
        });
        root.querySelector('[data-guide-unlock]')?.addEventListener('change', function () {
            applyFilter(root);
        });

        root.querySelectorAll('.course-guide-chip').forEach(function (chip) {
            chip.addEventListener('click', function () {
                root.querySelectorAll('.course-guide-chip').forEach(function (c) {
                    c.classList.remove('is-active');
                    c.setAttribute('aria-pressed', 'false');
                });
                chip.classList.add('is-active');
                chip.setAttribute('aria-pressed', 'true');
                applyFilter(root);
            });
        });

        const grid = root.querySelector('[data-guide-grid]');
        if (grid) {
            grid.addEventListener('change', function (e) {
                const input = e.target;
                if (input && input.matches('input[type="checkbox"]')) {
                    input.closest('.course-checkbox-item')?.classList.toggle('selected', input.checked);
                    updateCount(root);
                }
            });
        }

        root.querySelector('[data-guide-clear]')?.addEventListener('click', function () {
            grid?.querySelectorAll('input[type="checkbox"]:checked').forEach(function (cb) {
                cb.checked = false;
                cb.closest('.course-checkbox-item')?.classList.remove('selected');
            });
            updateCount(root);
        });

        applyFilter(root);
    }

    function initAll(scope) {
        (scope || document).querySelectorAll('[data-course-guide]').forEach(bindRoot);
    }

    /** Build checkbox items for AJAX-loaded course lists (Dean create forms). */
    function renderCourses(root, courses, selectedIds) {
        const grid = root.querySelector('[data-guide-grid]');
        if (!grid) return;
        selectedIds = (selectedIds || []).map(Number);
        grid.innerHTML = '';
        if (!courses || courses.length === 0) {
            grid.innerHTML = '<span class="course-section-empty">No courses available for this program.</span>';
            updateCount(root);
            return;
        }
        courses.forEach(function (c) {
            const checked = selectedIds.indexOf(Number(c.id)) !== -1;
            const item = document.createElement('label');
            item.className = 'course-checkbox-item' + (checked ? ' selected' : '');
            item.setAttribute('data-year', c.year_level != null ? String(c.year_level) : '');
            item.setAttribute('data-semester', c.semester || '');
            const tagParts = [];
            if (c.year_level) tagParts.push(c.year_level + 'Y');
            if (c.semester) tagParts.push(c.semester);
            const tag = tagParts.length
                ? ' <em class="course-term-tag">' + tagParts.join(' · ') + '</em>'
                : '';
            item.innerHTML =
                '<input type="checkbox" name="course_ids[]" value="' + c.id + '"' + (checked ? ' checked' : '') + '>' +
                '<span><strong>' + c.code + '</strong> &ndash; ' + c.title + tag + '</span>';
            grid.appendChild(item);
        });
        applyFilter(root);
    }

    return { initAll: initAll, bindRoot: bindRoot, applyFilter: applyFilter, renderCourses: renderCourses, updateCount: updateCount };
})();

document.addEventListener('DOMContentLoaded', function () {
    window.CourseAssignmentGuide.initAll();
});
if (document.readyState !== 'loading') {
    window.CourseAssignmentGuide.initAll();
}
</script>
