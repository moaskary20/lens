<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\FilterTag;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorType;
use Illuminate\Http\UploadedFile;
use App\Support\AppClient;
use App\Support\AppPassword;
use App\Support\Egypt;
use App\Support\Feature;
use App\Support\Roles;
use App\Support\VendorAppProfile;
use App\Support\VendorPhotos;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $email = trim((string) $data['email']);
        $user = User::query()->where('email', $email)->first()
            ?? (mb_strtolower($email) !== $email
                ? User::query()->whereRaw('LOWER(email) = ?', [mb_strtolower($email)])->first()
                : null);
        if (! $user || ! Hash::check($data['password'], $user->password) || ! $user->is_active) {
            throw ValidationException::withMessages([
                'email' => 'Invalid email or password.',
            ]);
        }

        return response()->json($this->presentUser($user->load('vendor.vendorType')));
    }

    public function google(Request $request): JsonResponse
    {
        $data = $request->validate([
            'id_token' => ['required', 'string'],
            'role' => ['nullable', Rule::in(['client', 'vendor'])],
        ]);

        $google = $this->verifyGoogleIdToken($data['id_token']);
        $email = mb_strtolower(trim((string) ($google['email'] ?? '')));
        $googleId = (string) ($google['sub'] ?? '');
        $name = trim((string) ($google['name'] ?? ''));
        if ($email === '' || $googleId === '') {
            throw ValidationException::withMessages([
                'id_token' => 'Google account is missing an email.',
            ]);
        }

        $user = User::query()->where('google_id', $googleId)->first()
            ?? User::query()->whereRaw('LOWER(email) = ?', [$email])->first();

        if ($user && ! $user->is_active) {
            throw ValidationException::withMessages([
                'id_token' => 'This account is disabled.',
            ]);
        }

        if (! $user) {
            $role = ($data['role'] ?? 'client') === 'vendor' ? 'vendor' : 'client';
            $user = User::query()->create([
                'name' => $name !== '' ? $name : Str::before($email, '@'),
                'email' => $email,
                'google_id' => $googleId,
                'password' => Str::password(32),
                'role' => $role,
                'is_active' => true,
                'locale' => 'en',
                'email_verified_at' => now(),
            ]);

            if ($role === 'vendor') {
                $type = VendorType::query()->where('slug', 'photographer')->first()
                    ?? VendorType::query()->orderBy('sort_order')->first();
                Vendor::query()->create([
                    'user_id' => $user->id,
                    'vendor_type_id' => $type?->id,
                    'display_name' => $user->name,
                    'verification_status' => 'pending',
                    'is_active' => true,
                    'contact_email' => $email,
                ]);
            }
        } else {
            $updates = [];
            if (! filled($user->google_id)) {
                $updates['google_id'] = $googleId;
            }
            if (! filled($user->email_verified_at)) {
                $updates['email_verified_at'] = now();
            }
            if ($name !== '' && $user->name === '') {
                $updates['name'] = $name;
            }
            if ($updates !== []) {
                $user->update($updates);
            }
        }

        return response()->json($this->presentUser($user->fresh()->load('vendor.vendorType')));
    }

    /**
     * @return array<string, mixed>
     */
    protected function verifyGoogleIdToken(string $idToken): array
    {
        $response = Http::timeout(12)->get('https://oauth2.googleapis.com/tokeninfo', [
            'id_token' => $idToken,
        ]);

        if (! $response->successful()) {
            throw ValidationException::withMessages([
                'id_token' => 'Invalid Google sign-in token.',
            ]);
        }

        /** @var array<string, mixed> $payload */
        $payload = $response->json() ?: [];
        $aud = (string) ($payload['aud'] ?? '');
        $allowed = array_values(array_filter([
            (string) config('services.google.client_id'),
        ]));

        if ($allowed !== [] && ! in_array($aud, $allowed, true)) {
            throw ValidationException::withMessages([
                'id_token' => 'Google token was issued for another app.',
            ]);
        }

        $verified = $payload['email_verified'] ?? false;
        if ($verified !== true && $verified !== 'true') {
            throw ValidationException::withMessages([
                'id_token' => 'Google email is not verified.',
            ]);
        }

        return $payload;
    }

    public function register(Request $request): JsonResponse
    {
        $isVendor = $request->input('role') === 'vendor';
        $request->merge([
            'phone' => Egypt::normalizeMobile($request->input('phone')),
            'contact_phone' => Egypt::normalizeMobile($request->input('contact_phone')),
            'whatsapp' => Egypt::normalizeMobile($request->input('whatsapp')),
            'wallet_phone' => Egypt::normalizeMobile($request->input('wallet_phone')),
        ]);

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => AppPassword::rule(),
            'role' => ['required', Rule::in(['client', 'vendor'])],
            'phone' => ['required', 'string', Egypt::mobileRule(), Rule::unique('users', 'phone')],
            'locale' => ['nullable', Rule::in(['en', 'ar'])],
            'city_id' => ['required', 'integer', 'exists:cities,id'],
        ];
        if ($isVendor) {
            $rules = array_merge($rules, VendorAppProfile::rules(true));
            $rules['wallet_phone'] = ['nullable', 'required_if:payout_method,wallet', Egypt::mobileRule()];
        }

        $data = $request->validate($rules, array_merge(AppPassword::messages(), [
            'phone.required' => Egypt::mobileMessage(),
            'phone.regex' => Egypt::mobileMessage(),
            'phone.unique' => Egypt::mobileUniqueMessage(),
            'contact_phone.regex' => Egypt::mobileMessage(),
            'whatsapp.required' => 'Enter a WhatsApp number.',
            'whatsapp.regex' => Egypt::mobileMessage(),
            'wallet_phone.regex' => Egypt::mobileMessage(),
            'wallet_phone.required_if' => Egypt::mobileMessage(),
            'city_id.required' => 'Choose your governorate.',
            'date_of_birth.required' => 'Enter your date of birth.',
            'profile_photo.required' => 'Add a personal photo.',
        ]));

        if ($isVendor) {
            VendorAppProfile::assertIdentityImages($request);
        }

        $user = User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => $data['password'],
            'role' => $data['role'],
            'city_id' => $data['city_id'] ?? null,
            'is_active' => true,
            'locale' => $data['locale'] ?? 'en',
        ]);

        if ($data['role'] === 'vendor') {
            $type = VendorType::query()->where('slug', $data['vendor_type'] ?? 'photographer')->first()
                ?? VendorType::query()->orderBy('sort_order')->first();
            $vendor = Vendor::query()->create([
                'user_id' => $user->id,
                'vendor_type_id' => $type?->id,
                'city_id' => $data['city_id'] ?? null,
                'display_name' => ($data['display_name'] ?? null) ?: $user->name,
                'date_of_birth' => $data['date_of_birth'] ?? null,
                'profession' => $data['profession'] ?? null,
                'years_experience' => max(0, (int) ($data['years_experience'] ?? 0)),
                'bio' => $data['bio'] ?? null,
                'contact_phone' => $data['contact_phone'] ?? $data['phone'] ?? null,
                'contact_email' => $data['contact_email'] ?? $user->email,
                'whatsapp' => $data['whatsapp'] ?? null,
                'instagram' => $data['instagram'] ?? null,
                'instagram_followers' => $data['instagram_followers'] ?? null,
                'tiktok_url' => $data['tiktok_url'] ?? null,
                'tiktok_followers' => $data['tiktok_followers'] ?? null,
                'facebook_url' => $data['facebook_url'] ?? null,
                'facebook_followers' => $data['facebook_followers'] ?? null,
                'address' => $data['address'] ?? null,
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'specialties' => $data['specialties'] ?? null,
                'delivery_formats' => $data['delivery_formats'] ?? null,
                'extras' => $this->vendorExtras($data),
                'half_day_price' => $data['half_day_price'] ?? null,
                'full_day_price' => $data['full_day_price'] ?? null,
                'hourly_price' => $data['hourly_price'] ?? null,
                'per_video_price' => $data['per_video_price'] ?? null,
                'turnaround_hours' => $data['turnaround_hours'] ?? null,
                'payout_method' => $data['payout_method'] ?? null,
                'bank_name' => $data['bank_name'] ?? null,
                'bank_account_holder' => $data['bank_account_holder'] ?? null,
                'bank_account_number' => $data['bank_account_number'] ?? null,
                'bank_iban' => $data['bank_iban'] ?? null,
                'bank_swift' => $data['bank_swift'] ?? null,
                'bank_branch' => $data['bank_branch'] ?? null,
                'bank_branch_code' => $data['bank_branch_code'] ?? null,
                'bank_account_type' => $data['bank_account_type'] ?? null,
                'wallet_network_type' => $data['wallet_network_type'] ?? null,
                'wallet_telecom' => $data['wallet_telecom'] ?? null,
                'wallet_bank_name' => $data['wallet_bank_name'] ?? null,
                'wallet_phone' => $data['wallet_phone'] ?? null,
                'paypal_email' => $data['paypal_email'] ?? null,
                'paypal_name' => $data['paypal_name'] ?? null,
                'transfer_notes' => $data['transfer_notes'] ?? null,
                'verification_status' => 'pending',
                'is_active' => true,
            ]);

            VendorAppProfile::storeIdentityFiles($request, $vendor);
            $vendor->save();
            $this->storeProjects($request, $vendor, $data['projects'] ?? []);
            $this->syncFilterTags($vendor, $data['filter_tags'] ?? []);
            if (array_key_exists('availabilities', $data)) {
                VendorAppProfile::syncAvailabilities($vendor, $data['availabilities'] ?? []);
            }
        }

        return response()->json($this->presentUser($user->load('vendor.vendorType')), 201);
    }

    public function me(): JsonResponse
    {
        return response()->json($this->presentUser(AppClient::requireUser()->load('vendor.vendorType')));
    }

    public function bookings(): JsonResponse
    {
        $user = AppClient::requireUser()->load('vendor');

        $query = Booking::query()->with(['vendor.vendorType', 'vendor.city', 'vendor.badges', 'vendor.user', 'client', 'category', 'dispute']);
        if ($user->isVendor() && $user->vendor) {
            $query->where('vendor_id', $user->vendor->id);
            $view = 'vendor';
        } else {
            abort_unless($user->canUseClientApp(), 403, 'Only clients and vendors can open bookings.');
            $query->where('client_id', $user->id);
            $view = 'client';
        }

        $items = $query->orderByDesc('scheduled_at')->limit(80)->get()->map(fn (Booking $booking): array => $this->presentBooking($booking, $view));
        $summary = [
            'upcoming' => 0,
            'completed' => 0,
            'canceled' => 0,
        ];
        foreach ($items as $item) {
            $group = (string) ($item['group'] ?? 'upcoming');
            if (! array_key_exists($group, $summary)) {
                $group = 'upcoming';
            }
            $summary[$group]++;
        }

        return response()->json([
            'role' => $view,
            'summary' => $summary,
            'bookings' => $items->values()->all(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function vendorExtras(array $data): array
    {
        $extras = is_array($data['extras'] ?? null) ? $data['extras'] : [];
        if (! empty($data['pricing_model_id'])) {
            $extras['pricing_model_id'] = (int) $data['pricing_model_id'];
        }

        return $extras;
    }

    /**
     * @param  list<string>  $slugs
     */
    protected function syncFilterTags(Vendor $vendor, array $slugs): void
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
    protected function storeProjects(Request $request, Vendor $vendor, array $projects): void
    {
        foreach (array_values($projects) as $index => $project) {
            $uploads = $request->file('projects.'.$index.'.files') ?? [];
            if ($uploads instanceof UploadedFile) {
                $uploads = [$uploads];
            }
            if ($uploads === []) {
                $vendor->portfolios()->create([
                    'type' => $project['type'],
                    'path' => '',
                    'title' => $project['title'],
                    'completed_on' => $project['completed_on'] ?? null,
                    'external_url' => $project['external_url'] ?? null,
                    'description' => $project['description'] ?? null,
                    'is_featured' => (bool) ($project['is_featured'] ?? false),
                    'sort_order' => $index,
                ]);

                continue;
            }

            foreach (array_values($uploads) as $order => $file) {
                if (! $file instanceof UploadedFile) {
                    continue;
                }
                $vendor->portfolios()->create([
                    'type' => $project['type'],
                    'path' => $file->store('portfolios', 'public'),
                    'title' => $project['title'],
                    'completed_on' => $project['completed_on'] ?? null,
                    'external_url' => $project['external_url'] ?? null,
                    'description' => $project['description'] ?? null,
                    'is_featured' => (bool) ($project['is_featured'] ?? false),
                    'sort_order' => ($index * 100) + $order,
                ]);
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function presentUser(User $user): array
    {
        $role = $user->isVendor() ? 'vendor' : 'client';

        $user->loadMissing('city');

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'role' => $role,
            'avatar' => $user->avatarUrl(),
            'locale' => $user->locale ?: 'en',
            'city_id' => $user->city_id,
            'city' => $user->city?->name_en,
            'is_active' => (bool) $user->is_active,
            'joined_at' => $user->created_at?->toDateString(),
            'vendor_id' => $user->vendor?->id,
            'vendor_name' => $user->vendor?->display_name,
            'verification_status' => $user->vendor?->verification_status,
            'capabilities' => Roles::capabilitiesFor($user->role),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function presentBooking(Booking $booking, string $view): array
    {
        $vendor = $booking->vendor;
        $slug = $vendor?->vendorType?->slug ?? 'photographer';
        $vendorId = (int) ($vendor?->id ?? 1);
        $counterpart = $view === 'vendor'
            ? ($booking->client?->name ?: 'Client')
            : ($vendor?->display_name ?: 'Creator');
        $profileUrl = VendorAppProfile::publicUrl($vendor?->profile_photo)
            ?: VendorPhotos::portrait($slug, $vendorId);
        $coverUrl = VendorAppProfile::publicUrl($vendor?->cover_image)
            ?: VendorPhotos::cover($slug, $vendorId);
        $start = $booking->scheduled_at?->timezone(config('app.timezone'));
        $end = $start?->copy()->addHours($booking->durationHours());
        $group = match ($booking->status) {
            'cancelled', 'rejected', 'failed', 'refunded' => 'canceled',
            'delivered', 'approved', 'completed' => 'completed',
            'disputed' => 'upcoming',
            default => 'upcoming',
        };
        $badge = match (true) {
            $booking->status === 'disputed' => 'Disputed',
            $group === 'canceled' => 'Canceled',
            $group === 'completed' => 'Completed',
            $booking->status === 'pending' => 'Pending',
            default => 'Confirmed',
        };

        return [
            'id' => $booking->id,
            'reference' => $booking->reference,
            'status' => $booking->status,
            'status_label' => self::statusLabel($booking->status),
            'badge' => $badge,
            'group' => $group,
            'scheduled_at' => $start?->toIso8601String(),
            'when' => $start?->format('d M Y · H:i'),
            'date_label' => $start?->format('l, M j, Y'),
            'time_label' => ($start && $end) ? $start->format('g:i A').' – '.$end->format('g:i A') : null,
            'package' => $booking->package_type ? str_replace('_', ' ', $booking->package_type) : null,
            'location' => $booking->location_text ?: ($vendor?->city?->name_en ? $vendor->city->name_en.', Egypt' : 'Egypt'),
            'total' => (float) ($booking->total_paid ?: $booking->session_price ?: 0),
            'counterpart' => $counterpart,
            'photo' => $view === 'vendor' ? $booking->client?->avatarUrl() : $profileUrl,
            'vendor_type' => $vendor?->vendorType?->name_en,
            'role' => $vendor?->vendorType?->name_en,
            'project_name' => $booking->project_name ?: ($booking->category?->name_en ? $booking->category->name_en.' session' : 'Creative session'),
            'phone' => $view === 'vendor'
                ? Egypt::normalizeMobile($booking->client?->phone)
                : Egypt::normalizeMobile($vendor?->contact_phone ?: $vendor?->whatsapp ?: $vendor?->user?->phone),
            'whatsapp' => $view === 'vendor'
                ? Egypt::normalizeMobile($booking->client?->phone)
                : Egypt::normalizeMobile($vendor?->whatsapp ?: $vendor?->contact_phone ?: $vendor?->user?->phone),
            'can_chat' => Feature::enabled('chat'),
            'vendor_id' => $vendor?->id,
            'client_id' => $view === 'vendor' ? $booking->client_id : null,
            'dispute' => $booking->dispute?->toApp(),
            'vendor' => $view === 'client' ? [
                'id' => $vendor?->id,
                'display_name' => $vendor?->display_name,
                'vendor_type_name' => $vendor?->vendorType?->name_en,
                'vendor_type' => $vendor?->vendorType?->slug,
                'city' => $vendor?->city?->name_en,
                'location' => $vendor?->address ?: $vendor?->city?->name_en,
                'rating_avg' => (float) ($vendor?->rating_avg ?: 0),
                'rating_count' => (int) ($vendor?->rating_count ?: 0),
                'badges' => $vendor?->badges?->pluck('slug')->values()->all() ?? [],
                'cover_url' => $coverUrl,
                'profile_photo_url' => $profileUrl,
                'initials' => mb_strtoupper(mb_substr((string) ($vendor?->display_name ?: 'C'), 0, 1)),
                'tags' => [],
                'verified' => $vendor?->verification_status === 'verified',
                'latitude' => $vendor?->latitude,
                'longitude' => $vendor?->longitude,
            ] : null,
        ];
    }

    protected static function statusLabel(string $status): string
    {
        return match ($status) {
            'pending' => 'Pending acceptance',
            'accepted' => 'Accepted',
            'rejected' => 'Rejected',
            'checked_in' => 'Checked in',
            'in_progress' => 'In progress',
            'delivered' => 'Delivered',
            'in_revision' => 'In revision',
            'approved' => 'Approved',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            'disputed' => 'Disputed',
            'failed' => 'Failed',
            'refunded' => 'Refunded',
            default => ucfirst(str_replace('_', ' ', $status)),
        };
    }
}
