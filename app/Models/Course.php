<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    public const DEPT_IT = 'Information Technology';

    public const DEPT_ENGINEERING = 'Engineering';

    public const PROGRAM_IT = 'BSIT';

    protected $fillable = [
        'code',
        'title',
        'program',
        'department', // Legacy migrations still populate this before the program migration runs.
        'year_level',
        'semester',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'year_level' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function (Course $course) {
            if ($course->program) $course->department = Program::DEPARTMENT_NAME;
        });
    }

    public function academicProgram() { return $this->belongsTo(Program::class, 'program', 'code'); }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->whereIn('program', Program::codes());
    }

    public function scopeForDepartment($query, ?string $department)
    {
        if (!$department) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where('program', $department);
    }

    public function scopeOrdered($query)
    {
        return $query
            ->orderByRaw('CASE WHEN year_level IS NULL THEN 99 ELSE year_level END')
            ->orderByRaw("CASE semester WHEN '1st' THEN 1 WHEN '2nd' THEN 2 WHEN 'Summer' THEN 3 ELSE 4 END")
            ->orderBy('sort_order')
            ->orderBy('code');
    }

    public function scopeForTerm($query, ?string $semester)
    {
        if (! $semester) {
            return $query;
        }

        return $query->where('semester', $semester);
    }

    public function scopeForYearLevel($query, ?int $yearLevel)
    {
        if (! $yearLevel) {
            return $query;
        }

        return $query->where('year_level', $yearLevel);
    }

    public function label(): string
    {
        return $this->code . ' – ' . $this->title;
    }

    public function termLabel(): string
    {
        $year = $this->year_level ? $this->year_level.'Y' : '';
        $sem = $this->semester ?: '';

        return trim($year.' '.$sem);
    }
}
