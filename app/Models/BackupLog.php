<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BackupLog extends Model
{
    protected $primaryKey = 'backup_log_id';

    protected $fillable = [
        'user_id',
        'filename',
        'file_size',
        'type',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function getFormattedSize(): string
    {
        $bytes = $this->file_size;
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        }
        return number_format($bytes / 1024, 2) . ' KB';
    }
}
