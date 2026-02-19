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
        'log_date',
    ];

    protected $casts = [
        'log_date' => 'datetime',
    ];

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
            // Coordinator sees:
            // 1. Their own activities
            // 2. All faculty activities
            // 3. Activities where they are the target
            $query->where(function($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhere('target_user_id', $user->id)
                  ->orWhereHas('user', function($subQ) {
                      $subQ->where('role_id', 3); // Faculty activities
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
