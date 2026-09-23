<?php

namespace Tests\Feature;

use App\Services\GeminiClient;
use Database\Seeders\LensSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AssistantChatTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(LensSeeder::class);
        config(['services.gemini.key' => null]);
    }

    public function test_arabic_wedding_brief_asks_location_then_compares_fahad(): void
    {
        $first = $this->postJson('/api/search/assistant/chat', [
            'message' => 'محتاج مصور لفرح الشهر الجاي وعاوز حد يكون سعره متوسط.',
        ]);

        $first->assertOk()
            ->assertJsonPath('ask', 'location')
            ->assertJsonPath('status', 'gathering')
            ->assertJsonPath('locale', 'ar')
            ->assertJsonPath('provider', 'lexicon')
            ->assertJsonPath('slots.budget_band', 'mid')
            ->assertJsonPath('slots.relative_date', 'next_month')
            ->assertJsonPath('slots.vendor_type_slugs.0', 'photographer')
            ->assertJsonPath('slots.category_slugs.0', 'wedding');

        $this->assertStringContainsString('محافظة', (string) $first->json('reply'));
        $this->assertSame([], $first->json('vendors'));

        $second = $this->postJson('/api/search/assistant/chat', [
            'conversation_id' => $first->json('conversation_id'),
            'message' => 'القاهرة',
        ]);

        $second->assertOk()
            ->assertJsonPath('status', 'ready')
            ->assertJsonPath('ask', null)
            ->assertJsonPath('pick.display_name', 'Fahad Studio Light')
            ->assertJsonPath('comparison.0.display_name', 'Fahad Studio Light')
            ->assertJsonPath('vendors.0.display_name', 'Fahad Studio Light');

        $this->assertStringContainsString('ترشيحي', (string) $second->json('reply'));
        $this->assertNotEmpty($second->json('comparison'));
    }

    public function test_gemini_chat_keeps_provider_wording_when_asking_for_location(): void
    {
        config(['services.gemini.key' => 'test-key']);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [['text' => json_encode([
                        'reply' => 'الفرح هيكون في أنهي محافظة؟',
                        'ask' => 'location',
                        'slots' => [
                            'category_slugs' => ['wedding'],
                            'vendor_type_slugs' => ['photographer'],
                            'budget_band' => 'mid',
                            'relative_date' => 'next_month',
                        ],
                    ], JSON_UNESCAPED_UNICODE)]]],
                ]],
            ]),
        ]);

        $this->assertTrue(app(GeminiClient::class)->configured());

        $this->postJson('/api/search/assistant/chat', [
            'message' => 'محتاج مصور لفرح الشهر الجاي وعاوز حد يكون سعره متوسط.',
        ])->assertOk()
            ->assertJsonPath('provider', 'gemini')
            ->assertJsonPath('ask', 'location')
            ->assertJsonPath('reply', 'الفرح هيكون في أنهي محافظة؟');
    }
}
