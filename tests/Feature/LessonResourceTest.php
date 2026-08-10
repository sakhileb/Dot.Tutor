<?php

namespace Tests\Feature;

use App\Models\LessonResource;
use App\Models\Subject;
use App\Models\TutorProfile;
use App\Models\TutorSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LessonResourceTest extends TestCase
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
            'status' => 'confirmed',
            'starts_at' => now()->addDay(),
            'duration_minutes' => 60,
            'rate' => 45,
        ], $overrides));

        return [$session, $tutorProfile, $tutorUser, $student];
    }

    public function test_the_tutor_can_upload_a_resource_to_a_confirmed_session(): void
    {
        Storage::fake('lesson-resources');
        [$session, , $tutorUser] = $this->bookedSession();

        $response = $this->actingAs($tutorUser)->post(route('resources.store', $session), [
            'title' => 'Practice worksheet',
            'file' => UploadedFile::fake()->create('worksheet.pdf', 100, 'application/pdf'),
        ]);

        $response->assertRedirect(route('sessions.show', $session));
        $this->assertDatabaseHas('lesson_resources', [
            'session_id' => $session->id,
            'uploader_id' => $tutorUser->id,
            'title' => 'Practice worksheet',
        ]);
        Storage::disk('lesson-resources')->assertExists(LessonResource::first()->file_path);
    }

    public function test_the_student_can_also_upload_a_resource(): void
    {
        Storage::fake('lesson-resources');
        [$session, , , $student] = $this->bookedSession();

        $response = $this->actingAs($student)->post(route('resources.store', $session), [
            'title' => 'My notes',
            'file' => UploadedFile::fake()->create('notes.pdf', 50, 'application/pdf'),
        ]);

        $response->assertRedirect(route('sessions.show', $session));
        $this->assertDatabaseCount('lesson_resources', 1);
    }

    public function test_a_resource_cannot_be_uploaded_to_a_pending_session(): void
    {
        Storage::fake('lesson-resources');
        [$session, , $tutorUser] = $this->bookedSession(['status' => 'pending']);

        $response = $this->actingAs($tutorUser)->post(route('resources.store', $session), [
            'title' => 'Too early',
            'file' => UploadedFile::fake()->create('worksheet.pdf', 100, 'application/pdf'),
        ]);

        $response->assertForbidden();
        $this->assertDatabaseCount('lesson_resources', 0);
    }

    public function test_an_outsider_cannot_upload_a_resource(): void
    {
        Storage::fake('lesson-resources');
        [$session] = $this->bookedSession();
        $outsider = User::factory()->withPersonalTeam()->create();

        $response = $this->actingAs($outsider)->post(route('resources.store', $session), [
            'title' => 'Not yours',
            'file' => UploadedFile::fake()->create('worksheet.pdf', 100, 'application/pdf'),
        ]);

        $response->assertForbidden();
    }

    public function test_a_participant_can_download_a_resource(): void
    {
        Storage::fake('lesson-resources');
        [$session, , $tutorUser, $student] = $this->bookedSession();
        $this->actingAs($tutorUser)->post(route('resources.store', $session), [
            'title' => 'Practice worksheet',
            'file' => UploadedFile::fake()->create('worksheet.pdf', 100, 'application/pdf'),
        ]);
        $resource = LessonResource::first();

        $response = $this->actingAs($student)->get(route('resources.download', $resource));

        $response->assertOk();
        $response->assertHeader('Content-Disposition');
    }

    public function test_an_outsider_cannot_download_a_resource(): void
    {
        Storage::fake('lesson-resources');
        [$session, , $tutorUser] = $this->bookedSession();
        $this->actingAs($tutorUser)->post(route('resources.store', $session), [
            'title' => 'Practice worksheet',
            'file' => UploadedFile::fake()->create('worksheet.pdf', 100, 'application/pdf'),
        ]);
        $resource = LessonResource::first();
        $outsider = User::factory()->withPersonalTeam()->create();

        $response = $this->actingAs($outsider)->get(route('resources.download', $resource));

        $response->assertForbidden();
    }

    public function test_the_uploader_can_delete_their_own_resource(): void
    {
        Storage::fake('lesson-resources');
        [$session, , $tutorUser] = $this->bookedSession();
        $this->actingAs($tutorUser)->post(route('resources.store', $session), [
            'title' => 'Practice worksheet',
            'file' => UploadedFile::fake()->create('worksheet.pdf', 100, 'application/pdf'),
        ]);
        $resource = LessonResource::first();

        $response = $this->actingAs($tutorUser)->delete(route('resources.destroy', $resource));

        $response->assertRedirect(route('sessions.show', $session));
        $this->assertDatabaseCount('lesson_resources', 0);
        Storage::disk('lesson-resources')->assertMissing($resource->file_path);
    }

    public function test_a_non_uploading_participant_cannot_delete_a_resource_they_did_not_upload(): void
    {
        Storage::fake('lesson-resources');
        [$session, , $tutorUser, $student] = $this->bookedSession();
        $this->actingAs($tutorUser)->post(route('resources.store', $session), [
            'title' => 'Practice worksheet',
            'file' => UploadedFile::fake()->create('worksheet.pdf', 100, 'application/pdf'),
        ]);
        $resource = LessonResource::first();

        $response = $this->actingAs($student)->delete(route('resources.destroy', $resource));

        $response->assertForbidden();
        $this->assertDatabaseCount('lesson_resources', 1);
        Storage::disk('lesson-resources')->assertExists($resource->file_path);
    }
}
