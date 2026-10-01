const root = document.getElementById('teacher-load-workspace');

document.querySelector('.tl-filters-toggle')?.addEventListener('click', (event) => {
    const filters = document.getElementById('tl-filters');
    const collapsed = filters.classList.toggle('is-collapsed-mobile');
    event.currentTarget.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
});

if (root && document.getElementById('tl-dialog')) {
    const $ = (selector, context = document) => context.querySelector(selector);
    const $$ = (selector, context = document) => [...context.querySelectorAll(selector)];
    const dialog = $('#tl-dialog');
    const csrf = $('meta[name="csrf-token"]').content;
    const LAST_STEP = 2;
    let step = 0;
    let loadId = null;
    let lockVersion = 1;
    let courses = [];
    let context = { faculty: '', semester: '' };
    let pendingContext = null;
    let dirty = false;
    let previewUrl = null;
    let previewKey = null;
    let previewTimer = null;
    let loading = false;
    const facultySearch = $('#tl-faculty-search');
    const facultySelect = $('#tl-faculty');
    const semesterSelect = $('#tl-semester');
    const facultyOptions = facultySelect
        ? [...facultySelect.options].slice(1).map(option => ({
            value: option.value,
            text: option.textContent,
            program: option.dataset.program || '',
            role: option.dataset.role || '',
            hasCourses: option.dataset.hasCourses || '0',
        }))
        : [];

    const renderFacultyOptions = (query = '') => {
        if (!facultySelect) return;
        const selected = facultySelect.value;
        const needle = query.trim().toLowerCase();
        facultySelect.replaceChildren(new Option('Choose faculty or program coordinator', ''));
        facultyOptions
            // The current selection stays listed so filtering never silently clears it.
            .filter(option => !needle || option.value === selected || option.text.toLowerCase().includes(needle))
            .forEach(option => {
                const node = new Option(option.text, option.value, false, option.value === selected);
                node.dataset.program = option.program;
                node.dataset.role = option.role;
                node.dataset.hasCourses = option.hasCourses;
                facultySelect.add(node);
            });
    };

    const setFaculty = (value) => {
        if (facultySearch) facultySearch.value = '';
        renderFacultyOptions('');
        facultySelect.value = value;
    };

    const api = async (url, options = {}) => {
        const response = await fetch(url, {
            credentials: 'same-origin',
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrf, ...(options.headers || {}) },
            ...options,
        });
        const contentType = response.headers.get('content-type') || '';
        const data = contentType.includes('json') ? await response.json() : null;
        if (!response.ok) {
            const error = new Error(data?.message || 'Unable to complete the request.');
            error.errors = data?.errors || {};
            throw error;
        }
        return data;
    };

    const showError = (error) => {
        const box = $('#tl-error');
        const messages = Object.values(error.errors || {}).flat();
        box.textContent = messages.length ? messages.join(' ') : error.message;
        box.hidden = false;
        box.focus();
    };

    const clearError = () => { $('#tl-error').hidden = true; $('#tl-error').textContent = ''; };

    const setBusy = (busy) => {
        loading = busy;
        $$('#tl-dialog button').forEach(button => {
            if (button.id === 'tl-back') button.disabled = busy || step === 0;
            else if (button.id === 'tl-finalize') button.disabled = busy || !$('#tl-confirm-final').checked;
            else button.disabled = busy;
        });
        $('#tl-fields').disabled = busy;
    };

    /* ── Units ─────────────────────────────────────────────────────────── */

    const number = (value) => Number.parseFloat(value || 0) || 0;
    const formatUnits = (value) => String(Number(number(value).toFixed(2)));
    const courseById = (id) => courses.find(course => String(course.id) === String(id));
    const hasCatalogUnits = (course) => course && course.lecture_units != null && course.lab_units != null;

    const setUnitsEditable = (item, editable) => {
        ['lecture_units', 'lab_units'].forEach(key => { $(`[data-field="${key}"]`, item).readOnly = !editable; });
        const button = $('[data-units-edit]', item);
        button.setAttribute('aria-pressed', editable ? 'true' : 'false');
        button.classList.toggle('is-editing', editable);
        $('i', button).className = editable ? 'fas fa-check' : 'fas fa-pencil-alt';
        $('span', button).textContent = editable ? 'Done editing' : 'Edit units';
    };

    const updateUnitsHint = (item) => {
        const hint = $('[data-units-hint]', item);
        const course = courseById($('[data-field="course_id"]', item).value);
        if (!course) { hint.textContent = 'Choose a subject to fill units from the Course Catalog.'; return; }
        if (!hasCatalogUnits(course)) {
            hint.textContent = 'No units in the Course Catalog for this subject yet. Use Edit units to enter them for this load.';
            return;
        }
        const lec = number($('[data-field="lecture_units"]', item).value);
        const lab = number($('[data-field="lab_units"]', item).value);
        const catalog = `${formatUnits(course.lecture_units)} lec · ${formatUnits(course.lab_units)} lab`;
        hint.textContent = lec === number(course.lecture_units) && lab === number(course.lab_units)
            ? `From Course Catalog: ${catalog}`
            : `Adjusted for this load only. Course Catalog: ${catalog}`;
    };

    const applyCatalogUnits = (item) => {
        const course = courseById($('[data-field="course_id"]', item).value);
        const lec = $('[data-field="lecture_units"]', item);
        const lab = $('[data-field="lab_units"]', item);
        lec.value = hasCatalogUnits(course) ? formatUnits(course.lecture_units) : 0;
        lab.value = hasCatalogUnits(course) ? formatUnits(course.lab_units) : 0;
        setUnitsEditable(item, !!course && !hasCatalogUnits(course));
        updateUnitsHint(item);
    };

    /* ── Rows ──────────────────────────────────────────────────────────── */

    const fillCourseSelect = (select, selected = '') => {
        select.replaceChildren(new Option('Choose assigned course', ''));
        courses.forEach(course => select.add(new Option(`${course.code} — ${course.title}`, course.id, false, String(course.id) === String(selected))));
    };

    const addSchedule = (item, values = {}) => {
        const node = $('#tl-schedule-template').content.firstElementChild.cloneNode(true);
        (values.days || []).forEach(day => { const input = $(`input[value="${day}"]`, node); if (input) input.checked = true; });
        ['start', 'end', 'mode', 'room'].forEach(key => { const input = $(`[data-meeting="${key}"]`, node); if (input && values[key] != null) input.value = values[key]; });
        $('.tl-remove-schedule', node).addEventListener('click', () => { node.remove(); markDirty(); updateSummary(); });
        $('.tl-schedules', item).append(node);
    };

    const addCourse = (values = {}) => {
        if (!courses.length && !values.course_id) { $('#tl-course-notice').textContent = 'No assigned courses are available. Update course assignments on their profile first.'; return; }
        const item = $('#tl-course-template').content.firstElementChild.cloneNode(true);
        const select = $('[data-field="course_id"]', item);
        fillCourseSelect(select, values.course_id);
        ['section', 'class_size', 'lecture_units', 'lab_units', 'load_equivalent'].forEach(key => { if (values[key] != null) $(`[data-field="${key}"]`, item).value = values[key]; });
        // Saved rows keep their recorded units; only a new subject choice pulls catalog defaults.
        setUnitsEditable(item, false);
        updateUnitsHint(item);
        select.addEventListener('change', () => applyCatalogUnits(item));
        ['lecture_units', 'lab_units'].forEach(key => $(`[data-field="${key}"]`, item).addEventListener('input', () => updateUnitsHint(item)));
        $('[data-units-edit]', item).addEventListener('click', () => {
            const editing = $('[data-units-edit]', item).getAttribute('aria-pressed') !== 'true';
            setUnitsEditable(item, editing);
            if (editing) $('[data-field="lecture_units"]', item).focus();
        });
        $('.tl-remove', item).addEventListener('click', () => { item.remove(); markDirty(); updateSummary(); });
        $('.tl-add-schedule', item).addEventListener('click', () => { addSchedule(item); markDirty(); });
        (values.schedules || []).forEach(meeting => addSchedule(item, meeting));
        $('#tl-course-rows').append(item);
    };

    const addDuty = (values = {}) => {
        const item = $('#tl-duty-template').content.firstElementChild.cloneNode(true);
        if (values.title != null) $('[data-field="title"]', item).value = values.title;
        if (values.load_equivalent != null) $('[data-field="load_equivalent"]', item).value = values.load_equivalent;
        $('.tl-remove', item).addEventListener('click', () => { item.remove(); markDirty(); updateSummary(); });
        $('#tl-duty-rows').append(item);
    };

    /* ── Faculty / semester context ────────────────────────────────────── */

    const setFacultyTag = (text) => {
        const tag = $('#tl-faculty-tag');
        if (!tag) return;
        tag.hidden = !text;
        tag.textContent = text || '';
    };

    const fetchContext = async (employee, semester) => {
        if (!employee || !semester) return null;
        const query = new URLSearchParams({ employee_id: employee, semester });
        return api(`${root.dataset.options}?${query}`);
    };

    const commitContext = (next, data) => {
        context = { faculty: next.faculty, semester: next.semester };
        const selected = facultySelect.selectedOptions[0];
        const program = selected?.dataset.program || '—';
        const role = data?.role_label || selected?.dataset.role || 'This person';
        $('#tl-program').textContent = `SITE / ${program}`;
        courses = data?.courses || [];
        if (data && !loadId && data.employment_status) $('#tl-employment').value = data.employment_status;

        const noCourses = next.faculty && (data ? data.has_assigned_courses === false : selected?.dataset.hasCourses === '0');
        setFacultyTag(noCourses ? `${role} has no courses assigned yet. You can still open a load and add duties; assign courses on their profile before adding teaching rows.` : '');

        $('#tl-course-notice').textContent = !next.faculty || !next.semester
            ? 'Choose a faculty or program coordinator and semester first.'
            : (courses.length
                ? `${courses.length} assigned course${courses.length === 1 ? '' : 's'} available for this semester.`
                : 'No assigned courses are available for this person and semester.');

        $$('#tl-course-rows .tl-item').forEach(item => {
            const select = $('[data-field="course_id"]', item);
            fillCourseSelect(select, select.value);
            updateUnitsHint(item);
        });
        updateSummary();
    };

    const loadCourses = async () => {
        const next = { faculty: facultySelect.value, semester: semesterSelect.value };
        commitContext(next, await fetchContext(next.faculty, next.semester));
    };

    const rowLabel = (item, index) => {
        const select = $('[data-field="course_id"]', item);
        const text = select.selectedOptions[0]?.textContent || 'Selected subject';
        const section = $('[data-field="section"]', item).value.trim();
        return `Row ${index + 1}: ${text}${section ? ` · Section ${section}` : ''}`;
    };

    const onContextChange = async () => {
        const next = { faculty: facultySelect.value, semester: semesterSelect.value };
        if (next.faculty === context.faculty && next.semester === context.semester) return;
        clearError();
        let data = null;
        try { data = await fetchContext(next.faculty, next.semester); }
        catch (error) { showError(error); revertContext(); return; }

        const allowed = new Set((data?.courses || []).map(course => String(course.id)));
        const rows = $$('#tl-course-rows .tl-item');
        const affected = rows.filter(item => {
            const value = $('[data-field="course_id"]', item).value;
            return value && !allowed.has(value);
        });

        if (!affected.length) { commitContext(next, data); markDirty(); return; }

        pendingContext = { next, data, affected };
        const list = $('#tl-context-rows');
        list.replaceChildren(...affected.map(item => {
            const li = document.createElement('li');
            li.textContent = rowLabel(item, rows.indexOf(item));
            return li;
        }));
        $('#tl-context-confirm').hidden = false;
        $('#tl-context-apply').focus();
    };

    const revertContext = () => {
        setFaculty(context.faculty);
        semesterSelect.value = context.semester;
    };

    /* ── Review, preview, navigation ───────────────────────────────────── */

    const readMeeting = meeting => ({
        days: $$('input[type="checkbox"]:checked', meeting).map(input => input.value),
        start: $('[data-meeting="start"]', meeting).value,
        end: $('[data-meeting="end"]', meeting).value,
        mode: $('[data-meeting="mode"]', meeting)?.value || 'Lec',
        room: $('[data-meeting="room"]', meeting).value.trim(),
    });

    const payload = () => {
        const items = $$('#tl-course-rows .tl-item').map(item => ({
            kind: 'course', course_id: Number($('[data-field="course_id"]', item).value), title: null,
            section: $('[data-field="section"]', item).value.trim(), class_size: Number($('[data-field="class_size"]', item).value),
            lecture_units: number($('[data-field="lecture_units"]', item).value), lab_units: number($('[data-field="lab_units"]', item).value),
            load_equivalent: number($('[data-field="load_equivalent"]', item).value), schedules: $$('.tl-meeting', item).map(readMeeting),
        }));
        $$('#tl-duty-rows .tl-item').forEach(item => items.push({
            kind: 'duty', course_id: null, title: $('[data-field="title"]', item).value.trim(), section: null, class_size: null,
            lecture_units: 0, lab_units: 0, load_equivalent: number($('[data-field="load_equivalent"]', item).value), schedules: [],
        }));
        return { employee_id: Number(facultySelect.value), school_year_id: Number($('#tl-year').value), semester: semesterSelect.value, employment_status: $('#tl-employment').value, lock_version: lockVersion, items };
    };

    const renderReview = (data) => {
        const review = $('#tl-review');
        const teaching = data.items.filter(item => item.kind === 'course');
        const duties = data.items.filter(item => item.kind === 'duty');
        const summary = document.createElement('p');
        summary.className = 'tl-note';
        summary.textContent = `${teaching.length} teaching row${teaching.length === 1 ? '' : 's'} and ${duties.length} additional dut${duties.length === 1 ? 'y' : 'ies'} will appear on the PDF.`;
        if (!data.items.length) { review.replaceChildren(summary); return; }

        const table = document.createElement('table');
        table.className = 'tl-review-table';
        const head = table.createTHead().insertRow();
        ['Subject / duty', 'Section', 'Lec', 'Lab', 'Load'].forEach(label => { const th = document.createElement('th'); th.textContent = label; head.append(th); });
        const body = table.createTBody();
        data.items.forEach(item => {
            const row = body.insertRow();
            const course = item.kind === 'course' ? courseById(item.course_id) : null;
            [
                item.kind === 'course' ? (course ? `${course.code} — ${course.title}` : 'Subject not selected') : (item.title || 'Untitled duty'),
                item.section || '—',
                item.kind === 'course' ? formatUnits(item.lecture_units) : '—',
                item.kind === 'course' ? formatUnits(item.lab_units) : '—',
                String(Number(item.load_equivalent.toFixed(3))),
            ].forEach(text => { row.insertCell().textContent = text; });
        });
        review.replaceChildren(summary, table);
    };

    const updateSummary = () => {
        const data = payload();
        const teaching = data.items.filter(item => item.kind === 'course');
        const units = teaching.reduce((sum, item) => sum + item.lecture_units + item.lab_units, 0);
        const total = data.items.reduce((sum, item) => sum + item.load_equivalent, 0);
        $('#tl-units').textContent = units.toFixed(2).replace(/\.00$/, '');
        $('#tl-total').textContent = total.toFixed(3).replace(/0+$/, '').replace(/\.$/, '') || '0';
        renderReview(data);
    };

    const previewSignature = () => { const data = payload(); delete data.lock_version; return JSON.stringify(data); };

    const preview = async () => {
        clearError(); setBusy(true);
        const signature = previewSignature();
        try {
            const data = payload(); data.id = loadId;
            const response = await fetch(root.dataset.preview, { method: 'POST', credentials: 'same-origin', headers: { 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/pdf', 'Content-Type': 'application/json' }, body: JSON.stringify(data) });
            const contentType = (response.headers.get('content-type') || '').toLowerCase();
            if (!response.ok) {
                let message = 'Preview could not be generated.';
                if (contentType.includes('application/json')) {
                    const details = await response.json();
                    message = details.message || message;
                    const error = new Error(message);
                    error.errors = details.errors;
                    throw error;
                }
                throw new Error(message);
            }
            if (!contentType.includes('pdf')) {
                throw new Error('Preview returned an unexpected file type.');
            }
            if (previewUrl) URL.revokeObjectURL(previewUrl); previewUrl = URL.createObjectURL(await response.blob());
            previewKey = signature;
            $('#tl-pdf-frame').src = previewUrl; $('#tl-pdf-frame').hidden = false; $('#tl-preview-link').href = previewUrl; $('#tl-preview-link').hidden = false; $('#tl-preview-note').textContent = 'Preview reflects the current form.';
        } catch (error) { showError(error); } finally { setBusy(false); }
    };

    const refreshPreviewIfStale = () => {
        if (step !== LAST_STEP || previewSignature() === previewKey) return;
        $('#tl-preview-note').textContent = 'Updating preview with your latest changes…';
        clearTimeout(previewTimer);
        previewTimer = setTimeout(() => { if (step === LAST_STEP && !loading) preview(); }, 700);
    };

    const setStep = (value) => {
        step = Math.max(0, Math.min(LAST_STEP, value));
        $$('[data-step]').forEach(section => section.hidden = Number(section.dataset.step) !== step);
        $$('[data-step-label]').forEach(label => {
            const index = Number(label.dataset.stepLabel);
            label.toggleAttribute('aria-current', index === step);
            label.classList.toggle('is-done', index < step);
        });
        $('#tl-back').disabled = step === 0;
        $('#tl-next').hidden = step === LAST_STEP;
        $('#tl-finalize').hidden = step !== LAST_STEP;
        $('#tl-scroll').scrollTop = 0;
        if (step === LAST_STEP) { updateSummary(); refreshPreviewIfStale(); }
    };

    const validateStep = (index) => {
        clearError();
        if (index === 0 && (!facultySelect.value || !$('#tl-year').value || !semesterSelect.value)) {
            setStep(0);
            (!facultySelect.value ? facultySelect : (!$('#tl-year').value ? $('#tl-year') : semesterSelect)).reportValidity();
            return false;
        }
        const section = $(`[data-step="${index}"]`);
        const invalid = $$('input,select', section).find(input => !input.checkValidity());
        if (invalid) {
            setStep(index);
            invalid.reportValidity();
            return false;
        }
        if (index === 1) {
            const missingSchedule = $$('#tl-course-rows .tl-meeting').find(meeting => !$$('input[type="checkbox"]:checked', meeting).length);
            if (missingSchedule) {
                setStep(1);
                showError(new Error('Choose at least one day for every schedule row, or remove the empty schedule.'));
                return false;
            }
        }
        return true;
    };

    const goToStep = (target) => {
        if (target <= step) { setStep(target); return; }
        for (let index = step; index < target; index++) {
            if (!validateStep(index)) return;
        }
        setStep(target);
    };

    const markDirty = () => { if (!loading) { dirty = true; $('#tl-save-state').textContent = 'Unsaved changes'; } };

    const reset = () => {
        loadId = null; lockVersion = 1; courses = []; dirty = false; pendingContext = null; previewKey = null; clearError();
        $('#tl-dialog-title').textContent = 'Create Teacher’s Load';
        facultySelect.disabled = false; $('#tl-year').disabled = false; semesterSelect.disabled = false;
        setFaculty(''); $('#tl-employment').value = 'Full-Time Faculty'; $('#tl-program').textContent = 'SITE / —';
        context = { faculty: '', semester: semesterSelect.value };
        $('#tl-course-rows').replaceChildren(); $('#tl-duty-rows').replaceChildren(); $('#tl-course-notice').textContent = '';
        setFacultyTag(''); $('#tl-context-confirm').hidden = true; $('#tl-discard').hidden = true;
        $('#tl-confirm-final').checked = false; $('#tl-finalize').disabled = true; $('#tl-save-state').textContent = 'Unsaved draft';
        if (previewUrl) URL.revokeObjectURL(previewUrl); previewUrl = null; $('#tl-pdf-frame').hidden = true; $('#tl-preview-link').hidden = true;
        $('#tl-preview-note').textContent = 'The preview refreshes automatically when you open this step.';
        setStep(0);
    };

    const close = (force = false) => {
        if (dirty && !force) { $('#tl-discard').hidden = false; return; }
        $('#tl-discard').hidden = true; dialog.close(); reset();
    };

    const save = async () => {
        clearError(); setBusy(true);
        try {
            const url = loadId ? `${root.dataset.base}/${loadId}` : root.dataset.base;
            const data = await api(url, { method: loadId ? 'PATCH' : 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload()) });
            loadId = data.id; lockVersion = data.lock_version; dirty = false; $('#tl-save-state').textContent = 'Draft saved';
            $('#tl-dialog-title').textContent = 'Edit Teacher’s Load'; facultySelect.disabled = $('#tl-year').disabled = semesterSelect.disabled = true;
            return true;
        } catch (error) { showError(error); return false; } finally { setBusy(false); }
    };

    const edit = async id => {
        reset(); dialog.showModal(); setBusy(true);
        try {
            const data = await api(`${root.dataset.base}/${id}`);
            loadId = data.id; lockVersion = data.lock_version; $('#tl-dialog-title').textContent = 'Edit Teacher’s Load';
            setFaculty(String(data.employee_id)); $('#tl-year').value = data.school_year_id; semesterSelect.value = data.semester; $('#tl-employment').value = data.employment_status;
            facultySelect.disabled = $('#tl-year').disabled = semesterSelect.disabled = true;
            await loadCourses();
            data.items.filter(item => item.kind === 'course').forEach(addCourse); data.items.filter(item => item.kind === 'duty').forEach(addDuty);
            updateSummary();
            dirty = false; $('#tl-save-state').textContent = 'Draft saved';
        } catch (error) { showError(error); } finally { setBusy(false); }
    };

    /* ── Events ────────────────────────────────────────────────────────── */

    $('#tl-create').addEventListener('click', () => { reset(); dialog.showModal(); });
    $$('[data-edit-load]').forEach(button => button.addEventListener('click', () => edit(button.dataset.editLoad)));
    $('#tl-close').addEventListener('click', () => close());
    $('#tl-keep').addEventListener('click', () => $('#tl-discard').hidden = true);
    $('#tl-discard-confirm').addEventListener('click', () => close(true));
    dialog.addEventListener('cancel', event => { event.preventDefault(); close(); });

    facultySearch?.addEventListener('input', () => renderFacultyOptions(facultySearch.value));
    facultySelect.addEventListener('change', onContextChange);
    semesterSelect.addEventListener('change', onContextChange);

    $('#tl-context-apply').addEventListener('click', () => {
        if (!pendingContext) return;
        pendingContext.affected.forEach(item => item.remove());
        commitContext(pendingContext.next, pendingContext.data);
        pendingContext = null;
        $('#tl-context-confirm').hidden = true;
        markDirty();
    });
    $('#tl-context-cancel').addEventListener('click', () => {
        pendingContext = null;
        $('#tl-context-confirm').hidden = true;
        revertContext();
    });

    dialog.addEventListener('input', event => {
        if (event.target.id === 'tl-confirm-final' || event.target.id === 'tl-faculty-search') return;
        markDirty(); updateSummary(); refreshPreviewIfStale();
    });
    dialog.addEventListener('change', event => {
        if (event.target.matches('#tl-course-rows select, #tl-course-rows input[type="checkbox"]')) { markDirty(); updateSummary(); refreshPreviewIfStale(); }
    });

    $('#tl-add-course').addEventListener('click', () => { addCourse(); markDirty(); updateSummary(); });
    $('#tl-add-duty').addEventListener('click', () => { addDuty(); markDirty(); updateSummary(); });
    $$('[data-go-step]').forEach(button => button.addEventListener('click', () => goToStep(Number(button.dataset.goStep))));
    $('#tl-back').addEventListener('click', () => setStep(step - 1));
    $('#tl-next').addEventListener('click', () => goToStep(step + 1));
    $('#tl-save').addEventListener('click', save);
    $('#tl-preview').addEventListener('click', preview);
    $('#tl-confirm-final').addEventListener('change', event => $('#tl-finalize').disabled = !event.target.checked);
    $('#tl-finalize').addEventListener('click', async () => {
        clearError(); if (!$('#tl-confirm-final').checked) return;
        for (let index = 0; index < LAST_STEP; index++) { if (!validateStep(index)) return; }
        if (dirty && !(await save())) return; setBusy(true);
        try { await api(`${root.dataset.base}/${loadId}/finalize`, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ lock_version: lockVersion }) }); window.location.reload(); }
        catch (error) { showError(error); setBusy(false); }
    });
}
