# Jambo — CyberPanel Deploy Runbook

End-to-end deploy guide for shipping Jambo to a VPS running **CyberPanel**
(OpenLiteSpeed-based, common with Hostinger / Contabo / DigitalOcean).
This is the runbook to follow if you're managing the box through
CyberPanel's web UI rather than raw SSH + nginx.

If your VPS is a clean Ubuntu install without a control panel, use the
generic [hostinger-vps.md](hostinger-vps.md) runbook instead — it
covers nginx + Supervisor + Let's Encrypt manually.

---

## 0. What you need before starting

| Thing | Where |
|---|---|
| CyberPanel installed and reachable at `https://your-vps-ip:8090` | Hostinger sets it up at provisioning, or `sh <(curl https://cyberpanel.net/install.sh)` |
| Domain pointed at VPS IP | A record at registrar → VPS IP |
| Root or sudo SSH access | Hostinger provides credentials |
| Repo accessible to VPS | GitHub deploy key or HTTPS clone |

---

## 1. Create the site in CyberPanel

1. Log in to CyberPanel: `https://your-vps-ip:8090`
2. **Websites → Create Website**
   - Domain: `jambo.co` (or whatever)
   - Email: your admin email
   - Package: `Default` (or any)
   - PHP: **8.2** (8.1 minimum — composer.json requires `^8.1`)
   - SSL: tick **Issue SSL** (Let's Encrypt, free)
   - DKIM Support: tick (helps email deliverability)
3. Click **Create Website**

CyberPanel now provisions:
- A domain-specific Linux user (usually the username you set, or auto-generated)
- A document root at `/home/<domain>/public_html`
- An OpenLiteSpeed vHost
- An SSL cert via Let's Encrypt
- A FileManager + phpMyAdmin entry per site

### 1.1 Point the doc root at `/public`

Laravel requires the doc root to be the **`public/`** subfolder, not the
project root. CyberPanel defaults to `public_html/`.

1. **Websites → List Websites → click your site → vHost Conf**
2. Find the line:
   ```
   docRoot                   $VH_ROOT/public_html
   ```
3. Change to:
   ```
   docRoot                   $VH_ROOT/public_html/public
   ```
4. Save → Restart LSWS when prompted

If you skip this, every URL serves Laravel's project root listing
(or a directory index) instead of the app.

### 1.2 PHP extensions

CyberPanel ships PHP with most extensions. Verify these are enabled
under **Server → PHP → Edit PHP Configs → 8.2 → Extensions**:

```
mbstring  bcmath  gd  intl  xml  zip  curl  mysql  exif  fileinfo  imagick
```

Tick anything not enabled, click **Save Changes** and **Restart PHP**.

### 1.3 PHP limits for FileManager

Same screen → **Basic** tab. Raise these so the 4 GB FileManager cap
isn't silently cut down by PHP:

```
upload_max_filesize  4G
post_max_size        4G
memory_limit         512M
max_execution_time   600
max_input_time       600
```

---

## 2. Create the database

1. **Databases → Create Database**
2. Select your site
3. Database Name suffix: `jambo` (CyberPanel prefixes with the site username)
4. Username suffix: `jambo`
5. Password: strong random, **save it** — goes into `.env`

Note the **full** database name and username CyberPanel shows after
creation — they're prefixed with the site's username
(e.g. `jambousr_jambo`). That's what goes in `.env`.

---

## 3. Install Node.js + Composer

CyberPanel comes with Composer per site (`/usr/local/bin/composer`).
Confirm: `composer --version` after SSH-ing in.

Node.js typically isn't preinstalled. Install it once per VPS:

```bash
# As root
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo bash -
apt install -y nodejs
node -v   # → v20.x
npm -v
```

If your VPS is on AlmaLinux/CentOS (some Hostinger CyberPanel images
are), use:

```bash
curl -fsSL https://rpm.nodesource.com/setup_20.x | sudo bash -
dnf install -y nodejs
```

---

## 4. Deploy the code

SSH in as **the site's domain user**, not root. CyberPanel created this
user in step 1.

```bash
ssh root@your-vps-ip
su - <site-user>          # e.g. jambousr
cd public_html
```

Or, if you prefer to stay as root, do the work and `chown` afterwards
(CyberPanel won't be happy long-term — file ownership matters for LSWS).

### 4.1 Clone the repo

```bash
# Public HTTPS (no key required, but you'll re-enter creds on every pull)
git clone https://github.com/RealAkram20/jambo.git .

# OR with a deploy key (preferred — set the key as a deploy key in GitHub)
# ssh-keygen -t ed25519 -C "jambo-vps"
# cat ~/.ssh/id_ed25519.pub  # paste into GitHub → Repo settings → Deploy keys → "Allow write access" OFF
# git clone git@github.com:RealAkram20/jambo.git .
```

The trailing `.` clones into the current dir (which is
`/home/<site-user>/public_html`). The `public/` subfolder you pointed
the docRoot at in step 1.1 is now where Laravel lives.

### 4.2 Environment file

```bash
cp .env.production.example .env
nano .env
```

Fill every `<CHANGE_ME>` marker. Critical ones:

| Key | Value |
|---|---|
| `APP_URL` | `https://jambo.co` (your real domain, https) |
| `DB_DATABASE` | the **full** name from step 2 (e.g. `jambousr_jambo`) |
| `DB_USERNAME` | full username (e.g. `jambousr_jambo`) |
| `DB_PASSWORD` | the password you wrote down in step 2 |
| `MAIL_HOST`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS` | Real SMTP — Hostinger gives you mail credentials per domain, or use SendGrid/Mailgun |
| `JAMBO_CURRENCY` | Default `UGX`, change to your market |
| `PESAPAL_VERIFY_SSL` | `true` |
| `VAPID_PUBLIC_KEY` / `VAPID_PRIVATE_KEY` | Generate via `php artisan webpush:vapid` after install — leave blank for now |

### 4.3 Install + build

```bash
# PHP deps without dev tooling
composer install --no-dev --optimize-autoloader

# Frontend assets — both build dirs are produced by one script
npm ci
npm run build
```

If `npm run build` runs out of memory on a small VPS, build locally and
rsync `public/build` + `public/build-frontend` up instead:

```bash
# On your dev machine, after npm run build:
rsync -avz public/build/ root@vps:/home/<site-user>/public_html/public/build/
rsync -avz public/build-frontend/ root@vps:/home/<site-user>/public_html/public/build-frontend/
```

### 4.4 Laravel one-time setup

```bash
php artisan key:generate --force        # writes APP_KEY into .env
php artisan storage:link                 # public/storage → ../storage/app/public

# Schema. Build it from migrations — DO NOT import a dump from XAMPP,
# that ships with IS_DUMMY_DATA / IS_FAKE_DATA seeded users + content.
php artisan migrate --force

# Seed the bare minimum: roles (admin, user, super-admin), system Pages,
# subscription tiers, notification settings. Idempotent — safe to re-run.
php artisan db:seed --force

# Promote yourself. The email must match the admin user that exists
# in the seeded data (or one you've created). After this you're
# untouchable from the admin UI.
php artisan users:make-super-admin you@yourdomain.com
```

### 4.5 File permissions

CyberPanel runs LSWS as the site user. Laravel writes to `storage/`
and `bootstrap/cache/` — those need to be group-writable:

```bash
chown -R <site-user>:<site-user> /home/<site-user>/public_html
chmod -R 755 /home/<site-user>/public_html
chmod -R 775 storage bootstrap/cache
find storage bootstrap/cache -type d -exec chmod g+s {} \;   # setgid
```

If you ran any of step 4.3 / 4.4 as root, run this `chown` afterwards
or LSWS will refuse to write logs and the app will silently fail to
log errors.

### 4.6 Prime production caches

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

If `view:cache` errors with "Unable to locate a class or view for
component", you have a blade referencing a deleted component. Fix or
delete the offending view, then re-run.

---

## 5. CyberPanel UI tasks

These can't be done over SSH — flip them in the panel.

### 5.1 Confirm SSL is active

**Websites → List Websites → your site → SSL** should show:
- ✅ "SSL successfully Issued"
- Auto-renewal: enabled

If it failed (often because DNS hadn't propagated), click **Issue SSL**
again.

### 5.2 Cron entry for the Laravel scheduler

Without this, the hourly subscription-expiry job from the
Subscriptions module never fires.

**Websites → List Websites → your site → Manage → Cron Jobs**
Or: **Server → Add Cron Jobs**

Add:

```
* * * * * cd /home/<site-user>/public_html && /usr/bin/php artisan schedule:run >> /dev/null 2>&1
```

Run as: the site user (NOT root).

### 5.3 (Optional) Queue worker via Supervisor

Jambo's `.env.production.example` defaults to `QUEUE_CONNECTION=database`
so things like the contact-form email don't block the request. That
needs a worker process. CyberPanel doesn't ship Supervisor — install
it once:

```bash
# As root
apt install -y supervisor       # Ubuntu/Debian
# OR
dnf install -y supervisor       # AlmaLinux

cat > /etc/supervisor/conf.d/jambo-worker.conf <<'EOF'
[program:jambo-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /home/<site-user>/public_html/artisan queue:work --sleep=3 --tries=3 --timeout=60 --max-time=3600
autostart=true
autorestart=true
user=<site-user>
numprocs=2
redirect_stderr=true
stdout_logfile=/var/log/jambo-worker.log
stopwaitsecs=3600
EOF

supervisorctl reread
supervisorctl update
supervisorctl start jambo-worker:*
```

**If you skip this:** flip `.env` → `QUEUE_CONNECTION=sync`. Mail and
async work runs inline (slower, blocks page loads, but works without
Supervisor). Fine for low traffic; revisit when scale demands it.

---

## 6. Subsequent deploys (every `git push`)

Tighter loop once everything's wired. ~2 min downtime.

```bash
ssh root@your-vps-ip
su - <site-user>
cd public_html

# 1. Backup the DB before any migration. Never skip — see runbook
#    section 2.1 in the generic doc for why a silent mysqldump
#    failure here is how data gets lost permanently.
BACKUP=/home/<site-user>/backups/jambo-$(date +%Y%m%d-%H%M%S).sql.gz
mkdir -p $(dirname $BACKUP)
mysqldump -u <db-user> -p<db-password> <db-name> | gzip > $BACKUP
ls -lh $BACKUP

# 2. Maintenance mode (tells visitors the site is updating)
php artisan down --render="errors::503" --retry=60

# 3. Pull + install
git pull origin main
composer install --no-dev --optimize-autoloader
npm ci && npm run build

# 4. Migrate. If this errors, STOP — do not bring the site back up.
#    Restore from the backup in step 1, git reset, redeploy fixed code.
php artisan migrate --force

# 5. Rebuild caches
php artisan config:clear && php artisan route:clear \
    && php artisan view:clear && php artisan event:clear
php artisan config:cache && php artisan route:cache \
    && php artisan view:cache && php artisan event:cache

# 6. Restart workers (if Supervisor is configured)
supervisorctl restart jambo-worker:*

# 7. Out of maintenance mode
php artisan up
```

**Better:** use the in-app updater at `/admin/updates` instead of this
loop. The `SystemUpdate` module backs up the DB and files, applies
the release, retains the last 3 backups for rollback, and leaves no
SSH steps. See [system-update.md](../modules/system-update.md).

---

## 7. CyberPanel-specific gotchas

### File ownership errors

LSWS runs as the site user. If you ran `composer install` as root, the
`vendor/` dir is root-owned and LSWS can't read it → 500 errors with
"Permission denied" in `/usr/local/lsws/logs/error.log`.

Fix:
```bash
chown -R <site-user>:<site-user> /home/<site-user>/public_html
```

### `.htaccess` not taking effect

OpenLiteSpeed reads `.htaccess` natively, but only after a graceful
reload. After the FileManager `.htaccess` files install (or any
`.htaccess` change), restart LSWS:

```bash
systemctl restart lsws
```

Or via panel: **Server → Restart LiteSpeed**.

### "404 — Page Not Found" on every URL except the homepage

Doc root probably wasn't pointed at `/public` (step 1.1). Fix the
vHost conf and restart LSWS.

### Blank white page with no error

`storage/logs/laravel.log` is the first place to look. If THAT's empty,
check `/usr/local/lsws/logs/error.log`. The most common cause is bad
file ownership — see the first gotcha.

### `composer install` hangs

Limited memory. Add a swap file once per VPS:

```bash
fallocate -l 2G /swapfile
chmod 600 /swapfile
mkswap /swapfile
swapon /swapfile
echo '/swapfile none swap sw 0 0' >> /etc/fstab
```

### Email not sending

Two failure modes:

1. **SMTP rejects the credentials** — check `storage/logs/laravel.log`
   for the SMTP exception. Test creds with `php artisan tinker`:
   ```php
   Mail::raw('test', fn ($m) => $m->to('you@gmail.com')->subject('SMTP test'));
   ```

2. **Mail sends but lands in spam** — set up SPF, DKIM, DMARC on the
   sending domain. CyberPanel's "Email" section helps with DKIM if
   you're using its built-in mail server. For SendGrid/Mailgun,
   their dashboards walk you through DNS records.

---

## 8. First-deploy checklist

Tick these in order. Each is verifiable in 10 seconds.

- [ ] Domain DNS points at the VPS IP (`dig jambo.co +short`)
- [ ] CyberPanel site created, doc root → `public_html/public`
- [ ] SSL active (`curl -I https://jambo.co` → `HTTP/2 200`)
- [ ] DB created, `.env` filled with the prefixed names
- [ ] `composer install --no-dev` finished without error
- [ ] `npm run build` produced `public/build/` and `public/build-frontend/`
- [ ] `php artisan migrate --force` ran clean
- [ ] `php artisan db:seed --force` ran clean (creates the seeded admin)
- [ ] `php artisan users:make-super-admin you@example.com` succeeded
- [ ] Cache commands ran without error
- [ ] `chown -R <site-user>:<site-user>` after any root-run command
- [ ] Cron job entered for `schedule:run`
- [ ] Queue: either Supervisor up OR `.env` flipped to `QUEUE_CONNECTION=sync`
- [ ] `https://jambo.co/` returns 200
- [ ] Login works, `/app` (admin dashboard) loads
- [ ] `/admin/file-manager` works (gear gate verified)
- [ ] `/admin/settings` shows the maintenance card and saves
- [ ] `/admin/updates` shows the current version (likely 0.0.0)
- [ ] Direct hit on `https://jambo.co/storage/media/index.php` returns
      403 (proves the FileManager `.htaccess` is being read)

When every box is ticked, you're live.

---

## 9. Hand-off checklist for whoever maintains the VPS next

If someone else takes over operations:

| Document | Lives at |
|---|---|
| Local dev setup | [jambo-setup.md](../../jambo-setup.md) |
| This runbook | `docs/deploy/cyberpanel.md` |
| Generic VPS runbook | `docs/deploy/hostinger-vps.md` |
| In-app updater design | `docs/modules/system-update.md` |
| FileManager hardening | commit `5665d9b` (FileManager bypass lock) |
| Super-admin contract | `tests/Feature/Admin/SuperAdminGuardTest.php` |

CyberPanel credentials, Github deploy key, SMTP creds, PesaPal keys —
NOT in git, NOT in this doc. Hand them off through whatever password
manager you use.
