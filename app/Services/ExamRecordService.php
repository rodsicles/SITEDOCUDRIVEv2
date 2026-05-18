<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DashboardLog;
use App\Models\ExamRecord;
use App\Models\Folder;
use Illuminate\Support\Facades\Cache;

class ExamRecordService
{
    public function __construct(
        protected WordDocumentService $wordService
    ) {}

    public function storePrcResults(array $data, int $userId): int
    {
        $prcFolder = Folder::where('slug', ExamRecord::PRC_FOLDER_SLUG)->first();
        if (!$prcFolder) {
            throw new \RuntimeException('PRC Results folder not found.');
        }

        // Parse passer names from newline-separated strings
        $ceNames = $this->parseNames($data['ce_names'] ?? '');
        $eseNames = $this->parseNames($data['ese_names'] ?? '');

        // Build exam data array
        $examData = [
            [
                'exam_type' => 'Civil Engineer',
                'passed_count' => $data['ce_passed'],
                'total_examinees' => $data['ce_total'] ?? null,
                'passer_names' => $ceNames,
            ],
            [
                'exam_type' => 'Environmental Sanitary Engineering',
                'passed_count' => $data['ese_passed'],
                'total_examinees' => $data['ese_total'] ?? null,
                'passer_names' => $eseNames,
            ],
        ];

        // Get recorder name
        $user = \App\Models\User::with('employee')->find($userId);
        $recorderName = $user->employee->full_name ?? $user->username;

        // Generate Word document
        $filePath = $this->wordService->generatePrcResultsDoc($examData, $data['batch_label'], $recorderName);

        // Create document record
        $document = Document::create([
            'uploaded_by' => $userId,
            'folder_id' => $prcFolder->folder_id,
            'document_title' => 'PRC Results - ' . $data['batch_label'],
            'file_path' => $filePath,
            'document_type' => 'word',
            'category' => $prcFolder->top_level_category ?? 'Accreditation and Certifications',
            'tags' => 'prc,exam-results,' . $data['batch_label'],
        ]);

        // Save exam records
        foreach ($examData as $exam) {
            ExamRecord::create([
                'folder_id' => $prcFolder->folder_id,
                'exam_type' => $exam['exam_type'],
                'batch_label' => $data['batch_label'],
                'passed_count' => $exam['passed_count'],
                'passer_names' => !empty($exam['passer_names']) ? $exam['passer_names'] : null,
                'total_examinees' => $exam['total_examinees'],
                'recorded_by' => $userId,
                'document_id' => $document->document_id,
            ]);
        }

        DashboardLog::create([
            'user_id' => $userId,
            'activity' => 'Recorded PRC exam results for batch: ' . $data['batch_label'],
            'activity_type' => 'exam_record',
            'visibility' => 'all',
        ]);

        $this->clearTrendsCache();

        return $document->document_id;
    }

    public function storeCertificationCount(int $folderId, array $data, int $userId): ExamRecord
    {
        $folder = Folder::findOrFail($folderId);
        $certName = $folder->folder_name;

        // Parse passer names from newline-separated string
        $names = $this->parseNames($data['passer_names'] ?? '');

        // Get recorder name (used in the generated document header)
        $user = \App\Models\User::with('employee')->find($userId);
        $recorderName = $user->employee->full_name ?? $user->username;

        // Generate Word + PDF document with the same SPUP letterhead format as PRC results
        $filePath = $this->wordService->generateCertResultsDoc(
            $certName,
            $data['batch_label'],
            (int) $data['passed_count'],
            $names,
            $recorderName
        );

        // Create document record so the file appears in the certification folder
        $document = Document::create([
            'uploaded_by'    => $userId,
            'folder_id'      => $folderId,
            'document_title' => $certName . ' Passers - ' . $data['batch_label'],
            'file_path'      => $filePath,
            'document_type'  => 'word',
            'category'       => $folder->top_level_category ?? 'Accreditation and Certifications',
            'tags'           => 'certification,' . str_replace(' ', '-', strtolower($certName)) . ',' . $data['batch_label'],
        ]);

        $record = ExamRecord::create([
            'folder_id' => $folderId,
            'exam_type' => $certName,
            'batch_label' => $data['batch_label'],
            'passed_count' => $data['passed_count'],
            'passer_names' => !empty($names) ? $names : null,
            'total_examinees' => null,
            'recorded_by' => $userId,
            'document_id' => $document->document_id,
        ]);

        DashboardLog::create([
            'user_id' => $userId,
            'activity' => "Recorded {$certName} certification passers: {$data['passed_count']} for {$data['batch_label']}",
            'activity_type' => 'cert_record',
            'visibility' => 'all',
        ]);

        $this->clearTrendsCache();

        return $record;
    }

    public function getFolderExamRecords(int $folderId, int $limit = 20)
    {
        return ExamRecord::where('folder_id', $folderId)
            ->with('recorder.employee')
            ->orderBy('created_at', 'desc')
            ->take($limit)
            ->get();
    }

    public function getTrends(): array
    {
        return Cache::remember('exam_trends', now()->addMinutes(10), function () {
            $prcTrends = ExamRecord::prc()
                ->select('batch_label', 'exam_type', 'passed_count', 'total_examinees', 'created_at')
                ->orderBy('created_at', 'desc')
                ->take(20)
                ->get()
                ->groupBy('batch_label')
                ->map(function ($group) {
                    return [
                        'batch_label' => $group->first()->batch_label,
                        'date' => $group->first()->created_at->format('M d, Y'),
                        'results' => $group->map(fn($r) => [
                            'exam_type' => $r->exam_type,
                            'passed' => $r->passed_count,
                            'total' => $r->total_examinees,
                        ])->values()->toArray(),
                    ];
                })
                ->values()
                ->toArray();

            $certTrends = ExamRecord::certification()
                ->select('exam_type', 'batch_label', 'passed_count', 'created_at')
                ->orderBy('created_at', 'desc')
                ->take(20)
                ->get()
                ->map(fn($r) => [
                    'exam_type' => $r->exam_type,
                    'batch_label' => $r->batch_label,
                    'passed' => $r->passed_count,
                    'date' => $r->created_at->format('M d, Y'),
                ])
                ->toArray();

            // Use placeholder data when no real records exist yet
            if (empty($prcTrends)) {
                $prcTrends = [
                    [
                        'batch_label' => 'Batch 2025',
                        'date' => 'Jan 15, 2025',
                        'results' => [
                            ['exam_type' => 'Civil Engineer', 'passed' => 8, 'total' => 12],
                            ['exam_type' => 'Environmental Sanitary Engineering', 'passed' => 5, 'total' => 9],
                        ],
                    ],
                    [
                        'batch_label' => 'Batch 2024',
                        'date' => 'Jan 20, 2024',
                        'results' => [
                            ['exam_type' => 'Civil Engineer', 'passed' => 6, 'total' => 10],
                            ['exam_type' => 'Environmental Sanitary Engineering', 'passed' => 4, 'total' => 7],
                        ],
                    ],
                    [
                        'batch_label' => 'Batch 2023',
                        'date' => 'Feb 10, 2023',
                        'results' => [
                            ['exam_type' => 'Civil Engineer', 'passed' => 7, 'total' => 11],
                            ['exam_type' => 'Environmental Sanitary Engineering', 'passed' => 3, 'total' => 6],
                        ],
                    ],
                ];
            }

            if (empty($certTrends)) {
                $certTrends = [
                    ['exam_type' => 'Cybersecurity', 'batch_label' => '2025', 'passed' => 10, 'date' => 'Mar 5, 2025'],
                    ['exam_type' => 'Cybersecurity', 'batch_label' => '2024', 'passed' => 7, 'date' => 'Mar 8, 2024'],
                    ['exam_type' => 'Cybersecurity', 'batch_label' => '2023', 'passed' => 5, 'date' => 'Apr 2, 2023'],
                    ['exam_type' => 'Networking', 'batch_label' => '2025', 'passed' => 9, 'date' => 'Mar 5, 2025'],
                    ['exam_type' => 'Networking', 'batch_label' => '2024', 'passed' => 6, 'date' => 'Mar 8, 2024'],
                    ['exam_type' => 'Networking', 'batch_label' => '2023', 'passed' => 4, 'date' => 'Apr 2, 2023'],
                    ['exam_type' => 'HTML & CSS', 'batch_label' => '2025', 'passed' => 12, 'date' => 'Mar 5, 2025'],
                    ['exam_type' => 'HTML & CSS', 'batch_label' => '2024', 'passed' => 8, 'date' => 'Mar 8, 2024'],
                    ['exam_type' => 'HTML & CSS', 'batch_label' => '2023', 'passed' => 6, 'date' => 'Apr 2, 2023'],
                ];
            }

            return [
                'prc' => $prcTrends,
                'certifications' => $certTrends,
            ];
        });
    }

    protected function clearTrendsCache(): void
    {
        Cache::forget('exam_trends');
    }

    protected function parseNames(string $text): array
    {
        if (empty($text)) {
            return [];
        }

        return array_values(array_filter(
            array_map('trim', preg_split('/\r\n|\r|\n/', $text)),
            fn($name) => $name !== ''
        ));
    }
}
