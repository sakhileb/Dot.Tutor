<?php

use App\Http\Controllers\Auth\EcosystemAuthController;
use Illuminate\Support\Facades\Route;


Route::get('/auth/ecosystem', [EcosystemAuthController::class, 'handle'])->name('ecosystem.auth');
Route::get('/', function () {
    return view('welcome');
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        $totalSessions     = \App\Models\TutorSession::count();
        $upcomingSessions  = \App\Models\TutorSession::whereIn('status', ['pending', 'confirmed'])
                                ->where('starts_at', '>=', now())->count();
        $completedSessions = \App\Models\TutorSession::where('status', 'completed')->count();
        $totalTutors       = \App\Models\TutorProfile::where('status', 'approved')->count();
        $availableTutors   = \App\Models\TutorProfile::where('status', 'approved')->count();
        $totalRevenue      = \App\Models\TutorSession::where('status', 'completed')
                                ->selectRaw('COALESCE(SUM(rate * duration_minutes / 60.0), 0) as rev')
                                ->value('rev') ?? 0;
        $subjects          = \App\Models\Subject::withCount('tutors')->orderBy('name')->get();

        // Scope the session lists to the signed-in user's own bookings (as student)
        // or their own tutor profile's sessions (as tutor). The KPI counts above stay
        // platform-wide aggregates (no PII), but per-session rows — tutor/student
        // names, subjects, times, amounts — must not be visible to every logged-in
        // user regardless of role; there is no admin/staff role in this schema yet
        // to justify a system-wide view here.
        $userId = auth()->id();
        $scopeToOwnSessions = function ($query) use ($userId) {
            $query->where('student_id', $userId)
                ->orWhereHas('tutorProfile', fn ($q) => $q->where('user_id', $userId));
        };

        $upcomingSessionsList = \App\Models\TutorSession::with(['tutorProfile.user', 'subject'])
                                ->where($scopeToOwnSessions)
                                ->whereIn('status', ['pending', 'confirmed'])
                                ->where('starts_at', '>=', now())
                                ->orderBy('starts_at')
                                ->limit(5)
                                ->get();
        $recentSessions    = \App\Models\TutorSession::with(['tutorProfile.user', 'subject'])
                                ->where($scopeToOwnSessions)
                                ->latest('starts_at')->limit(8)->get();

        return view('dashboard', compact(
            'totalSessions', 'upcomingSessions', 'completedSessions',
            'totalTutors', 'availableTutors', 'totalRevenue',
            'subjects', 'upcomingSessionsList', 'recentSessions'
        ));
    })->name('dashboard');
});
