{{--
    Inline search + sort toolbar (left-aligned beside section title).
    @param string $action Form GET action URL
    @param string $search Current search query
    @param string $sort Current sort key
    @param array $hiddenFields Extra hidden inputs [name => value]
--}}
@props([
    'action',
    'search' => '',
    'sort' => 'date_desc',
    'searchPlaceholder' => 'Search...',
    'hiddenFields' => [],
    'showSort' => true,
])

<form action="{{ $action }}" method="GET" class="resource-list-toolbar">
    @foreach($hiddenFields as $name => $value)
        @if($value !== null && $value !== '')
            <input type="hidden" name="{{ $name }}" value="{{ $value }}">
        @endif
    @endforeach
    <input type="text"
           name="search"
           value="{{ $search }}"
           class="form-control resource-list-toolbar__search"
           placeholder="{{ $searchPlaceholder }}"
           autocomplete="off">
    <button type="submit" class="btn btn-primary resource-list-toolbar__search-btn" title="Search" aria-label="Search">
        <i class="fas fa-search"></i>
    </button>
    @if($showSort)
    <label class="resource-list-toolbar__sort-label">
        <i class="fas fa-sort" aria-hidden="true"></i>
        <span class="sr-only">Sort</span>
        <select name="sort" class="form-control resource-list-toolbar__sort" onchange="this.form.submit()">
            <option value="date_desc" @selected($sort === 'date_desc')>Newest first</option>
            <option value="date_asc" @selected($sort === 'date_asc')>Oldest first</option>
            <option value="title_asc" @selected($sort === 'title_asc')>Title A–Z</option>
            <option value="title_desc" @selected($sort === 'title_desc')>Title Z–A</option>
        </select>
    </label>
    @endif
    @if($search || ($sort && $sort !== 'date_desc'))
        <a href="{{ $action }}{{ !empty($hiddenFields) ? '?' . http_build_query(array_filter($hiddenFields, fn ($v) => $v !== null && $v !== '')) : '' }}"
           class="btn bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 resource-list-toolbar__clear"
           title="Clear search and sort">
            <i class="fas fa-times"></i>
        </a>
    @endif
</form>
