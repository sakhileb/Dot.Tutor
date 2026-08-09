# Session No-Show Expiry + Tutor Profile Approval Gate Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** A `pending` `TutorSession` whose scheduled time has passed becomes `no_show` automatically via a daily unattended command (no approval needed). Separately, a `pending` `TutorProfile` is no longer approved by hand — a platform operator reviews it on a new screen and approves (`status: approved`, the real effect) or rejects (`status: rejected`, reason required).

**Architecture:** Two independent pieces sharing no code, built in this order: (1) widen `tutor_sessions.status`, add a scheduled command that only ever reads `pending` sessions and writes `no_show` — no gate, matches the audit's own risk judgment; (2) new `is_platform_operator` flag, widen `tutor_profiles.status`, a plain Controller+Blade review screen (matching this repo's existing Controller-based convention — no Livewire component in active use despite being a dependency).

**Tech Stack:** Laravel 13 (`bootstrap/app.php` `withSchedule()`/`withMiddleware()`), plain Controllers + Blade (no Livewire component exists in this app despite the dependency), PHPUnit.

## Global Constraints

- `tutor_sessions.status` and `tutor_profiles.status` both widen from native enum to plain string via the portable-append technique (conditionally drop the Postgres CHECK constraint, then `Schema::table()->change()`; SQLite, used by this app's tests, has no such constraint and rebuilds the table wholesale) — copied verbatim from the spec.
- The session-expiry command never gates on anything — it's Level 1, unattended, matching the audit's own risk framing. Only tutor-profile review is gated.
- `rejected` is a new, distinct value from `suspended` on `tutor_profiles.status` — never conflate the two, per the design discussion.
- Reviewer authority is a new `users.is_platform_operator` flag (boolean, default `false`, excluded from `$fillable`) — this schema has no `team_id` or admin role to reuse.
- The review screen is a plain Controller + Blade views with standard `<form method="POST">` submissions, matching `TutorBookingController`'s existing style — not Livewire, which this app depends on but doesn't actually use anywhere yet.
- This repo's own `CLAUDE.md` states: "Do not create verification scripts or tinker when tests cover that functionality and prove they work." Task 3 relies on the automated test suite only.
- Every `git add` lists files explicitly, never `-A`/`.` — this repo had pre-existing unrelated uncommitted changes (`application-mark.blade.php`, `layouts/app.blade.php`, `welcome.blade.php`, mark images) stashed before this work started.

---

### Task 1: `no_show` status + `ExpireStaleSessions` command

**Files:**
- Create: `database/migrations/2026_08_09_000001_widen_tutor_sessions_status_column.php`
- Create: `app/Console/Commands/ExpireStaleSessions.php`
- Modify: `routes/console.php`
- Test: `tests/Feature/ExpireStaleSessionsCommandTest.php`

**Interfaces:**
- Produces: `tutor_sessions.status` accepting `'no_show'` in addition to its existing values; `tutor:expire-stale-sessions` Artisan command.

- [ ] **Step 1: Write the failing tests**

Create `tests/Feature/ExpireStaleSessionsCommandTest.php`:

```php
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

    private function session(string $status, string $startsAt, int $durationMinutes = 60): TutorSession
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
        $session = $this->session('pending', now()->subHours(3)->toDateTimeString(), 60);

        $this->artisan(ExpireStaleSessions::class)->assertSuccessful();

        $this->assertSame('no_show', $session->fresh()->status);
    }

    public function test_a_pending_session_still_in_the_future_is_untouched(): void
    {
        $session = $this->session('pending', now()->addDay()->toDateTimeString(), 60);

        $this->artisan(ExpireStaleSessions::class)->assertSuccessful();

        $this->assertSame('pending', $session->fresh()->status);
    }

    public function test_a_confirmed_session_past_its_end_time_is_untouched(): void
    {
        $session = $this->session('confirmed', now()->subHours(3)->toDateTimeString(), 60);

        $this->artisan(ExpireStaleSessions::class)->assertSuccessful();

        $this->assertSame('confirmed', $session->fresh()->status);
    }

    public function test_a_completed_session_past_its_end_time_is_untouched(): void
    {
        $session = $this->session('completed', now()->subHours(3)->toDateTimeString(), 60);

        $this->artisan(ExpireStaleSessions::class)->assertSuccessful();

        $this->assertSame('completed', $session->fresh()->status);
    }

    public function test_a_cancelled_session_past_its_end_time_is_untouched(): void
    {
        $session = $this->session('cancelled', now()->subHours(3)->toDateTimeString(), 60);

        $this->artisan(ExpireStaleSessions::class)->assertSuccessful();

        $this->assertSame('cancelled', $session->fresh()->status);
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test tests/Feature/ExpireStaleSessionsCommandTest.php`
Expected: FAIL — `App\Console\Commands\ExpireStaleSessions` doesn't exist, and `'no_show'` isn't a valid value for the native `tutor_sessions.status` enum yet.

- [ ] **Step 3: Write the migration**

Create `database/migrations/2026_08_09_000001_widen_tutor_sessions_status_column.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE tutor_sessions DROP CONSTRAINT IF EXISTS tutor_sessions_status_check');
        }

        Schema::table('tutor_sessions', function (Blueprint $table) {
            $table->string('status')->default('pending')->change();
        });
    }

    public function down(): void
    {
        // Widening a column to a plain string is not meaningfully reversible
        // back to a narrower native enum without knowing every value already
        // stored -- intentionally left as a no-op, matching this session's
        // established convention for this exact migration shape.
    }
};
```

- [ ] **Step 4: Create the `ExpireStaleSessions` command**

Create `app/Console/Commands/ExpireStaleSessions.php`:

```php
<?php

namespace App\Console\Commands;

use App\Models\TutorSession;
use Illuminate\Console\Command;

/**
 * Marks a pending session as a no-show once its scheduled end time
 * (starts_at + duration_minutes, via TutorSession::endsAt()) has passed.
 * Pure internal bookkeeping -- no notification, no money movement, no
 * approval step. Intended to run daily (see routes/console.php). Only
 * 'pending' sessions are eligible: a session that was already confirmed,
 * completed, or cancelled is left exactly as it is.
 */
class ExpireStaleSessions extends Command
{
    protected $signature = 'tutor:expire-stale-sessions';

    protected $description = 'Mark pending tutor sessions whose scheduled time has passed as no-show.';

    public function handle(): int
    {
        $expired = 0;

        TutorSession::query()
            ->where('status', 'pending')
            ->chunkById(100, function ($sessions) use (&$expired) {
                foreach ($sessions as $session) {
                    if ($session->endsAt()->isPast()) {
                        $session->update(['status' => 'no_show']);
                        $expired++;
                    }
                }
            });

        $this->info("Marked {$expired} stale session(s) as no-show.");

        return self::SUCCESS;
    }
}
```

- [ ] **Step 5: Schedule the command**

Read `routes/console.php` first (already read during plan-writing — it currently contains only the stock `inspire` Artisan demo command, no `Schedule::` calls exist). Add to the end of the file:

```php
Schedule::command('tutor:expire-stale-sessions')->dailyAt('02:00');
```

Add the import at the top of the file alongside the existing ones:

```php
use Illuminate\Support\Facades\Schedule;
```

- [ ] **Step 6: Run migration**

Run: `php artisan migrate`
Expected: migration runs with no errors.

- [ ] **Step 7: Run tests to verify they pass**

Run: `php artisan test tests/Feature/ExpireStaleSessionsCommandTest.php`
Expected: PASS (5 tests)

- [ ] **Step 8: Run Pint**

Run: `vendor/bin/pint --dirty --format agent`
Expected: passes; re-run Step 7 if it reformats anything.

- [ ] **Step 9: Commit**

```bash
git add database/migrations/2026_08_09_000001_widen_tutor_sessions_status_column.php \
  app/Console/Commands/ExpireStaleSessions.php routes/console.php \
  tests/Feature/ExpireStaleSessionsCommandTest.php \
  docs/superpowers/plans/2026-08-09-session-expiry-and-profile-approval-gate.md
git commit -m "$(cat <<'EOF'
feat: auto-expire stale pending sessions to no_show

New tutor:expire-stale-sessions command, scheduled daily: any
'pending' session whose scheduled end time (starts_at +
duration_minutes, via the existing TutorSession::endsAt() helper) has
passed becomes 'no_show'. Pure internal bookkeeping, no approval gate
-- matches the platform audit's own risk judgment that this specific
transition is safe to run unattended, unlike tutor profile approval
(a separate, later change).

Confirmed/rejected/completed sessions are never touched, even when
past their scheduled time -- only a still-'pending' session (nobody
ever confirmed or completed it) is a real no-show signal.

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>
EOF
)"
```

---

### Task 2: `is_platform_operator` + `TutorProfileReviewController`

**Files:**
- Create: `database/migrations/2026_08_09_000002_add_is_platform_operator_to_users_table.php`
- Create: `database/migrations/2026_08_09_000003_widen_tutor_profiles_status_and_add_review_columns.php`
- Create: `app/Http/Middleware/EnsurePlatformOperator.php`
- Modify: `bootstrap/app.php`
- Create: `app/Http/Controllers/TutorProfileReviewController.php`
- Create: `resources/views/operator/tutor-profiles/index.blade.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/TutorProfileReviewControllerTest.php`

**Interfaces:**
- Produces: `users.is_platform_operator` (bool); `tutor_profiles.status` accepting `'rejected'`, plus `reviewed_by`/`reviewed_at`/`rejected_reason` columns; `TutorProfileReviewController::index()`, `approve(TutorProfile $tutorProfile)`, `reject(TutorProfile $tutorProfile)`.

- [ ] **Step 1: Write the failing tests**

Create `tests/Feature/TutorProfileReviewControllerTest.php`:

```php
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
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test tests/Feature/TutorProfileReviewControllerTest.php`
Expected: FAIL — `/operator/tutor-profiles/*` routes don't exist yet, `is_platform_operator` column doesn't exist yet.

- [ ] **Step 3: Write the `is_platform_operator` migration**

Create `database/migrations/2026_08_09_000002_add_is_platform_operator_to_users_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_platform_operator')->default(false)->after('password');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_platform_operator');
        });
    }
};
```

- [ ] **Step 4: Add the cast to `User`**

Read `app/Models/User.php` first (already read during plan-writing — it has a `protected function casts(): array` method returning `['email_verified_at' => 'datetime', 'password' => 'hashed']`). Update it:

```php
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_platform_operator' => 'boolean',
        ];
    }
```

Do **not** add `is_platform_operator` to `$fillable` — granted only by hand, matching every prior platform this session that used this flag.

- [ ] **Step 5: Write the `tutor_profiles` migration**

Create `database/migrations/2026_08_09_000003_widen_tutor_profiles_status_and_add_review_columns.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE tutor_profiles DROP CONSTRAINT IF EXISTS tutor_profiles_status_check');
        }

        Schema::table('tutor_profiles', function (Blueprint $table) {
            $table->string('status')->default('pending')->change();
        });

        Schema::table('tutor_profiles', function (Blueprint $table) {
            $table->foreignId('reviewed_by')->nullable()->after('status')->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
            $table->text('rejected_reason')->nullable()->after('reviewed_at');
        });
    }

    public function down(): void
    {
        Schema::table('tutor_profiles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropColumn(['reviewed_at', 'rejected_reason']);
        });
    }
};
```

- [ ] **Step 6: Update `TutorProfile::$fillable`**

Read `app/Models/TutorProfile.php` first (already read during plan-writing — `protected $fillable = ['user_id', 'bio', 'hourly_rate', 'rating', 'total_sessions', 'status'];`). Update it:

```php
    protected $fillable = [
        'user_id', 'bio', 'hourly_rate', 'rating', 'total_sessions', 'status',
        'reviewed_by', 'reviewed_at', 'rejected_reason',
    ];
```

This is required for `TutorProfileReviewController`'s `update([...])` calls in Step 8 to actually persist these columns — an `update()` call silently drops any attribute not in `$fillable`, a mass-assignment gotcha this session has hit repeatedly on other platforms (verify this is caught by Step 2's re-run in Step 10, which would otherwise show `reviewed_by`/`reviewed_at`/`rejected_reason` staying `null` even though `status` correctly changes).

- [ ] **Step 7: Create `EnsurePlatformOperator` middleware and register it**

Create `app/Http/Middleware/EnsurePlatformOperator.php`:

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePlatformOperator
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->is_platform_operator, 403);

        return $next($request);
    }
}
```

Read `bootstrap/app.php` first (already read during plan-writing — it has an empty `withMiddleware(function (Middleware $middleware): void { // })` closure). Replace the full file:

```php
<?php

use App\Http\Middleware\EnsurePlatformOperator;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'operator' => EnsurePlatformOperator::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
```

- [ ] **Step 8: Create the `TutorProfileReviewController`**

Create `app/Http/Controllers/TutorProfileReviewController.php`:

```php
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
```

- [ ] **Step 9: Create the review screen view**

Create `resources/views/operator/tutor-profiles/index.blade.php`:

```blade
<x-app-layout>

<style>
    .review-card {
        background:#141416;
        border: 1px solid rgba(255,255,255,0.07);
        border-radius: 12px;
        padding: 1.5rem;
        margin-bottom: 1rem;
    }
    .review-actions { display: flex; gap: 0.75rem; align-items: flex-start; margin-top: 1rem; }
    .btn-approve, .btn-reject {
        border-radius: 8px; padding: 0.5rem 1rem; font-size: 0.8rem; font-weight: 700;
        border: none; cursor: pointer; color: #fff;
    }
    .btn-approve { background: #22c55e; }
    .btn-reject { background: #ef4444; }
    .reject-reason {
        flex: 1; border-radius: 8px; padding: 0.5rem 0.75rem; font-size: 0.8rem;
        background: #1e1e22; border: 1px solid rgba(255,255,255,0.1); color: #f4f4f5;
    }
</style>

<div style="padding:2rem 2.5rem;max-width:900px;margin:0 auto;">
    <h1 style="font-family:'Manrope',sans-serif;font-size:1.5rem;font-weight:800;color:#dae2fd;margin:0 0 1.5rem;">
        Tutor Profile Review Queue
    </h1>

    @if(session('status'))
        <div style="margin-bottom:1.5rem;padding:0.75rem 1rem;border-radius:8px;background:rgba(34,197,94,0.1);color:#4ade80;font-size:0.85rem;">
            {{ session('status') }}
        </div>
    @endif

    @if($pendingProfiles->isEmpty())
        <p style="color:#8d90a2;font-size:0.85rem;">No tutor profiles awaiting review.</p>
    @endif

    @foreach($pendingProfiles as $profile)
        <div class="review-card">
            <p style="color:#dae2fd;font-weight:700;margin:0 0 0.25rem;">{{ $profile->user->name }}</p>
            <p style="color:#8d90a2;font-size:0.8rem;margin:0 0 0.5rem;">
                ${{ $profile->hourly_rate }}/hr &middot;
                {{ $profile->subjects->pluck('name')->join(', ') ?: 'No subjects listed' }}
            </p>
            @if($profile->bio)
                <p style="color:#c1c4d6;font-size:0.85rem;margin:0;">{{ $profile->bio }}</p>
            @endif

            <div class="review-actions">
                <form method="POST" action="{{ route('operator.tutor-profiles.approve', $profile) }}">
                    @csrf
                    <button type="submit" class="btn-approve">Approve</button>
                </form>
                <form method="POST" action="{{ route('operator.tutor-profiles.reject', $profile) }}" style="flex:1;display:flex;gap:0.5rem;">
                    @csrf
                    <input type="text" name="reason" class="reject-reason" placeholder="Reason for rejecting" />
                    <button type="submit" class="btn-reject">Reject</button>
                </form>
            </div>
        </div>
    @endforeach
</div>

</x-app-layout>
```

- [ ] **Step 10: Add the routes**

In `routes/web.php`, add inside the existing `auth:sanctum` / `jetstream.auth_session` / `verified` group, after the `/sessions/{tutorSession}/cancel` route:

```php
    Route::middleware('operator')->prefix('operator')->name('operator.')->group(function () {
        Route::get('/tutor-profiles', [TutorProfileReviewController::class, 'index'])->name('tutor-profiles.index');
        Route::post('/tutor-profiles/{tutorProfile}/approve', [TutorProfileReviewController::class, 'approve'])->name('tutor-profiles.approve');
        Route::post('/tutor-profiles/{tutorProfile}/reject', [TutorProfileReviewController::class, 'reject'])->name('tutor-profiles.reject');
    });
```

Add the import at the top of the file alongside the existing ones:

```php
use App\Http\Controllers\TutorProfileReviewController;
```

- [ ] **Step 11: Run migrations**

Run: `php artisan migrate`
Expected: both new migrations run with no errors.

- [ ] **Step 12: Run tests to verify they pass**

Run: `php artisan test tests/Feature/TutorProfileReviewControllerTest.php`
Expected: PASS (7 tests)

- [ ] **Step 13: Run Pint**

Run: `vendor/bin/pint --dirty --format agent`
Expected: passes; re-run Step 12 if it reformats anything.

- [ ] **Step 14: Commit**

```bash
git add database/migrations/2026_08_09_000002_add_is_platform_operator_to_users_table.php \
  database/migrations/2026_08_09_000003_widen_tutor_profiles_status_and_add_review_columns.php \
  app/Http/Middleware/EnsurePlatformOperator.php bootstrap/app.php \
  app/Http/Controllers/TutorProfileReviewController.php \
  resources/views/operator/tutor-profiles/index.blade.php \
  routes/web.php app/Models/User.php app/Models/TutorProfile.php \
  tests/Feature/TutorProfileReviewControllerTest.php
git commit -m "$(cat <<'EOF'
feat: operator review gate for tutor profile approval

TutorProfile.status = 'approved' gates marketplace visibility
(TutorBookingController::browse/show/store) but was written nowhere
in application code -- only via direct database edits or artisan
tinker. New /operator/tutor-profiles screen: an operator reviews each
pending profile and approves (the only place status flips to
'approved' now) or rejects (reason required, status: 'rejected' -- a
new value distinct from the existing 'suspended', which keeps its own
meaning: an approved tutor pulled later, not one never approved).

New is_platform_operator flag on users -- this schema has no team_id
or admin role to reuse (confirmed by the platform audit).

Plain Controller + Blade, matching TutorBookingController's existing
style -- this app depends on Livewire but has no component in active
use anywhere, so this doesn't introduce a new UI pattern.

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>
EOF
)"
```

---

### Task 3: Full regression

**Files:** none new — verification only.

- [ ] **Step 1: Run the full test suite**

Run: `php artisan test --compact`
Expected: 0 failures — confirms Tasks 1-2 didn't break `TutorBookingTest`, `DashboardSessionScopingTest`, or anything else.

- [ ] **Step 2: Run Pint**

Run: `vendor/bin/pint --dirty --format agent`
Expected: passes; re-run Step 1 if it reformats anything.

- [ ] **Step 3: Report completion**

No manual tinker verification for this task — this repo's own `CLAUDE.md` states "Do not create verification scripts or tinker when tests cover that functionality and prove they work," and Tasks 1-2's tests already exercise the real end-to-end lifecycle: a real stale session expired by the real `tutor:expire-stale-sessions` command, and a real pending profile reviewed via the real `TutorProfileReviewController` routes, confirmed to actually change what `TutorBookingController::browse` returns (`test_approving_makes_the_tutor_visible_in_browse`). No commit for this task — it's verification only. If Step 1 finds any failures, stop and fix them (return to the relevant earlier task) before considering this plan complete.

## Self-Review Notes

- **Spec coverage:** Task 1 covers spec §1 (`no_show` + `ExpireStaleSessions`). Task 2 covers spec §2-§4 (`is_platform_operator`, `tutor_profiles` review columns, `TutorProfileReviewController` + routes). Task 3 covers the spec's implicit "this all actually works together" requirement, adapted to this repo's own no-tinker rule.
- **Placeholder scan:** none — every step has literal file content, including full migration/command/middleware/controller/view contents and the complete replacement `bootstrap/app.php`.
- **Type consistency:** `ExpireStaleSessions`'s signature, `TutorProfileReviewController::index/approve/reject`'s signatures, and the `reviewed_by`/`reviewed_at`/`rejected_reason` column names are used identically everywhere they're referenced across both tasks.
- **Mass-assignment gotcha caught before it happens:** Task 2 Step 6 explicitly adds the three new columns to `TutorProfile::$fillable` and flags why — this exact bug (a `update()` call silently dropping a column missing from `$fillable`) has recurred on multiple platforms this session, so this plan fixes it proactively rather than discovering it via a failing test.
- **Session's own risk judgment preserved, not overridden:** Task 1's command has no gate anywhere in its steps or commit message framing — explicitly not forcing approval onto a transition the audit judged safe, per the design discussion.
- **No manual tinker step, per this repo's own rule:** Task 3 explains why it skips the manual-verification step, matching the same reasoning already established for Dot.Projects, Dot.Sheet, and Dot.Tasks.
