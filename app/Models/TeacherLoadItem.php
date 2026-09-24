<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeacherLoadItem extends Model
{
    public $timestamps = false;
    protected $guarded = [];
    protected $casts = [
        'schedules' => 'array',
        'lecture_units' => 'decimal:2',
        'lab_units' => 'decimal:2',
        'load_equivalent' => 'decimal:3',
    ];
    public function course() { return $this->belongsTo(Course::class); }
    public function teacherLoad() { return $this->belongsTo(TeacherLoad::class); }
}
