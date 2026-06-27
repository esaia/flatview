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

        {{-- These SEO tags are intentionally NOT marked with the `inertia`
             attribute: Inertia's head manager removes any `inertia="..."` tag
             that the active page's <Head> doesn't re-declare, which was
             stripping the description/OG/Twitter tags from the DOM after mount
             (Lighthouse audits the rendered DOM and reported them missing).
             Left as static tags they persist; each page is server-rendered on
             a fresh load, so the values are correct per-URL. --}}

        <!-- Primary SEO -->
        <meta name="description" content="{{ $seoDescription }}">
        <link rel="canonical" href="{{ $seoUrl }}">

        <!-- Open Graph -->
        <meta property="og:type" content="website">
        <meta property="og:site_name" content="{{ $seoTitle }}">
        <meta property="og:title" content="{{ $seoTitle }}">
        <meta property="og:description" content="{{ $seoDescription }}">
        <meta property="og:url" content="{{ $seoUrl }}">
        <meta property="og:image" content="{{ $seoImage }}">
        <meta property="og:image:type" content="image/jpeg">
        <meta property="og:image:width" content="1200">
        <meta property="og:image:height" content="637">
        <meta property="og:image:alt" content="{{ $seoTitle }}">

        <!-- Twitter -->
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="{{ $seoTitle }}">
        <meta name="twitter:description" content="{{ $seoDescription }}">
        <meta name="twitter:image" content="{{ $seoImage }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:300,400,500,700|fraunces:300,400,400i,500&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @routes
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
        @production
        <script defer src="https://umami.flatview.org/script.js" data-website-id="99b67c94-ea1c-4447-9401-6367b57a33c4"></script>
        @endproduction
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
