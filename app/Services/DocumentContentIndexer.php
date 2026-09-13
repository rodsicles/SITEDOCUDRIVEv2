<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentSearchIndex;
use App\Support\UploadStorage;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Smalot\PdfParser\Parser as PdfParser;
use ZipArchive;

class DocumentContentIndexer
{
    public function index(Document $document): DocumentSearchIndex
    {
        $index = DocumentSearchIndex::firstOrNew(['document_id' => $document->document_id]);

        if (!UploadStorage::exists($document->file_path)) {
            return $this->failed($index, 'Stored file is unavailable.');
        }

        $temporary = null;
        try {
            $path = UploadStorage::isLocal()
                ? UploadStorage::localAbsolutePath($document->file_path)
                : $temporary = $this->copyToTemporaryFile($document);

            $index->file_hash = hash_file('sha256', $path) ?: null;
            [$text, $method] = $this->extract($path, $document->document_type, $document->file_path);
            $index->fill([
                'content_text' => Str::limit($this->normalize($text), 4_000_000, ''),
                'extraction_method' => $method,
                'index_status' => trim($text) === '' ? 'no_text' : 'indexed',
                'index_error' => null,
                'indexed_at' => now(),
            ])->save();
        } catch (\Throwable $e) {
            report($e);
            $this->failed($index, Str::limit($e->getMessage(), 1000));
        } finally {
            if ($temporary && is_file($temporary)) {
                @unlink($temporary);
            }
        }

        return $index;
    }

    private function extract(string $path, ?string $type, string $storedPath): array
    {
        $extension = strtolower(pathinfo($storedPath, PATHINFO_EXTENSION));
        $type = strtolower((string) $type);

        if ($extension === 'docx') {
            return [$this->extractDocx($path), 'docx_xml'];
        }
        if ($extension === 'doc') {
            return [$this->runTextCommand(['antiword', $path]), 'antiword'];
        }
        if ($extension === 'pdf' || str_contains($type, 'pdf')) {
            try {
                $text = $this->runTextCommand(['pdftotext', '-layout', $path, '-']);
            } catch (\Throwable) {
                try {
                    $text = (new PdfParser())->parseFile($path)->getText();
                } catch (\Throwable) {
                    $text = '';
                }
            }
            if (mb_strlen(trim($text)) >= 40) {
                return [$text, 'pdf_text'];
            }

            return [$this->ocrPdf($path), 'tesseract_ocr'];
        }
        if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'tif', 'tiff'], true)) {
            return [$this->runTextCommand(['tesseract', $path, 'stdout', '-l', 'eng']), 'tesseract_ocr'];
        }

        return ['', 'unsupported'];
    }

    private function extractDocx(string $path): string
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new \RuntimeException('Unable to open Word document.');
        }
        $xml = $zip->getFromName('word/document.xml') ?: '';
        $zip->close();
        $xml = str_replace(['</w:p>', '</w:tr>', '<w:tab/>'], ["\n", "\n", "\t"], $xml);

        return html_entity_decode(strip_tags($xml), ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    private function ocrPdf(string $path): string
    {
        $directory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'site-ocr-'.Str::uuid();
        if (!mkdir($directory, 0700, true) && !is_dir($directory)) {
            throw new \RuntimeException('Unable to create OCR workspace.');
        }

        try {
            $prefix = $directory.DIRECTORY_SEPARATOR.'page';
            $this->runCommand(['pdftoppm', '-f', '1', '-l', '20', '-png', '-r', '160', $path, $prefix]);
            $text = '';
            foreach (glob($prefix.'-*.png') ?: [] as $image) {
                $text .= "\n".$this->runTextCommand(['tesseract', $image, 'stdout', '-l', 'eng']);
            }
            return $text;
        } finally {
            foreach (glob($directory.DIRECTORY_SEPARATOR.'*') ?: [] as $file) {
                @unlink($file);
            }
            @rmdir($directory);
        }
    }

    private function runTextCommand(array $command): string
    {
        $process = $this->runCommand($command);
        return $process->getOutput();
    }

    private function runCommand(array $command): Process
    {
        $process = new Process($command);
        $process->setTimeout(90);
        $process->mustRun();
        return $process;
    }

    private function copyToTemporaryFile(Document $document): string
    {
        $extension = pathinfo($document->file_path, PATHINFO_EXTENSION);
        $base = tempnam(sys_get_temp_dir(), 'site-index-');
        if ($base === false) {
            throw new \RuntimeException('Unable to create a temporary indexing file.');
        }
        $path = $base.($extension ? '.'.$extension : '');
        if ($path !== $base) {
            rename($base, $path);
        }
        file_put_contents($path, UploadStorage::disk()->get($document->file_path));
        return $path;
    }

    private function normalize(string $text): string
    {
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', ' ', $text) ?? $text;
        $text = preg_replace('/[ \t]+/u', ' ', $text) ?? $text;
        return trim(preg_replace('/\R{3,}/u', "\n\n", $text) ?? $text);
    }

    private function failed(DocumentSearchIndex $index, string $message): DocumentSearchIndex
    {
        $index->fill(['index_status' => 'failed', 'index_error' => $message, 'indexed_at' => now()])->save();
        return $index;
    }
}
