@props(['page_name' => ''])

@php
    if ($page_name == '') {
        $title = env('APP_NAME');
    }
    else {
        $title = env('APP_NAME') . ' — ' . $page_name;
    }
@endphp

<!doctype html>
<html lang="en" class="h-full bg-gray-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>{{ $title }}</title>
    @vite(['resources/js/app.js', 'resources/css/app.css'])
</head>
<body class="h-full">
    <div class="min-h-full">
        <x-nav-menu />

        <x-page-header :$page_name />

        <main>
            <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                {{ $slot }}
            </div>
        </main>
    </div>
</body>
</html>
