<?php

use App\Models\Folder;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (Folder::query()->where('slug', Folder::CUSTOM_FOLDERS_SLUG)->exists()) {
            return;
        }

        $ownerId = User::query()->orderBy('id')->value('id') ?? 1;
        $maxSort = (int) Folder::query()->whereNull('parent_id')->max('sort_order');

        DB::table('folders')->insert([
            'user_id' => $ownerId,
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
        $categoryId = Folder::query()
            ->where('slug', Folder::CUSTOM_FOLDERS_SLUG)
            ->value('folder_id');

        if (!$categoryId) {
            return;
        }

        Folder::query()->where('parent_id', $categoryId)->delete();
        Folder::query()->where('folder_id', $categoryId)->delete();
    }
};
