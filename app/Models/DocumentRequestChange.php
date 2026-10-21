<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentRequestChange extends Model
{
    protected $guarded = [];
    protected $casts = ['before' => 'array', 'after' => 'array'];

    public function actor() { return $this->belongsTo(User::class, 'actor_id'); }
}
