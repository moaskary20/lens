<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorType;
use App\Services\RecommendationService;
use Database\Seeders\LensSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecommendationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(LensSeeder::class);
        $this->actingAs(User::query()->where('email', 'admin@lens.app')->firstOrFail());
    }

    public function test_api_learns_from_the_seeded_client(): void
    {
        $client = User::query()->where('email', 'client@lens.app')->firstOrFail();

        $feed = app(RecommendationService::class)->forClient($client);

        $this->assertContains('photographer', $feed['profile']['vendor_type_slugs']);
        $this->assertContains('studio', $feed['profile']['vendor_type_slugs']);
        $this->assertNotEmpty($feed['nearby_vendors']);
        $this->assertSame('Fahad Studio Light', $feed['nearby_vendors'][0]['display_name']);
        $this->assertSame('Fahad Studio Light', $feed['top_in_specialty'][0]['display_name']);
        $this->assertNotEmpty($feed['budget_fit']);
        $this->assertTrue(collect($feed['suitable_services'])->contains(fn (array $row) => $row['slug'] === 'photographer'));
        $this->assertTrue(collect($feed['suitable_offers'])->contains(fn (array $row) => $row['code'] === 'WELCOME200'));
        $this->assertTrue(collect($feed['suitable_offers'])->contains(fn (array $row) => $row['code'] === 'PHOTO10'));

        $this->getJson('/api/search/recommendations?client_id='.$client->id)
            ->assertOk()
            ->assertJsonPath('nearby_vendors.0.display_name', 'Fahad Studio Light');
    }

    public function test_similar_vendors_come_from_the_same_specialty_not_already_booked(): void
    {
        $client = User::query()->where('email', 'client@lens.app')->firstOrFail();
        $type = VendorType::query()->where('slug', 'photographer')->firstOrFail();
        $user = User::query()->create([
            'name' => 'Layla Photo',
            'email' => 'layla-rec@lens.app',
            'password' => 'password',
            'role' => 'vendor',
            'is_active' => true,
        ]);
        $peer = Vendor::query()->create([
            'user_id' => $user->id,
            'vendor_type_id' => $type->id,
            'city_id' => $client->city_id,
            'display_name' => 'Layla Wedding Light',
            'verification_status' => 'verified',
            'is_active' => true,
            'half_day_price' => 1600,
            'completed_sessions' => 12,
            'rating_avg' => 4.9,
            'rating_count' => 8,
        ]);
        $peer->categories()->sync(Category::query()->where('slug', 'wedding')->pluck('id'));

        $feed = app(RecommendationService::class)->forClient($client);

        $this->assertTrue(collect($feed['similar_vendors'])->contains(fn (array $row) => $row['id'] === $peer->id));
        $this->assertFalse(collect($feed['similar_vendors'])->contains(fn (array $row) => $row['display_name'] === 'Fahad Studio Light'));
    }

    public function test_search_records_a_signal_used_by_the_engine(): void
    {
        $client = User::query()->create([
            'name' => 'Fresh client',
            'email' => 'fresh-rec@lens.app',
            'password' => 'password',
            'role' => 'client',
            'city_id' => User::query()->where('email', 'client@lens.app')->value('city_id'),
            'is_active' => true,
        ]);

        $this->postJson('/api/search', [
            'client_id' => $client->id,
            'vendor_type_slugs' => ['studio'],
            'max_price' => 1200,
        ])->assertOk();

        $this->assertTrue($client->recommendationSignals()->where('source', 'search')->exists());

        $feed = app(RecommendationService::class)->forClient($client->fresh());
        $this->assertContains('studio', $feed['profile']['vendor_type_slugs']);
        $this->assertNotEmpty($feed['nearby_vendors']);
    }

    public function test_new_client_gets_nearby_and_first_order_offer(): void
    {
        $client = User::query()->create([
            'name' => 'Brand new',
            'email' => 'new-rec@lens.app',
            'password' => 'password',
            'role' => 'client',
            'city_id' => User::query()->where('email', 'client@lens.app')->value('city_id'),
            'is_active' => true,
        ]);

        $feed = app(RecommendationService::class)->forClient($client);

        $this->assertSame([], $feed['profile']['vendor_type_slugs']);
        $this->assertNotEmpty($feed['nearby_vendors']);
        $this->assertTrue(collect($feed['suitable_offers'])->contains(fn (array $row) => $row['code'] === 'FIRSTSHOOT'));
        $this->assertFalse(collect($feed['suitable_offers'])->contains(fn (array $row) => $row['code'] === 'LOYAL10'));
    }
}
