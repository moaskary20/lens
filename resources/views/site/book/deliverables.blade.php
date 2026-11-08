@extends('site.layout', ['title' => 'Deliverables', 'active' => 'bookings'])

@section('content')
<section class="book-flow wrap" data-reveal>
    <div class="page-head">
        <h1>Deliverables</h1>
        <p class="lede">{{ $booking->project_name }} · {{ $booking->reference }}</p>
    </div>

    <div class="inbox-list">
        @forelse((array) $deliverables as $file)
            <div class="inbox-item">
                <div>
                    <strong>{{ is_array($file) ? ($file['name'] ?? 'File') : 'File' }}</strong>
                    <p class="lede" style="margin:4px 0 0">v{{ is_array($file) ? ($file['version'] ?? 1) : 1 }}</p>
                </div>
                @if(is_array($file) && !empty($file['url']))
                    <a class="ghost-btn" href="{{ $file['url'] }}" style="margin:0" target="_blank" rel="noopener">Download</a>
                @endif
            </div>
        @empty
            <div class="bookings-empty"><strong>No files yet</strong><p>Deliverables appear when the creator uploads them.</p></div>
        @endforelse
    </div>

    @if(($authUser->isVendor() ?? false) && (int) ($authUser->vendor?->id) === (int) $booking->vendor_id)
        <div class="site-shell-card delivery-drop" style="margin-top:18px">
            <h3 style="margin-top:0">Upload delivery</h3>
            <form method="post" action="{{ route('site.bookings.deliver', $booking) }}" enctype="multipart/form-data" class="site-shell-form">
                @csrf
                <input type="file" name="files[]" multiple required>
                <button class="ai-btn" type="submit">Upload</button>
            </form>
        </div>
    @else
        <div class="site-shell-card" style="margin-top:18px">
            <div class="cta-row" style="margin:0 0 14px">
                <form method="post" action="{{ route('site.bookings.approve', $booking) }}">@csrf<button class="ai-btn" type="submit">Approve delivery</button></form>
                <form method="post" action="{{ route('site.bookings.requestEdit', $booking) }}">
                    @csrf
                    <input type="hidden" name="change" value="Please revise the delivered files.">
                    <button class="ghost-btn" type="submit" style="margin:0">Request edit</button>
                </form>
            </div>
            <form method="post" action="{{ route('site.bookings.refuse', $booking) }}" class="site-shell-form">
                @csrf
                <label for="refuse-reason">Refuse delivery</label>
                <textarea id="refuse-reason" name="reason" rows="3" required maxlength="2000" placeholder="Why are you refusing this delivery?"></textarea>
                <button class="ghost-btn" type="submit" style="margin:0;color:#ff3b30;border-color:#ff3b30">Refuse delivery</button>
            </form>
        </div>
    @endif
</section>
@endsection
