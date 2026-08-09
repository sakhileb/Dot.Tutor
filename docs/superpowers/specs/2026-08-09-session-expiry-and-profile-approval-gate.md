# Dot.Tutor: Session No-Show Expiry + Tutor Profile Approval Gate

## Context

Dot.Tutor's autonomy classification audit (`Dot.Brain/platforms/dot-tutor.md`, 2026-08-08) found zero automation at any level — no scheduled commands, no queued jobs, no notifications, no CI/CD. Confirmed against the real code: `app/Console/Commands/`, `app/Jobs/`, and `app/Notifications/` don't exist as directories, and `bootstrap/app.php` has no `->withSchedule()` block. `TutorSession.status` only ever transitions `pending → cancelled` (`TutorBookingController.php:84,112`) — nothing auto-progresses it.

The audit's own recommendation is explicit and correctly scoped: the first real automation here should be a small, low-risk Level 1 job (auto-expiring stale bookings), not something needing approval. Separately, the audit also found a genuine Level 3 process the platform actually needs reviewed: `TutorProfile.status = 'approved'` gates marketplace visibility (`TutorBookingController::browse`/`show`/`store`) but is written nowhere in application code — confirmed via `grep -rn "'status'" app/Http/Controllers/`, which shows only `TutorSession` status writes. A tutor becomes visible today only via a direct database edit or `artisan tinker`.

Per the design discussion, this spec builds both pieces at their correct, honest level rather than forcing an artificial approval step onto the session-expiry job the audit judges doesn't need one: session expiry stays Level 1 (unattended), and tutor profile approval — a real Level 3 process today — becomes a genuine Level 2 one.

## Goal

A `pending` tutor session whose scheduled time has passed becomes `no_show` automatically, via a daily scheduled command — pure internal bookkeeping, no approval gate. Separately, a `pending` tutor profile is no longer approved by hand: a platform operator reviews it on a new screen and approves (`status: approved`, the real effect that flips on marketplace visibility) or rejects (`status: rejected`, reason required). No existing role or team concept fits reviewer authority here — the audit confirmed this schema has no `team_id` or admin role at all — so a new `is_platform_operator` flag is the reviewer identity, matching the pattern used on every other platform this session that lacked an existing role to reuse.

## Changes

### 1. `tutor_sessions.status` gains `no_show` + `ExpireStaleSessions` command

New migration widens `tutor_sessions.status` from a native enum to a plain string (the same portable-append technique used repeatedly this session — conditionally drop the Postgres CHECK constraint, then `Schema::table()->change()`; SQLite, used by this app's tests, has no such constraint and rebuilds the table wholesale). No backfill — `no_show` is a brand-new value no existing row has held.

New command, `tutor:expire-stale-sessions`: finds every `TutorSession` where `status = 'pending'` and `starts_at` plus `duration_minutes` is before now, and sets `status: no_show`. No notification, no side effect beyond the status change — internal bookkeeping only, matching the audit's own risk framing. Registered daily via `bootstrap/app.php`'s `->withSchedule()`.

### 2. `is_platform_operator` flag

New migration: boolean on `users`, default `false`, excluded from `$fillable`, boolean cast — identical shape to every prior platform this session that added this flag (Dot.Ehail/Emall/Files/Press/Sheet), since this schema has no existing role or team-scoped concept to reuse (confirmed: "no `team_id` or admin role exists in this schema").

### 3. `tutor_profiles.status` gains `rejected` + review columns

New migration widens `tutor_profiles.status` from a native enum to a plain string (same technique as §1), adding `rejected` alongside the existing `pending`/`approved`/`suspended` — a distinct value from `suspended`, so the column can still tell apart "never approved" from "approved, then later suspended," per the design discussion. Adds `reviewed_by` (nullable FK `users.id`, `nullOnDelete`), `reviewed_at` (nullable timestamp), `rejected_reason` (nullable text) directly on `tutor_profiles` — a profile has exactly one live review outcome at a time (unlike this session's recurring-reassessment gates, a profile isn't re-evaluated on a schedule), so these columns are overwritten, not accumulated in a separate table.

### 4. `TutorProfileReviewController` + operator routes

New `EnsurePlatformOperator` middleware, identical to Dot.Press's and Dot.Sheet's (`abort_unless($request->user()?->is_platform_operator, 403)`), aliased `operator`.

New `app/Http/Controllers/TutorProfileReviewController.php` — this repo's one real feature controller (`TutorBookingController`) is plain Controller-plus-Blade, with no Livewire component in active use despite Livewire being a Jetstream dependency (`app/Livewire/` doesn't exist), so this controller follows that same established shape rather than introducing a new pattern:

- `index()` — lists every `pending` `TutorProfile` (bio, hourly rate, subjects, the applicant's name).
- `approve(TutorProfile $tutorProfile)` (`POST`) — no-ops (redirects back with a flash message) unless the profile is still `pending`. Otherwise sets `status: approved`, `reviewed_by`, `reviewed_at`.
- `reject(TutorProfile $tutorProfile)` (`POST`) — validates `reason` is required (standard Laravel form validation, matching `TutorBookingController::store`'s own validation style). No-ops unless still `pending`. Otherwise sets `status: rejected`, `rejected_reason`, `reviewed_by`, `reviewed_at`.

Routes nested under `Route::middleware('operator')->prefix('operator')->name('operator.')->group(...)`, matching Dot.Press's exact structure.

## Testing

- `ExpireStaleSessions`: a `pending` session whose `starts_at` + `duration_minutes` has passed becomes `no_show`; a `pending` session still in the future is untouched; a `confirmed`, `completed`, or `cancelled` session past its scheduled time is untouched (only `pending` is eligible — a session someone already confirmed or completed isn't a no-show).
- `TutorProfileReviewController`: an operator can approve a pending profile (`status: approved`, `reviewed_by`/`reviewed_at` set, the profile now appears in `TutorBookingController::browse`); an operator can reject with a reason (`status: rejected`, `rejected_reason` recorded, the profile stays excluded from `browse`); rejecting without a reason fails validation and leaves the profile `pending`; a non-operator gets a 403 from both the route and a direct controller call; acting on a profile that's no longer `pending` is a no-op.

## Explicitly out of scope

- Any change to the `suspended` status or a workflow for suspending an already-approved tutor — untouched by this spec; `rejected` is a new, distinct value specifically so it never overloads `suspended`'s existing meaning.
- Notifying the tutor that their profile was reviewed — this platform has no notification pipeline at all (`app/Notifications/` doesn't exist), and building one is separate, future work, matching the same choice made for every other gate this session that didn't wire a new notification.
- Any change to `LessonResource` or `SessionRating`, both still UI-less per the audit — untouched, unrelated to this spec.
- Re-evaluating an already-reviewed tutor profile on a schedule — unlike Dot.Sheet's backup pruning or Dot.Tasks' overdue-task escalation, a profile is created once and reviewed once; there is no recurring "still eligible" condition to re-check.
