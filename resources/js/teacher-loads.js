const root = document.getElementById('teacher-load-workspace');

if (root && document.getElementById('tl-dialog')) {
    const $ = (selector, context = document) => context.querySelector(selector);
    const $$ = (selector, context = document) => [...context.querySelectorAll(selector)];
    const dialog = $('#tl-dialog');
    const csrf = $('meta[name="csrf-token"]').content;
    let step = 0;
    let loadId = null;
    let lockVersion = 1;
    let courses = [];
    let dirty = false;
    let previewUrl = null;
    let loading = false;

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
        $$('#tl-dialog button').forEach(button => button.disabled = busy || (button.id === 'tl-finalize' && !$('#tl-confirm-final').checked));
        $('#tl-fields').disabled = busy;
    };

    const setStep = (value) => {
        step = Math.max(0, Math.min(2, value));
        $$('[data-step]').forEach(section => section.hidden = Number(section.dataset.step) !== step);
        $$('[data-step-label]').forEach(label => label.toggleAttribute('aria-current', Number(label.dataset.stepLabel) === step));
        $('#tl-back').hidden = step === 0;
        $('#tl-next').hidden = step === 2;
        $('#tl-finalize').hidden = step !== 2;
        $('#tl-scroll').scrollTop = 0;
        if (step === 2) updateSummary();
    };

    const reset = () => {
        loadId = null; lockVersion = 1; courses = []; dirty = false; clearError();
        $('#tl-dialog-title').textContent = 'Create Teacher’s Load';
        $('#tl-faculty').disabled = false; $('#tl-year').disabled = false; $('#tl-semester').disabled = false;
        $('#tl-faculty').value = ''; $('#tl-employment').value = 'Full-Time Faculty'; $('#tl-program').textContent = 'SITE / —';
        $('#tl-course-rows').replaceChildren(); $('#tl-duty-rows').replaceChildren(); $('#tl-course-notice').textContent = '';
        $('#tl-confirm-final').checked = false; $('#tl-finalize').disabled = true; $('#tl-save-state').textContent = 'Unsaved draft';
        if (previewUrl) URL.revokeObjectURL(previewUrl); previewUrl = null; $('#tl-pdf-frame').hidden = true; $('#tl-preview-link').hidden = true;
        setStep(0);
    };

    const close = (force = false) => {
        if (dirty && !force) { $('#tl-discard').hidden = false; return; }
        $('#tl-discard').hidden = true; dialog.close(); reset();
    };

    const loadCourses = async () => {
        const employee = $('#tl-faculty').value;
        const semester = $('#tl-semester').value;
        const program = $('#tl-faculty').selectedOptions[0]?.dataset.program || '—';
        $('#tl-program').textContent = `SITE / ${program}`;
        courses = [];
        if (!employee || !semester) { $('#tl-course-notice').textContent = 'Choose a faculty member and semester first.'; return; }
        const query = new URLSearchParams({ employee_id: employee, semester });
        const data = await api(`${root.dataset.options}?${query}`);
        courses = data.courses || [];
        if (!loadId && data.employment_status) $('#tl-employment').value = data.employment_status;
        $('#tl-course-notice').textContent = courses.length ? `${courses.length} assigned subject${courses.length === 1 ? '' : 's'} available.` : 'No assigned subjects are available for this faculty and semester.';
        $$('#tl-course-rows select[data-field="course_id"]').forEach(select => fillCourseSelect(select, select.value));
    };

    const fillCourseSelect = (select, selected = '') => {
        select.replaceChildren(new Option('Choose assigned subject', ''));
        courses.forEach(course => select.add(new Option(`${course.code} — ${course.title}`, course.id, false, String(course.id) === String(selected))));
    };

    const addSchedule = (item, values = {}) => {
        const node = $('#tl-schedule-template').content.firstElementChild.cloneNode(true);
        (values.days || []).forEach(day => { const input = $(`input[value="${day}"]`, node); if (input) input.checked = true; });
        ['start', 'end', 'mode', 'room'].forEach(key => { const input = $(`[data-meeting="${key}"]`, node); if (values[key] != null) input.value = values[key]; });
        $('.tl-remove-schedule', node).addEventListener('click', () => { node.remove(); markDirty(); updateSummary(); });
        $('.tl-schedules', item).append(node);
    };

    const addCourse = (values = {}) => {
        if (!courses.length && !values.course_id) { $('#tl-course-notice').textContent = 'No assigned subjects are available. Update the faculty course assignment first.'; return; }
        const item = $('#tl-course-template').content.firstElementChild.cloneNode(true);
        fillCourseSelect($('[data-field="course_id"]', item), values.course_id);
        ['section', 'class_size', 'lecture_units', 'lab_units', 'load_equivalent'].forEach(key => { if (values[key] != null) $(`[data-field="${key}"]`, item).value = values[key]; });
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

    const number = (value) => Number.parseFloat(value || 0) || 0;
    const readMeeting = meeting => ({
        days: $$('input[type="checkbox"]:checked', meeting).map(input => input.value),
        start: $('[data-meeting="start"]', meeting).value,
        end: $('[data-meeting="end"]', meeting).value,
        mode: $('[data-meeting="mode"]', meeting).value,
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
        return { employee_id: Number($('#tl-faculty').value), school_year_id: Number($('#tl-year').value), semester: $('#tl-semester').value, employment_status: $('#tl-employment').value, lock_version: lockVersion, items };
    };

    const updateSummary = () => {
        const data = payload();
        const teaching = data.items.filter(item => item.kind === 'course');
        const units = teaching.reduce((sum, item) => sum + item.lecture_units + item.lab_units, 0);
        const total = data.items.reduce((sum, item) => sum + item.load_equivalent, 0);
        $('#tl-units').textContent = units.toFixed(2).replace(/\.00$/, '');
        $('#tl-total').textContent = total.toFixed(3).replace(/0+$/, '').replace(/\.$/, '') || '0';
        $('#tl-review').textContent = `${teaching.length} teaching row${teaching.length === 1 ? '' : 's'} and ${data.items.length - teaching.length} additional dut${data.items.length - teaching.length === 1 ? 'y' : 'ies'} will appear on the PDF.`;
    };

    const markDirty = () => { if (!loading) { dirty = true; $('#tl-save-state').textContent = 'Unsaved changes'; } };

    const validateStep = () => {
        clearError();
        const section = $(`[data-step="${step}"]`);
        const invalid = $$('input,select', section).find(input => !input.checkValidity());
        if (invalid) { invalid.reportValidity(); return false; }
        if (step === 0 && (!$('#tl-faculty').value || !$('#tl-year').value)) return false;
        return true;
    };

    const save = async () => {
        clearError(); setBusy(true);
        try {
            const url = loadId ? `${root.dataset.base}/${loadId}` : root.dataset.base;
            const data = await api(url, { method: loadId ? 'PATCH' : 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload()) });
            loadId = data.id; lockVersion = data.lock_version; dirty = false; $('#tl-save-state').textContent = 'Draft saved';
            $('#tl-dialog-title').textContent = 'Edit Teacher’s Load'; $('#tl-faculty').disabled = $('#tl-year').disabled = $('#tl-semester').disabled = true;
            return true;
        } catch (error) { showError(error); return false; } finally { setBusy(false); }
    };

    const preview = async () => {
        clearError(); setBusy(true);
        try {
            const data = payload(); data.id = loadId;
            const response = await fetch(root.dataset.preview, { method: 'POST', credentials: 'same-origin', headers: { 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/pdf', 'Content-Type': 'application/json' }, body: JSON.stringify(data) });
            if (!response.ok) { const details = await response.json(); const error = new Error(details.message); error.errors = details.errors; throw error; }
            if (previewUrl) URL.revokeObjectURL(previewUrl); previewUrl = URL.createObjectURL(await response.blob());
            $('#tl-pdf-frame').src = previewUrl; $('#tl-pdf-frame').hidden = false; $('#tl-preview-link').href = previewUrl; $('#tl-preview-link').hidden = false; $('#tl-preview-note').textContent = 'Draft preview generated from the current form.';
        } catch (error) { showError(error); } finally { setBusy(false); }
    };

    const edit = async id => {
        reset(); dialog.showModal(); setBusy(true);
        try {
            const data = await api(`${root.dataset.base}/${id}`);
            loadId = data.id; lockVersion = data.lock_version; $('#tl-dialog-title').textContent = 'Edit Teacher’s Load';
            $('#tl-faculty').value = data.employee_id; $('#tl-year').value = data.school_year_id; $('#tl-semester').value = data.semester; $('#tl-employment').value = data.employment_status;
            $('#tl-faculty').disabled = $('#tl-year').disabled = $('#tl-semester').disabled = true;
            await loadCourses();
            data.items.filter(item => item.kind === 'course').forEach(addCourse); data.items.filter(item => item.kind === 'duty').forEach(addDuty);
            dirty = false; $('#tl-save-state').textContent = 'Draft saved';
        } catch (error) { showError(error); } finally { setBusy(false); }
    };

    $('#tl-create').addEventListener('click', () => { reset(); dialog.showModal(); });
    $$('[data-edit-load]').forEach(button => button.addEventListener('click', () => edit(button.dataset.editLoad)));
    $('#tl-close').addEventListener('click', () => close()); $('#tl-keep').addEventListener('click', () => $('#tl-discard').hidden = true); $('#tl-discard-confirm').addEventListener('click', () => close(true));
    dialog.addEventListener('cancel', event => { event.preventDefault(); close(); });
    $('#tl-faculty').addEventListener('change', async () => { try { await loadCourses(); markDirty(); } catch (error) { showError(error); } });
    $('#tl-semester').addEventListener('change', async () => { try { await loadCourses(); markDirty(); } catch (error) { showError(error); } });
    dialog.addEventListener('input', event => { if (event.target.id !== 'tl-confirm-final') markDirty(); updateSummary(); });
    $('#tl-add-course').addEventListener('click', () => { addCourse(); markDirty(); }); $('#tl-add-duty').addEventListener('click', () => { addDuty(); markDirty(); });
    $('#tl-back').addEventListener('click', () => setStep(step - 1)); $('#tl-next').addEventListener('click', () => { if (validateStep()) setStep(step + 1); });
    $('#tl-save').addEventListener('click', save); $('#tl-preview').addEventListener('click', preview);
    $('#tl-confirm-final').addEventListener('change', event => $('#tl-finalize').disabled = !event.target.checked);
    $('#tl-finalize').addEventListener('click', async () => {
        clearError(); if (!$('#tl-confirm-final').checked) return; if (dirty && !(await save())) return; setBusy(true);
        try { await api(`${root.dataset.base}/${loadId}/finalize`, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ lock_version: lockVersion }) }); window.location.reload(); }
        catch (error) { showError(error); setBusy(false); }
    });
}
