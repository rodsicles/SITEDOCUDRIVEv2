<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\UploadStorage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PreviewTurnover extends Command
{
    protected $signature = 'turnover:preview {--keep-user-id= : Required verified Dean user ID} {--expect-name= : Required exact Dean name}';

    protected $description = 'Read-only turnover inventory for review; never deletes records or storage objects';

    public function handle(): int
    {
        $id = (string) $this->option('keep-user-id');
        $name = trim((string) $this->option('expect-name'));
        $user = ctype_digit($id) ? User::with(['role', 'employee'])->find((int) $id) : null;
        if (!$user || $name === '' || $user->username !== 'dean' || $user->role?->role_name !== 'Dean'
            || $user->name !== $name || ($user->employee && $user->employee->full_name !== $name)) {
            $this->error('STOP: verify the Dean ID, username, role and exact user/employee name. Nothing changed.');
            return self::FAILURE;
        }

        $this->warn('READ ONLY. Inventory is not approval to delete. Counts may change during normal use.');
        $this->table(['Environment', 'Database', 'Upload disk', 'Protected ID', 'Protected username'], [[
            app()->environment(), DB::connection()->getDatabaseName(), UploadStorage::diskName(), $user->id, $user->username,
        ]]);
        $this->line('Proposed account removals (all except protected ID):');
        $this->table(['ID', 'Username', 'Name'], DB::table('users')->where('id', '<>', $user->id)
            ->orderBy('id')->get(['id', 'username', 'name'])->map(fn ($row) => (array) $row)->all());

        $preserve = ['migrations', 'roles', 'programs', 'courses', 'course_sections', 'school_years', 'skill_tags', 'equipment_items'];
        $clear = ['documents', 'document_versions', 'document_comments', 'document_favorites', 'document_views',
            'document_recipients', 'document_search_indexes', 'saved_document_searches', 'document_filters',
            'teaching_guides', 'teaching_guide_recipients', 'exam_questionnaires', 'exam_records',
            'document_requests', 'document_request_recipients', 'document_request_changes',
            'teacher_loads', 'teacher_load_items', 'teacher_load_revisions', 'tasks', 'task_attachments',
            'performance_reports', 'reports', 'professional_developments', 'notifications', 'dashboard_logs',
            'announcements', 'announcement_reads', 'announcement_reactions', 'calendar_events', 'event_attendees',
            'leave_requests', 'leave_balances', 'equipment_borrows', 'password_reset_requests', 'password_reset_tokens', 'sessions'];
        $rows = [];
        foreach (Schema::getTableListing() as $table) {
            $action = in_array($table, $preserve, true) ? 'KEEP' : (in_array($table, $clear, true) ? 'PROPOSE CLEAR (approval required)' : 'REVIEW / DO NOT DELETE');
            $rows[] = [$table, DB::table($table)->count(), $action];
        }
        $this->table(['Table', 'Rows', 'Proposed treatment'], $rows);
        $this->line('Referenced uploaded objects (not a bucket scan; derived PDFs/orphans require separate review):');
        foreach (['documents' => 'file_path', 'document_versions' => 'file_path', 'teaching_guides' => 'file_path',
            'exam_questionnaires' => 'file_path', 'task_attachments' => 'file_path', 'reports' => 'file_path',
            'professional_developments' => 'certificate_path'] as $table => $column) {
            if (!Schema::hasColumn($table, $column)) {
                $this->warn("REVIEW: missing {$table}.{$column}");
                continue;
            }
            foreach (DB::table($table)->whereNotNull($column)->distinct()->pluck($column) as $path) {
                $this->line(json_encode(['table' => $table, 'disk' => UploadStorage::diskName(), 'path' => $path], JSON_UNESCAPED_SLASHES));
            }
        }
        $this->line('Non-Dean avatars are on the public disk; preserve any path shared with the Dean:');
        foreach (DB::table('users')->where('id', '<>', $user->id)->whereNotNull('avatar_path')->get(['id', 'avatar_path']) as $avatar) {
            $this->line(json_encode(['user_id' => $avatar->id, 'disk' => 'public', 'path' => $avatar->avatar_path,
                'protected_shared_path' => $avatar->avatar_path === $user->avatar_path], JSON_UNESCAPED_SLASHES));
        }
        $this->warn('Folders, categories, retained foreign keys, queues, caches and backups require review. No deletion command is enabled.');
        return self::SUCCESS;
    }
}
