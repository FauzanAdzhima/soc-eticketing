# AGENTS.md

## Cursor Cloud specific instructions

This is a Laravel 12 SOC (Security Operations Center) incident ticketing system built with Livewire, Alpine.js, and Tailwind CSS (via Vite). It uses SQLite for all storage (database, cache, sessions, queue).

### System dependencies

- **PHP 8.3+** with extensions: sqlite3, xml, mbstring, curl, zip, bcmath, gd, fileinfo, dom, tokenizer
- **Composer** (PHP package manager)
- **Node.js 22+** / npm (for Vite/Tailwind frontend build)

### Quick reference

| Task | Command |
|---|---|
| Install PHP deps | `composer install` |
| Install JS deps | `npm install` |
| Lint (code style) | `./vendor/bin/pint --test` |
| Fix lint | `./vendor/bin/pint` |
| Run tests | `./vendor/bin/pest` |
| Build frontend | `npm run build` |
| Dev server (all-in-one) | `composer dev` (runs artisan serve + Vite + queue + pail concurrently) |
| Dev server (manual) | `php artisan serve` (port 8000) and `npm run dev` (Vite, port 5173) |
| Run migrations | `php artisan migrate` |
| Seed database | `php artisan db:seed` |
| Fresh reset | `php artisan migrate:fresh --seed` |

### Non-obvious notes

- The lock file requires **PHP 8.3+** (not 8.2 as `composer.json`'s `^8.2` might suggest), because several dependencies (pest, spatie/laravel-permission, phpunit) pin to `^8.3`.
- Tests require the Vite manifest to exist. Run `npm run build` before `./vendor/bin/pest`, otherwise tests that render Blade views will fail with "Vite manifest not found".
- There are 2 pre-existing test failures in `tests/Feature/ProfileTest.php` (redirect URL mismatch `/profile` vs `/profile/edit`). These are not environment issues.
- Seeded test users all use password `password`. The admin account is `admin@test.com`.
- The `composer dev` script uses `npx concurrently` to run 4 processes at once (serve, queue, pail, vite). If you need individual control, start them separately.
- Environment setup: copy `.env.example` to `.env`, run `php artisan key:generate`, create `database/database.sqlite`, then `php artisan migrate --seed`.
