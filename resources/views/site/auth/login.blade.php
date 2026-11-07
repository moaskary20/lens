@extends('site.layout', ['title' => 'Sign in', 'active' => 'account'])

@section('content')
<section class="auth-page wrap" data-reveal>
    <div class="site-shell-grid">
        <div class="site-shell-card">
            <h1 style="margin:0 0 8px">Welcome back</h1>
            <p class="lede">Sign in with the same Lens account you use in the app.</p>

            <div class="auth-tabs" style="margin-top:18px">
                <a class="is-active" href="{{ route('site.login') }}">Sign in</a>
                <a href="{{ route('site.join') }}">Create account</a>
            </div>

            <form method="post" action="{{ route('site.login.submit') }}" class="site-shell-form">
                @csrf
                <label>
                    <span>Email</span>
                    <input type="email" name="email" value="{{ old('email') }}" required autocomplete="email">
                </label>
                <label>
                    <span>Password</span>
                    <input type="password" name="password" required autocomplete="current-password">
                </label>
                @error('email') <em class="bookings-error">{{ $message }}</em> @enderror
                <button class="ai-btn" type="submit">Sign In</button>
            </form>

            <div style="margin:18px 0; text-align:center; color:var(--muted); font-weight:700">or</div>

            <div id="g_id_onload"
                 data-client_id="{{ $googleClientId }}"
                 data-callback="lensGoogleCallback"
                 data-auto_prompt="false"></div>
            <div class="g_id_signin" data-type="standard" data-size="large" data-theme="outline" data-text="continue_with" data-shape="pill" data-width="360"></div>
            <form id="google-form" method="post" action="{{ route('site.auth.google') }}" class="sr-only">
                @csrf
                <input type="hidden" name="id_token" id="google-id-token">
                <input type="hidden" name="role" value="client">
            </form>
            @error('id_token') <em class="bookings-error">{{ $message }}</em> @enderror
        </div>
        <aside class="site-shell-card">
            <h3 style="margin-top:0">Why Lens</h3>
            <ul style="color:var(--muted); line-height:1.7; padding-left:18px">
                <li>Protected escrow bookings</li>
                <li>Chat with creatives after you book</li>
                <li>Same account on web and mobile</li>
            </ul>
            <a class="ghost-btn" href="{{ route('site.vendors.join') }}" style="display:inline-flex;margin-top:18px">Join as a vendor →</a>
        </aside>
    </div>
</section>
@endsection

@push('head')
<script src="https://accounts.google.com/gsi/client" async defer></script>
@endpush

@push('scripts')
<script>
window.lensGoogleCallback = (response) => {
    const input = document.getElementById('google-id-token');
    const form = document.getElementById('google-form');
    if (!input || !form || !response?.credential) return;
    input.value = response.credential;
    form.submit();
};
</script>
@endpush
