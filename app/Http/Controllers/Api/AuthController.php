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
use App\Support\Egypt;
use App\Support\VendorPhotos;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
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

        $user = User::query()->where('email', $data['email'])->first();
        if (! $user || ! Hash::check($data['password'], $user->password) || ! $user->is_active) {
            throw ValidationException::withMessages([
                'email' => 'Invalid email or password.',
            ]);
        }

        if (! in_array($user->role, ['client', 'vendor'], true)) {
            throw ValidationException::withMessages([
                'email' => 'Staff accounts sign in on the web console.',
            ]);
        }

        return response()->json($this->presentUser($user->load('vendor.vendorType')));
    }

    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
            'role' => ['required', Rule::in(['client', 'vendor'])],
            'phone' => ['required', 'string', Egypt::mobileRule()],
            'locale' => ['nullable', Rule::in(['en', 'ar'])],
            'city_id' => ['nullable', 'integer', 'exists:cities,id'],
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
            'wallet_phone' => ['nullable', 'required_if:payout_method,wallet', Egypt::mobileRule()],
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
            'projects.*.type' => ['required', Rule::in(['image', 'video', 'link'])],
            'projects.*.title' => ['required', 'string', 'max:255'],
            'projects.*.completed_on' => ['nullable', 'string', 'max:40'],
            'projects.*.external_url' => ['nullable', 'url', 'max:500'],
            'projects.*.description' => ['nullable', 'string'],
            'projects.*.is_featured' => ['nullable', 'boolean'],
            'projects.*.files' => ['nullable', 'array', 'max:20'],
            'projects.*.files.*' => ['file', 'max:51200'],
        ], [
            'phone.required' => Egypt::mobileMessage(),
            'phone.regex' => Egypt::mobileMessage(),
            'contact_phone.regex' => Egypt::mobileMessage(),
            'whatsapp.regex' => Egypt::mobileMessage(),
            'wallet_phone.regex' => Egypt::mobileMessage(),
            'wallet_phone.required_if' => Egypt::mobileMessage(),
        ]);

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
                'bio' => $data['bio'] ?? null,
                'contact_phone' => $data['contact_phone'] ?? $data['phone'] ?? null,
                'contact_email' => $data['contact_email'] ?? $user->email,
                'whatsapp' => $data['whatsapp'] ?? null,
                'instagram' => $data['instagram'] ?? null,
                'address' => $data['address'] ?? null,
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

            $this->storeProjects($request, $vendor, $data['projects'] ?? []);
            $this->syncFilterTags($vendor, $data['filter_tags'] ?? []);
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

        $query = Booking::query()->with(['vendor.vendorType', 'vendor.city', 'client', 'category']);
        if ($user->isVendor() && $user->vendor) {
            $query->where('vendor_id', $user->vendor->id);
            $view = 'vendor';
        } else {
            abort_unless($user->isClient(), 403, 'Only clients and vendors can open bookings.');
            $query->where('client_id', $user->id);
            $view = 'client';
        }

        $items = $query->orderByDesc('scheduled_at')->limit(80)->get()->map(fn (Booking $booking): array => $this->presentBooking($booking, $view));

        return response()->json([
            'role' => $view,
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

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $role,
            'vendor_id' => $user->vendor?->id,
            'vendor_name' => $user->vendor?->display_name,
            'verification_status' => $user->vendor?->verification_status,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function presentBooking(Booking $booking, string $view): array
    {
        $vendor = $booking->vendor;
        $slug = $vendor?->vendorType?->slug ?? 'photographer';
        $counterpart = $view === 'vendor'
            ? ($booking->client?->name ?: 'Client')
            : ($vendor?->display_name ?: 'Creator');
        $photo = $view === 'vendor'
            ? null
            : ($vendor?->profile_photo ?: VendorPhotos::cover($slug, (int) ($vendor?->id ?? 1)));
        $start = $booking->scheduled_at?->timezone(config('app.timezone'));
        $end = $start?->copy()->addHours($booking->durationHours());
        $group = match ($booking->status) {
            'cancelled', 'rejected', 'failed', 'refunded' => 'canceled',
            'delivered', 'approved', 'completed' => 'completed',
            default => 'upcoming',
        };
        $badge = match ($group) {
            'canceled' => 'Canceled',
            'completed' => 'Completed',
            default => $booking->status === 'pending' ? 'Pending' : 'Confirmed',
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
            'photo' => $photo,
            'vendor_type' => $vendor?->vendorType?->name_en,
            'role' => $vendor?->vendorType?->name_en,
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
