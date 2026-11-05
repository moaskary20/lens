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

    protected string $credentialsPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(LensSeeder::class);

        $this->credentialsPath = storage_path('app/firebase/testing-service-account.json');
        if (! is_dir(dirname($this->credentialsPath))) {
            mkdir(dirname($this->credentialsPath), 0755, true);
        }

        // Minimal RSA key for JWT signing in tests (not used against real Google).
        $key = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);
        openssl_pkey_export($key, $privateKey);

        file_put_contents($this->credentialsPath, json_encode([
            'type' => 'service_account',
            'project_id' => 'lens-test',
            'private_key_id' => 'test',
            'private_key' => $privateKey,
            'client_email' => 'firebase-adminsdk@lens-test.iam.gserviceaccount.com',
            'client_id' => '123',
            'token_uri' => 'https://oauth2.googleapis.com/token',
        ], JSON_THROW_ON_ERROR));

        config([
            'services.fcm.credentials' => $this->credentialsPath,
            'services.fcm.server_key' => null,
        ]);
    }

    protected function tearDown(): void
    {
        if (is_file($this->credentialsPath)) {
            @unlink($this->credentialsPath);
        }
        parent::tearDown();
    }

    public function test_lens_alert_sends_android_push_when_device_token_exists(): void
    {
        Http::fake([
            'oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'ya29.test-access-token',
                'expires_in' => 3600,
                'token_type' => 'Bearer',
            ], 200),
            'fcm.googleapis.com/v1/*' => Http::response(['name' => 'projects/lens-test/messages/1'], 200),
        ]);

        $client = User::query()->where('email', 'client@lens.app')->firstOrFail();
        DeviceToken::query()->create([
            'user_id' => $client->id,
            'token' => 'android-fcm-token',
            'platform' => 'android',
        ]);

        LensNotifier::toUser($client, LensNotifier::APP_NOTICE, 'Hello from Lens', 'Your booking was updated.');

        Http::assertSent(function ($request): bool {
            return $request->url() === 'https://fcm.googleapis.com/v1/projects/lens-test/messages:send'
                && $request->hasHeader('Authorization', 'Bearer ya29.test-access-token')
                && data_get($request->data(), 'message.token') === 'android-fcm-token'
                && data_get($request->data(), 'message.notification.title') === 'Hello from Lens'
                && data_get($request->data(), 'message.notification.body') === 'Your booking was updated.'
                && data_get($request->data(), 'message.android.notification.channel_id') === 'lens_alerts_v2'
                && data_get($request->data(), 'message.android.notification.click_action') === 'FLUTTER_NOTIFICATION_CLICK';
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
