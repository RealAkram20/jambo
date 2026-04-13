# Jambo Admin Dashboard — UI/UX Guidelines

**Template:** Streamit by Iqonic Design (Laravel)
**Framework:** Bootstrap 5.3 (dark theme)
**Icons:** Phosphor Icons (`ph ph-*`)
**Font:** Roboto (Google Fonts)
**Primary colour:** `#1A98FF` (var `--bs-primary`)

> Every new admin page, sidebar entry, form, or table **must** follow these
> patterns exactly. Do not invent new CSS classes, spacing, or component
> structures. Copy from a working template page and adapt.

---

## 1. Sidebar Navigation

**File:** `resources/views/components/partials/vertical-nav.blade.php`

### Simple nav item

```html
<li class="nav-item">
    <a class="nav-link {{ activeRoute(route('route.name')) }}" href="{{ route('route.name') }}">
        <i class="icon" data-bs-toggle="tooltip" data-bs-placement="right"
            aria-label="Label" data-bs-original-title="Label">
            <i class="ph ph-icon-name fs-4"></i>
        </i>
        <span class="item-name">Label</span>
    </a>
</li>
```

### Collapsible nav item with sub-menu

```html
<li class="nav-item">
    <a class="nav-link" data-bs-toggle="collapse" href="#sidebar-unique-id"
        role="button" aria-expanded="false" aria-controls="sidebar-unique-id">
        <i class="icon" data-bs-toggle="tooltip" data-bs-placement="right"
            aria-label="Label" data-bs-original-title="Label">
            <i class="ph ph-icon-name fs-4"></i>
        </i>
        <span class="item-name">Label</span>
        <i class="right-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" class="icon-18"
                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round"
                    stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
        </i>
    </a>
    <ul class="sub-nav collapse" id="sidebar-unique-id" data-bs-parent="#sidebar-menu">
        <li class="nav-item">
            <a class="nav-link {{ activeRoute(route('sub.route')) }}"
                href="{{ route('sub.route') }}">
                <i class="icon" data-bs-toggle="tooltip" data-bs-placement="right"
                    aria-label="Sub Label" data-bs-original-title="Sub Label">
                    <i class="ph ph-sub-icon fs-5"></i>
                </i>
                <span class="item-name">Sub Label</span>
            </a>
        </li>
    </ul>
</li>
```

### Rules

| Rule | Correct | Wrong |
|------|---------|-------|
| List item class | `nav-item` | `nav-item mb-4` |
| Parent icon size | `fs-4` | `fs-5`, `fs-3` |
| Sub-item icon size | `fs-5` | `fs-4` |
| Active state | `{{ activeRoute(route('...')) }}` | custom `active` logic |
| Accordion parent | `data-bs-parent="#sidebar-menu"` | omitting it |
| Spacing | None (template CSS handles it) | `mb-*`, `mt-*`, `py-*` |

**Never add `mb-4` or any extra margin to `nav-item`.** The sidebar CSS
already provides correct spacing. Adding margin creates visible gaps that
break the visual rhythm.

---

## 2. Layout Structure

**Master layout:** `resources/views/layouts/app.blade.php`

```html
@extends('layouts.app', ['module_title' => 'Page Title'])

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            {{-- Page content --}}
        </div>
    </div>
</div>
@endsection
```

### Content hierarchy

```
<main class="main-content">
    <nav class="nav navbar ...">            ← Header
    <div class="content-inner container-fluid pb-0" id="page_layout">
        @yield('content')                   ← Your page
    </div>
    <footer>                                ← Footer
</main>
```

---

## 3. Cards

```html
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h4 class="card-title mb-1">Title</h4>
            <p class="text-muted mb-0" style="font-size:13px;">Subtitle</p>
        </div>
        <a href="#" class="btn btn-primary">
            <i class="ph ph-plus me-1"></i> Action
        </a>
    </div>
    <div class="card-body">
        {{-- Content --}}
    </div>
</div>
```

- Card spacing between cards: `mt-4`
- Two-column form layout: `col-lg-8` (main) + `col-lg-4` (sidebar)
- Section heading inside card body: `<h6 class="mb-0">Section</h6>`

---

## 4. Tables

```html
<div class="table-responsive">
    <table class="table custom-table align-middle mb-0">
        <thead>
            <tr class="text-uppercase" style="font-size:11px;letter-spacing:.5px;">
                <th>Column</th>
                <th class="text-end">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($items as $item)
                <tr>
                    <td>{{ $item->name }}</td>
                    <td class="text-end">
                        <div class="d-inline-flex gap-1">
                            <a href="{{ route('edit', $item) }}"
                                class="btn btn-sm btn-success-subtle" title="Edit">
                                <i class="ph ph-pencil-simple"></i>
                            </a>
                            <form method="POST" action="{{ route('destroy', $item) }}"
                                class="d-inline"
                                onsubmit="return confirm('Delete {{ $item->name }}?');">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-danger-subtle" title="Delete">
                                    <i class="ph ph-trash-simple"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="99" class="text-center py-5 text-muted"
                        style="font-size:14px;">
                        No items yet. <a href="#">Add one &rarr;</a>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
```

### Table conventions

- Header: `text-uppercase`, 11px, 0.5px letter-spacing
- Row alignment: `align-middle`
- Action column: `text-end`
- Edit button: `btn btn-sm btn-success-subtle` + `ph ph-pencil-simple`
- Delete button: `btn btn-sm btn-danger-subtle` + `ph ph-trash-simple`
- Delete confirmation: native `confirm()` dialog
- Empty state: `colspan` full width, `text-center py-5 text-muted`, 14px

---

## 5. Forms

### Text input

```html
<div class="mb-3">
    <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
    <input type="text" class="form-control @error('name') is-invalid @enderror"
        id="name" name="name" value="{{ old('name', $model->name) }}" required>
    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>
```

### Textarea

```html
<div class="mb-3">
    <label for="bio" class="form-label">Bio</label>
    <textarea class="form-control @error('bio') is-invalid @enderror"
        id="bio" name="bio" rows="4">{{ old('bio', $model->bio) }}</textarea>
    @error('bio') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>
```

### Select

```html
<div class="mb-3">
    <label for="status" class="form-label">Status</label>
    <select name="status" id="status" class="form-select">
        <option value="draft" @selected(old('status', $model->status) === 'draft')>Draft</option>
        <option value="published" @selected(old('status', $model->status) === 'published')>Published</option>
    </select>
</div>
```

### Checkbox group

```html
<div class="card mt-4">
    <div class="card-header"><h6 class="mb-0">Genres</h6></div>
    <div class="card-body">
        @foreach ($genres as $genre)
            <div class="form-check">
                <input type="checkbox" class="form-check-input" id="g{{ $genre->id }}"
                    name="genre_ids[]" value="{{ $genre->id }}"
                    @checked(in_array($genre->id, old('genre_ids', $currentIds)))>
                <label class="form-check-label" for="g{{ $genre->id }}">
                    {{ $genre->name }}
                </label>
            </div>
        @endforeach
    </div>
</div>
```

### Multi-column row

```html
<div class="row g-3">
    <div class="col-md-6">
        {{-- Field 1 --}}
    </div>
    <div class="col-md-6">
        {{-- Field 2 --}}
    </div>
</div>
```

### Form actions (bottom)

```html
<div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
    <a href="{{ route('index') }}" class="btn btn-ghost">&larr; Back to list</a>
    <button type="submit" class="btn btn-primary">
        <i class="ph ph-floppy-disk me-1"></i> Save
    </button>
</div>
```

### Filter/search bar

```html
<form method="GET" action="{{ route('index') }}" class="row g-2 align-items-end mb-4">
    <div class="col-md-6">
        <label class="form-label"
            style="font-size:12px;text-transform:uppercase;letter-spacing:.5px;color:var(--bs-secondary);">
            Search
        </label>
        <input type="text" name="q" value="{{ $search }}" class="form-control"
            placeholder="Search...">
    </div>
    <div class="col-md-3">
        <label class="form-label"
            style="font-size:12px;text-transform:uppercase;letter-spacing:.5px;color:var(--bs-secondary);">
            Status
        </label>
        <select name="status" class="form-select">
            <option value="">All</option>
            <option value="published" @selected($filter === 'published')>Published</option>
            <option value="draft" @selected($filter === 'draft')>Draft</option>
        </select>
    </div>
    <div class="col-md-3 d-flex gap-2">
        <button type="submit" class="btn btn-primary flex-fill">Filter</button>
        @if ($search || $filter)
            <a href="{{ route('index') }}" class="btn btn-ghost">Clear</a>
        @endif
    </div>
</form>
```

---

## 6. Alerts / Flash Messages

```html
@if (session('success'))
    <div class="alert alert-success mx-4 mt-3 mb-0">{{ session('success') }}</div>
@endif

@if ($errors->any())
    <div class="alert alert-danger">
        <strong>Please fix the following:</strong>
        <ul class="mb-0 mt-1">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
```

---

## 7. Buttons

| Use | Classes | Icon |
|-----|---------|------|
| Primary action | `btn btn-primary` | `ph ph-floppy-disk` (save), `ph ph-plus` (add) |
| Back / cancel | `btn btn-ghost` | none |
| Edit (table) | `btn btn-sm btn-success-subtle` | `ph ph-pencil-simple` |
| Delete (table) | `btn btn-sm btn-danger-subtle` | `ph ph-trash-simple` |
| Remove (inline) | `btn btn-sm btn-danger-subtle` | `ph ph-x` |
| Filter submit | `btn btn-primary flex-fill` | none |
| Clear filter | `btn btn-ghost` | none |

Icon spacing: `me-1` between icon and text.

---

## 8. Badges

| Use | Classes |
|-----|---------|
| Published status | `badge bg-success` |
| Draft status | `badge bg-warning` |
| Generic info | `badge bg-secondary` |
| Tier required | `badge bg-primary` |
| Taxonomy (genre, tag) | `badge bg-secondary-subtle text-secondary-emphasis` |
| Count | `badge bg-info-subtle text-info-emphasis` |

---

## 9. Modals

```html
<div class="modal fade" id="modal-id" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Title</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                {{-- Content --}}
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary">Save</button>
            </div>
        </div>
    </div>
</div>
```

Trigger: `data-bs-toggle="modal" data-bs-target="#modal-id"`

---

## 10. Offcanvas

```html
<div class="offcanvas offcanvas-end" tabindex="-1" id="panel-id">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title">Title</h5>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body">
        {{-- Content --}}
    </div>
</div>
```

Trigger: `data-bs-toggle="offcanvas" data-bs-target="#panel-id"`

---

## 11. Colour Variables

```css
--bs-primary:           #1A98FF;
--bs-primary-rgb:       26, 152, 255;
--bs-link-color:        #1A98FF;
--bs-link-hover-color:  #147acc;
```

Dark backgrounds used in custom elements:

| Use | Colour |
|-----|--------|
| Dark row background | `#0b0f17` |
| Placeholder box | `#1f2738` |
| Placeholder text | `#8791a3` |

---

## 12. Typography

| Context | Size | Style |
|---------|------|-------|
| Filter/search label | 12px | `text-transform:uppercase; letter-spacing:.5px; color:var(--bs-secondary)` |
| Table header | 11px | `text-uppercase; letter-spacing:.5px` |
| Helper/subtitle | 13px | `text-muted` |
| Empty state | 14px | `text-muted text-center` |
| Card title | `h4` or `h6` | `mb-0` or `mb-1` |

---

## 13. Route naming conventions

| Pattern | Example |
|---------|---------|
| Admin CRUD | `admin.movies.index`, `admin.movies.create`, `admin.movies.store`, `admin.movies.edit`, `admin.movies.update`, `admin.movies.destroy` |
| Template dashboard | `dashboard.movie-list`, `dashboard.rating` |
| Module routes | `notifications.index`, `admin.payments.index`, `admin.updates.index` |

Admin routes use `middleware('auth', 'role:admin')`.

---

## 14. File locations

| What | Where |
|------|-------|
| Master layout | `resources/views/layouts/app.blade.php` |
| Sidebar nav | `resources/views/components/partials/vertical-nav.blade.php` |
| Header | `resources/views/components/partials/header.blade.php` |
| Sidebar wrapper | `resources/views/components/partials/sidebar.blade.php` |
| Notifications bell | `resources/views/components/partials/notifications-bell.blade.php` |
| Footer | `resources/views/components/partials/footer.blade.php` |
| Dashboard SCSS | `public/dashboard/scss/` |
| Dashboard JS | `public/dashboard/js/` |
| Content CRUD views | `Modules/Content/resources/views/admin/` |
| Notifications views | `Modules/Notifications/resources/views/` |
| Payments views | `Modules/Payments/resources/views/` |

---

## 15. Checklist for new admin pages

Before shipping any new admin page, verify:

- [ ] Extends `layouts.app` with `module_title`
- [ ] Sidebar entry uses `nav-item` (no `mb-4` or extra spacing)
- [ ] Icon uses Phosphor (`ph ph-*`) with `fs-4` for parent, `fs-5` for sub
- [ ] Tables use `custom-table align-middle` with uppercase 11px headers
- [ ] Edit/delete buttons use `btn-success-subtle` / `btn-danger-subtle`
- [ ] Forms use `form-control` + `@error` + `invalid-feedback`
- [ ] Required fields marked with `<span class="text-danger">*</span>`
- [ ] Flash messages use `alert alert-success` / `alert alert-danger`
- [ ] Delete uses `confirm()` dialog
- [ ] Card spacing uses `mt-4` between cards
- [ ] No custom CSS added — only Bootstrap 5 utility classes
- [ ] Spacing matches the original template items visually
