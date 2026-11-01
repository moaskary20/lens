<?php

namespace Tests\Feature;

use App\Models\Vendor;
use Database\Seeders\LensSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteHomeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(LensSeeder::class);
    }

    public function test_home_shows_coming_soon_with_november_countdown(): void
    {
        config([
            'lens.coming_soon' => true,
            'lens.launch_at' => '2026-11-08T00:00:00+02:00',
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Coming Soon')
            ->assertSee('Launching November 8')
            ->assertSee('branding/lenslogo.png', false)
            ->assertSee('branding/hero-pyramids.jpg', false)
            ->assertSee('data-launch-at="2026-11-08T00:00:00+02:00"', false)
            ->assertSee('Notify me')
            ->assertSee('Find. Book. Create.');
    }

    public function test_marketplace_preview_matches_the_public_layout(): void
    {
        config(['lens.coming_soon' => true]);

        $html = $this->get('/marketplace')
            ->assertOk()
            ->assertSee('branding/lenslogo_mark.png', false)
            ->assertSee('Home')
            ->assertSee('Bookings')
            ->assertSee('Search')
            ->assertSee('Explore')
            ->assertSee('Categories')
            ->assertSee('Photographers')
            ->assertSee('branding/categories/photographer.jpg', false)
            ->assertSee('branding/categories/videographer.jpg', false)
            ->assertSee('Popular Photographer')
            ->assertSee('Popular Videographer')
            ->assertSee('Featured Studios')
            ->assertSee('What will you create today?')
            ->assertDontSee('Need something special?')
            ->assertSee('/branding/hero-pyramids.jpg', false)
            ->assertSee('Ready to Bring Your')
            ->assertSee('Subscribe to Our Newsletter')
            ->assertSee('Download Our App')
            ->assertSee('Quick Links')
            ->assertSee('Explore Now')
            ->assertSee('UCC Creators')
            ->assertSee('It’s a creative community')
            ->getContent();

        $this->assertDoesNotMatchRegularExpression(
            "/background-image:\\s*url\\('https?:\\/\\/(?:127\\.0\\.0\\.1|localhost)/",
            $html,
            'Slider images must be reachable from the visitor browser, not localhost.',
        );
    }

    public function test_coming_soon_redirects_primary_site_pages_home(): void
    {
        config(['lens.coming_soon' => true]);

        $this->get('/search')->assertRedirect('/');
        $this->get('/bookings')->assertRedirect('/');
        $this->get('/explore')->assertRedirect('/');
        $this->get('/p/about')->assertRedirect('/');
    }

    public function test_live_site_routes_when_coming_soon_is_off(): void
    {
        config(['lens.coming_soon' => false]);

        $this->get('/')->assertOk()->assertSee('Categories');
        $this->get('/search')->assertOk()->assertSee('Search');
        $this->get('/bookings')->assertOk()->assertSee('Bookings');
        $this->get('/explore')->assertOk()->assertSee('Explore');
        $this->get('/p/about')->assertOk()->assertSee('About Us');
    }

    public function test_search_and_vendor_profile_are_reachable_when_site_is_live(): void
    {
        config(['lens.coming_soon' => false]);

        $vendor = Vendor::query()->where('is_active', true)->firstOrFail();

        $this->get('/search?q=studio')->assertOk();
        $this->get('/search?vendor_type=photographer')
            ->assertOk()
            ->assertSee('Photographers');
        $this->get('/creatives/'.$vendor->id)
            ->assertOk()
            ->assertSee($vendor->display_name);
    }
}
