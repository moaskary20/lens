<?php

namespace Tests\Feature;

use App\Models\Portfolio;
use App\Models\User;
use App\Models\Vendor;
use App\Support\VendorAppProfile;
use Database\Seeders\LensSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VendorPortfolioProjectsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(LensSeeder::class);
        Storage::fake('public');
    }

    public function test_public_vendor_profile_exposes_grouped_projects_with_cover(): void
    {
        $vendor = User::query()->where('email', 'vendor@lens.app')->firstOrFail()->vendor;
        Portfolio::query()->where('vendor_id', $vendor->id)->delete();

        $cover = UploadedFile::fake()->image('cover.jpg');
        $extra = UploadedFile::fake()->image('extra.jpg');
        $coverPath = $cover->store('portfolios', 'public');
        $extraPath = $extra->store('portfolios', 'public');

        Portfolio::query()->create([
            'vendor_id' => $vendor->id,
            'type' => 'image',
            'path' => $coverPath,
            'title' => 'Yasmin Hall wedding',
            'description' => 'Reception coverage',
            'is_cover' => true,
            'is_featured' => true,
            'sort_order' => 1,
        ]);
        Portfolio::query()->create([
            'vendor_id' => $vendor->id,
            'type' => 'image',
            'path' => $extraPath,
            'title' => 'Yasmin Hall wedding',
            'description' => 'Reception coverage',
            'is_cover' => false,
            'is_featured' => true,
            'sort_order' => 2,
        ]);
        Portfolio::query()->create([
            'vendor_id' => $vendor->id,
            'type' => 'video',
            'path' => UploadedFile::fake()->create('clip.mp4', 200, 'video/mp4')->store('portfolios', 'public'),
            'title' => 'Yasmin Hall wedding',
            'description' => 'Reception coverage',
            'is_cover' => false,
            'is_featured' => true,
            'sort_order' => 3,
        ]);

        $payload = $this->getJson('/api/app/vendors/'.$vendor->id)->assertOk();
        $projects = collect($payload->json('projects'));
        $this->assertCount(1, $projects);
        $this->assertSame('Yasmin Hall wedding', $projects->first()['title']);
        $this->assertSame(3, $projects->first()['media_count']);
        $this->assertNotEmpty($projects->first()['cover_url']);
        $this->assertCount(1, $payload->json('portfolio'));
        $this->assertSame('Yasmin Hall wedding', $payload->json('portfolio.0.title'));
    }

    public function test_admin_project_groups_sync_stores_cover_photos_and_videos(): void
    {
        $vendor = Vendor::query()->where('display_name', 'Fahad Studio Light')->first()
            ?? User::query()->where('email', 'vendor@lens.app')->firstOrFail()->vendor;

        VendorAppProfile::syncAdminProjectGroups($vendor, [
            [
                'title' => 'Brand lookbook',
                'completed_on' => '2026',
                'description' => 'Studio day',
                'is_featured' => true,
                'cover' => UploadedFile::fake()->image('folder-cover.jpg')->store('portfolios', 'public'),
                'images' => [
                    UploadedFile::fake()->image('a.jpg')->store('portfolios', 'public'),
                    UploadedFile::fake()->image('b.jpg')->store('portfolios', 'public'),
                ],
                'videos' => [
                    UploadedFile::fake()->create('reel.mp4', 120, 'video/mp4')->store('portfolios', 'public'),
                ],
            ],
        ]);

        $rows = $vendor->fresh()->portfolios()->orderBy('sort_order')->get();
        $this->assertCount(4, $rows);
        $this->assertTrue((bool) $rows->firstWhere('is_cover', true)?->is_cover);
        $this->assertSame(3, $rows->where('type', 'image')->count());
        $this->assertSame(1, $rows->where('type', 'video')->count());

        $admin = User::query()->where('email', 'admin@lens.app')->firstOrFail();
        $this->actingAs($admin)
            ->get('/admin/vendors/'.$vendor->id.'/edit')
            ->assertOk()
            ->assertSee('Cover image')
            ->assertSee('Project photos')
            ->assertSee('Project videos');
    }
}
