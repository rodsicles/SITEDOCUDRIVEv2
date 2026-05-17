{{-- Searchable ITE course subject picker (Information Technology) --}}
@php
    $pickerId = $pickerId ?? 'iteSubjectPicker';
    $subjects = \App\Support\IteSubjects::labels();
    $required = $required ?? true;
@endphp
<div class="form-group md:col-span-2" id="{{ $pickerId }}">
    <label class="form-label">Subject (ITE Course) @if($required)<span class="text-red-500">*</span>@endif</label>
    <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">Information Technology courses only. Search by code or title.</p>
    <input type="hidden" name="subject" id="{{ $pickerId }}Value" value="{{ old('subject') }}" @if($required) required @endif>
    <div class="relative">
        <input type="text" id="{{ $pickerId }}Search" class="form-control" placeholder="Search e.g. ITE108, Web Systems..." autocomplete="off" value="{{ old('subject') }}">
        <div id="{{ $pickerId }}Results" class="hidden absolute z-50 w-full mt-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 shadow-lg max-h-56 overflow-y-auto"></div>
    </div>
    @error('subject')<span class="text-red-500 text-xs block mt-1">{{ $message }}</span>@enderror
</div>

@push('scripts')
<script>
(function() {
    const pickerId = @json($pickerId);
    const subjects = @json($subjects);
    const searchInput = document.getElementById(pickerId + 'Search');
    const hiddenInput = document.getElementById(pickerId + 'Value');
    const resultsBox = document.getElementById(pickerId + 'Results');
    if (!searchInput || !hiddenInput || !resultsBox) return;

    function selectSubject(label) {
        hiddenInput.value = label;
        searchInput.value = label;
        resultsBox.classList.add('hidden');
    }

    function renderResults(term) {
        const q = (term || '').toLowerCase().trim();
        resultsBox.innerHTML = '';
        const matches = subjects.filter(s => !q || s.toLowerCase().includes(q)).slice(0, 30);
        matches.forEach(label => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'w-full text-left px-3 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-700 border-b border-gray-100 dark:border-gray-700';
            btn.textContent = label;
            btn.addEventListener('click', () => selectSubject(label));
            resultsBox.appendChild(btn);
        });
        resultsBox.classList.toggle('hidden', matches.length === 0);
    }

    searchInput.addEventListener('input', () => {
        hiddenInput.value = '';
        renderResults(searchInput.value);
    });
    searchInput.addEventListener('focus', () => renderResults(searchInput.value));

    document.addEventListener('click', (e) => {
        if (!e.target.closest('#' + pickerId)) {
            resultsBox.classList.add('hidden');
        }
    });

    if (hiddenInput.value) {
        searchInput.value = hiddenInput.value;
    }

    window[pickerId + 'Validate'] = function() {
        if (!hiddenInput.value || !subjects.includes(hiddenInput.value)) {
            alert('Please select a valid ITE subject from the list.');
            return false;
        }
        return true;
    };
})();
</script>
@endpush
