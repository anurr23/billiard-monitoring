# PoolStream - Agent Guide

Pool hall management: billiard table control, timed sessions, F&B orders.
Laravel 13 + Inertia.js + Vue 3 + Bootstrap 5. SQLite in test/dev, PostgreSQL in production.

## Commands

- `composer setup` - full first-time setup (install, .env, key, migrate, npm, build)
- `composer dev` - runs concurrently: artisan serve, queue worker, vite, schedule:work
- `composer test` - clears config then runs `php artisan test`
- `php artisan tables:auto-checkout` - manually trigger expired session checkout (runs every minute via scheduler)
- `npm run dev` - vite dev server only
- `php artisan test --filter TestName` - run a single test or class

## Architecture

- **Frontend:** Vue 3 `<script setup>` (no TS) in `resources/js/Pages/`, resolved via Inertia. Components in `resources/js/Components/`. Custom SCSS (`bb-` prefix classes) at `resources/scss/app.scss`. `@/` alias -> `resources/js/`. Ziggy `route()` helper available globally.
- **Backend:** Controllers in `app/Http/Controllers/`. Models in `app/Models/`. All routes manually defined (no `Route::resource`).
- **Routes:** `routes/web.php` (app), `routes/auth.php` (Breeze). Admin routes under `role:admin` middleware at `/master/*`. No `/register` route -- admin-only user creation via `/master/users`.
- **Database:** `.env` uses **PostgreSQL** (`billiard` DB, `pgsql` driver). `.env.example` uses **SQLite**. Tests use **in-memory SQLite** (`phpunit.xml`). Sessions/queue/cache all database-backed (non-default).
- **Hardware:** `app/Services/relay_controller.py` called via `shell_exec` -- **dummy placeholder** (real `hidapi` code commented out).
- **Scheduler:** `tables:auto-checkout` completes expired transactions and turns off relays. Every minute.

## Domain Model

- **Roles:** `admin` (full access), `kasir` (dashboard only). Enforced by `CheckRole` middleware.
- **UUIDs:** `User` and `Table` use `HasUuids`. `Transaction`, `TransactionItem`, `Package`, `FnbItem` use auto-increment.
- **Transactions:** status `active|completed|cancelled`, type `billiard` (table session) or `fnb_only` (standalone F&B). `table_id` nullable for F&B-only. Cost fields (`billiard_cost`, `fnb_cost`, `total_cost`) recalculated inline in multiple controllers (no shared service).
- **Auth:** username-based (no email column). `password_reset_tokens` keys on `username`. Login via `username` field.
- **User model:** `photo_path` + `photo_url` accessor, `is_active` boolean, `role` enum, PHP attribute `#[Fillable]`/`#[Hidden]`.

## Tests (WARNING: mostly stale Breeze scaffolding)

- Only `UserFactory` exists. No factories for `Table`, `Transaction`, `Package`, `FnbItem`.
- Profile tests reference `email`/`email_verified_at`/self-delete -- **none exist in the real app**.
- Auth tests POST `email` field -- real app uses `username`. These tests would fail.
- Email verification tests test a feature that doesn't exist.
- **Zero coverage** of any domain logic (tables, transactions, F&B, packages, roles, relay control, auto-checkout).

## Conventions & Gotchas

- **Bootstrap 5 classes only** (no Tailwind despite `@tailwindcss/forms` in devDeps).
- **Fixed overlay sidebar** (`position: fixed`, `translateX(-100%)`) at all screen sizes.
- **Dark-by-default** theme via `data-bs-theme` on `<html>`, persisted to `localStorage('theme')`.
- **UI strings in Indonesian** (`"Meja berhasil ditambahkan"`), code in English.
- **File uploads:** user photos -> `storage/app/public/user_photos/`, F&B images -> `fnb_images/`. Always use `FormData` + `forceFormData: true`. Client-side image compression in FnbItems.vue (800px max, 85%).
- **Cost recalculation** is duplicated across `TableController`, `TransactionItemController`, `FnbOrderController` -- no shared helper.
- **Name auto-capitalization**: `.replace(/\b\w/g, l => l.toUpperCase())` used in multiple forms.
- **Receipt printing:** inline HTML in `window.open()` with 80mm thermal receipt CSS.
- **Inconsistencies:** User update uses `POST` (not PUT/PATCH). Two PATCH routes to `updateQuantity`. `ConfirmablePasswordController` references `$request->user()->email` (property doesn't exist). `Table` model has dead casts for columns dropped in migration.
- **Vite base path:** `/poolstream/public/build/` (not default). SCSS deprecation warnings silenced in config.
- **4-space indent, LF line endings** (`.editorconfig`).
- **No pagination** in backend queries (all `->get()`).
- **`FnbItem` model lacks `HasFactory`** trait (unlike other models).
