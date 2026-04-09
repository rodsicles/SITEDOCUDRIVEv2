<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProfessionalDevelopment extends Model
{
    protected $primaryKey = 'professional_development_id';

    protected $fillable = [
        'user_id',
        'seminar_name',
        'date_attended',
        'organizer',
        'hours',
        'certificate_path',
    ];

    protected $casts = [
        'date_attended' => 'date',
        'hours' => 'decimal:1',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function hasCertificate(): bool
    {
        return !is_null($this->certificate_path);
    }
}
