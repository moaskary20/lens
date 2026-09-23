<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Coupon;
use App\Models\Payout;
use App\Models\User;
use App\Models\Vendor;
use App\Services\CancellationService;
use App\Services\DeliveryService;
use App\Services\EscrowService;
use App\Services\WalletService;
use Database\Seeders\LensSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalletFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(LensSeeder::class);
    }

    public function test_each_user_has_a_wallet_and_admin_pages_render(): void
    {
        $this->assertTrue(User::query()->where('email', 'client@lens.app')->firstOrFail()->wallet()->exists());
        $this->assertTrue(User::query()->where('email', 'vendor@lens.app')->firstOrFail()->wallet()->exists());

        $admin = User::query()->where('email', 'admin@lens.app')->firstOrFail();
        $this->actingAs($admin)->get('/admin/wallets')->assertOk()->assertSee('Available / balance');
        $this->actingAs($admin)->get('/admin/coupons')->assertOk()->assertSee('WELCOME200');
    }

    public function test_checkout_holds_vendor_pending_and_records_client_payment(): void
    {
        $booking = $this->photographerSession();
        $client = $booking->client;
        $vendorUser = $booking->vendor->user;

        app(EscrowService::class)->checkout($booking);

        $clientWallet = $client->fresh()->wallet;
        $vendorWallet = $vendorUser->fresh()->wallet;

        $this->assertEquals(1980.0, (float) $clientWallet->lifetime_paid);
        $this->assertEquals(0.0, (float) $vendorWallet->available);
        $this->assertEquals(1440.0, (float) $vendorWallet->pending);
        $this->assertEquals(0.0, (float) $vendorWallet->lifetime_earned);
        $this->assertSame(0, $booking->fresh()->payouts()->count());
    }

    public function test_client_pays_from_balance_and_coupon_credit(): void
    {
        $booking = $this->photographerSession();
        $client = $booking->client;
        $wallets = app(WalletService::class);
        $wallets->topUp($client, 1880, 'Test balance');

        $coupon = Coupon::query()->create([
            'code' => 'SHOOT100',
            'label' => 'Shoot credit',
            'campaign' => Coupon::CAMPAIGN_COUPON,
            'type' => Coupon::TYPE_WALLET,
            'amount' => 100,
            'max_uses' => 1,
            'is_active' => true,
        ]);
        $wallets->redeemCoupon($client, $coupon);

        app(EscrowService::class)->checkout($booking);

        $wallet = $client->fresh()->wallet;
        $this->assertEquals(0.0, (float) $wallet->available);
        $this->assertEquals(0.0, (float) $wallet->coupon_credit);
        $this->assertEquals(1980.0, (float) $wallet->lifetime_paid);
        $this->assertTrue($wallet->transactions()->where('type', 'payment')->where('booking_id', $booking->id)->exists());
    }

    public function test_approve_moves_pending_to_available_and_records_commission(): void
    {
        $booking = $this->photographerSession();
        $escrow = app(EscrowService::class);
        $delivery = app(DeliveryService::class);
        $escrow->checkout($booking);
        $delivery->upload($booking->fresh(), 'deliverables/wallet.jpg', 'wallet.jpg');
        $delivery->approve($booking->fresh());

        $wallet = $booking->vendor->user->fresh()->wallet;

        $this->assertEquals(0.0, (float) $wallet->pending);
        $this->assertEquals(1440.0, (float) $wallet->available);
        $this->assertEquals(1440.0, (float) $wallet->lifetime_earned);
        $this->assertEquals(360.0, (float) $wallet->lifetime_commission);
    }

    public function test_paid_payout_withdraws_available_earnings(): void
    {
        $booking = $this->photographerSession();
        $escrow = app(EscrowService::class);
        $delivery = app(DeliveryService::class);
        $escrow->checkout($booking);
        $delivery->upload($booking->fresh(), 'deliverables/wallet.jpg', 'wallet.jpg');
        $delivery->approve($booking->fresh());

        $payout = Payout::query()->where('booking_id', $booking->id)->firstOrFail();
        $payout->update(['status' => 'paid', 'paid_at' => now()]);

        $wallet = $booking->vendor->user->fresh()->wallet;
        $this->assertEquals(0.0, (float) $wallet->available);
        $this->assertEquals(1440.0, (float) $wallet->lifetime_withdrawn);
    }

    public function test_cancellation_refunds_the_client_wallet_and_clears_vendor_hold(): void
    {
        $booking = $this->photographerSession(now()->addDays(10));
        app(EscrowService::class)->checkout($booking);
        app(CancellationService::class)->cancel($booking->fresh(), 'client', 'Plans changed');

        $clientWallet = $booking->client->fresh()->wallet;
        $vendorWallet = $booking->vendor->user->fresh()->wallet;

        $this->assertGreaterThan(0, (float) $clientWallet->lifetime_refunded);
        $this->assertGreaterThan(0, (float) $clientWallet->available);
        $this->assertEquals(0.0, (float) $vendorWallet->pending);
    }

    public function test_vendor_can_open_wallet_ledger(): void
    {
        $vendor = User::query()->where('email', 'vendor@lens.app')->firstOrFail();

        $this->actingAs($vendor)
            ->get('/vendor/wallet')
            ->assertOk()
            ->assertSee('Total earnings')
            ->assertSee('Available')
            ->assertSee('Pending')
            ->assertSee('Withdrawn')
            ->assertSee('Commissions');
    }

    protected function photographerSession(?\DateTimeInterface $scheduledAt = null): Booking
    {
        $vendor = Vendor::query()->whereHas('vendorType', fn ($query) => $query->where('slug', 'photographer'))->firstOrFail();
        $client = User::query()->where('email', 'client@lens.app')->firstOrFail();

        return Booking::query()->create([
            'reference' => 'LN-WALLET-'.uniqid(),
            'client_id' => $client->id,
            'vendor_id' => $vendor->id,
            'status' => 'accepted',
            'scheduled_at' => $scheduledAt ?? now()->addDays(4),
            'session_price' => 1800,
            'escrow_status' => 'none',
            'payout_status' => 'none',
        ]);
    }
}
