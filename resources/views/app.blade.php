<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">

    <title inertia>{{ config('app.name', 'TrustCash') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">

    <!-- Clean & Modern Fonts: Inter (Latin/Digits) + Hind Siliguri & Noto Sans Bengali (Bangla) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@300;400;500;600;700&family=Inter:wght@300;400;500;600;700;800&family=Noto+Sans+Bengali:wght@400;500;600;700&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @routes
    {{-- Single entry: pages load via dynamic import in app.js (second entry duplicated Vite work and slows every visit in dev) --}}
    @vite(['resources/js/app.js'])
    @inertiaHead

    <style>
        @media print {
            @page {
                margin: 0;
            }

            body {
                margin: 0;
            }
        }
    </style>
</head>

<body class="font-sans antialiased">
    @inertia
</body>

</html>
