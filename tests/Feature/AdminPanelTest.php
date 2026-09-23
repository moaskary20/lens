<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\LensSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(LensSeeder::class);
    }

    public function test_guest_sees_english_login(): void
    {
        $response = $this->get('/admin/login');

        $response->assertOk();
        $response->assertSee('Sign in', false);
        $response->assertSee('Operations console', false);
        $response->assertSee('lens-login', false);
        $response->assertSee('lang="en"', false);
        $response->assertSee('css/lens-admin.css', false);
        $response->assertSee('vendor/lens/animate.min.css', false);
        $response->assertSee('vendor/lens/aos.js', false);
        $response->assertSee('vendor/lens/anime.min.js', false);
        $response->assertSee('class="fi dark"', false);
        $response->assertDontSee('fi-theme-switcher', false);
        $response->assertSee("localStorage.setItem('theme', 'dark')", false);
    }

    public function test_admin_can_open_dashboard(): void
    {
        $admin = User::query()->where('email', 'admin@lens.app')->firstOrFail();

        $this->actingAs($admin)
            ->get('/admin')
            ->assertOk()
            ->assertDontSee('Client filter map', false)
            ->assertDontSee('Filter options', false)
            ->assertDontSee('Marketplace loop', false)
            ->assertDontSee('Previous work', false)
            ->assertDontSee('Availability calendar', false)
            ->assertSee('Operations', false)
            ->assertSee('class="fi dark"', false)
            ->assertDontSee('fi-theme-switcher', false);
    }

    public function test_core_resources_render(): void
    {
        $admin = User::query()->where('email', 'admin@lens.app')->firstOrFail();

        foreach ([
            '/admin/users',
            '/admin/vendors',
            '/admin/bookings',
            '/admin/vendor-types',
            '/admin/pricing-models',
            '/admin/categories',
            '/admin/filter-tags',
            '/admin/filter-groups',
            '/admin/recommendation-rules',
            '/admin/search-settings',
            '/admin/cities',
            '/admin/badges',
            '/admin/escrow-transactions',
            '/admin/payouts',
            '/admin/disputes',
            '/admin/reviews',
            '/admin/deliverables',
            '/admin/portfolios',
            '/admin/conversations',
            '/admin/app-screens',
            '/admin/cms-pages',
            '/admin/cancellation-policies',
            '/admin/replacement-offers',
            '/admin/role-settings',
            '/admin/vendor-availabilities',
            '/admin/feature-settings',
            '/admin/financial-settings',
            '/admin/wallets',
            '/admin/coupons',
            '/admin/notification-log',
            '/admin/notification-settings',
            '/admin/favorites',
            '/admin/delivery-settings',
            '/admin/reputation-settings',
            '/admin/session-metrics',
            '/admin/platform-settings',
        ] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
    }

    public function test_marketplace_loop_is_editable_from_admin(): void
    {
        $admin = User::query()->where('email', 'admin@lens.app')->firstOrFail();
        $client = User::query()->where('email', 'client@lens.app')->firstOrFail();
        $vendor = \App\Models\Vendor::query()->firstOrFail();
        $booking = \App\Models\Booking::query()->where('reference', 'LN-1001')->firstOrFail();

        $this->actingAs($admin)->get('/admin/users/'.$client->id.'/edit')
            ->assertOk()
            ->assertSee('Client projects', false);

        $this->actingAs($admin)->get('/admin/vendors/'.$vendor->id.'/edit')
            ->assertOk()
            ->assertSee('Previous projects', false)
            ->assertSee('Client projects', false);

        $this->actingAs($admin)->get('/admin/bookings/'.$booking->id.'/edit')
            ->assertOk()
            ->assertSee('Client project', false)
            ->assertSee('Vendor delivery files', false);
    }
}
