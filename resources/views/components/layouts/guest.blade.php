<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Ruang kerja manajemen lumbung beras.">
    <meta name="theme-color" content="#00478a">
    <title>{{ $title ?? 'Masuk · Lumbung Beras' }}</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="flex min-h-dvh items-center justify-center px-4 py-8 sm:px-6">
    <x-notification/>
    <x-confirmation-dialog/>
    <main class="w-full max-w-[960px]">{{ $slot }}</main>
    @livewireScripts
</body>
</html>
