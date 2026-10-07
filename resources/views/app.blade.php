@php
    $siteFavicon = '';
    try {
        if (class_exists(\App\Models\Setting::class)) {
            $siteFavicon = \App\Models\Setting::getCentral('site_favicon', '');
        }
    } catch (\Throwable $e) {}
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">

    <title inertia>{{ config('app.name', 'TrustCash') }}</title>
    @if(!empty($siteFavicon))
        <link rel="icon" href="{{ $siteFavicon }}?v={{ substr(md5($siteFavicon), 0, 8) }}">
        <link rel="shortcut icon" href="{{ $siteFavicon }}?v={{ substr(md5($siteFavicon), 0, 8) }}">
        <link rel="apple-touch-icon" href="{{ $siteFavicon }}?v={{ substr(md5($siteFavicon), 0, 8) }}">
    @else
        <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('icons/favicon-32x32.png') }}">
        <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('icons/favicon-16x16.png') }}">
        <link rel="shortcut icon" type="image/png" href="{{ asset('icons/favicon-32x32.png') }}">
        <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('icons/apple-touch-icon.png') }}">
    @endif

    <!-- Progressive Web App (PWA) Meta & Manifest -->
    <link rel="manifest" href="/manifest.webmanifest">
    <meta name="theme-color" content="#0f172a">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="TrustCash">

    <!-- Clean & Modern Fonts: Inter (Latin/Digits) + Hind Siliguri & Noto Sans Bengali (Bangla) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@300;400;500;600;700&family=Inter:wght@300;400;500;600;700;800&family=Noto+Sans+Bengali:wght@400;500;600;700&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @routes
    {{-- Single entry: pages load via dynamic import in app.js (second entry duplicated Vite work and slows every visit in dev) --}}
    @vite(['resources/js/app.js'])
    @inertiaHead


</head>

<body class="font-sans antialiased">
    @inertia
</body>

</html>
