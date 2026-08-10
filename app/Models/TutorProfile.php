<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class TutorProfile extends Model
{
    protected $fillable = [
        'user_id', 'bio', 'hourly_rate', 'rating', 'total_sessions', 'status',
        'reviewed_by', 'reviewed_at', 'rejected_reason',
    ];

    protected $casts = [
        'hourly_rate' => 'decimal:2',
        'rating' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class, 'tutor_subject');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(TutorSession::class);
    }

    public function ratings(): HasManyThrough
    {
        return $this->hasManyThrough(SessionRating::class, TutorSession::class, 'tutor_profile_id', 'session_id');
    }

    /** Recompute the average rating from every review this tutor has received. */
    public function refreshRating(): void
    {
        $this->update(['rating' => $this->ratings()->avg('rating') ?? 0]);
    }
}
