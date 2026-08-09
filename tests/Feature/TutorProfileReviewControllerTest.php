<?php

namespace Tests\Feature;

use App\Models\TutorProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TutorProfileReviewControllerTest extends TestCase
{
    use RefreshDatabase;

    private function pendingProfile(): TutorProfile
    {
        $tutorUser = User::factory()->withPersonalTeam()->create();

        return TutorProfile::create([
            'user_id' => $tutorUser->id,
            'hourly_rate' => 40,
            'status' => 'pending',
        ]);
    }

    private function operator(): User
    {
        return User::factory()->withPersonalTeam()->create(['is_platform_operator' => true]);
    }

    public function test_operator_can_approve_a_pending_profile(): void
    {
        $profile = $this->pendingProfile();
        $operator = $this->operator();

        $response = $this->actingAs($operator)
            ->post("/operator/tutor-profiles/{$profile->id}/approve");

        $response->assertRedirect();
        $fresh = $profile->fresh();
        $this->assertSame('approved', $fresh->status);
        $this->assertSame($operator->id, $fresh->reviewed_by);
        $this->assertNotNull($fresh->reviewed_at);
    }

    public function test_approving_makes_the_tutor_visible_in_browse(): void
    {
        $profile = $this->pendingProfile();
        $operator = $this->operator();

        $this->actingAs($operator)->post("/operator/tutor-profiles/{$profile->id}/approve");

        $student = User::factory()->withPersonalTeam()->create();
        $this->actingAs($student)->get('/tutors')->assertOk()->assertSee($profile->user->name);
    }

    public function test_operator_can_reject_with_a_reason(): void
    {
        $profile = $this->pendingProfile();
        $operator = $this->operator();

        $response = $this->actingAs($operator)
            ->post("/operator/tutor-profiles/{$profile->id}/reject", [
                'reason' => 'Bio does not meet our verification requirements.',
            ]);

        $response->assertRedirect();
        $fresh = $profile->fresh();
        $this->assertSame('rejected', $fresh->status);
        $this->assertSame('Bio does not meet our verification requirements.', $fresh->rejected_reason);
        $this->assertSame($operator->id, $fresh->reviewed_by);
    }

    public function test_rejecting_without_a_reason_fails_validation(): void
    {
        $profile = $this->pendingProfile();
        $operator = $this->operator();

        $response = $this->actingAs($operator)
            ->post("/operator/tutor-profiles/{$profile->id}/reject", []);

        $response->assertSessionHasErrors('reason');
        $this->assertSame('pending', $profile->fresh()->status);
    }

    public function test_acting_on_a_profile_no_longer_pending_is_a_noop(): void
    {
        $profile = $this->pendingProfile();
        $profile->update(['status' => 'approved']); // already resolved by someone else
        $operator = $this->operator();

        $this->actingAs($operator)->post("/operator/tutor-profiles/{$profile->id}/reject", [
            'reason' => 'Too late.',
        ]);

        $this->assertSame('approved', $profile->fresh()->status);
        $this->assertNull($profile->fresh()->rejected_reason);
    }

    public function test_non_operator_is_blocked(): void
    {
        $profile = $this->pendingProfile();
        $regularUser = User::factory()->withPersonalTeam()->create(['is_platform_operator' => false]);

        $this->actingAs($regularUser)
            ->post("/operator/tutor-profiles/{$profile->id}/approve")
            ->assertForbidden();
    }

    public function test_operator_tutor_profiles_route_requires_authentication(): void
    {
        $this->get('/operator/tutor-profiles')->assertRedirect('/login');
    }
}
