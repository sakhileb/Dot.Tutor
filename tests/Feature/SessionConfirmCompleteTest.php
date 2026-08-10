<?php

namespace Tests\Feature;

use App\Models\Subject;
use App\Models\TutorProfile;
use App\Models\TutorSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SessionConfirmCompleteTest extends TestCase
{
    use RefreshDatabase;

    private function bookedSession(array $overrides = []): array
    {
        $subject = Subject::create(['name' => 'Algebra', 'level' => 'high_school']);
        $tutorUser = User::factory()->withPersonalTeam()->create(['name' => 'Tutor']);
        $tutorProfile = TutorProfile::create([
            'user_id' => $tutorUser->id,
            'hourly_rate' => 45,
            'status' => 'approved',
        ]);
        $tutorProfile->subjects()->attach($subject->id);
        $student = User::factory()->withPersonalTeam()->create(['name' => 'Student']);

        $session = TutorSession::create(array_merge([
            'tutor_profile_id' => $tutorProfile->id,
            'student_id' => $student->id,
            'subject_id' => $subject->id,
            'status' => 'pending',
            'starts_at' => now()->addDay(),
            'duration_minutes' => 60,
            'rate' => 45,
        ], $overrides));

        return [$session, $tutorProfile, $tutorUser, $student];
    }

    // -----------------------------------------------------------------------
    // Confirm
    // -----------------------------------------------------------------------

    public function test_the_tutor_can_confirm_a_pending_session(): void
    {
        [$session, , $tutorUser] = $this->bookedSession();

        $response = $this->actingAs($tutorUser)->post(route('sessions.confirm', $session));

        $response->assertRedirect(route('sessions.show', $session));
        $this->assertSame('confirmed', $session->fresh()->status);
    }

    public function test_the_student_cannot_confirm_their_own_booking(): void
    {
        [$session, , , $student] = $this->bookedSession();

        $response = $this->actingAs($student)->post(route('sessions.confirm', $session));

        $response->assertForbidden();
        $this->assertSame('pending', $session->fresh()->status);
    }

    public function test_an_outsider_cannot_confirm_a_session(): void
    {
        [$session] = $this->bookedSession();
        $outsider = User::factory()->withPersonalTeam()->create();

        $response = $this->actingAs($outsider)->post(route('sessions.confirm', $session));

        $response->assertForbidden();
    }

    public function test_an_already_confirmed_session_cannot_be_confirmed_again(): void
    {
        [$session, , $tutorUser] = $this->bookedSession(['status' => 'confirmed']);

        $response = $this->actingAs($tutorUser)->post(route('sessions.confirm', $session));

        $response->assertForbidden();
    }

    // -----------------------------------------------------------------------
    // Complete
    // -----------------------------------------------------------------------

    public function test_either_party_can_mark_a_confirmed_past_session_completed(): void
    {
        [$session, , $tutorUser] = $this->bookedSession([
            'status' => 'confirmed',
            'starts_at' => now()->subHour(),
        ]);

        $response = $this->actingAs($tutorUser)->post(route('sessions.complete', $session));

        $response->assertRedirect(route('sessions.show', $session));
        $this->assertSame('completed', $session->fresh()->status);
    }

    public function test_the_student_can_also_mark_a_confirmed_past_session_completed(): void
    {
        [$session, , , $student] = $this->bookedSession([
            'status' => 'confirmed',
            'starts_at' => now()->subHour(),
        ]);

        $response = $this->actingAs($student)->post(route('sessions.complete', $session));

        $response->assertRedirect(route('sessions.show', $session));
        $this->assertSame('completed', $session->fresh()->status);
    }

    public function test_a_session_that_has_not_started_yet_cannot_be_marked_completed(): void
    {
        [$session, , $tutorUser] = $this->bookedSession([
            'status' => 'confirmed',
            'starts_at' => now()->addDay(),
        ]);

        $response = $this->actingAs($tutorUser)->post(route('sessions.complete', $session));

        $response->assertForbidden();
        $this->assertSame('confirmed', $session->fresh()->status);
    }

    public function test_a_pending_session_cannot_be_marked_completed(): void
    {
        [$session, , $tutorUser] = $this->bookedSession(['starts_at' => now()->subHour()]);

        $response = $this->actingAs($tutorUser)->post(route('sessions.complete', $session));

        $response->assertForbidden();
    }

    public function test_completing_a_session_increments_the_tutors_total_sessions(): void
    {
        [$session, $tutorProfile, $tutorUser] = $this->bookedSession([
            'status' => 'confirmed',
            'starts_at' => now()->subHour(),
        ]);

        $this->actingAs($tutorUser)->post(route('sessions.complete', $session));

        $this->assertSame(1, $tutorProfile->fresh()->total_sessions);
    }

    public function test_an_outsider_cannot_complete_a_session(): void
    {
        [$session] = $this->bookedSession(['status' => 'confirmed', 'starts_at' => now()->subHour()]);
        $outsider = User::factory()->withPersonalTeam()->create();

        $response = $this->actingAs($outsider)->post(route('sessions.complete', $session));

        $response->assertForbidden();
    }
}
