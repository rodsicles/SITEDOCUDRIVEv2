@php $programFull = $program ? (\App\Models\Program::OPTIONS[$program] ?? null) : null; @endphp
@if($programFull)<span class="program-name" title="{{ $programFull }}"><span class="program-name__full">{{ $programFull }}</span><span class="program-name__code">{{ $program }}</span></span>@else{{ $program ?: 'N/A' }}@endif
