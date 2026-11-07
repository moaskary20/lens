@extends('site.layout', ['title' => 'Report an issue', 'active' => 'account'])

@section('content')
<section class="acc-sub wrap" data-reveal>
    @include('site.account.partials.sub-hero', [
        'title' => 'Report an issue',
        'lede' => 'Tell Lens about a booking problem, bug, or safety concern.',
        'image' => asset('branding/account/report.svg'),
        'icon' => 'flag',
        'tips' => ['We review every report', 'Include booking details', 'Usually reply within 24h'],
    ])

    <div class="acc-sub-body">
        <div class="acc-sub-card">
            <header class="acc-sub-card-head">
                <span class="acc-sub-card-ico" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M5 21V4m0 0h10l-2 4 2 4H5"/></svg>
                </span>
                <div>
                    <h2>Send a report</h2>
                    <p>Be specific — screenshots and booking IDs help.</p>
                </div>
            </header>
            <form method="post" action="{{ route('site.account.report.store') }}" class="acc-sub-form">
                @csrf
                <label>
                    <span class="acc-sub-label"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M8 7h8M8 12h8M8 17h5"/></svg> Topic</span>
                    <select name="topic" required>
                        @foreach(\App\Models\IssueReport::TOPICS as $value => $label)
                            <option value="{{ $value }}" @selected(old('topic') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label>
                    <span class="acc-sub-label"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M5 7h14M5 12h10M5 17h7"/></svg> Subject</span>
                    <input name="subject" value="{{ old('subject') }}" required placeholder="Short summary">
                </label>
                <label>
                    <span class="acc-sub-label"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M5 6.5A2.5 2.5 0 0 1 7.5 4h9A2.5 2.5 0 0 1 19 6.5v6A2.5 2.5 0 0 1 16.5 15H11l-4 3v-3H7.5A2.5 2.5 0 0 1 5 12.5z"/></svg> Details</span>
                    <textarea name="body" required placeholder="What happened? Include booking or chat context if relevant.">{{ old('body') }}</textarea>
                </label>
                @foreach($errors->all() as $error)<em class="bookings-error">{{ $error }}</em>@endforeach
                <button class="ai-btn" type="submit">Send report</button>
            </form>
        </div>
    </div>
</section>
@endsection
