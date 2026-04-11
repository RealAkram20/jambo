# Streamit Laravel Template — Documentation

Source: https://templates.iqonic.design/streamit-dist/documentation/laravel/index.html

Streamit is a responsive Bootstrap 5 admin template for building Netflix-like
applications. The Laravel edition ships both a Frontend module and a Dashboard.

---

## 1. Requirements

**Laravel:** 10 (minimum PHP 8.1).

**Required PHP extensions:**

- OpenSSL
- PDO
- Mbstring
- Tokenizer
- XML
- Ctype
- JSON
- GD (or Imagick)
- Fileinfo
- Zip Archive
- URL rewrite module (Apache `mod_rewrite` or Nginx equivalent)

**Folder permissions** (recursive where applicable):

- `/bootstrap` — `775`
- `/storage` — `775`

---

## 2. Getting Started

1. Pick the template folder you need — Frontend Page or Dashboard.
2. Upload the files to your server via FTP (or cPanel File Manager).
3. Preserve the original folder structure during upload.

On Linux servers, upload to `/var/www/html/`. On cPanel, upload to
`public_html/`.

---

## 3. Installation (Laravel + Gulp/Vite)

From the Laravel project root:

```bash
# 1. Install JS dependencies
npm install

# 2. Install PHP dependencies
composer install

# 3. Build assets (choose one)
npm run dev       # development build
npm run build     # production build
npm run watch     # watch mode

# 4. Create env file
cp .env.example .env

# 5. Generate the application key
php artisan key:generate

# 6. Link public storage
php artisan storage:link
```

### Configure `.env`

Edit the hidden `.env` file and set:

- App name and environment (`APP_NAME`, `APP_ENV`, `APP_URL`)
- Database credentials (`DB_*`)
- Mail server (`MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`,
  `MAIL_ENCRYPTION`)

`.env` changes must be made before deployment.

---

## 4. File Structure

```
app/                     Controllers, Models, Middleware, Providers
bootstrap/
config/
database/
lang/
Modules/
  Frontend/              Frontend module (routes, views, assets)
node_modules/
public/                  Compiled assets for dashboard + frontend
resources/
  views/
    auth/
    components/
    dashboards/          analytics, movies, shows, episodes, ...
    layouts/
  sass/
  css/
  js/
  client-images/
routes/
storage/
tests/
composer.json
package.json
vite.config.js
tailwind.config.js
postcss.config.js
```

Pre-built pages include: user management & privacy, content organization
(movies, shows, seasons, episodes), analytics dashboards, UI elements,
form components, and authentication flows.

---

## 5. Customization Topics

The upstream docs cover each of these on its own page:

- **Theme** — `pages/main/theme.html`
- **Sidebar Appearance** — `pages/main/sidebar-appearance.html`
- **Color** — `pages/main/color.html`
- **Favicon** — `pages/main/favicon.html`
- **Logo** — `pages/main/logo.html`
- **Loader / Loading Transitions** — `pages/main/loadingTransitions.html`
- **Changing Fonts** — `pages/main/changingFont.html`
- **Live Customizer** — `pages/main/liveCustomizer.html`
- **Header Style** — `pages/headerfooter/header-style.html`
- **Footer Style** — `pages/headerfooter/footer-style.html`
- **Megamenu** — `pages/headerfooter/megamenu.html`
- **Components / Forms** — `pages/components/component.html`

---

## 6. Bundled Plugins

- NoUi Slider
- ApexCharts
- Circle Progress
- Flatpickr
- DataTables
- SweetAlert
- Swiper Slider

---

## 7. Other Resources

- Source & Credits — `pages/main/sourceAndCredit.html`
- Upgrade Guide — `pages/main/upgrade-guide.html`
- Changelog — `../changelog.html`
- Support — https://iqonic.desky.support/
- Vendor site — https://iqonic.design

---

*This file is a local snapshot summarized from the official Streamit Laravel
documentation for offline reference. For full details (especially per-topic
customization and plugin examples) consult the upstream URL at the top.*
