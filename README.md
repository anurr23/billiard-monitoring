<p align="center">
  <br>
  <h1 align="center">🎱 PoolStream</h1>
  <p align="center">
    <strong>Billiard & Lounge Management System</strong>
  </p>
</p>

---

## 📖 About

**PoolStream** is a billiard hall management application built with Laravel 13 + Inertia.js + Vue 3 + Bootstrap 5. It handles table session management, F&B ordering, automated relay hardware control, and receipt printing.

## ✨ Features

- 🕒 **Table Session Management** — Start, monitor, and check out billiard sessions with live countdown timers and auto-checkout for expired sessions.
- 🍔 **Integrated F&B Ordering** — Order food and drinks from tables or as standalone orders. Edit quantities or remove items while orders are active.
- 💡 **Automated Hardware Control** — USB HID relay integration (Python) to turn table lights on/off with session start/end.
- 💳 **Smart Checkout & Receipts** — Detailed receipts combining billiard and F&B costs. Printable via browser.
- 🌓 **Dynamic Theming** — Light and Dark modes with Bootstrap 5 custom SCSS.
- 🛡️ **Role-Based Access** — `admin` (full access) and `kasir` (cashier, dashboard only).
- 📱 **Responsive Layout** — Overlay sidebar works across desktop, tablet, and mobile.

## 🛠️ Stack

- **Backend:** Laravel 13 (PHP 8.3+)
- **Frontend:** Vue 3 (Composition API) + Inertia.js
- **Styling:** Bootstrap 5 (Custom SCSS with `bb-` prefix)
- **Database:** SQLite
- **Hardware:** Python (PyUSB for relay control)

## 🚀 Setup

```bash
composer setup
composer dev
```

Then access `http://localhost:8000`. Login uses **username**, not email.

Default accounts seeded: `admin` / `kasir` — both with password `password`.

## 📁 Structure

- `app/Http/Controllers/` — Controllers for tables, transactions, F&B, users
- `app/Models/` — Eloquent models (User, Table with UUIDs; Transaction auto-increment)
- `app/Console/Commands/AutoCheckoutExpiredTables.php` — scheduled auto-checkout
- `app/Services/relay_controller.py` — USB HID relay bridge
- `resources/js/Pages/` — Inertia Vue 3 pages
- `resources/js/Components/` — Shared Vue components (`BbSelect`, modal, form elements)
- `resources/scss/app.scss` — Custom Bootstrap 5 theme

## 🔒 Roles

- **Admin** — `/master/*` routes: manage tables, packages, F&B items, users
- **Kasir** — Dashboard: handle sessions, F&B orders, checkout

## 📄 License

MIT
