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

    public function test_home_matches_the_public_marketplace_layout(): void
    {
        $html = $this->get('/')
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

        $this->assertLessThan(
            strpos($html, 'data-slider'),
            strpos($html, 'id="home-q"'),
            'Keyword search should live in the header, before the slider.',
        );
        $this->assertLessThan(
            strpos($html, 'What will you create today?'),
            strpos($html, 'data-slider'),
            'The AI prompt should render below the hero slider.',
        );
    }

    public function test_header_nav_opens_each_primary_page(): void
    {
        $this->get('/search')->assertOk()->assertSee('Search');
        $this->get('/bookings')->assertOk()->assertSee('Bookings');
        $this->get('/explore')->assertOk()->assertSee('Explore');
        $this->get('/p/about')->assertOk()->assertSee('About Us');
    }

    public function test_search_and_vendor_profile_are_reachable_from_home(): void
    {
        $vendor = Vendor::query()->where('is_active', true)->firstOrFail();

        $this->get('/search?q=studio')->assertOk();
        $this->get('/search?vendor_type=photographer')
            ->assertOk()
            ->assertSee('Photographers')
            ->assertSee('Sort')
            ->assertSee('Filter')
            ->assertSee('Map')
            ->assertSee('Recommended')
            ->assertSee('Starting from');
        $this->get('/creatives/'.$vendor->id)
            ->assertOk()
            ->assertSee($vendor->display_name)
            ->assertSee('About')
            ->assertSee('Specialties')
            ->assertSee('Equipment')
            ->assertSee('Portfolio')
            ->assertSee('Packages')
            ->assertSee('Reviews')
            ->assertSee('Book Now')
            ->assertSee('Watch Showreel')
            ->assertSee('Projects Completed');
    }
}
