<?php

namespace Tests\Feature;

use App\Filament\Resources\VendorResource\Pages\ListVendors;
use App\Models\Booking;
use App\Models\User;
use App\Models\Vendor;
use Database\Seeders\LensSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
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
        $response->assertSee('lens/animate.min.css', false);
        $response->assertSee('lens/aos.js', false);
        $response->assertSee('lens/anime.min.js', false);
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

    public function test_admin_password_can_be_reset_from_the_console(): void
    {
        $admin = User::query()->where('email', 'admin@lens.app')->firstOrFail();

        $this->artisan('lens:reset-admin-password', ['--password' => 'password'])
            ->expectsOutput('Admin password updated.')
            ->assertSuccessful();

        $this->assertTrue(Hash::check('password', $admin->fresh()->password));
        $this->assertTrue($admin->fresh()->is_active);
        $this->assertTrue($admin->fresh()->isAdmin());
    }

    public function test_admin_account_can_be_created_from_the_console(): void
    {
        User::query()->where('email', 'ops@lens.app')->delete();

        $this->artisan('lens:reset-admin-password', [
            'email' => 'ops@lens.app',
            '--password' => 'password',
            '--name' => 'Ops Admin',
        ])
            ->expectsOutput('Admin account created and password set.')
            ->assertSuccessful();

        $admin = User::query()->where('email', 'ops@lens.app')->firstOrFail();
        $this->assertTrue(Hash::check('password', $admin->password));
        $this->assertTrue($admin->isAdmin());
        $this->assertTrue($admin->is_active);
    }

    public function test_admin_password_reset_refuses_non_staff_accounts(): void
    {
        $this->artisan('lens:reset-admin-password', [
            'email' => 'client@lens.app',
            '--password' => 'password',
        ])
            ->expectsOutput('That email belongs to a non-staff account and was left unchanged.')
            ->assertFailed();
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
            '/admin/issue-reports',
            '/admin/reviews',
            '/admin/deliverables',
            '/admin/portfolios',
            '/admin/conversations',
            '/admin/app-screens',
            '/admin/cms-pages',
            '/admin/cancellation-policies',
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

    public function test_vendor_status_tabs_filter_and_global_search_covers_vendor_fields(): void
    {
        $admin = User::query()->where('email', 'admin@lens.app')->firstOrFail();
        $verified = Vendor::query()->where('display_name', 'Fahad Studio Light')->firstOrFail();
        $pending = Vendor::query()->where('display_name', 'Noor Cyclorama')->firstOrFail();
        $rejected = $verified->replicate();
        $rejected->user_id = User::factory()->create(['email' => 'rejected-vendor@lens.app'])->id;
        $rejected->display_name = 'Rejected Vendor';
        $rejected->verification_status = 'rejected';
        $rejected->save();

        $this->actingAs($admin);

        Livewire::test(ListVendors::class)
            ->assertSee('Vendor Verified')
            ->assertSee('Vendor Pending')
            ->assertSee('Vendor Rejected')
            ->set('activeTab', 'verified')
            ->assertCanSeeTableRecords([$verified])
            ->assertCanNotSeeTableRecords([$pending, $rejected])
            ->set('activeTab', 'pending')
            ->assertCanSeeTableRecords([$pending])
            ->assertCanNotSeeTableRecords([$verified, $rejected])
            ->set('activeTab', 'rejected')
            ->assertCanSeeTableRecords([$rejected])
            ->assertCanNotSeeTableRecords([$verified, $pending]);

        foreach ([
            ['name' => $verified->display_name],
            ['vendor_type_id' => (string) $verified->vendor_type_id],
            ['city_id' => (string) $verified->city_id],
            ['rating_avg' => (string) $verified->rating_avg],
            ['completed_sessions' => (string) $verified->completed_sessions],
            ['failed_sessions' => (string) $verified->failed_sessions],
            ['date' => $verified->created_at->format('Y-m-d')],
        ] as $search) {
            $component = Livewire::test(ListVendors::class)
                ->callAction('searchVendors', $search)
                ->assertSet('vendorSearch', $search);

            $this->assertTrue(
                $component->instance()->getTableRecords()->contains('id', $verified->id),
                'The modal search did not match '.array_key_first($search).'.',
            );
        }

        Livewire::test(ListVendors::class)
            ->callAction('searchVendors', ['name' => $verified->display_name])
            ->mountAction('searchVendors')
            ->assertActionMounted('searchVendors')
            ->assertActionDataSet(['name' => $verified->display_name])
            ->unmountAction()
            ->assertActionVisible('clearVendorSearch')
            ->callAction('clearVendorSearch')
            ->assertSet('vendorSearch', [])
            ->assertCountTableRecords(Vendor::query()->count())
            ->assertActionHidden('clearVendorSearch');
    }

    public function test_marketplace_loop_is_editable_from_admin(): void
    {
        $admin = User::query()->where('email', 'admin@lens.app')->firstOrFail();
        $client = User::query()->where('email', 'client@lens.app')->firstOrFail();
        $vendor = Vendor::query()->firstOrFail();
        $booking = Booking::query()->where('reference', 'LN-1001')->firstOrFail();

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
            ->assertSee('Project name', false)
            ->assertSee('Map location', false)
            ->assertSee('Payment method', false)
            ->assertSee('Vendor delivery files', false)
            ->assertSee('Latest revision request', false)
            ->assertSee('Revision reference images', false);
    }
}
