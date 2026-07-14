<!doctype html>
<html lang="en" data-bs-theme="dark" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Jambo UI Kit</title>

    {{-- Real admin theme so the mirrored components preview exactly as they
         render inside the admin panel (Streamit). Token partial goes AFTER
         the bundles (same order as the real layouts) so #1A98FF wins. --}}
    <script>
        // Same customizer seed the admin layout uses, so the theme applies its
        // blue "color-2" scheme (not the default red) — faithful admin preview.
        (function () {
            try {
                var storageKey = 'streamit';
                if (sessionStorage.getItem(storageKey) || localStorage.getItem(storageKey)) return;
                sessionStorage.setItem(storageKey, JSON.stringify({
                    saveLocal: 'sessionStorage', storeKey: storageKey,
                    setting: {
                        theme_scheme_direction: { target: 'html', choices: ['ltr','rtl'], value: 'ltr' },
                        theme_color: { colors: {
                            "--bs-primary": "#1A98FF", "--bs-primary-rgb": "26, 152, 255",
                            "--bs-secondary": "#adafb8", "--bs-tertiray": "#89F425"
                        }, value: "color-2" }
                    }
                }));
            } catch (e) {}
        })();
    </script>
    @vite(['resources/css/app.scss', 'public/dashboard/scss/streamit.scss',
        'public/dashboard/scss/dashboard-custom.scss', 'public/dashboard/scss/customizer.scss',
        'public/dashboard/scss/pro.scss', 'public/dashboard/scss/custom.scss',
        'resources/js/app.js'])
    @include('components.partials.theme-tokens')
    <link rel="stylesheet" href="{{ asset('dashboard/vendor/phosphor-icons/Fonts/regular/style.css') }}">
    <link rel="stylesheet" href="{{ asset('dashboard/vendor/phosphor-icons/Fonts/fill/style.css') }}">
    <script src="https://cdn.jsdelivr.net/npm/apexcharts@3.54.0"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js"></script>
    <style>body{padding:24px;} </style>
</head>
<body>
<div class="container-fluid" style="max-width:1100px;">

    <x-ui.page-header title="UI Kit Showcase" subtitle="Mirrors the admin theme — same components for partner & user">
        <x-slot:actions>
            <button class="btn btn-primary"><i class="ph ph-plus me-1"></i>Add movie</button>
        </x-slot:actions>
    </x-ui.page-header>

    {{-- Stat cards — Streamit icon-space/card-details style --}}
    <div class="row mb-4">
        <div class="col-md-3 col-sm-6">
            <x-ui.stat-card label="Total users" value="18,204" icon="ph ph-user" :trend="3.1" sub="7-day" href="#"/>
        </div>
        <div class="col-md-3 col-sm-6">
            <x-ui.stat-card label="Movies" value="342" icon="ph ph-film-strip"/>
        </div>
        <div class="col-md-3 col-sm-6">
            <x-ui.stat-card label="Watch minutes" value="1.9M" icon="ph ph-play-circle" :trend="-2.7" sub="dropping"/>
        </div>
        <div class="col-md-3 col-sm-6">
            <x-ui.stat-card label="Pending payouts" value="7" icon="ph ph-hourglass"/>
        </div>
    </div>

    <div class="row">
        {{-- Chart in a card --}}
        <div class="col-lg-8">
            <x-ui.card title="Streaming activity" subtitle="Last 7 days">
                <x-slot:actions><x-ui.badge variant="success">Live</x-ui.badge></x-slot:actions>
                <x-ui.chart
                    type="area"
                    :height="280"
                    :series="[['name' => 'Views', 'data' => [820, 932, 901, 1290, 1330, 1120, 1450]]]"
                    :options="['xaxis' => ['categories' => ['Mon','Tue','Wed','Thu','Fri','Sat','Sun']]]"
                />
            </x-ui.card>
        </div>

        {{-- Badges + empty state --}}
        <div class="col-lg-4">
            <x-ui.card title="Statuses">
                <div class="d-flex flex-wrap gap-2 mb-4">
                    <x-ui.badge variant="success">Published</x-ui.badge>
                    <x-ui.badge variant="info">Upcoming</x-ui.badge>
                    <x-ui.badge variant="warning">Draft</x-ui.badge>
                    <x-ui.badge variant="secondary" soft>Action</x-ui.badge>
                    <x-ui.badge variant="info" soft>4 cast</x-ui.badge>
                </div>
                <x-ui.empty-state icon="ph ph-film-strip" title="No episodes" message="Add the first episode to this season.">
                    <x-slot:action><button class="btn btn-sm btn-primary">Add episode</button></x-slot:action>
                </x-ui.empty-state>
            </x-ui.card>
        </div>
    </div>

    {{-- Data table component --}}
    <x-ui.card title="Recent uploads" :padded="false">
        <x-slot:actions><a href="#" class="btn btn-sm btn-primary">View all</a></x-slot:actions>
        <x-ui.data-table :heads="['Title', 'Type', 'Status', 'Views']">
            <tr>
                <td class="fw-semibold">The Great Escape</td><td>Movie</td>
                <td><x-ui.badge variant="success">Published</x-ui.badge></td><td>12,908</td>
            </tr>
            <tr>
                <td class="fw-semibold">City Lights S2</td><td>Show</td>
                <td><x-ui.badge variant="warning">Draft</x-ui.badge></td><td>—</td>
            </tr>
            <tr>
                <td class="fw-semibold">Nightfall</td><td>Movie</td>
                <td><x-ui.badge variant="danger">Suspended</x-ui.badge></td><td>4,201</td>
            </tr>
        </x-ui.data-table>
    </x-ui.card>

    <p class="text-muted text-center mt-4" style="font-size:12px;">
        Delete <code>resources/views/ui-kit-demo.blade.php</code> and its route when done previewing.
    </p>
</div>
</body>
</html>
