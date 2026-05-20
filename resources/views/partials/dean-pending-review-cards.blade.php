{{-- Dean dashboard: quick access to pending teaching guides & exam questionnaires --}}
@php
    $tgCount = $pendingTeachingGuidesCount ?? 0;
    $eqCount = $pendingExamQuestionnairesCount ?? 0;
@endphp
<div class="dean-pending-review-grid mb-6">
    <a href="{{ route('dean.teaching-guides.index', ['status' => 'pending']) }}"
       class="content-card dean-pending-review-card dean-pending-review-card--tg no-underline hover:shadow-md transition-shadow">
        <div class="p-4 flex items-start gap-4">
            <div class="dean-pending-review-card__icon">
                <i class="fas fa-book-open"></i>
            </div>
            <div class="min-w-0 flex-1">
                <h3 class="card-title text-base mb-1">Pending Teaching Guides</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-0">Review and approve faculty teaching guide uploads.</p>
            </div>
            @if($tgCount > 0)
            <span class="badge badge-danger shrink-0">{{ $tgCount }}</span>
            @else
            <span class="badge badge-neutral shrink-0">0</span>
            @endif
        </div>
    </a>

    <a href="{{ route('dean.exam-questionnaires.index', ['status' => 'pending']) }}"
       class="content-card dean-pending-review-card dean-pending-review-card--eq no-underline hover:shadow-md transition-shadow">
        <div class="p-4 flex items-start gap-4">
            <div class="dean-pending-review-card__icon">
                <i class="fas fa-file-alt"></i>
            </div>
            <div class="min-w-0 flex-1">
                <h3 class="card-title text-base mb-1">Pending Exam Questionnaires</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-0">Review TOS/TOQ and exam files awaiting approval.</p>
            </div>
            @if($eqCount > 0)
            <span class="badge badge-danger shrink-0">{{ $eqCount }}</span>
            @else
            <span class="badge badge-neutral shrink-0">0</span>
            @endif
        </div>
    </a>
</div>
