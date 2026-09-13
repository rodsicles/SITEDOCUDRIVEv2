<?php

namespace App\Services;

use App\Models\DashboardLog;
use App\Models\Report;
use App\Models\User;
use App\Support\UploadStorage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class ReportService
{
    public function __construct(
        protected NotificationService $notificationService,
        protected StorageQuotaService $quotaService,
    ) {}

    public function listForUser(int $userId)
    {
        return Report::query()
            ->where('submitted_by', $userId)
            ->latest('created_at')
            ->paginate(15);
    }

    public function create(array $validated, UploadedFile $file, User $user): Report
    {
        $needed = $this->quotaService->sumUploadedSizes([$file]);
        if (!$this->quotaService->hasQuotaForBytes($user->id, $needed)) {
            throw new \RuntimeException(
                'Storage quota exceeded (limit: ' . $this->quotaService->formatBytes(StorageQuotaService::DEFAULT_QUOTA_BYTES) . ').'
            );
        }

        return DB::transaction(function () use ($validated, $file, $user) {
            $user->loadMissing('employee');
            $filename = 'report_' . time() . '_' . $file->hashName();
            $path = UploadStorage::putFileAs("reports/{$user->id}", $file, $filename);

            $report = Report::create([
                'submitted_by' => $user->id,
                'report_title' => $validated['report_title'],
                'report_category' => $validated['report_category'] ?? null,
                'description' => $validated['description'] ?? null,
                'file_path' => $path,
            ]);

            DashboardLog::create([
                'user_id' => $user->id,
                'activity' => 'Submitted report: ' . $report->report_title,
                'activity_type' => 'report_submitted',
                'visibility' => 'own',
            ]);

            $employeeName = $user->employee->full_name ?? $user->username;
            $employeeId = $user->employee?->employee_id;

            $this->notificationService->notifySupervisors(
                "{$employeeName} submitted a report: {$report->report_title}",
                null,
                $user->id,
                null,
                function (User $recipient) use ($employeeId) {
                    if (!$employeeId) {
                        return null;
                    }

                    if ($recipient->isProgramCoordinator()) {
                        return route('coordinator.faculty-profile', $employeeId);
                    }

                    return route('dean.employee-profile', $employeeId);
                }
            );

            return $report;
        });
    }

    public function serve(Report $report, bool $download = false)
    {
        $path = (string) $report->file_path;
        $name = $report->displayFilename();

        if (UploadStorage::exists($path)) {
            UploadStorage::assertPathInDirectory($path, 'reports');

            return $download
                ? UploadStorage::downloadResponse($path, $name)
                : UploadStorage::inlineResponse($path, $name);
        }

        $public = public_path(ltrim($path, '/\\'));
        $realPublic = realpath($public);
        $realRoot = realpath(public_path());
        if ($realPublic && $realRoot && str_starts_with($realPublic, $realRoot) && is_file($realPublic)) {
            return $download
                ? response()->download($realPublic, $name)
                : response()->file($realPublic);
        }

        abort(404, 'Report file not found');
    }
}
