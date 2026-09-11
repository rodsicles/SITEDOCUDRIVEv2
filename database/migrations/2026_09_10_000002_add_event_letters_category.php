<?php

use App\Models\Folder;
use App\Services\DocumentService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public const SLUG = 'event-letters';

    public function up(): void
    {
        $this->expandDocumentsCategoryEnum();

        if (Folder::query()->where('slug', self::SLUG)->exists()) {
            $this->ensureSortOrder();

            return;
        }

        $accreditationSort = (int) Folder::query()
            ->whereNull('parent_id')
            ->where('slug', 'accreditation-and-certifications')
            ->value('sort_order');

        // Make room after Accreditation (and before Academics / other roots).
        Folder::query()
            ->whereNull('parent_id')
            ->where('sort_order', '>', $accreditationSort)
            ->increment('sort_order');

        DB::table('folders')->insert([
            'user_id' => null,
            'folder_name' => 'Event Letters',
            'slug' => self::SLUG,
            'color' => '#028a0f',
            'parent_id' => null,
            'is_system' => true,
            'level' => 0,
            'sort_order' => $accreditationSort + 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        $folderId = Folder::query()->where('slug', self::SLUG)->value('folder_id');

        if ($folderId) {
            DB::table('documents')
                ->where('category', 'Event Letters')
                ->update(['category' => 'Other', 'folder_id' => null]);

            Folder::query()->where('parent_id', $folderId)->delete();
            Folder::query()->where('folder_id', $folderId)->delete();
        }

        $this->shrinkDocumentsCategoryEnum();
    }

    protected function ensureSortOrder(): void
    {
        $accreditation = Folder::query()
            ->whereNull('parent_id')
            ->where('slug', 'accreditation-and-certifications')
            ->first();

        $eventLetters = Folder::query()
            ->whereNull('parent_id')
            ->where('slug', self::SLUG)
            ->first();

        if (!$accreditation || !$eventLetters) {
            return;
        }

        $desired = (int) $accreditation->sort_order + 1;
        if ((int) $eventLetters->sort_order === $desired) {
            return;
        }

        Folder::query()
            ->whereNull('parent_id')
            ->where('folder_id', '!=', $eventLetters->folder_id)
            ->where('sort_order', '>=', $desired)
            ->increment('sort_order');

        $eventLetters->update(['sort_order' => $desired]);
    }

    protected function expandDocumentsCategoryEnum(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        $values = array_map(
            fn (string $v) => "'".str_replace("'", "''", $v)."'",
            DocumentService::allowedCategories()
        );

        DB::statement('ALTER TABLE documents MODIFY COLUMN category ENUM('.implode(', ', $values).") DEFAULT 'Other'");
    }

    protected function shrinkDocumentsCategoryEnum(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        // Prior enum set before Event Letters was introduced.
        $values = [
            'Accreditation and Certifications',
            'Academics',
            'Teaching Guides',
            'Exam Questionnaires',
            'Other',
        ];

        $sql = array_map(fn (string $v) => "'".str_replace("'", "''", $v)."'", $values);
        DB::statement('ALTER TABLE documents MODIFY COLUMN category ENUM('.implode(', ', $sql).") DEFAULT 'Other'");
    }
};
