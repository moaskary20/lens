@extends('site.layout', ['title' => 'Addresses', 'active' => 'account'])

@section('content')
<section class="acc-sub wrap" data-reveal>
    @include('site.account.partials.sub-hero', [
        'title' => 'Addresses',
        'lede' => 'Save shoot locations you use often for quicker booking.',
        'image' => asset('branding/account/addresses.svg'),
        'icon' => 'pin',
        'tips' => ['Home or studio labels', 'Used at checkout', 'Remove anytime'],
    ])

    <div class="acc-sub-body">
        <div class="acc-sub-stack">
            <div class="acc-sub-card">
                <header class="acc-sub-card-head">
                    <span class="acc-sub-card-ico" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 21s6-5.2 6-10a6 6 0 1 0-12 0c0 4.8 6 10 6 10z"/><circle cx="12" cy="11" r="2.2"/></svg>
                    </span>
                    <div>
                        <h2>Saved addresses</h2>
                        <p>Pick a location when you book a shoot.</p>
                    </div>
                </header>
                <div class="acc-sub-list">
                    @forelse((array) $addresses as $address)
                        <div class="acc-sub-row">
                            <span class="acc-sub-row-ico" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 21s6-5.2 6-10a6 6 0 1 0-12 0c0 4.8 6 10 6 10z"/><circle cx="12" cy="11" r="2.2"/></svg>
                            </span>
                            <div class="acc-sub-row-copy">
                                <strong>{{ $address['label'] ?? 'Address' }}</strong>
                                <small>{{ $address['line'] ?? $address['line1'] ?? '' }} {{ $address['city'] ?? '' }}</small>
                            </div>
                            <form method="post" action="{{ route('site.account.addresses.destroy', $address['id']) }}">
                                @csrf @method('DELETE')
                                <button class="ghost-btn acc-sub-remove" type="submit">Remove</button>
                            </form>
                        </div>
                    @empty
                        <div class="acc-sub-empty">
                            <strong>No addresses yet</strong>
                            <p>Save shoot locations for faster booking.</p>
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="acc-sub-card">
                <header class="acc-sub-card-head">
                    <span class="acc-sub-card-ico" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 5v14M5 12h14"/></svg>
                    </span>
                    <div>
                        <h2>Add address</h2>
                        <p>Label it Home, Studio, or a client location.</p>
                    </div>
                </header>
                <form method="post" action="{{ route('site.account.addresses.store') }}" class="acc-sub-form">
                    @csrf
                    <div class="acc-sub-grid">
                        <label><span class="acc-sub-label">Label</span><input name="label" value="{{ old('label') }}" placeholder="Home / Studio"></label>
                        <label><span class="acc-sub-label">City</span><input name="city" value="{{ old('city') }}" placeholder="Cairo"></label>
                        <label class="acc-sub-span-2"><span class="acc-sub-label">Address line</span><input name="line" value="{{ old('line') }}" required></label>
                    </div>
                    @foreach($errors->all() as $error)<em class="bookings-error">{{ $error }}</em>@endforeach
                    <button class="ai-btn" type="submit">Save address</button>
                </form>
            </div>
        </div>
    </div>
</section>
@endsection
