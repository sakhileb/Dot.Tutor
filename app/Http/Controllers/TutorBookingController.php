<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use App\Models\TutorProfile;
use App\Models\TutorSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class TutorBookingController extends Controller
{
    /**
     * Browse approved tutors, optionally filtered to one subject.
     */
    public function browse(Request $request): View
    {
        $subjects = Subject::orderBy('name')->get();

        $selectedSubjectId = $request->integer('subject');

        $tutors = TutorProfile::query()
            ->with(['user', 'subjects'])
            ->where('status', 'approved')
            ->when($selectedSubjectId, fn ($query) => $query->whereHas(
                'subjects',
                fn ($q) => $q->where('subjects.id', $selectedSubjectId),
            ))
            ->orderByDesc('rating')
            ->paginate(12);

        return view('tutors.browse', [
            'tutors' => $tutors,
            'subjects' => $subjects,
            'selectedSubjectId' => $selectedSubjectId,
        ]);
    }

    /**
     * One tutor's public profile plus the booking form.
     */
    public function show(TutorProfile $tutorProfile): View
    {
        abort_unless($tutorProfile->status === 'approved', 404);

        $tutorProfile->load(['user', 'subjects']);

        $reviews = $tutorProfile->ratings()
            ->whereNotNull('review')
            ->with('rater')
            ->latest()
            ->limit(20)
            ->get();

        return view('tutors.show', [
            'tutorProfile' => $tutorProfile,
            'reviews' => $reviews,
        ]);
    }

    /**
     * Create a session booking. A student cannot book their own tutor
     * profile, the chosen subject must actually be one this tutor teaches,
     * and the requested time must be in the future — all real constraints
     * from the schema and domain, not placeholder validation.
     */
    public function store(Request $request, TutorProfile $tutorProfile): RedirectResponse
    {
        abort_unless($tutorProfile->status === 'approved', 404);
        abort_if($tutorProfile->user_id === $request->user()->id, 403, 'You cannot book a session with yourself.');

        $validated = $request->validate([
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'starts_at' => ['required', 'date', 'after:now'],
            'duration_minutes' => ['required', 'integer', 'in:30,60,90,120'],
            'delivery' => ['required', 'in:online,in_person'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        abort_unless(
            $tutorProfile->subjects()->where('subjects.id', $validated['subject_id'])->exists(),
            422,
            'This tutor does not teach the selected subject.',
        );

        $session = TutorSession::create([
            'tutor_profile_id' => $tutorProfile->id,
            'student_id' => $request->user()->id,
            'subject_id' => $validated['subject_id'],
            'status' => 'pending',
            'delivery' => $validated['delivery'],
            'starts_at' => $validated['starts_at'],
            'duration_minutes' => $validated['duration_minutes'],
            'rate' => $tutorProfile->hourly_rate,
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()
            ->route('sessions.show', $session)
            ->with('status', 'Session requested — the tutor will confirm shortly.');
    }

    public function showSession(TutorSession $tutorSession): View
    {
        Gate::authorize('view', $tutorSession);

        $tutorSession->load(['tutorProfile.user', 'subject', 'student', 'rating', 'resources.uploader']);

        return view('sessions.show', [
            'session' => $tutorSession,
        ]);
    }

    public function cancel(TutorSession $tutorSession): RedirectResponse
    {
        Gate::authorize('cancel', $tutorSession);

        $tutorSession->update(['status' => 'cancelled']);

        return redirect()
            ->route('sessions.show', $tutorSession)
            ->with('status', 'Session cancelled.');
    }

    /**
     * The tutor accepts a pending booking request.
     */
    public function confirm(TutorSession $tutorSession): RedirectResponse
    {
        Gate::authorize('confirm', $tutorSession);

        $tutorSession->update(['status' => 'confirmed']);

        return redirect()
            ->route('sessions.show', $tutorSession)
            ->with('status', 'Session confirmed.');
    }

    /**
     * Either party marks a confirmed session as having happened. This is
     * what makes leaving a rating/review possible — see SessionRatingController.
     */
    public function complete(TutorSession $tutorSession): RedirectResponse
    {
        Gate::authorize('complete', $tutorSession);

        $tutorSession->update(['status' => 'completed']);

        $tutorSession->tutorProfile->increment('total_sessions');

        return redirect()
            ->route('sessions.show', $tutorSession)
            ->with('status', 'Session marked complete.');
    }
}
