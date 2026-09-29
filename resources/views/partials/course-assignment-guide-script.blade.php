{{-- Shared Course Assignment Guide behaviour. Include once per page. --}}
<script>
window.CourseAssignmentGuide = window.CourseAssignmentGuide || (function () {
    const REQUIRED_MESSAGE = 'Assign at least one subject from the selected program.';

    function items(root) {
        return root.querySelectorAll('[data-guide-grid] .course-checkbox-item');
    }

    function checkedIds(root) {
        return Array.from(root.querySelectorAll('[data-guide-grid] input[type="checkbox"]:checked'))
            .map(function (cb) { return Number(cb.value); });
    }

    function errorEl(root) {
        return root.closest('[data-course-picker-wrap]')?.querySelector('[data-guide-error]') || null;
    }

    function showError(root, message) {
        const el = errorEl(root);
        if (!el) return;
        el.textContent = message;
        el.classList.remove('hidden');
    }

    function hideError(root) {
        errorEl(root)?.classList.add('hidden');
    }

    function applyFilter(root) {
        const grid = root.querySelector('[data-guide-grid]');
        const empty = root.querySelector('[data-guide-empty]');
        if (!grid) return;

        const q = (root.querySelector('[data-guide-search]')?.value || '').trim().toLowerCase();
        const term = root.querySelector('[data-guide-term]')?.value || '';
        const unlockEl = root.querySelector('[data-guide-unlock]');
        const unlock = !!(unlockEl && unlockEl.checked);
        const yearBtn = root.querySelector('.course-guide-chip.is-active');
        const year = yearBtn ? (yearBtn.getAttribute('data-guide-year') || '') : '';

        let visible = 0;
        items(root).forEach(function (item) {
            const textOk = q === '' || item.textContent.toLowerCase().includes(q);
            const itemSem = item.getAttribute('data-semester') || '';
            const itemYear = item.getAttribute('data-year') || '';
            const termOk = unlock || !term || itemSem === term || itemSem === '';
            const yearOk = year === '' || itemYear === '' || itemYear === year;
            // Selected subjects stay visible so restored selections are never hidden by the term filter.
            const selected = !!item.querySelector('input:checked');
            const show = textOk && ((termOk && yearOk) || selected);
            item.classList.toggle('course-hidden', !show);
            if (show) visible++;
        });

        root.classList.toggle('is-empty', items(root).length === 0);
        if (empty) {
            empty.classList.toggle('visible', visible === 0 && items(root).length > 0);
        }
        updateCount(root);
    }

    function updateCount(root) {
        const countEl = root.querySelector('[data-guide-count]');
        if (countEl) countEl.textContent = checkedIds(root).length + ' selected';
    }

    function setState(root, message, isError) {
        const grid = root.querySelector('[data-guide-grid]');
        if (!grid) return;
        grid.innerHTML = '';
        const span = document.createElement('span');
        span.className = 'course-section-empty' + (isError ? ' is-error' : '');
        span.textContent = message;
        grid.appendChild(span);
        syncYearChips(root);
        applyFilter(root);
    }

    /** Hide year chips (e.g. 5Y) the current program has no subjects for. */
    function syncYearChips(root) {
        const years = new Set(Array.from(items(root)).map(function (item) { return item.getAttribute('data-year') || ''; }));
        root.querySelectorAll('.course-guide-chip[data-guide-year]').forEach(function (chip) {
            const year = chip.getAttribute('data-guide-year');
            if (!year) return;
            const show = years.has(year) || (year !== '5' && years.size === 0);
            chip.hidden = !show;
            if (!show && chip.classList.contains('is-active')) {
                root.querySelector('.course-guide-chip[data-guide-year=""]')?.click();
            }
        });
    }

    /** Build checkbox items for AJAX-loaded course lists. */
    function renderCourses(root, courses, selectedIds, emptyMessage) {
        const grid = root.querySelector('[data-guide-grid]');
        if (!grid) return;
        selectedIds = (selectedIds || []).map(Number);
        if (!courses || courses.length === 0) {
            setState(root, emptyMessage || 'No subjects are available for this program yet.');
            return;
        }
        const inputName = grid.getAttribute('data-input-name') || 'course_ids[]';
        grid.innerHTML = '';
        courses.forEach(function (c) {
            const checked = selectedIds.indexOf(Number(c.id)) !== -1;
            const item = document.createElement('label');
            item.className = 'course-checkbox-item' + (checked ? ' selected' : '');
            item.setAttribute('data-year', c.year_level != null ? String(c.year_level) : '');
            item.setAttribute('data-semester', c.semester || '');

            const input = document.createElement('input');
            input.type = 'checkbox';
            input.name = inputName;
            input.value = c.id;
            input.checked = checked;

            const text = document.createElement('span');
            const code = document.createElement('strong');
            code.textContent = c.code;
            text.appendChild(code);
            text.appendChild(document.createTextNode(' \u2013 ' + c.title));
            const tagParts = [];
            if (c.year_level) tagParts.push(c.year_level + 'Y');
            if (c.semester) tagParts.push(c.semester);
            if (tagParts.length) {
                const tag = document.createElement('em');
                tag.className = 'course-term-tag';
                tag.textContent = tagParts.join(' \u00b7 ');
                text.appendChild(document.createTextNode(' '));
                text.appendChild(tag);
            }

            item.appendChild(input);
            item.appendChild(text);
            grid.appendChild(item);
        });
        syncYearChips(root);
        applyFilter(root);
    }

    function loadProgram(root, select, url) {
        const program = select.value;
        const keep = checkedIds(root);
        root.setAttribute('data-program', program);
        hideError(root);

        if (!program) {
            setState(root, 'Select a program to load its subjects.');
            return;
        }

        const programLabel = select.options[select.selectedIndex]?.text || program;
        setState(root, 'Loading subjects\u2026');
        fetch(url + '?dept=' + encodeURIComponent(program), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
        })
            .then(function (res) {
                if (!res.ok) throw new Error('HTTP ' + res.status);
                return res.json();
            })
            .then(function (courses) {
                if (root.getAttribute('data-program') !== program) return;
                renderCourses(root, courses, keep,
                    'No curriculum has been set up for ' + programLabel + ' yet. You can save now and assign subjects after courses are added in the Course Catalog.');
            })
            .catch(function () {
                if (root.getAttribute('data-program') !== program) return;
                setState(root, 'Failed to load subjects. Please try again.', true);
            });
    }

    function bindProgramSource(root) {
        const selectId = root.getAttribute('data-program-select');
        const url = root.getAttribute('data-courses-url');
        const select = selectId ? document.getElementById(selectId) : null;
        if (!select || !url) return;

        select.addEventListener('change', function () { loadProgram(root, select, url); });

        // Browsers can restore a different select value on back/forward navigation.
        if ((root.getAttribute('data-program') || '') !== select.value) {
            loadProgram(root, select, url);
        }
    }

    function bindRequired(root) {
        if (root.getAttribute('data-required') !== '1') return;
        const form = root.closest('form');
        if (!form) return;

        // Capture phase so the check runs before double-submit handlers disable the button.
        form.addEventListener('submit', function (e) {
            if (items(root).length === 0 || checkedIds(root).length > 0) return;
            e.preventDefault();
            e.stopImmediatePropagation();
            showError(root, REQUIRED_MESSAGE);
            root.scrollIntoView({ behavior: 'instant', block: 'center' });
        }, true);
    }

    function bindRoot(root) {
        if (!root || root.dataset.guideBound === '1') return;
        root.dataset.guideBound = '1';

        const defaultTerm = root.getAttribute('data-default-term');
        const termEl = root.querySelector('[data-guide-term]');
        if (termEl && defaultTerm) termEl.value = defaultTerm;

        root.querySelector('[data-guide-search]')?.addEventListener('input', function () { applyFilter(root); });
        termEl?.addEventListener('change', function () { applyFilter(root); });
        root.querySelector('[data-guide-unlock]')?.addEventListener('change', function () { applyFilter(root); });

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
        grid?.addEventListener('change', function (e) {
            const input = e.target;
            if (input && input.matches('input[type="checkbox"]')) {
                input.closest('.course-checkbox-item')?.classList.toggle('selected', input.checked);
                if (input.checked) hideError(root);
                updateCount(root);
            }
        });

        root.querySelector('[data-guide-clear]')?.addEventListener('click', function () {
            grid?.querySelectorAll('input[type="checkbox"]:checked').forEach(function (cb) {
                cb.checked = false;
                cb.closest('.course-checkbox-item')?.classList.remove('selected');
            });
            applyFilter(root);
        });

        bindProgramSource(root);
        bindRequired(root);
        syncYearChips(root);
        applyFilter(root);
    }

    function bindNumberPreview(input) {
        if (!input || input.dataset.previewBound === '1') return;
        input.dataset.previewBound = '1';
        const select = document.getElementById(input.getAttribute('data-program-select'));
        let previews = {};
        try { previews = JSON.parse(input.getAttribute('data-number-preview') || '{}'); } catch (e) {}
        if (!select) return;

        const update = function () {
            const value = previews[select.value];
            input.value = value || '';
            input.placeholder = value ? '' : 'Select program first';
        };
        select.addEventListener('change', update);
        update();
    }

    function initAll(scope) {
        (scope || document).querySelectorAll('[data-course-guide]').forEach(bindRoot);
        (scope || document).querySelectorAll('[data-number-preview]').forEach(bindNumberPreview);
    }

    return {
        initAll: initAll,
        bindRoot: bindRoot,
        applyFilter: applyFilter,
        renderCourses: renderCourses,
        updateCount: updateCount,
    };
})();

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () { window.CourseAssignmentGuide.initAll(); });
} else {
    window.CourseAssignmentGuide.initAll();
}
</script>
