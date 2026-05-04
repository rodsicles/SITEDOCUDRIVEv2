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
    <div class="relative overflow-hidden rounded-lg border border-green-200 dark:border-green-800/40 bg-gradient-to-br from-green-50 to-white dark:from-[#1a2a1a] dark:to-[#141a14] p-5 mb-5 shadow-sm">
        <div class="absolute top-0 left-0 w-1.5 h-full bg-gradient-to-b from-[#02b815] to-[#028a0f]"></div>
        <div class="flex items-start gap-3 pl-2">
            <div class="flex-shrink-0 w-9 h-9 rounded-full bg-[#028a0f]/10 dark:bg-[#02b815]/15 flex items-center justify-center mt-0.5">
                <i class="fas fa-chart-line text-[#028a0f] dark:text-[#02b815] text-sm"></i>
            </div>
            <p class="flex-1 text-sm md:text-[0.95rem] leading-relaxed text-gray-800 dark:text-gray-100 mb-0">
                {!! $insight['narrative'] !!}
            </p>
        </div>
    </div>

    {{-- Recommendations --}}
    @if(!empty($insight['recommendations']))
    <div class="mt-4">
        <div class="flex items-center gap-2 mb-3">
            <div class="w-7 h-7 rounded-md bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center">
                <i class="fas fa-lightbulb text-amber-600 dark:text-amber-400 text-xs"></i>
            </div>
            <span class="text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300">
                Recommended Actions
            </span>
        </div>
        <div class="space-y-2">
            @foreach($insight['recommendations'] as $rec)
                <div class="flex items-start gap-3 p-3 rounded-lg border border-gray-200 dark:border-gray-700/60 bg-white dark:bg-[#1a1a1a] hover:border-[#028a0f] dark:hover:border-[#02b815] hover:shadow-sm transition-all">
                    <div class="flex-shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-[#028a0f]/10 dark:bg-[#02b815]/20 text-[#028a0f] dark:text-[#02b815] text-[0.65rem] font-bold">
                            {{ $loop->iteration }}
                        </span>
                    </div>
                    <p class="text-sm leading-relaxed text-gray-700 dark:text-gray-200 mb-0 flex-1">
                        {{ $rec }}
                    </p>
                </div>
            @endforeach
        </div>
    </div>
    @endif

    <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-[0.7rem] text-gray-400 dark:text-gray-500 mt-5 pt-3 border-t border-gray-100 dark:border-gray-800">
        <span class="inline-flex items-center gap-1">
            <i class="far fa-clock"></i>
            Generated {{ $insight['generated_at']->diffForHumans() }}
        </span>
        <span class="text-gray-300 dark:text-gray-700">•</span>
        <span class="inline-flex items-center gap-1">
            <i class="fas fa-sync-alt text-[10px]"></i>
            Refreshes hourly
        </span>
        <span class="text-gray-300 dark:text-gray-700">•</span>
        <span class="inline-flex items-center gap-1">
            <i class="fas fa-shield-alt text-[10px]"></i>
            Deterministic — derived from system records
        </span>
    </div>
</div>
@endif
