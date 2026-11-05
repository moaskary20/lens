@extends('site.layout', ['title' => 'Payment methods', 'active' => 'account'])

@section('content')
<section class="account-page wrap" data-reveal>
    <div class="page-head"><h1>Payment methods</h1></div>
    <div class="inbox-list" style="margin-top:16px">
        @forelse((array) $methods as $method)
            <div class="inbox-item">
                <div>
                    <strong>{{ $method['label'] ?? $method['brand'] ?? 'Method' }}</strong>
                    <p class="lede" style="margin:4px 0 0">{{ $method['type'] ?? '' }}</p>
                </div>
                <form method="post" action="{{ route('site.account.payments.destroy', $method['id']) }}">
                    @csrf @method('DELETE')
                    <button class="ghost-btn" type="submit" style="margin:0">Remove</button>
                </form>
            </div>
        @empty
            <div class="bookings-empty"><strong>No saved methods</strong><p>Add a card or wallet for faster checkout.</p></div>
        @endforelse
    </div>
    <div class="site-shell-card" style="margin-top:18px;max-width:560px" x-data="{ type: 'card' }">
        <h3 style="margin-top:0">Add payment method</h3>
        <form method="post" action="{{ route('site.account.payments.store') }}" class="site-shell-form">
            @csrf
            <label><span>Type</span>
                <select name="type" x-model="type" required>
                    <option value="card">Card</option>
                    <option value="wallet">Wallet</option>
                    <option value="paypal">PayPal</option>
                </select>
            </label>
            <div x-show="type === 'card'" class="site-shell-form">
                <label><span>Card holder</span><input name="details[card_holder]"></label>
                <label><span>Last 4</span><input name="details[card_last4]" maxlength="4"></label>
                <label><span>Expiry</span><input name="details[card_expiry]" placeholder="MM/YY"></label>
            </div>
            <div x-show="type === 'wallet'" class="site-shell-form">
                <label><span>Telecom</span><input name="details[wallet_telecom]" placeholder="Vodafone Cash"></label>
                <label><span>Phone</span><input name="details[wallet_phone]"></label>
            </div>
            <div x-show="type === 'paypal'" class="site-shell-form">
                <label><span>PayPal email</span><input name="details[paypal_email]" type="email"></label>
                <label><span>Name</span><input name="details[paypal_name]"></label>
            </div>
            @foreach($errors->all() as $error)<em class="bookings-error">{{ $error }}</em>@endforeach
            <button class="ai-btn" type="submit">Save</button>
        </form>
    </div>
</section>
@endsection
