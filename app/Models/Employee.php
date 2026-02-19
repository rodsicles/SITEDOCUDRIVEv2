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
}
