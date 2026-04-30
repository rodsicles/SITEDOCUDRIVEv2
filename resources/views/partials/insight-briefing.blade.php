{{--
    Weekly Insight Briefing
    Auto-generated narrative analytics card. All figures are computed
    from real data by App\Services\WeeklyInsightService.
--}}
@if(!empty($insight))
<div class="content-card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-brain mr-2 text-[#028a0f] dark:text-[#02b815]"></i>
            Weekly Insight Briefing
            <span class="badge badge-info ml-2">{{ $insight['period_label'] }}</span>
        </h3>
        <form method="POST" action="{{ route('dean.insight.refresh') }}" class="m-0">
            @csrf
            <button type="submit"
                    class="btn btn-secondary border-0"
                    title="Recompute the briefing from the latest data">
                <i class="fas fa-sync-alt mr-1"></i> Refresh
            </button>
        </form>
    </div>

    {{-- Highlight chips --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-5">
        @foreach($insight['highlights'] as $chip)
            @php
                $toneClasses = match($chip['tone']) {
                    'positive' => 'border-green-200 bg-green-50 text-green-800 dark:border-green-800 dark:bg-green-900/20 dark:text-green-300',
                    'warning'  => 'border-orange-200 bg-orange-50 text-orange-800 dark:border-orange-800 dark:bg-orange-900/20 dark:text-orange-300',
                    'negative' => 'border-red-200 bg-red-50 text-red-800 dark:border-red-800 dark:bg-red-900/20 dark:text-red-300',
                    default    => 'border-gray-200 bg-gray-50 text-gray-700 dark:border-gray-700 dark:bg-[#1e1e1e] dark:text-gray-300',
                };
            @endphp
            <div class="border {{ $toneClasses }} p-3">
                <div class="text-[0.65rem] uppercase tracking-wider font-semibold opacity-75">{{ $chip['label'] }}</div>
                <div class="text-2xl font-bold mt-1">{{ $chip['value'] }}</div>
            </div>
        @endforeach
    </div>

    {{-- Narrative paragraph --}}
    <div class="border-l-4 border-[#028a0f] dark:border-[#02b815] bg-green-50 dark:bg-[#1a2a1a] p-4 mb-4">
        <p class="text-sm leading-relaxed text-gray-800 dark:text-gray-200 mb-0">
            {!! $insight['narrative'] !!}
        </p>
    </div>

    {{-- Recommendations --}}
    @if(!empty($insight['recommendations']))
    <div class="mt-3">
        <div class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">
            <i class="fas fa-lightbulb mr-1"></i> Recommended Actions
        </div>
        <ul class="text-sm text-gray-700 dark:text-gray-300 pl-5 mb-0" style="list-style: disc;">
            @foreach($insight['recommendations'] as $rec)
                <li class="mb-1">{{ $rec }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <div class="text-[0.7rem] text-gray-400 dark:text-gray-500 mt-4">
        <i class="far fa-clock mr-0.5"></i>
        Generated {{ $insight['generated_at']->diffForHumans() }} ·
        Data refreshes hourly · All figures derived from system records (deterministic, no AI inference)
    </div>
</div>
@endif
