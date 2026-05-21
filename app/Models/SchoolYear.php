<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SchoolYear extends Model
{
    protected $fillable = [
        'name',
        'start_year',
        'end_year',
        'is_active',
        'archived_at',
        'archived_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'archived_at' => 'datetime',
    ];

    public function archivedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'archived_by');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'school_year_id');
    }

    public function teachingGuides(): HasMany
    {
        return $this->hasMany(TeachingGuide::class, 'school_year_id');
    }

    public function examQuestionnaires(): HasMany
    {
        return $this->hasMany(ExamQuestionnaire::class, 'school_year_id');
    }

    public function folders(): HasMany
    {
        return $this->hasMany(Folder::class, 'school_year_id');
    }

    public static function active(): ?self
    {
        return static::where('is_active', true)->first();
    }

    public static function activeId(): ?int
    {
        return static::where('is_active', true)->value('id');
    }

    /**
     * School years for filter dropdowns (newest first). Keys are start_year strings.
     *
     * @return array<string, string>
     */
    public static function filterOptions(): array
    {
        return static::orderByDesc('start_year')
            ->get()
            ->mapWithKeys(fn (self $sy) => [
                (string) $sy->start_year => $sy->is_active
                    ? $sy->name . ' (Current)'
                    : $sy->name,
            ])
            ->all();
    }

    public static function labelForStartYear(int $startYear): string
    {
        $record = static::where('start_year', $startYear)->first();

        return $record?->name ?? ('S.Y. ' . $startYear . '-' . ($startYear + 1));
    }

    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }

    public function scopeArchived($query)
    {
        return $query->whereNotNull('archived_at')->orderByDesc('start_year');
    }
}
