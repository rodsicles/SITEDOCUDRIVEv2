@php
    $insights = collect($operationalInsights ?? [])->take(3);
    $icons = ['urgent'=>'fa-circle-exclamation','attention'=>'fa-clock','clear'=>'fa-circle-check','trend'=>'fa-arrow-trend-up','positive'=>'fa-award'];
    $labels = ['urgent'=>'Priority','attention'=>'In review','clear'=>'Current status','trend'=>'7-day trend','positive'=>'Useful signal'];
@endphp

<section class="operational-insights" aria-labelledby="operationalInsightsTitle">
    <div class="operational-insights__heading">
        <div>
            <h3 id="operationalInsightsTitle">Operational Insights</h3>
            <p>Key changes and items worth your attention</p>
        </div>
        <span>Updated {{ now()->format('g:i A') }}</span>
    </div>
    <div class="operational-insights__list">
        @foreach($insights as $insight)
        <article class="operational-insight operational-insight--{{ $insight['tone'] }}">
            <div class="operational-insight__icon"><i class="fas {{ $icons[$insight['tone']] ?? 'fa-circle-info' }}" aria-hidden="true"></i></div>
            <div class="operational-insight__copy">
                <span class="operational-insight__label">{{ $labels[$insight['tone']] ?? 'Insight' }}</span>
                <strong>{{ $insight['title'] }}</strong>
                <p>{{ $insight['detail'] }}</p>
            </div>
            <a href="{{ $insight['url'] }}">{{ $insight['action'] }} <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
        </article>
        @endforeach
    </div>
</section>
