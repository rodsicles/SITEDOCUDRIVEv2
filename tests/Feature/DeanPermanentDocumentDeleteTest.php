<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DeanPermanentDocumentDeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_dean_can_purge_unlinked_file_and_linked_version_blocks_purge(): void
    {
        Storage::fake('local');
        config(['filesystems.upload_disk' => 'local']);
        $dean = User::where('username', 'dean')->firstOrFail();
        $document = Document::create(['uploaded_by' => $dean->id, 'document_title' => 'Draft',
            'file_path' => 'documents/draft.pdf', 'document_type' => 'pdf']);
        Storage::disk('local')->put('documents/draft.pdf', 'test');
        DocumentVersion::create(['document_id' => $document->document_id, 'version_number' => 1,
            'document_title' => 'Draft', 'file_path' => 'documents/older.pdf', 'created_at' => now()]);
        $faculty = User::whereHas('role', fn ($q) => $q->where('role_name', 'Faculty Employee'))->firstOrFail();
        $this->actingAs($faculty)->getJson(route('dean.documents.delete-impact', $document->document_id))->assertForbidden();
        $this->actingAs($faculty)->delete(route('dean.documents.force-delete', $document->document_id))->assertForbidden();
        $this->actingAs($dean)->getJson(route('dean.documents.delete-impact', $document->document_id))
            ->assertOk()->assertJsonPath('saved_versions', 1)->assertJsonPath('blocked', true);
        $this->actingAs($dean)->delete(route('dean.documents.force-delete', $document->document_id))->assertRedirect()->assertSessionHas('error');
        $this->assertNotNull($document->fresh());
        Storage::disk('local')->assertExists('documents/draft.pdf');
        DocumentVersion::where('document_id', $document->document_id)->delete();
        $this->actingAs($dean)->getJson(route('dean.documents.delete-impact', $document->document_id))
            ->assertOk()->assertJsonPath('blocked', false);
        $this->actingAs($dean)->delete(route('dean.documents.force-delete', $document->document_id))->assertRedirect();
        $this->assertNull(Document::withTrashed()->find($document->document_id));
        Storage::disk('local')->assertMissing('documents/draft.pdf');
    }
}
