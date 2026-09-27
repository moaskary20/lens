<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\City;
use App\Models\Coupon;
use App\Models\Vendor;
use App\Services\DeliveryService;
use App\Services\PromoService;
use App\Support\AppClient;
use App\Support\Finance;
use App\Support\Travel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use LogicException;

class BookingController extends Controller
{
    public function quote(Request $request): JsonResponse
    {
        $data = $request->validate([
            'vendor_id' => ['required', 'integer', 'exists:vendors,id'],
            'session_price' => ['required', 'numeric', 'min:1'],
            'promo_code' => ['nullable', 'string', 'max:40'],
            'location_text' => ['nullable', 'string', 'max:255'],
        ]);

        $user = AppClient::requireUser();
        abort_unless($user->canUseClientApp(), 403, 'Only clients can quote a booking.');

        $vendor = Vendor::query()->with(['city', 'vendorType', 'travelRates'])->findOrFail($data['vendor_id']);
        $city = $this->cityFromLocation((string) ($data['location_text'] ?? '')) ?? $vendor->city;
        $travel = $this->travelFee($vendor, $city);
        $coupon = $this->couponFromCode($data['promo_code'] ?? null);

        if (filled($data['promo_code'] ?? null) && ! $coupon) {
            return response()->json(['message' => 'This promo code is not valid.', 'errors' => ['promo_code' => ['This promo code is not valid.']]], 422);
        }

        $quote = app(PromoService::class)->quotePrice(
            (float) $data['session_price'],
            $travel,
            $coupon,
            $user,
            $vendor,
        );

        return response()->json([
            ...$quote,
            'currency' => Finance::currency(),
            'promo_code' => $coupon?->code,
            'travel_fee' => $travel,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'vendor_id' => ['required', 'integer', 'exists:vendors,id'],
            'project_name' => ['required', 'string', 'max:255'],
            'project_type' => ['required', 'string', 'max:120'],
            'client_brief' => ['required', 'string'],
            'location_text' => ['required', 'string', 'max:255'],
            'location_lat' => ['nullable', 'numeric'],
            'location_lng' => ['nullable', 'numeric'],
            'package_type' => ['required', 'string', 'max:64'],
            'session_price' => ['required', 'numeric', 'min:1'],
            'scheduled_at' => ['nullable', 'date'],
            'duration_hours' => ['nullable', 'numeric', 'min:1'],
            'payment_method' => ['required', Rule::in(['card', 'wallet', 'paypal'])],
            'payment_details' => ['nullable', 'array'],
            'promo_code' => ['nullable', 'string', 'max:40'],
            'project_details' => ['nullable', 'array'],
            'notes' => ['nullable', 'string'],
        ]);

        $user = AppClient::requireUser();
        abort_unless($user->canUseClientApp(), 403, 'Only clients can create a booking.');

        $vendor = Vendor::query()->with(['city', 'vendorType', 'travelRates'])->findOrFail($data['vendor_id']);
        $city = $this->cityFromLocation($data['location_text']) ?? $vendor->city;
        $travel = $this->travelFee($vendor, $city);
        $coupon = $this->couponFromCode($data['promo_code'] ?? null);

        if (filled($data['promo_code'] ?? null) && ! $coupon) {
            return response()->json(['message' => 'This promo code is not valid.', 'errors' => ['promo_code' => ['This promo code is not valid.']]], 422);
        }

        $booking = Booking::query()->create([
            'reference' => $this->nextReference(),
            'client_id' => $user->id,
            'vendor_id' => $vendor->id,
            'city_id' => $city?->id,
            'status' => 'pending',
            'scheduled_at' => $data['scheduled_at'] ?? now()->addDays(7),
            'duration_hours' => $data['duration_hours'] ?? null,
            'package_type' => $data['package_type'],
            'location_text' => $data['location_text'],
            'location_lat' => $data['location_lat'] ?? null,
            'location_lng' => $data['location_lng'] ?? null,
            'session_price' => $data['session_price'],
            'travel_fee' => $travel,
            'payment_method' => $data['payment_method'],
            'payment_details' => $this->safePaymentDetails($data['payment_method'], $data['payment_details'] ?? []),
            'project_name' => $data['project_name'],
            'project_type' => $data['project_type'],
            'client_brief' => $data['client_brief'],
            'project_details' => $data['project_details'] ?? [],
            'notes' => $data['notes'] ?? null,
            'escrow_status' => 'held',
            'payout_status' => 'none',
        ]);

        app(PromoService::class)->applyToBooking($booking, $coupon, consume: true);

        return response()->json([
            'id' => $booking->id,
            'reference' => $booking->reference,
            'status' => $booking->status,
            'project_name' => $booking->project_name,
            'package_type' => $booking->package_type,
            'session_price' => (float) $booking->session_price,
            'total_paid' => (float) $booking->total_paid,
            'payment_method' => $booking->payment_method,
            'location_text' => $booking->location_text,
        ], 201);
    }

    public function deliverables(Booking $booking): JsonResponse
    {
        $booking = $this->ownedBooking($booking)->load(['deliverables', 'vendor.vendorType']);
        $state = app(DeliveryService::class)->previewState($booking);
        $vendor = $booking->vendor;

        return response()->json([
            'project_name' => $booking->project_name ?: 'Session files',
            'vendor_name' => $vendor?->display_name,
            'vendor_type' => $vendor?->vendorType?->name_en,
            'vendor_photo' => $vendor?->profile_photo ? asset('storage/'.$vendor->profile_photo) : null,
            'date_label' => $booking->scheduled_at?->format('F j, Y'),
            'total' => (float) ($booking->total_paid ?: $booking->session_price ?: 0),
            'status' => $booking->status,
            'preview' => $state,
            'deliverables' => $booking->deliverables->map(fn ($file): array => [
                'id' => $file->id,
                'name' => $file->original_name ?: basename((string) $file->path),
                'version' => $file->version,
                'watermarked' => (bool) $file->is_watermarked,
                'unlocked' => (bool) $file->is_unlocked || (bool) $state['downloads_unlocked'],
                'url' => $file->path ? asset('storage/'.$file->path) : null,
            ])->values()->all(),
        ]);
    }

    public function requestEdit(Request $request, Booking $booking): JsonResponse
    {
        $booking = $this->ownedBooking($booking);
        $data = $request->validate([
            'note' => ['required', 'string', 'max:2000'],
        ]);

        try {
            $updated = app(DeliveryService::class)->requestRevision($booking, $data['note'], $booking->client_id);
        } catch (LogicException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'status' => $updated->status,
            'revision_count' => $updated->revision_count,
            'message' => 'Edit requested. The creator will upload a new version.',
        ]);
    }

    public function approve(Booking $booking): JsonResponse
    {
        $booking = $this->ownedBooking($booking);

        try {
            $updated = app(DeliveryService::class)->approve($booking);
        } catch (LogicException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'status' => $updated->status,
            'message' => 'Delivery approved. Files are unlocked.',
        ]);
    }

    public function refuse(Request $request, Booking $booking): JsonResponse
    {
        $booking = $this->ownedBooking($booking);
        $data = $request->validate([
            'reason' => ['required', 'string', 'max:2000'],
        ]);

        try {
            $dispute = app(DeliveryService::class)->rejectAndDispute($booking, $booking->client_id, $data['reason']);
        } catch (LogicException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'status' => $booking->fresh()->status,
            'dispute_id' => $dispute->id,
            'message' => 'Delivery refused. Admin will review the complaint.',
        ]);
    }

    protected function ownedBooking(Booking $booking): Booking
    {
        $user = AppClient::requireUser();
        abort_unless($user->canUseClientApp() && (int) $booking->client_id === (int) $user->id, 403, 'This booking is not yours.');

        return $booking;
    }

    protected function nextReference(): string
    {
        do {
            $reference = 'LN-'.now()->format('ymd').random_int(100, 999);
        } while (Booking::query()->where('reference', $reference)->exists());

        return $reference;
    }

    protected function couponFromCode(?string $code): ?Coupon
    {
        $code = strtoupper(trim((string) $code));
        if ($code === '') {
            return null;
        }

        return Coupon::query()
            ->where('is_active', true)
            ->whereRaw('upper(code) = ?', [$code])
            ->first();
    }

    protected function cityFromLocation(string $location): ?City
    {
        if ($location === '') {
            return null;
        }

        return City::query()
            ->orderByRaw('length(name_en) desc')
            ->get()
            ->first(fn (City $city): bool => stripos($location, $city->name_en) !== false);
    }

    protected function travelFee(Vendor $vendor, ?City $city): float
    {
        try {
            return Travel::fee($vendor, $city);
        } catch (LogicException) {
            return 0.0;
        }
    }

    /**
     * @param  array<string, mixed>  $details
     * @return array<string, string>
     */
    protected function safePaymentDetails(string $method, array $details): array
    {
        $value = fn (string $key): string => trim((string) ($details[$key] ?? ''));

        return match ($method) {
            'card' => array_filter([
                'method' => 'card',
                'card_holder' => $value('card_holder'),
                'card_brand' => $value('card_brand'),
                'card_last4' => substr(preg_replace('/\D+/', '', $value('card_last4')) ?: '', -4),
                'card_expiry' => $value('card_expiry'),
            ]),
            'wallet' => array_filter([
                'method' => 'wallet',
                'wallet_phone' => $value('wallet_phone'),
                'wallet_telecom' => $value('wallet_telecom'),
            ]),
            'paypal' => array_filter([
                'method' => 'paypal',
                'paypal_email' => $value('paypal_email'),
                'paypal_name' => $value('paypal_name'),
            ]),
            default => ['method' => $method],
        };
    }
}
