@extends('site.layout', ['title' => 'Wallet', 'active' => 'account'])

@section('content')
<section class="account-page wrap" data-reveal>
    <div class="page-head"><h1>Wallet</h1><p class="lede">Balances and recent activity.</p></div>
    <div class="site-shell-card" style="margin-top:16px">
        <div class="vp-stats" style="border:0;padding:0;margin:0">
            <div><strong>{{ number_format((float) ($wallet['available'] ?? $wallet['balance'] ?? 0), 0) }}</strong><span>Available</span></div>
            <div><strong>{{ number_format((float) ($wallet['pending'] ?? 0), 0) }}</strong><span>Pending</span></div>
            <div><strong>{{ number_format((float) ($wallet['coupon_credit'] ?? $wallet['held'] ?? 0), 0) }}</strong><span>Credit</span></div>
            <div><strong>{{ $wallet['currency'] ?? 'EGP' }}</strong><span>Currency</span></div>
        </div>
    </div>
    <div class="inbox-list" style="margin-top:18px">
        @forelse(($wallet['transactions'] ?? []) as $tx)
            <div class="inbox-item">
                <div>
                    <strong>{{ $tx['label'] ?? $tx['type'] ?? 'Transaction' }}</strong>
                    <p class="lede" style="margin:4px 0 0">{{ $tx['created_at'] ?? $tx['when'] ?? '' }}</p>
                </div>
                <strong>{{ $tx['amount_label'] ?? ($tx['amount'] ?? '') }}</strong>
            </div>
        @empty
            <div class="bookings-empty"><strong>No transactions yet</strong><p>Wallet activity appears after bookings and payouts.</p></div>
        @endforelse
    </div>
</section>
@endsection
