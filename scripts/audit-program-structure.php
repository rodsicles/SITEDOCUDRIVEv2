<?php
// Read-only schema and aggregate audit. No user names, passwords or document contents.
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
echo 'Driver: '.DB::connection()->getDriverName()."\n";
if (DB::connection()->getDriverName() === 'mysql') {
    echo 'Engines: '.json_encode(DB::select('SELECT TABLE_NAME, ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN ("employees", "courses", "users")'))."\n";
}
foreach (['departments', 'programs', 'employees', 'courses', 'faculty_courses', 'documents', 'announcements', 'reports', 'performance_reports'] as $table) {
    if (!Schema::hasTable($table)) { echo "$table: absent\n"; continue; }
    echo $table.': '.DB::table($table)->count().' rows; columns: '.implode(', ', Schema::getColumnListing($table))."\n";
    if (Schema::hasColumn($table, 'department')) echo json_encode(DB::table($table)->select('department')->selectRaw('COUNT(*) as total')->groupBy('department')->get())."\n";
    if (Schema::hasColumn($table, 'program')) echo json_encode(DB::table($table)->select('program')->selectRaw('COUNT(*) as total')->groupBy('program')->get())."\n";
    echo 'Foreign keys: '.json_encode(Schema::getForeignKeys($table))."\n";
}
