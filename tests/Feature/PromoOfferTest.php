<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Coupon;
use App\Models\User;
use App\Models\Vendor;
use App\Services\EscrowService;
use App\Services\PromoService;
use Database\Seeders\LensSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PromoOfferTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(LensSeeder::class);
        $this->actingAs(User::query()->where('email', 'admin@lens.app')->firstOrFail());
    }

    public function test_admin_can_create_each_offer_campaign(): void
    {
        $this->get('/admin/coupons')
            ->assertOk()
            ->assertSee('WELCOME200')
            ->assertSee('SUMMER15')
            ->assertSee('PHOTO10')
            ->assertSee('FAHAD100')
            ->assertSee('FIRSTSHOOT')
            ->assertSee('LOYAL10');

        $this->get('/admin/coupons/create')
            ->assertOk()
            ->assertSee('Discount coupon')
            ->assertSee('Seasonal offer')
            ->assertSee('Specific clients')
            ->assertSee('Specific vendor')
            ->assertSee('Service type')
            ->assertSee('First order')
            ->assertSee('Loyal clients');
    }

    public function test_plain_checkout_does_not_auto_apply_coded_seasonal_or_service_offers(): void
    {
        $booking = $this->promoBooking();
        app(EscrowService::class)->checkout($booking);

        $booking->refresh();
        $this->assertEquals(1980.0, (float) $booking->total_paid);
        $this->assertEquals(0.0, (float) $booking->discount_amount);
        $this->assertEquals(1440.0, (float) $booking->vendor_net);
        $this->assertNull($booking->coupon_id);
    }

    public function test_percent_coupon_reduces_client_total_and_leaves_vendor_net(): void
    {
        $booking = $this->promoBooking();
        $coupon = Coupon::query()->where('code', 'SUMMER15')->firstOrFail();
        $booking->update(['coupon_id' => $coupon->id]);

        app(EscrowService::class)->checkout($booking->fresh());

        $booking->refresh();
        $this->assertEquals(270.0, (float) $booking->discount_amount);
        $this->assertEquals(1710.0, (float) $booking->total_paid);
        $this->assertEquals(1440.0, (float) $booking->vendor_net);
        $this->assertSame($coupon->id, $booking->coupon_id);
        $this->assertSame(1, (int) $coupon->fresh()->uses_count);
    }

    public function test_specific_client_offer_is_rejected_for_someone_else(): void
    {
        $other = User::query()->create([
            'name' => 'Other client',
            'email' => 'other-promo@lens.app',
            'password' => 'password',
            'role' => 'client',
            'is_active' => true,
        ]);
        $coupon = Coupon::query()->where('code', 'WELCOME200')->firstOrFail();

        $this->assertFalse($coupon->appliesTo($other));
        $this->assertTrue($coupon->appliesTo(User::query()->where('email', 'client@lens.app')->firstOrFail()));
    }

    public function test_vendor_offer_only_applies_to_that_vendor(): void
    {
        $coupon = Coupon::query()->where('code', 'FAHAD100')->firstOrFail();
        $photo = $this->promoBooking();
        $studio = $this->promoBooking(vendorSlug: 'studio');

        $photoQuote = app(PromoService::class)->quoteBooking($photo, $coupon);
        $studioQuote = app(PromoService::class)->quoteBooking($studio, $coupon);

        $this->assertEquals(100.0, (float) $photoQuote['discount_amount']);
        $this->assertEquals(0.0, (float) $studioQuote['discount_amount']);
    }

    public function test_service_offer_matches_vendor_type(): void
    {
        $coupon = Coupon::query()->where('code', 'PHOTO10')->firstOrFail();
        $photo = $this->promoBooking();
        $studio = $this->promoBooking(vendorSlug: 'studio');

        $this->assertEquals(180.0, (float) app(PromoService::class)->quoteBooking($photo, $coupon)['discount_amount']);
        $this->assertEquals(0.0, (float) app(PromoService::class)->quoteBooking($studio, $coupon)['discount_amount']);
    }

    public function test_seasonal_offer_outside_the_window_does_not_apply(): void
    {
        $coupon = Coupon::query()->where('code', 'SUMMER15')->firstOrFail();
        $coupon->update(['starts_at' => now()->addWeek(), 'expires_at' => now()->addMonths(2)]);

        $quote = app(PromoService::class)->quoteBooking($this->promoBooking(), $coupon->fresh());
        $this->assertEquals(0.0, (float) $quote['discount_amount']);
    }

    public function test_first_order_offer_auto_applies_for_a_new_client(): void
    {
        $client = User::query()->create([
            'name' => 'New shooter',
            'email' => 'first-promo@lens.app',
            'password' => 'password',
            'role' => 'client',
            'is_active' => true,
        ]);
        $booking = $this->promoBooking(client: $client);

        app(EscrowService::class)->checkout($booking);

        $booking->refresh();
        $this->assertEquals(200.0, (float) $booking->discount_amount);
        $this->assertEquals(1780.0, (float) $booking->total_paid);
        $this->assertEquals('FIRSTSHOOT', $booking->coupon?->code);
        $this->assertEquals(1440.0, (float) $booking->vendor_net);
    }

    public function test_loyal_offer_auto_applies_after_completed_sessions(): void
    {
        $client = User::query()->create([
            'name' => 'Repeat client',
            'email' => 'loyal-promo@lens.app',
            'password' => 'password',
            'role' => 'client',
            'is_active' => true,
        ]);
        $vendor = Vendor::query()->whereHas('vendorType', fn ($query) => $query->where('slug', 'photographer'))->firstOrFail();

        foreach ([1, 2] as $i) {
            Booking::query()->create([
                'reference' => 'LN-LOYAL-'.$i,
                'client_id' => $client->id,
                'vendor_id' => $vendor->id,
                'status' => 'approved',
                'scheduled_at' => now()->subMonths($i),
                'session_price' => 1800,
                'escrow_status' => 'released',
                'payout_status' => 'paid',
            ]);
        }

        $booking = $this->promoBooking(client: $client);
        app(EscrowService::class)->checkout($booking);

        $booking->refresh();
        $this->assertEquals(180.0, (float) $booking->discount_amount);
        $this->assertEquals(1800.0, (float) $booking->total_paid);
        $this->assertEquals('LOYAL10', $booking->coupon?->code);
    }

    protected function promoBooking(?User $client = null, string $vendorSlug = 'photographer'): Booking
    {
        $vendor = Vendor::query()->whereHas('vendorType', fn ($query) => $query->where('slug', $vendorSlug))->firstOrFail();
        $client ??= User::query()->where('email', 'client@lens.app')->firstOrFail();
        $price = $vendorSlug === 'studio' ? 1000 : 1800;

        return Booking::query()->create([
            'reference' => 'LN-PROMO-'.uniqid(),
            'client_id' => $client->id,
            'vendor_id' => $vendor->id,
            'status' => 'accepted',
            'scheduled_at' => now()->addDays(5),
            'session_price' => $price,
            'escrow_status' => 'none',
            'payout_status' => 'none',
        ]);
    }
}
