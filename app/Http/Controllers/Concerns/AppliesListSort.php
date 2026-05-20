<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait AppliesListSort
{
    protected function applyListSort(Builder $query, ?string $sort, string $titleColumn = 'title'): Builder
    {
        return match ($sort) {
            'title_asc' => $query->orderBy($titleColumn)->orderByDesc('id'),
            'title_desc' => $query->orderByDesc($titleColumn)->orderByDesc('id'),
            'date_asc' => $query->orderBy('created_at')->orderBy('id'),
            default => $query->orderByDesc('created_at')->orderByDesc('id'),
        };
    }

    protected function normalizeListSort(?string $sort): string
    {
        return in_array($sort, ['title_asc', 'title_desc', 'date_asc', 'date_desc'], true)
            ? $sort
            : 'date_desc';
    }
}
