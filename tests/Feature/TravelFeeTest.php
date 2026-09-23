<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\City;
use App\Models\User;
use App\Models\Vendor;
use App\Services\EscrowService;
use App\Support\Egypt;
use App\Support\Finance;
use App\Support\Travel;
use Database\Seeders\LensSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

class TravelFeeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(LensSeeder::class);
    }

    public function test_cities_are_the_twenty_seven_egyptian_governorates(): void
    {
        $names = City::query()->orderBy('sort_order')->pluck('name_en')->all();

        $this->assertCount(27, $names);
        $this->assertSame(Egypt::names(), $names);
        $this->assertTrue(City::query()->where('country', '!=', 'EG')->doesntExist());
    }

    public function test_same_governorate_has_no_travel_fee(): void
    {
        $vendor = $this->photoVendor();
        $cairo = City::query()->where('name_en', 'Cairo')->firstOrFail();

        $this->assertSame(0.0, Travel::fee($vendor, $cairo));
    }

    public function test_destination_governorate_uses_specific_transport_rate(): void
    {
        $vendor = $this->photoVendor();
        $alexandria = City::query()->where('name_en', 'Alexandria')->firstOrFail();

        $this->assertSame(450.0, Travel::fee($vendor, $alexandria));
    }

    public function test_unknown_governorate_falls_back_to_default_travel_fee(): void
    {
        $vendor = $this->photoVendor();
        $aswan = City::query()->where('name_en', 'Aswan')->firstOrFail();

        $this->assertSame(250.0, Travel::fee($vendor, $aswan));
    }

    public function test_vendor_who_does_not_travel_cannot_book_outside(): void
    {
        $vendor = Vendor::query()->where('display_name', 'Noor Cyclorama')->firstOrFail();
        $cairo = City::query()->where('name_en', 'Cairo')->firstOrFail();

        $this->expectException(LogicException::class);
        Travel::fee($vendor, $cairo);
    }

    public function test_quote_adds_travel_to_client_total_and_vendor_net(): void
    {
        $quote = Finance::quote(1800, 450);

        $this->assertEquals(450.0, $quote['travel_fee']);
        $this->assertEquals(225.0, $quote['client_fee']);
        $this->assertEquals(2475.0, $quote['total_paid']);
        $this->assertEquals(360.0, $quote['vendor_commission']);
        $this->assertEquals(1890.0, $quote['vendor_net']);
    }

    public function test_checkout_applies_travel_fee_when_city_is_outside(): void
    {
        $vendor = $this->photoVendor();
        $client = User::query()->where('email', 'client@lens.app')->firstOrFail();
        $alexandria = City::query()->where('name_en', 'Alexandria')->firstOrFail();

        $booking = Booking::query()->create([
            'reference' => 'LN-TRAVEL-1',
            'client_id' => $client->id,
            'vendor_id' => $vendor->id,
            'city_id' => $alexandria->id,
            'status' => 'accepted',
            'session_price' => 1800,
            'escrow_status' => 'none',
            'payout_status' => 'none',
        ]);

        app(EscrowService::class)->checkout($booking);
        $booking->refresh();

        $this->assertEquals(450.0, (float) $booking->travel_fee);
        $this->assertEquals(2475.0, (float) $booking->total_paid);
        $this->assertEquals(1890.0, (float) $booking->vendor_net);
        $this->assertEquals(2475.0, (float) $booking->escrowTransactions()->where('type', 'hold')->sum('amount'));
    }

    protected function photoVendor(): Vendor
    {
        return Vendor::query()
            ->whereHas('vendorType', fn ($query) => $query->where('slug', 'photographer'))
            ->firstOrFail();
    }
}
