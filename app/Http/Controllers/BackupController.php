<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\BackupService;

class BackupController extends Controller
{
    public function __construct(
        protected BackupService $backupService
    ) {}

    public function index()
    {
        $backups = $this->backupService->getBackupHistory();
        return view('dean.backup', compact('backups'));
    }

    public function create()
    {
        try {
            $result = $this->backupService->createBackup(auth()->user());
            return redirect()->back()->with('success', "Backup created: {$result['filename']}");
        } catch (\Exception $e) {
            \Log::error('Backup failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Backup failed. Please try again or contact support.');
        }
    }

    public function download($filename)
    {
        return $this->backupService->downloadBackup($filename);
    }

    public function restore(Request $request)
    {
        $request->validate([
            'backup_file' => 'required|file|max:51200',
        ]);

        $file = $request->file('backup_file');
        if ($file->getClientOriginalExtension() !== 'sql') {
            return redirect()->back()->with('error', 'Only .sql files are allowed.');
        }

        try {
            $this->backupService->restoreBackup($file, auth()->user());
            return redirect()->back()->with('success', 'Database restored successfully from: ' . $file->getClientOriginalName());
        } catch (\Exception $e) {
            \Log::error('Restore failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Restore failed. Please try again or contact support.');
        }
    }

    public function destroy($filename)
    {
        $this->backupService->deleteBackup($filename, auth()->id());
        return redirect()->back()->with('success', 'Backup deleted.');
    }
}
