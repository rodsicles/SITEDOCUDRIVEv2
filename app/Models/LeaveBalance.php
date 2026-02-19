<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveBalance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'year',
        'sick_leave_balance',
        'vacation_leave_balance',
        'sick_leave_used',
        'vacation_leave_used',
    ];

    protected $casts = [
        'sick_leave_balance' => 'decimal:1',
        'vacation_leave_balance' => 'decimal:1',
        'sick_leave_used' => 'decimal:1',
        'vacation_leave_used' => 'decimal:1',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Get or create balance for current year
    public static function getOrCreateBalance($userId, $year = null)
    {
        $year = $year ?? date('Y');
        
        return self::firstOrCreate(
            ['user_id' => $userId, 'year' => $year],
            [
                'sick_leave_balance' => 15.0,
                'vacation_leave_balance' => 15.0,
                'sick_leave_used' => 0,
                'vacation_leave_used' => 0,
            ]
        );
    }

    // Get remaining sick leave
    public function getRemainingSickLeave()
    {
        return $this->sick_leave_balance - $this->sick_leave_used;
    }

    // Get remaining vacation leave
    public function getRemainingVacationLeave()
    {
        return $this->vacation_leave_balance - $this->vacation_leave_used;
    }

    // Deduct leave
    public function deductLeave($leaveType, $days)
    {
        if (str_contains($leaveType, 'Sick')) {
            $this->sick_leave_used += $days;
        } else {
            $this->vacation_leave_used += $days;
        }
        $this->save();
    }

    // Restore leave (when rejected)
    public function restoreLeave($leaveType, $days)
    {
        if (str_contains($leaveType, 'Sick')) {
            $this->sick_leave_used = max(0, $this->sick_leave_used - $days);
        } else {
            $this->vacation_leave_used = max(0, $this->vacation_leave_used - $days);
        }
        $this->save();
    }
}
