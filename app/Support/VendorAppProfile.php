<?php

namespace App\Support;

use App\Models\FilterTag;
use App\Models\Portfolio;
use App\Models\Vendor;
use App\Models\VendorAvailability;
use App\Models\VendorType;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class VendorAppProfile
{
    /**
     * @return array<string, mixed>
     */
    public static function rules(bool $required = false): array
    {
        $req = $required ? 'required' : 'nullable';

        return [
            'vendor_type' => [$req, 'string'],
            'display_name' => [$req, 'string', 'max:255'],
            'date_of_birth' => [$req, 'date'],
            'profession' => [$req, 'string', 'max:255'],
            'years_experience' => [$req, 'integer', 'min:0', 'max:80'],
            'bio' => [$req, 'string'],
            'contact_phone' => ['nullable', Egypt::mobileRule()],
            'contact_email' => [$req, 'email'],
            'whatsapp' => [$req, 'string', Egypt::mobileRule()],
            'instagram' => [$req, 'string', 'max:255'],
            'instagram_followers' => [$req, 'integer', 'min:0'],
            'tiktok_url' => [$req, 'string', 'max:255'],
            'tiktok_followers' => [$req, 'integer', 'min:0'],
            'facebook_url' => [$req, 'string', 'max:255'],
            'facebook_followers' => [$req, 'integer', 'min:0'],
            'address' => [$req, 'string', 'max:255'],
            'latitude' => [$req, 'numeric', 'between:-90,90'],
            'longitude' => [$req, 'numeric', 'between:-180,180'],
            'city_id' => [$req, 'integer', 'exists:cities,id'],
            'profile_photo' => ['nullable', 'image', 'max:4096'],
            'national_id_image' => ['nullable', 'image', 'max:4096'],
            'cover_image' => ['nullable', 'image', 'max:8192'],
            'payout_method' => [$req, Rule::in(['bank', 'wallet', 'paypal'])],
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
            'availabilities' => ['nullable', 'array', 'max:120'],
            'availabilities.*.id' => ['nullable', 'integer'],
            'availabilities.*.starts_at' => ['required', 'date'],
            'availabilities.*.ends_at' => ['required', 'date'],
            'availabilities.*.status' => ['nullable', Rule::in(['open', 'blocked', 'booked'])],
            'availabilities.*.notes' => ['nullable', 'string', 'max:1000'],
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

        $vendor->loadMissing(['vendorType', 'city', 'filterTags', 'portfolios', 'availabilities']);
        $extras = is_array($vendor->extras) ? $vendor->extras : [];

        return [
            'id' => $vendor->id,
            'vendor_type' => $vendor->vendorType?->slug,
            'vendor_type_label' => $vendor->vendorType?->name_en,
            'display_name' => $vendor->display_name,
            'date_of_birth' => $vendor->date_of_birth?->toDateString(),
            'profession' => $vendor->profession,
            'years_experience' => (int) ($vendor->years_experience ?? 0),
            'completed_sessions' => (int) ($vendor->completed_sessions ?? 0),
            'assisted_sessions' => (int) ($vendor->assisted_sessions ?? 0),
            'client_satisfaction' => (int) ($vendor->client_satisfaction ?? 0),
            'bio' => $vendor->bio,
            'contact_phone' => $vendor->contact_phone,
            'contact_email' => $vendor->contact_email,
            'whatsapp' => $vendor->whatsapp,
            'instagram' => $vendor->instagram,
            'instagram_followers' => $vendor->instagram_followers,
            'tiktok_url' => $vendor->tiktok_url,
            'tiktok_followers' => $vendor->tiktok_followers,
            'facebook_url' => $vendor->facebook_url,
            'facebook_followers' => $vendor->facebook_followers,
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
            'availabilities' => self::presentAvailabilities($vendor),
            'calendar_title' => VendorProfile::calendarTitle($vendor->vendorType?->slug),
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
            'years_experience' => array_key_exists('years_experience', $data)
                ? max(0, (int) $data['years_experience'])
                : $vendor->years_experience,
            'bio' => $data['bio'] ?? $vendor->bio,
            'contact_phone' => $data['contact_phone'] ?? $vendor->contact_phone,
            'contact_email' => $data['contact_email'] ?? $vendor->contact_email,
            'whatsapp' => $data['whatsapp'] ?? $vendor->whatsapp,
            'instagram' => array_key_exists('instagram', $data) ? $data['instagram'] : $vendor->instagram,
            'instagram_followers' => array_key_exists('instagram_followers', $data) ? $data['instagram_followers'] : $vendor->instagram_followers,
            'tiktok_url' => array_key_exists('tiktok_url', $data) ? $data['tiktok_url'] : $vendor->tiktok_url,
            'tiktok_followers' => array_key_exists('tiktok_followers', $data) ? $data['tiktok_followers'] : $vendor->tiktok_followers,
            'facebook_url' => array_key_exists('facebook_url', $data) ? $data['facebook_url'] : $vendor->facebook_url,
            'facebook_followers' => array_key_exists('facebook_followers', $data) ? $data['facebook_followers'] : $vendor->facebook_followers,
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

        if (array_key_exists('availabilities', $data)) {
            self::syncAvailabilities($vendor, $data['availabilities'] ?? []);
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
            $projectType = (string) ($project['type'] ?? 'image');
            $meta = [
                'title' => $project['title'],
                'completed_on' => $project['completed_on'] ?? null,
                'external_url' => $project['external_url'] ?? null,
                'description' => $project['description'] ?? null,
                'is_featured' => (bool) ($project['is_featured'] ?? false),
            ];
            $coverId = (int) ($project['cover_id'] ?? 0);
            $projectKeep = [];

            foreach ($ids as $order => $id) {
                $row = $vendor->portfolios()->whereKey($id)->first();
                if (! $row) {
                    continue;
                }
                $row->fill([
                    ...$meta,
                    'type' => $row->type ?: $projectType,
                    'is_cover' => $coverId > 0 ? $row->id === $coverId : $order === 0,
                    'sort_order' => ($index * 100) + $order,
                ]);
                $row->save();
                $keep[] = $row->id;
                $projectKeep[] = $row->id;
            }

            $uploads = $request->file('projects.'.$index.'.files') ?? [];
            if ($uploads instanceof UploadedFile) {
                $uploads = [$uploads];
            }

            foreach (array_values($uploads) as $order => $file) {
                if (! $file instanceof UploadedFile) {
                    continue;
                }
                $mime = (string) $file->getMimeType();
                $type = str_starts_with($mime, 'video/') ? 'video' : ($projectType === 'link' ? 'image' : $projectType);
                $created = $vendor->portfolios()->create([
                    ...$meta,
                    'type' => in_array($type, ['image', 'video', 'link'], true) ? $type : 'image',
                    'path' => $file->store('portfolios', 'public'),
                    'is_cover' => false,
                    'sort_order' => ($index * 100) + 50 + $order,
                ]);
                $keep[] = $created->id;
                $projectKeep[] = $created->id;
            }

            if ($projectKeep === [] && ($projectType === 'link' || filled($meta['external_url'] ?? null))) {
                $created = $vendor->portfolios()->create([
                    ...$meta,
                    'type' => 'link',
                    'path' => '',
                    'is_cover' => true,
                    'sort_order' => $index * 100,
                ]);
                $keep[] = $created->id;
                $projectKeep[] = $created->id;
            }

            if ($projectKeep !== [] && $coverId <= 0) {
                $first = $vendor->portfolios()->whereKey($projectKeep)->orderBy('sort_order')->first();
                if ($first && ! $first->is_cover) {
                    $vendor->portfolios()->whereIn('id', $projectKeep)->update(['is_cover' => false]);
                    $first->forceFill(['is_cover' => true])->save();
                }
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
     * Sync open/blocked calendar slots. Booked slots stay locked and are never deleted here.
     *
     * @param  list<array<string, mixed>>  $slots
     */
    public static function syncAvailabilities(Vendor $vendor, array $slots): void
    {
        $keep = $vendor->availabilities()
            ->where('status', 'booked')
            ->pluck('id')
            ->all();

        foreach (array_values($slots) as $index => $slot) {
            if (! is_array($slot)) {
                continue;
            }

            $id = (int) ($slot['id'] ?? 0);
            $status = (string) ($slot['status'] ?? 'open');
            if (! in_array($status, ['open', 'blocked', 'booked'], true)) {
                $status = 'open';
            }

            if ($id > 0) {
                $row = $vendor->availabilities()->whereKey($id)->first();
                if (! $row) {
                    continue;
                }
                if ($row->status === 'booked') {
                    $keep[] = $row->id;

                    continue;
                }
                if ($status === 'booked') {
                    throw ValidationException::withMessages([
                        "availabilities.$index.status" => 'Booked slots can only be created by a booking.',
                    ]);
                }
                $row->fill([
                    'starts_at' => $slot['starts_at'],
                    'ends_at' => $slot['ends_at'],
                    'status' => $status,
                    'notes' => $slot['notes'] ?? null,
                ]);
                if ($row->ends_at <= $row->starts_at) {
                    throw ValidationException::withMessages([
                        "availabilities.$index.ends_at" => 'Ends must be after starts.',
                    ]);
                }
                $row->save();
                $keep[] = $row->id;

                continue;
            }

            if ($status === 'booked') {
                throw ValidationException::withMessages([
                    "availabilities.$index.status" => 'New slots must be open or blocked.',
                ]);
            }

            $created = $vendor->availabilities()->create([
                'starts_at' => $slot['starts_at'],
                'ends_at' => $slot['ends_at'],
                'status' => $status === 'blocked' ? 'blocked' : 'open',
                'notes' => $slot['notes'] ?? null,
            ]);
            if ($created->ends_at <= $created->starts_at) {
                $created->delete();
                throw ValidationException::withMessages([
                    "availabilities.$index.ends_at" => 'Ends must be after starts.',
                ]);
            }
            $keep[] = $created->id;
        }

        $vendor->availabilities()
            ->whereIn('status', ['open', 'blocked'])
            ->when($keep !== [], fn ($query) => $query->whereNotIn('id', $keep))
            ->delete();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function presentAvailabilities(Vendor $vendor): array
    {
        return $vendor->availabilities
            ->sortBy('starts_at')
            ->values()
            ->map(fn (VendorAvailability $slot): array => [
                'id' => $slot->id,
                'starts_at' => $slot->starts_at?->toIso8601String(),
                'ends_at' => $slot->ends_at?->toIso8601String(),
                'status' => $slot->status,
                'notes' => $slot->notes,
                'locked' => $slot->status === 'booked',
            ])
            ->all();
    }

    /**
     * Group portfolio rows into projects (same title + completed_on + description).
     *
     * @return list<array<string, mixed>>
     */
    public static function presentProjects(Vendor $vendor): array
    {
        $vendor->loadMissing('portfolios');
        $groups = [];
        foreach ($vendor->portfolios->sortBy('sort_order') as $item) {
            $key = implode('|', [
                mb_strtolower(trim((string) $item->title)),
                (string) $item->completed_on,
                (string) $item->description,
                $item->is_featured ? '1' : '0',
            ]);
            if (! isset($groups[$key])) {
                $groups[$key] = [
                    'id' => $item->id,
                    'ids' => [],
                    'type' => $item->type,
                    'title' => $item->title ?: 'Featured work',
                    'completed_on' => $item->completed_on,
                    'external_url' => $item->external_url,
                    'description' => $item->description,
                    'is_featured' => (bool) $item->is_featured,
                    'cover_url' => null,
                    'cover_id' => null,
                    'files' => [],
                    'media_count' => 0,
                    'photo_count' => 0,
                    'video_count' => 0,
                ];
            }
            $groups[$key]['ids'][] = $item->id;
            $kind = self::mediaKind($item);
            $url = filled($item->path) ? self::publicUrl($item->path) : null;
            if ($url || $kind === 'link') {
                $file = [
                    'id' => $item->id,
                    'name' => filled($item->path) ? basename((string) $item->path) : ($item->title ?: 'Link'),
                    'url' => $url,
                    'path' => filled($item->path) ? (string) $item->path : null,
                    'type' => $item->type,
                    'kind' => $kind,
                    'is_cover' => (bool) $item->is_cover,
                    'external_url' => $item->external_url,
                ];
                $groups[$key]['files'][] = $file;
                $groups[$key]['media_count']++;
                if ($kind === 'image') {
                    $groups[$key]['photo_count']++;
                }
                if ($kind === 'video') {
                    $groups[$key]['video_count']++;
                }
                if ($item->is_cover && $url) {
                    $groups[$key]['cover_url'] = $url;
                    $groups[$key]['cover_id'] = $item->id;
                }
            }
            if ($item->external_url && blank($groups[$key]['external_url'])) {
                $groups[$key]['external_url'] = $item->external_url;
            }
        }

        foreach ($groups as &$group) {
            if (! $group['cover_url']) {
                $cover = collect($group['files'])->firstWhere('kind', 'image')
                    ?? collect($group['files'])->firstWhere('kind', 'video')
                    ?? collect($group['files'])->first();
                if ($cover) {
                    $group['cover_url'] = $cover['url'] ?? null;
                    $group['cover_id'] = $cover['id'] ?? null;
                }
            }
            $kinds = collect($group['files'])->pluck('kind')->unique()->values();
            $group['type'] = $kinds->contains('video') && $kinds->contains('image')
                ? 'gallery'
                : ($kinds->first() ?: $group['type']);
        }
        unset($group);

        return array_values($groups);
    }

    /**
     * Sync Filament admin / vendor-panel project groups (cover + many photos/videos).
     *
     * @param  list<array<string, mixed>>  $groups
     */
    public static function syncAdminProjectGroups(Vendor $vendor, array $groups): void
    {
        $keep = [];

        foreach (array_values($groups) as $index => $group) {
            if (! is_array($group)) {
                continue;
            }
            $title = trim((string) ($group['title'] ?? ''));
            if ($title === '') {
                continue;
            }

            $meta = [
                'title' => $title,
                'completed_on' => $group['completed_on'] ?? null,
                'description' => $group['description'] ?? null,
                'external_url' => $group['external_url'] ?? null,
                'is_featured' => (bool) ($group['is_featured'] ?? false),
            ];

            $cover = self::normalizeUploadPath($group['cover'] ?? null);
            $images = self::normalizeUploadList($group['images'] ?? []);
            $videos = self::normalizeUploadList($group['videos'] ?? []);
            $order = 0;

            if ($cover) {
                $images = array_values(array_filter($images, fn (string $path): bool => $path !== $cover));
            }

            if ($cover) {
                $keep[] = self::upsertPortfolioMedia($vendor, $meta, [
                    'type' => 'image',
                    'path' => $cover,
                    'is_cover' => true,
                    'sort_order' => ($index * 100) + $order++,
                ])->id;
            }

            foreach ($images as $path) {
                $keep[] = self::upsertPortfolioMedia($vendor, $meta, [
                    'type' => 'image',
                    'path' => $path,
                    'is_cover' => $cover === null && $order === 0,
                    'sort_order' => ($index * 100) + $order++,
                ])->id;
            }

            foreach ($videos as $path) {
                $keep[] = self::upsertPortfolioMedia($vendor, $meta, [
                    'type' => 'video',
                    'path' => $path,
                    'is_cover' => $cover === null && $images === [] && $order === 0,
                    'sort_order' => ($index * 100) + $order++,
                ])->id;
            }

            if ($order === 0 && filled($meta['external_url'])) {
                $keep[] = self::upsertPortfolioMedia($vendor, $meta, [
                    'type' => 'link',
                    'path' => '',
                    'is_cover' => true,
                    'sort_order' => ($index * 100),
                ])->id;
            }
        }

        $query = $vendor->portfolios();
        if ($keep !== []) {
            $query->whereNotIn('id', $keep);
        }
        $query->get()->each(function (Portfolio $item): void {
            if (filled($item->path)) {
                Storage::disk('public')->delete($item->path);
            }
            $item->delete();
        });
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function presentProjectsForAdmin(Vendor $vendor): array
    {
        return array_map(function (array $project): array {
            $files = collect($project['files'] ?? []);
            $cover = $files->firstWhere('is_cover', true) ?? $files->firstWhere('kind', 'image');

            return [
                'title' => $project['title'],
                'completed_on' => $project['completed_on'],
                'description' => $project['description'],
                'external_url' => $project['external_url'],
                'is_featured' => $project['is_featured'],
                'cover' => $cover['path'] ?? null,
                'images' => $files->where('kind', 'image')
                    ->when($cover, fn ($collection) => $collection->reject(fn ($file) => ($file['path'] ?? null) === ($cover['path'] ?? null)))
                    ->pluck('path')
                    ->filter()
                    ->values()
                    ->all(),
                'videos' => $files->where('kind', 'video')->pluck('path')->filter()->values()->all(),
            ];
        }, self::presentProjects($vendor));
    }

    /**
     * @param  array<string, mixed>  $meta
     * @param  array<string, mixed>  $media
     */
    protected static function upsertPortfolioMedia(Vendor $vendor, array $meta, array $media): Portfolio
    {
        $existing = null;
        if (filled($media['path'] ?? null)) {
            $existing = $vendor->portfolios()
                ->where('path', $media['path'])
                ->where('title', $meta['title'])
                ->first();
        }

        $payload = [
            ...$meta,
            'type' => $media['type'],
            'path' => $media['path'] ?? '',
            'is_cover' => (bool) ($media['is_cover'] ?? false),
            'sort_order' => (int) ($media['sort_order'] ?? 0),
        ];

        if ($existing) {
            $existing->fill($payload)->save();

            return $existing;
        }

        return $vendor->portfolios()->create($payload);
    }

    /**
     * @param  mixed  $value
     * @return list<string>
     */
    protected static function normalizeUploadList(mixed $value): array
    {
        if ($value instanceof UploadedFile) {
            return [self::storeUploaded($value)];
        }
        if (! is_array($value)) {
            $one = self::normalizeUploadPath($value);

            return $one ? [$one] : [];
        }

        $paths = [];
        foreach ($value as $item) {
            if ($item instanceof UploadedFile) {
                $paths[] = self::storeUploaded($item);

                continue;
            }
            $path = self::normalizeUploadPath($item);
            if ($path) {
                $paths[] = $path;
            }
        }

        return array_values(array_unique($paths));
    }

    protected static function normalizeUploadPath(mixed $value): ?string
    {
        if ($value instanceof UploadedFile) {
            return self::storeUploaded($value);
        }
        if (! is_string($value) || trim($value) === '') {
            return null;
        }
        $path = ltrim($value, '/');
        if (str_starts_with($path, 'storage/')) {
            $path = substr($path, strlen('storage/'));
        }
        if (str_contains($path, '://')) {
            $marker = '/storage/';
            $pos = strpos($path, $marker);
            if ($pos !== false) {
                $path = substr($path, $pos + strlen($marker));
            } else {
                return null;
            }
        }

        return $path;
    }

    protected static function storeUploaded(UploadedFile $file): string
    {
        return $file->store('portfolios', 'public');
    }

    protected static function mediaKind(Portfolio $item): string
    {
        if ($item->type === 'video') {
            return 'video';
        }
        if ($item->type === 'link' || (blank($item->path) && filled($item->external_url))) {
            return 'link';
        }
        $path = strtolower((string) $item->path);
        if (str_ends_with($path, '.mp4') || str_ends_with($path, '.mov') || str_ends_with($path, '.webm')) {
            return 'video';
        }

        return 'image';
    }

    public static function assertIdentityImages(Request $request, ?Vendor $vendor = null): void
    {
        $messages = [];
        foreach ([
            'profile_photo' => 'Add a personal photo.',
            'national_id_image' => 'Upload your national ID card.',
            'cover_image' => 'Add a cover image.',
        ] as $field => $message) {
            if (! $request->hasFile($field) && blank($vendor?->{$field})) {
                $messages[$field] = $message;
            }
        }

        if ($messages !== []) {
            throw \Illuminate\Validation\ValidationException::withMessages($messages);
        }
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

        $stored = $request->file($field)->store($directory, 'public');
        $vendor->{$field} = $stored;

        if ($field === 'profile_photo') {
            $user = $vendor->user;
            if ($user) {
                if (filled($user->avatar) && is_string($user->avatar)
                    && ! str_starts_with($user->avatar, 'http://')
                    && ! str_starts_with($user->avatar, 'https://')
                    && $user->avatar !== $stored) {
                    Storage::disk('public')->delete($user->avatar);
                }
                $user->forceFill(['avatar' => $stored])->save();
            }
        }
    }

    public static function publicUrl(?string $path): ?string
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
