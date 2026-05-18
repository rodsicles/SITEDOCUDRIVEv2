<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentView extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'document_id',
        'viewed_at',
    ];

    protected $casts = [
        'viewed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function document()
    {
        return $this->belongsTo(Document::class, 'document_id', 'document_id');
    }

    // Track a document view
    public static function trackView($userId, $documentId)
    {
        return self::create([
            'user_id' => $userId,
            'document_id' => $documentId,
            'viewed_at' => now(),
        ]);
    }

    // Get recent documents for a user
    public static function getRecentDocuments($userId, $limit = 10)
    {
        return self::where('user_id', $userId)
            ->with('document')
            ->orderBy('viewed_at', 'desc')
            ->limit($limit)
            ->get()
            ->pluck('document')
            ->unique('document_id');
    }
}
