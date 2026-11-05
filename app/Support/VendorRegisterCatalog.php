<?php

namespace App\Support;

use App\Models\PricingModel;
use App\Models\VendorType;
use App\Support\Finance;
use App\Support\Roles;
use App\Support\VendorProfile;

class VendorRegisterCatalog
{
    /**
     * @return list<array<string, mixed>>
     */
    public static function types(): array
    {
        return VendorType::query()
            ->where('is_active', true)
            ->whereIn('slug', Roles::enabledVendorTypeSlugs())
            ->with(['pricingModel.fields' => fn ($query) => $query->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->get()
            ->map(fn (VendorType $type): array => [
                'slug' => $type->slug,
                'label' => $type->name_en,
                'profile_title' => self::profileTitle($type->slug),
                'profile_description' => self::profileDescription($type->slug),
                'fields' => self::fieldsFor($type->slug),
                'pricing_model' => $type->pricingModel?->name_en,
                'pricing_model_id' => $type->pricingModel?->id,
                'pricing_models' => self::modelsFor($type),
                'pricing' => self::pricingFor($type),
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function fieldsFor(string $slug): array
    {
        $fields = [];
        foreach (VendorProfile::filterProfileGroups($slug) as $group) {
            $fields[] = self::multi(
                'filter.'.$group->slug,
                $group->name,
                $group->options->mapWithKeys(fn ($option) => [$option->slug => $option->name_en])->all(),
            );
        }

        return array_merge($fields, self::extraFieldsFor($slug));
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected static function extraFieldsFor(string $slug): array
    {
        $gear = array_map(
            fn (string $key, string $label): array => self::textarea('extras.'.$key, $label),
            array_keys(VendorProfile::freeTextGearFieldsFor($slug)),
            array_values(VendorProfile::freeTextGearFieldsFor($slug)),
        );

        return match ($slug) {
            'photographer' => [
                ...$gear,
                self::multi('specialties', 'Primary specialties', VendorProfile::specialtyOptions()),
            ],
            'videographer' => $gear,
            'reels' => [
                ...$gear,
                self::url('extras.tiktok_url', 'TikTok sample link'),
                self::url('extras.instagram_url', 'Instagram / Reels sample link'),
                self::number('extras.standard_session_hours', 'Standard session length (hours)'),
            ],
            'studio' => $gear,
            'model' => [
                ...$gear,
                self::text('extras.hair_color', 'Hair color'),
                self::text('extras.eye_color', 'Eye color'),
            ],
            'ugc' => $gear,
            'food_stylist' => $gear,
            default => $gear,
        };
    }

    /**
     * @return list<array<string, mixed>>
     */
    /**
     * @return list<array<string, mixed>>
     */
    public static function modelsFor(VendorType $type): array
    {
        return PricingModel::query()
            ->where('is_active', true)
            ->where(function ($query) use ($type): void {
                $query->where('vendor_type_id', $type->id)
                    ->orWhere('id', $type->pricing_model_id);
            })
            ->with(['fields' => fn ($query) => $query->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->get()
            ->map(fn (PricingModel $model): array => [
                'id' => $model->id,
                'slug' => $model->slug,
                'name' => $model->name_en,
                'fields' => self::fieldsFromModel($model, $type->slug),
            ])
            ->values()
            ->all();
    }

    public static function pricingFor(VendorType $type): array
    {
        return $type->pricingModel
            ? self::fieldsFromModel($type->pricingModel, $type->slug)
            : [];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function fieldsFromModel(PricingModel $model, string $slug): array
    {
        $fields = [];
        foreach ($model->fields as $field) {
            $fields[] = self::number(
                $field->statePath(),
                $field->label,
                Finance::currency(),
                $field->helper_text,
            );
        }

        if (in_array($slug, ['videographer', 'reels', 'ugc', 'photographer'], true)) {
            $fields[] = self::number('turnaround_hours', 'Post-production turnaround (hours)');
        }

        return $fields;
    }

    public static function profileTitle(string $slug): string
    {
        return match ($slug) {
            'photographer' => 'Photographer profile',
            'videographer' => 'Videographer profile',
            'reels' => 'Mobile Reels Creator profile',
            'studio' => 'Studio profile',
            'model' => 'Model profile',
            'ugc' => 'UGC Creator profile',
            'food_stylist' => 'Food Stylist profile',
            default => 'Type profile',
        };
    }

    public static function profileDescription(string $slug): string
    {
        return match ($slug) {
            'photographer' => 'Write your camera, lenses, lighting, and photo extras freely — shown on your public vendor profile.',
            'videographer' => 'Write video quality, gear, and delivery formats freely — shown on your public vendor profile.',
            'reels' => 'Write reel quality, gear, and setup freely, then add sample links.',
            'studio' => 'Write features, sets, amenities, and equipment freely. Studio type and area stay as search tags.',
            'model' => 'Write sizes and experience freely. Gender, category, age, and height stay as search tags.',
            'ugc' => 'Write content capabilities and production notes freely. Niche and city stay as search tags.',
            'food_stylist' => 'Write expertise, content type, and add-ons freely. Cuisine and city stay as search tags.',
            default => 'Write your gear and capabilities freely — shown on your public vendor profile.',
        };
    }

    /**
     * @param  array<string, string>  $options
     * @return array<string, mixed>
     */
    protected static function multi(string $key, string $label, array $options): array
    {
        return [
            'key' => $key,
            'type' => 'multi',
            'label' => $label,
            'options' => collect($options)
                ->map(fn (string $label, string $id): array => ['id' => $id, 'label' => $label])
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected static function tags(string $key, string $label, string $placeholder = ''): array
    {
        return ['key' => $key, 'type' => 'tags', 'label' => $label, 'placeholder' => $placeholder];
    }

    /**
     * @return array<string, mixed>
     */
    protected static function text(string $key, string $label, string $placeholder = ''): array
    {
        return ['key' => $key, 'type' => 'text', 'label' => $label, 'placeholder' => $placeholder];
    }

    /**
     * @return array<string, mixed>
     */
    protected static function url(string $key, string $label): array
    {
        return ['key' => $key, 'type' => 'url', 'label' => $label];
    }

    /**
     * @return array<string, mixed>
     */
    protected static function textarea(string $key, string $label): array
    {
        return ['key' => $key, 'type' => 'textarea', 'label' => $label];
    }

    /**
     * @return array<string, mixed>
     */
    protected static function number(string $key, string $label, ?string $prefix = null, ?string $helper = null): array
    {
        return [
            'key' => $key,
            'type' => 'number',
            'label' => $label,
            'prefix' => $prefix,
            'helper' => $helper,
        ];
    }
}
