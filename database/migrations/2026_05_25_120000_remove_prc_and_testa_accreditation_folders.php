<?php

use App\Models\Folder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const SLUGS = [
        'prc-results-civil-sanitary',
        'testa',
    ];

    public function up(): void
    {
        $folderIds = Folder::query()
            ->whereIn('slug', self::SLUGS)
            ->pluck('folder_id')
            ->all();

        if ($folderIds === []) {
            return;
        }

        $fallback = Folder::query()
            ->where('slug', 'paascu-documentation-files')
            ->value('folder_id');

        if ($fallback) {
            DB::table('documents')
                ->whereIn('folder_id', $folderIds)
                ->update(['folder_id' => $fallback]);
        } else {
            DB::table('documents')
                ->whereIn('folder_id', $folderIds)
                ->update(['folder_id' => null]);
        }

        Folder::query()
            ->whereIn('slug', self::SLUGS)
            ->delete();
    }

    public function down(): void
    {
        $parent = Folder::query()
            ->where('slug', 'accreditation-and-certifications')
            ->first();

        if (!$parent) {
            return;
        }

        $restore = [
            ['name' => 'PRC Results Civil and Sanitary Engineering', 'slug' => 'prc-results-civil-sanitary', 'sort_order' => 1],
            ['name' => 'TESTA', 'slug' => 'testa', 'sort_order' => 3],
        ];

        foreach ($restore as $row) {
            Folder::firstOrCreate(
                ['slug' => $row['slug']],
                [
                    'folder_name' => $row['name'],
                    'parent_id' => $parent->folder_id,
                    'user_id' => null,
                    'color' => '#028a0f',
                    'is_system' => true,
                    'level' => 1,
                    'sort_order' => $row['sort_order'],
                    'school_year_id' => null,
                ]
            );
        }
    }
};
