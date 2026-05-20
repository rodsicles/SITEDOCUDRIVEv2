<?php

namespace App\Services;

use App\Models\Document;
use App\Models\ExamQuestionnaire;
use App\Models\SchoolYear;
use App\Models\TeachingGuide;
use App\Models\User;
use App\Support\AcademicYear;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SubmissionAnalyticsService
{
    /**
     * @param  array{school_year: ?int, semester: ?string, department: ?string}  $filters
     * @return array{
     *     topFaculty: Collection,
     *     monthlyTrend: Collection,
     *     totalSubmissions: int,
     *     scopeLabel: string,
     *     showTopFacultyTable: bool,
     *     schoolYearOptions: array,
     *     filters: array
     * }
     */
    public function getAnalytics(User $viewer, array $filters = []): array
    {
        $filters = $this->normalizeFilters($viewer, $filters);
        $cacheKey = $this->cacheKey($viewer, $filters);

        return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($viewer, $filters) {
            $facultyIds = $this->scopedFacultyIds($viewer, $filters['department']);
            $counts = $this->aggregateFacultyCounts($facultyIds, $filters);
            $monthly = $this->aggregateMonthlyTrend($facultyIds, $filters);

            $topFaculty = $this->buildTopFacultyList($counts, $viewer);

            return [
                'topFaculty' => $topFaculty,
                'monthlyTrend' => $monthly,
                'totalSubmissions' => (int) $counts->sum('count'),
                'scopeLabel' => $this->scopeLabel($viewer, $filters),
                'showTopFacultyTable' => !$viewer->isFaculty(),
                'schoolYearOptions' => AcademicYear::options(),
                'filters' => $filters,
            ];
        });
    }

    public function normalizeFilters(User $viewer, array $filters): array
    {
        $schoolYear = !empty($filters['school_year'])
            ? (int) $filters['school_year']
            : null;

        if (!$schoolYear) {
            $active = SchoolYear::active();
            $schoolYear = $active?->start_year ?? AcademicYear::currentStartYear();
        }

        $semester = $viewer->isFaculty() ? null : ($filters['semester'] ?? null);
        if ($semester && !in_array($semester, ['1st', '2nd'], true)) {
            $semester = null;
        }

        $department = $filters['department'] ?? null;
        if ($viewer->isProgramCoordinator()) {
            $department = optional($viewer->employee)->department;
        } elseif ($viewer->isFaculty()) {
            $department = optional($viewer->employee)->department;
        } elseif ($department && !in_array($department, ['Information Technology', 'Engineering'], true)) {
            $department = null;
        }

        return [
            'school_year' => $schoolYear,
            'academic_year' => AcademicYear::rangeString($schoolYear),
            'school_year_id' => SchoolYear::where('start_year', $schoolYear)->value('id'),
            'semester' => $semester,
            'department' => $department,
        ];
    }

    protected function cacheKey(User $viewer, array $filters): string
    {
        $role = $viewer->role->role_name ?? 'user';

        return 'submission_analytics_' . md5(json_encode([
            'role' => $role,
            'user' => $viewer->id,
            'filters' => $filters,
        ]));
    }

    /** @return list<int> */
    protected function scopedFacultyIds(User $viewer, ?string $department): array
    {
        if ($viewer->isFaculty()) {
            return [$viewer->id];
        }

        $query = User::query()
            ->whereHas('role', fn ($q) => $q->where('role_name', 'Faculty Employee'))
            ->where('status', 'Active');

        if ($department) {
            $query->whereHas('employee', fn ($q) => $q->where('department', $department));
        }

        return $query->pluck('id')->all();
    }

    protected function aggregateFacultyCounts(array $facultyIds, array $filters): Collection
    {
        if (empty($facultyIds)) {
            return collect();
        }

        $counts = collect($facultyIds)->mapWithKeys(fn ($id) => [(int) $id => 0]);

        $eqRows = ExamQuestionnaire::query()
            ->select('submitted_by', DB::raw('COUNT(*) as aggregate'))
            ->whereIn('submitted_by', $facultyIds)
            ->when($filters['semester'], fn ($q, $sem) => $q->where('semester', $sem))
            ->when($filters['school_year_id'], fn ($q, $id) => $q->where('school_year_id', $id))
            ->when(!$filters['school_year_id'] && $filters['academic_year'], fn ($q) => $q->where('academic_year', $filters['academic_year']))
            ->groupBy('submitted_by')
            ->pluck('aggregate', 'submitted_by');

        foreach ($eqRows as $userId => $count) {
            $counts[(int) $userId] = ($counts[(int) $userId] ?? 0) + (int) $count;
        }

        $tgRows = TeachingGuide::query()
            ->select('user_id', DB::raw('COUNT(*) as aggregate'))
            ->whereIn('user_id', $facultyIds)
            ->when($filters['semester'], fn ($q, $sem) => $q->where('semester', $sem))
            ->when($filters['school_year_id'], fn ($q, $id) => $q->where('school_year_id', $id))
            ->when(!$filters['school_year_id'] && $filters['academic_year'], fn ($q) => $q->where('academic_year', $filters['academic_year']))
            ->groupBy('user_id')
            ->pluck('aggregate', 'user_id');

        foreach ($tgRows as $userId => $count) {
            $counts[(int) $userId] = ($counts[(int) $userId] ?? 0) + (int) $count;
        }

        $docRows = Document::query()
            ->select('uploaded_by', DB::raw('COUNT(*) as aggregate'))
            ->whereIn('uploaded_by', $facultyIds)
            ->whereDoesntHave('examQuestionnaire')
            ->whereDoesntHave('teachingGuide')
            ->when($filters['semester'], function ($q) use ($filters) {
                $q->where(function ($inner) use ($filters) {
                    $inner->whereHas('folder', function ($fq) use ($filters) {
                        $sem = $filters['semester'] === '2nd' ? '2nd' : '1st';
                        $fq->where('folder_name', 'like', '%' . $sem . '%');
                    })->orWhereNull('folder_id');
                });
            })
            ->when($filters['school_year_id'], fn ($q, $id) => $q->where('school_year_id', $id))
            ->groupBy('uploaded_by')
            ->pluck('aggregate', 'uploaded_by');

        foreach ($docRows as $userId => $count) {
            $counts[(int) $userId] = ($counts[(int) $userId] ?? 0) + (int) $count;
        }

        return $counts
            ->map(fn ($count, $userId) => ['user_id' => (int) $userId, 'count' => (int) $count])
            ->filter(fn ($row) => $row['count'] > 0)
            ->values();
    }

    protected function aggregateMonthlyTrend(array $facultyIds, array $filters): Collection
    {
        if (empty($facultyIds)) {
            return collect();
        }

        $months = [];

        $append = function (Collection $rows) use (&$months) {
            foreach ($rows as $row) {
                $key = $row->month;
                $months[$key] = ($months[$key] ?? 0) + (int) $row->count;
            }
        };

        $eqQuery = ExamQuestionnaire::query()
            ->select(DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'), DB::raw('COUNT(*) as count'))
            ->whereIn('submitted_by', $facultyIds)
            ->where('created_at', '>=', now()->subMonths(11)->startOfMonth());

        $this->applyEqTgFilters($eqQuery, $filters);
        $append($eqQuery->groupBy('month')->orderBy('month')->get());

        $tgQuery = TeachingGuide::query()
            ->select(DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'), DB::raw('COUNT(*) as count'))
            ->whereIn('user_id', $facultyIds)
            ->where('created_at', '>=', now()->subMonths(11)->startOfMonth());

        $this->applyEqTgFilters($tgQuery, $filters, 'user_id');
        $append($tgQuery->groupBy('month')->orderBy('month')->get());

        $docQuery = Document::query()
            ->select(DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'), DB::raw('COUNT(*) as count'))
            ->whereIn('uploaded_by', $facultyIds)
            ->whereDoesntHave('examQuestionnaire')
            ->whereDoesntHave('teachingGuide')
            ->where('created_at', '>=', now()->subMonths(11)->startOfMonth())
            ->when($filters['school_year_id'], fn ($q, $id) => $q->where('school_year_id', $id));

        $append($docQuery->groupBy('month')->orderBy('month')->get());

        ksort($months);

        return collect($months)->map(fn ($count, $month) => [
            'month' => $month,
            'label' => date('M Y', strtotime($month . '-01')),
            'count' => $count,
        ])->values();
    }

    protected function applyEqTgFilters($query, array $filters, string $userColumn = 'submitted_by'): void
    {
        if ($filters['semester']) {
            $query->where('semester', $filters['semester']);
        }
        if ($filters['school_year_id']) {
            $query->where('school_year_id', $filters['school_year_id']);
        } elseif ($filters['academic_year']) {
            $query->where('academic_year', $filters['academic_year']);
        }
    }

    protected function buildTopFacultyList(Collection $counts, User $viewer): Collection
    {
        if ($viewer->isFaculty()) {
            $row = $counts->first(fn ($r) => ($r['user_id'] ?? null) === $viewer->id)
                ?? ['user_id' => $viewer->id, 'count' => 0];
            $user = $viewer->loadMissing('employee');

            return collect([[
                'rank' => 1,
                'name' => $user->employee?->full_name ?? $user->username ?? 'You',
                'department' => $user->employee?->department ?? '—',
                'count' => (int) ($row['count'] ?? 0),
                'percent' => 0,
            ]]);
        }

        if ($counts->isEmpty()) {
            return collect();
        }

        $userIds = $counts->pluck('user_id')->all();
        $users = User::with('employee')
            ->whereIn('id', $userIds)
            ->get()
            ->keyBy('id');

        $total = max(1, (int) $counts->sum('count'));

        return $counts
            ->sortByDesc('count')
            ->take(10)
            ->values()
            ->map(function ($row, $index) use ($users, $total) {
                $user = $users->get($row['user_id']);

                return [
                    'rank' => $index + 1,
                    'name' => $user?->employee?->full_name ?? $user?->username ?? 'Unknown',
                    'department' => $user?->employee?->department ?? '—',
                    'count' => $row['count'],
                    'percent' => round(($row['count'] / $total) * 100, 1),
                ];
            });
    }

    protected function scopeLabel(User $viewer, array $filters): string
    {
        $parts = [AcademicYear::label((int) $filters['school_year'])];

        if ($filters['semester']) {
            $parts[] = $filters['semester'] . ' Semester';
        }

        if ($viewer->isDean() || $viewer->isSecretary()) {
            $parts[] = $filters['department'] ?: 'All Departments';
        } elseif ($viewer->isProgramCoordinator() && $filters['department']) {
            $parts[] = $filters['department'];
        } elseif ($viewer->isFaculty()) {
            $parts[] = 'Your submissions';
        }

        return implode(' · ', $parts);
    }
}
