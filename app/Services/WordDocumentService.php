<?php

namespace App\Services;

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\SimpleType\TblWidth;
use Illuminate\Support\Facades\Storage;

class WordDocumentService
{
    public function generatePrcResultsDoc(array $examData, string $batchLabel, string $recorderName): string
    {
        $phpWord = new PhpWord();

        // Default font
        $phpWord->setDefaultFontName('Arial');
        $phpWord->setDefaultFontSize(11);

        $section = $phpWord->addSection([
            'marginTop' => 600,
            'marginBottom' => 600,
            'marginLeft' => 720,
            'marginRight' => 720,
        ]);

        // Logo
        $logoPath = public_path('images/site-logo.png');
        if (file_exists($logoPath)) {
            $section->addImage($logoPath, [
                'width' => 80,
                'height' => 80,
                'alignment' => Jc::CENTER,
            ]);
        }

        // School name header
        $section->addText(
            'SITE - School of Information Technology and Engineering',
            ['bold' => true, 'size' => 14, 'color' => '028a0f'],
            ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
        );

        $section->addText(
            'St. Paul University Philippines',
            ['size' => 11, 'color' => '333333'],
            ['alignment' => Jc::CENTER, 'spaceAfter' => 200]
        );

        // Divider line
        $section->addText(
            str_repeat('─', 60),
            ['size' => 8, 'color' => '028a0f'],
            ['alignment' => Jc::CENTER, 'spaceAfter' => 200]
        );

        // Title
        $section->addText(
            'PRC Board Examination Results',
            ['bold' => true, 'size' => 16],
            ['alignment' => Jc::CENTER, 'spaceAfter' => 100]
        );

        $section->addText(
            'Batch: ' . $batchLabel,
            ['bold' => true, 'size' => 12, 'color' => '028a0f'],
            ['alignment' => Jc::CENTER, 'spaceAfter' => 300]
        );

        // Results table
        $tableStyle = [
            'borderSize' => 6,
            'borderColor' => '999999',
            'cellMargin' => 80,
            'unit' => TblWidth::PERCENT,
            'width' => 100 * 50,
        ];

        $phpWord->addTableStyle('ResultsTable', $tableStyle);
        $table = $section->addTable('ResultsTable');

        // Header row
        $headerStyle = ['bgColor' => '028a0f'];
        $headerFont = ['bold' => true, 'color' => 'FFFFFF', 'size' => 11];
        $cellStyle = ['valign' => 'center'];

        $table->addRow(400);
        $table->addCell(4000, array_merge($headerStyle, $cellStyle))->addText('Examination', $headerFont, ['alignment' => Jc::CENTER]);
        $table->addCell(2000, array_merge($headerStyle, $cellStyle))->addText('Passed', $headerFont, ['alignment' => Jc::CENTER]);
        $table->addCell(2000, array_merge($headerStyle, $cellStyle))->addText('Total Examinees', $headerFont, ['alignment' => Jc::CENTER]);

        // Data rows
        $rowFont = ['size' => 11];
        $numberFont = ['bold' => true, 'size' => 12];

        foreach ($examData as $exam) {
            $altBg = [];
            $table->addRow(350);
            $table->addCell(4000, array_merge($altBg, $cellStyle))->addText($exam['exam_type'], $rowFont);
            $table->addCell(2000, array_merge($altBg, $cellStyle))->addText((string) $exam['passed_count'], $numberFont, ['alignment' => Jc::CENTER]);
            $table->addCell(2000, array_merge($altBg, $cellStyle))->addText($exam['total_examinees'] ? (string) $exam['total_examinees'] : 'N/A', $rowFont, ['alignment' => Jc::CENTER]);
        }

        // Total row
        $totalPassed = array_sum(array_column($examData, 'passed_count'));
        $totalExaminees = array_sum(array_filter(array_column($examData, 'total_examinees')));
        $table->addRow(400);
        $table->addCell(4000, array_merge(['bgColor' => 'f0f0f0'], $cellStyle))->addText('TOTAL', ['bold' => true, 'size' => 11]);
        $table->addCell(2000, array_merge(['bgColor' => 'f0f0f0'], $cellStyle))->addText((string) $totalPassed, ['bold' => true, 'size' => 12, 'color' => '028a0f'], ['alignment' => Jc::CENTER]);
        $table->addCell(2000, array_merge(['bgColor' => 'f0f0f0'], $cellStyle))->addText($totalExaminees > 0 ? (string) $totalExaminees : 'N/A', ['bold' => true, 'size' => 11], ['alignment' => Jc::CENTER]);

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
                ['bold' => true, 'size' => 14],
                ['alignment' => Jc::CENTER, 'spaceAfter' => 200]
            );

            foreach ($examData as $exam) {
                if (empty($exam['passer_names'])) {
                    continue;
                }

                $section->addText(
                    $exam['exam_type'],
                    ['bold' => true, 'size' => 12, 'color' => '028a0f'],
                    ['spaceAfter' => 100]
                );

                foreach ($exam['passer_names'] as $index => $name) {
                    $section->addText(
                        ($index + 1) . '. ' . $name,
                        ['size' => 11],
                        ['indent' => 0.5, 'spaceAfter' => 40]
                    );
                }

                $section->addTextBreak(1);
            }
        } else {
            $section->addTextBreak(2);
        }

        // Footer
        $section->addText(
            str_repeat('─', 60),
            ['size' => 8, 'color' => 'cccccc'],
            ['alignment' => Jc::CENTER, 'spaceAfter' => 100]
        );

        $section->addText(
            'Generated: ' . date('F d, Y h:i A'),
            ['size' => 9, 'color' => '666666', 'italic' => true],
            ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
        );

        $section->addText(
            'Recorded by: ' . $recorderName,
            ['size' => 9, 'color' => '666666', 'italic' => true],
            ['alignment' => Jc::CENTER]
        );

        // Save file
        $filename = 'PRC_Results_' . str_replace(' ', '_', $batchLabel) . '_' . time() . '.docx';
        $storagePath = Storage::disk('local')->path('documents/' . $filename);

        // Ensure directory exists
        $dir = dirname($storagePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $writer = IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($storagePath);

        return 'documents/' . $filename;
    }
}
