<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\SimpleType\TblWidth;
use App\Support\UploadStorage;

class WordDocumentService
{
    public function generatePrcResultsDoc(array $examData, string $batchLabel, string $recorderName, string $department = 'SCHOOL OF INFORMATION TECHNOLOGY AND ENGINEERING'): string
    {
        $phpWord = new PhpWord();

        // Default font
        $phpWord->setDefaultFontName('Arial');
        $phpWord->setDefaultFontSize(11);

        // Margins: top/bottom 1080 twips (~1.9 cm), sides 720 twips (~1.27 cm)
        $section = $phpWord->addSection([
            'marginTop'    => 1080,
            'marginBottom' => 900,
            'marginLeft'   => 900,
            'marginRight'  => 900,
            'headerHeight' => 720,
            'footerHeight' => 600,
        ]);

        // ── REPEATING HEADER (every page) ──────────────────────────────
        $header = $section->addHeader();

        // Seal + university info in a 2-column table
        $sealPath   = public_path('images/SPUP-final-logo.png');
        $headerTable = $header->addTable([
            'unit'             => TblWidth::PERCENT,
            'width'            => 100 * 50,
            'borderSize'       => 0,
            'borderColor'      => 'ffffff',
            'cellMarginTop'    => 0,
            'cellMarginBottom' => 0,
        ]);

        $headerTable->addRow(800);

        // Seal cell
        $sealCell = $headerTable->addCell(1200, ['valign' => 'center']);
        if (file_exists($sealPath)) {
            $sealCell->addImage($sealPath, [
                'width'     => 58,
                'height'    => 58,
                'alignment' => Jc::RIGHT,
            ]);
        }

        // University info cell
        $infoCell = $headerTable->addCell(10800, ['valign' => 'center']);
        $infoCell->addText(
            'St. Paul University Philippines',
            ['name' => 'Times New Roman', 'bold' => true, 'size' => 18],
            ['alignment' => Jc::CENTER, 'spaceAfter' => 0, 'spaceBefore' => 0]
        );
        $infoCell->addText(
            'Tuguegarao City, Cagayan 3500',
            ['size' => 9, 'color' => '000000'],
            ['alignment' => Jc::CENTER, 'spaceAfter' => 0, 'spaceBefore' => 0]
        );
        $infoCell->addText(
            'Tel: 396-1967-1994',
            ['size' => 9, 'color' => '000000'],
            ['alignment' => Jc::CENTER, 'spaceAfter' => 0, 'spaceBefore' => 0]
        );
        $infoCell->addText(
            'Fax: 078-8464305',
            ['size' => 9, 'color' => '000000'],
            ['alignment' => Jc::CENTER, 'spaceAfter' => 0, 'spaceBefore' => 0]
        );
        $infoCell->addText(
            'www.spup.edu.ph',
            ['size' => 9, 'color' => '000000'],
            ['alignment' => Jc::CENTER, 'spaceAfter' => 0, 'spaceBefore' => 0]
        );

        // Thin gold separator (30 twip = ~0.5mm)
        $sepTable = $header->addTable([
            'borderSize' => 0, 'borderColor' => 'ffffff',
            'width' => 5000, 'unit' => TblWidth::PERCENT,
        ]);
        $sepTable->addRow(30);
        $sepTable->addCell(5000, [
            'bgColor'           => 'C9A227',
            'borderTopSize'     => 0, 'borderTopColor'    => 'ffffff',
            'borderBottomSize'  => 0, 'borderBottomColor' => 'ffffff',
            'borderLeftSize'    => 0, 'borderLeftColor'   => 'ffffff',
            'borderRightSize'   => 0, 'borderRightColor'  => 'ffffff',
        ])->addText('');

        // Thin green separator
        $greenTable = $header->addTable([
            'borderSize' => 0, 'borderColor' => 'ffffff',
            'width' => 5000, 'unit' => TblWidth::PERCENT,
        ]);
        $greenTable->addRow(30);
        $greenTable->addCell(5000, [
            'bgColor'           => '006633',
            'borderTopSize'     => 0, 'borderTopColor'    => 'ffffff',
            'borderBottomSize'  => 0, 'borderBottomColor' => 'ffffff',
            'borderLeftSize'    => 0, 'borderLeftColor'   => 'ffffff',
            'borderRightSize'   => 0, 'borderRightColor'  => 'ffffff',
        ])->addText('');

        // Department / office label
        $header->addText(
            strtoupper($department),
            ['bold' => true, 'size' => 9, 'color' => '000000'],
            ['alignment' => Jc::CENTER, 'spaceAfter' => 0, 'spaceBefore' => 40]
        );

        // ── REPEATING FOOTER (every page) ──────────────────────────────
        $footer = $section->addFooter();

        // Thin gold separator
        $fGoldTable = $footer->addTable([
            'borderSize' => 0, 'borderColor' => 'ffffff',
            'width' => 5000, 'unit' => TblWidth::PERCENT,
        ]);
        $fGoldTable->addRow(30);
        $fGoldTable->addCell(5000, [
            'bgColor'           => 'C9A227',
            'borderTopSize'     => 0, 'borderTopColor'    => 'ffffff',
            'borderBottomSize'  => 0, 'borderBottomColor' => 'ffffff',
            'borderLeftSize'    => 0, 'borderLeftColor'   => 'ffffff',
            'borderRightSize'   => 0, 'borderRightColor'  => 'ffffff',
        ])->addText('');

        // Thin green separator
        $fGreenTable = $footer->addTable([
            'borderSize' => 0, 'borderColor' => 'ffffff',
            'width' => 5000, 'unit' => TblWidth::PERCENT,
        ]);
        $fGreenTable->addRow(30);
        $fGreenTable->addCell(5000, [
            'bgColor'           => '006633',
            'borderTopSize'     => 0, 'borderTopColor'    => 'ffffff',
            'borderBottomSize'  => 0, 'borderBottomColor' => 'ffffff',
            'borderLeftSize'    => 0, 'borderLeftColor'   => 'ffffff',
            'borderRightSize'   => 0, 'borderRightColor'  => 'ffffff',
        ])->addText('');

        // Footer badges image
        $badgesPath = public_path('images/spup-footer-badges.jpg');
        if (file_exists($badgesPath)) {
            $footer->addImage($badgesPath, [
                'width'     => 460,
                'height'    => 51,
                'alignment' => Jc::LEFT,
            ]);
        } else {
            // Text fallback if image missing
            $footer->addText(
                'TÜV Rheinland Certified  |  AASBI Accredited  |  PACU  |  1957          MAKING A DIFFERENCE GLOBALLY',
                ['size' => 7, 'color' => '444444'],
                ['alignment' => Jc::LEFT]
            );
        }

        // Title
        $section->addText(
            'PRC BOARD EXAMINATION RESULTS',
            ['bold' => true, 'size' => 13],
            ['alignment' => Jc::CENTER, 'spaceAfter' => 60]
        );

        $section->addText(
            $batchLabel,
            ['bold' => true, 'size' => 11],
            ['alignment' => Jc::CENTER, 'spaceAfter' => 180]
        );

        // Results table
        $tableStyle = [
            'borderSize'  => 6,
            'borderColor' => '000000',
            'cellMargin'  => 40,
            'unit'        => TblWidth::PERCENT,
            'width'       => 100 * 50,
        ];

        $phpWord->addTableStyle('ResultsTable', $tableStyle);
        $table = $section->addTable('ResultsTable');

        // Header row — clean bold text, no background
        $headerFont = ['bold' => true, 'size' => 9, 'color' => '000000'];
        $cellStyle  = ['valign' => 'center'];

        $table->addRow(280);
        $table->addCell(4000, $cellStyle)->addText('EXAMINATION', $headerFont, ['alignment' => Jc::CENTER]);
        $table->addCell(2000, $cellStyle)->addText('PASSED', $headerFont, ['alignment' => Jc::CENTER]);
        $table->addCell(2000, $cellStyle)->addText('TOTAL EXAMINEES', $headerFont, ['alignment' => Jc::CENTER]);

        // Data rows
        $rowFont    = ['size' => 9];
        $numberFont = ['bold' => true, 'size' => 9];

        foreach ($examData as $exam) {
            $table->addRow(260);
            $table->addCell(4000, $cellStyle)->addText($exam['exam_type'], $rowFont);
            $table->addCell(2000, $cellStyle)->addText((string) $exam['passed_count'], $numberFont, ['alignment' => Jc::CENTER]);
            $table->addCell(2000, $cellStyle)->addText($exam['total_examinees'] ? (string) $exam['total_examinees'] : 'N/A', $rowFont, ['alignment' => Jc::CENTER]);
        }

        // Total row
        $totalPassed = array_sum(array_column($examData, 'passed_count'));
        $totalExaminees = array_sum(array_filter(array_column($examData, 'total_examinees')));
        $table->addRow(280);
        $table->addCell(4000, $cellStyle)->addText('TOTAL', ['bold' => true, 'size' => 9]);
        $table->addCell(2000, $cellStyle)->addText((string) $totalPassed, ['bold' => true, 'size' => 9], ['alignment' => Jc::CENTER]);
        $table->addCell(2000, $cellStyle)->addText($totalExaminees > 0 ? (string) $totalExaminees : 'N/A', ['bold' => true, 'size' => 9], ['alignment' => Jc::CENTER]);

        $section->addTextBreak(1);

        // List of Passers section
        $hasNames = false;
        foreach ($examData as $exam) {
            if (!empty($exam['passer_names'])) {
                $hasNames = true;
                break;
            }
        }

        if ($hasNames) {
            $section->addText(
                'List of Passers',
                ['bold' => true, 'size' => 11],
                ['alignment' => Jc::CENTER, 'spaceAfter' => 80]
            );

            foreach ($examData as $exam) {
                if (empty($exam['passer_names'])) {
                    continue;
                }

                $section->addText(
                    $exam['exam_type'],
                    ['bold' => true, 'size' => 10],
                    ['spaceAfter' => 40]
                );

                foreach ($exam['passer_names'] as $index => $name) {
                    $section->addText(
                        ($index + 1) . '. ' . $name,
                        ['size' => 9],
                        ['indent' => 0.5, 'spaceAfter' => 20]
                    );
                }

                $section->addTextBreak(1);
            }
        } else {
            $section->addTextBreak(2);
        }

        $section->addTextBreak(2);

        // Recorded-by note
        $section->addText(
            'Generated: ' . date('F d, Y h:i A') . '   |   Recorded by: ' . $recorderName,
            ['size' => 9, 'color' => '666666', 'italic' => true],
            ['alignment' => Jc::CENTER]
        );

        // Save to temp, then persist on upload disk (local or cloud object storage)
        $filename = 'PRC_Results_' . str_replace(' ', '_', $batchLabel) . '_' . time() . '.docx';
        $relativePath = 'documents/' . $filename;
        $tempDocx = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('prc_', true) . '.docx';
        $tempPdf = str_replace('.docx', '.pdf', $tempDocx);

        $writer = IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($tempDocx);

        $this->generatePrcResultsPdf($examData, $batchLabel, $recorderName, $tempPdf);

        UploadStorage::putFromLocalFile($relativePath, $tempDocx);
        UploadStorage::putFromLocalFile(str_replace('.docx', '.pdf', $relativePath), $tempPdf);

        @unlink($tempDocx);
        @unlink($tempPdf);

        return $relativePath;
    }

    /**
     * Generate a PDF version of PRC results using dompdf.
     */
    private function generatePrcResultsPdf(array $examData, string $batchLabel, string $recorderName, string $pdfStoragePath): void
    {
        $totalPassed    = array_sum(array_column($examData, 'passed_count'));
        $totalExaminees = array_sum(array_filter(array_column($examData, 'total_examinees')));
        $hasNames       = collect($examData)->contains(fn($e) => !empty($e['passer_names']));

        $pdf = Pdf::loadView('exports.prc-results-pdf', compact(
            'examData', 'batchLabel', 'recorderName',
            'totalPassed', 'totalExaminees', 'hasNames'
        ))->setPaper('letter', 'portrait');

        file_put_contents($pdfStoragePath, $pdf->output());
    }

    /**
     * Generate a Word doc + sibling PDF for an IT certification record,
     * using the same SPUP letterhead layout as the PRC results.
     *
     * Returns the relative storage path of the .docx file.
     */
    public function generateCertResultsDoc(
        string $certName,
        string $batchLabel,
        int $passedCount,
        array $passerNames,
        string $recorderName,
        string $department = 'SCHOOL OF INFORMATION TECHNOLOGY AND ENGINEERING'
    ): string {
        // Reuse the PRC pipeline by shaping cert data into the same examData structure,
        // so the Word output also uses the SPUP header/footer + bordered table.
        $examData = [[
            'exam_type'       => $certName,
            'passed_count'    => $passedCount,
            'total_examinees' => null,
            'passer_names'    => $passerNames,
        ]];

        // Build the .docx using the existing PRC generator (header/footer/table styling shared).
        $docPath = $this->generatePrcResultsDoc($examData, $batchLabel, $recorderName, $department);

        // Overwrite the auto-generated PDF sibling with the cert-specific PDF view
        $pdfRelativePath = preg_replace('/\.docx$/', '.pdf', $docPath);
        $tempPdf = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('cert_', true) . '.pdf';

        $this->generateCertResultsPdf(
            $certName, $batchLabel, $passedCount, $passerNames, $recorderName, $tempPdf
        );

        UploadStorage::putFromLocalFile($pdfRelativePath, $tempPdf);
        @unlink($tempPdf);

        return $docPath;
    }

    /**
     * Generate a PDF version of an IT certification record using dompdf.
     */
    private function generateCertResultsPdf(
        string $certName,
        string $batchLabel,
        int $passedCount,
        array $passerNames,
        string $recorderName,
        string $pdfStoragePath
    ): void {
        $pdf = Pdf::loadView('exports.cert-results-pdf', compact(
            'certName', 'batchLabel', 'passedCount', 'passerNames', 'recorderName'
        ))->setPaper('letter', 'portrait');

        file_put_contents($pdfStoragePath, $pdf->output());
    }
}