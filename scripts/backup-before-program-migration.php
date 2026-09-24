<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$config = config('database.connections.mysql');
if (!in_array($config['host'], ['localhost', '127.0.0.1'], true)) throw new RuntimeException('Local database required.');
$directory = storage_path('app/backups/program-structure-'.date('Ymd-His'));
if (!mkdir($directory, 0700, true)) throw new RuntimeException('Cannot create backup directory.');
$binary = 'C:/wamp64/bin/mysql/mysql8.4.7/bin/mysqldump.exe';
$process = new Symfony\Component\Process\Process([
    $binary, '--host='.$config['host'], '--port='.($config['port'] ?? 3306), '--user='.$config['username'],
    '--lock-all-tables', '--routines', '--triggers', '--result-file='.$directory.'/database.sql', $config['database'],
], null, ['MYSQL_PWD' => $config['password']]);
$process->setTimeout(120);
$process->run();
if (!$process->isSuccessful() || filesize($directory.'/database.sql') < 100) throw new RuntimeException('Database backup failed; do not migrate.');
file_put_contents($directory.'/courses.json', json_encode(Illuminate\Support\Facades\DB::table('courses')->orderBy('id')->get(), JSON_PRETTY_PRINT));
echo 'Backup verified: '.$directory."\n";
