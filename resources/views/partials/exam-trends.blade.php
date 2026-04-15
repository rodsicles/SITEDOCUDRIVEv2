{{-- Exam & Certification Trends --}}
@if(isset($examTrends))
<div class="content-card mb-6">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-chart-line mr-2"></i>Exam & Certification Trends</h3>
        <button onclick="toggleExamTrends()" class="btn btn-sm bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
            <i id="examTrendsIcon" class="fas fa-chevron-up"></i>
            <span id="examTrendsText">Hide</span>
        </button>
    </div>

    <div id="examTrendsContent">
        {{-- PRC Board Exam Results --}}
        <div class="px-6 py-4">
            <h4 class="text-sm font-bold mb-3 text-gray-700 dark:text-gray-300">
                <i class="fas fa-university mr-1"></i> PRC Board Exam Results
            </h4>
            @if(count($examTrends['prc']) > 0)
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Batch</th>
                        <th>Exam Type</th>
                        <th>Passed</th>
                        <th>Total Examinees</th>
                        <th>Date Recorded</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($examTrends['prc'] as $batch)
                        @foreach($batch['results'] as $result)
                        <tr>
                            <td>{{ $batch['batch_label'] }}</td>
                            <td>{{ $result['exam_type'] }}</td>
                            <td><strong>{{ $result['passed'] }}</strong></td>
                            <td>{{ $result['total'] ?? '—' }}</td>
                            <td>{{ $batch['date'] }}</td>
                        </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
            @else
            <div class="text-center py-4 text-gray-500 dark:text-gray-400 text-sm">
                No PRC exam records yet.
            </div>
            @endif
        </div>

        {{-- IT Certification Passers --}}
        <div class="px-6 py-4" style="border-top: 1px solid #e0e0e0;">
            <h4 class="text-sm font-bold mb-3 text-gray-700 dark:text-gray-300">
                <i class="fas fa-certificate mr-1"></i> IT Certification Passers
            </h4>
            @if(count($examTrends['certifications']) > 0)
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Certification</th>
                        <th>Year / Period</th>
                        <th>Passed</th>
                        <th>Date Recorded</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($examTrends['certifications'] as $cert)
                    <tr>
                        <td>{{ $cert['exam_type'] }}</td>
                        <td>{{ $cert['batch_label'] }}</td>
                        <td><strong>{{ $cert['passed'] }}</strong></td>
                        <td>{{ $cert['date'] }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @else
            <div class="text-center py-4 text-gray-500 dark:text-gray-400 text-sm">
                No certification records yet.
            </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
    function toggleExamTrends() {
        const content = document.getElementById('examTrendsContent');
        const icon = document.getElementById('examTrendsIcon');
        const text = document.getElementById('examTrendsText');
        if (content.style.display === 'none') {
            content.style.display = '';
            icon.classList.replace('fa-chevron-down', 'fa-chevron-up');
            text.textContent = 'Hide';
        } else {
            content.style.display = 'none';
            icon.classList.replace('fa-chevron-up', 'fa-chevron-down');
            text.textContent = 'Show';
        }
    }
</script>
@endpush
@endif
