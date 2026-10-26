<?php

namespace Tests\Feature;

use App\Models\{Document, DocumentVersion, User};
use App\Services\{DocumentService, DocumentVersionService};
use App\Support\UploadStorage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\{Queue, Storage};
use Tests\TestCase;

class OriginalFilenameTest extends TestCase
{
    use RefreshDatabase;

    private function upload(): array
    {
        Storage::fake('local'); Queue::fake(); config(['filesystems.upload_disk' => 'local']);
        $dean = User::where('username', 'dean')->firstOrFail();
        $file = UploadedFile::fake()->create('Complete Original Filename Beyond The Title Limit 2026.pdf', 10, 'application/pdf');
        app(DocumentService::class)->uploadDocuments(['document_type' => 'pdf', 'document_title' => 'Short display title'], [$file], $dean->id);
        return [$dean, Document::latest('document_id')->firstOrFail(), $file->getClientOriginalName()];
    }

    public function test_download_and_copy_preserve_original_name_after_title_rename(): void
    {
        [$dean, $document, $name] = $this->upload();
        $this->assertSame($name, $document->original_filename);
        $document->update(['document_title' => 'Renamed title']);
        $this->actingAs($dean)->get(route('dean.download-document', $document->document_id))->assertOk()->assertDownload($name);
        $copy = app(DocumentService::class)->copyDocument($document, null, $dean);
        $this->assertSame($name, $copy->original_filename);
        $this->get(route('dean.download-document', $copy->document_id))->assertOk()->assertDownload($name);
    }

    public function test_both_version_paths_and_restore_keep_each_files_own_name(): void
    {
        [$dean, $document, $firstName] = $this->upload();
        app(DocumentService::class)->uploadNewVersion($document, UploadedFile::fake()->create('Second original.pdf', 10, 'application/pdf'), $dean);
        $archived = DocumentVersion::where('document_id', $document->document_id)->firstOrFail();
        $this->assertSame($firstName, $archived->original_filename);
        $this->actingAs($dean)->get(route('document-versions.download', $archived->id))->assertOk()->assertDownload($firstName);
        $this->assertSame('Second original.pdf', $document->fresh()->original_filename);
        $service = app(DocumentVersionService::class);
        $service->restoreVersion($document->fresh(), $archived->id, $dean);
        $this->assertSame($firstName, $document->fresh()->original_filename);
        $service->uploadNewVersion($document->fresh(), UploadedFile::fake()->create('Correction original.pdf', 10, 'application/pdf'), $dean);
        $this->assertSame('Correction original.pdf', $document->fresh()->original_filename);
        $this->actingAs($dean)->get(route('dean.download-document', $document->document_id))->assertOk()->assertDownload('Correction original.pdf');
    }

    public function test_inline_and_remote_attachment_names_support_unicode_and_legacy_fallback(): void
    {
        [$dean, $document] = $this->upload();
        $document->update(['original_filename' => null, 'document_title' => 'Legacy readable title']);
        $this->actingAs($dean)->get(route('dean.download-document', $document->document_id))->assertOk()->assertDownload('Legacy readable title.pdf');
        $response = UploadStorage::inlineResponse($document->file_path, 'Peña report.pdf');
        $this->assertStringContainsString("filename*=utf-8''Pe%C3%B1a%20report.pdf", $response->headers->get('Content-Disposition'));
        config(['filesystems.disks.remote-test' => ['driver' => 'local'], 'filesystems.upload_disk' => 'remote-test']); Storage::fake('remote-test');
        Storage::disk('remote-test')->put('documents/file.pdf', 'test');
        $response = UploadStorage::downloadResponse('documents/file.pdf', 'Readable name.pdf');
        $this->assertStringContainsString('Readable name.pdf', $response->headers->get('Content-Disposition'));
    }

    public function test_report_and_training_certificate_keep_uploaded_names(): void
    {
        [$dean] = $this->upload();
        $report = app(\App\Services\ReportService::class)->create(['report_title' => 'Display title', 'description' => 'Test'], UploadedFile::fake()->create('Signed report original.pdf', 10, 'application/pdf'), $dean);
        $this->assertSame('Signed report original.pdf', $report->displayFilename());
        $this->assertStringContainsString('Signed report original.pdf', app(\App\Services\ReportService::class)->serve($report, true)->headers->get('Content-Disposition'));
        $service = app(\App\Services\ProfessionalDevelopmentService::class);
        $data = ['seminar_name' => 'Training display name', 'date_attended' => now()->toDateString(), 'organizer' => 'SITE', 'hours' => 2];
        $certificate = $service->create($data, UploadedFile::fake()->create('Training certificate original.pdf', 10, 'application/pdf'), $dean);
        $this->assertSame('Training certificate original.pdf', $certificate->original_filename);
        $service->update($certificate, $data, UploadedFile::fake()->create('Replacement certificate.pdf', 10, 'application/pdf'), $dean->id);
        $this->assertSame('Replacement certificate.pdf', $certificate->fresh()->original_filename);
    }
}
