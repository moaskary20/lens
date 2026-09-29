<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\City;
use App\Models\Coupon;
use App\Models\Vendor;
use App\Models\Dispute;
use App\Services\DeliveryService;
use App\Services\DisputeService;
use App\Services\PromoService;
use App\Support\Feature;
use App\Support\AppClient;
use App\Support\Roles;
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
        Roles::abortUnlessCan($user, 'book_sessions', 'Booking is disabled for this role.');

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
        Roles::abortUnlessCan($user, 'book_sessions', 'Booking is disabled for this role.');

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
        $booking = $this->ownedBooking($booking)->load(['deliverables', 'vendor.vendorType', 'vendor.portfolios']);
        $state = app(DeliveryService::class)->previewState($booking);
        $vendor = $booking->vendor;
        $unlocked = (bool) $state['downloads_unlocked'];
        $watermarked = (bool) $state['watermarked'];
        $files = $booking->deliverables->map(fn ($file): array => [
            'id' => $file->id,
            'name' => $file->original_name ?: basename((string) $file->path),
            'version' => $file->version,
            'watermarked' => $watermarked || (bool) $file->is_watermarked,
            'unlocked' => (bool) $file->is_unlocked || $unlocked,
            'url' => $this->publicFile($file->path),
        ]);

        if ($files->isEmpty()) {
            $files = collect($vendor?->portfolios ?? [])
                ->filter(fn ($item): bool => filled($item->path) && $item->type !== 'link')
                ->values()
                ->map(fn ($item, int $index): array => [
                    'id' => $item->id,
                    'name' => $this->previewFileName($item->title ?: $booking->project_name, $index),
                    'version' => 1,
                    'watermarked' => $watermarked,
                    'unlocked' => $unlocked,
                    'url' => $this->publicFile($item->path),
                ]);
        }

        return response()->json([
            'project_name' => $booking->project_name ?: 'Session files',
            'vendor_name' => $vendor?->display_name,
            'vendor_type' => $vendor?->vendorType?->name_en,
            'vendor_type_slug' => $vendor?->vendorType?->slug,
            'vendor_photo' => $this->publicFile($vendor?->profile_photo),
            'date_label' => $booking->scheduled_at?->format('M j, Y'),
            'total' => (float) ($booking->total_paid ?: $booking->session_price ?: 0),
            'status' => $booking->status,
            'watermark_text' => $state['watermark_text'],
            'watermarked' => $watermarked,
            'preview' => $state,
            'deliverables' => $files->values()->all(),
        ]);
    }

    public function requestEdit(Request $request, Booking $booking): JsonResponse
    {
        $booking = $this->ownedBooking($booking);
        Roles::abortUnlessCan(AppClient::requireUser(), 'request_revisions', 'Revisions are disabled for this role.');
        $data = $request->validate([
            'note' => ['nullable', 'string', 'max:2000'],
            'change' => ['nullable', 'string', 'max:500'],
            'comment' => ['nullable', 'string', 'max:500'],
            'file_name' => ['nullable', 'string', 'max:255'],
            'quick_requests' => ['nullable', 'array', 'max:8'],
            'quick_requests.*' => ['string', 'max:40'],
            'references' => ['nullable', 'array', 'max:10'],
            'references.*' => ['file', 'max:10240'],
        ]);

        $note = $this->composeRevisionNote($data);
        abort_if($note === '', 422, 'Describe the edit you need.');

        $paths = [];
        foreach ($request->file('references', []) as $file) {
            if ($file) {
                $paths[] = $file->store('revision-refs/'.$booking->id, 'public');
            }
        }

        try {
            $updated = app(DeliveryService::class)->requestRevision($booking, $note, $booking->client_id, $paths);
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
        Roles::abortUnlessCan(AppClient::requireUser(), 'approve_payouts', 'Approving delivery is disabled for this role.');

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
        Roles::abortUnlessCan(AppClient::requireUser(), 'open_disputes', 'Disputes are disabled for this role.');
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

    public function disputes(): JsonResponse
    {
        $user = AppClient::requireUser();
        abort_unless($user->canUseClientApp(), 403, 'Only clients can open disputes.');

        $items = Dispute::query()
            ->with(['booking.vendor.vendorType', 'booking.category'])
            ->where(function ($query) use ($user): void {
                $query->where('opened_by', $user->id)
                    ->orWhereHas('booking', fn ($inner) => $inner->where('client_id', $user->id));
            })
            ->latest('id')
            ->limit(40)
            ->get()
            ->map(fn (Dispute $dispute): array => $dispute->toApp())
            ->values()
            ->all();

        return response()->json(['disputes' => $items]);
    }

    public function showDispute(Booking $booking): JsonResponse
    {
        $booking = $this->ownedBooking($booking)->load(['dispute.booking.vendor.vendorType']);
        abort_unless($booking->dispute, 404, 'No dispute on this booking.');

        return response()->json($booking->dispute->toApp());
    }

    public function openDispute(Request $request, Booking $booking): JsonResponse
    {
        $booking = $this->ownedBooking($booking);
        abort_unless(Feature::enabled('disputes'), 422, 'Disputes are disabled.');
        Roles::abortUnlessCan(AppClient::requireUser(), 'open_disputes', 'Disputes are disabled for this role.');
        $data = $request->validate([
            'kind' => ['required', Rule::in(['dispute', 'complaint'])],
            'reason' => ['required', 'string', 'max:2000'],
        ]);

        try {
            $dispute = app(DisputeService::class)->open(
                $booking,
                $booking->client_id,
                $data['reason'],
                $data['kind'],
            );
        } catch (LogicException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            ...$dispute->toApp(),
            'message' => 'Dispute opened. Escrow stays held until Lens support decides.',
        ], 201);
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

    protected function composeRevisionNote(array $data): string
    {
        if (filled($data['note'] ?? null)) {
            return trim((string) $data['note']);
        }

        $parts = [];
        $quick = array_values(array_filter($data['quick_requests'] ?? []));
        if ($quick !== []) {
            $parts[] = 'Quick requests: '.implode(', ', $quick);
        }
        if (filled($data['change'] ?? null)) {
            $parts[] = trim((string) $data['change']);
        }
        if (filled($data['comment'] ?? null)) {
            $file = filled($data['file_name'] ?? null) ? $data['file_name'] : 'selected photo';
            $parts[] = 'Photo '.$file.': '.trim((string) $data['comment']);
        }

        return implode("\n", $parts);
    }

    protected function publicFile(?string $path): ?string
    {
        $path = trim((string) $path);
        if ($path === '') {
            return null;
        }
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return asset('storage/'.$path);
    }

    protected function previewFileName(?string $title, int $index): string
    {
        $base = preg_replace('/[^A-Za-z0-9]+/', '_', trim((string) $title)) ?: 'Shot';
        $base = trim((string) $base, '_');

        return $base.'_'.str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT).'.jpg';
    }
}
