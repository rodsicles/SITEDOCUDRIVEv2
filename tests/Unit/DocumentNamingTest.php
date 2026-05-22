<?php

namespace Tests\Unit;

use App\Support\DocumentNaming;
use PHPUnit\Framework\TestCase;

class DocumentNamingTest extends TestCase
{
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
}
