<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SkillTag extends Model
{
    protected $primaryKey = 'skill_tag_id';

    protected $fillable = [
        'user_id',
        'tag_name',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
