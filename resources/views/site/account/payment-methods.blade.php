@extends('site.layout', ['title' => 'Payment methods', 'active' => 'account'])

@section('content')
<section class="acc-sub wrap" data-reveal>
    @include('site.account.partials.sub-hero', [
        'title' => 'Payment methods',
        'lede' => 'Cards and wallets for faster checkout — synced with the app.',
        'image' => asset('branding/account/payments.svg'),
        'icon' => 'card',
        'tips' => ['Escrow-ready checkout', 'Remove anytime', 'Cards, wallets, PayPal'],
    ])

    <div class="acc-sub-body">
        <div class="acc-sub-stack">
            <div class="acc-sub-card">
                <header class="acc-sub-card-head">
                    <span class="acc-sub-card-ico" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="6" width="18" height="12" rx="2.5"/><path d="M3 10h18M7 15h4"/></svg>
                    </span>
                    <div>
                        <h2>Saved methods</h2>
                        <p>Used when you book and pay through Lens.</p>
                    </div>
                </header>
                <div class="acc-sub-list">
                    @forelse((array) $methods as $method)
                        <div class="acc-sub-row">
                            <span class="acc-sub-row-ico" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="6" width="18" height="12" rx="2.5"/><path d="M3 10h18"/></svg>
                            </span>
                            <div class="acc-sub-row-copy">
                                <strong>{{ $method['label'] ?? $method['brand'] ?? 'Method' }}</strong>
                                <small>{{ $method['type'] ?? '' }}</small>
                            </div>
                            <form method="post" action="{{ route('site.account.payments.destroy', $method['id']) }}">
                                @csrf @method('DELETE')
                                <button class="ghost-btn acc-sub-remove" type="submit">Remove</button>
                            </form>
                        </div>
                    @empty
                        <div class="acc-sub-empty">
                            <strong>No saved methods</strong>
                            <p>Add a card or wallet for faster checkout.</p>
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="acc-sub-card" x-data="{ type: 'card' }">
                <header class="acc-sub-card-head">
                    <span class="acc-sub-card-ico" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 5v14M5 12h14"/></svg>
                    </span>
                    <div>
                        <h2>Add payment method</h2>
                        <p>Choose card, mobile wallet, or PayPal.</p>
                    </div>
                </header>
                <form method="post" action="{{ route('site.account.payments.store') }}" class="acc-sub-form">
                    @csrf
                    <label>
                        <span class="acc-sub-label">Type</span>
                        <select name="type" x-model="type" required>
                            <option value="card">Card</option>
                            <option value="wallet">Wallet</option>
                            <option value="paypal">PayPal</option>
                        </select>
                    </label>
                    <div x-show="type === 'card'" class="acc-sub-grid">
                        <label class="acc-sub-span-2"><span class="acc-sub-label">Card holder</span><input name="details[card_holder]"></label>
                        <label><span class="acc-sub-label">Last 4</span><input name="details[card_last4]" maxlength="4" inputmode="numeric"></label>
                        <label><span class="acc-sub-label">Expiry</span><input name="details[card_expiry]" placeholder="MM/YY"></label>
                    </div>
                    <div x-show="type === 'wallet'" class="acc-sub-grid">
                        <label><span class="acc-sub-label">Telecom</span><input name="details[wallet_telecom]" placeholder="Vodafone Cash"></label>
                        <label><span class="acc-sub-label">Phone</span><input name="details[wallet_phone]"></label>
                    </div>
                    <div x-show="type === 'paypal'" class="acc-sub-grid">
                        <label><span class="acc-sub-label">PayPal email</span><input name="details[paypal_email]" type="email"></label>
                        <label><span class="acc-sub-label">Name</span><input name="details[paypal_name]"></label>
                    </div>
                    @foreach($errors->all() as $error)<em class="bookings-error">{{ $error }}</em>@endforeach
                    <button class="ai-btn" type="submit">Save method</button>
                </form>
            </div>
        </div>
    </div>
</section>
@endsection
