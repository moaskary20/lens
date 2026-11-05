@extends('site.layout', ['title' => 'Help & Support', 'active' => $active ?? 'home'])

@section('content')
@php
    $user = $user ?? null;
    $topics = $topics ?? \App\Models\IssueReport::TOPICS;
    $guides = $guides ?? [];
@endphp
<section class="page-head wrap">
    <h1>Help &amp; Support</h1>
    <p class="lede">Answers for clients and creators — the same topics from the Lens app.</p>
</section>

<section class="wrap block help-guides">
    <div class="block-head"><h2>Quick guides</h2></div>
    <div class="help-guide-grid">
        @foreach($guides as $guide)
            <article class="help-guide-card" id="guide-{{ $guide['key'] }}">
                <p class="kicker">{{ $topics[$guide['key']] ?? 'Help' }}</p>
                <h3>{{ $guide['title'] }}</h3>
                <p>{{ $guide['body'] }}</p>
            </article>
        @endforeach
    </div>
    <div class="help-links">
        <a class="ghost-btn" href="{{ route('site.page', 'faqs') }}">FAQs</a>
        <a class="ghost-btn" href="{{ route('site.page', 'contact') }}">Contact Us</a>
        <a class="ghost-btn" href="{{ route('site.page', 'terms') }}">Terms</a>
        <a class="ghost-btn" href="{{ route('site.page', 'privacy') }}">Privacy</a>
        <a class="ghost-btn" href="https://play.google.com/store/apps/details?id=app.lens.lens" target="_blank" rel="noopener noreferrer">Get the app</a>
    </div>
</section>

<section class="wrap block help-report" id="report">
    <div class="block-head"><h2>Report an issue</h2></div>
    <p class="lede">Pick a topic, tell us what happened, and Lens support will follow up — same flow as Report an Issue in the app.</p>

    @if(session('help_report_ok'))
        <div class="help-success">
            <strong>We received {{ is_string(session('help_report_ok')) ? session('help_report_ok') : 'your report' }}.</strong>
            <p>{{ session('status') ?: 'Lens support will review it and follow up.' }}</p>
        </div>
    @endif

    <form class="help-form" method="post" action="{{ route('site.help.report') }}" data-help-report>
        @csrf
        <input type="hidden" name="topic" value="{{ old('topic', 'other') }}" data-help-topic>
        <div class="help-topics" role="listbox" aria-label="Issue topic">
            @foreach($topics as $value => $label)
                <button
                    type="button"
                    class="help-topic {{ old('topic', 'other') === $value ? 'is-on' : '' }}"
                    data-topic="{{ $value }}"
                    aria-pressed="{{ old('topic', 'other') === $value ? 'true' : 'false' }}"
                >{{ $label }}</button>
            @endforeach
        </div>

        <label>
            <span>Subject</span>
            <input type="text" name="subject" value="{{ old('subject') }}" maxlength="120" required placeholder="Short summary">
        </label>
        <label>
            <span>Tell us what happened</span>
            <textarea name="body" rows="5" maxlength="2000" required placeholder="Steps, booking ref, or a screenshot description…">{{ old('body') }}</textarea>
        </label>
        <label data-help-booking {{ old('topic', 'other') === 'booking' ? '' : 'hidden' }}>
            <span>Booking reference (optional)</span>
            <input type="text" name="booking_reference" value="{{ old('booking_reference') }}" maxlength="40" placeholder="LN-1001">
        </label>

        @unless($user)
            <div class="help-guest-grid">
                <label>
                    <span>Name</span>
                    <input type="text" name="name" value="{{ old('name') }}" maxlength="80" required>
                </label>
                <label>
                    <span>Email</span>
                    <input type="email" name="email" value="{{ old('email') }}" maxlength="120" required>
                </label>
                <label>
                    <span>Phone (optional)</span>
                    <input type="text" name="phone" value="{{ old('phone') }}" maxlength="20">
                </label>
            </div>
        @else
            <p class="help-signed">Signed in as <strong>{{ $user->name }}</strong> · {{ $user->email }}</p>
        @endunless

        @foreach($errors->all() as $error)
            <em class="bookings-error">{{ $error }}</em>
        @endforeach

        <button class="ai-btn" type="submit">Send report</button>
    </form>
</section>
@endsection

@push('scripts')
<script>
(() => {
    const form = document.querySelector('[data-help-report]');
    if (!form) return;
    const hidden = form.querySelector('[data-help-topic]');
    const booking = form.querySelector('[data-help-booking]');
    form.querySelectorAll('[data-topic]').forEach((btn) => {
        btn.addEventListener('click', () => {
            form.querySelectorAll('[data-topic]').forEach((el) => {
                el.classList.toggle('is-on', el === btn);
                el.setAttribute('aria-pressed', el === btn ? 'true' : 'false');
            });
            hidden.value = btn.dataset.topic;
            if (booking) booking.hidden = btn.dataset.topic !== 'booking';
        });
    });
})();
</script>
@endpush
