<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;

    protected $primaryKey = 'employee_id';
    
    protected $fillable = [
        'user_id',
        'employee_no',
        'full_name',
        'department',
        'position',
        'hire_date',
    ];

    protected $casts = [
        'hire_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function performanceReports()
    {
        return $this->hasMany(PerformanceReport::class, 'employee_id', 'employee_id');
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
