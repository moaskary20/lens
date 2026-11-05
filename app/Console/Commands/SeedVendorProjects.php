<?php

namespace App\Console\Commands;

use App\Models\Portfolio;
use App\Models\Vendor;
use App\Support\VendorPhotos;
use Illuminate\Console\Command;

class SeedVendorProjects extends Command
{
    protected $signature = 'lens:seed-vendor-projects
        {--force : Replace existing portfolio rows}
        {--only-empty : Skip vendors that already have portfolio media}
        {--projects=3 : Number of projects per vendor}
        {--photos=3 : Photos per project}';

    protected $description = 'Add sample project photo galleries to vendor portfolios';

    public function handle(): int
    {
        $force = (bool) $this->option('force');
        $onlyEmpty = (bool) $this->option('only-empty') || ! $force;
        $projectCount = max(1, (int) $this->option('projects'));
        $photosPerProject = max(1, (int) $this->option('photos'));

        $vendors = Vendor::query()
            ->with(['vendorType', 'portfolios'])
            ->where('is_active', true)
            ->orderBy('id')
            ->get();

        $created = 0;
        $skipped = 0;
        $cleared = 0;

        foreach ($vendors as $vendor) {
            $hasMedia = $vendor->portfolios->contains(
                fn (Portfolio $item): bool => filled($item->path) || filled($item->external_url)
            );

            if ($onlyEmpty && $hasMedia) {
                $skipped++;
                continue;
            }

            if ($force && $hasMedia) {
                $vendor->portfolios()->delete();
                $cleared++;
            }

            $slug = $vendor->vendorType?->slug ?? 'photographer';
            $groups = VendorPhotos::projectGroups($slug, (int) $vendor->id, $projectCount, $photosPerProject);
            $sort = 1;

            foreach ($groups as $groupIndex => $group) {
                foreach ($group['images'] as $imageIndex => $url) {
                    Portfolio::query()->create([
                        'vendor_id' => $vendor->id,
                        'type' => 'image',
                        'path' => $url,
                        'title' => $group['title'],
                        'description' => $group['description'],
                        'completed_on' => $group['completed_on'],
                        'is_featured' => (bool) $group['is_featured'],
                        'is_cover' => $imageIndex === 0,
                        'sort_order' => $sort++,
                    ]);
                    $created++;
                }
            }

            $this->line("Vendor #{$vendor->id} {$vendor->display_name}: ".count($groups)." projects");
        }

        $this->info("Created {$created} portfolio image(s). Skipped {$skipped}. Cleared {$cleared} vendor gallery(ies).");

        return self::SUCCESS;
    }
}
