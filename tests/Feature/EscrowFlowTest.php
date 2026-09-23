<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Deliverable;
use App\Models\Setting;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorType;
use App\Services\DeliveryService;
use App\Services\EscrowService;
use App\Support\Finance;
use Database\Seeders\LensSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

class EscrowFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(LensSeeder::class);
    }

    public function test_quote_matches_spec_fees(): void
    {
        $quote = Finance::quote(1800);

        $this->assertSame(10.0, $quote['client_fee_percent']);
        $this->assertSame(20.0, $quote['vendor_commission_percent']);
        $this->assertEquals(180.0, $quote['client_fee']);
        $this->assertEquals(0.0, $quote['tax_amount']);
        $this->assertEquals(1980.0, $quote['total_paid']);
        $this->assertEquals(360.0, $quote['vendor_commission']);
        $this->assertEquals(1440.0, $quote['vendor_net']);
        $this->assertSame('EGP', Finance::currency());
    }

    public function test_checkout_holds_one_hundred_percent(): void
    {
        $booking = $this->photographerBooking();

        app(EscrowService::class)->checkout($booking);

        $booking->refresh();
        $this->assertSame('held', $booking->escrow_status);
        $this->assertEquals(1980.0, (float) $booking->total_paid);
        $this->assertEquals(1980.0, (float) $booking->escrowTransactions()->where('type', 'hold')->sum('amount'));
        $this->assertSame('none', $booking->payout_status);
        $this->assertSame(0, $booking->payouts()->count());
    }

    public function test_photographer_check_in_does_not_release(): void
    {
        $booking = $this->photographerBooking();
        $escrow = app(EscrowService::class);
        $escrow->checkout($booking);
        $escrow->checkIn($booking->fresh());

        $booking->refresh();
        $this->assertSame('checked_in', $booking->status);
        $this->assertSame('held', $booking->escrow_status);
        $this->assertFalse($escrow->releasesOnCheckIn($booking));
    }

    public function test_studio_check_in_releases_immediately(): void
    {
        $booking = $this->studioBooking();
        $escrow = app(EscrowService::class);
        $escrow->checkout($booking);
        $escrow->checkIn($booking->fresh());

        $booking->refresh();
        $this->assertSame('checked_in', $booking->status);
        $this->assertSame('released', $booking->escrow_status);
        $this->assertSame('pending', $booking->payout_status);
        $this->assertEquals(800.0, (float) $booking->payouts()->sum('amount'));
        $this->assertTrue($escrow->releasesOnCheckIn($booking));
    }

    public function test_revision_keeps_photographer_funds_on_hold(): void
    {
        $booking = $this->photographerBooking();
        $escrow = app(EscrowService::class);
        $escrow->checkout($booking);
        $escrow->checkIn($booking->fresh());
        $escrow->markDelivered($booking->fresh());
        $escrow->requestRevision($booking->fresh());

        $booking->refresh();
        $this->assertSame('in_revision', $booking->status);
        $this->assertSame('held', $booking->escrow_status);
        $this->assertSame(1, $booking->revision_count);
        $this->assertSame(0, $booking->payouts()->count());
    }

    public function test_photographer_approve_requires_deliverables_then_releases_net(): void
    {
        $booking = $this->photographerBooking();
        $escrow = app(EscrowService::class);
        $escrow->checkout($booking);
        $escrow->markDelivered($booking->fresh());

        try {
            $escrow->approve($booking->fresh());
            $this->fail('Approval without deliverables should fail.');
        } catch (LogicException $exception) {
            $this->assertStringContainsString('Deliverables', $exception->getMessage());
        }

        Deliverable::query()->create([
            'booking_id' => $booking->id,
            'path' => 'deliverables/preview.jpg',
            'original_name' => 'preview.jpg',
            'is_watermarked' => true,
            'is_unlocked' => false,
            'version' => 1,
        ]);

        $escrow->approve($booking->fresh());
        $booking->refresh();

        $this->assertSame('approved', $booking->status);
        $this->assertSame('released', $booking->escrow_status);
        $this->assertEquals(1440.0, (float) $booking->payouts()->sum('amount'));
        $this->assertTrue((bool) $booking->deliverables()->first()?->is_unlocked);
    }

    public function test_checkout_never_pays_the_vendor(): void
    {
        $booking = $this->photographerBooking();
        app(EscrowService::class)->checkout($booking);
        $booking->refresh();

        $this->assertEquals(180.0, (float) $booking->client_fee);
        $this->assertEquals(1980.0, (float) $booking->total_paid);
        $this->assertEquals(360.0, (float) $booking->vendor_commission);
        $this->assertEquals(1440.0, (float) $booking->vendor_net);
        $this->assertSame(0, $booking->escrowTransactions()->whereIn('type', ['release', 'fee'])->count());
        $this->assertSame(0, $booking->payouts()->count());
        $this->assertSame('none', $booking->payout_status);
    }

    public function test_checkout_hold_includes_taxes(): void
    {
        Setting::setGroupValues('finance', [
            ...Finance::settings(),
            'tax_percent' => 14,
        ]);

        $quote = Finance::quote(1800);
        $this->assertEquals(180.0, $quote['client_fee']);
        $this->assertEquals(277.2, $quote['tax_amount']);
        $this->assertEquals(2257.2, $quote['total_paid']);

        $booking = $this->photographerBooking();
        $booking->update(['reference' => 'LN-TEST-TAX']);
        app(EscrowService::class)->checkout($booking->fresh());

        $this->assertEquals(2257.2, (float) $booking->fresh()->total_paid);
        $this->assertEquals(2257.2, (float) $booking->fresh()->escrowTransactions()->where('type', 'hold')->sum('amount'));
        $this->assertSame(0, $booking->fresh()->payouts()->count());
    }

    public function test_model_check_in_releases_like_studio(): void
    {
        $booking = $this->bookingForType('model', 1000);
        $escrow = app(EscrowService::class);
        $escrow->checkout($booking);
        $this->assertSame(0, $booking->fresh()->payouts()->count());

        $escrow->checkIn($booking->fresh());
        $booking->refresh();

        $this->assertTrue($escrow->releasesOnCheckIn($booking));
        $this->assertSame('released', $booking->escrow_status);
        $this->assertEquals(200.0, (float) $booking->vendor_commission);
        $this->assertEquals(800.0, (float) $booking->payouts()->sum('amount'));
    }

    public function test_videographer_stays_on_hold_until_deliverables_and_approve(): void
    {
        $booking = $this->bookingForType('videographer', 1800);
        $escrow = app(EscrowService::class);
        $delivery = app(DeliveryService::class);

        $escrow->checkout($booking);
        $escrow->checkIn($booking->fresh());
        $booking->refresh();

        $this->assertFalse($escrow->releasesOnCheckIn($booking));
        $this->assertSame('held', $booking->escrow_status);
        $this->assertSame(0, $booking->payouts()->count());

        $delivery->upload($booking->fresh(), 'deliverables/cut.mp4', 'cut.mp4');
        $escrow->requestRevision($booking->fresh());
        $this->assertSame('held', $booking->fresh()->escrow_status);

        $delivery->upload($booking->fresh(), 'deliverables/cut-v2.mp4', 'cut-v2.mp4');
        $delivery->approve($booking->fresh());
        $booking->refresh();

        $this->assertSame('approved', $booking->status);
        $this->assertSame('released', $booking->escrow_status);
        $this->assertEquals(1440.0, (float) $booking->payouts()->sum('amount'));
    }

    protected function photographerBooking(): Booking
    {
        $vendor = Vendor::query()->whereHas('vendorType', fn ($query) => $query->where('slug', 'photographer'))->firstOrFail();
        $client = User::query()->where('email', 'client@lens.app')->firstOrFail();

        return Booking::query()->create([
            'reference' => 'LN-TEST-PHOTO',
            'client_id' => $client->id,
            'vendor_id' => $vendor->id,
            'status' => 'accepted',
            'session_price' => 1800,
            'escrow_status' => 'none',
            'payout_status' => 'none',
        ]);
    }

    protected function studioBooking(): Booking
    {
        $vendor = Vendor::query()->whereHas('vendorType', fn ($query) => $query->where('slug', 'studio'))->firstOrFail();
        $client = User::query()->where('email', 'client@lens.app')->firstOrFail();

        return Booking::query()->create([
            'reference' => 'LN-TEST-STUDIO',
            'client_id' => $client->id,
            'vendor_id' => $vendor->id,
            'status' => 'accepted',
            'session_price' => 1000,
            'escrow_status' => 'none',
            'payout_status' => 'none',
        ]);
    }

    protected function bookingForType(string $slug, float $sessionPrice): Booking
    {
        $type = VendorType::query()->where('slug', $slug)->firstOrFail();
        $client = User::query()->where('email', 'client@lens.app')->firstOrFail();
        $user = User::query()->create([
            'name' => $slug.' talent',
            'email' => $slug.'-escrow@lens.app',
            'password' => 'password',
            'role' => 'vendor',
            'is_active' => true,
        ]);
        $vendor = Vendor::query()->create([
            'user_id' => $user->id,
            'vendor_type_id' => $type->id,
            'display_name' => $type->name_en.' escrow test',
            'verification_status' => 'verified',
            'is_active' => true,
        ]);

        return Booking::query()->create([
            'reference' => 'LN-TEST-'.strtoupper($slug),
            'client_id' => $client->id,
            'vendor_id' => $vendor->id,
            'status' => 'accepted',
            'session_price' => $sessionPrice,
            'escrow_status' => 'none',
            'payout_status' => 'none',
        ]);
    }
}
