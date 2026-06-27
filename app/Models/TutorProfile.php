<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TutorProfile extends Model
{
    protected $fillable = ['user_id', 'bio', 'hourly_rate', 'rating', 'total_sessions', 'status'];

    protected $casts = [
        'hourly_rate' => 'decimal:2',
        'rating'      => 'decimal:2',
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
}
