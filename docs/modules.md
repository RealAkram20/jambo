# Jambo — Module Map

Jambo is organised as a Laravel project with a set of first-class modules
managed by [nwidart/laravel-modules](https://github.com/nwidart/laravel-modules).
Each module lives under `Modules/<Name>/` and owns its own controllers,
models, routes, views, migrations, seeders, config, service providers, and
(optionally) frontend assets. Modules can be enabled or disabled via
`modules_statuses.json` or `php artisan module:enable|disable <Name>`.

The goal of this split is feature isolation: new work (payments, streaming,
installer, etc.) goes into its own module and can be inspected, tested, or
removed without touching the rest of the codebase.

---

## Current modules

| Module | Status | Purpose | Phase |
|---|---|---|---|
| [Frontend](../Modules/Frontend/) | shipped | Public streaming site (homepage, movie/show pages, blog, merchandise). Imported from the Streamit template — all Blade views are static for now and will be wired to real data in Phase 3. | 3 |
| [Content](../Modules/Content/) | empty skeleton | Domain models and admin CRUD for movies, shows, seasons, episodes, genres, tags, categories, persons (cast/crew), ratings, reviews, comments. Will replace the static DashboardPages views. | 1–2 |
| [Subscriptions](../Modules/Subscriptions/) | empty skeleton | Subscription tiers, active subscriptions, renewals, cancellations, subscribe middleware. | 4 |
| [Payments](../Modules/Payments/) | empty skeleton | Payment gateway abstraction. First provider: PesaPal. Webhook controller, transaction logging, refund/void flows. | 4 |
| [Streaming](../Modules/Streaming/) | empty skeleton | Dropbox proxy controller (short-lived links or ranged passthrough), tier-gate middleware, `watch_history` heartbeat endpoint. | 5 |
| [Installer](../Modules/Installer/) | empty skeleton | Web-based first-time setup wizard: environment check, database credentials, admin account creation, seeder run, key generation, storage link — everything currently done by hand post-clone. | new |
| [SystemUpdate](../Modules/SystemUpdate/) | empty skeleton | In-app updater: pull new code from the release channel, run pending migrations, rebuild assets, clear caches. Opt-in, admin-only. | new |

All modules except Frontend are **empty scaffolds** right now — they were
generated with `php artisan module:make` to give us clean feature folders
ready to receive code. Nothing has been moved out of `app/` or
`resources/views/DashboardPages/` yet. The existing admin dashboard still
works exactly as it did before.

---

## What lives where (decision rules)

Use this table when deciding where to put a new file:

| Concern | Location |
|---|---|
| Cross-cutting framework code (User model, base classes, auth middleware) | `app/` |
| Admin dashboard shell, layout, sidebar, header, login pages | `app/Http/Controllers/`, `resources/views/` |
| Spatie permission/role CRUD pages | `app/Http/Controllers/{Permission,Role}Controller.php` |
| Public-site Blade views and static pages | `Modules/Frontend/` |
| Domain models and admin CRUD for streamable content | `Modules/Content/` |
| Subscription tiers and access lifecycle | `Modules/Subscriptions/` |
| Payment gateway integrations and webhooks | `Modules/Payments/` |
| Stream delivery, Dropbox proxy, watch history | `Modules/Streaming/` |
| Web-based installer and first-time setup | `Modules/Installer/` |
| In-app update mechanism | `Modules/SystemUpdate/` |

Rule of thumb: if a feature could be disabled without breaking the rest of
the app, it belongs in a module. If it's infrastructure everything else
depends on, it stays in `app/`.

---

## Module anatomy

Every generated module ships with this structure:

```
Modules/<Name>/
├── app/
│   ├── Http/
│   │   └── Controllers/<Name>Controller.php
│   └── Providers/
│       ├── <Name>ServiceProvider.php
│       └── RouteServiceProvider.php
├── config/config.php           module-local config, merged into config('<name>.*')
├── database/
│   ├── factories/
│   ├── migrations/             run with `php artisan module:migrate <Name>`
│   └── seeders/<Name>DatabaseSeeder.php
├── lang/                        (optional) module translations
├── resources/
│   ├── assets/
│   │   ├── js/app.js           module-specific entry point
│   │   └── sass/app.scss
│   └── views/
│       ├── layouts/master.blade.php
│       └── index.blade.php
├── routes/
│   ├── api.php                  loaded under `api/<name>` prefix
│   └── web.php                  loaded under `/` (module owns its own URL space)
├── tests/
├── composer.json                psr-4 autoload for Modules\<Name>\
├── module.json                  nwidart metadata (name, providers, files)
├── package.json                 (optional) module-local npm deps
└── vite.config.js               (optional) module-local vite build
```

---

## Working with modules

### Common commands (run with XAMPP PHP)

```bash
# List all modules and their status
"c:/xampp/php/php.exe" artisan module:list

# Enable / disable a module
"c:/xampp/php/php.exe" artisan module:enable <Name>
"c:/xampp/php/php.exe" artisan module:disable <Name>

# Generate a scaffold file inside a module
"c:/xampp/php/php.exe" artisan module:make-controller FooController <Name>
"c:/xampp/php/php.exe" artisan module:make-model Foo <Name>
"c:/xampp/php/php.exe" artisan module:make-migration create_foos_table <Name>
"c:/xampp/php/php.exe" artisan module:make-seed FooSeeder <Name>
"c:/xampp/php/php.exe" artisan module:make-middleware FooMiddleware <Name>
"c:/xampp/php/php.exe" artisan module:make-request FooRequest <Name>

# Run a module's migrations / seeders
"c:/xampp/php/php.exe" artisan module:migrate <Name>
"c:/xampp/php/php.exe" artisan module:seed <Name>
"c:/xampp/php/php.exe" artisan module:migrate-rollback <Name>

# Wipe and rerun a single module's migrations
"c:/xampp/php/php.exe" artisan module:migrate-fresh <Name>
```

### Routing

Each module's `routes/web.php` is loaded automatically by its
`RouteServiceProvider`. By default the routes are registered at the root
path (no prefix), so a module is free to own a namespace like `/movies`
or `/subscriptions`. To add a URL prefix, edit the module's
`app/Providers/RouteServiceProvider.php` and wrap the route registration
in `Route::prefix('movies')->group(...)`.

### Autoloading

After adding a module, run:

```bash
"c:/xampp/php/php.exe" composer dump-autoload
```

nwidart's installer already wires this up via `composer.json`, but
re-dumping is cheap and guarantees fresh classmaps.

---

## Future work

The modules listed above are **empty scaffolds**. Each phase of the work
plan fills one or more of them:

- **Phase 1–2** fills `Content/` with migrations, models, admin controllers,
  and wires the existing DashboardPages views to real data.
- **Phase 4** fills `Payments/` (PesaPal integration) and `Subscriptions/`
  (tiers, subscription model, middleware).
- **Phase 5** fills `Streaming/` (Dropbox proxy, tier gate, watch history).
- The `Installer/` and `SystemUpdate/` modules are not tied to a specific
  phase — they'll be built when we're ready to ship to non-technical users
  or self-update a deployed copy.

When a module reaches "functional", update its **Status** in the table at
the top of this file from `empty skeleton` to `shipped` (or `in progress`).
