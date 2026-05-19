<?php

namespace App\Providers;

use App\Console\Commands\CreateSchoolYearFolders;
use App\Console\Commands\EnsureAcademicYearFolders;
use App\Console\Commands\PurgeOldTrashedDocuments;
use App\Console\Commands\VerifyUploadStorageCommand;
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

        View::composer('layouts.dashboard', function ($view) {
            $user = auth()->user();
            if (!$user || (!$user->isFaculty() && !$user->isProgramCoordinator())) {
                return;
            }

            $view->with(
                'unreadNotifications',
                app(DashboardService::class)->getUnreadNotificationCount($user->id),
            );
        });
    }
}
