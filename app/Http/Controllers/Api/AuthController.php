<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorType;
use App\Support\AppClient;
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
            'vendor_type' => ['nullable', 'string'],
        ]);

        $user = User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => $data['role'],
            'is_active' => true,
            'locale' => 'en',
        ]);

        if ($data['role'] === 'vendor') {
            $type = VendorType::query()->where('slug', $data['vendor_type'] ?? 'photographer')->first()
                ?? VendorType::query()->orderBy('sort_order')->first();
            Vendor::query()->create([
                'user_id' => $user->id,
                'vendor_type_id' => $type?->id,
                'display_name' => $user->name,
                'verification_status' => 'pending',
                'is_active' => true,
            ]);
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
