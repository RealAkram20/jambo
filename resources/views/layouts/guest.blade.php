<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="dark" class="theme-fs-md">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ app_name() }}</title>
    @include('components.partials.head.head')


    <meta name="setting_options" content="{{ setting('customization_json') }}">

    <!-- Fonts -->
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;1,100;1,300&display=swap">


    <!-- Scripts -->
    @vite(['resources/css/app.scss', 'public/dashboard/scss/streamit.scss', 'public/dashboard/scss/dashboard-custom.scss', 'public/dashboard/scss/customizer.scss', 'public/dashboard/scss/pro.scss', 'public/dashboard/scss/custom.scss', 'resources/js/app.js'])
    <style>
        :root {
            --bs-primary: #1A98FF;
            --bs-primary-rgb: 26, 152, 255;
            --bs-link-color: #1A98FF;
            --bs-link-color-rgb: 26, 152, 255;
            --bs-link-hover-color: #147acc;
        }
    </style>

</head>

<body class="card-default dark">
    <div>
        @yield('content')
    </div>

    @include('components.partials.scripts.script')
</body>

</html>
