<div class="teaching-summary">
    <div class="teaching-summary__head">
        <div>
            <h3 id="{{ $headingId }}">Current teaching assignments</h3>
            <p>{{ $assignments['year']?->name ?? 'Current assignments' }} &middot; {{ \App\Support\SchoolTerm::label($assignments['semester']) }}</p>
        </div>
        @if(!empty($manageSubjectsUrl))
            <a class="btn btn-secondary btn-sm" href="{{ $manageSubjectsUrl }}">Manage subjects</a>
        @endif
        @if(!empty($loadUrl))
            <a class="btn btn-secondary btn-sm" href="{{ $loadUrl }}">Teacher's Load</a>
        @endif
    </div>
    @if($assignments['rows']->isEmpty())
        <p class="teaching-summary__note">No subjects assigned for this semester. The Dean or Program Coordinator can update subject assignments.</p>
    @else
        <ul class="teaching-summary__list">
            @foreach($assignments['rows'] as $row)
                <li>
                    <div class="teaching-summary__subject"><strong>{{ $row['code'] }}</strong><span>{{ $row['title'] }}</span></div>
                    @if($row['section'])<span class="teaching-summary__section">Section {{ $row['section'] }}</span>@endif
                    @foreach($row['schedules'] as $schedule)
                        <p class="teaching-summary__schedule">
                            {{ implode(', ', $schedule['days']) }} &middot;
                            {{ \Carbon\Carbon::createFromFormat('H:i', $schedule['start'])->format('g:i A') }}&ndash;{{ \Carbon\Carbon::createFromFormat('H:i', $schedule['end'])->format('g:i A') }}
                            &middot; {{ $schedule['mode'] }} &middot; {{ $schedule['room'] }}
                        </p>
                    @endforeach
                </li>
            @endforeach
        </ul>
        <p class="teaching-summary__note">{{ $assignments['has_schedules'] ? "Schedules come from the finalized Teacher's Load. Assigned subjects without a published schedule are listed by name only." : "Schedules will appear when the Dean or Program Coordinator finalizes a Teacher's Load. Your existing subject assignments are already listed above." }}</p>
    @endif
</div>
