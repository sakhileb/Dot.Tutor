<?php

namespace App\Http\Controllers;

use App\Models\SessionRating;
use App\Models\TutorSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class SessionRatingController extends Controller
{
    /**
     * The student leaves a rating + optional review on a completed session
     * they booked. See TutorSessionPolicy::rate() for why only the student
     * (not the tutor) can do this.
     */
    public function store(Request $request, TutorSession $tutorSession): RedirectResponse
    {
        Gate::authorize('rate', $tutorSession);

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'review' => ['nullable', 'string', 'max:2000'],
        ]);

        SessionRating::create([
            'session_id' => $tutorSession->id,
            'rated_by' => $request->user()->id,
            'rating' => $validated['rating'],
            'review' => $validated['review'] ?? null,
        ]);

        $tutorSession->tutorProfile->refreshRating();

        return redirect()
            ->route('sessions.show', $tutorSession)
            ->with('status', 'Thanks for your review!');
    }
}
