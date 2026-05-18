<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use App\Services\ExamRecordService;
use App\Services\SubmissionAnalyticsService;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public function __construct(
        protected DashboardService $dashboardService,
        protected SubmissionAnalyticsService $submissionAnalytics,
        protected ExamRecordService $examRecordService,
    ) {}

    public function index(Request $request)
    {
        $user = auth()->user();

        $submissionData = $this->submissionAnalytics->getAnalytics($user, [
            'school_year' => $request->query('school_year'),
            'semester' => $request->query('semester'),
            'department' => $request->query('department'),
        ]);

        $data = array_merge($submissionData, [
            'analyticsRoute' => $this->routeNameFor($user),
        ]);

        if ($user->isDean() || $user->isSecretary()) {
            $data = array_merge($data, $this->dashboardService->getAnalyticsData(), [
                'examTrends' => $this->examRecordService->getTrends(),
            ]);

            return view('dean.analytics', $data);
        }

        if ($user->isProgramCoordinator()) {
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
