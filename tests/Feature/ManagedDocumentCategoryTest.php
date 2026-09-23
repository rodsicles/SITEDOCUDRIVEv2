<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\DocumentRequest;
use App\Models\User;
use App\Services\DocumentService;
use App\Services\FolderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManagedDocumentCategoryTest extends TestCase
{
    use RefreshDatabase;

    private function createCategory(string $role, string $scope = 'personal', string $name = 'Research Outputs'): DocumentCategory
    {
        $this->actingAs(User::where('username', $role)->firstOrFail())->post(route('document-categories.store'), ['category_name' => $name, 'scope' => $scope])->assertRedirect()->assertSessionHasNoErrors();

        return DocumentCategory::where('category_name', $name)->latest('category_id')->firstOrFail();
    }

    public function test_all_roles_can_create_personal_categories_without_name_collisions(): void
    {
        foreach (['faculty', 'coordinator', 'dean'] as $role) {
            $category = $this->createCategory($role);
            $this->assertSame(auth()->id(), $category->owner_id);
            $this->get(route($role.'.documents', ['tab' => 'category-'.$category->category_id]))->assertOk()->assertSee('Research Outputs')->assertSee('Manage Categories');
        }
        $this->assertSame(3, DocumentCategory::where('category_name', 'Research Outputs')->count());
    }

    public function test_personal_category_files_and_children_are_hidden_even_from_dean(): void
    {
        $category = $this->createCategory('faculty', 'personal', 'Confidential Faculty Research');
        $owner = auth()->user();
        $leaf = $category->folders()->whereNotNull('parent_id')->firstOrFail();
        $child = app(FolderService::class)->createFolder($owner->id, 'Drafts', '#0d5c3b', $leaf->folder_id);
        $this->assertSame($category->category_id, $child->document_category_id);
        $this->assertTrue($child->is_private);
        $document = Document::create(['uploaded_by' => $owner->id, 'folder_id' => $child->folder_id, 'category_id' => $category->category_id, 'document_title' => 'Private research', 'category' => 'Other', 'document_type' => 'pdf', 'file_path' => 'test.pdf', 'file_size' => 1]);
        foreach (['coordinator', 'dean'] as $role) {
            $user = User::where('username', $role)->firstOrFail();
            $this->actingAs($user)->getJson(route('upload-destinations.index', ['folder' => $child->folder_id]))->assertNotFound();
            $this->assertFalse($document->canView($user));
            $this->assertFalse(Document::visibleTo($user)->whereKey($document->document_id)->exists());
            $this->get(route($role.'.documents'))->assertOk()->assertDontSee('Confidential Faculty Research');
            $this->post(route($role.'.folders.store'), ['parent_id' => $leaf->folder_id, 'folder_name' => 'Intrusion'])->assertNotFound();
        }
    }

    public function test_only_dean_can_create_system_categories_and_nonowners_cannot_edit(): void
    {
        foreach (['faculty', 'coordinator'] as $role) {
            $this->actingAs(User::where('username', $role)->firstOrFail())->post(route('document-categories.store'), ['category_name' => 'Shared', 'scope' => 'system'])->assertForbidden();
        }
        $category = $this->createCategory('dean', 'system');
        $this->assertNull($category->owner_id);
        $this->actingAs(User::where('username', 'faculty')->firstOrFail())->get(route('faculty.documents'))->assertOk()->assertSee('Research Outputs');
        $this->patch(route('document-categories.update', $category), ['category_name' => 'Hijack', 'is_active' => 1])->assertForbidden();
    }

    public function test_rename_and_deactivate_preserve_files_but_block_new_uploads(): void
    {
        $category = $this->createCategory('faculty');
        $leaf = $category->folders()->whereNotNull('parent_id')->firstOrFail();
        $document = Document::create(['uploaded_by' => auth()->id(), 'folder_id' => $leaf->folder_id, 'category_id' => $category->category_id, 'document_title' => 'Existing research', 'category' => 'Other', 'document_type' => 'pdf', 'file_path' => 'test.pdf', 'file_size' => 1]);
        $this->patch(route('document-categories.update', $category), ['category_name' => 'Research Archive', 'is_active' => 0])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseHas('documents', ['document_id' => $document->document_id, 'category_id' => $category->category_id]);
        $this->get(route('faculty.documents', ['tab' => 'category-'.$category->category_id]))->assertOk()->assertSee('Existing research')->assertSee('Research Archive');
        $this->postJson(route('faculty.upload-document'), ['folder_id' => $leaf->folder_id])->assertUnprocessable();
        $this->getJson(route('upload-destinations.index', ['folder' => $leaf->folder_id]))->assertNotFound();
        $this->assertTrue($document->canView(auth()->user()));
        $this->patch(route('document-categories.update', $category), ['category_name' => 'Research Archive', 'is_active' => 1])->assertRedirect();
        $this->getJson(route('upload-destinations.index', ['folder' => $leaf->folder_id]))->assertOk()->assertJsonPath('uploadable', true);
    }

    public function test_duplicate_and_reserved_names_are_rejected(): void
    {
        $this->createCategory('faculty');
        foreach ([' research outputs ', 'Teaching Guides', '   '] as $name) {
            $this->post(route('document-categories.store'), ['category_name' => $name, 'scope' => 'personal'])->assertSessionHasErrors('category_name');
        }
    }

    public function test_general_request_uses_system_category_and_rejects_personal_category(): void
    {
        $system = $this->createCategory('dean', 'system');
        $personal = $this->createCategory('dean', 'personal', 'Private Notes');
        $payload = ['title' => 'Research submission', 'request_category' => 'general', 'document_type' => 'pdf', 'recipient_ids' => [User::where('username', 'faculty')->value('id')], 'custom_category_id' => $personal->category_id];
        $this->post(route('document-requests.store'), $payload)->assertSessionHasErrors('custom_category_id');
        $payload['custom_category_id'] = $system->category_id;
        $this->post(route('document-requests.store'), $payload)->assertRedirect()->assertSessionHasNoErrors();
        $request = DocumentRequest::where('title', 'Research submission')->firstOrFail();
        $this->assertSame($system->category_id, $request->destinationFolder->document_category_id);
    }

    public function test_each_role_can_upload_and_filter_by_its_custom_category(): void
    {
        config(['filesystems.upload_disk' => 'local']);
        \Illuminate\Support\Facades\Storage::fake('local');
        \Illuminate\Support\Facades\Queue::fake();
        foreach (['faculty', 'coordinator', 'dean'] as $role) {
            $category = $this->createCategory($role);
            $leaf = $category->folders()->whereNotNull('parent_id')->firstOrFail();
            $this->postJson(route($role.'.upload-document'), [
                'folder_id' => $leaf->folder_id, 'guided_upload' => 1, 'document_type' => 'pdf',
                'documents' => [\Illuminate\Http\UploadedFile::fake()->create('research.pdf', 10, 'application/pdf')],
            ])->assertOk()->assertJsonPath('success', true);
            $document = Document::where('category_id', $category->category_id)->firstOrFail();
            \Illuminate\Support\Facades\Storage::disk('local')->assertExists($document->file_path);
            $result = app(DocumentService::class)->getFilteredDocuments(auth()->user(), null, null, ['managed_category_id' => $category->category_id]);
            $this->assertSame(1, $result->total());
            $this->assertSame($document->document_id, $result->first()->document_id);
        }
    }
}
