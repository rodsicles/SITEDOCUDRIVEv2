<?php

use App\Console\Commands\CreateSchoolYearFolders;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Auto-create new school year folders every August 10
// e.g. Aug 10, 2026 → creates AY 2026-2027 folders under Teaching Guides & Exam Questionnaires
Schedule::command('folders:create-school-year')->yearlyOn(8, 10, '00:05');
