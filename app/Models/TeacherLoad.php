<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeacherLoad extends Model
{
    protected $guarded = [];

    protected $casts = [
        'finalized_at' => 'datetime',
        'total_units' => 'decimal:2',
        'total_load' => 'decimal:3',
    ];

    public function employee() { return $this->belongsTo(Employee::class, 'employee_id', 'employee_id'); }
    public function schoolYear() { return $this->belongsTo(SchoolYear::class); }
    public function items() { return $this->hasMany(TeacherLoadItem::class)->orderBy('position'); }

    public function scopeVisibleTo($query, User $viewer)
    {
        if ($viewer->isDean()) return $query;
        if ($viewer->isProgramCoordinator()) {
            return $query->where('program', $viewer->employee?->program ?? '__unassigned__');
        }
        if ($viewer->isFaculty()) {
            return $query->where('employee_id', $viewer->employee?->employee_id ?? 0)->where('status', 'finalized');
        }
        return $query->whereRaw('1 = 0');
    }
}
