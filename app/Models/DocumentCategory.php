<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentCategory extends Model
{
    protected $primaryKey = 'category_id';
    
    protected $fillable = [
        'category_name',
        'color',
    ];

    public function documents()
    {
        return $this->hasMany(Document::class, 'category_id', 'category_id');
    }
}
