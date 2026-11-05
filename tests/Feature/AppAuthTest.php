<?php

namespace Tests\Feature;

use App\Models\City;
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

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function vendorRegisterPayload(array $overrides = []): array
    {
        $cairo = City::query()->where('name_en', 'Cairo')->firstOrFail();

        return array_merge([
            'name' => 'Cairo Motion',
            'email' => 'cairo.motion@lens.app',
            'password' => 'Password1!',
            'role' => 'vendor',
            'phone' => '01112345678',
            'city_id' => $cairo->id,
            'vendor_type' => 'videographer',
            'display_name' => 'Cairo Motion',
            'date_of_birth' => '1994-06-12',
            'profession' => 'Videographer',
            'years_experience' => 6,
            'bio' => 'Commercial and wedding films.',
            'contact_email' => 'cairo.motion@lens.app',
            'whatsapp' => '01112345678',
            'instagram' => 'https://instagram.com/cairomotion',
            'instagram_followers' => 12000,
            'tiktok_url' => 'https://tiktok.com/@cairomotion',
            'tiktok_followers' => 8000,
            'facebook_url' => 'https://facebook.com/cairomotion',
            'facebook_followers' => 5000,
            'address' => 'Zamalek, Cairo',
            'latitude' => 30.0626,
            'longitude' => 31.2197,
            'payout_method' => 'wallet',
            'wallet_network_type' => 'telecom',
            'wallet_telecom' => 'vodafone',
            'wallet_phone' => '01112345678',
            'half_day_price' => 1800,
            'full_day_price' => 3200,
            'profile_photo' => UploadedFile::fake()->image('photo.jpg'),
            'national_id_image' => UploadedFile::fake()->image('nid.jpg'),
            'cover_image' => UploadedFile::fake()->image('cover.jpg'),
        ], $overrides);
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
        $this->assertSame('01022223344', $clientBookings->json('bookings.0.phone'));
        $this->assertSame('01022223344', $clientBookings->json('bookings.0.whatsapp'));
        $this->assertContains($clientBookings->json('bookings.0.group'), ['upcoming', 'completed', 'canceled']);
        $this->assertIsArray($clientBookings->json('summary'));
        $this->assertEquals(
            count($clientBookings->json('bookings')),
            (int) $clientBookings->json('summary.upcoming')
            + (int) $clientBookings->json('summary.completed')
            + (int) $clientBookings->json('summary.canceled'),
        );

        $vendor = Vendor::query()->where('display_name', 'Fahad Studio Light')->firstOrFail();
        $vendor->update(['contact_phone' => null, 'whatsapp' => null]);
        $this->withHeaders(['X-Lens-Client' => 'client@lens.app'])
            ->getJson('/api/app/bookings')
            ->assertOk()
            ->assertJsonPath('bookings.0.phone', '01022223344')
            ->assertJsonPath('bookings.0.whatsapp', '01022223344');

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
        Storage::fake('public');
        $cairo = City::query()->where('name_en', 'Cairo')->firstOrFail();

        $this->postJson('/api/app/auth/register', [
            'name' => 'Nour Adel',
            'email' => 'nour@lens.app',
            'password' => 'Password1!',
            'role' => 'client',
            'phone' => '01012345678',
            'city_id' => $cairo->id,
        ])->assertCreated()->assertJsonPath('role', 'client');

        $this->assertDatabaseHas('users', ['email' => 'nour@lens.app', 'role' => 'client']);

        $this->post('/api/app/auth/register', $this->vendorRegisterPayload())
            ->assertCreated()
            ->assertJsonPath('role', 'vendor');

        $user = User::query()->where('email', 'cairo.motion@lens.app')->firstOrFail();
        $this->assertTrue(Vendor::query()->where('user_id', $user->id)->exists());
    }

    public function test_register_stores_admin_profile_and_payout_fields(): void
    {
        Storage::fake('public');
        $cairo = City::query()->where('name_en', 'Cairo')->firstOrFail();

        $this->postJson('/api/app/auth/register', [
            'name' => 'Mona Client',
            'email' => 'mona.client@lens.app',
            'password' => 'Password1!',
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

        $this->post('/api/app/auth/register', $this->vendorRegisterPayload([
            'name' => 'Yasmin Lens',
            'email' => 'yasmin.vendor@lens.app',
            'phone' => '01588887766',
            'whatsapp' => '01588887766',
            'wallet_phone' => '01588887766',
            'vendor_type' => 'photographer',
            'display_name' => 'Yasmin Studio',
            'profession' => 'Wedding photographer',
            'years_experience' => 5,
            'contact_email' => 'yasmin.vendor@lens.app',
            'instagram' => 'https://instagram.com/yasminstudio',
            'instagram_followers' => 128000,
            'tiktok_url' => 'https://tiktok.com/@yasminstudio',
            'tiktok_followers' => 86000,
            'facebook_url' => 'https://facebook.com/yasminstudio',
            'facebook_followers' => 24000,
            'specialties' => ['wedding', 'editorial'],
            'filter_tags' => ['full-frame', 'portrait-lenses'],
            'extras' => [
                'cameras' => ['Sony A7 IV'],
                'lenses' => ['35mm f/1.4'],
            ],
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
        ]))->assertCreated()->assertJsonPath('role', 'vendor');

        $vendor = Vendor::query()->where('display_name', 'Yasmin Studio')->firstOrFail();
        $this->assertSame(5, $vendor->years_experience);
        $this->assertSame('pending', $vendor->verification_status);
        $this->assertSame('wallet', $vendor->payout_method);
        $this->assertSame('vodafone', $vendor->wallet_telecom);
        $this->assertSame('01588887766', $vendor->wallet_phone);
        $this->assertSame('https://instagram.com/yasminstudio', $vendor->instagram);
        $this->assertSame(128000, $vendor->instagram_followers);
        $this->assertSame('https://tiktok.com/@yasminstudio', $vendor->tiktok_url);
        $this->assertSame(86000, $vendor->tiktok_followers);
        $this->assertSame('https://facebook.com/yasminstudio', $vendor->facebook_url);
        $this->assertSame(24000, $vendor->facebook_followers);
        $this->assertEqualsCanonicalizing(['wedding', 'editorial'], $vendor->specialties);
        $this->assertEqualsCanonicalizing(['full-frame', 'portrait-lenses'], $vendor->filterTags()->pluck('slug')->all());
        $this->assertSame(['Sony A7 IV'], $vendor->extras['cameras']);
        $this->assertEquals(1800.0, (float) $vendor->half_day_price);
        $this->assertTrue($vendor->portfolios()->where('title', 'Sahel campaign')->exists());
    }

    public function test_vendor_register_stores_a_photo_gallery(): void
    {
        Storage::fake('public');

        $this->post('/api/app/auth/register', $this->vendorRegisterPayload([
            'name' => 'Gallery Vendor',
            'email' => 'gallery.vendor@lens.app',
            'phone' => '01298765432',
            'whatsapp' => '01298765432',
            'wallet_phone' => '01298765432',
            'vendor_type' => 'photographer',
            'display_name' => 'Gallery Light',
            'contact_email' => 'gallery.vendor@lens.app',
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
        ]))->assertCreated();

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
        $cairo = City::query()->where('name_en', 'Cairo')->firstOrFail();

        $this->postJson('/api/app/auth/register', [
            'name' => 'Bad Phone',
            'email' => 'bad.phone@lens.app',
            'password' => 'Password1!',
            'role' => 'client',
            'phone' => '01912345678',
            'city_id' => $cairo->id,
        ])->assertStatus(422)->assertJsonValidationErrors('phone');

        $this->postJson('/api/app/auth/register', [
            'name' => 'Short Phone',
            'email' => 'short.phone@lens.app',
            'password' => 'Password1!',
            'role' => 'vendor',
            'phone' => '0101234567',
            'city_id' => $cairo->id,
            'vendor_type' => 'photographer',
        ])->assertStatus(422)->assertJsonValidationErrors('phone');
    }

    public function test_staff_can_sign_in_on_the_app_as_a_client(): void
    {
        $this->postJson('/api/app/auth/login', [
            'email' => 'admin@lens.app',
            'password' => 'password',
        ])->assertOk()
            ->assertJsonPath('email', 'admin@lens.app')
            ->assertJsonPath('role', 'client');

        $this->withHeaders(['X-Lens-Client' => 'admin@lens.app'])
            ->getJson('/api/app/bookings')
            ->assertOk()
            ->assertJsonPath('role', 'client');
    }
}
