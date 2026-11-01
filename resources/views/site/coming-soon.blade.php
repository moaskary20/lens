<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lens · Coming Soon</title>
    <meta name="description" content="Lens is almost here. Find, book, and create with Egypt’s creative marketplace.">
    <link rel="icon" href="{{ asset('branding/lenslogo_mark.png') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800|syne:700,800" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/lens-coming-soon.css') }}?v={{ @filemtime(public_path('css/lens-coming-soon.css')) }}">
</head>
<body class="coming-soon" data-launch-at="{{ $launchAt }}">
    <div class="cs-stage" aria-hidden="true">
        <div class="cs-photo" style="background-image: url('{{ asset('branding/hero-pyramids.jpg') }}')"></div>
        <div class="cs-veil"></div>
        <div class="cs-glow"></div>
        <div class="cs-grain"></div>
    </div>

    <main class="cs-main">
        <header class="cs-brand">
            <img
                class="cs-logo"
                src="{{ asset('branding/lenslogo_clear.png') }}"
                alt="Lens"
                width="220"
                height="64"
            >
        </header>

        <p class="cs-kicker">Launching November 8</p>
        <h1 class="cs-title">Coming Soon</h1>
        <p class="cs-lede">Egypt’s creative marketplace for photographers, studios, and creators. Find. Book. Create.</p>

        <div class="cs-countdown" role="timer" aria-live="polite" aria-label="Countdown to launch">
            <div class="cs-unit">
                <span class="cs-value" data-unit="days">00</span>
                <span class="cs-label">Days</span>
            </div>
            <div class="cs-sep" aria-hidden="true">:</div>
            <div class="cs-unit">
                <span class="cs-value" data-unit="hours">00</span>
                <span class="cs-label">Hours</span>
            </div>
            <div class="cs-sep" aria-hidden="true">:</div>
            <div class="cs-unit">
                <span class="cs-value" data-unit="minutes">00</span>
                <span class="cs-label">Minutes</span>
            </div>
            <div class="cs-sep" aria-hidden="true">:</div>
            <div class="cs-unit">
                <span class="cs-value" data-unit="seconds">00</span>
                <span class="cs-label">Seconds</span>
            </div>
        </div>

        <form class="cs-notify" action="{{ route('site.newsletter') }}" method="post">
            @csrf
            <label class="sr-only" for="cs-email">Email</label>
            <input
                id="cs-email"
                type="email"
                name="email"
                required
                maxlength="255"
                placeholder="Get launch updates"
                autocomplete="email"
            >
            <button type="submit">Notify me</button>
        </form>
        @if (session('newsletter_ok'))
            <p class="cs-thanks" role="status">You’re on the list. We’ll ping you at launch.</p>
        @endif

        <p class="cs-foot">© {{ now()->year }} Lens · Find. Book. Create.</p>
    </main>

    <script src="{{ asset('js/lens-coming-soon.js') }}?v={{ @filemtime(public_path('js/lens-coming-soon.js')) }}"></script>
</body>
</html>
