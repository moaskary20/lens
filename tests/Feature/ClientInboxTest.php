<?php

namespace Tests\Feature;

use App\Models\Favorite;
use App\Models\User;
use App\Models\Vendor;
use App\Support\LensNotifier;
use Database\Seeders\LensSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientInboxTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(LensSeeder::class);
    }

    public function test_bootstrap_exposes_inbox_counts(): void
    {
        $guest = $this->getJson('/api/app/bootstrap')
            ->assertOk()
            ->assertJsonPath('features.favorites', true)
            ->assertJsonPath('features.notifications', true);
        $this->assertSame([], $guest->json('favorite_ids'));
        $this->assertSame(0, $guest->json('unread_notifications'));

        $payload = $this->withHeaders(['X-Lens-Client' => 'client@lens.app'])
            ->getJson('/api/app/bootstrap')
            ->assertOk();
        $this->assertGreaterThan(0, count($payload->json('favorite_ids')));
        $this->assertGreaterThan(0, $payload->json('unread_notifications'));
    }

    public function test_client_can_save_and_forget_a_vendor(): void
    {
        $headers = ['X-Lens-Client' => 'client@lens.app'];
        $client = User::query()->where('email', 'client@lens.app')->firstOrFail();
        $vendor = Vendor::query()->where('is_active', true)->orderBy('id')->firstOrFail();
        Favorite::query()->where('user_id', $client->id)->where('vendor_id', $vendor->id)->delete();

        $this->withHeaders($headers)
            ->postJson('/api/app/favorites/'.$vendor->id)
            ->assertOk()
            ->assertJsonPath('favorited', true)
            ->assertJsonPath('vendor.id', $vendor->id);

        $this->assertTrue($client->favoriteVendors()->where('vendors.id', $vendor->id)->exists());

        $list = $this->withHeaders($headers)->getJson('/api/app/favorites')->assertOk();
        $this->assertContains($vendor->id, $list->json('ids'));

        $this->withHeaders($headers)
            ->deleteJson('/api/app/favorites/'.$vendor->id)
            ->assertOk()
            ->assertJsonPath('favorited', false);

        $this->assertFalse($client->fresh()->favoriteVendors()->where('vendors.id', $vendor->id)->exists());
    }

    public function test_client_can_read_app_notifications(): void
    {
        $headers = ['X-Lens-Client' => 'client@lens.app'];
        $inbox = $this->withHeaders($headers)->getJson('/api/app/notifications')->assertOk();
        $this->assertGreaterThan(0, $inbox->json('unread'));
        $this->assertNotEmpty($inbox->json('notifications.0.title'));

        $id = $inbox->json('notifications.0.id');
        $this->withHeaders($headers)
            ->postJson('/api/app/notifications/'.$id.'/read')
            ->assertOk()
            ->assertJsonPath('notification.read', true);

        $this->withHeaders($headers)->postJson('/api/app/notifications/read-all')->assertOk()->assertJsonPath('unread', 0);
        $this->withHeaders($headers)->getJson('/api/app/notifications')->assertJsonPath('unread', 0);

        $this->withHeaders($headers)
            ->postJson('/api/app/notifications/clear-all')
            ->assertOk()
            ->assertJsonPath('unread', 0)
            ->assertJsonPath('notifications', []);

        $client = User::query()->where('email', 'client@lens.app')->firstOrFail();
        $this->assertSame(0, $client->notifications()->count());
        $this->withHeaders($headers)
            ->getJson('/api/app/notifications')
            ->assertOk()
            ->assertJsonPath('unread', 0)
            ->assertJsonPath('notifications', []);
    }

    public function test_client_can_register_and_forget_a_device_token(): void
    {
        $headers = ['X-Lens-Client' => 'client@lens.app'];

        $this->withHeaders($headers)
            ->postJson('/api/app/device-token', [
                'token' => 'fcm-test-token-123',
                'platform' => 'android',
            ])
            ->assertOk()
            ->assertJsonPath('registered', true);

        $client = User::query()->where('email', 'client@lens.app')->firstOrFail();
        $this->assertTrue($client->deviceTokens()->where('token', 'fcm-test-token-123')->exists());

        $this->withHeaders($headers)
            ->deleteJson('/api/app/device-token', ['token' => 'fcm-test-token-123'])
            ->assertOk()
            ->assertJsonPath('registered', false);

        $this->assertFalse($client->fresh()->deviceTokens()->where('token', 'fcm-test-token-123')->exists());
    }

    public function test_admin_can_send_notice_into_the_app(): void
    {
        $admin = User::query()->where('email', 'admin@lens.app')->firstOrFail();
        $client = User::query()->where('email', 'client@lens.app')->firstOrFail();
        $before = $client->notifications()->count();

        $this->actingAs($admin)
            ->get('/admin/favorites')
            ->assertOk()
            ->assertSee('Saved', false);

        $this->actingAs($admin)
            ->get('/admin/notification-log')
            ->assertOk()
            ->assertSee('Send to the app', false);

        $this->actingAs($admin)
            ->get('/admin/notification-settings')
            ->assertOk()
            ->assertSee('App notice', false)
            ->assertSee('Send notice', false)
            ->assertSee('Send notification emails', false);

        $count = LensNotifier::toClients('Shoot tomorrow', 'Your studio hold is confirmed.');
        $this->assertGreaterThan(0, $count);
        $this->assertGreaterThan($before, $client->fresh()->notifications()->count());
        $this->assertTrue(
            $client->fresh()->notifications()->get()->contains(fn ($item) => ($item->data['title'] ?? null) === 'Shoot tomorrow')
        );

        $this->actingAs($admin)->get('/admin/clients/'.$client->id.'/edit')
            ->assertOk()
            ->assertSee('Saved creators', false);
    }
}
