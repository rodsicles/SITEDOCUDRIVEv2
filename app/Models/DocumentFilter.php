<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentFilter extends Model
{
    use HasFactory;

    protected $primaryKey = 'document_filter_id';

    protected $fillable = [
        'user_id',
        'name',
        'filters',
    ];

    protected $casts = [
        'filters' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function toQueryParams(): array
    {
        return array_filter($this->filters ?? [], static fn ($value) => $value !== null && $value !== '');
    }
}