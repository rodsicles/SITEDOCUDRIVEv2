<?php

namespace App\Providers;

use App\Console\Commands\CreateSchoolYearFolders;
use App\Console\Commands\EnsureAcademicYearFolders;
use App\Console\Commands\PurgeOldTrashedDocuments;
use App\Console\Commands\VerifyUploadStorageCommand;
use App\Models\ExamQuestionnaire;
use App\Models\SchoolYear;
use App\Models\TeachingGuide;
use App\Services\DashboardService;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::defaultView('partials.pagination');
        Paginator::defaultSimpleView('partials.pagination');

        Schema::defaultStringLength(191);

        $uploadDisk = config('filesystems.upload_disk');
        if (is_string($uploadDisk) && $uploadDisk !== '' && $uploadDisk !== 'local') {
            config(["filesystems.disks.{$uploadDisk}.throw" => true]);
        }

        if ($this->app->runningInConsole()) {
            $this->commands([
                CreateSchoolYearFolders::class,
                EnsureAcademicYearFolders::class,
                PurgeOldTrashedDocuments::class,
                VerifyUploadStorageCommand::class,
            ]);
        }

        View::composer(
            ['layouts.dashboard', 'partials.dean-sidebar', 'partials.secretary-sidebar', 'partials.notifications-list'],
            function ($view) {
                $user = auth()->user();
                if (!$user || (!$user->isFaculty() && !$user->isProgramCoordinator() && !$user->isDeanOrSecretary())) {
                    return;
                }

                $view->with(
                    'unreadNotifications',
                    app(DashboardService::class)->getUnreadNotificationCount($user->id),
                );
            },
        );

        View::composer('partials.faculty-sidebar', function ($view) {
            $user = auth()->user();
            if (!$user?->isFaculty()) {
                return;
            }

            // Faculty badges: own submissions with open revision notes (no approval queue any more).
            $pendingTeachingGuidesCount = \App\Support\SubmissionPendingCounts::openNotesFor($user, TeachingGuide::class);
            $pendingExamQuestionnairesCount = \App\Support\SubmissionPendingCounts::openNotesFor($user, ExamQuestionnaire::class);

            $view->with(compact('pendingTeachingGuidesCount', 'pendingExamQuestionnairesCount'));
        });

        View::composer(['partials.dean-sidebar', 'partials.secretary-sidebar'], function ($view) {
            $user = auth()->user();
            if (!$user?->isDeanOrSecretary()) {
                return;
            }

            $view->with([
                'pendingTeachingGuidesCount' => \App\Support\SubmissionPendingCounts::teachingGuidesFor($user),
                'pendingExamQuestionnairesCount' => \App\Support\SubmissionPendingCounts::examQuestionnairesFor($user),
            ]);
        });

        View::composer('partials.coordinator-sidebar', function ($view) {
            $user = auth()->user();
            if (!$user?->isProgramCoordinator()) {
                return;
            }

            $view->with([
                'pendingTeachingGuidesCount' => \App\Support\SubmissionPendingCounts::teachingGuidesFor($user),
                'pendingExamQuestionnairesCount' => \App\Support\SubmissionPendingCounts::examQuestionnairesFor($user),
            ]);
        });
    }
}
