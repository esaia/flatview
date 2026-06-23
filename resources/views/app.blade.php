<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        @php
            $seoTitle = config('app.name', 'Flatview');
            $seoDescription = 'Flatview — interactive real-estate visual experiences. Explore properties, floors and flats in immersive 360° tours.';
            $seoImage = url('/featured.jpg');
            $seoUrl = url()->current();
        @endphp

        <title inertia>{{ $seoTitle }}</title>

        <!-- Primary SEO -->
        <meta name="description" content="{{ $seoDescription }}" inertia="description">
        <link rel="canonical" href="{{ $seoUrl }}" inertia="canonical">

        <!-- Open Graph -->
        <meta property="og:type" content="website" inertia="og:type">
        <meta property="og:site_name" content="{{ $seoTitle }}" inertia="og:site_name">
        <meta property="og:title" content="{{ $seoTitle }}" inertia="og:title">
        <meta property="og:description" content="{{ $seoDescription }}" inertia="og:description">
        <meta property="og:url" content="{{ $seoUrl }}" inertia="og:url">
        <meta property="og:image" content="{{ $seoImage }}" inertia="og:image">
        <meta property="og:image:type" content="image/jpeg" inertia="og:image:type">
        <meta property="og:image:width" content="1200" inertia="og:image:width">
        <meta property="og:image:height" content="637" inertia="og:image:height">
        <meta property="og:image:alt" content="{{ $seoTitle }}" inertia="og:image:alt">

        <!-- Twitter -->
        <meta name="twitter:card" content="summary_large_image" inertia="twitter:card">
        <meta name="twitter:title" content="{{ $seoTitle }}" inertia="twitter:title">
        <meta name="twitter:description" content="{{ $seoDescription }}" inertia="twitter:description">
        <meta name="twitter:image" content="{{ $seoImage }}" inertia="twitter:image">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:300,400,500,700|fraunces:300,400,400i,500&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @routes
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
