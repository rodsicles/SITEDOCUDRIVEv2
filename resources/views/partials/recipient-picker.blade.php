{{-- Multi-select recipient picker with search (dean/coordinator shared uploads) --}}
@php
    $pickerId = $pickerId ?? 'recipientPicker';
    $searchUrl = $searchUrl ?? route($role . '.documents.recipient-search');
@endphp
<div class="form-group md:col-span-2" id="{{ $pickerId }}">
    <label class="form-label">Send to Faculty / Coordinators *</label>
    <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">Search and select who can view and download this upload. They will also see it under Teaching Guides.</p>
    <div class="relative mb-2">
        <input type="text" id="{{ $pickerId }}Search" class="form-control" placeholder="Search by name or email..." autocomplete="off">
        <div id="{{ $pickerId }}Results" class="hidden absolute z-50 w-full mt-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 shadow-lg max-h-48 overflow-y-auto"></div>
    </div>
    <div id="{{ $pickerId }}Selected" class="flex flex-wrap gap-2 min-h-[2rem]"></div>
    <div id="{{ $pickerId }}Hidden"></div>
    @error('recipient_ids')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
    @error('recipient_ids.*')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
</div>

@push('scripts')
<script>
(function() {
    const pickerId = @json($pickerId);
    const searchUrl = @json($searchUrl);
    const searchInput = document.getElementById(pickerId + 'Search');
    const resultsBox = document.getElementById(pickerId + 'Results');
    const selectedBox = document.getElementById(pickerId + 'Selected');
    const hiddenBox = document.getElementById(pickerId + 'Hidden');
    if (!searchInput || !resultsBox) return;

    const selected = new Map();
    let debounceTimer = null;

    function renderSelected() {
        selectedBox.innerHTML = '';
        hiddenBox.innerHTML = '';
        selected.forEach((user) => {
            const chip = document.createElement('span');
            chip.className = 'inline-flex items-center gap-1 px-2 py-1 text-xs bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-100 rounded';
            chip.innerHTML = `${user.name} <button type="button" class="ml-1 text-red-600" data-id="${user.id}">&times;</button>`;
            chip.querySelector('button').addEventListener('click', () => {
                selected.delete(user.id);
                renderSelected();
            });
            selectedBox.appendChild(chip);

            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'recipient_ids[]';
            input.value = user.id;
            hiddenBox.appendChild(input);
        });
    }

    function addUser(user) {
        selected.set(user.id, user);
        renderSelected();
        searchInput.value = '';
        resultsBox.classList.add('hidden');
    }

    async function runSearch() {
        const q = searchInput.value.trim();
        const url = new URL(searchUrl, window.location.origin);
        url.searchParams.set('q', q);
        try {
            const res = await fetch(url, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await res.json();
            resultsBox.innerHTML = '';
            (data.results || []).forEach(user => {
                if (selected.has(user.id)) return;
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'w-full text-left px-3 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-700 border-b border-gray-100 dark:border-gray-700';
                btn.innerHTML = `<strong>${user.name}</strong><br><span class="text-xs text-gray-500">${user.email} · ${user.role}</span>`;
                btn.addEventListener('click', () => addUser(user));
                resultsBox.appendChild(btn);
            });
            resultsBox.classList.toggle('hidden', resultsBox.children.length === 0);
        } catch (e) {
            console.error('Recipient search failed', e);
        }
    }

    searchInput.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(runSearch, 250);
    });
    searchInput.addEventListener('focus', runSearch);

    document.addEventListener('click', (e) => {
        if (!e.target.closest('#' + pickerId)) {
            resultsBox.classList.add('hidden');
        }
    });

    window[pickerId + 'Validate'] = function() {
        if (selected.size === 0) {
            alert('Please select at least one faculty member or coordinator to receive this upload.');
            return false;
        }
        return true;
    };
})();
</script>
@endpush
