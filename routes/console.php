<?php

use App\Console\Commands\CreateSchoolYearFolders;
use App\Support\UploadStorage;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('storage:verify-uploads', function () {
    $diskName = UploadStorage::diskName();
    $this->info('Upload disk: ' . $diskName);

    $testPath = '_healthcheck/' . uniqid('ping_', true) . '.txt';
    $payload = 'ok-' . now()->toIso8601String();

    try {
        UploadStorage::disk()->put($testPath, $payload, ['visibility' => 'private']);

        if (! UploadStorage::exists($testPath)) {
            $this->error('Write succeeded but file not found on read.');

            return 1;
        }

        $read = UploadStorage::disk()->get($testPath);
        UploadStorage::delete($testPath);

        if ($read !== $payload) {
            $this->error('Read content mismatch.');

            return 1;
        }

        $this->info('Upload storage is working. Files will persist across deploys.');

        return 0;
    } catch (\Throwable $e) {
        $this->error('Upload storage failed: ' . $e->getMessage());

        return 1;
    }
})->purpose('Verify upload disk (local or S3/R2) can write and read');

// Auto-create new school year folders every August 10
// e.g. Aug 10, 2026 → creates AY 2026-2027 folders under Teaching Guides & Exam Questionnaires
Schedule::command('folders:create-school-year')->yearlyOn(8, 10, '00:05');
Schedule::command('recycle-bin:purge --days=30')->dailyAt('02:00');
