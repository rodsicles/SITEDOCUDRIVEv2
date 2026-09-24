<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$path = $argv[1] ?? '';
if (!is_file($path)) throw new RuntimeException('Provide the pre-migration courses.json backup path.');
$before = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
$after = Illuminate\Support\Facades\DB::table('courses')->get()->keyBy('id');
if (count($before) !== $after->count()) throw new RuntimeException('Course count changed.');
foreach ($before as $course) {
    $current = (array) $after->get($course['id']);
    foreach (['id', 'code', 'title', 'is_active', 'sort_order', 'created_at', 'updated_at'] as $column) {
        if ((string) $current[$column] !== (string) $course[$column]) throw new RuntimeException('Course preservation check failed: '.$course['id'].' '.$column);
    }
}
echo 'Verified: '.count($before)." course IDs, codes, titles, activity flags, ordering and timestamps preserved.\n";
