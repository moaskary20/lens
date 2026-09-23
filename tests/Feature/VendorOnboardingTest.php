<?php

namespace Tests\Feature;

use App\Models\Portfolio;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorType;
use App\Support\VendorProfile;
use Database\Seeders\LensSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VendorOnboardingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(LensSeeder::class);
    }

    public function test_photographer_edit_shows_gear_and_previous_projects(): void
    {
        $admin = User::query()->where('email', 'admin@lens.app')->firstOrFail();
        $vendor = Vendor::query()->where('display_name', 'Fahad Studio Light')->firstOrFail();

        $this->actingAs($admin)
            ->get('/admin/vendors/'.$vendor->id.'/edit')
            ->assertOk()
            ->assertSee('Cameras')
            ->assertSee('Lenses')
            ->assertSee('Lighting / strobes')
            ->assertSee('Primary specialties')
            ->assertSee('Previous projects')
            ->assertSee('Yasmin Hall wedding')
            ->assertSee('Half-day price (6 hours)')
            ->assertSee('Full-day price (12 hours)');
    }

    public function test_studio_edit_shows_rooms_props_and_hourly_calendar(): void
    {
        $admin = User::query()->where('email', 'admin@lens.app')->firstOrFail();
        $vendor = Vendor::query()->where('display_name', 'Noor Cyclorama')->firstOrFail();

        $this->actingAs($admin)
            ->get('/admin/vendors/'.$vendor->id.'/edit')
            ->assertOk()
            ->assertSee('Room / space inventory')
            ->assertSee('Available props')
            ->assertSee('Hourly slot booking calendar')
            ->assertSee('Studio gallery')
            ->assertSee('Price per hour per location');
    }

    public function test_each_vendor_type_loads_its_profile_fields(): void
    {
        $admin = User::query()->where('email', 'admin@lens.app')->firstOrFail();
        $cityId = Vendor::query()->where('display_name', 'Fahad Studio Light')->value('city_id');

        $cases = [
            'videographer' => ['Camera kit', 'Half-day price (6 hours)', 'Full-day price (12 hours)'],
            'reels' => ['Mobile devices used', 'Half-day price (6 hours)', 'Full-day price (12 hours)'],
            'model' => ['Height (cm)', 'Half-day price (6 hours)', 'Full-day price (12 hours)'],
            'ugc' => ['Short-form UGC samples', 'Price per video'],
            'food_stylist' => ['Add-on services', 'Half-day price (6 hours)', 'Full-day price (12 hours)'],
        ];

        foreach ($cases as $slug => $needles) {
            $type = VendorType::query()->where('slug', $slug)->firstOrFail();
            $user = User::query()->create([
                'name' => $slug.' owner',
                'email' => $slug.'@lens.test',
                'password' => 'password',
                'role' => 'vendor',
                'is_active' => true,
            ]);
            $vendor = Vendor::query()->create([
                'user_id' => $user->id,
                'vendor_type_id' => $type->id,
                'city_id' => $cityId,
                'display_name' => ucfirst($slug).' profile',
                'verification_status' => 'pending',
                'is_active' => true,
            ]);

            $response = $this->actingAs($admin)->get('/admin/vendors/'.$vendor->id.'/edit');
            $response->assertOk();

            foreach ($needles as $needle) {
                $response->assertSee($needle);
            }
        }
    }

    public function test_vendor_can_store_type_extras_and_previous_projects(): void
    {
        $type = VendorType::query()->where('slug', 'photographer')->firstOrFail();
        $user = User::query()->create([
            'name' => 'New shooter',
            'email' => 'newshooter@lens.test',
            'password' => 'password',
            'role' => 'vendor',
            'is_active' => true,
        ]);

        $vendor = Vendor::query()->create([
            'user_id' => $user->id,
            'vendor_type_id' => $type->id,
            'display_name' => 'Desert Light',
            'specialties' => ['wedding', 'corporate'],
            'extras' => [
                'cameras' => ['Sony A7 IV'],
                'lenses' => ['35mm f/1.4'],
                'lighting' => ['Profoto B10'],
            ],
        ]);

        $vendor->portfolios()->create([
            'type' => 'image',
            'path' => 'portfolios/desert-light.jpg',
            'title' => 'AlUla campaign',
            'description' => 'Editorial portraits in sandstone.',
            'completed_on' => '2025',
            'is_featured' => true,
        ]);

        $vendor->refresh();

        $this->assertEqualsCanonicalizing(['Sony A7 IV', '35mm f/1.4', 'Profoto B10'], $vendor->equipment);
        $this->assertEqualsCanonicalizing(['wedding', 'corporate'], $vendor->specialties);
        $this->assertSame(['Sony A7 IV'], $vendor->extras['cameras']);
        $this->assertTrue(Portfolio::query()->where('vendor_id', $vendor->id)->where('title', 'AlUla campaign')->exists());
    }

    public function test_vendor_profile_helpers_and_create_page_render(): void
    {
        $this->assertContains('wedding', array_keys(VendorProfile::specialtyOptions()));
        $this->assertArrayHasKey('cyclorama', VendorProfile::studioRooms());
        $this->assertArrayHasKey('recipe_development', VendorProfile::foodAddons());

        $photographer = VendorType::query()->where('slug', 'photographer')->firstOrFail();
        $this->assertSame('photographer', VendorProfile::slugFromId($photographer->id));

        $admin = User::query()->where('email', 'admin@lens.app')->firstOrFail();
        $this->actingAs($admin)
            ->get('/admin/vendors/create')
            ->assertOk()
            ->assertSee('Vendor type')
            ->assertSee('Previous projects')
            ->assertSee('Type profile');
    }
}
