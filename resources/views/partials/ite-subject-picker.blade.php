{{-- Searchable ITE course subject picker (Information Technology) --}}
@php
    $pickerId = $pickerId ?? 'iteSubjectPicker';
    $subjects = $subjects ?? \App\Support\IteSubjects::labelsForUser(auth()->user());
    $required = $required ?? true;
    $label = $label ?? 'Subject (ITE Course)';
    $compact = $compact ?? false;
@endphp
<div class="form-group {{ $compact ? '' : 'md:col-span-2' }} ite-subject-picker" id="{{ $pickerId }}">
    <label class="form-label" for="{{ $pickerId }}Search">{{ $label }} @if($required)<span class="text-red-500">*</span>@endif</label>
    <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">Information Technology courses only. Search by code or title.</p>
    <input type="hidden" name="subject" id="{{ $pickerId }}Value" value="{{ old('subject') }}" @if($required) required @endif>
    <div class="ite-subject-picker-field relative">
        <input
            type="text"
            id="{{ $pickerId }}Search"
            class="form-control ite-subject-search"
            placeholder="Search e.g. ITE108, Web Systems..."
            autocomplete="off"
            value="{{ old('subject') }}"
            aria-autocomplete="list"
            aria-controls="{{ $pickerId }}Results"
            aria-expanded="false"
        >
        <div
            id="{{ $pickerId }}Results"
            class="ite-subject-results hidden"
            role="listbox"
            aria-label="ITE courses"
        ></div>
    </div>
    @error('subject')<span class="text-red-500 text-xs block mt-1">{{ $message }}</span>@enderror
    @error('document_title')<span class="text-red-500 text-xs block mt-1">{{ $message }}</span>@enderror
</div>

@push('scripts')
<script>
(function() {
    const pickerId = @json($pickerId);
    const subjects = @json($subjects);
    const root = document.getElementById(pickerId);
    const searchInput = document.getElementById(pickerId + 'Search');
    const hiddenInput = document.getElementById(pickerId + 'Value');
    const resultsBox = document.getElementById(pickerId + 'Results');
    if (!root || !searchInput || !hiddenInput || !resultsBox) return;

    let activeIndex = -1;

    function setExpanded(open) {
        searchInput.setAttribute('aria-expanded', open ? 'true' : 'false');
    }

    function selectSubject(label) {
        hiddenInput.value = label;
        searchInput.value = label;
        resultsBox.classList.add('hidden');
        setExpanded(false);
        activeIndex = -1;
    }

    function renderResults(term) {
        const q = (term || '').toLowerCase().trim();
        resultsBox.innerHTML = '';
        activeIndex = -1;
        const matches = subjects.filter(s => !q || s.toLowerCase().includes(q));

        matches.forEach((label, index) => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'ite-subject-option';
            btn.setAttribute('role', 'option');
            btn.dataset.index = String(index);
            btn.textContent = label;
            btn.addEventListener('mousedown', (e) => e.preventDefault());
            btn.addEventListener('click', () => selectSubject(label));
            btn.addEventListener('mouseenter', () => setActiveOption(index));
            resultsBox.appendChild(btn);
        });

        const show = matches.length > 0;
        resultsBox.classList.toggle('hidden', !show);
        setExpanded(show);
    }

    function setActiveOption(index) {
        activeIndex = index;
        resultsBox.querySelectorAll('.ite-subject-option').forEach((el, i) => {
            el.classList.toggle('is-active', i === index);
        });
    }

    searchInput.addEventListener('input', () => {
        hiddenInput.value = '';
        renderResults(searchInput.value);
    });

    searchInput.addEventListener('focus', () => renderResults(searchInput.value));

    searchInput.addEventListener('keydown', (e) => {
        const options = resultsBox.querySelectorAll('.ite-subject-option');
        if (!options.length) return;

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            const next = activeIndex < options.length - 1 ? activeIndex + 1 : 0;
            setActiveOption(next);
            options[next].scrollIntoView({ block: 'nearest' });
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            const prev = activeIndex > 0 ? activeIndex - 1 : options.length - 1;
            setActiveOption(prev);
            options[prev].scrollIntoView({ block: 'nearest' });
        } else if (e.key === 'Enter' && activeIndex >= 0) {
            e.preventDefault();
            selectSubject(options[activeIndex].textContent);
        } else if (e.key === 'Escape') {
            resultsBox.classList.add('hidden');
            setExpanded(false);
        }
    });

    document.addEventListener('click', (e) => {
        if (!e.target.closest('#' + pickerId)) {
            resultsBox.classList.add('hidden');
            setExpanded(false);
        }
    });

    if (hiddenInput.value) {
        searchInput.value = hiddenInput.value;
    }

    window[pickerId + 'Validate'] = function() {
        if (!hiddenInput.value || !subjects.includes(hiddenInput.value)) {
            alert('Please select a valid ITE subject from the list.');
            searchInput.focus();
            return false;
        }
        return true;
    };
})();
</script>
@endpush
