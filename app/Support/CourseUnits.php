<?php

namespace App\Support;

use App\Models\Course;
use Illuminate\Support\Facades\Schema;

/**
 * Official lecture / laboratory units per program curriculum.
 *
 * Re-run safely with `php artisan courses:sync-units`. Courses without data keep NULL units
 * (BSIT ITE132 Practicum has no confirmed Lec/Lab split yet).
 */
class CourseUnits
{
    /** @var array<string, array<string, array{0: float|int, 1: float|int}>> program => code => [lec, lab] */
    public const DATA = [
        'BLIS' => [
            'LIS101' => [3, 0], 'SpT01' => [3, 0], 'LIS102' => [3, 0], 'ICT101Bis' => [2, 1], 'SpT102' => [3, 0],
            'LIS103' => [3, 0], 'LIS105' => [3, 0], 'ICT102BLIS' => [2, 1], 'SpT03' => [3, 0], 'BLIS301' => [3, 0],
            'LIS104' => [3, 0], 'LIS106' => [3, 0], 'ICT103' => [2, 1], 'SpT04' => [3, 0], 'LIT101' => [3, 0],
            'LIT102' => [3, 0], 'LIS107' => [3, 0], 'LIS110' => [3, 0], 'ICT104' => [2, 1], 'SpT05' => [3, 0],
            'LIS108' => [3, 0], 'LIS109' => [3, 0], 'ICT105' => [2, 1], 'LIS111' => [3, 0], 'SpT06' => [3, 0],
            'FLG101' => [3, 0], 'ICT106' => [2, 1], 'SpT07' => [3, 0], 'LPr01' => [3, 0], 'LIS112' => [3, 0],
            'BLIS401' => [9, 0], 'LPr02' => [3, 0], 'LIS113' => [3, 0], 'BLIS402' => [9, 0],
        ],
        'BSCpE' => [
            'ENGGMAT1' => [3, 0], 'CHEM1' => [3, 1], 'CPE101' => [2, 0], 'CPE102' => [0, 2], 'ENGGMAT2' => [3, 0],
            'PHY1' => [3, 1], 'CPE104' => [0, 2], 'CPE103' => [3, 0], 'ENGGMAT3' => [3, 0], 'CPE105' => [0, 2],
            'EE101' => [3, 1], 'CPE107' => [0, 2], 'ENGGMAT4' => [3, 0], 'CPE108' => [3, 1], 'ENGG102' => [3, 0],
            'ENGG106' => [0, 1], 'ECE101' => [3, 1], 'CPE106' => [3, 0], 'CPE109' => [3, 1], 'CPE111' => [3, 0],
            'CPE112' => [0, 2], 'CPE113' => [3, 0], 'CPE110' => [3, 0], 'CPE115' => [0, 1], 'ELEC101CPE' => [2, 1],
            'CPE116' => [3, 1], 'CPE117' => [3, 1], 'CPE118' => [2, 0], 'CPE114' => [3, 0], 'CPE119' => [2, 0],
            'ENGG121' => [3, 0], 'ELEC102CPE' => [2, 1], 'CPE120' => [3, 1], 'CPE121' => [3, 1], 'CPE124' => [0, 1],
            'CPE123' => [3, 1], 'ENGG107' => [3, 0], 'CPE122' => [3, 0], 'ELEC103CPE' => [2, 1], 'CPE125' => [0, 2],
            'CPE126' => [0, 1], 'CPE127' => [3, 0],
        ],
        'BSIT' => [
            'ITE101' => [0, 3], 'ITE102' => [0, 3], 'ITE103' => [0, 3], 'ITE104' => [0, 3], 'ITE105' => [3, 0],
            'ITE106' => [0, 3], 'ITE107' => [0, 3], 'ITE108' => [0, 3], 'ITE109' => [0, 3], 'ITE110' => [0, 3],
            'ITE111' => [0, 3], 'ITE112' => [0, 3], 'ITE113' => [3, 0], 'ITE114' => [0, 3], 'ITE115' => [0, 3],
            'ITE116' => [0, 3], 'ITE117' => [3, 0], 'ITE118' => [0, 3], 'ITE119' => [0, 3], 'ITE120' => [3, 0],
            'ITE121' => [0, 3], 'ITE122' => [0, 3], 'ITE123' => [0, 3], 'ITE124' => [0, 3], 'ITE125' => [3, 0],
            'ITE126' => [0, 3], 'ITE127' => [0, 3], 'ITE128' => [0, 3], 'ITE129' => [3, 0], 'ITE130' => [0, 3],
            'ITE131' => [0, 3],
        ],
        'BSEnSE' => [
            'ENGGMAT1' => [3, 0], 'CHEM1set' => [3, 1], 'ENSE101' => [2, 0], 'ENGGMAT2' => [3, 0], 'PHY1set' => [3, 1],
            'ENGG101' => [0, 1], 'ENGGMAT3' => [3, 0], 'CE101' => [3, 0], 'CE102' => [4, 1], 'ENGG102' => [0, 2],
            'ENGG103' => [3, 0], 'ENGGEO1' => [3, 0], 'ENGG105' => [2, 0], 'ENGG106' => [4, 0], 'ENGG102A' => [0, 1],
            'ENGGMAT4' => [3, 0], 'ENSE102' => [2, 1], 'ENSE103' => [3, 0], 'ENGGMAT5' => [2, 1], 'ENGG112' => [2, 1],
            'ENGG114' => [3, 1], 'ENGG110' => [3, 0], 'ENGG111' => [3, 0], 'ENSE104' => [2, 1], 'ENGG119' => [3, 1],
            'ENGG115' => [3, 1], 'ENGG116' => [4, 1], 'ENGG117' => [3, 0], 'ENGG109' => [3, 0], 'ENSE105' => [3, 0],
            'CPE308' => [2, 0], 'ENSE117' => [2, 1], 'ENGG107' => [3, 0], 'ENGG118' => [3, 1], 'ENSE107' => [3, 0],
            'ENSE108' => [3, 0], 'ENSE109' => [3, 0], 'ENSE110' => [3, 1], 'ENSE111' => [1, 1], 'ENSE112' => [3, 0],
            'ENSE113' => [0, 1], 'ENSE114' => [1, 1], 'ENSE115' => [3, 0], 'ENSE116' => [3, 0], 'ENSE117A' => [3, 0],
            'ENSE118' => [0, 1], 'ENGG120' => [0, 9], 'ENSE119' => [0, 9],
        ],
    ];

    /** "ITE 101", "ite-101" and "ITE101" all compare equal. */
    public static function normalize(?string $code): string
    {
        return strtoupper(preg_replace('/[\s\-_.]+/', '', (string) $code));
    }

    public static function available(): bool
    {
        return Schema::hasColumn('courses', 'lecture_units') && Schema::hasColumn('courses', 'lab_units');
    }

    /**
     * @return array{updated: int, unchanged: int, missing: array<string, list<string>>}
     */
    public static function apply(): array
    {
        $stats = ['updated' => 0, 'unchanged' => 0, 'missing' => []];
        if (! self::available()) {
            return $stats;
        }

        foreach (self::DATA as $program => $rows) {
            $courses = Course::where('program', $program)->get()
                ->keyBy(fn (Course $course) => self::normalize($course->code));

            foreach ($rows as $code => [$lec, $lab]) {
                $course = $courses->get(self::normalize($code));
                if (! $course) {
                    $stats['missing'][$program][] = $code;
                    continue;
                }

                $course->forceFill(['lecture_units' => $lec, 'lab_units' => $lab]);
                if ($course->isDirty()) {
                    $course->save();
                    $stats['updated']++;
                } else {
                    $stats['unchanged']++;
                }
            }
        }

        return $stats;
    }
}
