<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    use HasFactory;

    protected $table = 'reports';
    protected $primaryKey = 'report_id';

    public const CATEGORIES = [
        'Accomplishment Report',
        'Incident Report',
        'Inventory Report',
        'Research Report',
        'Other',
    ];

    protected $fillable = [
        'submitted_by',
        'report_title',
        'report_category',
        'file_path',
        'original_filename',
        'description',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function submitter()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function displayFilename(): string
    {
        return \App\Support\DocumentNaming::downloadFilename($this->report_title ?: 'Report file', (string) $this->file_path, $this->original_filename);
    }

    public function canBeAccessedBy(User $user): bool
    {
        if ((int) $this->submitted_by === (int) $user->id) {
            return true;
        }

        return $user->isDeanOrSecretary() || $user->isProgramCoordinator();
    }
}
