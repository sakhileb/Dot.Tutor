<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

class TutorSession extends Model
{
    protected $table = 'tutor_sessions';

    protected $fillable = [
        'tutor_profile_id', 'student_id', 'subject_id',
        'status', 'delivery', 'starts_at', 'duration_minutes', 'rate', 'notes',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'rate' => 'decimal:2',
    ];

    public function tutorProfile(): BelongsTo
    {
        return $this->belongsTo(TutorProfile::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function resources(): HasMany
    {
        return $this->hasMany(LessonResource::class, 'session_id');
    }

    public function rating(): HasOne
    {
        return $this->hasOne(SessionRating::class, 'session_id');
    }

    public function endsAt(): Carbon
    {
        return $this->starts_at->addMinutes($this->duration_minutes);
    }
}
