{{-- Today's Calendar Events widget --}}
@php
    $events = $todayEvents ?? collect();
@endphp
<div class="content-card mb-4">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-calendar-day mr-2"></i> Today's Schedule
            <span class="text-xs font-normal text-gray-500 dark:text-gray-400 ml-2">{{ now()->format('l, M d') }}</span>
        </h3>
        <span class="badge badge-info">{{ $events->count() }} {{ \Illuminate\Support\Str::plural('Event', $events->count()) }}</span>
    </div>

    @if($events->isEmpty())
        <div class="text-center py-6 text-gray-500 dark:text-gray-400">
            <i class="fas fa-mug-hot text-3xl block mb-2 text-gray-300 dark:text-gray-600"></i>
            <p class="text-sm m-0">No events scheduled today. Enjoy a clear calendar.</p>
        </div>
    @else
        <div class="flex flex-col">
            @foreach($events as $event)
                @php
                    $isAllDay = (bool)($event->all_day ?? false);
                    $isOngoing = !$isAllDay && now()->between($event->start_datetime, $event->end_datetime);
                    $isPast = !$isAllDay && now()->greaterThan($event->end_datetime);
                    $accent = $isOngoing ? 'border-l-4 border-[#028a0f]' : ($isPast ? 'border-l-4 border-gray-300 dark:border-gray-600 opacity-70' : 'border-l-4 border-blue-400');
                    $typeColors = [
                        'Meeting'   => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
                        'Deadline'  => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
                        'Holiday'   => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
                        'Training'  => 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300',
                        'Personal'  => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
                    ];
                    $typeColor = $typeColors[$event->event_type] ?? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200';
                @endphp
                <div class="{{ $accent }} pl-3 py-2 mb-2 bg-gray-50 dark:bg-[#1e1e1e]">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="font-semibold text-sm text-gray-800 dark:text-gray-100">{{ $event->title }}</span>
                                <span class="text-[10px] font-semibold px-2 py-0.5 {{ $typeColor }}">{{ $event->event_type }}</span>
                                @if($isOngoing)
                                    <span class="text-[10px] font-bold px-2 py-0.5 bg-[#028a0f] text-white">NOW</span>
                                @endif
                            </div>
                            <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                                <i class="fas fa-clock mr-1"></i>
                                @if($isAllDay)
                                    All day
                                @else
                                    {{ $event->start_datetime->format('h:i A') }} – {{ $event->end_datetime->format('h:i A') }}
                                @endif
                                @if(!empty($event->location))
                                    <span class="ml-3"><i class="fas fa-location-dot mr-1"></i>{{ $event->location }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
