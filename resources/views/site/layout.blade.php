<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Lens' }} · Find. Book. Create.</title>
    <link rel="icon" href="{{ asset('branding/lenslogo_mark.png') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800|caveat:600,700" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/lens-site.css') }}?v={{ @filemtime(public_path('css/lens-site.css')) }}">
</head>
<body>
    @include('site.partials.header')
    <main>
        @yield('content')
    </main>
    @include('site.partials.footer')
    <script src="{{ asset('js/lens-site.js') }}?v={{ @filemtime(public_path('js/lens-site.js')) }}"></script>
</body>
</html>
