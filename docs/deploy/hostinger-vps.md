# Jambo — Hostinger VPS Deploy Runbook

End-to-end deploy guide for shipping Jambo to a Hostinger KVM VPS.
Written as a runbook: read the whole thing first, then follow it
top-to-bottom. Every command assumes you're SSH'd in as a sudo-capable
user on Ubuntu 22.04 LTS (Hostinger's default for KVM plans).

If your VPS is a different OS (AlmaLinux, Debian), the package names
change but the shape of the work is the same.

---

## 0. Before you start — facts to collect

Fill these in *before* running anything. The deploy plan below
assumes you have them nailed down.

| Thing | Value | Where to find it |
|---|---|---|
| Domain name | e.g. `jambo.co` | Your registrar + Hostinger DNS |
| Server IP | e.g. `123.45.67.89` | Hostinger hPanel → VPS overview |
| OS | Ubuntu 22.04 LTS assumed | `lsb_release -a` after SSH |
| PHP version target | **8.2** recommended | `composer.json` requires `^8.1` |
| Web server | **nginx** recommended | This runbook uses nginx |
| DB | MariaDB 10.6 or MySQL 8.0 | Hostinger ships MariaDB by default |
| Deploy path | `/var/www/jambo` | Convention |
| Deploy user | `deploy` (non-root) | Create below |

If any of these are unknown, stop and figure them out before
touching the server.

---

## 1. One-time server bootstrap

Do **exactly once** per VPS. Re-running is safe but pointless.

### 1.1 System packages

```bash
sudo apt update && sudo apt upgrade -y

# PHP 8.2 + every extension composer.json + Jambo's modules need.
# mbstring, bcmath, gd, intl, xml, zip are hard Laravel requirements;
# curl, mysql for Laravel; redis only if you switch QUEUE/CACHE to
# redis later; exif + fileinfo for spatie/medialibrary + FileManager;
# imagick for the Files Gallery config that prefers ImageMagick over GD.
sudo add-apt-repository ppa:ondrej/php -y
sudo apt install -y \
    php8.2-fpm php8.2-cli php8.2-common \
    php8.2-mbstring php8.2-bcmath php8.2-gd php8.2-intl \
    php8.2-xml php8.2-zip php8.2-curl php8.2-mysql \
    php8.2-exif php8.2-fileinfo php8.2-imagick \
    php8.2-redis

# Web server + DB + Node + Composer + FFmpeg + Git + Supervisor
sudo apt install -y nginx mariadb-server mariadb-client \
    redis-server supervisor git ffmpeg unzip curl

# Node 20 (Vite build tool needs it)
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo bash -
sudo apt install -y nodejs

# Composer 2
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
sudo chmod +x /usr/local/bin/composer
```

Verify:

```bash
php -v                # → PHP 8.2.x
composer --version    # → Composer 2.x
node -v               # → v20.x
nginx -v              # → nginx/1.24+
mariadb --version     # → 10.6+ or MySQL 8.0+
ffmpeg -version       # needed by Modules/Streaming transcode proxy
```

### 1.2 PHP-FPM config for large uploads

Jambo's FileManager config caps uploads at 4 GB. PHP's own limits
start at 2M / 8M, which silently caps the admin's upload UI at
whichever is lower. Raise them:

```bash
sudo vi /etc/php/8.2/fpm/php.ini
```

Find and set:

```ini
upload_max_filesize = 4G
post_max_size       = 4G
memory_limit        = 512M
max_execution_time  = 600
max_input_time      = 600
```

Same file for CLI (`/etc/php/8.2/cli/php.ini`) so `php artisan` jobs
that move files don't hit the lower CLI default.

Reload:

```bash
sudo systemctl reload php8.2-fpm
```

### 1.3 Firewall

```bash
sudo ufw allow 22/tcp          # SSH
sudo ufw allow 80/tcp          # HTTP (Let's Encrypt challenge)
sudo ufw allow 443/tcp         # HTTPS
sudo ufw --force enable
sudo ufw status
```

### 1.4 Create the deploy user + dirs

Running Laravel as root is a security risk. Create a dedicated
`deploy` user that owns the app directory and runs the queue worker.

```bash
sudo adduser --disabled-password --gecos "" deploy
sudo usermod -aG www-data deploy

# nginx runs as www-data; deploy writes files, nginx reads them.
sudo mkdir -p /var/www/jambo
sudo chown deploy:www-data /var/www/jambo
sudo chmod 2775 /var/www/jambo   # setgid so new files inherit group
```

Give your SSH key to `deploy` so you can `ssh deploy@host` for deploys
(keep the root user for emergencies only):

```bash
sudo mkdir /home/deploy/.ssh
sudo cp ~/.ssh/authorized_keys /home/deploy/.ssh/
sudo chown -R deploy:deploy /home/deploy/.ssh
sudo chmod 700 /home/deploy/.ssh
sudo chmod 600 /home/deploy/.ssh/authorized_keys
```

### 1.5 MariaDB setup

```bash
sudo mariadb-secure-installation
```

Answer: yes to everything, pick a strong root password.

Create the app DB + user:

```bash
sudo mariadb -u root -p
```

```sql
CREATE DATABASE jambo CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'jambo'@'localhost' IDENTIFIED BY 'STRONG_PASSWORD_HERE';
GRANT ALL PRIVILEGES ON jambo.* TO 'jambo'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

Write that password down — it goes into `.env` as `DB_PASSWORD`.

### 1.6 Clone the repo

```bash
sudo -u deploy bash
cd /var/www/jambo
git clone git@github.com:RealAkram20/Jambo.git .
```

If git needs a deploy key, add `~/.ssh/id_ed25519.pub` to the
GitHub repo's Deploy keys (read-only is enough).

### 1.7 First-time Laravel install

Still as `deploy`:

```bash
cd /var/www/jambo

# Production install — skips dev deps (phpunit, breeze, ignition, etc.)
# so they never reach the VPS and can't be exploited. Also runs
# filemanager:install which drops the security .htaccess files into
# storage/app/public/{media,gallery}/.
composer install --no-dev --optimize-autoloader

# Build frontend + dashboard Vite assets (both are built by one script)
npm ci
npm run build

# .env setup. Template ships all the prod-safe defaults.
cp .env.production.example .env
vi .env    # fill in every <CHANGE_ME> marker — see checklist below
```

#### .env values you MUST set

| Key | Notes |
|---|---|
| `APP_URL` | `https://jambo.co` (real domain with https) |
| `APP_KEY` | Leave blank here — generated next |
| `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | From section 1.5 |
| `MAIL_HOST`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS` | SMTP (Hostinger gives you one, or use SendGrid/Mailgun) |
| `JAMBO_CURRENCY` | Default `UGX` — change if serving a different market |
| `PESAPAL_VERIFY_SSL` | `true` on VPS (only `false` on Windows XAMPP dev) |
| `VAPID_SUBJECT`, `VAPID_PUBLIC_KEY`, `VAPID_PRIVATE_KEY` | If using web push — run `php artisan webpush:vapid` once to generate |
| `GOOGLE_CLIENT_ID`/`SECRET` | Only if enabling Google social login |

Then:

```bash
php artisan key:generate --force     # fills APP_KEY in .env
php artisan storage:link             # public/storage → storage/app/public
```

### 1.8 Database migration + first seed

```bash
# Dry-run first so you can eyeball the SQL
php artisan migrate --pretend

# If everything looks right, apply for real
php artisan migrate --force

# One-time seed: creates admin user + demo subscription tiers + the 5
# system Pages rows (about-us, contact-us, faqs, terms, privacy).
# DO NOT re-run on subsequent deploys unless you want demo data
# regenerated — the PagesDatabaseSeeder uses firstOrCreate so it's
# idempotent for the Pages module, but ContentDatabaseSeeder may
# duplicate demo content.
php artisan db:seed --force
```

### 1.9 Directory permissions

Laravel writes to `storage/` and `bootstrap/cache/`. The web server
(www-data) must be able to write there too.

```bash
sudo chown -R deploy:www-data /var/www/jambo
sudo chmod -R 775 /var/www/jambo/storage
sudo chmod -R 775 /var/www/jambo/bootstrap/cache
sudo find /var/www/jambo -type d -exec chmod g+s {} \;   # setgid
```

### 1.10 Prime the production caches

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

If any of these error, **stop** — the deploy is broken. `view:cache`
is the one most likely to fail on a new blade introduced since the
last deploy. Fix the blade, clear (`php artisan *:clear`), re-run.

### 1.11 nginx vhost

```bash
sudo vi /etc/nginx/sites-available/jambo
```

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name jambo.co www.jambo.co;

    # Redirect all HTTP → HTTPS. Certbot adds the 443 block below.
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name jambo.co www.jambo.co;

    root /var/www/jambo/public;
    index index.php;

    # Large upload bodies (File Manager allows up to 4 GB per the
    # Files Gallery config). nginx needs its own limit.
    client_max_body_size 4G;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header Referrer-Policy "strict-origin-when-cross-origin";

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_read_timeout 600;
        # Respect the app's AllowOverride-compatible .htaccess rules
        # for the file manager cookie gate. nginx doesn't read .htaccess;
        # the equivalent check is below.
    }

    # Mirror the admin-gated Files Gallery rule from the Apache
    # .htaccess. Without the JAMBO_FM_SESSION cookie issued by
    # /admin/file-manager, block direct hits on /storage/media/*.
    location ~ ^/storage/media/ {
        if ($http_cookie !~ "JAMBO_FM_SESSION=[^;]+") {
            return 403;
        }
        try_files $uri /index.php?$query_string;
    }

    # Mirror the "no PHP execution in public gallery" rule.
    location ~ ^/storage/gallery/.*\.(php|phtml|phar|pl|py|sh|cgi)$ {
        deny all;
    }

    location ~ /\. {
        deny all;
    }

    location ~ /\.env {
        deny all;
    }
}
```

Enable + test + reload:

```bash
sudo ln -s /etc/nginx/sites-available/jambo /etc/nginx/sites-enabled/
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t
sudo systemctl reload nginx
```

### 1.12 SSL via Let's Encrypt (certbot)

Hostinger's default plans don't ship free auto-SSL on KVM VPS — use
certbot. (If you're on a shared/managed plan with hPanel SSL, use that
instead and skip this step.)

```bash
sudo snap install --classic certbot
sudo ln -s /snap/bin/certbot /usr/bin/certbot
sudo certbot --nginx -d jambo.co -d www.jambo.co
```

Certbot will:
1. Prove domain ownership via HTTP-01 challenge
2. Install the cert
3. Rewrite your nginx config to add the correct `ssl_certificate` and
   `ssl_certificate_key` lines
4. Set up auto-renewal (runs every 12h, only renews if within 30 days
   of expiry)

Verify: `curl -I https://jambo.co` → `HTTP/2 200`.

### 1.13 Queue worker (Supervisor)

Jambo's production .env sets `QUEUE_CONNECTION=database`, so every
mail send + async job sits in the `jobs` table until a worker picks
it up. Without a worker running, the contact form submit **silently
blocks** until you notice emails never arrived.

```bash
sudo vi /etc/supervisor/conf.d/jambo-worker.conf
```

```ini
[program:jambo-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/jambo/artisan queue:work --sleep=3 --tries=3 --timeout=60 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=deploy
numprocs=2
redirect_stderr=true
stdout_logfile=/var/log/jambo-worker.log
stopwaitsecs=3600
```

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start jambo-worker:*
sudo supervisorctl status
```

Should print `jambo-worker:jambo-worker_00 RUNNING` twice.

### 1.14 Scheduler cron

Laravel's scheduler runs anything registered in
`app/Console/Kernel.php`. Without the cron entry, those jobs never
fire. Edit the `deploy` user's crontab:

```bash
sudo -u deploy crontab -e
```

Append:

```
* * * * * cd /var/www/jambo && php artisan schedule:run >> /dev/null 2>&1
```

Cron runs this every minute; Laravel decides internally which jobs
are due.

---

## 2. Standard deploy — every `git push` to main

This is the tight loop after the one-time setup. ~2 min of downtime;
most of it is cache rebuild.

### 2.1 Backup the DB (do NOT skip)

```bash
cd /var/www/jambo
BACKUP=/var/backups/jambo/jambo-$(date +%Y%m%d-%H%M%S).sql.gz
sudo mkdir -p $(dirname $BACKUP)
mysqldump -u jambo -p jambo | gzip > $BACKUP
ls -lh $BACKUP
```

If the dump is suspiciously small (< 100 KB on a populated DB),
**stop** and investigate. A silent mysqldump failure before a
migration is how you lose data forever.

### 2.2 Maintenance mode

```bash
php artisan down --render="errors::503" --retry=60
```

Users get a 503 while the deploy runs.

### 2.3 Pull + install

```bash
git pull origin main
composer install --no-dev --optimize-autoloader
npm ci
npm run build
```

`composer install` runs `filemanager:install` in post-autoload-dump,
which re-installs the `.htaccess` security drops (force-overwritten
on every run, so policy can't drift).

### 2.4 Migrate

```bash
php artisan migrate --force
```

If this fails, **stop** — do not rebuild caches, do not bring the
site back up. Jump to section 3 (rollback).

### 2.5 Rebuild caches

```bash
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan event:clear

php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

### 2.6 Restart the queue worker

Queue workers cache code in memory — without a restart, they keep
running the pre-deploy version.

```bash
sudo supervisorctl restart jambo-worker:*
```

### 2.7 Exit maintenance mode

```bash
php artisan up
```

### 2.8 Verify — do every one of these

Run through [section 4](#4-post-deploy-smoke-test). If any check
fails, jump to [section 3](#3-rollback).

---

## 3. Rollback

### 3.1 "The migration blew up mid-deploy"

The DB is in a partial state. The safe path is full restore.

```bash
# Still in maintenance mode? Good. If not:
php artisan down

# Roll back the code
git reset --hard ORIG_HEAD   # or a specific known-good commit

# Restore the DB from the backup you took in 2.1
gunzip -c /var/backups/jambo/jambo-YYYYMMDD-HHMMSS.sql.gz | mysql -u jambo -p jambo

# Re-cache against the old code
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
sudo supervisorctl restart jambo-worker:*
php artisan up
```

Then figure out why the migration broke before trying again.

### 3.2 "Site 500s after deploy but DB is fine"

Migrations worked, something else broke (blade, route, config).

```bash
# Temporarily flip APP_DEBUG=true to see the actual error on-screen
sed -i 's/^APP_DEBUG=false/APP_DEBUG=true/' .env
php artisan config:clear
php artisan config:cache
```

Visit the site, read the stack trace. **Flip it back immediately**
once you know the cause:

```bash
sed -i 's/^APP_DEBUG=true/APP_DEBUG=false/' .env
php artisan config:cache
```

If the fix isn't obvious in 5 minutes, `git reset --hard ORIG_HEAD`,
re-cache, bring up. Fix on a branch, redeploy later.

### 3.3 "Queue worker is stuck"

```bash
sudo supervisorctl stop jambo-worker:*
# Clear any failed jobs
php artisan queue:flush
php artisan queue:failed    # review + decide to retry or drop
sudo supervisorctl start jambo-worker:*
```

---

## 4. Post-deploy smoke test

Do every one of these after bringing the site back up. If any fail,
rollback.

### 4.1 HTTP response codes

```bash
# Homepage
curl -sI https://jambo.co/ | head -1        # → HTTP/2 200

# Frontend golden paths
curl -sI https://jambo.co/about-us      | head -1   # 200
curl -sI https://jambo.co/contact-us    | head -1   # 200
curl -sI https://jambo.co/faq_page      | head -1   # 200
curl -sI https://jambo.co/pricing       | head -1   # 200
curl -sI https://jambo.co/login         | head -1   # 200

# File Manager direct-access bypass — this MUST be 403 on a fresh
# curl (no JAMBO_FM_SESSION cookie). If it returns 200, the
# .htaccess (Apache) or nginx location block (nginx) didn't install.
curl -sI https://jambo.co/storage/media/index.php | head -1   # → 403
```

### 4.2 End-to-end flows (manual, in a browser)

- [ ] Login as admin → `/app` (dashboard) loads with live charts
- [ ] Admin profile → enable 2FA → log out → log back in with TOTP
- [ ] `/admin/pages` — edit Contact page, save, confirm public `/contact-us` reflects the change
- [ ] `/admin/file-manager` — iframe loads, upload an image, confirm it appears at `/storage/gallery/…`
- [ ] Log in as a non-admin user → try right-click on any frontend page → context menu blocked (deterrent, not defense)
- [ ] Watch a movie → inspect `<video>` → `src` is `/watch/src/movie/…` (Laravel), not the raw Contabo URL
- [ ] Contact form submit → check the SMTP inbox, then `php artisan queue:failed` to confirm no failures
- [ ] Payment via PesaPal (sandbox) → order marked completed in `/app/payments/orders`

### 4.3 Tail the logs for the first 10 minutes

```bash
# Laravel app log
tail -f /var/www/jambo/storage/logs/laravel.log

# nginx error log
sudo tail -f /var/log/nginx/error.log

# PHP-FPM slow log (helps spot 500s rooted in fatal errors)
sudo tail -f /var/log/php8.2-fpm.log

# Queue worker log
sudo tail -f /var/log/jambo-worker.log
```

Nothing red? Deploy is good. Close the terminal.

---

## 5. Ongoing maintenance

### 5.1 Rotate logs

Laravel's default `storage/logs/laravel.log` grows forever. Either
switch `LOG_CHANNEL=daily` in `.env` (auto-rotates, 14-day retention)
or add a logrotate entry.

### 5.2 Re-run composer security audit monthly

```bash
cd /var/www/jambo
composer audit --no-dev
```

`--no-dev` because only prod deps matter for VPS exposure. If it
reports HIGH or CRITICAL, cut a branch that runs `composer update
<pkg> --with-all-dependencies`, test locally, deploy.

### 5.3 Renew certs

Certbot auto-renews. Verify it's working monthly:

```bash
sudo certbot renew --dry-run
```

### 5.4 Backup retention

Decide a policy — e.g. keep daily for 7 days, weekly for 4 weeks,
monthly for 12 months. A one-liner in cron:

```bash
find /var/backups/jambo -name "*.sql.gz" -mtime +30 -delete
```

---

## 6. Appendix — what ships on deploy vs. stays local

The following are **not in git** but the app depends on them.
Each is re-created by the deploy steps above.

| Artifact | Created by |
|---|---|
| `.env` | `cp .env.production.example .env` + manual edits |
| `storage/app/public/media/index.php` (Files Gallery) | `composer install` → `filemanager:install` |
| `storage/app/public/media/.htaccess` (cookie gate) | `composer install` → `filemanager:install` |
| `storage/app/public/gallery/.htaccess` (PHP-exec block) | `composer install` → `filemanager:install` |
| `public/build/*` (dashboard Vite assets) | `npm run build` |
| `public/build-frontend/*` (frontend Vite assets) | `npm run build` |
| `public/storage/` (symlink) | `php artisan storage:link` |
| Laravel caches (config/route/view/event) | `php artisan *:cache` |

If a deploy skips any of these, expect breakage.
