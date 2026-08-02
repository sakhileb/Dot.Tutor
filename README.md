<div align="center">

<img src="public/images/logo.png" alt="Dot.Tutor" width="220" />

<br /><br />

**Find expert tutors, book sessions, and learn online or in person.**

<br />

![Laravel](https://img.shields.io/badge/Laravel-13.8-FF2D20?style=flat-square&logo=laravel&logoColor=white) ![PHP](https://img.shields.io/badge/PHP-8.3+-777BB4?style=flat-square&logo=php&logoColor=white) ![Livewire](https://img.shields.io/badge/Livewire-3-FB70A9?style=flat-square) ![PostgreSQL](https://img.shields.io/badge/PostgreSQL-336791?style=flat-square&logo=postgresql&logoColor=white)

<br /><br />

**Part of the [InfoDot Ecosystem](https://github.com/sakhileb/InfoDot)** &nbsp;·&nbsp; `tutor.infodot.co.za`

</div>

---

## What is Dot.Tutor?

Dot.Tutor is the tutoring/education platform in the InfoDot ecosystem: tutors list subjects and an hourly rate, students book sessions, and both sides can leave a rating and review once a session completes. It is a Laravel 13.8 / Jetstream 5 / Livewire 3 app with a working `EcosystemAuthController` SSO handoff, matching the contract used across the rest of the ecosystem.

> **Note:** this README previously described features — AI session summaries, AI-generated learning paths, a video-call virtual classroom, an earnings/payout dashboard, Meilisearch search, S3 storage, Redis/Horizon queues — that do not exist in this codebase. `composer.json` has no Anthropic/AI, Scout, or Horizon dependency, and no such service classes exist under `app/`. See `wiki.md` for what is actually built vs. still ahead.

## Core Features (as implemented)

- Tutor directory data model — `TutorProfile` with bio, hourly rate, rating, status (`pending`/`approved`/`suspended`), and a many-to-many link to `Subject`
- Session booking data model — `TutorSession` with status (`pending`/`confirmed`/`completed`/`cancelled`), delivery mode (`online`/`in_person`), start time, duration, and rate
- Post-session lesson resources (`LessonResource`) and one rating/review per session (`SessionRating`)
- An internal ops dashboard (`/dashboard`) with platform-wide KPIs (total/upcoming/completed sessions, approved tutor count, revenue) and session lists scoped to the signed-in user's own bookings
- Ecosystem SSO from the InfoDot hub (`/auth/ecosystem`, Sanctum token handoff)
- Jetstream Teams, 2FA, profile management (framework defaults)

**Not implemented** (present only as unused env vars, aspirational docs, or not present at all): AI session summaries, AI learning-path generation, video calling / whiteboard, tutor earnings & payout tracking, booking UI/controllers, search, S3 storage, Redis/Horizon queues. There is no controller or Livewire component yet for browsing tutors or booking a session — the domain tables and models exist, but the booking flow itself is unbuilt.

## Domain Models (as implemented)

- **TutorProfile** — a user's tutor listing: bio, hourly rate, rating, subjects, status
- **TutorSession** — a booked session between a `TutorProfile` and a student (`User`)
- **Subject** — a bookable subject/level combination
- **LessonResource** — a file attached to a session by either party
- **SessionRating** — a single rating + review per completed session

## Tech Stack

| Layer | Technology | Status |
|---|---|---|
| Framework | Laravel 13.8, PHP 8.3+ | Installed |
| Frontend | Livewire 3 · Alpine.js (CDN) · Tailwind (CDN) | Installed |
| Database | PostgreSQL (shared `infodot` instance) | `DB_DATABASE=infodot` in `.env.example`, matches ecosystem convention |
| Auth | Laravel Sanctum + Jetstream/Fortify (teams, 2FA) | Installed; `EcosystemAuthController` verified against the ecosystem SSO contract |
| Realtime | Laravel Reverb | Dependency present, not wired to any domain broadcast |
| AI | — | Not integrated. `ANTHROPIC_API_KEY`/`ANTHROPIC_MODEL` exist in `.env.example` but no service class reads them |
| Storage | Local (Flysystem) | AWS S3 not configured |
| Search | — | Laravel Scout not installed |
| Queue | Database queue driver | Redis/Horizon not installed |

## Quick Start

```bash
git clone https://github.com/sakhileb/Dot.Tutor.git
cd Dot.Tutor
cp .env.example .env
composer install
npm install && npm run build
php artisan key:generate
php artisan migrate
php artisan serve
```

> **Ecosystem SSO:** Set `DB_*` env vars to the shared InfoDot PostgreSQL instance and `APP_URL` to this platform's real ecosystem hostname. Users authenticated through InfoDot gain access automatically via Sanctum handoff tokens against `/auth/ecosystem`.

### Running Tests

```bash
php artisan test
```

Feature tests use an in-memory SQLite connection (see `phpunit.xml`) and Laravel's `RefreshDatabase` trait — no shared Postgres instance required to run them. **This environment has no PHP/Composer available, so tests added in the Aug 2026 ecosystem-integration pass are written to the repo's existing standard but have not been executed here** — see `wiki.md` and `Dot.Brain/os/02-Engineering-Loop.md` §2.

## Ecosystem

**Dot.Tutor** is one of the platforms in the InfoDot ecosystem, connected via shared PostgreSQL and Sanctum SSO. Visit [InfoDot](https://github.com/sakhileb/InfoDot) to explore the full platform map.

## License

MIT © [SK Digital / BluPin Incorporated](https://github.com/sakhileb)
