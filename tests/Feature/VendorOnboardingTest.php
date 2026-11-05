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
            ->assertSee('Camera type')
            ->assertSee('Lenses available')
            ->assertSee('Lighting gear')
            ->assertSee('Primary specialties')
            ->assertSee('Previous projects')
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
            ->assertSee('Studio Type')
            ->assertSee('Features & equipment')
            ->assertSee('Studio sets & decoration')
            ->assertSee('Hourly slot booking calendar')
            ->assertSee('Studio gallery')
            ->assertSee('Price per hour per location');
    }

    public function test_each_vendor_type_loads_its_profile_fields(): void
    {
        $admin = User::query()->where('email', 'admin@lens.app')->firstOrFail();
        $cityId = Vendor::query()->where('display_name', 'Fahad Studio Light')->value('city_id');

        $cases = [
            'videographer' => ['Video quality & formats', 'Video gear', 'Delivery formats', 'Half-day price (6 hours)', 'Full-day price (12 hours)'],
            'reels' => ['Mobile reel setup', 'TikTok sample link', 'Half-day price (6 hours)', 'Full-day price (12 hours)'],
            'model' => ['Gender', 'Category', 'Height (cm)', 'Sizes (tops, waist, shoes)', 'Half-day price (6 hours)', 'Full-day price (12 hours)'],
            'ugc' => ['Category / Niche', 'Content type & capabilities', 'Short-form UGC samples', 'Price per video'],
            'food_stylist' => ['Expertise & styling', 'Add-on notes', 'Half-day price (6 hours)', 'Full-day price (12 hours)'],
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
                'camera_type' => 'Sony A7 IV',
                'lenses_available' => '35mm f/1.4',
                'lighting_gear' => 'Profoto B10',
                'photo_extras' => 'RAW + retouching',
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

        $this->assertContains('Camera type: Sony A7 IV', $vendor->equipment);
        $this->assertContains('Lenses available: 35mm f/1.4', $vendor->equipment);
        $this->assertContains('Lighting gear: Profoto B10', $vendor->equipment);
        $this->assertEqualsCanonicalizing(['wedding', 'corporate'], $vendor->specialties);
        $this->assertSame('Sony A7 IV', $vendor->extras['camera_type']);
        $this->assertSame(
            ['Camera type', 'Lenses available', 'Lighting gear', 'Photo extras'],
            collect(VendorProfile::presentFreeTextGear($vendor))->pluck('label')->all(),
        );
        $this->assertTrue(Portfolio::query()->where('vendor_id', $vendor->id)->where('title', 'AlUla campaign')->exists());
    }

    public function test_vendor_profile_helpers_and_create_page_render(): void
    {
        $this->assertContains('wedding', array_keys(VendorProfile::specialtyOptions()));
        $this->assertArrayHasKey('cyclorama', VendorProfile::studioRooms());
        $this->assertArrayHasKey('recipe_development', VendorProfile::foodAddons());
        $this->assertFalse(VendorProfile::filterProfileGroups('photographer')->contains('slug', 'camera'));
        $this->assertSame('Camera type', VendorProfile::FREE_TEXT_GEAR_FIELDS['camera_type']);
        $this->assertFalse(VendorProfile::filterProfileGroups('videographer')->contains('slug', 'video_gear'));
        $this->assertFalse(VendorProfile::filterProfileGroups('reels')->contains('slug', 'reels'));
        $this->assertFalse(VendorProfile::filterProfileGroups('studio')->contains('slug', 'studio_features'));
        $this->assertTrue(VendorProfile::filterProfileGroups('studio')->contains('slug', 'studio_type'));
        $this->assertFalse(VendorProfile::filterProfileGroups('photographer')->contains('slug', 'studio_type'));
        $this->assertFalse(VendorProfile::filterProfileGroups('studio')->contains('slug', 'studio_hourly'));
        $this->assertSame('Video gear', VendorProfile::freeTextGearFieldsFor('videographer')['video_gear']);
        $this->assertSame('Features & equipment', VendorProfile::freeTextGearFieldsFor('studio')['studio_features']);

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
