<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeacherLoadRevision extends Model
{
    protected $guarded = [];
    protected $casts = ['snapshot' => 'array'];

    public function actor() { return $this->belongsTo(User::class, 'changed_by'); }
}
