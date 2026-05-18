<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeSkill extends Model
{
    use HasFactory;

    protected $primaryKey = 'skill_id';

    protected $fillable = [
        'employee_id',
        'skill_name',
        'skill_level',
        'certification_name',
        'certification_date',
        'expiry_date',
    ];

    protected $casts = [
        'certification_date' => 'date',
        'expiry_date' => 'date',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'employee_id');
    }
}
