<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\LensSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GoogleAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(LensSeeder::class);
        config([
            'services.google.client_id' => '165894637736-lil7v98s08ndt4munuj7885iv8nmsos1.apps.googleusercontent.com',
        ]);
    }

    public function test_google_sign_in_creates_client(): void
    {
        Http::fake([
            'oauth2.googleapis.com/*' => Http::response([
                'sub' => 'google-sub-100',
                'email' => 'new.google@lens.app',
                'email_verified' => 'true',
                'name' => 'Google Client',
                'aud' => '165894637736-lil7v98s08ndt4munuj7885iv8nmsos1.apps.googleusercontent.com',
            ], 200),
        ]);

        $this->postJson('/api/app/auth/google', [
            'id_token' => 'fake-google-id-token',
            'role' => 'client',
        ])
            ->assertOk()
            ->assertJsonPath('email', 'new.google@lens.app')
            ->assertJsonPath('role', 'client')
            ->assertJsonPath('name', 'Google Client');

        $user = User::query()->where('email', 'new.google@lens.app')->firstOrFail();
        $this->assertSame('google-sub-100', $user->google_id);
    }

    public function test_google_sign_in_links_existing_email(): void
    {
        Http::fake([
            'oauth2.googleapis.com/*' => Http::response([
                'sub' => 'google-sub-client',
                'email' => 'client@lens.app',
                'email_verified' => true,
                'name' => 'Lens Client',
                'aud' => '165894637736-lil7v98s08ndt4munuj7885iv8nmsos1.apps.googleusercontent.com',
            ], 200),
        ]);

        $this->postJson('/api/app/auth/google', [
            'id_token' => 'fake-google-id-token-2',
        ])
            ->assertOk()
            ->assertJsonPath('email', 'client@lens.app')
            ->assertJsonPath('role', 'client');

        $existing = User::query()->where('email', 'client@lens.app')->firstOrFail();
        $this->assertSame('google-sub-client', $existing->google_id);
    }

    public function test_google_sign_in_rejects_bad_audience(): void
    {
        Http::fake([
            'oauth2.googleapis.com/tokeninfo*' => Http::response([
                'sub' => 'x',
                'email' => 'bad@lens.app',
                'email_verified' => 'true',
                'aud' => 'other-client.apps.googleusercontent.com',
            ], 200),
        ]);

        $this->postJson('/api/app/auth/google', [
            'id_token' => 'fake',
        ])->assertStatus(422);
    }
}
