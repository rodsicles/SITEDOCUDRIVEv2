<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use App\Services\EngagementAnalyticsService;
use App\Services\SubmissionAnalyticsService;
use App\Support\CoordinatorDepartment;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public function __construct(
        protected DashboardService $dashboardService,
        protected SubmissionAnalyticsService $submissionAnalytics,
        protected EngagementAnalyticsService $engagementAnalytics,
    ) {}

    public function index(Request $request)
    {
        $user = auth()->user();

        $filterInput = $user->isFaculty()
            ? ['school_year' => $request->query('school_year')]
            : [
                'school_year' => $request->query('school_year'),
                'semester' => $request->query('semester'),
                'department' => $request->query('department'),
            ];

        $submissionData = $this->submissionAnalytics->getAnalytics($user, $filterInput);
        $engagementData = $this->engagementAnalytics->getEngagement($user, $filterInput);

        $data = array_merge($submissionData, $engagementData, [
            'analyticsRoute' => $this->routeNameFor($user),
        ]);

        if ($user->isDean() || $user->isSecretary()) {
            $data = array_merge($data, $this->dashboardService->getAnalyticsData());
            return view('dean.analytics', $data);
        }

        if ($user->isProgramCoordinator()) {
            CoordinatorDepartment::require($user);

            return view('coordinator.analytics', $data);
        }

        return view('faculty.analytics', $data);
    }

    protected function routeNameFor($user): string
    {
        if ($user->isDean() || $user->isSecretary()) {
            return 'dean.analytics';
        }
        if ($user->isProgramCoordinator()) {
            return 'coordinator.analytics';
        }

        return 'faculty.analytics';
    }
}
