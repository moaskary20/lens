<?php

namespace Tests\Feature;

use App\Models\DeviceToken;
use App\Models\User;
use App\Services\FcmPush;
use App\Support\LensNotifier;
use Database\Seeders\LensSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FcmPushTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(LensSeeder::class);
        config(['services.fcm.server_key' => 'test-fcm-server-key']);
    }

    public function test_lens_alert_sends_android_push_when_device_token_exists(): void
    {
        Http::fake([
            'fcm.googleapis.com/*' => Http::response(['success' => 1, 'failure' => 0], 200),
        ]);

        $client = User::query()->where('email', 'client@lens.app')->firstOrFail();
        DeviceToken::query()->create([
            'user_id' => $client->id,
            'token' => 'android-fcm-token',
            'platform' => 'android',
        ]);

        LensNotifier::toUser($client, LensNotifier::APP_NOTICE, 'Hello from Lens', 'Your booking was updated.');

        Http::assertSent(function ($request): bool {
            return $request->url() === 'https://fcm.googleapis.com/fcm/send'
                && $request['to'] === 'android-fcm-token'
                && $request['notification']['title'] === 'Hello from Lens'
                && $request['notification']['body'] === 'Your booking was updated.';
        });

        $this->assertTrue(
            $client->notifications()->where('data->title', 'Hello from Lens')->exists(),
        );
    }

    public function test_fcm_skips_when_push_preference_is_off(): void
    {
        Http::fake();

        $client = User::query()->where('email', 'client@lens.app')->firstOrFail();
        $client->update([
            'app_settings' => array_merge($client->app_settings ?? [], [
                'push_notifications' => false,
            ]),
        ]);
        DeviceToken::query()->create([
            'user_id' => $client->id,
            'token' => 'android-fcm-token',
            'platform' => 'android',
        ]);

        $sent = app(FcmPush::class)->sendToUser($client->fresh(), 'Muted', 'Should not send');

        $this->assertSame(0, $sent);
        Http::assertNothingSent();
    }
}
