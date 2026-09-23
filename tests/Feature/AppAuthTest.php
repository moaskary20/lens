<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vendor;
use Database\Seeders\LensSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AppAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(LensSeeder::class);
    }

    public function test_client_and_vendor_can_sign_in_and_see_their_bookings(): void
    {
        $this->postJson('/api/app/auth/login', [
            'email' => 'client@lens.app',
            'password' => 'password',
        ])->assertOk()
            ->assertJsonPath('role', 'client')
            ->assertJsonPath('email', 'client@lens.app');

        $clientBookings = $this->withHeaders(['X-Lens-Client' => 'client@lens.app'])
            ->getJson('/api/app/bookings')
            ->assertOk()
            ->assertJsonPath('role', 'client');
        $this->assertNotEmpty($clientBookings->json('bookings'));
        $this->assertEquals('Fahad Studio Light', $clientBookings->json('bookings.0.counterpart'));
        $this->assertContains($clientBookings->json('bookings.0.group'), ['upcoming', 'completed', 'canceled']);

        $this->postJson('/api/app/auth/login', [
            'email' => 'vendor@lens.app',
            'password' => 'password',
        ])->assertOk()->assertJsonPath('role', 'vendor');

        $vendorBookings = $this->withHeaders(['X-Lens-Client' => 'vendor@lens.app'])
            ->getJson('/api/app/bookings')
            ->assertOk()
            ->assertJsonPath('role', 'vendor');
        $this->assertNotEmpty($vendorBookings->json('bookings'));
        $this->assertEquals('Sarah Bennett', $vendorBookings->json('bookings.0.counterpart'));
    }

    public function test_guest_can_register_as_client_or_vendor(): void
    {
        $this->postJson('/api/app/auth/register', [
            'name' => 'Nour Adel',
            'email' => 'nour@lens.app',
            'password' => 'password',
            'role' => 'client',
            'phone' => '01012345678',
        ])->assertCreated()->assertJsonPath('role', 'client');

        $this->assertDatabaseHas('users', ['email' => 'nour@lens.app', 'role' => 'client']);

        $this->postJson('/api/app/auth/register', [
            'name' => 'Cairo Motion',
            'email' => 'cairo.motion@lens.app',
            'password' => 'password',
            'role' => 'vendor',
            'phone' => '01112345678',
            'vendor_type' => 'videographer',
        ])->assertCreated()->assertJsonPath('role', 'vendor');

        $user = User::query()->where('email', 'cairo.motion@lens.app')->firstOrFail();
        $this->assertTrue(Vendor::query()->where('user_id', $user->id)->exists());
    }

    public function test_register_stores_admin_profile_and_payout_fields(): void
    {
        $cairo = \App\Models\City::query()->where('name_en', 'Cairo')->firstOrFail();

        $this->postJson('/api/app/auth/register', [
            'name' => 'Mona Client',
            'email' => 'mona.client@lens.app',
            'password' => 'password',
            'role' => 'client',
            'phone' => '01599998877',
            'locale' => 'ar',
            'city_id' => $cairo->id,
        ])->assertCreated()->assertJsonPath('role', 'client');

        $this->assertDatabaseHas('users', [
            'email' => 'mona.client@lens.app',
            'phone' => '01599998877',
            'locale' => 'ar',
            'city_id' => $cairo->id,
        ]);

        $this->postJson('/api/app/auth/register', [
            'name' => 'Yasmin Lens',
            'email' => 'yasmin.vendor@lens.app',
            'password' => 'password',
            'role' => 'vendor',
            'phone' => '01588887766',
            'city_id' => $cairo->id,
            'vendor_type' => 'photographer',
            'display_name' => 'Yasmin Studio',
            'profession' => 'Wedding photographer',
            'specialties' => ['wedding', 'editorial'],
            'filter_tags' => ['full-frame', 'portrait-lenses'],
            'extras' => [
                'cameras' => ['Sony A7 IV'],
                'lenses' => ['35mm f/1.4'],
            ],
            'half_day_price' => 1800,
            'full_day_price' => 3200,
            'projects' => [
                [
                    'type' => 'link',
                    'title' => 'Sahel campaign',
                    'completed_on' => '2025',
                    'external_url' => 'https://instagram.com/p/sahel',
                    'description' => 'Editorial portraits.',
                    'is_featured' => true,
                ],
            ],
            'payout_method' => 'wallet',
            'wallet_network_type' => 'telecom',
            'wallet_telecom' => 'vodafone',
            'wallet_phone' => '01588887766',
        ])->assertCreated()->assertJsonPath('role', 'vendor');

        $vendor = Vendor::query()->where('display_name', 'Yasmin Studio')->firstOrFail();
        $this->assertSame('pending', $vendor->verification_status);
        $this->assertSame('wallet', $vendor->payout_method);
        $this->assertSame('vodafone', $vendor->wallet_telecom);
        $this->assertSame('01588887766', $vendor->wallet_phone);
        $this->assertEqualsCanonicalizing(['wedding', 'editorial'], $vendor->specialties);
        $this->assertEqualsCanonicalizing(['full-frame', 'portrait-lenses'], $vendor->filterTags()->pluck('slug')->all());
        $this->assertSame(['Sony A7 IV'], $vendor->extras['cameras']);
        $this->assertEquals(1800.0, (float) $vendor->half_day_price);
        $this->assertTrue($vendor->portfolios()->where('title', 'Sahel campaign')->exists());
    }

    public function test_vendor_register_stores_a_photo_gallery(): void
    {
        Storage::fake('public');

        $this->post('/api/app/auth/register', [
            'name' => 'Gallery Vendor',
            'email' => 'gallery.vendor@lens.app',
            'password' => 'password',
            'role' => 'vendor',
            'phone' => '01298765432',
            'vendor_type' => 'photographer',
            'display_name' => 'Gallery Light',
            'half_day_price' => 1500,
            'full_day_price' => 2800,
            'projects' => [
                [
                    'type' => 'image',
                    'title' => 'Sahel gallery',
                    'files' => [
                        UploadedFile::fake()->image('one.jpg'),
                        UploadedFile::fake()->image('two.jpg'),
                    ],
                ],
            ],
        ])->assertCreated();

        $vendor = Vendor::query()->where('display_name', 'Gallery Light')->firstOrFail();
        $this->assertSame(2, $vendor->portfolios()->count());
        $this->assertTrue($vendor->portfolios()->where('title', 'Sahel gallery')->where('type', 'image')->exists());
        foreach ($vendor->portfolios as $item) {
            $this->assertNotSame('', $item->path);
            Storage::disk('public')->assertExists($item->path);
        }
    }

    public function test_register_rejects_non_egyptian_mobile_numbers(): void
    {
        $this->postJson('/api/app/auth/register', [
            'name' => 'Bad Phone',
            'email' => 'bad.phone@lens.app',
            'password' => 'password',
            'role' => 'client',
            'phone' => '01912345678',
        ])->assertStatus(422)->assertJsonValidationErrors('phone');

        $this->postJson('/api/app/auth/register', [
            'name' => 'Short Phone',
            'email' => 'short.phone@lens.app',
            'password' => 'password',
            'role' => 'vendor',
            'phone' => '0101234567',
            'vendor_type' => 'photographer',
        ])->assertStatus(422)->assertJsonValidationErrors('phone');
    }

    public function test_staff_cannot_sign_in_on_the_app(): void
    {
        $this->postJson('/api/app/auth/login', [
            'email' => 'admin@lens.app',
            'password' => 'password',
        ])->assertStatus(422);
    }
}
