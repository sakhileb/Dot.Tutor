<div align="center">

<img src="docs/logo.svg" alt="Dot.Tutor" width="320" />

<br /><br />

**Find expert tutors, book sessions, and learn online or in person.**

<br />

![Laravel](https://img.shields.io/badge/Laravel-12-FF2D20?style=flat-square&logo=laravel&logoColor=white) ![PHP](https://img.shields.io/badge/PHP-8.4-777BB4?style=flat-square&logo=php&logoColor=white) ![Livewire](https://img.shields.io/badge/Livewire-3-FB70A9?style=flat-square) ![PostgreSQL](https://img.shields.io/badge/PostgreSQL-16-336791?style=flat-square&logo=postgresql&logoColor=white)

<br /><br />

**Part of the [InfoDot Ecosystem](https://github.com/sakhileb/InfoDot)** &nbsp;·&nbsp; `tutor.infodot.app`

</div>

---

## What is Dot.Tutor?

Dot.Tutor is the learning platform in the InfoDot ecosystem. Students find and book subject-matter tutors for online or in-person sessions; tutors manage their availability, materials, and earnings — with AI-powered session summaries and personalised learning paths.

## Core Features

- Tutor directory — searchable by subject, level, and availability
- Session booking with calendar integration and reminders
- Virtual classroom — video call and shared whiteboard
- AI session summary — key points and action items generated post-session
- Learning path generation — AI structures a subject curriculum
- Tutor rating and review system
- Earnings dashboard for tutors with payout tracking
- Ecosystem SSO from InfoDot hub

## Domain Models

- **Tutor** — expert profile with subjects and rates
- **TutorSession** — booked learning session
- **TutorReview** — student rating and feedback
- **LearningPath** — AI-generated curriculum per subject

## Tech Stack

| Layer | Technology |
|---|---|
| Framework | Laravel 12 |
| Language | PHP 8.4 |
| Frontend | Livewire 3 · Alpine.js 3 · Tailwind CSS |
| Database | PostgreSQL 16 (shared across ecosystem) |
| Realtime | Laravel Reverb |
| Auth | Laravel Sanctum (InfoDot SSO) |
| AI | Anthropic Claude (`claude-sonnet-4-6`) |
| Storage | AWS S3 / Local (Flysystem) |
| Search | Laravel Scout · Meilisearch |
| Queue | Redis · Laravel Horizon |

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

> **Ecosystem SSO:** Set `DB_*` env vars to the shared InfoDot PostgreSQL instance and `APP_URL=https://tutor.infodot.app`. Users authenticated through InfoDot gain access automatically via Sanctum handoff tokens.

## Ecosystem

**Dot.Tutor** is one of **21 platforms** in the InfoDot ecosystem, connected via shared PostgreSQL and Sanctum SSO. Visit [InfoDot](https://github.com/sakhileb/InfoDot) to explore the full platform map.

## License

MIT © [SK Digital / BluPin Incorporated](https://github.com/sakhileb)
