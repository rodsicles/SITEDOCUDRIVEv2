<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;

    public const FACULTY_TYPES = [
        'full_time' => 'Full-Time Faculty',
        'shared' => 'Shared Faculty',
    ];

    protected $primaryKey = 'employee_id';
    
    protected $fillable = [
        'user_id',
        'employee_no',
        'full_name',
        'program',
        'faculty_type',
        'department',
        'position',
        'hire_date',
    ];

    protected $casts = [
        'hire_date' => 'date',
    ];

    protected static function booted(): void
    {
        static::saving(function (Employee $employee) {
            if ($employee->program) $employee->department = Program::DEPARTMENT_NAME;
        });
    }

    public function academicProgram() { return $this->belongsTo(Program::class, 'program', 'code'); }

    /** Programs a coordinator handles in addition to their home program. */
    public function extraPrograms()
    {
        return $this->hasMany(EmployeeProgram::class, 'employee_id', 'employee_id');
    }

    /** @return list<string> */
    public function extraProgramCodes(): array
    {
        $extra = $this->extraPrograms->pluck('program')->all();

        return array_values(array_filter(
            Program::codes(),
            fn (string $code) => $code !== $this->program && in_array($code, $extra, true)
        ));
    }

    /** @param list<string> $codes */
    public function syncExtraPrograms(array $codes): void
    {
        $codes = array_values(array_unique(array_filter(
            $codes,
            fn ($code) => is_string($code) && $code !== $this->program && in_array($code, Program::codes(), true)
        )));

        $this->extraPrograms()->whereNotIn('program', $codes)->delete();
        foreach ($codes as $code) {
            $this->extraPrograms()->firstOrCreate(['program' => $code]);
        }
        $this->unsetRelation('extraPrograms');
    }

    public function facultyTypeLabel(): string
    {
        return self::FACULTY_TYPES[$this->faculty_type] ?? 'Full-Time Faculty';
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function performanceReports()
    {
        return $this->hasMany(PerformanceReport::class, 'employee_id', 'employee_id');
    }

    public function skills()
    {
        return $this->hasMany(EmployeeSkill::class, 'employee_id', 'employee_id');
    }

    public function getYearsOfService(): ?int
    {
        if (!$this->hire_date) {
            return null;
        }
        return (int) $this->hire_date->diffInYears(now());
    }

    public function getServiceMilestone(): ?int
    {
        $years = $this->getYearsOfService();
        if ($years === null) {
            return null;
        }

        $milestones = [30, 25, 20, 15, 10, 5];
        foreach ($milestones as $milestone) {
            if ($years >= $milestone) {
                return $milestone;
            }
        }
        return null;
    }
}
