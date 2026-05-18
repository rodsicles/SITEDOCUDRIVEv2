<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    public const DEPT_IT = 'Information Technology';

    public const DEPT_ENGINEERING = 'Engineering';

    protected $fillable = [
        'code',
        'title',
        'department',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForDepartment($query, ?string $department)
    {
        if (!$department) {
            return $query;
        }

        return $query->where('department', $department);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('code');
    }

    public function label(): string
    {
        return $this->code . ' – ' . $this->title;
    }
}
