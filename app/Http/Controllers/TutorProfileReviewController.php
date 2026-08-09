<?php

namespace App\Http\Controllers;

use App\Models\TutorProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TutorProfileReviewController extends Controller
{
    public function index(): View
    {
        $pendingProfiles = TutorProfile::query()
            ->with(['user', 'subjects'])
            ->where('status', 'pending')
            ->orderBy('created_at')
            ->get();

        return view('operator.tutor-profiles.index', [
            'pendingProfiles' => $pendingProfiles,
        ]);
    }

    public function approve(TutorProfile $tutorProfile): RedirectResponse
    {
        if ($tutorProfile->status !== 'pending') {
            return redirect()->route('operator.tutor-profiles.index')
                ->with('status', 'That profile has already been reviewed.');
        }

        $tutorProfile->update([
            'status' => 'approved',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        return redirect()->route('operator.tutor-profiles.index')
            ->with('status', 'Tutor profile approved.');
    }

    public function reject(Request $request, TutorProfile $tutorProfile): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:2000'],
        ]);

        if ($tutorProfile->status !== 'pending') {
            return redirect()->route('operator.tutor-profiles.index')
                ->with('status', 'That profile has already been reviewed.');
        }

        $tutorProfile->update([
            'status' => 'rejected',
            'rejected_reason' => $validated['reason'],
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        return redirect()->route('operator.tutor-profiles.index')
            ->with('status', 'Tutor profile rejected.');
    }
}
