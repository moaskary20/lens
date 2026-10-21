<?php

namespace App\Support;

use App\Models\FilterTag;
use App\Models\Vendor;
use App\Models\VendorType;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class VendorAppProfile
{
    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [
            'vendor_type' => ['nullable', 'string'],
            'display_name' => ['nullable', 'string', 'max:255'],
            'date_of_birth' => ['nullable', 'date'],
            'profession' => ['nullable', 'string', 'max:255'],
            'bio' => ['nullable', 'string'],
            'contact_phone' => ['nullable', Egypt::mobileRule()],
            'contact_email' => ['nullable', 'email'],
            'whatsapp' => ['nullable', Egypt::mobileRule()],
            'instagram' => ['nullable', 'string', 'max:120'],
            'address' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'profile_photo' => ['nullable', 'image', 'max:4096'],
            'national_id_image' => ['nullable', 'image', 'max:4096'],
            'cover_image' => ['nullable', 'image', 'max:8192'],
            'payout_method' => ['nullable', Rule::in(['bank', 'wallet', 'paypal'])],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'bank_account_holder' => ['nullable', 'string', 'max:255'],
            'bank_account_number' => ['nullable', 'string', 'max:64'],
            'bank_iban' => ['nullable', 'string', 'max:64'],
            'bank_swift' => ['nullable', 'string', 'max:32'],
            'bank_branch' => ['nullable', 'string', 'max:255'],
            'bank_branch_code' => ['nullable', 'string', 'max:32'],
            'bank_account_type' => ['nullable', Rule::in(['current', 'savings'])],
            'wallet_network_type' => ['nullable', Rule::in(['telecom', 'bank'])],
            'wallet_telecom' => ['nullable', 'string', 'max:64'],
            'wallet_bank_name' => ['nullable', 'string', 'max:255'],
            'wallet_phone' => ['nullable', Egypt::mobileRule()],
            'paypal_email' => ['nullable', 'email'],
            'paypal_name' => ['nullable', 'string', 'max:255'],
            'transfer_notes' => ['nullable', 'string'],
            'specialties' => ['nullable', 'array'],
            'specialties.*' => ['string', 'max:64'],
            'filter_tags' => ['nullable', 'array'],
            'filter_tags.*' => ['string', 'max:80'],
            'delivery_formats' => ['nullable', 'array'],
            'delivery_formats.*' => ['string', 'max:64'],
            'extras' => ['nullable', 'array'],
            'half_day_price' => ['nullable', 'numeric', 'min:0'],
            'full_day_price' => ['nullable', 'numeric', 'min:0'],
            'hourly_price' => ['nullable', 'numeric', 'min:0'],
            'per_video_price' => ['nullable', 'numeric', 'min:0'],
            'turnaround_hours' => ['nullable', 'numeric', 'min:1'],
            'pricing_model_id' => ['nullable', 'integer', 'exists:pricing_models,id'],
            'projects' => ['nullable', 'array', 'max:20'],
            'projects.*.ids' => ['nullable', 'array'],
            'projects.*.ids.*' => ['integer'],
            'projects.*.type' => ['required_with:projects', Rule::in(['image', 'video', 'link'])],
            'projects.*.title' => ['required_with:projects', 'string', 'max:255'],
            'projects.*.completed_on' => ['nullable', 'string', 'max:40'],
            'projects.*.external_url' => ['nullable', 'url', 'max:500'],
            'projects.*.description' => ['nullable', 'string'],
            'projects.*.is_featured' => ['nullable', 'boolean'],
            'projects.*.files' => ['nullable', 'array', 'max:20'],
            'projects.*.files.*' => ['file', 'max:51200'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function present(?Vendor $vendor): ?array
    {
        if (! $vendor) {
            return null;
        }

        $vendor->loadMissing(['vendorType', 'city', 'filterTags', 'portfolios']);
        $extras = is_array($vendor->extras) ? $vendor->extras : [];

        return [
            'id' => $vendor->id,
            'vendor_type' => $vendor->vendorType?->slug,
            'vendor_type_label' => $vendor->vendorType?->name_en,
            'display_name' => $vendor->display_name,
            'date_of_birth' => $vendor->date_of_birth?->toDateString(),
            'profession' => $vendor->profession,
            'bio' => $vendor->bio,
            'contact_phone' => $vendor->contact_phone,
            'contact_email' => $vendor->contact_email,
            'whatsapp' => $vendor->whatsapp,
            'instagram' => $vendor->instagram,
            'address' => $vendor->address,
            'latitude' => self::number($vendor->latitude),
            'longitude' => self::number($vendor->longitude),
            'profile_photo' => self::publicUrl($vendor->profile_photo),
            'national_id_image' => self::publicUrl($vendor->national_id_image),
            'cover_image' => self::publicUrl($vendor->cover_image),
            'city_id' => $vendor->city_id,
            'specialties' => array_values($vendor->specialties ?? []),
            'delivery_formats' => array_values($vendor->delivery_formats ?? []),
            'filter_tags' => $vendor->filterTags->pluck('slug')->values()->all(),
            'extras' => $extras,
            'half_day_price' => self::number($vendor->half_day_price),
            'full_day_price' => self::number($vendor->full_day_price),
            'hourly_price' => self::number($vendor->hourly_price),
            'per_video_price' => self::number($vendor->per_video_price),
            'turnaround_hours' => self::number($vendor->turnaround_hours),
            'pricing_model_id' => $extras['pricing_model_id'] ?? $vendor->vendorType?->pricing_model_id,
            'payout_method' => $vendor->payout_method,
            'bank_name' => $vendor->bank_name,
            'bank_account_holder' => $vendor->bank_account_holder,
            'bank_account_number' => $vendor->bank_account_number,
            'bank_iban' => $vendor->bank_iban,
            'bank_swift' => $vendor->bank_swift,
            'bank_branch' => $vendor->bank_branch,
            'bank_branch_code' => $vendor->bank_branch_code,
            'bank_account_type' => $vendor->bank_account_type,
            'wallet_network_type' => $vendor->wallet_network_type,
            'wallet_telecom' => $vendor->wallet_telecom,
            'wallet_bank_name' => $vendor->wallet_bank_name,
            'wallet_phone' => $vendor->wallet_phone,
            'paypal_email' => $vendor->paypal_email,
            'paypal_name' => $vendor->paypal_name,
            'transfer_notes' => $vendor->transfer_notes,
            'verification_status' => $vendor->verification_status,
            'projects' => self::presentProjects($vendor),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function apply(Request $request, Vendor $vendor, array $data): void
    {
        if (! empty($data['vendor_type'])) {
            $typeId = VendorType::query()->where('slug', $data['vendor_type'])->value('id');
            if ($typeId) {
                $vendor->vendor_type_id = $typeId;
            }
        }

        $extras = array_replace_recursive(
            is_array($vendor->extras) ? $vendor->extras : [],
            is_array($data['extras'] ?? null) ? $data['extras'] : [],
        );
        if (! empty($data['pricing_model_id'])) {
            $extras['pricing_model_id'] = (int) $data['pricing_model_id'];
        }

        $vendor->fill([
            'display_name' => ($data['display_name'] ?? null) ?: $vendor->display_name,
            'date_of_birth' => $data['date_of_birth'] ?? $vendor->date_of_birth,
            'profession' => $data['profession'] ?? $vendor->profession,
            'bio' => $data['bio'] ?? $vendor->bio,
            'contact_phone' => $data['contact_phone'] ?? $vendor->contact_phone,
            'contact_email' => $data['contact_email'] ?? $vendor->contact_email,
            'whatsapp' => $data['whatsapp'] ?? $vendor->whatsapp,
            'instagram' => $data['instagram'] ?? $vendor->instagram,
            'address' => $data['address'] ?? $vendor->address,
            'latitude' => $data['latitude'] ?? $vendor->latitude,
            'longitude' => $data['longitude'] ?? $vendor->longitude,
            'city_id' => $data['city_id'] ?? $vendor->city_id,
            'specialties' => $data['specialties'] ?? $vendor->specialties,
            'delivery_formats' => $data['delivery_formats'] ?? $vendor->delivery_formats,
            'extras' => $extras,
            'half_day_price' => $data['half_day_price'] ?? $vendor->half_day_price,
            'full_day_price' => $data['full_day_price'] ?? $vendor->full_day_price,
            'hourly_price' => $data['hourly_price'] ?? $vendor->hourly_price,
            'per_video_price' => $data['per_video_price'] ?? $vendor->per_video_price,
            'turnaround_hours' => $data['turnaround_hours'] ?? $vendor->turnaround_hours,
            'payout_method' => $data['payout_method'] ?? $vendor->payout_method,
            'bank_name' => $data['bank_name'] ?? $vendor->bank_name,
            'bank_account_holder' => $data['bank_account_holder'] ?? $vendor->bank_account_holder,
            'bank_account_number' => $data['bank_account_number'] ?? $vendor->bank_account_number,
            'bank_iban' => $data['bank_iban'] ?? $vendor->bank_iban,
            'bank_swift' => $data['bank_swift'] ?? $vendor->bank_swift,
            'bank_branch' => $data['bank_branch'] ?? $vendor->bank_branch,
            'bank_branch_code' => $data['bank_branch_code'] ?? $vendor->bank_branch_code,
            'bank_account_type' => $data['bank_account_type'] ?? $vendor->bank_account_type,
            'wallet_network_type' => $data['wallet_network_type'] ?? $vendor->wallet_network_type,
            'wallet_telecom' => $data['wallet_telecom'] ?? $vendor->wallet_telecom,
            'wallet_bank_name' => $data['wallet_bank_name'] ?? $vendor->wallet_bank_name,
            'wallet_phone' => $data['wallet_phone'] ?? $vendor->wallet_phone,
            'paypal_email' => $data['paypal_email'] ?? $vendor->paypal_email,
            'paypal_name' => $data['paypal_name'] ?? $vendor->paypal_name,
            'transfer_notes' => $data['transfer_notes'] ?? $vendor->transfer_notes,
        ]);
        self::storeIdentityFiles($request, $vendor);
        $vendor->save();

        if (array_key_exists('filter_tags', $data)) {
            self::syncFilterTags($vendor, $data['filter_tags'] ?? []);
        }

        if (array_key_exists('projects', $data)) {
            self::syncProjects($request, $vendor, $data['projects'] ?? []);
        }
    }

    /**
     * @param  list<string>  $slugs
     */
    public static function syncFilterTags(Vendor $vendor, array $slugs): void
    {
        $ids = FilterTag::query()
            ->where('is_active', true)
            ->whereIn('slug', array_values(array_filter($slugs)))
            ->pluck('id')
            ->all();

        $vendor->filterTags()->sync($ids);
    }

    /**
     * @param  list<array<string, mixed>>  $projects
     */
    public static function syncProjects(Request $request, Vendor $vendor, array $projects): void
    {
        $keep = [];

        foreach (array_values($projects) as $index => $project) {
            $ids = collect($project['ids'] ?? [])
                ->map(fn ($id): int => (int) $id)
                ->filter(fn (int $id): bool => $id > 0)
                ->values()
                ->all();
            $meta = [
                'type' => $project['type'],
                'title' => $project['title'],
                'completed_on' => $project['completed_on'] ?? null,
                'external_url' => $project['external_url'] ?? null,
                'description' => $project['description'] ?? null,
                'is_featured' => (bool) ($project['is_featured'] ?? false),
            ];

            foreach ($ids as $order => $id) {
                $row = $vendor->portfolios()->whereKey($id)->first();
                if (! $row) {
                    continue;
                }
                $row->fill($meta);
                $row->sort_order = ($index * 100) + $order;
                $row->save();
                $keep[] = $row->id;
            }

            $uploads = $request->file('projects.'.$index.'.files') ?? [];
            if ($uploads instanceof UploadedFile) {
                $uploads = [$uploads];
            }

            foreach (array_values($uploads) as $order => $file) {
                if (! $file instanceof UploadedFile) {
                    continue;
                }
                $created = $vendor->portfolios()->create([
                    ...$meta,
                    'path' => $file->store('portfolios', 'public'),
                    'sort_order' => ($index * 100) + 50 + $order,
                ]);
                $keep[] = $created->id;
            }

            if ($ids === [] && $uploads === []) {
                $created = $vendor->portfolios()->create([
                    ...$meta,
                    'path' => '',
                    'sort_order' => $index,
                ]);
                $keep[] = $created->id;
            }
        }

        $query = $vendor->portfolios();
        if ($keep !== []) {
            $query->whereNotIn('id', $keep);
        }
        $query->get()->each(function ($item): void {
            if (filled($item->path)) {
                Storage::disk('public')->delete($item->path);
            }
            $item->delete();
        });
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected static function presentProjects(Vendor $vendor): array
    {
        $groups = [];
        foreach ($vendor->portfolios->sortBy('sort_order') as $item) {
            $key = implode('|', [
                (string) $item->type,
                (string) $item->title,
                (string) $item->completed_on,
                (string) $item->external_url,
                $item->is_featured ? '1' : '0',
            ]);
            if (! isset($groups[$key])) {
                $groups[$key] = [
                    'ids' => [],
                    'type' => $item->type,
                    'title' => $item->title,
                    'completed_on' => $item->completed_on,
                    'external_url' => $item->external_url,
                    'description' => $item->description,
                    'is_featured' => (bool) $item->is_featured,
                    'files' => [],
                ];
            }
            $groups[$key]['ids'][] = $item->id;
            if (filled($item->path)) {
                $groups[$key]['files'][] = [
                    'id' => $item->id,
                    'name' => basename((string) $item->path),
                    'url' => Storage::disk('public')->url($item->path),
                ];
            }
        }

        return array_values($groups);
    }

    public static function storeIdentityFiles(Request $request, Vendor $vendor): void
    {
        self::storeImage($request, $vendor, 'profile_photo', 'vendors/photos');
        self::storeImage($request, $vendor, 'national_id_image', 'vendors/ids');
        self::storeImage($request, $vendor, 'cover_image', 'vendors/covers');
    }

    protected static function storeImage(Request $request, Vendor $vendor, string $field, string $directory): void
    {
        if (! $request->hasFile($field)) {
            return;
        }

        $current = $vendor->{$field};
        if (filled($current) && is_string($current) && ! str_starts_with($current, 'http://') && ! str_starts_with($current, 'https://')) {
            Storage::disk('public')->delete($current);
        }

        $vendor->{$field} = $request->file($field)->store($directory, 'public');
    }

    protected static function publicUrl(?string $path): ?string
    {
        if (! filled($path)) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return asset('storage/'.$path);
    }

    protected static function number(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (float) $value;
    }
}
