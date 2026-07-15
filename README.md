<p align="center">
  <br>
  <img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="200" alt="Laravel Logo">
  <br>
  <h1 align="center">🎱 PoolStream</h1>
  <p align="center">
    <strong>Billiard & Lounge Management System</strong>
  </p>
</p>

---

## 📖 About PoolStream

**PoolStream** is a modern, comprehensive management application tailored specifically for billiard halls and lounges. Built on top of the robust Laravel 13 framework and powered by an interactive Vue 3 (Inertia.js) frontend, it provides an all-in-one solution for handling billiard table sessions, Food & Beverage (F&B) transactions, and automated hardware control (lighting relays).

The system prioritizes speed, ease of use, and a beautiful UI, allowing cashiers (*Kasir*) to effortlessly manage incoming customers, while offering administrators complete control over inventory, pricing, and system users.

## ✨ Key Features

- 🕒 **Table Session Management**: Start, monitor, and edit active billiard sessions seamlessly. Time tracking is precise and automated.
- 🍔 **Integrated F&B Ordering**: Customers can order food and drinks directly to their active billiard tables or independently. Total costs are automatically aggregated upon checkout.
- 💡 **Automated Hardware Control**: Integrates directly with USB HID relays (via Python script) to automatically turn on table lights when a session starts and turn them off when it ends.
- 💳 **Smart Checkout & Receipt Printing**: Generates detailed, neatly formatted receipts summarizing both billiard playtime and F&B consumption.
- 🌓 **Dynamic Theming**: Features a gorgeous, responsive UI supporting both Light and Dark modes.
- 🛡️ **Role-Based Access**: Secure role separation between `admin` (full access) and `kasir` (dashboard operations only).

## 🛠️ Technology Stack

- **Backend**: Laravel 13 (PHP)
- **Frontend**: Vue 3 (Composition API) + Inertia.js
- **Styling**: Bootstrap 5 (Custom SCSS Theming)
- **Database**: SQLite (Zero-configuration database)
- **Hardware Integration**: Python (PyUSB for relay control)

## 🚀 Installation & Setup

PoolStream is designed to be extremely easy to set up.

1. **Clone the repository:**
   ```bash
   git clone https://github.com/anurr23/billiard-monitoring.git
   cd billiard-monitoring
   ```

2. **Run the initial setup:**
   PoolStream includes a custom composer script that handles full initialization (copying `.env`, generating keys, migrating the database, and building NPM assets).
   ```bash
   composer setup
   ```

3. **Start the Development Server:**
   This command concurrently runs the PHP development server, queue worker, and Vite asset bundler.
   ```bash
   composer dev
   ```

4. **Login:**
   Access the app at `http://localhost:8000`. Login using your configured username and password (note: authentication uses the `username` field).

## 📂 Project Structure

- `app/Http/Controllers/`: Contains the core logic for transactions, table management, and user roles.
- `app/Models/`: Eloquent models (Uses UUIDs for primary entities like `User` and `Table`).
- `app/Services/relay_controller.py`: The Python hardware bridge for controlling table lighting.
- `database/database.sqlite`: The primary data store.
- `resources/js/Pages/`: Vue 3 frontend components, routed via Inertia.
- `resources/scss/app.scss`: Centralized styling and theme tokens (Bootstrap 5 customizations).

## 🔒 Roles & Permissions

- **Admin**: Has access to `/master/*` routes to manage users, add/remove tables, update F&B inventory, and configure pricing packages.
- **Kasir (Cashier)**: Restricted to the main dashboard to handle daily operations, table checkout, and F&B orders. Users cannot delete their own accounts.

## 📄 License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
