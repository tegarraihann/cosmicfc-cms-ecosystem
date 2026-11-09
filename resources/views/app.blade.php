<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @php($seoMeta = $page['props']['seoMeta'] ?? [])
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>{{ $seoMeta['title'] ?? config('app.name', 'COSMIC') }}</title>
        <meta inertia="description" name="description" content="{{ $seoMeta['description'] ?? '' }}">
        @if (! empty($seoMeta['keywords']))
            <meta inertia="keywords" name="keywords" content="{{ $seoMeta['keywords'] }}">
        @endif
        <meta inertia="robots" name="robots" content="{{ $seoMeta['robots'] ?? 'noindex, nofollow' }}">
        <link inertia="canonical" rel="canonical" href="{{ $seoMeta['canonical'] ?? request()->url() }}">
        <link inertia="favicon" rel="icon" href="{{ $seoMeta['favicon'] ?? asset('favicon.ico') }}">
        <meta inertia="og:type" property="og:type" content="{{ $seoMeta['type'] ?? 'website' }}">
        <meta inertia="og:title" property="og:title" content="{{ $seoMeta['title'] ?? config('app.name', 'COSMIC') }}">
        <meta inertia="og:description" property="og:description" content="{{ $seoMeta['description'] ?? '' }}">
        <meta inertia="og:url" property="og:url" content="{{ $seoMeta['canonical'] ?? request()->url() }}">
        <meta inertia="og:site_name" property="og:site_name" content="{{ $seoMeta['siteName'] ?? 'COSMIC' }}">
        @if (! empty($seoMeta['image']))
            <meta inertia="og:image" property="og:image" content="{{ $seoMeta['image'] }}">
            <meta inertia="og:image:width" property="og:image:width" content="1200">
            <meta inertia="og:image:height" property="og:image:height" content="630">
        @endif
        <meta inertia="twitter:card" name="twitter:card" content="{{ $seoMeta['twitterCard'] ?? 'summary_large_image' }}">
        <meta inertia="twitter:title" name="twitter:title" content="{{ $seoMeta['title'] ?? config('app.name', 'COSMIC') }}">
        <meta inertia="twitter:description" name="twitter:description" content="{{ $seoMeta['description'] ?? '' }}">
        @if (! empty($seoMeta['image']))
            <meta inertia="twitter:image" name="twitter:image" content="{{ $seoMeta['image'] }}">
        @endif
        @if (! empty($seoMeta['publishedTime']))
            <meta inertia="article:published_time" property="article:published_time" content="{{ $seoMeta['publishedTime'] }}">
        @endif
        @if (! empty($seoMeta['schema']))
            <script inertia="structured-data" type="application/ld+json">{!! json_encode($seoMeta['schema'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
        @endif

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Archivo+Black&family=Barlow+Condensed:wght@400;500;600;700;800;900&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

        <!-- Scripts -->
        @routes
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
        @php($measurementId = $page['props']['site']['website']['analytics_id'] ?? null)
        @if ($measurementId && ! request()->is('admin/*') && ! request()->is('dashboard') && ! request()->is('profile'))
            <script async src="https://www.googletagmanager.com/gtag/js?id={{ rawurlencode($measurementId) }}"></script>
            <script>
                window.dataLayer = window.dataLayer || [];
                function gtag(){dataLayer.push(arguments);}
                gtag('js', new Date());
                gtag('config', @json($measurementId), { anonymize_ip: true });
            </script>
        @endif
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
