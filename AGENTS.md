# PoolStream - Agent Guide

Pool hall management app: billiard table control, timed sessions, F&B orders.
Laravel 13 + Inertia.js + Vue 3 + Bootstrap 5 (not Tailwind). SQLite database.

## Commands

- `composer setup` — full first-time setup (install, .env, key, migrate, npm, build)
- `composer dev` — run all dev services (artisan serve, queue, pail, vite) via concurrently
- `composer test` — clears config then runs `php artisan test`
- `php artisan migrate --force` — run migrations
- `npm run dev` — vite dev server only

## Architecture

- **Frontend:** Vue 3 pages in `resources/js/Pages/`, resolved via Inertia. Components in `resources/js/Components/`. Custom SCSS in `resources/scss/app.scss` using `bb-` prefixed class names (Bootstrap 5 theme).
- **Backend:** Controllers in `app/Http/Controllers/`. Models in `app/Models/`. Middleware `CheckRole` at `app/Http/Middleware/CheckRole.php` — registered as `role` alias.
- **Routes:** `routes/web.php` (app routes), `routes/auth.php` (Breeze auth). Admin routes under `role:admin` middleware at `/master/*`.
- **Database:** SQLite at `database/database.sqlite`. Migrations in `database/migrations/`.
- **Hardware:** `app/Services/relay_controller.py` — Python script for USB HID relay control, called via `shell_exec` from `TableController`.

## Key Domain Facts

- **Roles:** `admin` (full access), `kasir` (cashier — dashboard only). Enforced by `CheckRole` middleware.
- **Models use UUIDs:** `User` and `Table` use `HasUuids`. `Transaction` uses auto-increment IDs.
- **Table status:** `active` or `inactive`. Transactions have status: `active`, `completed`, `cancelled`.
- **Packages:** pricing per hour (e.g. Regular Siang Rp25,000, VIP Rp50,000).
- **UI language:** Indonesian for flash messages and user-facing strings. English for code variables.
- **Auth is username-based** (not email). Login uses `username` field.
- **User model:** `photo_path` attribute, `photo_url` accessor via `$appends`, `is_active` boolean, `role` enum.
- **EnsureUserIsActive** middleware registered globally — blocks login for disabled users.
- **Users cannot delete their own account** — only admin can delete via `/master/users`.
- **Profile page:** allows name edit + photo upload only. No self-delete, no email field.

## Conventions

- Laravel Pint is available but no custom config found — uses defaults.
- 4-space indent, LF line endings (`.editorconfig`).
- Vue pages use `<script setup>` (Composition API). No TypeScript.
- Bootstrap classes, not Tailwind, despite `@tailwindcss/forms` in devDeps (unused).
- Ziggy provides `route()` helper in JS. Use named routes.
- Auth scaffolding is Laravel Breeze.
