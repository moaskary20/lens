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

    public function test_sync_vendor_pricing_aligns_vendors_to_models(): void
    {
        $studio = Vendor::query()->where('display_name', 'Noor Cyclorama')->firstOrFail();
        $studio->update([
            'half_day_price' => 999,
            'full_day_price' => 1999,
            'hourly_price' => null,
            'extras' => [],
        ]);

        $this->artisan('lens:sync-vendor-pricing')->assertSuccessful();

        $studio->refresh();
        $this->assertNull($studio->half_day_price);
        $this->assertNull($studio->full_day_price);
        $this->assertNotNull($studio->hourly_price);
        $this->assertGreaterThan(0, (float) $studio->hourly_price);
        $this->assertSame(
            (int) VendorType::query()->where('slug', 'studio')->value('pricing_model_id'),
            (int) data_get($studio->extras, 'pricing_model_id'),
        );
        $this->assertNotEmpty($studio->bookablePackages());
        $this->assertSame(['hourly'], collect($studio->bookablePackages())->pluck('key')->all());
    }

    public function test_sync_vendor_pricing_does_not_clear_type_expected_prices_when_model_mismatch(): void
    {
        $photographerType = VendorType::query()->where('slug', 'photographer')->firstOrFail();
        $halfFullModelId = (int) PricingModel::query()->where('slug', 'half_full_day')->value('id');

        $wrongModel = PricingModel::query()->create([
            'slug' => 'per_min_test',
            'name_en' => 'Per minute test',
            'is_active' => true,
            'sort_order' => 99,
        ]);
        $wrongModel->fields()->create([
            'key' => 'custom',
            'storage_key' => 'voice',
            'label' => 'Voice rate',
            'package_type' => 'per min',
            'unit' => 'minute',
            'duration_hours' => null,
            'helper_text' => 'Test-only custom field',
            'sort_order' => 0,
        ]);

        $photographerType->update(['pricing_model_id' => $wrongModel->id]);

        $vendor = Vendor::query()->where('display_name', 'Fahad Studio Light')->firstOrFail();
        $vendor->update([
            'half_day_price' => 2100,
            'full_day_price' => 3600,
            'hourly_price' => null,
        ]);

        $this->artisan('lens:sync-vendor-pricing')->assertSuccessful();

        $vendor->refresh();
        $photographerType->refresh();

        $this->assertEquals(2100.0, (float) $vendor->half_day_price);
        $this->assertEquals(3600.0, (float) $vendor->full_day_price);
        $this->assertNotEmpty($vendor->bookablePackages());
        $this->assertSame($halfFullModelId, (int) $photographerType->pricing_model_id);
    }
}
