{{-- Exam & Certification Trends - Compact Design with Mini Bars --}}
@if(isset($examTrends))
@php
    // Calculate totals
    $prcByBatch = [];
    foreach ($examTrends['prc'] as $batch) {
        $batchTotal = 0;
        foreach ($batch['results'] as $r) $batchTotal += $r['passed'];
        $prcByBatch[] = ['label' => $batch['batch_label'], 'passed' => $batchTotal];
    }
    $certByType = [];
    foreach ($examTrends['certifications'] as $c) {
        if (!isset($certByType[$c['exam_type']])) $certByType[$c['exam_type']] = [];
        $certByType[$c['exam_type']][] = ['year' => $c['batch_label'], 'passed' => $c['passed']];
    }
    $maxPrc = max(array_column($prcByBatch, 'passed') ?: [1]);
    $allCertValues = [];
    foreach ($certByType as $vals) foreach ($vals as $v) $allCertValues[] = $v['passed'];
    $maxCert = max($allCertValues ?: [1]);
@endphp
<div class="content-card mb-6">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-chart-line mr-2"></i>Passers and Results and Certifications</h3>
        <span class="badge badge-success">Exam & Certification</span>
    </div>

    <div class="trends-compact-grid">
        {{-- PRC Board Exam --}}
        <div class="trends-panel">
            <div class="trends-panel-header">
                <span class="trends-panel-title"><i class="fas fa-university mr-1"></i> PRC Board Exam</span>
            </div>
            @if(count($prcByBatch) > 0)
            <div class="trends-bars">
                @foreach($prcByBatch as $b)
                <div class="trends-bar-row">
                    <span class="trends-bar-label">{{ $b['label'] }}</span>
                    <div class="trends-bar-track">
                        <div class="trends-bar-fill" style="width: {{ ($b['passed'] / $maxPrc) * 100 }}%"></div>
                    </div>
                    <span class="trends-bar-value">{{ $b['passed'] }}</span>
                </div>
                @endforeach
            </div>
            {{-- Breakdown --}}
            <div class="trends-breakdown">
                @foreach($examTrends['prc'] as $batch)
                <div class="trends-breakdown-row">
                    <span class="trends-breakdown-batch">{{ $batch['batch_label'] }}</span>
                    @foreach($batch['results'] as $result)
                    <span class="trends-breakdown-item">
                        {{ $result['exam_type'] === 'Civil Engineer' ? 'CE' : 'ESE' }}:
                        <strong>{{ $result['passed'] }}</strong>{{ $result['total'] ? '/' . $result['total'] : '' }}
                    </span>
                    @endforeach
                </div>
                @endforeach
            </div>
            @else
            <div class="text-center py-3 text-gray-400 text-xs">No PRC records yet</div>
            @endif
        </div>

        {{-- IT Certifications --}}
        @foreach($certByType as $certName => $years)
        <div class="trends-panel">
            <div class="trends-panel-header">
                <span class="trends-panel-title"><i class="fas fa-certificate mr-1"></i> {{ $certName }}</span>
            </div>
            <div class="trends-bars">
                @foreach($years as $y)
                <div class="trends-bar-row">
                    <span class="trends-bar-label">{{ $y['year'] }}</span>
                    <div class="trends-bar-track">
                        <div class="trends-bar-fill" style="width: {{ ($y['passed'] / $maxCert) * 100 }}%"></div>
                    </div>
                    <span class="trends-bar-value">{{ $y['passed'] }}</span>
                </div>
                @endforeach
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif
