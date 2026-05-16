<?php

namespace App\Services;

use App\Models\BackupLog;
use App\Models\DashboardLog;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Symfony\Component\Process\Process;

class BackupService
{
    public function createBackup(User $user): array
    {
        $filename = 'backup_' . date('Y-m-d_His') . '.sql';
        $backupDir = storage_path('app/backups');

        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $path = $backupDir . '/' . $filename;

        $dbConfig = config('database.connections.mysql');

        $mysqldump = config('database.dump.mysqldump_path', 'mysqldump');

        $command = sprintf(
            '%s --host=%s --port=%s --user=%s --password=%s %s',
            escapeshellarg($mysqldump),
            escapeshellarg($dbConfig['host']),
            escapeshellarg($dbConfig['port'] ?? '3306'),
            escapeshellarg($dbConfig['username']),
            escapeshellarg($dbConfig['password']),
            escapeshellarg($dbConfig['database'])
        );

        $process = Process::fromShellCommandline($command . ' > ' . escapeshellarg($path));
        $process->setTimeout(120);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException('Backup failed: ' . $process->getErrorOutput());
        }

        $fileSize = file_exists($path) ? filesize($path) : 0;

        BackupLog::create([
            'user_id' => $user->id,
            'filename' => $filename,
            'file_size' => $fileSize,
            'type' => 'export',
        ]);

        DashboardLog::create([
            'user_id' => $user->id,
            'activity' => 'Created database backup: ' . $filename,
            'activity_type' => 'backup_created',
            'log_date' => now(),
        ]);

        return [
            'filename' => $filename,
            'path' => $path,
            'size' => $fileSize,
        ];
    }

    public function getBackupHistory(): Collection
    {
        return BackupLog::with('user')
            ->orderByDesc('created_at')
            ->get();
    }

    public function downloadBackup(string $filename)
    {
        $filename = basename($filename);
        $path = storage_path('app/backups/' . $filename);

        if (!file_exists($path)) {
            abort(404, 'Backup file not found.');
        }

        return response()->download($path);
    }

    public function restoreBackup(UploadedFile $file, User $user): void
    {
        $tempPath = $file->storeAs('backups/temp', 'restore_' . time() . '.sql', 'local');
        $fullPath = \Illuminate\Support\Facades\Storage::disk('local')->path($tempPath);

        $dbConfig = config('database.connections.mysql');

        $mysql = config('database.dump.mysql_path', 'mysql');

        $command = sprintf(
            '%s --host=%s --port=%s --user=%s --password=%s %s',
            escapeshellarg($mysql),
            escapeshellarg($dbConfig['host']),
            escapeshellarg($dbConfig['port'] ?? '3306'),
            escapeshellarg($dbConfig['username']),
            escapeshellarg($dbConfig['password']),
            escapeshellarg($dbConfig['database'])
        );

        $process = Process::fromShellCommandline($command . ' < ' . escapeshellarg($fullPath));
        $process->setTimeout(300);
        $process->run();

        $fileSize = filesize($fullPath);

        // Clean up temp file
        if (file_exists($fullPath)) {
            unlink($fullPath);
        }

        if (!$process->isSuccessful()) {
            throw new \RuntimeException('Restore failed: ' . $process->getErrorOutput());
        }

        BackupLog::create([
            'user_id' => $user->id,
            'filename' => $file->getClientOriginalName(),
            'file_size' => $fileSize,
            'type' => 'import',
        ]);

        DashboardLog::create([
            'user_id' => $user->id,
            'activity' => 'Restored database from: ' . $file->getClientOriginalName(),
            'activity_type' => 'backup_restored',
            'log_date' => now(),
        ]);
    }

    public function deleteBackup(string $filename, int $userId): void
    {
        $filename = basename($filename);
        $path = storage_path('app/backups/' . $filename);

        if (file_exists($path)) {
            unlink($path);
        }

        BackupLog::where('filename', $filename)->delete();

        DashboardLog::create([
            'user_id' => $userId,
            'activity' => 'Deleted backup: ' . $filename,
            'activity_type' => 'backup_deleted',
            'log_date' => now(),
        ]);
    }
}
