<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DashboardLog extends Model
{
    use HasFactory;

    protected $primaryKey = 'log_id';
    
    protected $fillable = [
        'user_id',
        'target_user_id',
        'activity',
        'activity_type',
        'visibility',
        'ip_address',
        'log_date',
    ];

    protected $casts = [
        'log_date' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (DashboardLog $log) {
            if (empty($log->ip_address) && request()) {
                $log->ip_address = request()->ip();
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function targetUser()
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }

    /**
     * Get filtered logs based on user role
     */
    public static function getFilteredLogs($user, $limit = 10)
    {
        $query = self::with(['user.employee', 'targetUser.employee']);

        if ($user->isDean()) {
            // Dean sees everything
            $query->latest('log_date');
        } elseif ($user->role_id === 2) { // Program Coordinator
            $coordinatorDept = optional($user->employee)->department;

            // Coordinator sees:
            // 1. Their own activities
            // 2. Activities where they are the target
            // 3. Faculty activities from the SAME DEPARTMENT only
            $query->where(function($q) use ($user, $coordinatorDept) {
                $q->where('user_id', $user->id)
                  ->orWhere('target_user_id', $user->id)
                  ->orWhereHas('user', function($subQ) use ($coordinatorDept) {
                      $subQ->where('role_id', 3);
                      if ($coordinatorDept) {
                          $subQ->whereHas('employee', function($empQ) use ($coordinatorDept) {
                              $empQ->where('department', $coordinatorDept);
                          });
                      }
                  });
            });
        } else { // Faculty
            // Faculty sees:
            // 1. Only their own activities
            // 2. Activities where they are the target (e.g., password reset by coordinator)
            $query->where(function($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhere('target_user_id', $user->id);
            });
        }

        return $query->latest('log_date')->limit($limit)->get();
    }
}
