<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SessionRating extends Model
{
    protected $fillable = ['session_id', 'rated_by', 'rating', 'review'];

    public function session(): BelongsTo
    {
        return $this->belongsTo(TutorSession::class, 'session_id');
    }

    public function rater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rated_by');
    }
}
