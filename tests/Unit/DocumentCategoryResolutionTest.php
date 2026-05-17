<?php

namespace Tests\Unit;

use App\Models\Folder;
use App\Services\DocumentService;
use PHPUnit\Framework\TestCase;

class DocumentCategoryResolutionTest extends TestCase
{
    public function test_allowed_categories_include_teaching_guides_and_exam_questionnaires(): void
    {
        $allowed = DocumentService::allowedCategories();

        $this->assertContains('Teaching Guides', $allowed);
        $this->assertContains('Exam Questionnaires', $allowed);
        $this->assertContains('Other', $allowed);
    }

    public function test_top_level_category_walks_to_root(): void
    {
        $root = new Folder(['folder_id' => 1, 'folder_name' => 'Teaching Guides', 'parent_id' => null, 'level' => 0]);
        $semester = new Folder(['folder_id' => 2, 'folder_name' => '2nd Semester AY 2025-2026', 'parent_id' => 1, 'level' => 1]);
        $finals = new Folder(['folder_id' => 3, 'folder_name' => 'Finals', 'parent_id' => 2, 'level' => 2]);

        $semester->setRelation('parent', $root);
        $finals->setRelation('parent', $semester);

        $this->assertSame('Teaching Guides', $finals->top_level_category);
        $this->assertSame('Teaching Guides', $semester->top_level_category);
        $this->assertSame('Teaching Guides', $root->top_level_category);
    }
}
