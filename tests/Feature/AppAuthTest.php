<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vendor;
use Database\Seeders\LensSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        ])->assertCreated()->assertJsonPath('role', 'client');

        $this->assertDatabaseHas('users', ['email' => 'nour@lens.app', 'role' => 'client']);

        $this->postJson('/api/app/auth/register', [
            'name' => 'Cairo Motion',
            'email' => 'cairo.motion@lens.app',
            'password' => 'password',
            'role' => 'vendor',
            'vendor_type' => 'videographer',
        ])->assertCreated()->assertJsonPath('role', 'vendor');

        $user = User::query()->where('email', 'cairo.motion@lens.app')->firstOrFail();
        $this->assertTrue(Vendor::query()->where('user_id', $user->id)->exists());
    }

    public function test_staff_cannot_sign_in_on_the_app(): void
    {
        $this->postJson('/api/app/auth/login', [
            'email' => 'admin@lens.app',
            'password' => 'password',
        ])->assertStatus(422);
    }
}
