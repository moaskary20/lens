<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\User;
use App\Models\UserAddress;
use App\Models\UserPaymentMethod;
use App\Support\AppClient;
use App\Support\ClientPayment;
use App\Support\ClientPreferences;
use App\Support\Egypt;
use App\Support\Finance;
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

    public function profile(): JsonResponse
    {
        return response()->json($this->presentProfile($this->member()));
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $user = $this->member();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['required', 'string', Egypt::mobileRule(), Rule::unique('users', 'phone')->ignore($user->id)],
            'locale' => ['nullable', Rule::in(['en', 'ar'])],
            'city_id' => ['nullable', 'integer', 'exists:cities,id'],
            'password' => ['nullable', 'string', 'min:6'],
            'current_password' => ['nullable', 'string'],
            'avatar' => ['nullable', 'image', 'max:4096'],
        ], [
            'phone.required' => Egypt::mobileMessage(),
            'phone.regex' => Egypt::mobileMessage(),
        ]);

        if (filled($data['password'] ?? null)) {
            if (! Hash::check((string) ($data['current_password'] ?? ''), $user->password)) {
                throw ValidationException::withMessages([
                    'current_password' => 'Current password is incorrect.',
                ]);
            }
        }

        $payload = [
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'locale' => $data['locale'] ?? $user->locale,
            'city_id' => $data['city_id'] ?? null,
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

        return response()->json($this->presentProfile($user->fresh()));
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
        $user = $this->client();
        $items = Booking::query()
            ->with(['vendor.vendorType', 'vendor.city'])
            ->where('client_id', $user->id)
            ->whereNotIn('status', ['cancelled', 'rejected', 'failed', 'refunded'])
            ->orderByDesc('scheduled_at')
            ->limit(40)
            ->get()
            ->map(fn (Booking $booking): array => $this->presentProject($booking))
            ->values()
            ->all();

        return response()->json(['projects' => $items]);
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
        $user->loadMissing(['city', 'wallet']);

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
            'payment_methods_count' => $user->paymentMethods()->count(),
            'addresses_count' => $user->addresses()->count(),
            'bookings_count' => $user->bookings()->count(),
            'favorites_count' => $user->favorites()->count(),
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
    protected function presentProject(Booking $booking): array
    {
        $vendor = $booking->vendor;
        $phone = preg_replace('/\D+/', '', (string) ($vendor?->contact_phone ?: $vendor?->whatsapp ?: '')) ?: null;

        return [
            'id' => $booking->id,
            'title' => $booking->project_name ?: (($vendor?->vendorType?->name_en ?: 'Creative').' Session'),
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
}
