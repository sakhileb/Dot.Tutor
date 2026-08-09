<?php

namespace Tests\Feature;

use App\Console\Commands\ExpireStaleSessions;
use App\Models\Subject;
use App\Models\TutorProfile;
use App\Models\TutorSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpireStaleSessionsCommandTest extends TestCase
{
    use RefreshDatabase;

    private function makeSession(string $status, string $startsAt, int $durationMinutes = 60): TutorSession
    {
        $subject = Subject::create(['name' => 'Algebra', 'level' => 'high_school']);
        $tutorUser = User::factory()->withPersonalTeam()->create();
        $tutorProfile = TutorProfile::create([
            'user_id' => $tutorUser->id,
            'hourly_rate' => 45,
            'status' => 'approved',
        ]);
        $student = User::factory()->withPersonalTeam()->create();

        return TutorSession::create([
            'tutor_profile_id' => $tutorProfile->id,
            'student_id' => $student->id,
            'subject_id' => $subject->id,
            'status' => $status,
            'delivery' => 'online',
            'starts_at' => $startsAt,
            'duration_minutes' => $durationMinutes,
            'rate' => 45,
        ]);
    }

    public function test_a_pending_session_past_its_end_time_becomes_no_show(): void
    {
        $session = $this->makeSession('pending', now()->subHours(3)->toDateTimeString(), 60);

        $this->artisan(ExpireStaleSessions::class)->assertSuccessful();

        $this->assertSame('no_show', $session->fresh()->status);
    }

    public function test_a_pending_session_still_in_the_future_is_untouched(): void
    {
        $session = $this->makeSession('pending', now()->addDay()->toDateTimeString(), 60);

        $this->artisan(ExpireStaleSessions::class)->assertSuccessful();

        $this->assertSame('pending', $session->fresh()->status);
    }

    public function test_a_confirmed_session_past_its_end_time_is_untouched(): void
    {
        $session = $this->makeSession('confirmed', now()->subHours(3)->toDateTimeString(), 60);

        $this->artisan(ExpireStaleSessions::class)->assertSuccessful();

        $this->assertSame('confirmed', $session->fresh()->status);
    }

    public function test_a_completed_session_past_its_end_time_is_untouched(): void
    {
        $session = $this->makeSession('completed', now()->subHours(3)->toDateTimeString(), 60);

        $this->artisan(ExpireStaleSessions::class)->assertSuccessful();

        $this->assertSame('completed', $session->fresh()->status);
    }

    public function test_a_cancelled_session_past_its_end_time_is_untouched(): void
    {
        $session = $this->makeSession('cancelled', now()->subHours(3)->toDateTimeString(), 60);

        $this->artisan(ExpireStaleSessions::class)->assertSuccessful();

        $this->assertSame('cancelled', $session->fresh()->status);
    }
}
