<?php

namespace Tests\Unit;

use App\Support\DocumentNaming;
use PHPUnit\Framework\TestCase;

class DocumentNamingTest extends TestCase
{
    public function test_original_name_wins_over_renamed_or_shortened_document_title(): void
    {
        $this->assertSame('Original full filename with spaces.pdf', DocumentNaming::downloadFilename('Short renamed title', 'documents/random.pdf', 'Original full filename with spaces.pdf'));
        $this->assertSame('Meeting notes.pdf', DocumentNaming::downloadFilename('Short', 'documents/converted.pdf', 'Meeting notes.docx'));
        $this->assertSame('Image.png', DocumentNaming::downloadFilename('Image.jpg', 'documents/random.png'));
    }

    public function test_original_filename_is_safe_and_long_names_keep_the_extension(): void
    {
        $name = DocumentNaming::downloadFilename('Title', 'documents/hash.pdf', "../bad\r\nname.pdf");
        $this->assertSame('badname.pdf', $name);
        $long = DocumentNaming::downloadFilename('Title', 'documents/hash.docx', str_repeat('a', 240).'.docx');
        $this->assertLessThanOrEqual(200, mb_strlen($long));
        $this->assertStringEndsWith('.docx', $long);
    }

    public function test_download_filename_uses_title_and_stored_extension(): void
    {
        $name = DocumentNaming::downloadFilename(
            'try rename',
            'exam-questionnaires/1779263963_0_abc.pdf',
        );

        $this->assertSame('try rename.pdf', $name);
    }

    public function test_download_filename_replaces_wrong_extension(): void
    {
        $name = DocumentNaming::downloadFilename(
            'Final Revision.pdf',
            'documents/123_0_file.docx',
        );

        $this->assertSame('Final Revision.docx', $name);
    }

    public function test_sanitize_strips_invalid_path_characters(): void
    {
        $name = DocumentNaming::sanitizeDownloadFilename('bad/name:test');

        $this->assertSame('badnametest', $name);
    }

    public function test_title_with_version_appends_and_replaces_suffix(): void
    {
        $this->assertSame('GameDevPrelim (v2)', DocumentNaming::titleWithVersion('GameDevPrelim', 2));
        $this->assertSame('GameDevPrelim (v3)', DocumentNaming::titleWithVersion('GameDevPrelim (v2)', 3));
        $this->assertSame('GameDevPrelim (v4)', DocumentNaming::titleWithVersion('GameDevPrelim v3', 4));
    }
}
