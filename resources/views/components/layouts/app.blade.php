@php
    $seoOutput = '';
    try {
        if (class_exists(\Artesaos\SEOTools\Facades\SEOTools::class)) {
            $seoOutput = \Artesaos\SEOTools\Facades\SEOTools::generate();
        }
    } catch (\Throwable $e) {
        $seoOutput = '';
    }
@endphp
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Munoludy' }}</title>

    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">

    {!! $seoOutput !!}

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="min-h-screen bg-munoludy-bg font-body text-white antialiased">
    <x-circular-pattern />
    <div class="relative z-10 min-h-screen flex flex-col">
        {{ $slot }}
    </div>
    @stack('scripts')
</body>
</html>
