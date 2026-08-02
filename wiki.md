---
title: Dot.Tutor — Platform Wiki
version: 0.3.0
status: draft
owners: [Tutor Platform Lead]
platform-id: dot-tutor
last-review: 2026-08-02
---

# Dot.Tutor

Purpose: this is Dot.Tutor's own knowledge home — owned and maintained by the Dot.Tutor team. It describes what this platform actually is, as implemented, and how it connects to the wider Dot Ecosystem. Dot.Brain never edits this file; it only reads what we choose to publish.

> **Related:** [Dot.Brain's ingested view of this platform](https://github.com/sakhilebhayi/Dot.Brain/blob/main/platforms/dot-tutor.md)

---

## 1. What Dot.Tutor Is

Dot.Tutor is the tutoring/education platform in the InfoDot ecosystem — its `ecosystem.php` registry entry uses the `school` icon, and the real domain code confirms that framing: tutors list subjects and an hourly rate, students book sessions against a tutor, and either side can leave one rating/review per completed session. It is a Laravel 13.8 / Jetstream 5 / Livewire 3 app, not the AI-tutoring or video-classroom product earlier README drafts described.

**Status:** this platform was previously untouched by the ecosystem-wide engineering effort but turns out to already be substantially scaffolded — real models, migrations, a working ecosystem SSO controller, and a functional (if not yet booking-capable) dashboard. Before this pass: no `wiki.md`, a stale/aspirational `README.md`, no favicon/nav/login branding, and a real cross-user data-exposure bug on the one built page (§6). Treat §8 (Roadmap) as what's ahead; everything else in this document as what's actually in the repo today.

## 2. Architecture

| Layer | Technology | Notes |
|---|---|---|
| Framework | Laravel 13.8, PHP 8.3+ | `composer.json` still carries the stock `laravel/laravel` package name/description — cosmetic, not fixed in this pass (see §8) |
| UI | Livewire 3, Alpine.js 3 (CDN), Tailwind CSS (CDN via `<script src="https://cdn.tailwindcss.com">` in `resources/views/layouts/app.blade.php`) | Server-rendered; the dashboard layout is a hand-built dark "Dot OS" spatial layout (sidebar + topbar), not the stock Jetstream navigation-menu chrome |
| Database | PostgreSQL, shared InfoDot instance | `.env.example` sets `DB_DATABASE=infodot`, `DB_CONNECTION=pgsql` — matches the ecosystem convention confirmed in Dot.Billing's wiki |
| Auth | Laravel Sanctum + `App\Http\Controllers\Auth\EcosystemAuthController` | Verified against the SSO contract used elsewhere in the ecosystem: looks up the query-string `token` as a `PersonalAccessToken`, checks the `ecosystem:read` ability and non-expiry, logs the tokenable user in, deletes the one-time token, redirects to `dashboard`. Same shape as the pattern documented in Dot.Billing's wiki — no drift found. |
| Realtime | Laravel Reverb | Dependency present (`laravel/reverb`), env vars scaffolded, nothing in `app/` broadcasts on it yet |
| AI | None | `.env.example` defines `ANTHROPIC_API_KEY` / `ANTHROPIC_MODEL`, but no service class anywhere under `app/` reads `config('services.anthropic.*')` or calls the Anthropic API. This is dead configuration carried over from a template, not a built feature — unlike Dot.Billing's `AiBillingService`, Dot.Tutor has no AI code at all. |
| Storage | Local (Flysystem default) | No S3 config in use despite older README claims |
| Search | None | Laravel Scout is not a dependency |
| Queue | Database driver (`QUEUE_CONNECTION=database`) | Redis/Horizon not installed |

Jetstream Teams is enabled (`Features::teams(['invitations' => true])` in `config/jetstream.php`), but the tutoring domain tables (`tutor_profiles`, `tutor_sessions`, `subjects`, `lesson_resources`, `session_ratings`) carry **no `team_id` column at all** — the domain is single shared marketplace, not team-scoped, unlike Dot.Billing's team-scoped billing tables. This matters for §6.

## 3. Domain Entities (as implemented)

Source: `database/migrations/2026_06_27_000001_create_tutor_tables.php` and `app/Models/`.

| Model | Table | Purpose |
|---|---|---|
| `Subject` | `subjects` | A bookable subject + level (`name`, `level`, default `high_school`) |
| `TutorProfile` | `tutor_profiles` | A user's tutor listing — bio, hourly rate, rating, total session count, status (`pending`/`approved`/`suspended`); one-to-one with `User` |
| `TutorSession` | `tutor_sessions` | A booked session — links a `TutorProfile`, a student (`User`), and a `Subject`; status (`pending`/`confirmed`/`completed`/`cancelled`), delivery (`online`/`in_person`), `starts_at`, `duration_minutes`, `rate`, free-text `notes` |
| `LessonResource` | `lesson_resources` | A file attached to a session by either the tutor or the student |
| `SessionRating` | `session_ratings` | One rating (1–255 tinyint) + optional review per session, unique per session |

`TutorSession::endsAt()` is the only domain business-logic helper (`starts_at->addMinutes(duration_minutes)`). Everything else is plain Eloquent relations — no state-transition guards (e.g. nothing stops a `cancelled` session from being marked `completed`), no validation beyond DB column types. **A booking controller now exists** — see §4.

## 4. What Exists Today vs. What's Modeled but Unbuilt

**Built:**
- Ecosystem SSO route (`/auth/ecosystem`) — verified matching the ecosystem-wide contract
- `/dashboard` route (`routes/web.php`) — platform-wide KPI counts (total/upcoming/completed sessions, approved-tutor count, revenue) plus a subjects grid and two session lists, scoped to the signed-in user's own sessions (fixed a prior pass, see §6); the dashboard's "Book Session" button and upcoming-session rows now link to real routes instead of `href="#"`
- Full Jetstream Teams/Fortify scaffold (registration, 2FA, team management, profile) — framework defaults, untouched this pass per instructions
- Hand-built dark "Dot OS" dashboard layout (`resources/views/layouts/app.blade.php`) — this is the real, functional result of the `feat: InfoDot dark-theme dashboard and layout for Dot.Tutor` commit; it renders correctly against the seeded models and is not a stub
- **The booking flow, built this pass** (`App\Http\Controllers\TutorBookingController`): browse approved tutors filterable by subject (`GET /tutors`), a tutor's public profile with a booking form (`GET /tutors/{tutorProfile}`), session creation (`POST /tutors/{tutorProfile}/sessions`), a session detail page (`GET /sessions/{tutorSession}`), and cancellation (`POST /sessions/{tutorSession}/cancel`). `store()` enforces real domain constraints — a tutor can't book themselves, the chosen subject must actually be one the tutor's `subjects()` relation includes, the requested time must be in the future — and snapshots `hourly_rate` onto the session's `rate` column at booking time rather than reading it live later. `TutorSessionPolicy` (view/cancel, gated to the student or the tutor involved) ships alongside it, closing the gap §6 flagged.

**Modeled in the schema but not yet built:**
- Uploading a `LessonResource`, or leaving a `SessionRating` — the tables and models exist; nothing in `app/Http` or `resources/views` lets a user create these records yet (booking itself is now built, see above)
- Tutor/student confirming or completing a session — `status` only ever reaches `pending` (on booking) or `cancelled` (via the new cancel action); nothing transitions a session to `confirmed` or `completed` yet
- AI session summaries / AI-generated learning paths — no service class, no dependency; only unused `.env.example` keys
- Video calling / shared whiteboard ("virtual classroom") — not modeled in the schema at all, not built
- Tutor earnings & payout tracking — the dashboard shows an aggregate `$totalRevenue` figure; there is no per-tutor earnings view, ledger, or payout table
- `TutorProfilePolicy` (e.g. gating profile edits to the owning user) — only `TutorSessionPolicy` exists so far, scoped to what the new booking flow actually needed

## 5. Events Emitted

**None.** There are no Laravel event/listener classes and no domain events in this repository. Dot.Brain's ingested view (§7) may describe target-state events for this platform; this codebase does not fire any of them yet.

## 6. Security Scan — Finding Fixed This Pass

**Cross-user session data disclosure on `/dashboard` (fixed).** The dashboard route's "Upcoming Sessions" and "Recent Sessions" panels queried `TutorSession::with(['tutorProfile.user', 'subject'])` with **no scoping at all** — any authenticated user, student or tutor, regardless of whether they were involved in a given session, could see every other user's booked-session details: who they were being tutored by (or tutoring), the subject, the date/time, and the dollar amount charged. Since `tutor_sessions` has no `team_id` and this schema has no admin/staff role concept, there was no basis for a platform-wide view on a route every logged-in user lands on post-login.

Fix applied in `routes/web.php`: both list queries are now scoped with a `student_id = auth()->id() OR tutorProfile.user_id = auth()->id()` clause, so a user only sees sessions they are actually part of. The KPI counts above them (`totalSessions`, `upcomingSessions`, etc.) remain platform-wide aggregates — no PII in a count, consistent with how Dot.Billing's dashboard aggregates are treated. A regression test (`tests/Feature/DashboardSessionScopingTest.php`) asserts another user's session details do not leak and that a user's own session still renders — written to this repo's testing standard but **not executed**, per this environment's constraints (§9).

**Booking UI built this pass (§4), with authorization designed in from the start, not retrofitted:** `TutorBookingController::showSession()` and `::cancel()` both call `Gate::authorize()` against the new `TutorSessionPolicy` before touching a `TutorSession` by ID — a user can only view or cancel a session they're the student or tutor on. `store()` additionally checks the booking-time constraints in §4 (can't book yourself, subject must belong to the tutor). `TutorProfile::show()` only exposes `approved` profiles (`abort_unless`), so a `pending`/`suspended` profile isn't browsable by ID either. No `LessonResource`/`SessionRating` routes exist yet, so those remain unaudited until built (§10).

## 7. Branding

This repo ships with two unrelated image assets at its root: `dot.logos10.png` and (until this pass) a stray `index.html` + `styles.css` "coming soon" static page.

- **`dot.logos10.png` — confirmed to be Dot.Tutor's real logo, not a personal brand mark.** It was flagged going into this pass as a possible mix-up with the owner's personal signature wordmark (a pattern seen in other repos this session), so it was opened and inspected directly rather than assumed either way. The image is a yellow circle containing a teacher-at-a-chalkboard icon, a green arrow, and the wordmark "dot.tutor" in the exact yellow/green pair already used as `--brand-green`/`--brand-gold` in the removed `styles.css`. The shared `Downloads/Dot.logos/` asset drop (which holds the numbered `dot.logosN.png` files for other platforms) only goes up to `dot.logos9.png` — `dot.logos10.png` isn't part of that shared set, meaning it's a Dot.Tutor-specific asset checked in here, not a leftover from another platform's numbered file. It is now used as the platform's real logo: `public/images/logo.png` / `logo-512.png` (resized via `sips`), `public/favicon-32x32.png` / `favicon-16x16.png`, wired into the login page (`authentication-card-logo.blade.php`), the dashboard sidebar mark, and both layout `<head>`s.
- **`index.html` / `styles.css` (removed this pass).** Confirmed as the same leftover static "coming soon" marketing template pattern found on other platforms this session — unrelated to the real Laravel app, referencing `dot.logos10.png` directly from the repo root with its own inline color system. Removed (`git rm`) rather than kept alongside the real app.

## 8. Connecting to Dot.Brain

Dot.Tutor is registered in Dot.Brain's platform map (icon `school`, education domain). Dot.Brain's ingested view is maintained at [`platforms/dot-tutor.md`](https://github.com/sakhilebhayi/Dot.Brain/blob/main/platforms/dot-tutor.md); that document may describe more target-state capability (AI summaries, learning paths, a full booking flow) than this repo currently implements — the gap is intentional and tracked in §4 and §9, not a discrepancy to silently paper over.

Once domain events exist (§5), Dot.Tutor would publish Knowledge Packs following the same shape as other platforms:

| Payload type | Would contain |
|---|---|
| `observation` | Aggregated session/tutor metrics — session volume, subject demand, completion rate — never individual booking detail |
| `insight` | Patterns worth surfacing to Dot.Dopemine around learning progress/momentum (ethical constraints per Dot.Brain's Manifesto — never optimized for engagement/screen-time) |
| `outcome` | Verification of any Dot.Brain recommendation (e.g. tutor-matching suggestions) |
| `incident` | The §6 disclosure bug is exactly the shape of incident this ecosystem's anti-fragility principle expects to be captured, generalized, and fed back — flagging it here even though no Knowledge Pack pipeline exists yet to publish it |

No aggregation or publishing code exists in this repo yet — this section states intent, not a shipped integration.

## 9. Environment Constraints on This Pass

This pass was written and reviewed with **no local PHP, Composer, PostgreSQL, or Docker available**. Nothing in this repository was executed, migrated, or tested locally: not the existing test suite, not the new `DashboardSessionScopingTest`, not `composer install`. Everything here is hand-authored and hand-reviewed against the actual source, following [`Dot.Brain/os/02-Engineering-Loop.md`](../Dot.Brain/os/02-Engineering-Loop.md). CI or a genuine PHP/Postgres environment is a mandatory gate before any of this reaches production.

## 10. Roadmap / Open Questions

- [ ] **Missing dedicated ecosystem logo asset resolved this pass** — `dot.logos10.png` was confirmed as Dot.Tutor's real logo (§7) and wired into favicon/nav/login; no outstanding gap here, listed for provenance since the task going in assumed the opposite
- [x] ~~Build the actual booking flow~~ — done this pass (§4): browse/show/store/cancel routes, `TutorBookingController`, `TutorSessionPolicy`
- [ ] Uploading `LessonResource`s, leaving a `SessionRating`, and a real `pending → confirmed → completed` status transition (tutor confirms, someone marks complete) — still just schema, no UI
- [ ] Write `TutorProfilePolicy` when profile editing is built
- [ ] Decide whether the tutoring domain should gain a `team_id` (org-scoped tutoring, e.g. a school licensing Dot.Tutor for its students) or stay a single shared marketplace — current schema assumes the latter with no way to express the former
- [ ] Remove or genuinely implement the AI config (`ANTHROPIC_API_KEY`/`ANTHROPIC_MODEL` in `.env.example`) — currently dead, unlike Dot.Billing's real (if fallback-heavy) `AiBillingService`
- [ ] Fix `composer.json` — still names the package `laravel/laravel` with the stock skeleton description, not `sakhileb/dot-tutor`-style ecosystem naming
- [ ] Domain events for session lifecycle (booked/confirmed/completed/cancelled) — prerequisite for any Knowledge Pack publishing (§8)
- [ ] CI or a real PHP/Postgres dev environment to actually run the migrations and both the pre-existing and newly-added test suites — nothing in this repo has been executed as of this pass

## Change Log

| Version | Date | Author | Change |
|---|---|---|---|
| 0.3.0 | 2026-08-02 | Sakhile Bhayi | **Executed for real** against PHP 8.5 + PostgreSQL — the booking flow from 0.2.0 works as written, confirmed by 6 new Feature tests (`tests/Feature/TutorBookingTest.php`) all passing on first run, plus the full suite (52 tests, 45 passed, 7 skipped by config, 0 failed). Separately, found and fixed a real collision: this platform's six shared Jetstream-core migrations broke when a second platform's migrations ran against the same real `infodot` database — guarded per Dot.Brain ADR-0013, verified by running Dot.Billing → Dot.Forms → Dot.Tutor's migrations back-to-back against one database with zero errors. |
| 0.2.0 | 2026-08-02 | Tutor Platform Lead | **Built the booking flow flagged as missing in 0.1.0** — `App\Http\Controllers\TutorBookingController` (browse tutors by subject, tutor profile + booking form, session creation, session detail, cancellation), `App\Policies\TutorSessionPolicy` (view/cancel gated to the student or tutor involved, wired in from the start), three new dark-themed views matching the existing dashboard's design language, and the dashboard's dead `href="#"` "Book Session" button and upcoming-session rows now link to real routes. `store()` enforces the tutor-can't-book-self and subject-must-belong-to-tutor constraints and snapshots `hourly_rate` onto the booking's `rate` column. Written and reviewed with no PHP runtime available (§9) — unexecuted. |
| 0.1.0 | 2026-08-02 | Tutor Platform Lead | Initial platform-owned wiki. Verified `EcosystemAuthController` and `DB_DATABASE=infodot` against the ecosystem SSO contract (no drift). Confirmed `dot.logos10.png` is Dot.Tutor's real logo, not a personal brand mark — wired into favicon/login/nav; removed the stray `index.html`/`styles.css` coming-soon template. Fixed a real cross-user data-disclosure bug: `/dashboard` showed every user's session details to every other logged-in user regardless of involvement, now scoped to the signed-in user's own sessions, with a regression test added (written, unexecuted — see §9). Rewrote `README.md` to drop aspirational AI/video/search/payout claims that don't exist in `composer.json` or `app/`. |

## Open Questions

- Is the tutoring domain intentionally team-agnostic (a single shared marketplace), or should it eventually gain `team_id` scoping like Dot.Billing? Not decided here — flagged in §10.
- Should `dot.logos10.png` be renamed to something self-describing (e.g. `docs/logo.png`) now that it's confirmed as the real asset, rather than keeping the ambiguous numbered filename at the repo root? Left as-is this pass to avoid an unrelated rename in a security/docs-focused diff.
