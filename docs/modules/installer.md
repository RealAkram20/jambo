# Web Installer — Reusable Pattern

A first-time setup wizard that lets a non-technical user install a Laravel
app from a browser: checks server requirements, writes `.env`, creates
the database, runs migrations + seeders, and creates the first admin user.

Based on the implementation at
[github.com/RealAkram20/Forever-Loved-updates](https://github.com/RealAkram20/Forever-Loved-updates)
(`app/Http/Controllers/InstallController.php`), with the parts worth reusing
and the parts worth improving.

---

## What the installer does, in order

1. **Requirements check** — PHP version, required extensions, writable
   directories. Pass/fail summary with red/green rows.
2. **Database form** — host, port, name, user, password. AJAX validate button
   that tests the connection with a plain PDO call and offers to create the
   database if it doesn't exist.
3. **App settings form** — app name, app URL, environment (local/production).
4. **Admin account form** — name, email, password (min 8 chars, confirmation).
5. **Run step** — a progress page that runs the remaining work in sequence
   via AJAX calls, showing each step's result in real time.
6. **Completion page** — tells the user where to log in and offers a "delete
   installer" button (which just flips the installed flag).

Steps 1–4 store their output in the session under `install.*` keys, then
step 5 reads them, persists them to `storage/app/install-data.json`, clears
the session (because the DB connection is about to change), and runs the
remaining work by reading from that JSON.

---

## Architecture at a glance

```
┌───────────────────────────────┐
│ InstallMiddleware             │
│ if (!file_exists(             │
│   storage/installed))         │
│   → redirect to /install      │
└──────────────┬────────────────┘
               │
      ┌────────▼─────────┐
      │ /install routes  │
      │ GET  requirements│
      │ POST database    │
      │ POST settings    │
      │ POST admin       │
      │ GET  run         │──► JS runs AJAX to /install/execute/{step}
      │ POST execute/{n} │
      │ GET  complete    │
      └──────────────────┘
               │
               ▼
   storage/app/install-data.json  ← holds settings + admin creds + last_completed_step
               │
    after step 8 (success):
               ▼
   storage/installed  ← JSON {installed_at, version} — middleware looks for this file
```

**Key insight:** the install flag is a *file on disk*, not a database row.
This is deliberate: the database may not exist yet, and the session driver
may need to be swapped to `file` before the DB table exists.

---

## File layout to reproduce

```
app/
├── Http/
│   ├── Controllers/
│   │   └── InstallController.php      # ~500 lines, all wizard logic
│   └── Middleware/
│       └── InstallMiddleware.php      # the gate
routes/
└── install.php                         # dedicated routes file, loaded
                                        # only when installer is needed
resources/
└── views/
    └── pages/install/
        ├── layout.blade.php            # shared wizard shell
        ├── requirements.blade.php      # step 1
        ├── database.blade.php          # step 2
        ├── app-settings.blade.php      # step 3
        ├── admin.blade.php             # step 4
        ├── progress.blade.php          # step 5 — runs AJAX for steps 1-8
        └── complete.blade.php          # step 6
```

---

## Routes

| Method | URI | Controller method | Purpose |
|---|---|---|---|
| GET | `/install` | `requirements()` (via redirect) | entry |
| GET | `/install/requirements` | `requirements()` | step 1 UI |
| GET | `/install/database` | `database()` | step 2 form |
| POST | `/install/database/validate` | `validateDatabase()` | AJAX: test PDO connection, return JSON |
| POST | `/install/database` | `storeDatabase()` | save to session, proceed |
| GET | `/install/settings` | `appSettings()` | step 3 form |
| POST | `/install/settings` | `storeAppSettings()` | save to session |
| GET | `/install/admin` | `adminAccount()` | step 4 form |
| POST | `/install/admin` | `storeAdmin()` | save to session |
| GET | `/install/run` | `run()` | step 5: write install-data.json, show progress page |
| POST | `/install/execute/{step}` | `executeStep($step)` | runs a single numbered step, returns JSON |
| GET | `/install/complete` | `complete()` | done |

All routes belong to the `web` middleware group but **must not** require
`auth` — they run before any user exists.

---

## The gate middleware

```php
class InstallMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $flag = storage_path('installed');
        $onInstallRoute = $request->is('install*') || $request->is('install/*');

        if (!file_exists($flag) && !$onInstallRoute) {
            return redirect('/install');
        }

        if (file_exists($flag) && $onInstallRoute) {
            return redirect('/');  // installer is locked after install
        }

        return $next($request);
    }
}
```

Register globally in `Kernel::$middleware` (not just the web group) so it
runs on API calls and assets too. Then publish it alongside Laravel's
defaults.

**Critical detail:** during the install session, Laravel's default session
driver is `database`, but the DB doesn't exist yet — Laravel would crash on
`session_start()`. Swap the session driver to `file` inside the middleware
for install routes only:

```php
if ($onInstallRoute && !file_exists($flag)) {
    config(['session.driver' => 'file']);
}
```

---

## Writing the `.env` file

Read the existing `.env` (or `.env.example`), replace the target keys with
new values, write back:

```php
private function writeEnv(array $values): void
{
    $path = base_path('.env');
    $content = File::exists($path)
        ? File::get($path)
        : File::get(base_path('.env.example'));

    foreach ($values as $key => $value) {
        $escaped = addcslashes($value, '"\\$');
        $line = "$key=\"$escaped\"";

        if (preg_match("/^$key=.*$/m", $content)) {
            $content = preg_replace("/^$key=.*$/m", $line, $content);
        } else {
            $content .= "\n$line";
        }
    }

    File::put($path, $content);
}
```

**Always** quote the values and escape `"`, `\`, `$` — passwords containing
special characters will otherwise corrupt `.env` (production outage
territory).

Keys written by a typical installer:

```
APP_NAME        APP_ENV          APP_KEY (generated)     APP_DEBUG
APP_URL
DB_CONNECTION=mysql    DB_HOST    DB_PORT    DB_DATABASE    DB_USERNAME    DB_PASSWORD
SESSION_DRIVER=database    SESSION_LIFETIME=120
CACHE_STORE=database
QUEUE_CONNECTION=sync
```

Optional (only if the app uses them): `MAIL_*`, `PESAPAL_VERIFY_SSL`, etc.

---

## Running migrations / seeders / artisan from a web request

```php
Artisan::call('key:generate', ['--force' => true]);

Artisan::call('migrate', ['--force' => true]);

Artisan::call('db:seed', [
    '--class' => 'RoleSeeder',
    '--force' => true,
]);

Artisan::call('storage:link');
Artisan::call('config:clear');
Artisan::call('route:clear');
Artisan::call('view:clear');
```

Set a high PHP timeout before these (`set_time_limit(300)`) — migrations on
a slow shared host can easily take 60+ seconds.

**Gotcha:** after writing `.env`, call
`(new \Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables())->bootstrap($app)`
or just redirect to a new request — the current PHP process still has the
old env in memory and will connect to the wrong DB otherwise.

---

## Creating the first admin

```php
$admin = User::firstOrCreate(
    ['email' => $data['admin']['email']],
    [
        'name' => $data['admin']['name'],
        'password' => Hash::make($data['admin']['password']),
        'email_verified_at' => now(),
    ]
);

$admin->assignRole('super-admin');   // spatie/laravel-permission
```

---

## The AJAX progress pattern

Step 5 renders a page with one `<div>` per step and a small JS that calls
`POST /install/execute/{n}` in order:

```js
async function runSteps(lastCompleted) {
    for (let step = lastCompleted + 1; step <= 8; step++) {
        setStepStatus(step, 'running');
        try {
            const res = await fetch(`/install/execute/${step}`, { method: 'POST' });
            const json = await res.json();
            if (!json.ok) throw new Error(json.error);
            setStepStatus(step, 'done');
        } catch (e) {
            setStepStatus(step, 'failed', e.message);
            return;  // stop on first failure; user can click Retry
        }
    }
    location.href = '/install/complete';
}
```

On the server, each step returns `{ok: true}` or `{ok: false, error: "..."}`.
On failure, write the current step number into `install-data.json` so a retry
picks up where it left off.

---

## The 8 execution steps

| # | Action |
|---|---|
| 1 | Write `.env` from `install-data.json` |
| 2 | `php artisan key:generate --force` |
| 3 | `php artisan migrate --force` |
| 4 | Run core seeders (`RoleSeeder`, any plan/permission seeders) |
| 5 | Create admin user, assign `super-admin` role |
| 6 | `php artisan storage:link` |
| 7 | Clear config/route/view caches |
| 8 | Write `storage/installed` with `{installed_at, version}` + delete `install-data.json` |

---

## Gotchas and clever bits

- **File flag, not DB row.** The "installed" marker is a file because the
  DB may be in an unknown state while the installer runs.
- **Session driver swap.** Switch to file sessions inside the installer so
  the wizard can progress before the `sessions` table exists.
- **Password escaping in `.env`.** Use `addcslashes($value, '"\\$')` or you
  will corrupt `.env` when an admin uses a password containing `$` or `"`.
- **Retry resumption.** Persist `last_completed_step` so a mid-step crash
  doesn't force the user to re-enter everything.
- **PDO, not Laravel DB facade, for step 2.** Laravel's DB manager caches
  the connection config; testing a candidate DB with plain PDO avoids
  poisoning Laravel's config.
- **Delete install-data.json on success.** It contains the admin password
  in plain text — purge it immediately after use.
- **Lock the installer after success.** The middleware redirects
  `/install*` → `/` once `storage/installed` exists. Bonus: add a CLI
  `php artisan app:reset-install` for developers.
- **Version tag.** Write the current app version (read from `version.txt`
  or `config('app.version')`) into `storage/installed` — the system
  updater will use it later to know the starting point.

---

## How to rebuild in Jambo

Drop this into [Modules/Installer/](../../Modules/Installer/):

1. `Modules/Installer/app/Http/Controllers/InstallController.php` — 8-step wizard
2. `Modules/Installer/app/Http/Middleware/EnsureInstalled.php` — the gate
3. `Modules/Installer/routes/web.php` — install routes (no auth middleware)
4. `Modules/Installer/resources/views/install/` — wizard Blade views
5. Register `EnsureInstalled` in `app/Http/Kernel.php` as a global middleware
6. Add `storage/installed` to `.gitignore`
7. Use the Jambo dashboard layout (Prime Video default) for the wizard shell
   so the installer already matches the brand

When Jambo reaches Phase 0 completion, add a CLI helper:
`php artisan jambo:reset-install` → deletes `storage/installed` and the
sqlite/mysql database, so developers can re-test the wizard cleanly.
