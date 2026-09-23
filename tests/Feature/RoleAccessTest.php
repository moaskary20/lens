<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\VendorAvailability;
use App\Models\VendorType;
use App\Support\Roles;
use Database\Seeders\LensSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(LensSeeder::class);
    }

    public function test_client_and_vendor_cannot_open_admin_panel(): void
    {
        $client = User::query()->where('email', 'client@lens.app')->firstOrFail();
        $vendor = User::query()->where('email', 'vendor@lens.app')->firstOrFail();

        $this->assertTrue($client->isClient());
        $this->assertTrue($client->roleCan('browse'));
        $this->assertTrue($client->roleCan('approve_payouts'));
        $this->assertTrue($vendor->isVendor());
        $this->assertTrue($vendor->roleCan('set_availability'));
        $this->assertTrue($vendor->roleCan('deliver_assets'));

        $this->actingAs($client)->get('/admin')->assertForbidden();
        $this->actingAs($client)->get('/vendor')->assertForbidden();

        $this->actingAs($vendor)->get('/admin')->assertForbidden();
        $this->actingAs($vendor)->get('/admin/vendors')->assertForbidden();
        $this->actingAs($vendor)->get('/vendor')
            ->assertOk()
            ->assertDontSee('/admin/vendors', false);
        $this->actingAs($vendor)->get('/vendor/requests')->assertOk();
        $this->actingAs($vendor)->get('/vendor/my-pricing')->assertOk();
        $this->actingAs($vendor)->get('/vendor/my-profile')->assertOk();
        $this->actingAs($vendor)->get('/vendor/portfolio')->assertOk();
        $this->actingAs($vendor)->get('/vendor/availability')->assertOk();
        $this->actingAs($vendor)->get('/vendor/travel-fees')->assertOk();
        $this->actingAs($vendor)->get('/vendor/earnings')->assertOk();
        $this->actingAs($vendor)->get('/vendor/wallet')->assertOk();
        $this->actingAs($vendor)->get('/vendor/ratings')->assertOk();
        $this->actingAs($vendor)->get('/vendor/messages')->assertOk();
        $this->actingAs($vendor)->get('/vendor/disputes')->assertOk();
    }

    public function test_vendor_only_sees_own_account(): void
    {
        $photographer = User::query()->where('email', 'vendor@lens.app')->firstOrFail();
        $studio = User::query()->where('email', 'studio@lens.app')->firstOrFail();
        $studioPortfolio = $studio->vendor->portfolios()->firstOrFail();
        $studioSlot = $studio->vendor->availabilities()->firstOrFail();

        $this->actingAs($photographer)->get('/vendor/my-profile')
            ->assertOk()
            ->assertSee('Fahad Studio Light')
            ->assertDontSee('Noor Cyclorama');

        $this->actingAs($photographer)->get('/vendor/portfolio')
            ->assertOk()
            ->assertSee('Yasmin Hall wedding')
            ->assertDontSee('Cyclorama wall');

        $this->actingAs($photographer)->get('/vendor/availability')
            ->assertOk()
            ->assertDontSee('Kitchen + cyclorama hourly block');

        $this->actingAs($photographer)->get('/vendor/portfolio/'.$studioPortfolio->id.'/edit')
            ->assertNotFound();
        $this->actingAs($photographer)->get('/vendor/availability/'.$studioSlot->id.'/edit')
            ->assertNotFound();
        $this->actingAs($photographer)->get('/admin/vendors/'.$studio->vendor->id.'/edit')
            ->assertForbidden();
    }

    public function test_supervisor_handles_ops_but_not_settings(): void
    {
        $supervisor = User::query()->where('email', 'supervisor@lens.app')->firstOrFail();

        $this->assertTrue($supervisor->isSupervisor());
        $this->assertTrue($supervisor->staffCan('verify_vendors'));
        $this->assertTrue($supervisor->staffCan('resolve_disputes'));
        $this->assertTrue($supervisor->staffCan('refund_overrides'));
        $this->assertTrue($supervisor->staffCan('policy_exceptions'));
        $this->assertFalse($supervisor->staffCan('manage_settings'));

        $this->actingAs($supervisor)->get('/admin')->assertOk();
        $this->actingAs($supervisor)->get('/admin/vendors')->assertOk();
        $this->actingAs($supervisor)->get('/admin/disputes')->assertOk();
        $this->actingAs($supervisor)->get('/admin/bookings')->assertOk();
        $this->actingAs($supervisor)->get('/admin/notification-log')->assertOk();
        $this->actingAs($supervisor)->get('/admin/feature-settings')->assertForbidden();
        $this->actingAs($supervisor)->get('/admin/notification-settings')->assertForbidden();
        $this->actingAs($supervisor)->get('/admin/financial-settings')->assertForbidden();
        $this->actingAs($supervisor)->get('/admin/role-settings')->assertForbidden();
        $this->actingAs($supervisor)->get('/admin/vendor-types')->assertForbidden();
        $this->actingAs($supervisor)->get('/admin/pricing-models')->assertForbidden();
    }

    public function test_all_seven_vendor_types_are_marketplace_ready(): void
    {
        $slugs = VendorType::query()->marketplace()->pluck('slug')->all();

        $this->assertEqualsCanonicalizing([
            'photographer',
            'videographer',
            'reels',
            'studio',
            'model',
            'ugc',
            'food_stylist',
        ], $slugs);

        $this->assertCount(7, Roles::enabledVendorTypeSlugs());
        $this->assertNotEmpty(Roles::blueprint()['roles']['client']['capabilities']);
    }

    public function test_vendor_calendar_slots_are_seeded_and_admin_page_renders(): void
    {
        $this->assertTrue(VendorAvailability::query()->where('status', 'open')->exists());

        $admin = User::query()->where('email', 'admin@lens.app')->firstOrFail();

        $this->actingAs($admin)->get('/admin/role-settings')->assertOk();
        $this->actingAs($admin)->get('/admin/vendor-availabilities')->assertOk();
    }
}
