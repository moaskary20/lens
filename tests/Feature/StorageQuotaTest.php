<?php

namespace Tests\Feature;

use App\Filament\Pages\PlatformSettings;
use App\Models\Booking;
use App\Models\User;
use App\Models\Vendor;
use App\Support\StorageQuota;
use Database\Seeders\LensSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class StorageQuotaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(LensSeeder::class);
    }

    public function test_platform_settings_show_storage_defaults(): void
    {
        $admin = User::query()->where('email', 'admin@lens.app')->firstOrFail();

        $this->actingAs($admin)
            ->get('/admin/platform-settings')
            ->assertOk()
            ->assertSee('File storage', false)
            ->assertSee('Photographer portfolio', false)
            ->assertSee('Client project files', false)
            ->assertSee('Auto-delete client project files after', false);
    }

    public function test_admin_can_save_category_and_client_project_quotas(): void
    {
        $admin = User::query()->where('email', 'admin@lens.app')->firstOrFail();

        $this->actingAs($admin);

        Livewire::test(PlatformSettings::class)
            ->fillForm([
                'default_portfolio_quota_mb' => 500,
                'portfolio_quota_mb' => ['photographer' => 250],
                'client_project_quota_mb' => 2048,
                'client_project_retention_days' => 7,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(250, StorageQuota::portfolioQuotaMb('photographer'));
        $this->assertSame(500, StorageQuota::portfolioQuotaMb('studio'));
        $this->assertSame(2048, StorageQuota::clientProjectQuotaMb());
        $this->assertSame(7, StorageQuota::retentionDays());
        $this->assertSame(250 * 1024, StorageQuota::portfolioMaxSizeKb('photographer'));
    }

    public function test_expired_client_project_files_are_purged(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('client-projects/old.bin', 'expired-project');

        $client = User::query()->where('role', 'client')->firstOrFail();
        $vendor = Vendor::query()->firstOrFail();
        $booking = Booking::query()->create([
            'reference' => 'LN-PURGE-1',
            'client_id' => $client->id,
            'vendor_id' => $vendor->id,
            'status' => 'completed',
            'approved_at' => now()->subDays(8),
            'session_price' => 1000,
            'total_paid' => 1100,
            'client_project_files' => ['client-projects/old.bin'],
        ]);

        $this->artisan('lens:purge-expired-project-files')->assertSuccessful();

        $this->assertFalse(Storage::disk('public')->exists('client-projects/old.bin'));
        $this->assertSame([], $booking->fresh()->clientProjectPaths());
    }

    public function test_defaults_match_spec(): void
    {
        $this->assertSame(500, StorageQuota::portfolioQuotaMb('photographer'));
        $this->assertSame(2048, StorageQuota::clientProjectQuotaMb());
        $this->assertSame(7, StorageQuota::retentionDays());
    }
}
