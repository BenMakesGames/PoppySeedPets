# CLAUDE.md

## Project Overview

Poppy Seed Pets is a browser-based pet adoption and activity simulation game (poppyseedpets.com). Monorepo with two independent apps:

- **`api/`** — Symfony 7.3 (PHP 8.4) backend with MySQL + Redis
- **`webapp/`** — Angular 20 frontend (TypeScript, SCSS, Angular Material)

## Common Commands

- **API lint**: `composer run php-cs-fixer-dry-run` / `composer run php-cs-fixer` (in `api/`)
- **API static analysis**: `vendor/bin/phpstan --configuration=phpstan.dist.neon` (in `api/`)
- **Cron (manual)**: `vendor/bin/crunz schedule:run` (in `api/`)
- **Storybook**: `ng run PoppySeedPetsApp:storybook` (in `webapp/`)
- **Quick start**: `install.bat` / `run.bat` from repo root

## Architecture Decisions & Patterns

* [docs/architecture/Departures from Symfony Standard.md](docs/architecture/Departures from Symfony Standard.md)
* [docs/architecture/Project Patterns.md](docs/architecture/Project Patterns.md)

## Feature References

* [docs/features/merits.md](docs/features/merits.md) — every pet merit (trait), what it does, how it's acquired, and the conventions for each
* [docs/features/satyr-dice.md](docs/features/satyr-dice.md) — satyr dice mechanic

## Cron Jobs (api/tasks/AllTasks.php)

- **Every minute**: `app:increase-time` (pet activity time), `app:run-park-events`
- **Hourly**: `app:buzz-buzz` (beehive production)
- **Daily**: `app:calculate-daily-market-item-averages`, `app:calculate-daily-stats`

## Frontend Notes

- Dev server requires HTTPS — uses `dev.key`/`dev.pem` from repo root (expire Dec 2033)
- Proprietary assets expected at `proprietary-assets/` in the repo root (gitignored) — app builds without them but images will be missing
- API URL configured in `webapp/src/environments/environment.ts` (defaults to `https://localhost:8000`)

## Database

- No seed data in repo — game data (recipes, items, NPCs) must be sourced separately
