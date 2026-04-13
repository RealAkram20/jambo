# Jambo Frontend (User-Facing) — Complete Page Inventory

> The Streamit template provides **147 blade templates** for the public-facing
> streaming platform. Every page is fully designed with dark theme, responsive
> layout, and placeholder content. Our job is to wire these to real data.

---

## Layout System

**Master layout:** `Modules/Frontend/resources/views/master.blade.php`

- Dark theme (`data-bs-theme="dark"`)
- Includes: header, footer, breadcrumbs, loader, back-to-top button
- Props: `isSwiperSlider`, `isFslightbox`, `bodyClass`, `isSelect2`,
  `isVideoJs`, `isBreadCrumb`, `IS_MEGA`, `title`
- Assets via Vite (app.scss, app.js)
- RTL support via locale direction

**Blank layout:** `Modules/Frontend/resources/views/blank.blade.php`

- Minimal — no header/footer (for auth pages, modals)

---

## Controller

`Modules/Frontend/app/Http/Controllers/FrontendController.php`

- **52 methods**, each returning a static template view
- No database queries — pure view dispatch
- All routes defined in `Modules/Frontend/routes/web.php`

---

## Page Inventory

### 1. Main Pages (5 pages)

| Page | URL | Method | Blade file |
|------|-----|--------|------------|
| OTT Home | `/` | `ott()` | `Pages/MainPages/ott-page` |
| Home | `/home` | `index()` | `Pages/MainPages/index-page` |
| Movies | `/movie` | `movie()` | `Pages/MainPages/movies-page` |
| TV Shows | `/tv-show` | `tv_show()` | `Pages/MainPages/tv-shows-page` |
| Videos | `/video` | `video()` | `Pages/MainPages/videos-Page` |

### 2. Movie Detail Pages (4 pages)

| Page | URL | Method | Blade file |
|------|-----|--------|------------|
| Movie Detail | `/movie-detail` | `movie_detail()` | `Pages/Movies/detail-page` |
| Movie Player | `/movie-player` | `movie_player()` | `Pages/Movies/movie-player` |
| Download | `/download` | `download()` | `Pages/Movies/download-page` |
| Restricted | `/resticted` | `resticted()` | `Pages/Movies/resticted-page` |

### 3. TV Show Detail Pages (2 pages)

| Page | URL | Method | Blade file |
|------|-----|--------|------------|
| Show Detail | `/tv-show-detail` | `tvshow_detail()` | `Pages/TvShows/detail-page` |
| Episode | `/episode` | `episode()` | `Pages/TvShows/episode-page` |

### 4. Video Pages (2 pages)

| Page | URL | Method | Blade file |
|------|-----|--------|------------|
| Video Detail | `/video-detail` | `video_detail()` | `Pages/videos-detail` |
| Video Player | `/video-player` | `video_player()` | `Pages/video-player` |

### 5. Browse & Discovery Pages (6 pages)

| Page | URL | Method | Blade file |
|------|-----|--------|------------|
| Genres | `/geners` | `genres()` | `Pages/geners-page` |
| All Genres | `/all-genres` | `all_genres()` | `Pages/all-geners-page` |
| Tags | `/tag` | `tag()` | `Pages/tags-page` |
| All Tags | `/view-all-tags` | `view_all_tags()` | `Pages/view-all-tags` |
| View All | `/view-all` | `view_all()` | `Pages/view-all` |
| View More | `/view-more` | `view_more()` | `Pages/view-more` |

### 6. Cast/Personality Pages (3 pages)

| Page | URL | Method | Blade file |
|------|-----|--------|------------|
| Cast List | `/cast-list` | `cast_list()` | `Pages/Cast/list-page` |
| Cast Detail | `/cast-details` | `cast_details()` | `Pages/Cast/detail-page` |
| All Personalities | `/all-personality` | `all_personality()` | `Pages/Cast/all-personality` |

### 7. Watchlist & Playlist Pages (3 pages)

| Page | URL | Method | Blade file |
|------|-----|--------|------------|
| Watchlist | `/watchlist-detail` | `watchlist_detail()` | `Pages/watchlist-detail` |
| Playlist | `/playlist` | `play_list()` | `Pages/playlist` |
| Playlist Detail | `/playlist-detail` | `playlist_detail()` | `Pages/playlist-detail` |

### 8. User Profile & Account Pages (9 pages)

| Page | URL | Method | Blade file |
|------|-----|--------|------------|
| Profile | `/your-profile` | `your_profile()` | `Pages/Profile/your-profile` |
| Change Password | `/change-password` | `change_password()` | `Pages/Profile/change-password` |
| Membership Account | `/membership-account` | `membership_account()` | `Pages/Profile/membership-account` |
| Membership Level | `/membership-level` | `membership_level()` | `Pages/Profile/membership-level` |
| Membership Invoice | `/membership-invoice` | `membership_invoice()` | `Pages/Profile/membership-invoice` |
| Membership Orders | `/membership-orders` | `membership_orders()` | `Pages/Profile/membership-orders` |
| Order Confirmation | `/membership-comfirmation` | `membership_comfirmation()` | `Pages/Profile/membership-comfirmation` |
| Profile (Marvin) | `/profile-marvin` | `profile_marvin()` | `Pages/profile-marvin` |
| Archive Playlist | `/archive-playlist` | `archive_playlist()` | `Pages/archive-playlist` |

### 9. Blog Pages (21 pages)

**Main blog:**
- Blog List, Blog Detail, Blog Filter

**Layout variants:**
- 1-Column, 2-Column, 3-Column, 4-Column grids

**Sidebar variants:**
- Left Sidebar, Right Sidebar, Sidebar List

**Post types:**
- Audio, Video, Link, Quote, Gallery

**Pagination styles:**
- Numbered, Load More, Infinite Scroll

**Filter pages:**
- By Category, By Tag, By Date, By Author

### 10. Static/Info Pages (9 pages)

| Page | URL | Method | Blade file |
|------|-----|--------|------------|
| About Us | `/about-us` | `about_us()` | `Pages/ExtraPages/about-page` |
| Contact Us | `/contact-us` | `contact_us()` | `Pages/ExtraPages/contact-page` |
| FAQ | `/faq_page` | `faq_page()` | `Pages/ExtraPages/faq-page` |
| Privacy Policy | `/privacy-policy` | `privacy()` | `Pages/ExtraPages/privacy-policy-page` |
| Terms | `/terms-and-policy` | `terms_and_policy()` | `Pages/ExtraPages/terms-of-use-page` |
| Pricing | `/pricing-page` | `pricing_page()` | `Pages/pricing-page` |
| Coming Soon | `/comming-soon` | `comming_soon_page()` | `Pages/ExtraPages/comming-soon-page` |
| Error Page 1 | `/error-page1` | `error_page1()` | `Pages/ExtraPages/error-page1` |
| Error Page 2 | `/error-page2` | `error_page2()` | `Pages/ExtraPages/error-page2` |

---

## Component Library

### Section Components (22)

Homepage content blocks — each is a self-contained slider or grid section:

- `continue-watching` — Resume watching carousel
- `Popular-movies` — Popular movies slider
- `latest-movies` — Latest movies slider
- `specials-latest-movies` — Featured movies
- `popular-show` — Popular TV shows
- `best-in-tv` — Best TV shows
- `best-of-international-shows` — International shows
- `shows-we-recommend` — Recommended shows
- `popular-videos` — Popular videos
- `specials-latest-videol` — Featured videos
- `videos-recommended` — Video recommendations
- `top-ten-block` — Top 10 movies
- `top-ten-tvshow` — Top 10 shows
- `top-ten-video` — Top 10 videos
- `recommended` — General recommendations
- `suggested` — Suggested content
- `fresh-picks-just-for-you` — Personalized picks
- `only-on-streamit` — Exclusive content
- `geners` — Genre browse section
- `tab-slider` — Tabbed content slider
- `tranding-tab` — Trending tabs
- `parallax` — Parallax effect section
- `upcoming` / `upcomming` — Upcoming content
- `verticle-slider` — Vertical carousel
- `Your-Favourite-Personality` — Featured cast

### Card Components (23)

Individual item display templates:

- `movie-slider` — Movie carousel card (banner slider)
- `card-style` — Generic content card
- `continue-watch-card` — Continue watching card (with progress bar)
- `episode-card` — TV episode card
- `genres-card` — Genre card
- `card-genres-grid` — Genre grid item
- `tags-card` — Tag card
- `cast` — Cast member card
- `card-cast-grid` — Cast grid item
- `personality-card` — Cast/crew card (detail)
- `top-ten-card` — Top 10 ranking card
- `watchlist-card` — Watchlist item card
- `movie-description` — Movie info panel
- `movie-source` — Video source indicator
- `blog-card` — Blog post card
- `blog-details` — Blog details widget
- `blog-sidebar` — Blog sidebar widget
- `rating-star` — Star rating display
- `filter-rating` — Rating filter widget
- `video-popup` — Video modal/popup
- `custom-button` — Reusable button

### Widget Components (15+)

- `details-description-modal` — Content description modal
- `details-review` — Review/rating widget
- `download-modal` — Download options modal
- `playlist-modal` — Playlist creation modal
- `share-modal` — Social sharing modal
- `quick-view` — Quick preview modal
- `person-detail-card` — Cast member biography
- `profile-card` — User profile card
- `membership-invoce-card` — Invoice display
- `notification-item` — Notification item
- `mobile-footer` — Mobile navigation bar
- `watchlist-detail-card` — Watchlist item detail

### Partial Components

- `header-default` — Main site header with navigation
- `footer-default` — Main site footer
- `breadcrumb-widget` — Breadcrumb navigation
- `back-to-top` — Scroll to top button
- `loader-component` — Page loading spinner
- `setting` — Theme settings panel

---

## Frontend Assets

### Public assets (`public/frontend/`)

```
images/
  ├── media/         100+ movie/show poster images (webp)
  ├── cast/          55+ actor photos (webp)
  ├── blog/          26 blog thumbnails
  ├── genre/         7 genre icons
  ├── video/         Video thumbnails
  ├── user/          User profile images
  ├── tags/          Tag icons
  └── pages/         Page-specific images

vendor/
  ├── swiperSlider/  Carousel library
  ├── video/         Video.js player
  ├── videojs-youtube-master/
  ├── sweetalert2/   Alert dialogs
  ├── select2/       Enhanced dropdowns
  ├── gsap/          Animations
  ├── phosphor-icons/ (6 styles: regular, bold, fill, duotone, light, thin)
  ├── font-awesome/
  ├── iconly/
  ├── flatpickr/     Date picker
  ├── noUiSlider/    Range slider
  ├── lodash/
  └── fonts/         Gilroy, Helvetica
```

### SCSS (`Modules/Frontend/resources/assets/sass/`)

- `app.scss` — Main entry point
- Custom design system with components, variables, helpers
- Full RTL support

---

## Translation Files

| File | Content |
|------|---------|
| `lang/en/home.php` | Home page strings |
| `lang/en/movie.php` | Movie page strings |
| `lang/en/otthome.php` | OTT platform strings |
| `lang/en/tvshow.php` | TV show strings |
| `lang/en/video.php` | Video page strings |

---

## Wiring Strategy (Phase 3)

When wiring frontend pages to real data:

1. **Read the template blade** to understand component props and structure
2. **Pass Eloquent collections** from the controller to the view
3. **Replace hardcoded image paths** with `$movie->poster_url`
4. **Replace hardcoded text** with `$movie->title`, `$movie->synopsis`, etc.
5. **Keep every CSS class, layout, and animation** exactly as designed
6. **Use the section components** (Popular-movies, top-ten-block, etc.) with
   real data — they're designed as reusable sliders
7. **Wire the detail pages** with route parameters (`/movie-detail/{slug}`)
8. **Wire the player pages** with Dropbox streaming URLs

**Start with:** Home page (`/`) → Movie listing (`/movie`) → Movie detail →
TV Show listing → Show detail → Episode player
