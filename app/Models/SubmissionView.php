<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** First time a reviewer (Dean office or Coordinator) opened a submission version. */
class SubmissionView extends Model
{
    protected $fillable = ['submission_type', 'submission_id', 'user_id', 'role_name', 'version', 'viewed_at'];

    protected $casts = ['viewed_at' => 'datetime', 'version' => 'integer'];

    public function viewer()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
