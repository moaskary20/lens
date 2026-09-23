<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\PricingModel;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorType;
use App\Services\BookingWorkflow;
use App\Support\Pricing;
use Database\Seeders\LensSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VendorPricingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(LensSeeder::class);
    }

    public function test_default_models_match_vendor_types(): void
    {
        $this->assertTrue(PricingModel::query()->where('slug', 'half_full_day')->exists());
        $this->assertTrue(PricingModel::query()->where('slug', 'hourly')->exists());
        $this->assertTrue(PricingModel::query()->where('slug', 'per_video')->exists());

        $map = [
            'photographer' => 'half_full_day',
            'videographer' => 'half_full_day',
            'reels' => 'half_full_day',
            'studio' => 'hourly',
            'model' => 'half_full_day',
            'ugc' => 'per_video',
            'food_stylist' => 'half_full_day',
        ];

        foreach ($map as $slug => $model) {
            $type = VendorType::query()->where('slug', $slug)->with('pricingModel')->firstOrFail();
            $this->assertSame($model, $type->pricingModel?->slug);
        }
    }

    public function test_vendor_rate_fills_session_price_for_half_day(): void
    {
        $vendor = Vendor::query()->where('display_name', 'Fahad Studio Light')->firstOrFail();

        $this->assertEquals(1800.0, Pricing::sessionAmount($vendor, 'half_day'));
        $this->assertEquals(3200.0, Pricing::sessionAmount($vendor, 'full_day'));
    }

    public function test_studio_hourly_multiplies_by_hours(): void
    {
        $vendor = Vendor::query()->where('display_name', 'Noor Cyclorama')->firstOrFail();

        $this->assertEquals(250.0, Pricing::sessionAmount($vendor, 'hourly', 1));
        $this->assertEquals(1000.0, Pricing::sessionAmount($vendor, 'hourly', 4));
    }

    public function test_vendor_can_accept_and_reject_pending_requests(): void
    {
        $booking = Booking::query()->where('reference', 'LN-1003')->firstOrFail();
        $this->assertSame('pending', $booking->status);

        app(BookingWorkflow::class)->accept($booking);
        $this->assertSame('accepted', $booking->fresh()->status);

        $other = Booking::query()->create([
            'reference' => 'LN-1004',
            'client_id' => $booking->client_id,
            'vendor_id' => $booking->vendor_id,
            'status' => 'pending',
            'session_price' => 1800,
            'escrow_status' => 'none',
            'payout_status' => 'none',
        ]);

        app(BookingWorkflow::class)->reject($other);
        $this->assertSame('rejected', $other->fresh()->status);
    }

    public function test_admin_can_create_a_custom_pricing_model(): void
    {
        $model = PricingModel::query()->create([
            'slug' => 'weekend_pack',
            'name_en' => 'Weekend package',
            'is_active' => true,
        ]);
        $model->fields()->create([
            'key' => 'custom',
            'storage_key' => 'weekend',
            'label' => 'Weekend 8 hours',
            'package_type' => 'weekend',
            'unit' => 'session',
            'duration_hours' => 8,
        ]);

        $type = VendorType::query()->where('slug', 'photographer')->firstOrFail();
        $type->update(['pricing_model_id' => $model->id]);

        $vendor = Vendor::query()->where('display_name', 'Fahad Studio Light')->firstOrFail();
        $vendor->update(['extras' => array_merge($vendor->extras ?? [], ['prices' => ['weekend' => 4500]])]);

        $this->assertEquals(4500.0, Pricing::sessionAmount($vendor->fresh(), 'weekend'));

        $admin = User::query()->where('email', 'admin@lens.app')->firstOrFail();
        $this->actingAs($admin)->get('/admin/pricing-models/'.$model->id.'/edit')->assertOk()->assertSee('Weekend 8 hours');
    }

    public function test_pricing_model_form_requires_a_vendor_type(): void
    {
        $admin = User::query()->where('email', 'admin@lens.app')->firstOrFail();

        $this->actingAs($admin)
            ->get('/admin/pricing-models/create')
            ->assertOk()
            ->assertSee('Vendor type');
    }
}
