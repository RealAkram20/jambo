<?php

namespace Modules\Installer\app\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use PDO;
use PDOException;
use Throwable;

/**
 * Core installer actions. Pure service — no HTTP, no request state.
 * The controller stores wizard input in a JSON state file and calls
 * these methods one by one via AJAX.
 */
class InstallerService
{
    public const STATE_FILE = 'install-data.json';
    public const FLAG_FILE = 'installed';

    /** Total number of run-phase steps. */
    public const STEP_COUNT = 8;

    /* -------------------------------------------------------------------- */
    /* State file                                                           */
    /* -------------------------------------------------------------------- */

    public function stateFilePath(): string
    {
        return storage_path('app/' . self::STATE_FILE);
    }

    public function flagFilePath(): string
    {
        return storage_path(self::FLAG_FILE);
    }

    public function isInstalled(): bool
    {
        return File::exists($this->flagFilePath());
    }

    public function loadState(): array
    {
        $path = $this->stateFilePath();
        if (!File::exists($path)) {
            return ['last_completed_step' => 0];
        }
        $decoded = json_decode(File::get($path), true);
        return is_array($decoded) ? $decoded : ['last_completed_step' => 0];
    }

    public function saveState(array $state): void
    {
        File::ensureDirectoryExists(dirname($this->stateFilePath()));
        File::put($this->stateFilePath(), json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    public function clearState(): void
    {
        File::delete($this->stateFilePath());
    }

    /* -------------------------------------------------------------------- */
    /* Requirements check                                                   */
    /* -------------------------------------------------------------------- */

    public function checkRequirements(): array
    {
        $requiredExtensions = [
            'openssl', 'pdo', 'mbstring', 'tokenizer', 'xml', 'ctype',
            'json', 'gd', 'fileinfo', 'zip', 'exif',
        ];

        $rows = [
            [
                'label' => 'PHP >= 8.1',
                'pass' => version_compare(PHP_VERSION, '8.1.0', '>='),
                'detail' => PHP_VERSION,
            ],
        ];

        foreach ($requiredExtensions as $ext) {
            $rows[] = [
                'label' => ucfirst($ext) . ' extension',
                'pass' => extension_loaded($ext),
                'detail' => extension_loaded($ext) ? 'enabled' : 'missing',
            ];
        }

        $writables = [
            'storage/' => storage_path(),
            'bootstrap/cache/' => base_path('bootstrap/cache'),
            '.env' => base_path('.env'),
        ];

        foreach ($writables as $label => $path) {
            $rows[] = [
                'label' => "$label writable",
                'pass' => File::exists($path)
                    ? is_writable($path)
                    : is_writable(dirname($path)),
                'detail' => File::exists($path) ? 'exists' : 'missing — will be created',
            ];
        }

        $allOk = collect($rows)->every(fn ($r) => $r['pass']);

        return compact('rows', 'allOk');
    }

    /* -------------------------------------------------------------------- */
    /* Database connection test                                             */
    /* -------------------------------------------------------------------- */

    public function testDatabaseConnection(array $creds): array
    {
        $host = $creds['host'] ?? '127.0.0.1';
        $port = $creds['port'] ?? '3306';
        $database = $creds['database'] ?? '';
        $username = $creds['username'] ?? 'root';
        $password = $creds['password'] ?? '';

        try {
            $pdo = new PDO(
                "mysql:host=$host;port=$port",
                $username,
                $password,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 5]
            );

            $stmt = $pdo->query("SHOW DATABASES LIKE " . $pdo->quote($database));
            $exists = $stmt->fetch() !== false;

            if (!$exists) {
                $pdo->exec(sprintf(
                    'CREATE DATABASE `%s` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci',
                    str_replace('`', '', $database)
                ));
                return ['ok' => true, 'message' => "Database '$database' created."];
            }

            return ['ok' => true, 'message' => "Connected. Database '$database' exists."];
        } catch (PDOException $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }

    /* -------------------------------------------------------------------- */
    /* Step runners — executed one at a time by the progress page           */
    /* -------------------------------------------------------------------- */

    public function runStep(int $step, array $state): array
    {
        try {
            match ($step) {
                1 => $this->stepWriteEnv($state),
                2 => $this->stepKeyGenerate(),
                3 => $this->stepMigrate(),
                4 => $this->stepSeed(),
                5 => $this->stepCreateAdmin($state),
                6 => $this->stepStorageLink(),
                7 => $this->stepClearCaches(),
                8 => $this->stepWriteFlag(),
                default => throw new \InvalidArgumentException("Unknown step: $step"),
            };

            $state['last_completed_step'] = $step;
            $this->saveState($state);

            return ['ok' => true, 'step' => $step];
        } catch (Throwable $e) {
            return [
                'ok' => false,
                'step' => $step,
                'error' => $e->getMessage(),
                'trace' => app()->hasDebugModeEnabled() ? $e->getTraceAsString() : null,
            ];
        }
    }

    private function stepWriteEnv(array $state): void
    {
        $env = [
            'APP_NAME' => $state['settings']['app_name'] ?? 'Jambo',
            'APP_ENV' => $state['settings']['app_env'] ?? 'local',
            'APP_DEBUG' => ($state['settings']['app_env'] ?? 'local') === 'production' ? 'false' : 'true',
            'APP_URL' => $state['settings']['app_url'] ?? 'http://localhost',

            'DB_CONNECTION' => 'mysql',
            'DB_HOST' => $state['database']['host'] ?? '127.0.0.1',
            'DB_PORT' => $state['database']['port'] ?? '3306',
            'DB_DATABASE' => $state['database']['database'] ?? 'jambo',
            'DB_USERNAME' => $state['database']['username'] ?? 'root',
            'DB_PASSWORD' => $state['database']['password'] ?? '',

            'SESSION_DRIVER' => 'database',
            'SESSION_LIFETIME' => '120',
            'CACHE_DRIVER' => 'file',
            'QUEUE_CONNECTION' => 'sync',
        ];

        $this->writeEnv($env);

        // Force Laravel to re-read config with the new env values.
        Artisan::call('config:clear');
    }

    private function stepKeyGenerate(): void
    {
        Artisan::call('key:generate', ['--force' => true]);
    }

    private function stepMigrate(): void
    {
        set_time_limit(300);
        Artisan::call('migrate', ['--force' => true]);
    }

    private function stepSeed(): void
    {
        if (!class_exists(\Database\Seeders\AuthTableSeeder::class)) {
            return;
        }
        Artisan::call('db:seed', [
            '--class' => \Database\Seeders\AuthTableSeeder::class,
            '--force' => true,
        ]);
    }

    private function stepCreateAdmin(array $state): void
    {
        $admin = $state['admin'] ?? [];
        if (empty($admin['email']) || empty($admin['password'])) {
            throw new \RuntimeException('Admin credentials missing from install state.');
        }

        $userClass = \App\Models\User::class;

        $user = $userClass::firstWhere('email', $admin['email'])
            ?? new $userClass();

        $user->email = $admin['email'];
        $user->first_name = $admin['first_name'] ?? 'Admin';
        $user->last_name = $admin['last_name'] ?? 'User';
        $user->username = $admin['username'] ?? null;
        $user->password = Hash::make($admin['password']);
        $user->email_verified_at = now();
        $user->save();

        // Assign the admin role if spatie/laravel-permission is wired up.
        if (method_exists($user, 'assignRole') && \Spatie\Permission\Models\Role::where('name', 'admin')->exists()) {
            $user->assignRole('admin');
        }
    }

    private function stepStorageLink(): void
    {
        try {
            Artisan::call('storage:link');
        } catch (Throwable $e) {
            // A pre-existing link is fine. Any other error is real.
            if (!str_contains($e->getMessage(), 'already exists')) {
                throw $e;
            }
        }
    }

    private function stepClearCaches(): void
    {
        Artisan::call('config:clear');
        Artisan::call('route:clear');
        Artisan::call('view:clear');
    }

    private function stepWriteFlag(): void
    {
        $flag = [
            'installed_at' => now()->toIso8601String(),
            'version' => $this->currentVersion(),
            'installer_version' => '1',
        ];

        File::put($this->flagFilePath(), json_encode($flag, JSON_PRETTY_PRINT));

        // The state file contains the admin password in plain text — purge it.
        $this->clearState();
    }

    /* -------------------------------------------------------------------- */
    /* Helpers                                                              */
    /* -------------------------------------------------------------------- */

    private function writeEnv(array $values): void
    {
        $path = base_path('.env');
        $content = File::exists($path)
            ? File::get($path)
            : File::get(base_path('.env.example'));

        foreach ($values as $key => $value) {
            $escaped = addcslashes((string) $value, '"\\$');
            $line = "$key=\"$escaped\"";

            if (preg_match("/^$key=.*$/m", $content)) {
                $content = preg_replace("/^$key=.*$/m", $line, $content);
            } else {
                $content = rtrim($content) . "\n" . $line . "\n";
            }
        }

        File::put($path, $content);
    }

    public function currentVersion(): string
    {
        $versionFile = base_path('version.txt');
        if (File::exists($versionFile)) {
            return trim(File::get($versionFile));
        }
        return config('app.version', '1.0.0');
    }
}
