@extends('site.layout', ['title' => 'Addresses', 'active' => 'account'])

@section('content')
<section class="account-page wrap" data-reveal>
    <div class="page-head"><h1>Addresses</h1></div>
    <div class="inbox-list" style="margin-top:16px">
        @forelse((array) $addresses as $address)
            <div class="inbox-item">
                <div>
                    <strong>{{ $address['label'] ?? 'Address' }}</strong>
                    <p class="lede" style="margin:4px 0 0">{{ $address['line'] ?? $address['line1'] ?? '' }} {{ $address['city'] ?? '' }}</p>
                </div>
                <form method="post" action="{{ route('site.account.addresses.destroy', $address['id']) }}">
                    @csrf @method('DELETE')
                    <button class="ghost-btn" type="submit" style="margin:0">Remove</button>
                </form>
            </div>
        @empty
            <div class="bookings-empty"><strong>No addresses</strong><p>Save shoot locations for faster booking.</p></div>
        @endforelse
    </div>
    <div class="site-shell-card" style="margin-top:18px;max-width:560px">
        <h3 style="margin-top:0">Add address</h3>
        <form method="post" action="{{ route('site.account.addresses.store') }}" class="site-shell-form">
            @csrf
            <label><span>Label</span><input name="label" value="{{ old('label') }}" placeholder="Home / Studio"></label>
            <label><span>Address line</span><input name="line" value="{{ old('line') }}" required></label>
            <label><span>City</span><input name="city" value="{{ old('city') }}" placeholder="Cairo"></label>
            @foreach($errors->all() as $error)<em class="bookings-error">{{ $error }}</em>@endforeach
            <button class="ai-btn" type="submit">Save</button>
        </form>
    </div>
</section>
@endsection
