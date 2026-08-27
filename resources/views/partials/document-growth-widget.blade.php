{{-- Document Growth & Storage mini widget. Expects $growthOverview = ['growth' => ['labels'=>[], 'counts'=>[], 'max'=>int], 'storageUsed' => string, 'storageQuota' => ?string, 'storagePercent' => ?int] --}}
@php
    $labels = $growthOverview['growth']['labels'] ?? [];
    $counts = $growthOverview['growth']['counts'] ?? [];
    $max = $growthOverview['growth']['max'] ?? 1;
@endphp

<div class="content-card mb-4">
    <div class="flex justify-between items-center mb-3">
        <h3 class="card-title text-sm mb-0">
            <i class="fas fa-chart-column mr-2 text-[#028a0f]"></i>Document Growth
        </h3>
        <span class="text-xs text-gray-500 dark:text-gray-400">Last {{ count($labels) }} months</span>
    </div>

    <div class="flex items-end justify-between gap-2" style="height: 90px;">
        @foreach($labels as $i => $label)
            @php $pct = $max > 0 ? max(2, round(($counts[$i] / $max) * 100)) : 2; @endphp
            <div class="flex-1 flex flex-col items-center justify-end h-full" title="{{ $counts[$i] }} document(s) in {{ $label }}">
                <span class="text-[10px] text-gray-500 dark:text-gray-400 mb-1">{{ $counts[$i] }}</span>
                <div class="w-full bg-[#028a0f] dark:bg-[#04b012]" style="height: {{ $pct }}%; min-height: 2px;"></div>
                <span class="text-[10px] text-gray-500 dark:text-gray-400 mt-1">{{ $label }}</span>
            </div>
        @endforeach
    </div>

    <div class="mt-4 pt-3 border-t border-gray-200 dark:border-gray-700">
        <div class="flex justify-between items-center text-xs mb-1">
            <span class="text-gray-600 dark:text-gray-400">
                <i class="fas fa-hard-drive mr-1 text-[#028a0f]"></i>Storage Used
            </span>
            <span class="font-semibold">
                {{ $growthOverview['storageUsed'] ?? '0 B' }}
                @isset($growthOverview['storageQuota'])
                    <span class="text-gray-500 dark:text-gray-400 font-normal">/ {{ $growthOverview['storageQuota'] }}</span>
                @endisset
            </span>
        </div>
        @isset($growthOverview['storagePercent'])
            <div class="w-full bg-gray-200 dark:bg-gray-700" style="height: 6px;">
                <div class="bg-[#028a0f] dark:bg-[#04b012]" style="height: 6px; width: {{ $growthOverview['storagePercent'] }}%;"></div>
            </div>
        @endisset
    </div>
</div>
