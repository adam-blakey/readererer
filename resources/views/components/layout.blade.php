@props(['page_name' => ''])

@php
    if ($page_name == '') {
        $title = env('APP_NAME');
        $page_name = $title;
    } else {
        $title = env('APP_NAME') . ' — ' . $page_name;
    }
@endphp

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
    <meta http-equiv="X-UA-Compatible" content="ie=edge" />
    <title>{{ $title }}</title>
    @vite(['resources/js/app.js', 'resources/css/app.css'])
    <link rel="shortcut icon" href="{{ Vite::asset('resources/images/favicon.png') }}">
</head>

<body>
    <div class="page">
        <x-nav-menu></x-nav-menu>
        <div class="page-wrapper">
            <x-page-header :$page_name />
            <div class="page-body">
                {{ $slot }}
            </div>
            <x-footer />
        </div>
    </div>
</body>

</html>
