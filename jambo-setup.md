# Jambo — Local Development Setup Guide

**Project:** Jambo Streaming Platform
**Template:** Streamit by Iqonic Design (Laravel)
**Local path:** `C:/xampp/htdocs/jambo`
**Local URL:** `http://localhost/jambo/public`

---

## Ground Rules

- Do not modify any template CSS, JS, or Blade layout files directly.
  Every customization goes through the proper config points the template provides.
- Do not invent new UI components. Use only what Streamit ships with.
- Keep the template design exactly as purchased. Only replace placeholder
  content (text, images, colors) through the correct channels.
- All custom logic (payments, streaming, subscriptions) will be added later.
  Right now the goal is a fully running template with real demo data.

---

## Requirements Check

Before running any command, confirm your environment meets these:

| Requirement         | Minimum         | How to check                        |
|---------------------|-----------------|-------------------------------------|
| PHP                 | 8.1             | Open CMD: `php -v`                  |
| Composer            | 2.x             | Open CMD: `composer -V`             |
| Node.js             | 18.x or 20.x   | Open CMD: `node -v`                 |
| npm                 | 9.x or 10.x    | Open CMD: `npm -v`                  |
| MySQL               | 8.0             | Check XAMPP control panel           |
| OpenSSL extension   | Enabled         | Check `php.ini`                     |
| GD extension        | Enabled         | Check `php.ini`                     |
| Fileinfo extension  | Enabled         | Check `php.ini`                     |
| Zip extension       | Enabled         | Check `php.ini`                     |

### Enable PHP Extensions in XAMPP

Open `C:/xampp/php/php.ini` and make sure these lines are uncommented
(remove the `;` at the start if present):

```ini
extension=openssl
extension=pdo_mysql
extension=mbstring
extension=fileinfo
extension=gd
extension=zip
```

Restart Apache in the XAMPP control panel after saving.

---

## Step 1 — Create the Database

1. Start Apache and MySQL in the XAMPP control panel.
2. Open `http://localhost/phpmyadmin` in your browser.
3. Click **New** in the left sidebar.
4. Database name: `jambo`
5. Collation: `utf8mb4_unicode_ci`
6. Click **Create**.

---

## Step 2 — Environment File

Open CMD, navigate to the project folder, and run:

```bash
cd C:/xampp/htdocs/jambo
cp .env.example .env
php artisan key:generate
```

Now open `.env` in VS Code and update the following values:

```env
APP_NAME=Jambo
APP_ENV=local
APP_KEY=        ← auto-filled by key:generate
APP_DEBUG=true
APP_URL=http://localhost/jambo/public

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=jambo
DB_USERNAME=root
DB_PASSWORD=

CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync
```

Leave all other values as the template default. Do not touch mail,
broadcasting, or any service keys at this stage.

---

## Step 3 — Install PHP Dependencies

In CMD from `C:/xampp/htdocs/jambo`:

```bash
composer install
```

This installs all Laravel and template PHP packages. It will take 1 to 3 minutes.
If Composer is not found, download it from `https://getcomposer.org` and install globally.

---

## Step 4 — Install Node Dependencies and Compile Assets

```bash
npm install
```

Once complete, run:

```bash
npm run dev
```

Leave this terminal open and running. It watches your assets and recompiles
on change. Open a second CMD window for all remaining commands.

---

## Step 5 — Run Migrations and Seeders

In a second CMD window:

```bash
cd C:/xampp/htdocs/jambo
php artisan migrate
php artisan db:seed
```

This creates all the tables and populates the database with the template's
demo data: movies, shows, categories, users, comments, and ratings.
The template ships with this seeder — it makes the frontend look complete immediately.

---

## Step 6 — Set Folder Permissions

On Windows with XAMPP, permissions are generally not an issue. But if you
see any storage or cache errors, run:

```bash
php artisan storage:link
```

And manually ensure these folders exist (create them if missing):

```
C:/xampp/htdocs/jambo/storage/framework/cache
C:/xampp/htdocs/jambo/storage/framework/sessions
C:/xampp/htdocs/jambo/storage/framework/views
C:/xampp/htdocs/jambo/storage/logs
```

---

## Step 7 — Access the Application

Open your browser and visit:

**Frontend (user-facing):**
```
http://localhost/jambo/public
```

**Admin Dashboard:**
```
http://localhost/jambo/public/dashboard
```

**Login credentials (from the demo seeder):**

| Role  | Email                 | Password |
|-------|-----------------------|----------|
| Admin | admin@example.com     | password |
| User  | user@example.com      | password |

---

## Step 8 — Optional: Clean Local URL (Recommended)

Instead of typing `/jambo/public` every time, set up a virtual host so
the project runs at `http://jambo.test`.

### 8a. Edit the XAMPP Virtual Hosts File

Open `C:/xampp/apache/conf/extra/httpd-vhosts.conf` and add at the bottom:

```apacheconf
<VirtualHost *:80>
    DocumentRoot "C:/xampp/htdocs/jambo/public"
    ServerName jambo.test
    <Directory "C:/xampp/htdocs/jambo/public">
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

### 8b. Edit the Windows Hosts File

Open Notepad as Administrator, then open:
`C:/Windows/System32/drivers/etc/hosts`

Add this line at the bottom:

```
127.0.0.1   jambo.test
```

Save and close.

### 8c. Update .env

```env
APP_URL=http://jambo.test
```

### 8d. Restart Apache

Click Stop then Start on Apache in the XAMPP control panel.

Now visit `http://jambo.test` and the app loads directly — no `/public` in the URL.

---

## Step 9 — Verify All Template Pages Are Working

Go through each of these pages and confirm they load with demo data:

### Frontend Pages

| Page                    | URL                                      |
|-------------------------|------------------------------------------|
| Homepage                | `http://jambo.test/`                     |
| Movie list              | `http://jambo.test/movies`               |
| Movie detail            | `http://jambo.test/movie/{slug}`         |
| Show list               | `http://jambo.test/shows`                |
| Show detail / Seasons   | `http://jambo.test/show/{slug}`          |
| Episodes                | `http://jambo.test/show/{slug}/season/1` |
| Category list           | `http://jambo.test/category/{slug}`      |
| Pricing page            | `http://jambo.test/pricing`              |
| Login                   | `http://jambo.test/login`                |
| Register                | `http://jambo.test/register`             |
| User profile            | `http://jambo.test/profile`              |
| User privacy settings   | `http://jambo.test/privacy-setting`      |

### Dashboard Pages (Admin)

| Page                  | URL                                         |
|-----------------------|---------------------------------------------|
| Dashboard home        | `http://jambo.test/dashboard`               |
| Analytics             | `http://jambo.test/dashboard/analytics`     |
| Movie list            | `http://jambo.test/dashboard/movies`        |
| Add movie             | `http://jambo.test/dashboard/movies/create` |
| Show list             | `http://jambo.test/dashboard/shows`         |
| Seasons               | `http://jambo.test/dashboard/seasons`       |
| Episodes              | `http://jambo.test/dashboard/episodes`      |
| Category list         | `http://jambo.test/dashboard/categories`    |
| Comments              | `http://jambo.test/dashboard/comments`      |
| User list             | `http://jambo.test/dashboard/users`         |
| User profile (admin)  | `http://jambo.test/dashboard/profile`       |

---

## Common Errors and Fixes

### "Class not found" or autoload errors

```bash
composer dump-autoload
```

### Blank page with no error

Set `APP_DEBUG=true` in `.env`, then visit the page again to see the actual error.

### "No application encryption key has been specified"

```bash
php artisan key:generate
```

### Assets not loading (CSS/JS 404)

Make sure `npm run dev` is still running in its terminal window.
If you stopped it, run it again:

```bash
npm run dev
```

### Migration error: "Table already exists"

```bash
php artisan migrate:fresh --seed
```

This drops all tables and re-runs everything from scratch.
Only use this on local — never on a live database.

### "could not find driver" (MySQL PDO)

Open `C:/xampp/php/php.ini`, find and uncomment:

```ini
extension=pdo_mysql
```

Restart Apache.

### Storage permission errors

```bash
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan storage:link
```

---

## Useful Local Development Commands

Run these from `C:/xampp/htdocs/jambo`:

```bash
# Clear all caches (run this after any .env or config change)
php artisan optimize:clear

# Re-run migrations and seeders from scratch
php artisan migrate:fresh --seed

# Watch assets (keep running in its own terminal)
npm run dev

# Build assets for production (when deploying to live server later)
npm run build

# Check all registered routes
php artisan route:list

# Open Laravel's interactive console
php artisan tinker
```

---

## Project Folder Structure (Streamit Laravel)

```
jambo/
├── app/
│   ├── Http/
│   │   ├── Controllers/        ← All controllers
│   │   └── Middleware/         ← Route middleware
│   └── Models/                 ← Eloquent models
├── Modules/
│   └── frontend/               ← Frontend module (Streamit)
│       └── resources/views/    ← All frontend Blade views
├── public/
│   ├── dashboard/              ← Dashboard CSS, JS, images
│   └── frontend/               ← Frontend CSS, JS, images
├── resources/
│   └── views/
│       ├── DashboardPages/     ← All admin Blade views
│       │   ├── movie/
│       │   ├── show/
│       │   ├── category/
│       │   └── user/
│       ├── auth/               ← Login, register, 2FA views
│       └── layouts/            ← Master layout files
├── routes/
│   └── web.php                 ← All routes
├── database/
│   ├── migrations/             ← Table definitions
│   └── seeders/                ← Demo data seeders
├── .env                        ← Your local config
└── vite.config.js              ← Asset bundler config
```

---

## What Comes Next (After Template is Running)

Once every page above loads correctly with demo data, we move to Phase 1
of the build plan in this order:

1. Custom database migrations (subscriptions, transactions, watchlist, watch history)
2. Extend the movie model with Dropbox file path fields and tier access
3. PesaPal payment integration
4. Dropbox proxy streaming controller
5. Tier-gated middleware
6. Admin subscription management panel
7. Live server deployment to Hostinger VPS

None of these touch the template design. They only add backend logic and
new routes on top of the existing template structure.

---

*Get the template running first. Once every page loads, come back and we start Phase 1.*
