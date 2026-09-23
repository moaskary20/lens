<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Moodboard;
use App\Models\Vendor;
use App\Services\AiAssistant;
use App\Services\EscrowService;
use App\Services\GeminiClient;
use App\Services\VendorSearch;
use App\Support\SearchQuery;
use Database\Seeders\LensSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SearchEngineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(LensSeeder::class);
    }

    public function test_primary_facets_rank_photographers_in_cairo(): void
    {
        $cairo = Vendor::query()->where('display_name', 'Fahad Studio Light')->firstOrFail()->city_id;

        $results = app(VendorSearch::class)->search(SearchQuery::fromArray([
            'vendor_type_slugs' => ['photographer'],
            'category_slugs' => ['wedding'],
            'city_id' => $cairo,
            'package_type' => 'half_day',
            'min_price' => 1000,
            'max_price' => 5000,
        ]));

        $this->assertGreaterThan(0, $results['total']);
        $this->assertSame('Fahad Studio Light', $results['vendors'][0]['vendor']->display_name);
    }

    public function test_secondary_studio_kitchen_tag_filters_studios(): void
    {
        $results = app(VendorSearch::class)->search(SearchQuery::fromArray([
            'vendor_type_slugs' => ['studio'],
            'filter_tag_slugs' => ['kitchen-set'],
        ]));

        $this->assertSame('Noor Cyclorama', $results['vendors'][0]['vendor']->display_name);
        $this->assertContains('kitchen-set', $results['vendors'][0]['matched_tags']);
    }

    public function test_natural_language_food_brief_suggests_stylist_and_kitchen(): void
    {
        $result = app(AiAssistant::class)->assist('I want a food photoshoot in a kitchen in Cairo');

        $this->assertContains('fnb', $result['interpreted']['category_slugs']);
        $this->assertContains('photographer', $result['interpreted']['vendor_type_slugs']);
        $this->assertNotEmpty($result['add_ons']);
        $this->assertTrue(collect($result['add_ons'])->contains(fn (array $addon): bool => $addon['vendor_type'] === 'food_stylist'));
        $this->assertTrue(collect($result['add_ons'])->contains(fn (array $addon): bool => $addon['vendor_type'] === 'studio'));
        $this->assertTrue($result['moodboard_locked']);
        $this->assertSame('lexicon', $result['provider']);
        $this->assertSame('Fahad Studio Light', $result['results']['vendors'][0]['vendor']->display_name);
    }

    public function test_gemini_json_is_used_when_configured(): void
    {
        config(['services.gemini.key' => 'test-key']);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [['text' => json_encode([
                        'category_slugs' => ['wedding'],
                        'vendor_type_slugs' => ['photographer'],
                        'filter_tag_slugs' => ['strobes'],
                        'governorate' => 'Cairo',
                        'package_type' => 'half_day',
                        'summary' => 'Wedding photographer in Cairo',
                    ])]]],
                ]],
            ]),
        ]);

        $this->assertTrue(app(GeminiClient::class)->configured());

        $result = app(AiAssistant::class)->assist('messy wedding ideas please');

        $this->assertSame('gemini', $result['provider']);
        $this->assertContains('wedding', $result['interpreted']['category_slugs']);
        $this->assertSame('Wedding photographer in Cairo', $result['summary']);
    }

    public function test_api_search_and_assistant_endpoints(): void
    {
        $this->postJson('/api/search', [
            'vendor_type_slugs' => ['photographer'],
        ])->assertOk()->assertJsonPath('vendors.0.display_name', 'Fahad Studio Light');

        $this->postJson('/api/search/assistant', [
            'brief' => 'I want a food photoshoot in Cairo',
        ])->assertOk()->assertJsonPath('interpreted.category_slugs.0', 'fnb');

        $this->getJson('/api/search/catalog')->assertOk()->assertJsonStructure(['settings', 'groups']);
    }

    public function test_moodboard_unlocks_after_checkout(): void
    {
        $booking = Booking::query()->where('reference', 'LN-1003')->firstOrFail();
        $this->assertSame('none', $booking->escrow_status);

        $this->postJson('/api/bookings/'.$booking->id.'/moodboard')
            ->assertStatus(402);

        app(EscrowService::class)->checkout($booking->fresh());

        $this->assertTrue(Moodboard::query()->where('booking_id', $booking->id)->exists());
        $this->postJson('/api/bookings/'.$booking->id.'/moodboard')->assertOk();
    }
}
