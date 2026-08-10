<?php

namespace Tests\Feature;

use App\Models\Subject;
use App\Models\TutorProfile;
use App\Models\TutorSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SessionRatingTest extends TestCase
{
    use RefreshDatabase;

    private function completedSession(array $overrides = []): array
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
            'status' => 'completed',
            'starts_at' => now()->subHour(),
            'duration_minutes' => 60,
            'rate' => 45,
        ], $overrides));

        return [$session, $tutorProfile, $tutorUser, $student];
    }

    public function test_the_student_can_rate_a_completed_session(): void
    {
        [$session, , , $student] = $this->completedSession();

        $response = $this->actingAs($student)->post(route('sessions.rating.store', $session), [
            'rating' => 5,
            'review' => 'Excellent tutor, very patient.',
        ]);

        $response->assertRedirect(route('sessions.show', $session));
        $this->assertDatabaseHas('session_ratings', [
            'session_id' => $session->id,
            'rated_by' => $student->id,
            'rating' => 5,
            'review' => 'Excellent tutor, very patient.',
        ]);
    }

    public function test_rating_a_session_updates_the_tutors_average_rating(): void
    {
        [$sessionA, $tutorProfile, , $studentA] = $this->completedSession();
        $this->actingAs($studentA)->post(route('sessions.rating.store', $sessionA), ['rating' => 5]);

        $studentB = User::factory()->withPersonalTeam()->create();
        $sessionB = TutorSession::create([
            'tutor_profile_id' => $tutorProfile->id,
            'student_id' => $studentB->id,
            'subject_id' => $sessionA->subject_id,
            'status' => 'completed',
            'starts_at' => now()->subHour(),
            'duration_minutes' => 60,
            'rate' => 45,
        ]);
        $this->actingAs($studentB)->post(route('sessions.rating.store', $sessionB), ['rating' => 3]);

        $this->assertEquals(4.00, $tutorProfile->fresh()->rating);
    }

    public function test_the_tutor_cannot_rate_their_own_session(): void
    {
        [$session, , $tutorUser] = $this->completedSession();

        $response = $this->actingAs($tutorUser)->post(route('sessions.rating.store', $session), ['rating' => 5]);

        $response->assertForbidden();
        $this->assertDatabaseCount('session_ratings', 0);
    }

    public function test_a_pending_session_cannot_be_rated(): void
    {
        [$session, , , $student] = $this->completedSession(['status' => 'pending']);

        $response = $this->actingAs($student)->post(route('sessions.rating.store', $session), ['rating' => 5]);

        $response->assertForbidden();
    }

    public function test_a_session_cannot_be_rated_twice(): void
    {
        [$session, , , $student] = $this->completedSession();
        $this->actingAs($student)->post(route('sessions.rating.store', $session), ['rating' => 5]);

        $response = $this->actingAs($student)->post(route('sessions.rating.store', $session), ['rating' => 1]);

        $response->assertForbidden();
        $this->assertDatabaseCount('session_ratings', 1);
    }

    public function test_rating_must_be_between_1_and_5(): void
    {
        [$session, , , $student] = $this->completedSession();

        $response = $this->actingAs($student)->post(route('sessions.rating.store', $session), ['rating' => 6]);

        $response->assertSessionHasErrors('rating');
    }

    public function test_an_outsider_cannot_rate_a_session(): void
    {
        [$session] = $this->completedSession();
        $outsider = User::factory()->withPersonalTeam()->create();

        $response = $this->actingAs($outsider)->post(route('sessions.rating.store', $session), ['rating' => 5]);

        $response->assertForbidden();
    }

    public function test_a_review_with_text_is_surfaced_on_the_tutors_public_profile(): void
    {
        [$session, $tutorProfile, , $student] = $this->completedSession();
        $this->actingAs($student)->post(route('sessions.rating.store', $session), [
            'rating' => 5,
            'review' => 'Fantastic explanations.',
        ]);

        $response = $this->actingAs($student)->get(route('tutors.show', $tutorProfile));

        $response->assertOk();
        $response->assertSee('Fantastic explanations.');
    }
}
