<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MonthlyAnalyticsSnapshot extends Model
{
    protected $fillable = ['period_start', 'period_end', 'metrics', 'closed_by'];

    protected function casts(): array
    {
        return ['period_start' => 'date', 'period_end' => 'date', 'metrics' => 'array'];
    }

    public function closer()
    {
        return $this->belongsTo(User::class, 'closed_by');
    }
}
