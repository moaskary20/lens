<?php

namespace Tests\Feature;

use App\Models\Setting;
use Database\Seeders\LensSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppBootstrapTest extends TestCase
{
    use RefreshDatabase;

    public function test_mobile_home_payload_follows_admin_features(): void
    {
        $this->seed(LensSeeder::class);

        $payload = $this->getJson('/api/app/bootstrap')
            ->assertOk()
            ->assertJsonPath('name', 'Lens')
            ->assertJsonPath('tagline', 'Find. Book. Create.')
            ->assertJsonPath('features.ai_assistant', true)
            ->assertJsonPath('features.vendor_photographers', true)
            ->assertJsonPath('vendor_types.0.slug', 'photographer')
            ->assertJsonPath('popular.0.vendors.0.display_name', 'Fahad Studio Light');

        $this->assertNotEmpty($payload->json('popular.0.vendors.0.cover_url'));
        $this->assertGreaterThan(1, count($payload->json('vendor_types')));

        $popular = collect($payload->json('popular'))->keyBy('slug');
        $this->assertTrue($popular->has('studio'));
        $this->assertGreaterThan(1, count($popular['photographer']['vendors']));

        foreach ($payload->json('vendor_types') as $type) {
            $this->assertTrue($popular->has($type['slug']), 'Missing popular section for '.$type['slug']);
            $this->assertGreaterThanOrEqual(3, count($popular[$type['slug']]['vendors']));
            foreach ($popular[$type['slug']]['vendors'] as $vendor) {
                $this->assertNotEmpty($vendor['cover_url']);
                $this->assertNotEmpty($vendor['profile_photo_url']);
            }
        }

        $this->assertIsNumeric($payload->json('popular.0.vendors.0.rating_avg'));
        $this->assertNotEmpty($payload->json('popular.0.vendors.0.tags'));
        $this->assertNotNull($payload->json('popular.0.vendors.0.starting_from'));

        $catalog = collect($payload->json('filter_catalog'))->keyBy('slug');
        $this->assertTrue($catalog->has('model_identity'));
        $this->assertTrue($catalog->has('model_category'));
        $this->assertTrue($catalog->has('model_age'));
        $this->assertTrue($catalog->has('model_height'));
        $this->assertTrue($catalog->has('model_tops'));
        $this->assertTrue($catalog->has('model_pants'));
        $this->assertTrue($catalog->has('model_shoes'));
        $this->assertTrue($catalog->has('search_cities'));
        $this->assertSame(['Men', 'Women', 'Kids'], collect($catalog['model_identity']['options'])->pluck('label')->all());
        $this->assertContains('Fashion', collect($catalog['model_category']['options'])->pluck('label')->all());
        $this->assertContains('Acting', collect($catalog['model_category']['options'])->pluck('label')->all());
        $this->assertContains('Social Media', collect($catalog['model_category']['options'])->pluck('label')->all());
        $this->assertContains('Under 18', collect($catalog['model_age']['options'])->pluck('label')->all());
        $this->assertContains('200+', collect($catalog['model_height']['options'])->pluck('label')->all());
        $this->assertSame(['XS', 'S', 'M', 'L', 'XL', 'XXL'], collect($catalog['model_tops']['options'])->pluck('label')->all());
        $this->assertContains('32', collect($catalog['model_pants']['options'])->pluck('label')->all());
        $this->assertContains('45+', collect($catalog['model_shoes']['options'])->pluck('label')->all());
        $this->assertContains('Cairo', collect($catalog['search_cities']['options'])->pluck('label')->all());
        $this->assertContains('6th of October', collect($catalog['search_cities']['options'])->pluck('label')->all());
        $this->assertContains('10th of Ramadan', collect($catalog['search_cities']['options'])->pluck('label')->all());
        $this->assertContains('4.0+', collect($catalog['rating']['options'])->pluck('label')->all());
        $this->assertContains('This Week', collect($catalog['availability']['options'])->pluck('label')->all());
        $this->assertTrue($catalog->has('studio_type'));
        $this->assertTrue($catalog->has('studio_areas'));
        $this->assertTrue($catalog->has('studio_hourly'));
        $this->assertTrue($catalog->has('studio_size'));
        $this->assertTrue($catalog->has('studio_features'));
        $this->assertContains('Photo Studio', collect($catalog['studio_type']['options'])->pluck('label')->all());
        $this->assertContains('Podcast Studio', collect($catalog['studio_type']['options'])->pluck('label')->all());
        $this->assertContains('Zamalek', collect($catalog['studio_areas']['options'])->pluck('label')->all());
        $this->assertContains('Sheikh Zayed', collect($catalog['studio_areas']['options'])->pluck('label')->all());
        $this->assertContains('2,000+', collect($catalog['studio_hourly']['options'])->pluck('label')->all());
        $this->assertContains('Under 50', collect($catalog['studio_size']['options'])->pluck('label')->all());
        $this->assertContains('Makeup Room', collect($catalog['studio_features']['options'])->pluck('label')->all());
        $this->assertContains('Natural Light', collect($catalog['studio_features']['options'])->pluck('label')->all());
        $this->assertContains('3.5+', collect($catalog['rating']['options'])->pluck('label')->all());
        $this->assertTrue($catalog->has('ugc_niche'));
        $this->assertTrue($catalog->has('ugc_cities'));
        $this->assertTrue($catalog->has('ugc_accent'));
        $this->assertTrue($catalog->has('ugc_followers'));
        $this->assertTrue($catalog->has('ugc_content'));
        $this->assertTrue($catalog->has('ugc_gender'));
        $this->assertTrue($catalog->has('ugc_language'));
        $this->assertContains('Beauty', collect($catalog['ugc_niche']['options'])->pluck('label')->all());
        $this->assertContains('Food & Beverages', collect($catalog['ugc_niche']['options'])->pluck('label')->all());
        $this->assertContains('Sharm El Sheikh', collect($catalog['ugc_cities']['options'])->pluck('label')->all());
        $this->assertContains('Shami (Levantine)', collect($catalog['ugc_accent']['options'])->pluck('label')->all());
        $this->assertContains('500K - 1M+', collect($catalog['ugc_followers']['options'])->pluck('label')->all());
        $this->assertContains('Product Demo', collect($catalog['ugc_content']['options'])->pluck('label')->all());
        $this->assertContains('Non-binary', collect($catalog['ugc_gender']['options'])->pluck('label')->all());
        $this->assertContains('Bilingual', collect($catalog['ugc_language']['options'])->pluck('label')->all());
        $this->assertContains('Any Time', collect($catalog['availability']['options'])->pluck('label')->all());
        $this->assertTrue($catalog->has('food_expertise'));
        $this->assertTrue($catalog->has('food_cuisine'));
        $this->assertTrue($catalog->has('food_cities'));
        $this->assertTrue($catalog->has('food_content'));
        $this->assertContains('Recipe Development', collect($catalog['food_expertise']['options'])->pluck('label')->all());
        $this->assertContains('Props Styling', collect($catalog['food_expertise']['options'])->pluck('label')->all());
        $this->assertContains('Desserts & Bakery', collect($catalog['food_cuisine']['options'])->pluck('label')->all());
        $this->assertContains('Fine Dining', collect($catalog['food_cuisine']['options'])->pluck('label')->all());
        $this->assertContains('Sharm El Sheikh', collect($catalog['food_cities']['options'])->pluck('label')->all());
        $this->assertContains('Recipe Video', collect($catalog['food_content']['options'])->pluck('label')->all());
        $this->assertContains('Social Media Content', collect($catalog['food_content']['options'])->pluck('label')->all());

        $listing = $this->getJson('/api/app/vendors?vendor_type=photographer')->assertOk();
        $this->assertGreaterThanOrEqual(3, count($listing->json('vendors')));
        $listing->assertJsonPath('title', 'Photographers');

        $profile = $this->getJson('/api/app/vendors/'.$listing->json('vendors.0.id'))->assertOk();
        $this->assertNotEmpty($profile->json('display_name'));
        $this->assertNotEmpty($profile->json('portfolio'));
        $this->assertNotEmpty($profile->json('portfolio_filters'));
        $this->assertNotEmpty($profile->json('tagline'));

        Setting::setValue('features.ai_assistant', false);
        Setting::setValue('features.vendor_videographers', false);

        $hidden = $this->getJson('/api/app/bootstrap')->assertOk();
        $hidden->assertJsonPath('features.ai_assistant', false);
        $this->assertFalse(collect($hidden->json('vendor_types'))->contains(fn ($type) => $type['slug'] === 'videographer'));
    }
}
