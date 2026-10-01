<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeProgram extends Model
{
    protected $fillable = ['employee_id', 'program'];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'employee_id');
    }
}
