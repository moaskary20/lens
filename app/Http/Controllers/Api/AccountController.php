<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Conversation;
use App\Models\User;
use App\Models\UserAddress;
use App\Models\UserPaymentMethod;
use App\Models\VendorAvailability;
use App\Models\VendorType;
use App\Models\WalletTransaction;
use App\Services\WalletService;
use App\Support\AppClient;
use App\Support\AppPassword;
use App\Support\ClientPayment;
use App\Support\ClientPreferences;
use App\Support\Egypt;
use App\Support\Feature;
use App\Support\Finance;
use App\Support\Roles;
use App\Support\VendorAppProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AccountController extends Controller
{
    public function paymentMethods(): JsonResponse
    {
        $user = $this->client();

        return response()->json([
            'methods' => $user->paymentMethods()->orderByDesc('is_default')->orderByDesc('id')->get()
                ->map(fn (UserPaymentMethod $method): array => $this->presentPayment($method))
                ->values()
                ->all(),
        ]);
    }

    public function storePaymentMethod(Request $request): JsonResponse
    {
        $user = $this->client();
        $data = $request->validate([
            'type' => ['required', Rule::in(['card', 'wallet', 'paypal'])],
            'is_default' => ['nullable', 'boolean'],
            'details' => ['nullable', 'array'],
        ]);
        $details = ClientPayment::sanitize($data['type'], $data['details'] ?? []);
        abort_if($details === [], 422, 'Enter the payment details first.');

        $method = UserPaymentMethod::query()->create([
            'user_id' => $user->id,
            'type' => $data['type'],
            'label' => ClientPayment::label($data['type'], $details),
            'details' => $details,
            'is_default' => (bool) ($data['is_default'] ?? $user->paymentMethods()->doesntExist()),
        ]);
        $this->rememberDefault($user, $method);

        return response()->json($this->presentPayment($method->fresh()), 201);
    }

    public function destroyPaymentMethod(UserPaymentMethod $paymentMethod): JsonResponse
    {
        $user = $this->client();
        abort_unless((int) $paymentMethod->user_id === (int) $user->id, 403);
        $paymentMethod->delete();

        return response()->json(['ok' => true]);
    }

    public function addresses(): JsonResponse
    {
        $user = $this->client();

        return response()->json([
            'addresses' => $user->addresses()->orderByDesc('is_default')->orderByDesc('id')->get()
                ->map(fn (UserAddress $address): array => $this->presentAddress($address))
                ->values()
                ->all(),
        ]);
    }

    public function storeAddress(Request $request): JsonResponse
    {
        $user = $this->client();
        $data = $request->validate([
            'label' => ['nullable', 'string', 'max:40'],
            'line' => ['required', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:80'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        $address = UserAddress::query()->create([
            'user_id' => $user->id,
            'label' => $data['label'] ?? 'Home',
            'line' => $data['line'],
            'city' => $data['city'] ?? null,
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'is_default' => (bool) ($data['is_default'] ?? $user->addresses()->doesntExist()),
        ]);
        if ($address->is_default) {
            $user->addresses()->where('id', '!=', $address->id)->update(['is_default' => false]);
        }

        return response()->json($this->presentAddress($address->fresh()), 201);
    }

    public function destroyAddress(UserAddress $address): JsonResponse
    {
        $user = $this->client();
        abort_unless((int) $address->user_id === (int) $user->id, 403);
        $address->delete();

        return response()->json(['ok' => true]);
    }

    public function wallet(): JsonResponse
    {
        abort_unless(Feature::enabled('wallets'), 404, 'Wallets are disabled.');

        $user = $this->member();
        $wallet = app(WalletService::class)->ensure($user);
        $currency = Finance::currency();
        $transactions = $wallet->transactions()
            ->with(['booking:id,reference,project_name'])
            ->latest('id')
            ->limit(40)
            ->get()
            ->map(fn (WalletTransaction $tx): array => $this->presentWalletTransaction($tx, $currency))
            ->values()
            ->all();

        return response()->json([
            'currency' => $currency,
            'role' => $user->isVendor() ? 'vendor' : 'client',
            'wallet' => [
                'available' => (float) $wallet->available,
                'pending' => (float) $wallet->pending,
                'coupon_credit' => (float) $wallet->coupon_credit,
                'spendable' => $wallet->spendable(),
                'lifetime_earned' => (float) $wallet->lifetime_earned,
                'lifetime_withdrawn' => (float) $wallet->lifetime_withdrawn,
                'lifetime_commission' => (float) $wallet->lifetime_commission,
                'lifetime_paid' => (float) $wallet->lifetime_paid,
                'lifetime_refunded' => (float) $wallet->lifetime_refunded,
            ],
            'transactions' => $transactions,
        ]);
    }

    public function profile(): JsonResponse
    {
        return response()->json($this->presentProfile($this->member()));
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $user = $this->member();
        $data = $request->validate(array_merge([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['required', 'string', Egypt::mobileRule(), Rule::unique('users', 'phone')->ignore($user->id)],
            'locale' => ['required', Rule::in(['en', 'ar'])],
            'city_id' => ['required', 'integer', 'exists:cities,id'],
            'password' => AppPassword::rule(required: false),
            'current_password' => ['nullable', 'string'],
            'avatar' => ['nullable', 'image', 'max:4096'],
        ], $user->isVendor() ? VendorAppProfile::rules(true) : []), array_merge(AppPassword::messages(), [
            'phone.required' => Egypt::mobileMessage(),
            'phone.regex' => Egypt::mobileMessage(),
            'contact_phone.regex' => Egypt::mobileMessage(),
            'whatsapp.required' => 'Enter a WhatsApp number.',
            'whatsapp.regex' => Egypt::mobileMessage(),
            'wallet_phone.regex' => Egypt::mobileMessage(),
            'city_id.required' => 'Choose your governorate.',
            'date_of_birth.required' => 'Enter your date of birth.',
        ]));

        if (filled($data['password'] ?? null)) {
            if (! Hash::check((string) ($data['current_password'] ?? ''), $user->password)) {
                throw ValidationException::withMessages([
                    'current_password' => 'Current password is incorrect.',
                ]);
            }
        }

        if (! $user->isVendor() && ! $request->hasFile('avatar') && blank($user->avatar)) {
            throw ValidationException::withMessages([
                'avatar' => 'Add a personal photo.',
            ]);
        }

        $payload = [
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'locale' => $data['locale'] ?? $user->locale,
            'city_id' => $data['city_id'],
        ];

        if (filled($data['password'] ?? null)) {
            $payload['password'] = $data['password'];
        }

        if ($request->hasFile('avatar')) {
            if (filled($user->avatar) && ! str_starts_with((string) $user->avatar, 'http')) {
                Storage::disk('public')->delete($user->avatar);
            }
            $payload['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }

        $user->update($payload);

        $settings = $this->mergedSettings($user);
        $settings['language'] = $user->locale ?: 'en';
        $user->update(['app_settings' => $settings]);

        if ($user->isVendor()) {
            $vendor = $user->vendor;
            if (! $vendor) {
                $typeId = VendorType::query()->where('slug', $data['vendor_type'] ?? 'photographer')->value('id')
                    ?? VendorType::query()->orderBy('sort_order')->value('id');
                $vendor = $user->vendor()->create([
                    'vendor_type_id' => $typeId,
                    'display_name' => $data['display_name'] ?? $user->name,
                    'verification_status' => 'pending',
                    'is_active' => true,
                ]);
            }
            VendorAppProfile::assertIdentityImages($request, $vendor);
            VendorAppProfile::apply($request, $vendor, $data);
        }

        return response()->json($this->presentProfile($user->fresh(['vendor.vendorType', 'vendor.filterTags', 'vendor.portfolios', 'city', 'wallet'])));
    }

    public function settings(): JsonResponse
    {
        $user = $this->client();

        return response()->json(['settings' => $this->mergedSettings($user)]);
    }

    public function updateSettings(Request $request): JsonResponse
    {
        $user = $this->client();
        $data = $request->validate([
            'settings' => ['required', 'array'],
            'settings.push_notifications' => ['nullable', 'boolean'],
            'settings.chat_alerts' => ['nullable', 'boolean'],
            'settings.booking_reminders' => ['nullable', 'boolean'],
            'settings.email_offers' => ['nullable', 'boolean'],
            'settings.message_preview' => ['nullable', 'boolean'],
            'settings.vibration' => ['nullable', 'boolean'],
            'settings.review_prompts' => ['nullable', 'boolean'],
            'settings.read_receipts' => ['nullable', 'boolean'],
            'settings.hide_activity' => ['nullable', 'boolean'],
            'settings.haptic_feedback' => ['nullable', 'boolean'],
            'settings.reduce_motion' => ['nullable', 'boolean'],
            'settings.language' => ['nullable', Rule::in(['en', 'ar'])],
        ]);
        $user->update([
            'app_settings' => ClientPreferences::merge(array_merge($this->mergedSettings($user), $data['settings']), $data['settings']['language'] ?? $user->locale),
            'locale' => $data['settings']['language'] ?? $user->locale,
        ]);

        return response()->json(['settings' => $this->mergedSettings($user->fresh())]);
    }

    public function projects(): JsonResponse
    {
        $user = $this->member();
        $asVendor = $user->isVendor() && $user->vendor;
        $query = Booking::query()
            ->with(['vendor.vendorType', 'vendor.city', 'vendor.user', 'client', 'conversation'])
            ->whereNotIn('status', ['cancelled', 'rejected', 'failed', 'refunded'])
            ->orderByDesc('scheduled_at')
            ->limit(40);

        if ($asVendor) {
            $query->where('vendor_id', $user->vendor->id);
            $view = 'vendor';
        } else {
            abort_unless($user->canUseClientApp(), 403, 'Only clients can manage this account.');
            $query->where('client_id', $user->id);
            $view = 'client';
        }

        $items = $query->get()
            ->map(fn (Booking $booking): array => $this->presentProject($booking, $view))
            ->values()
            ->all();

        return response()->json(['projects' => $items]);
    }

    public function availabilities(): JsonResponse
    {
        $vendor = $this->vendorAccount();

        return response()->json([
            'calendar_title' => \App\Support\VendorProfile::calendarTitle($vendor->vendorType?->slug),
            'availabilities' => VendorAppProfile::presentAvailabilities($vendor->loadMissing('availabilities')),
        ]);
    }

    public function storeAvailability(Request $request): JsonResponse
    {
        $vendor = $this->vendorAccount();
        $data = $request->validate([
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'status' => ['nullable', Rule::in(['open', 'blocked'])],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $slot = $vendor->availabilities()->create([
            'starts_at' => $data['starts_at'],
            'ends_at' => $data['ends_at'],
            'status' => ($data['status'] ?? 'open') === 'blocked' ? 'blocked' : 'open',
            'notes' => $data['notes'] ?? null,
        ]);
        $items = VendorAppProfile::presentAvailabilities($vendor->load('availabilities'));

        return response()->json([
            'availability' => collect($items)->firstWhere('id', $slot->id),
            'availabilities' => $items,
        ], 201);
    }

    public function updateAvailability(Request $request, VendorAvailability $availability): JsonResponse
    {
        $vendor = $this->vendorAccount();
        abort_unless((int) $availability->vendor_id === (int) $vendor->id, 403);
        abort_if($availability->status === 'booked', 422, 'Booked slots stay locked until the session ends.');

        $data = $request->validate([
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'status' => ['nullable', Rule::in(['open', 'blocked'])],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $availability->update([
            'starts_at' => $data['starts_at'],
            'ends_at' => $data['ends_at'],
            'status' => ($data['status'] ?? $availability->status) === 'blocked' ? 'blocked' : 'open',
            'notes' => array_key_exists('notes', $data) ? $data['notes'] : $availability->notes,
        ]);
        $items = VendorAppProfile::presentAvailabilities($vendor->load('availabilities'));

        return response()->json([
            'availability' => collect($items)->firstWhere('id', $availability->id),
            'availabilities' => $items,
        ]);
    }

    public function destroyAvailability(VendorAvailability $availability): JsonResponse
    {
        $vendor = $this->vendorAccount();
        abort_unless((int) $availability->vendor_id === (int) $vendor->id, 403);
        abort_if($availability->status === 'booked', 422, 'Booked slots cannot be deleted.');
        $availability->delete();

        return response()->json([
            'ok' => true,
            'availabilities' => VendorAppProfile::presentAvailabilities($vendor->load('availabilities')),
        ]);
    }

    protected function vendorAccount(): \App\Models\Vendor
    {
        abort_unless(Feature::enabled('bookings'), 404);
        $user = AppClient::requireUser();
        abort_unless($user->isVendor() && $user->vendor, 403, 'Only vendors can manage calendar availability.');
        Roles::abortUnlessCan($user, 'set_availability', 'Calendar availability is disabled for this role.');

        return $user->vendor->loadMissing('vendorType');
    }

    protected function client(): User
    {
        $user = AppClient::requireUser();
        abort_unless($user->canUseClientApp(), 403, 'Only clients can manage this account.');

        return $user;
    }

    protected function member(): User
    {
        $user = AppClient::requireUser();
        abort_unless($user->canUseClientApp() || $user->isVendor(), 403, 'Only app accounts can manage this profile.');

        return $user;
    }

    /**
     * @return array<string, mixed>
     */
    protected function presentProfile(User $user): array
    {
        $user->loadMissing(['city', 'wallet', 'vendor.vendorType', 'vendor.filterTags', 'vendor.portfolios', 'vendor.availabilities']);

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'role' => $user->isVendor() ? 'vendor' : 'client',
            'role_label' => $user->isVendor() ? 'Vendor' : 'Client',
            'avatar' => $user->avatarUrl(),
            'locale' => $user->locale ?: 'en',
            'city_id' => $user->city_id,
            'city' => $user->city?->name_en,
            'is_active' => (bool) $user->is_active,
            'joined_at' => $user->created_at?->toDateString(),
            'currency' => Finance::currency(),
            'wallet_available' => (float) ($user->wallet?->available ?? 0),
            'wallet_pending' => (float) ($user->wallet?->pending ?? 0),
            'wallet_coupon_credit' => (float) ($user->wallet?->coupon_credit ?? 0),
            'wallet_lifetime_earned' => (float) ($user->wallet?->lifetime_earned ?? 0),
            'wallet_lifetime_paid' => (float) ($user->wallet?->lifetime_paid ?? 0),
            'payment_methods_count' => $user->paymentMethods()->count(),
            'addresses_count' => $user->addresses()->count(),
            'bookings_count' => $user->isVendor()
                ? $user->vendor?->bookings()->count() ?? 0
                : $user->bookings()->count(),
            'favorites_count' => $user->favorites()->count(),
            'vendor_id' => $user->vendor?->id,
            'vendor_name' => $user->vendor?->display_name,
            'vendor' => VendorAppProfile::present($user->vendor),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function presentPayment(UserPaymentMethod $method): array
    {
        $details = is_array($method->details) ? $method->details : [];

        return [
            'id' => $method->id,
            'type' => $method->type,
            'label' => $method->label ?: ClientPayment::label($method->type, $details),
            'is_default' => (bool) $method->is_default,
            'details' => $details,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function presentAddress(UserAddress $address): array
    {
        return [
            'id' => $address->id,
            'label' => $address->label,
            'line' => $address->line,
            'city' => $address->city,
            'latitude' => $address->latitude === null ? null : (float) $address->latitude,
            'longitude' => $address->longitude === null ? null : (float) $address->longitude,
            'is_default' => (bool) $address->is_default,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function presentProject(Booking $booking, string $view = 'client'): array
    {
        $vendor = $booking->vendor;
        $vendor?->loadMissing('user');
        $booking->loadMissing(['conversation', 'client']);
        $phone = preg_replace('/\D+/', '', (string) ($vendor?->contact_phone ?: $vendor?->whatsapp ?: $vendor?->user?->phone ?: '')) ?: null;
        $counterpart = $view === 'vendor'
            ? ($booking->client?->name ?: 'Client')
            : ($vendor?->display_name ?: 'Creator');
        $conversationId = $booking->conversation?->id
            ?? Conversation::query()
                ->where('client_id', $booking->client_id)
                ->where('vendor_id', $booking->vendor_id)
                ->value('id');

        return [
            'id' => $booking->id,
            'booking_id' => $booking->id,
            'title' => $booking->project_name ?: (($vendor?->vendorType?->name_en ?: 'Creative').' Session'),
            'project_name' => $booking->project_name,
            'reference' => $booking->reference,
            'status' => $booking->status,
            'status_label' => match ($booking->status) {
                'pending' => 'Pending',
                'accepted', 'checked_in', 'in_progress' => 'Confirmed',
                'delivered' => 'Delivered',
                'completed', 'approved' => 'Completed',
                default => ucfirst(str_replace('_', ' ', (string) $booking->status)),
            },
            'when' => $booking->scheduled_at?->format('l, j F Y • g:i A'),
            'location' => $booking->location_text ?: ($vendor?->city?->name_en ?: 'Egypt'),
            'phone' => $phone,
            'whatsapp' => preg_replace('/\D+/', '', (string) ($vendor?->whatsapp ?: $phone)) ?: $phone,
            'vendor_id' => $vendor?->id,
            'client_id' => $booking->client_id,
            'vendor_name' => $vendor?->display_name,
            'counterpart' => $counterpart,
            'conversation_id' => $conversationId ? (int) $conversationId : null,
            'vendor' => [
                'id' => $vendor?->id,
                'display_name' => $vendor?->display_name,
                'vendor_type_name' => $vendor?->vendorType?->name_en,
                'vendor_type' => $vendor?->vendorType?->slug,
                'city' => $vendor?->city?->name_en,
                'location' => $vendor?->address ?: $vendor?->city?->name_en,
                'rating_avg' => (float) ($vendor?->rating_avg ?: 0),
                'rating_count' => (int) ($vendor?->rating_count ?: 0),
                'badges' => [],
                'cover_url' => $vendor?->cover_image,
                'profile_photo_url' => $vendor?->profile_photo,
                'initials' => mb_strtoupper(mb_substr((string) $vendor?->display_name, 0, 1)),
                'tags' => [],
                'verified' => $vendor?->verification_status === 'verified',
                'latitude' => $vendor?->latitude,
                'longitude' => $vendor?->longitude,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function mergedSettings(User $user): array
    {
        return ClientPreferences::merge($user->app_settings, $user->locale ?: 'en');
    }

    protected function rememberDefault(User $user, UserPaymentMethod $method): void
    {
        if (! $method->is_default) {
            return;
        }
        $user->paymentMethods()->where('id', '!=', $method->id)->update(['is_default' => false]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function presentWalletTransaction(WalletTransaction $tx, string $currency): array
    {
        $label = match ($tx->type) {
            WalletTransaction::PAYMENT => 'Booking payment',
            WalletTransaction::COUPON => 'Coupon credit',
            WalletTransaction::REFUND => 'Refund',
            WalletTransaction::EARNING_HOLD => 'Earnings held',
            WalletTransaction::HOLD_REVERSAL => 'Hold released',
            WalletTransaction::EARNING_RELEASE => 'Earnings available',
            WalletTransaction::COMMISSION => 'Platform commission',
            WalletTransaction::WITHDRAWAL => 'Withdrawal',
            WalletTransaction::TOPUP => 'Top up',
            default => ucfirst(str_replace('_', ' ', (string) $tx->type)),
        };

        $signed = (float) $tx->available_delta
            + (float) $tx->pending_delta
            + (float) $tx->coupon_delta;
        if (abs($signed) < 0.0001) {
            $signed = (float) $tx->amount;
            if (in_array($tx->type, [WalletTransaction::PAYMENT, WalletTransaction::COMMISSION, WalletTransaction::WITHDRAWAL], true)) {
                $signed = -abs($signed);
            }
        }

        return [
            'id' => $tx->id,
            'type' => $tx->type,
            'label' => $label,
            'amount' => (float) $tx->amount,
            'signed_amount' => round($signed, 2),
            'currency' => $currency,
            'status' => $tx->status ?: 'posted',
            'notes' => $tx->notes,
            'booking_reference' => $tx->booking?->reference,
            'created_at' => $tx->created_at?->toIso8601String(),
            'created_label' => $tx->created_at?->timezone(config('app.timezone'))->format('j M Y · g:i A'),
        ];
    }
}
