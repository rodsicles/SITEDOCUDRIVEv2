<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemResetEvent extends Model
{
    protected $fillable = ['scope', 'reset_at', 'closed_metrics', 'reset_by'];

    protected function casts(): array
    {
        return ['reset_at' => 'datetime', 'closed_metrics' => 'array'];
    }
}
