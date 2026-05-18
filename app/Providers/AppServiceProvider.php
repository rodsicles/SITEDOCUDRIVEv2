<?php

namespace App\Providers;

use App\Console\Commands\CreateSchoolYearFolders;
use App\Console\Commands\EnsureAcademicYearFolders;
use App\Console\Commands\VerifyUploadStorageCommand;
use Illuminate\Support\Facades\Schema;
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
        Schema::defaultStringLength(191);

        $uploadDisk = config('filesystems.upload_disk');
        if (is_string($uploadDisk) && $uploadDisk !== '' && $uploadDisk !== 'local') {
            config(["filesystems.disks.{$uploadDisk}.throw" => true]);
        }

        if ($this->app->runningInConsole()) {
            $this->commands([
                CreateSchoolYearFolders::class,
                EnsureAcademicYearFolders::class,
                VerifyUploadStorageCommand::class,
            ]);
        }
    }
}
