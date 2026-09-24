<?php
// Local-only isolated database for migration and MySQL workflow verification.
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$config = config('database.connections.mysql');
if (!in_array($config['host'], ['127.0.0.1', 'localhost'], true)) throw new RuntimeException('Local database required.');
Illuminate\Support\Facades\DB::statement('CREATE DATABASE IF NOT EXISTS docudrive_program_restructure_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
echo "Isolated local test database ready.\n";
