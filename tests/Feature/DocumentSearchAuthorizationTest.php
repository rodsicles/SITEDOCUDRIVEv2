<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\SavedDocumentSearch;
use App\Models\User;
use App\Services\DocumentSearchService;
use App\Support\CoordinatorDepartment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentSearchAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $username): User
    {
        return User::where('username', $username)->firstOrFail();
    }

    private function coordinator(string $home = 'BSIT', array $extra = []): User
    {
        $coordinator = $this->user('coordinator');
        $coordinator->employee->update(['program' => $home]);
        $coordinator->employee->syncExtraPrograms($extra);

        return $coordinator->fresh(['employee.extraPrograms']);
    }

    public function test_coordinator_search_filters_drop_unassigned_programs(): void
    {
        $service = app(DocumentSearchService::class);
        $coordinator = $this->coordinator('BSIT', ['BSCpE', 'BLIS']);

        $filters = $service->normalizeFilters($coordinator, ['program' => 'BSEnSE', 'q' => 'report']);
        $this->assertArrayNotHasKey('program', $filters);

        $filters = $service->normalizeFilters($coordinator, ['program' => 'BLIS', 'q' => 'report']);
        $this->assertSame('BLIS', $filters['program']);
    }

    public function test_global_document_hits_respect_extra_program_scope(): void
    {
        $service = app(DocumentSearchService::class);
        $facultyCpe = $this->user('faculty');
        $facultyCpe->employee->update(['program' => 'BSCpE']);

        $doc = Document::create([
            'uploaded_by' => $facultyCpe->id,
            'document_title' => 'CpE Unique Alpha Report',
            'file_path' => 'documents/test-cpe.pdf',
            'file_size' => 12,
            'document_type' => 'pdf',
            'category' => 'Academics',
        ]);

        $homeOnly = $this->coordinator('BSIT');
        $this->assertSame([], $service->globalDocumentHits($homeOnly, 'CpE Unique Alpha', 5));

        $multi = $this->coordinator('BSIT', ['BSCpE']);
        $hits = $service->globalDocumentHits($multi, 'CpE Unique Alpha', 5);
        $this->assertNotEmpty($hits);
        $this->assertSame($doc->document_title, $hits[0]['title']);
    }

    public function test_saved_search_strips_program_outside_handled_scope(): void
    {
        $coordinator = $this->coordinator('BSIT', ['BLIS']);

        $this->actingAs($coordinator)->post(route('document-search.saved.store'), [
            'name' => 'Scoped',
            'filters' => ['q' => 'alpha', 'program' => 'BSEnSE'],
        ])->assertRedirect();

        $saved = SavedDocumentSearch::where('user_id', $coordinator->id)->where('name', 'Scoped')->first();
        $this->assertNotNull($saved);
        $this->assertArrayNotHasKey('program', $saved->filters);
    }

    public function test_search_api_returns_structured_failure_instead_of_html(): void
    {
        $this->actingAs($this->user('faculty'))
            ->getJson('/search?q=alpha')
            ->assertOk()
            ->assertJsonStructure(['ok', 'results']);
    }

    public function test_deep_folder_scope_uses_descendant_ids(): void
    {
        $parent = \App\Models\Folder::create(['folder_name' => 'Parent', 'is_system' => true]);
        $child = \App\Models\Folder::create(['folder_name' => 'Child', 'parent_id' => $parent->folder_id, 'is_system' => true]);
        $grand = \App\Models\Folder::create(['folder_name' => 'Grand', 'parent_id' => $child->folder_id, 'is_system' => true]);

        $ids = \App\Models\Folder::descendantIdsIncludingSelf($parent->folder_id);
        $this->assertEqualsCanonicalizing([$parent->folder_id, $child->folder_id, $grand->folder_id], $ids);
    }
}
