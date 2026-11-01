<?php

namespace App\Console\Commands;

use App\Models\PricingModel;
use App\Models\PricingModelField;
use App\Models\Vendor;
use App\Models\VendorType;
use App\Support\Pricing;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class SyncVendorPricing extends Command
{
    protected $signature = 'lens:sync-vendor-pricing {--dry-run : Show changes without saving}';

    protected $description = 'Align every vendor type and vendor with the active Pricing Models';

    /**
     * Default pricing model slug for each marketplace vendor type.
     *
     * @var array<string, string>
     */
    protected array $typeModelMap = [
        'photographer' => 'half_full_day',
        'videographer' => 'half_full_day',
        'reels' => 'half_full_day',
        'model' => 'half_full_day',
        'food_stylist' => 'half_full_day',
        'studio' => 'hourly',
        'ugc' => 'per_video',
    ];

    /**
     * @var array<string, list<string>>
     */
    protected array $modelFieldKeys = [
        'half_full_day' => ['half_day_price', 'full_day_price'],
        'hourly' => ['hourly_price'],
        'per_video' => ['per_video_price'],
    ];

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $this->ensurePricingModelsExist($dry);
        $this->alignVendorTypes($dry);
        $updated = $this->alignVendors($dry);

        $this->info(($dry ? '[dry-run] ' : '')."Synced {$updated} vendor(s) to Pricing Models.");

        return self::SUCCESS;
    }

    protected function ensurePricingModelsExist(bool $dry): void
    {
        foreach ($this->modelFieldKeys as $slug => $fieldKeys) {
            $model = PricingModel::query()->where('slug', $slug)->first();
            if ($model) {
                $this->ensureModelFields($model, $fieldKeys, $dry);

                continue;
            }

            $this->line(($dry ? '[dry-run] ' : '')."Creating pricing model {$slug}");
            if ($dry) {
                continue;
            }

            $model = PricingModel::query()->create([
                'slug' => $slug,
                'name_en' => match ($slug) {
                    'half_full_day' => 'Half day / full day',
                    'hourly' => 'Per hour per location',
                    'per_video' => 'Per video',
                    default => Str::headline($slug),
                },
                'is_active' => true,
                'sort_order' => match ($slug) {
                    'half_full_day' => 0,
                    'hourly' => 1,
                    'per_video' => 2,
                    default => 99,
                },
            ]);
            $this->ensureModelFields($model, $fieldKeys, false);
        }
    }

    /**
     * @param  list<string>  $fieldKeys
     */
    protected function ensureModelFields(PricingModel $model, array $fieldKeys, bool $dry): void
    {
        foreach ($fieldKeys as $index => $key) {
            $template = Pricing::template($key);
            if (! $template) {
                continue;
            }

            $exists = $model->fields()
                ->where(function ($query) use ($key, $template): void {
                    $query->where('key', $key)
                        ->orWhere('package_type', $template['package_type'])
                        ->orWhere('storage_key', $template['storage_key']);
                })
                ->exists();

            if ($exists) {
                continue;
            }

            $this->line(($dry ? '[dry-run] ' : '')."Adding field {$key} to {$model->slug}");
            if ($dry) {
                continue;
            }

            $model->fields()->create([
                'key' => $key,
                'storage_key' => $template['storage_key'],
                'label' => $template['label'],
                'package_type' => $template['package_type'],
                'unit' => $template['unit'],
                'duration_hours' => $template['duration_hours'],
                'helper_text' => $template['helper_text'],
                'sort_order' => $index,
            ]);
        }
    }

    protected function alignVendorTypes(bool $dry): void
    {
        $models = PricingModel::query()->where('is_active', true)->get()->keyBy('slug');

        foreach (VendorType::query()->orderBy('sort_order')->get() as $type) {
            $slug = $this->typeModelMap[$type->slug] ?? null;
            if (! $slug) {
                continue;
            }

            $model = $models->get($slug) ?: PricingModel::query()->where('slug', $slug)->first();
            if (! $model) {
                $this->warn("No pricing model {$slug} for vendor type {$type->slug}");

                continue;
            }

            $needs = (int) $type->pricing_model_id !== (int) $model->id
                || $type->pricing_model !== $model->slug;

            if (! $needs) {
                continue;
            }

            $this->line(($dry ? '[dry-run] ' : '')."Type {$type->slug} → model {$model->slug}");
            if ($dry) {
                continue;
            }

            $type->update([
                'pricing_model_id' => $model->id,
                'pricing_model' => $model->slug,
            ]);
        }

        $canonicalSlugs = array_values(array_unique($this->typeModelMap));

        if (! $dry) {
            PricingModel::query()
                ->whereNotIn('slug', $canonicalSlugs)
                ->whereNotNull('vendor_type_id')
                ->update(['vendor_type_id' => null]);
        }

        // Keep Filament "owned by vendor type" pointer populated for each canonical model.
        foreach ($models as $model) {
            if (! in_array($model->slug, $canonicalSlugs, true)) {
                continue;
            }

            $ownerSlug = match ($model->slug) {
                'hourly' => 'studio',
                'per_video' => 'ugc',
                default => 'photographer',
            };
            $ownerId = VendorType::query()->where('slug', $ownerSlug)->value('id');
            if (! $ownerId || (int) $model->vendor_type_id === (int) $ownerId) {
                continue;
            }
            if ($dry) {
                $this->line("[dry-run] Model {$model->slug} owner → {$ownerSlug}");

                continue;
            }
            $model->update(['vendor_type_id' => $ownerId]);
        }
    }

    protected function alignVendors(bool $dry): int
    {
        $updated = 0;

        Vendor::query()
            ->with(['vendorType.pricingModel.fields'])
            ->orderBy('id')
            ->each(function (Vendor $vendor) use ($dry, &$updated): void {
                $model = $vendor->resolvedPricingModel();
                if (! $model) {
                    $this->warn("Vendor #{$vendor->id} {$vendor->display_name} has no pricing model");

                    return;
                }

                $typeSlug = $vendor->vendorType?->slug;
                $protected = $this->expectedColumnsForVendorType($typeSlug);
                $allowed = $model->fields
                    ->map(fn (PricingModelField $field): ?string => $this->vendorColumnForField($field))
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();

                $changes = [];
                foreach (PricingModelField::VENDOR_COLUMNS as $column) {
                    if (in_array($column, $allowed, true) || in_array($column, $protected, true)) {
                        continue;
                    }
                    if ($vendor->{$column} !== null) {
                        $changes[$column] = null;
                    }
                }

                $columnsToFill = array_values(array_unique(array_merge(
                    $allowed,
                    $protected,
                )));

                foreach ($columnsToFill as $column) {
                    if (! in_array($column, PricingModelField::VENDOR_COLUMNS, true)) {
                        continue;
                    }
                    if ($vendor->{$column} !== null && (float) $vendor->{$column} > 0) {
                        continue;
                    }
                    $fallback = $this->fallbackAmount($vendor, $column);
                    if ($fallback !== null) {
                        $changes[$column] = $fallback;
                    }
                }

                $extras = is_array($vendor->extras) ? $vendor->extras : [];
                if ((int) ($extras['pricing_model_id'] ?? 0) !== (int) $model->id) {
                    $extras['pricing_model_id'] = $model->id;
                    $changes['extras'] = $extras;
                }

                if ($changes === []) {
                    return;
                }

                $updated++;
                $summary = collect($changes)
                    ->except('extras')
                    ->map(fn ($value, $key) => "{$key}=".($value === null ? 'null' : $value))
                    ->implode(', ');
                $this->line(($dry ? '[dry-run] ' : '')."#{$vendor->id} {$vendor->display_name}: {$summary}".(isset($changes['extras']) ? ' +extras.pricing_model_id' : ''));

                if (! $dry) {
                    $vendor->update($changes);
                }
            });

        return $updated;
    }

    protected function fallbackAmount(Vendor $vendor, string $column): ?float
    {
        $starting = $vendor->starting_from !== null && $vendor->starting_from !== ''
            ? (float) $vendor->starting_from
            : null;
        $half = $vendor->half_day_price !== null ? (float) $vendor->half_day_price : null;
        $full = $vendor->full_day_price !== null ? (float) $vendor->full_day_price : null;
        $hourly = $vendor->hourly_price !== null ? (float) $vendor->hourly_price : null;
        $video = $vendor->per_video_price !== null ? (float) $vendor->per_video_price : null;

        return match ($column) {
            'half_day_price' => $half ?: ($full ? round($full / 1.7, 2) : ($starting ?: ($hourly ? round($hourly * 6, 2) : 1500.0))),
            'full_day_price' => $full ?: ($half ? round($half * 1.7, 2) : ($starting ? round($starting * 1.7, 2) : ($hourly ? round($hourly * 12, 2) : 2550.0))),
            'hourly_price' => $hourly ?: ($half ? round($half / 6, 2) : 250.0),
            'per_video_price' => $video ?: 800.0,
            default => null,
        };
    }

    /**
     * @return list<string>
     */
    protected function expectedColumnsForVendorType(?string $typeSlug): array
    {
        $modelSlug = $this->typeModelMap[$typeSlug ?? ''] ?? null;

        return $modelSlug ? ($this->modelFieldKeys[$modelSlug] ?? []) : [];
    }

    protected function vendorColumnForField(PricingModelField $field): ?string
    {
        $column = $field->storageColumn();
        if (in_array($column, PricingModelField::VENDOR_COLUMNS, true)) {
            return $column;
        }

        return match ($field->package_type) {
            'half_day' => 'half_day_price',
            'full_day' => 'full_day_price',
            'hourly' => 'hourly_price',
            'per_video' => 'per_video_price',
            default => null,
        };
    }
}
