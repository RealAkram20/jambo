<?php

/*
|--------------------------------------------------------------------------
| Read This App's Own Environment, And Nobody Else's
|--------------------------------------------------------------------------
|
| This must run before the framework boots, which is why it is the first
| statement in the file rather than a service provider.
|
| Seven Laravel applications live under D:\xampp\htdocs — ArmPOS, Forever,
| Jambo, Kugawana, Precious, ingo and zagatech — and one Apache with mod_php
| serves all of them from a shared pool of worker processes.
|
| By default Laravel's dotenv loader writes every variable through THREE
| channels: $_ENV, $_SERVER and putenv(). The first two are superglobals and
| die with the request. putenv() writes into the PROCESS environment, and a
| mod_php worker outlives the request that warmed it. Laravel then reads
| through an *immutable* repository, which will not overwrite a variable it
| finds already set.
|
| Put together: whichever application warms a worker owns that worker's
| environment until it recycles, and every later request served by it reads
| the wrong values. Which worker takes a request is chance, so the symptoms
| are intermittent and look like anything but a configuration fault.
|
| It has bitten this project twice, in two different disguises:
|
|   1. `SQLSTATE[HY000] [1044] Access denied for user 'u117476830_Precious1234'`
|      — Jambo reaching the Precious database. Worse, six of the seven use
|      DB_USERNAME=root, so a bleed between those CONNECTS SUCCESSFULLY to the
|      wrong database and only fails if a table happens to be missing. The log
|      shows Jambo having queried `forever_love.movies`.
|
|   2. `419 Page Expired` on /login. Jambo reads another app's APP_KEY, cannot
|      decrypt its own session cookie, starts a fresh empty session on every
|      request, and the CSRF token can never match. The tell is thousands of
|      118-byte files in storage/framework/sessions.
|
| `Env::disablePutenv()` removes the PutenvAdapter from the repository, so
| Jambo neither writes to the process environment nor reads from it. Its
| configuration comes from its own .env every request, and another app's
| leftovers are invisible to it. It also stops Jambo leaking into its
| neighbours.
|
| This does NOT affect direct getenv() calls, which are used in exactly two
| places here — Modules/Notifications reading OPENSSL_CONF, a genuine OS
| variable set outside PHP and unaffected by any of this.
|
| Do not "simplify" this away. The failure it prevents is intermittent, reads
| as seven unrelated bugs, and has already cost this project real time.
*/

Illuminate\Support\Env::disablePutenv();

/*
|--------------------------------------------------------------------------
| Create The Application
|--------------------------------------------------------------------------
|
| The first thing we will do is create a new Laravel application instance
| which serves as the "glue" for all the components of Laravel, and is
| the IoC container for the system binding all of the various parts.
|
*/

$app = new Illuminate\Foundation\Application(
    $_ENV['APP_BASE_PATH'] ?? dirname(__DIR__)
);

/*
|--------------------------------------------------------------------------
| Bind Important Interfaces
|--------------------------------------------------------------------------
|
| Next, we need to bind some important interfaces into the container so
| we will be able to resolve them when needed. The kernels serve the
| incoming requests to this application from both the web and CLI.
|
*/

$app->singleton(
    Illuminate\Contracts\Http\Kernel::class,
    App\Http\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class
);

/*
|--------------------------------------------------------------------------
| Return The Application
|--------------------------------------------------------------------------
|
| This script returns the application instance. The instance is given to
| the calling script so we can separate the building of the instances
| from the actual running of the application and sending responses.
|
*/

return $app;
