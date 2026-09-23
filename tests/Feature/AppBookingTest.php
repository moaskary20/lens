<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Vendor;
use Database\Seeders\LensSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppBookingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(LensSeeder::class);
    }

    public function test_client_can_book_with_specialty_price_and_map_pin(): void
    {
        $vendor = Vendor::query()->where('display_name', 'Fahad Studio Light')->firstOrFail();

        $this->withHeaders(['X-Lens-Client' => 'client@lens.app'])
            ->postJson('/api/app/bookings', [
                'vendor_id' => $vendor->id,
                'project_name' => 'Summer Menu Campaign',
                'project_type' => 'Food & drinks',
                'client_brief' => 'Lifestyle plates and close-ups of the new summer menu.',
                'location_text' => 'Zamalek, Cairo',
                'location_lat' => 30.0626,
                'location_lng' => 31.2197,
                'package_type' => 'half_day',
                'session_price' => 1800,
                'scheduled_at' => '2026-10-12 10:00:00',
                'duration_hours' => 6,
                'payment_method' => 'wallet',
                'payment_details' => [
                    'wallet_phone' => '01011112233',
                    'wallet_telecom' => 'Vodafone Cash',
                    'card_number' => '4242424242424242',
                    'cvv' => '123',
                ],
                'promo_code' => 'FAHAD100',
                'project_details' => [
                    'style' => 'Natural light',
                    'deliverables' => '50 edited photos',
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('project_name', 'Summer Menu Campaign')
            ->assertJsonPath('payment_method', 'wallet');

        $booking = Booking::query()->where('project_name', 'Summer Menu Campaign')->firstOrFail();
        $this->assertSame('Food & drinks', $booking->project_type);
        $this->assertSame('Zamalek, Cairo', $booking->location_text);
        $this->assertEquals(30.0626, (float) $booking->location_lat);
        $this->assertEquals(1800.0, (float) $booking->session_price);
        $this->assertSame('wallet', $booking->payment_method);
        $this->assertSame('Natural light', $booking->project_details['style']);
        $this->assertSame('01011112233', $booking->payment_details['wallet_phone']);
        $this->assertSame('Vodafone Cash', $booking->payment_details['wallet_telecom']);
        $this->assertArrayNotHasKey('card_number', $booking->payment_details);
        $this->assertArrayNotHasKey('cvv', $booking->payment_details);

        $this->actingAs(\App\Models\User::query()->where('email', 'admin@lens.app')->firstOrFail())
            ->get('/admin/bookings/'.$booking->id.'/edit')
            ->assertOk()
            ->assertSee('Summer Menu Campaign')
            ->assertSee('Food & drinks')
            ->assertSee('Zamalek, Cairo')
            ->assertSee('Natural light')
            ->assertSee('Mobile wallet')
            ->assertSee('Vodafone Cash')
            ->assertSee('01011112233');

        $this->withHeaders(['X-Lens-Client' => 'client@lens.app'])
            ->getJson('/api/app/vendors/'.$vendor->id)
            ->assertOk()
            ->assertJsonPath('contact_unlocked', true)
            ->assertJsonPath('contact_phone', $vendor->contact_phone);

        $locked = Vendor::query()->where('display_name', 'Dina Plates')->firstOrFail();
        $this->withHeaders(['X-Lens-Client' => 'client@lens.app'])
            ->getJson('/api/app/vendors/'.$locked->id)
            ->assertOk()
            ->assertJsonPath('contact_unlocked', false)
            ->assertJsonPath('contact_phone', null);
    }

    public function test_quote_rejects_unknown_promo_code(): void
    {
        $vendor = Vendor::query()->where('display_name', 'Fahad Studio Light')->firstOrFail();

        $this->withHeaders(['X-Lens-Client' => 'client@lens.app'])
            ->postJson('/api/app/bookings/quote', [
                'vendor_id' => $vendor->id,
                'session_price' => 1800,
                'promo_code' => 'NOT-A-CODE',
            ])
            ->assertStatus(422);
    }
}
