# Museo Sang Bata sa Negros — Laravel Web System

A comprehensive museum web system built with **PHP 8.2+ / Laravel 12**, pre-configured for MySQL (via XAMPP) or instant zero-configuration SQLite, featuring full REST API endpoints, automated migrations and seeders, printable QR passes/tags, interactive maps, AI assistant chatbot, and an admin management dashboard.

---

## 🏛️ System Features

- **PHP / Laravel Backend**: Complete RESTful API architecture powered by Laravel 12 controllers, models, and migrations.
- **Frontend Compatibility**: Seamless integration with the existing HTML5/CSS/JavaScript responsive frontend, QR scanner, interactive maps, and audio players.
- **Database Support**:
  - **MySQL (Default)**: Pre-configured for local XAMPP MySQL (`127.0.0.1:3306`).
  - **SQLite (Zero Config)**: Instant standalone file-based database for development.
- **JWT Authentication**: Secure bcrypt token-based auth for admin dashboard and museum curators.
- **Automated Seeder**: Pre-loaded with all real exhibits, marine galleries, programs, events, and curator accounts.
- **AI Chatbot**: Trilingual museum guide (English, Hiligaynon, Cebuano, Tagalog) powered by OpenRouter.
- **Visitor Check-In Kiosk**: Automated visitor pass generation with scannable QR passes.

---

## 🚀 Quick Start Guide

### Prerequisites
- **PHP 8.2+** (Installed in `C:\xampp\php\` or on your system PATH)
- **Composer** (Installed in `C:\xampp\php\` or globally)
- **MySQL / XAMPP** (Optional: SQLite works out of the box without running MySQL)

---

### Option A: Using MySQL with XAMPP (Recommended)

1. Open **XAMPP Control Panel** and click **Start** next to **MySQL**.
2. Run the automated setup script:
   - Double-click **`run_setup_mysql.bat`**
   - *Or run via terminal:*
     ```powershell
     php artisan museum:setup
     ```
   *(This automatically verifies MySQL, creates the `museum_system` database if needed, runs all migrations, and seeds the exhibits and admin credentials).*
3. Start the Laravel server:
   - Double-click **`start_server.bat`**
   - *Or run via terminal:*
     ```powershell
     php artisan serve
     ```
4. Open your browser at: **[http://localhost:8000](http://localhost:8000)**

---

### Option B: Instant Zero-Configuration with SQLite

If MySQL is not installed or currently stopped, run SQLite instantly with zero setup:

1. Run the SQLite setup script:
   - Double-click **`run_setup_sqlite.bat`**
   - *Or run via terminal:*
     ```powershell
     php artisan museum:setup --sqlite
     ```
2. Start the server:
   - Double-click **`start_server.bat`**
   - *Or run via terminal:*
     ```powershell
     php artisan serve
     ```
3. Open your browser at: **[http://localhost:8000](http://localhost:8000)**

---

## 🔑 Default Admin Credentials

- **Username**: `admin`
- **Password**: `museum-admin-2026`
- **Admin Portal**: [http://localhost:8000/admin.html](http://localhost:8000/admin.html)

*(You can customize these credentials in your `.env` file via `SEED_ADMIN_USER` and `SEED_ADMIN_PASS`).*

---

## 🧪 Running Automated Tests

Run the full feature test suite verifying health checks, API endpoints, visitor passes, ratings, and auth:

```powershell
php artisan test --filter MuseumApiTest
```

---

## Deploying to Render

The repository root contains a Render Blueprint for the Laravel Docker web service. The Blueprint deploys the app in Oregon to match the existing Render PostgreSQL database.

1. In Render, choose **New → Blueprint**, then connect `eugtemplado03-ui/museum-system-laravel` on the `main` branch.
2. When prompted, set `DB_URL` to the database's **internal URL** from its **Connect** menu. Add `GROQ_API_KEY` and `OPENROUTER_API_KEY` if you want voice synthesis and chatbot/translation features enabled.
3. Review and apply the Blueprint. Render builds `museum-system/Dockerfile`. On startup, the app runs non-destructive database migrations and seeds the initial museum content only if the exhibits table is empty.
4. After the first deploy, open the web service's **Environment** settings. Set a strong `SEED_ADMIN_PASS` before sharing access; the Blueprint generates an initial value. The admin username is `admin`.
5. Visit the service's `/api/health` URL. It checks the live database connection and returns HTTP 503 if the database is unavailable.

Do not run `php artisan museum:setup` in production: that command runs `migrate:fresh` and drops database tables. Render's web filesystem is ephemeral, so use PostgreSQL for persistent data. Uploaded files also need external or persistent storage to survive redeploys.

---

## 📂 Project Architecture

```
museum-system/
├── app/
│   ├── Console/Commands/
│   │   └── MuseumSetupCommand.php     # Custom automated setup command
│   ├── Http/
│   │   ├── Controllers/               # All REST API & admin controllers
│   │   │   ├── AuthController.php
│   │   │   ├── ExhibitController.php
│   │   │   ├── VisitorController.php
│   │   │   ├── ChatController.php
│   │   │   ├── AdminController.php
│   │   │   └── ...
│   │   └── Middleware/
│   │       └── JwtAuth.php            # Token verification middleware
│   └── Models/                        # Eloquent models (Exhibit, Visitor, etc.)
├── database/
│   ├── migrations/                    # MySQL / SQLite table schemas
│   ├── seeders/
│   │   └── DatabaseSeeder.php         # Auto-populates exhibits & admin user
│   └── data.json                      # Seed database source snapshot
├── public/                            # Web root & static frontend assets
│   ├── index.php                      # Laravel front controller
│   ├── index.html                     # Main interactive visitor portal
│   ├── admin.html                     # Curator admin dashboard
│   ├── css/                           # Museum styles
│   ├── js/                            # Client scanner & interactivity
│   └── uploads/                       # Exhibit photos and audio
├── routes/
│   ├── api.php                        # All REST API endpoints (/api/*)
│   └── web.php                        # Page routes
├── legacy_node_backup/                # Original Node.js backend backup
└── tests/Feature/
    └── MuseumApiTest.php              # Automated test suite
```

---

## 📄 License

This system is maintained for the educational and cultural preservation of Museo Sang Bata sa Negros.
