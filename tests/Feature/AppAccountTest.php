<?php

namespace Tests\Feature;

use App\Filament\Resources\UserResource\Pages\EditUser;
use App\Models\City;
use App\Models\User;
use App\Models\UserAddress;
use App\Models\UserPaymentMethod;
use App\Models\Vendor;
use Database\Seeders\LensSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class AppAccountTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(LensSeeder::class);
    }

    public function test_client_saves_payment_method_address_and_admin_sees_them(): void
    {
        $headers = ['X-Lens-Client' => 'client@lens.app'];

        $this->withHeaders($headers)
            ->postJson('/api/app/account/payment-methods', [
                'type' => 'card',
                'is_default' => true,
                'details' => [
                    'card_holder' => 'Sarah Bennett',
                    'card_number' => '4242424242424242',
                    'card_expiry' => '12/28',
                    'cvv' => '123',
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('type', 'card')
            ->assertJsonPath('details.card_last4', '4242')
            ->assertJsonMissingPath('details.card_number')
            ->assertJsonMissingPath('details.cvv');

        $this->withHeaders($headers)
            ->postJson('/api/app/account/addresses', [
                'label' => 'Home',
                'line' => 'Zamalek, Cairo',
                'city' => 'Cairo',
                'latitude' => 30.0626,
                'longitude' => 31.2197,
                'is_default' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('line', 'Zamalek, Cairo');

        $this->withHeaders($headers)
            ->postJson('/api/app/account/settings', [
                'settings' => ['language' => 'en', 'chat_alerts' => false],
            ])
            ->assertOk()
            ->assertJsonPath('settings.chat_alerts', false);

        $this->assertTrue(UserPaymentMethod::query()->where('label', 'like', '%4242%')->exists());
        $this->assertTrue(UserAddress::query()->where('line', 'Zamalek, Cairo')->exists());

        $client = User::query()->where('email', 'client@lens.app')->firstOrFail();
        $this->actingAs(User::query()->where('email', 'admin@lens.app')->firstOrFail())
            ->get('/admin/users/'.$client->id.'/edit')
            ->assertOk()
            ->assertSee('Payment methods')
            ->assertSee('Addresses')
            ->assertSee('App settings')
            ->assertSee('Push notifications')
            ->assertSee('Reduce motion');
    }

    public function test_admin_can_set_client_preferences_from_dropdowns(): void
    {
        $admin = User::query()->where('email', 'admin@lens.app')->firstOrFail();
        $client = User::query()->where('email', 'client@lens.app')->firstOrFail();

        $this->actingAs($admin);

        Livewire::test(EditUser::class, ['record' => $client->getRouteKey()])
            ->fillForm([
                'app_settings' => [
                    'push_notifications' => '0',
                    'email_offers' => '0',
                    'hide_activity' => '1',
                    'reduce_motion' => '1',
                    'language' => 'ar',
                ],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $client->refresh();
        $this->assertFalse((bool) $client->app_settings['push_notifications']);
        $this->assertFalse((bool) $client->app_settings['email_offers']);
        $this->assertTrue((bool) $client->app_settings['hide_activity']);
        $this->assertTrue((bool) $client->app_settings['reduce_motion']);
        $this->assertSame('ar', $client->app_settings['language'] ?? null);
        $this->assertSame('ar', $client->locale);

        $this->withHeaders(['X-Lens-Client' => 'client@lens.app'])
            ->getJson('/api/app/account/settings')
            ->assertOk()
            ->assertJsonPath('settings.push_notifications', false)
            ->assertJsonPath('settings.email_offers', false)
            ->assertJsonPath('settings.hide_activity', true)
            ->assertJsonPath('settings.reduce_motion', true)
            ->assertJsonPath('settings.language', 'ar');
    }

    public function test_client_projects_include_current_bookings(): void
    {
        $vendor = Vendor::query()->where('display_name', 'Fahad Studio Light')->firstOrFail();

        $projects = $this->withHeaders(['X-Lens-Client' => 'client@lens.app'])
            ->getJson('/api/app/account/projects')
            ->assertOk()
            ->assertJsonStructure(['projects' => [['title', 'phone', 'whatsapp', 'vendor']]])
            ->json('projects');

        $this->assertTrue(collect($projects)->contains(fn ($item) => (int) ($item['vendor']['id'] ?? 0) === (int) $vendor->id));
    }

    public function test_client_updates_profile_and_admin_sees_it(): void
    {
        Storage::fake('public');
        $giza = City::query()->where('name_en', 'Giza')->firstOrFail();
        $headers = ['X-Lens-Client' => 'client@lens.app', 'Accept' => 'application/json'];

        $this->withHeaders($headers)
            ->getJson('/api/app/account/profile')
            ->assertOk()
            ->assertJsonPath('email', 'client@lens.app')
            ->assertJsonPath('phone', '01011112233')
            ->assertJsonPath('role_label', 'Client');

        $this->withHeaders($headers)
            ->post('/api/app/account/profile', [
                'name' => 'Nora Adel',
                'email' => 'client@lens.app',
                'phone' => '01511112233',
                'locale' => 'ar',
                'city_id' => $giza->id,
                'avatar' => UploadedFile::fake()->image('avatar.jpg'),
            ])
            ->assertOk()
            ->assertJsonPath('name', 'Nora Adel')
            ->assertJsonPath('phone', '01511112233')
            ->assertJsonPath('locale', 'ar')
            ->assertJsonPath('city', 'Giza');

        $client = User::query()->where('email', 'client@lens.app')->firstOrFail();
        $this->assertNotNull($client->avatar);
        $this->assertSame('ar', $client->locale);
        $this->assertSame((int) $giza->id, (int) $client->city_id);
        $this->assertSame('ar', $client->app_settings['language'] ?? null);

        $this->actingAs(User::query()->where('email', 'admin@lens.app')->firstOrFail())
            ->get('/admin/users/'.$client->id.'/edit')
            ->assertOk()
            ->assertSee('Nora Adel')
            ->assertSee('01511112233');
    }

    public function test_client_needs_current_password_to_change_it(): void
    {
        $headers = ['X-Lens-Client' => 'client@lens.app'];
        $payload = [
            'name' => 'Sarah Bennett',
            'email' => 'client@lens.app',
            'phone' => '01011112233',
            'locale' => 'en',
            'password' => 'newpass',
            'current_password' => 'wrong-pass',
        ];

        $this->withHeaders($headers)
            ->postJson('/api/app/account/profile', $payload)
            ->assertUnprocessable();

        $payload['current_password'] = 'password';
        $this->withHeaders($headers)
            ->postJson('/api/app/account/profile', $payload)
            ->assertOk();

        $this->assertTrue(Hash::check('newpass', User::query()->where('email', 'client@lens.app')->firstOrFail()->password));
    }

    public function test_vendor_updates_profile_and_admin_sees_it(): void
    {
        $vendor = Vendor::query()->where('display_name', 'Fahad Studio Light')->firstOrFail();
        $headers = ['X-Lens-Client' => 'vendor@lens.app', 'Accept' => 'application/json'];

        $profile = $this->withHeaders($headers)
            ->getJson('/api/app/account/profile')
            ->assertOk()
            ->assertJsonPath('role', 'vendor')
            ->assertJsonPath('vendor.display_name', 'Fahad Studio Light')
            ->assertJsonPath('vendor.vendor_type', 'photographer')
            ->assertJsonPath('vendor.payout_method', 'bank')
            ->json();
        $this->assertNotEmpty(data_get($profile, 'vendor.national_id_image'));
        $this->assertNotEmpty(data_get($profile, 'vendor.cover_image'));

        $this->withHeaders($headers)
            ->postJson('/api/app/account/profile', [
                'name' => 'Fahad Photography',
                'email' => 'vendor@lens.app',
                'phone' => '01022223344',
                'display_name' => 'Fahad Light Desk',
                'vendor_type' => 'photographer',
                'profession' => 'Editorial photographer',
                'address' => 'Zamalek, Cairo',
                'latitude' => 30.0626,
                'longitude' => 31.2197,
                'half_day_price' => 2100,
                'full_day_price' => 3600,
                'payout_method' => 'paypal',
                'paypal_email' => 'fahad@paypal.com',
                'paypal_name' => 'Fahad',
                'specialties' => ['wedding', 'editorial'],
                'projects' => [
                    [
                        'type' => 'link',
                        'title' => 'Gouna editorial',
                        'external_url' => 'https://instagram.com/p/gouna',
                        'description' => 'Summer lookbook.',
                        'is_featured' => true,
                    ],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('vendor.display_name', 'Fahad Light Desk')
            ->assertJsonPath('vendor.payout_method', 'paypal')
            ->assertJsonPath('vendor.half_day_price', 2100)
            ->assertJsonPath('vendor.address', 'Zamalek, Cairo')
            ->assertJsonPath('vendor.latitude', 30.0626);

        Storage::fake('public');
        $this->withHeaders($headers)
            ->post('/api/app/account/profile', [
                'name' => 'Fahad Photography',
                'email' => 'vendor@lens.app',
                'phone' => '01022223344',
                'display_name' => 'Fahad Light Desk',
                'address' => 'Zamalek, Cairo',
                'latitude' => 30.0626,
                'longitude' => 31.2197,
                'cover_image' => UploadedFile::fake()->image('cover.jpg'),
                'national_id_image' => UploadedFile::fake()->image('nid.jpg'),
                'profile_photo' => UploadedFile::fake()->image('photo.jpg'),
            ])
            ->assertOk();

        $vendor->refresh();
        $this->assertSame('Fahad Light Desk', $vendor->display_name);
        $this->assertSame('paypal', $vendor->payout_method);
        $this->assertEquals(2100.0, (float) $vendor->half_day_price);
        $this->assertEqualsCanonicalizing(['wedding', 'editorial'], $vendor->specialties);
        $this->assertTrue($vendor->portfolios()->where('title', 'Gouna editorial')->exists());
        $this->assertSame('Zamalek, Cairo', $vendor->address);
        $this->assertEquals(30.0626, (float) $vendor->latitude);
        $this->assertEquals(31.2197, (float) $vendor->longitude);
        $this->assertNotEmpty($vendor->cover_image);
        $this->assertNotEmpty($vendor->national_id_image);
        $this->assertNotEmpty($vendor->profile_photo);

        $this->actingAs(User::query()->where('email', 'admin@lens.app')->firstOrFail())
            ->get('/admin/vendors/'.$vendor->id.'/edit')
            ->assertOk()
            ->assertSee('Fahad Light Desk')
            ->assertSee('Identity')
            ->assertSee('Type profile')
            ->assertSee('Previous projects')
            ->assertSee('Bank & payouts')
            ->assertSee('Gouna editorial')
            ->assertSee('National ID card')
            ->assertSee('Cover image')
            ->assertSee('Zamalek, Cairo');
    }
}
