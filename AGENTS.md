# PoolStream - Agent Guide

Pool hall management app: billiard table control, timed sessions, F&B orders.
Laravel 13 + Inertia.js + Vue 3 + Bootstrap 5 (not Tailwind). SQLite database.

## Commands

- `composer setup` — full first-time setup (install, .env, key, migrate, npm, build)
- `composer dev` — runs concurrently: artisan serve, queue worker, vite, schedule:work
- `composer test` — clears config then runs `php artisan test`
- `php artisan migrate --force` — run migrations
- `npm run dev` — vite dev server only
- `php artisan tables:auto-checkout` — manually trigger expired session checkout (also runs every minute via scheduler)

Tests use **in-memory SQLite** (configured in `phpunit.xml`). No external services needed.

## Architecture

- **Frontend:** Vue 3 pages in `resources/js/Pages/`, resolved via Inertia. Components in `resources/js/Components/`. Custom SCSS in `resources/scss/app.scss` using `bb-` prefixed class names (Bootstrap 5 theme).
- **Backend:** Controllers in `app/Http/Controllers/`. Models in `app/Models/`. Middleware `CheckRole` at `app/Http/Middleware/CheckRole.php` — registered as `role` alias in `bootstrap/app.php`.
- **Routes:** `routes/web.php` (app routes), `routes/auth.php` (Breeze auth). Admin routes under `role:admin` middleware at `/master/*`.
- **Database:** SQLite at `database/database.sqlite`. Migrations in `database/migrations/`.
- **Hardware:** `app/Services/relay_controller.py` — Python script for USB HID relay control, called via `shell_exec` from `TableController` and `AutoCheckoutExpiredTables`.
- **Scheduled command:** `tables:auto-checkout` auto-completes transactions past `expected_end_time` and turns off relays. Runs every minute.

## Key Domain Facts

- **Roles:** `admin` (full access), `kasir` (cashier — dashboard only). Enforced by `CheckRole` middleware.
- **Models use UUIDs:** `User` and `Table` use `HasUuids`. `Transaction` uses auto-increment IDs.
- **Table status:** `active` or `inactive`. Transactions have status: `active`, `completed`, `cancelled`.
- **Transaction types:** `billiard` (table session) or `fnb_only` (standalone F&B order). `table_id` is nullable for F&B-only orders.
- **Packages:** pricing per hour (e.g. Regular Siang Rp25,000, VIP Rp50,000).
- **UI language:** Indonesian for flash messages and user-facing strings. English for code variables.
- **Auth is username-based** (not email). No email field on users. Login uses `username`. Password reset tokens keyed by `username`.
- **User model:** `photo_path` attribute, `photo_url` accessor via `$appends`, `is_active` boolean, `role` enum. Uses PHP attribute-based `#[Fillable]` and `#[Hidden]`.
- **EnsureUserIsActive** middleware in global web stack — logs out and blocks disabled users.
- **Users cannot delete their own account** — only admin can delete via `/master/users`.
- **Profile page:** allows name edit + photo upload only. No self-delete, no email field.
- **File uploads:** user photos stored at `storage/app/public/user_photos/` via `public` disk.

## Conventions

- Laravel Pint is available but no custom config — uses defaults.
- 4-space indent, LF line endings (`.editorconfig`).
- Vue pages use `<script setup>` (Composition API). No TypeScript. Path alias: `@/` → `resources/js/`.
- Bootstrap classes, not Tailwind, despite `@tailwindcss/forms` in devDeps (unused).
- Ziggy provides `route()` helper in JS. Use named routes.
- Auth scaffolding is Laravel Breeze.
- Vite base path is `/poolstream/public/build/` — not the default.
- SCSS compiles with deprecation warnings silenced (`color-functions`, `global-builtin`, `import`, `mixed-decls`) in vite config.
