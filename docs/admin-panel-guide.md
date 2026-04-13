# Jambo Admin Panel — Complete Page Inventory & Wiring Guide

> **Key insight:** The Streamit template already provides **84+ professionally
> designed admin pages** with full UI — tables, forms, offcanvas panels, modals,
> charts, and data tables. Our job is to **wire these existing pages to real
> database data**, not to build new pages from scratch.

---

## Architecture: Template Pages vs Module CRUD

The template ships demo pages via `DashboardController` (static views with
hardcoded placeholder data). Our modules provide real CRUD controllers.

**The correct approach:** Replace the template's static data with Eloquent
queries inside the existing blade templates — or route the sidebar links to
module controllers that render the template's blade layout.

| Template route | Template blade | Module route | Status |
|---|---|---|---|
| `dashboard.movie-list` | `DashboardPages/movies/MovieListPage` | `admin.movies.index` | Module has own views (should use template views) |
| `dashboard.movie-genres` | `DashboardPages/movies/MovieGenres` | — | Not wired yet |
| `dashboard.movie-tags` | `DashboardPages/movies/MovieTag` | — | Not wired yet |
| `dashboard.movie-playlist` | `DashboardPages/movies/MoviePlaylist` | — | Not wired yet |
| `dashboard.show-list` | `DashboardPages/tv-show/ShowListPage` | `admin.shows.index` | Module has own views |
| `dashboard.seasons` | `DashboardPages/tv-show/SeasonsPage` | `admin.seasons.index` | Module has own views |
| `dashboard.episodes` | `DashboardPages/tv-show/EpisodesPage` | `admin.episodes.index` | Module has own views |
| `dashboard.tvshow-genres` | `DashboardPages/tv-show/ShowGenres` | — | Not wired yet |
| `dashboard.tvshow-tags` | `DashboardPages/tv-show/ShowTag` | — | Not wired yet |
| `dashboard.tvshow-playlist` | `DashboardPages/tv-show/ShowPlaylist` | — | Not wired yet |
| `dashboard.videopage` | `DashboardPages/videos/VideoPage` | — | Not wired yet |
| `dashboard.video-category` | `DashboardPages/videos/VideoCategory` | — | Not wired yet |
| `dashboard.video-tags` | `DashboardPages/videos/VideoTag` | — | Not wired yet |
| `dashboard.video-playlist` | `DashboardPages/videos/VideoPlaylist` | — | Not wired yet |
| `dashboard.person` | `DashboardPages/persons/PersonPage` | `admin.persons.index` | Module has own views |
| `dashboard.person-categories` | `DashboardPages/persons/PersonCategoies` | — | Not wired yet |
| `dashboard.person-tags` | `DashboardPages/persons/PersonTag` | — | Not wired yet |
| `dashboard.rating` | `DashboardPages/rating/RatingPage` | — | Static template |
| `dashboard.comment` | `DashboardPages/CommentPage` | — | Static template |
| `dashboard.user-list` | `DashboardPages/user/ListPage` | — | Static template |
| `dashboard.review` | `DashboardPages/review/ReviewPage` | — | Static template |
| `dashboard.pricing` | `DashboardPages/spacial-pages/PricingPage` | — | Static template |

---

## Complete Page Inventory

### 1. Dashboards

| Page | Route name | Blade file | Description |
|------|-----------|------------|-------------|
| Dashboard | `dashboard` | `DashboardPages/IndexPage` | Stats, charts, top-rated carousel, data table |
| Dashboard 1 | `dashboard1` | `DashboardPages/IndexPage1` | Users, subscribers, movies, revenue charts |

### 2. Content Management

| Page | Route name | Blade file | Description |
|------|-----------|------------|-------------|
| Rating | `dashboard.rating` | `DashboardPages/rating/RatingPage` | Ratings table with scores |
| Comments | `dashboard.comment` | `DashboardPages/CommentPage` | Comment moderation table |
| Users | `dashboard.user-list` | `DashboardPages/user/ListPage` | User list with status, actions |
| Review | `dashboard.review` | `DashboardPages/review/ReviewPage` | Review approval table |
| Pricing | `dashboard.pricing` | `DashboardPages/spacial-pages/PricingPage` | Pricing plans cards |

### 3. Movies (4 pages)

| Page | Route name | Blade file | Description |
|------|-----------|------------|-------------|
| Movie List | `dashboard.movie-list` | `DashboardPages/movies/MovieListPage` | Data table + offcanvas add form |
| Movie Genres | `dashboard.movie-genres` | `DashboardPages/movies/MovieGenres` | Genre management |
| Movie Tags | `dashboard.movie-tags` | `DashboardPages/movies/MovieTag` | Tag management |
| Movie Playlists | `dashboard.movie-playlist` | `DashboardPages/movies/MoviePlaylist` | Playlist management |

### 4. TV Shows (6 pages)

| Page | Route name | Blade file | Description |
|------|-----------|------------|-------------|
| Show List | `dashboard.show-list` | `DashboardPages/tv-show/ShowListPage` | Shows data table |
| Seasons | `dashboard.seasons` | `DashboardPages/tv-show/SeasonsPage` | Season management |
| Episodes | `dashboard.episodes` | `DashboardPages/tv-show/EpisodesPage` | Episode management |
| Show Genres | `dashboard.tvshow-genres` | `DashboardPages/tv-show/ShowGenres` | Genre management |
| Show Tags | `dashboard.tvshow-tags` | `DashboardPages/tv-show/ShowTag` | Tag management |
| Show Playlists | `dashboard.tvshow-playlist` | `DashboardPages/tv-show/ShowPlaylist` | Playlist management |

### 5. Videos (4 pages)

| Page | Route name | Blade file | Description |
|------|-----------|------------|-------------|
| Videos | `dashboard.videopage` | `DashboardPages/videos/VideoPage` | Video list |
| Video Category | `dashboard.video-category` | `DashboardPages/videos/VideoCategory` | Category management |
| Video Tags | `dashboard.video-tags` | `DashboardPages/videos/VideoTag` | Tag management |
| Video Playlists | `dashboard.video-playlist` | `DashboardPages/videos/VideoPlaylist` | Playlist management |

### 6. Persons (3 pages)

| Page | Route name | Blade file | Description |
|------|-----------|------------|-------------|
| Person List | `dashboard.person` | `DashboardPages/persons/PersonPage` | Cast/crew list |
| Person Categories | `dashboard.person-categories` | `DashboardPages/persons/PersonCategoies` | Category management |
| Person Tags | `dashboard.person-tags` | `DashboardPages/persons/PersonTag` | Tag management |

### 7. Authentication Demo Pages (7 pages)

| Page | Route name | Blade file |
|------|-----------|------------|
| Login | `dashboard.login` | `DashboardPages/auth/default/SignIn` |
| Register | `dashboard.register` | `DashboardPages/auth/default/SignUp` |
| Reset Password | `dashboard.reset-password` | `DashboardPages/auth/default/ResetPassword` |
| Verify Email | `dashboard.verify-email` | `DashboardPages/auth/default/VarifyEmail` |
| Lock Screen | `dashboard.lock-screen` | `DashboardPages/auth/default/LockScreen` |
| Two Factor | `dashboard.TwoFactor` | `DashboardPages/auth/default/TwoFactor` |
| Account Deactivated | `dashboard.AccountDeactivated` | `DashboardPages/auth/default/AccountDeactivated` |

### 8. Error & Utility Pages (5 pages)

| Page | Route name | Blade file |
|------|-----------|------------|
| 404 Error | `dashboard.error-404` | `DashboardPages/errors/Error404Page` |
| 500 Error | `dashboard.error-500` | `DashboardPages/errors/Error500Page` |
| Maintenance | `dashboard.maintenance` | `DashboardPages/errors/MaintenancePage` |
| Coming Soon | `dashboard.coming-soon` | `DashboardPages/errors/ComingSoon` |
| Blank Page | `dashboard.blank-page` | `DashboardPages/BlankPage` |

### 9. UI Elements (21 pages)

All located in `DashboardPages/ui-elements/`:
Alerts, Avatars, Badge, Breadcrumb, Buttons, Button Groups, Cards, Carousel,
Colors, Grid, Images, List Group, Modal, Notifications, Offcanvas, Pagination,
Popovers, Tabs, Tooltips, Typography, Video

### 10. Widgets (3 pages)

| Page | Route name | Blade file |
|------|-----------|------------|
| Widget Basic | `dashboard.widget-basic` | `DashboardPages/widgets/WidgetBasic` |
| Widget Chart | `dashboard.widget-chart` | `DashboardPages/widgets/WidgetChart` |
| Widget Card | `dashboard.widget-card` | `DashboardPages/widgets/WidgetCard` |

### 11. Forms (3 pages)

Elements, Wizard, Validation — all in `DashboardPages/forms/`

### 12. Tables (4 pages)

Bootstrap Table, Data Table, Bordered Table, Fixed Table — all in `DashboardPages/tables/`

### 13. Icons (4 pages)

Font Awesome, Ph Regular, Ph Bold, Ph Fill — all in `DashboardPages/icons/`

### 14. Access Control

| Page | Route name | Blade file |
|------|-----------|------------|
| Roles & Permissions | `backend.permission-role` | `DashboardPages/admin/AdminPage` |

### 15. Settings & Profile

| Page | Route name | Blade file |
|------|-----------|------------|
| User Profile | `dashboard.profile` | `DashboardPages/user-profile` |
| Privacy Settings | `dashboard.privacy` | `DashboardPages/user-privacy-setting` |
| Privacy Policy | `dashboard.privacy-policy` | `DashboardPages/extra/PrivacyPolicy` |
| Terms | `dashboard.terms-of-use` | `DashboardPages/extra/TermsAndConditions` |

---

## Sidebar Navigation Structure (vertical-nav.blade.php)

The template sidebar is organised as follows — this is the **canonical order**:

```
Dashboard              (simple item)
Dashboard 1            (simple item)
Rating                 (simple item, @can view_rating)
Comments               (simple item, @can view_comments)
Users                  (simple item, @can view_users)
Movie            ▸     (collapsible)
  ├── Movie List
  ├── Genres
  ├── Tags
  └── Movie Playlists
TV Shows         ▸     (collapsible)
  ├── Show Lists
  ├── Episodes
  ├── Genres
  ├── Tags
  └── Episodes Playlist
Videos           ▸     (collapsible)
  ├── Videos
  ├── Video Category
  ├── Video Tags
  └── Video Playlist
Persons          ▸     (collapsible)
  ├── Person
  ├── Categories
  └── Tags
Review                 (simple item)
Pricing                (simple item)
Authentication   ▸     (collapsible, 7 sub-items)
Utilities        ▸     (collapsible, 4 sub-items)
Blank Page             (simple item)
UI Elements      ▸     (collapsible, 21 sub-items)
Widgets          ▸     (collapsible, 3 sub-items)
Form             ▸     (collapsible, 3 sub-items)
Table            ▸     (collapsible, 4 sub-items)
Icons            ▸     (collapsible, 3+ sub-items)
Access Control         (simple item)
─── Below are custom additions ───
Movies                 (simple item, duplicate — REMOVE)
Shows                  (simple item, duplicate — REMOVE)
Persons                (simple item, duplicate — REMOVE)
System Updates         (simple item)
Notifications          (simple item)
Payments               (simple item)
```

### What needs to happen

The custom module entries at the bottom (Movies, Shows, Persons) are
**duplicates** of the template's collapsible menus above. They should be
**removed** from the sidebar, and instead the template's existing Movie,
TV Shows, and Persons menu items should be wired to the module's real
CRUD controllers.

System Updates, Notifications, and Payments are genuinely new entries that
belong in the sidebar — but they should match the template's spacing and
style exactly (no `mb-4`).

---

## Shared Components

| Component | File | Used by |
|-----------|------|---------|
| Sidebar | `components/partials/sidebar.blade.php` | All admin pages |
| Vertical Nav | `components/partials/vertical-nav.blade.php` | Sidebar |
| Header | `components/partials/header.blade.php` | All admin pages |
| Footer | `components/partials/footer.blade.php` | All admin pages |
| Notifications Bell | `components/partials/notifications-bell.blade.php` | Header |
| Loader | `components/partials/loader.blade.php` | All pages |
| Customizer | `components/partials/customizer.blade.php` | All admin pages |
| Logo | `components/widget/logo.blade.php` | Sidebar, header |
| DataTable row | `components/datatable/DataTable.blade.php` | Table pages |
| Upload widget | `components/widget/UploadImageVideo.blade.php` | Create/edit forms |
| Cast modal | `DashboardPages/widgets/model/cast-modal-edit.blade.php` | Movie/show forms |
| Subtitle modal | `DashboardPages/widgets/model/subtitle-modal-edit.blade.php` | Movie/show forms |
| Video modal | `DashboardPages/widgets/model/video-modal-edit.blade.php` | Movie/show forms |

---

## The Wiring Strategy

For each template page that currently shows static placeholder data:

1. **Read the template blade** to understand its exact HTML structure
2. **Pass real data** from the module controller to the template blade
3. **Replace hardcoded values** with `{{ $model->field }}` Blade syntax
4. **Keep the exact layout, classes, and design** — only change data bindings
5. **Use the template's existing form patterns** (offcanvas, modals) for add/edit
6. **Use the template's existing table patterns** (DataTables) for listings

**Never build a new page layout when the template already has one.**

---

## Controllers Reference

### Template Controller (static demo pages)
- `app/Http/Controllers/DashboardController.php` — 62 methods, all return static views

### Module Controllers (real CRUD)
- `Modules/Content/app/Http/Controllers/Admin/MovieController.php`
- `Modules/Content/app/Http/Controllers/Admin/ShowController.php`
- `Modules/Content/app/Http/Controllers/Admin/SeasonController.php`
- `Modules/Content/app/Http/Controllers/Admin/EpisodeController.php`
- `Modules/Content/app/Http/Controllers/Admin/PersonController.php`

### Backend Controllers (system features)
- `app/Http/Controllers/Backend/SettingController.php` — App settings
- `app/Http/Controllers/Backend/UserController.php` — User management
- `app/Http/Controllers/Backend/BackupController.php` — DB backups
- `app/Http/Controllers/Backend/BackendController.php` — Dashboard stats
- `app/Http/Controllers/Backend/RolesController.php` — Role management
- `app/Http/Controllers/PermissionController.php` — Permission CRUD
- `app/Http/Controllers/RolePermission.php` — Permission assignment

### Module Controllers (custom features)
- `Modules/Payments/app/Http/Controllers/PaymentController.php`
- `Modules/Payments/app/Http/Controllers/Admin/PaymentSettingsController.php`
- `Modules/Notifications/app/Http/Controllers/NotificationController.php`
- `Modules/SystemUpdate/app/Http/Controllers/UpdateController.php`
- `Modules/Installer/app/Http/Controllers/InstallController.php`

---

## Helper Functions

| Helper | File | Purpose |
|--------|------|---------|
| `activeRoute($route, $class)` | `app/helpers.php` | Returns 'active' class if URL matches |
| `setting($key, $default)` | `app/helpers.php` | Read/write DB settings |
| `app_name()` | `app/helpers.php` | Get app name from settings |
| `user_avatar()` | `app/helpers.php` | Get auth user avatar URL |
| `language_direction($lang)` | `app/helpers.php` | Detect RTL/LTR |

---

## Middleware Stack (web group)

1. `EnsureInstalled` (Installer module) — gate-keeps entire app
2. `EncryptCookies`
3. `AddQueuedCookiesToResponse`
4. `StartSession`
5. `ShareErrorsFromSession`
6. `VerifyCsrfToken`
7. `SetLocale`
8. `SubstituteBindings`

### Route middleware aliases
- `role` → Spatie RoleMiddleware
- `permission` → Spatie PermissionMiddleware
- `role_or_permission` → Spatie RoleOrPermissionMiddleware
