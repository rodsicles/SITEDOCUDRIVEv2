<?php

use App\Models\Folder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (Folder::query()->where('slug', Folder::CUSTOM_FOLDERS_SLUG)->exists()) {
            return;
        }

        $maxSort = (int) Folder::query()->whereNull('parent_id')->max('sort_order');

        DB::table('folders')->insert([
            'user_id' => null,
            'folder_name' => 'Custom Folders',
            'slug' => Folder::CUSTOM_FOLDERS_SLUG,
            'color' => '#028a0f',
            'parent_id' => null,
            'is_system' => true,
            'level' => 0,
            'sort_order' => $maxSort + 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        // Keep Custom Folders — do not drop user-created subfolders on rollback.
    }
};
