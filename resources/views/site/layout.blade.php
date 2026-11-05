<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @if(($authUser->locale ?? null) === 'ar') dir="rtl" @endif>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Lens' }} · Find. Book. Create.</title>
    <link rel="icon" href="{{ asset('branding/lenslogo_mark.png') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800|caveat:600,700" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/lens-site.css') }}?v={{ @filemtime(public_path('css/lens-site.css')) }}">
    @vite(['resources/css/site.css', 'resources/js/site.js'])
    @stack('head')
</head>
<body>
    @include('site.partials.header')
    <main>
        @if (session('status'))
            <div class="wrap" style="padding-top:16px">
                <div class="flash-ok">{{ session('status') }}</div>
            </div>
        @endif
        @yield('content')
    </main>
    @include('site.partials.footer')
    @stack('scripts')
</body>
</html>
