<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Api\AppController;
use App\Http\Controllers\Api\BookingController as ApiBookingController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Site\Concerns\InteractsWithSite;
use App\Models\Booking;
use App\Models\Vendor;
use App\Support\BookProjectCatalog;
use App\Support\VendorNamePrivacy;
use App\Support\SiteAuth;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BookingFlowController extends Controller
{
    use InteractsWithSite;

    public function __construct(
        protected AppController $app,
        protected ApiBookingController $api,
    ) {}

    public function start(Vendor $vendor): View|RedirectResponse
    {
        abort_unless($vendor->isMarketplaceVisible(), 404);
        $user = SiteAuth::user();
        if (! $user) {
            return redirect()->guest(route('site.login'));
        }
        abort_unless($user->canUseClientApp(), 403);

        $this->shareShell('bookings');

        $profile = VendorNamePrivacy::present(
            $this->app->publicProfile($vendor, false),
            $this->webUser(),
            forceReveal: true,
        );
        $vendorType = (string) ($vendor->vendorType?->slug ?? '');
        $remoteTypes = collect($profile['project_types'] ?? [])
            ->map(fn ($item): string => trim((string) (is_array($item) ? ($item['label'] ?? '') : $item)))
            ->filter()
            ->values()
            ->all();

        return view('site.book.start', [
            'vendor' => $profile,
            'vendorType' => $vendorType,
            'typeLabel' => BookProjectCatalog::typeLabel($vendorType),
            'typeOptions' => $remoteTypes !== [] ? $remoteTypes : BookProjectCatalog::typeOptions($vendorType),
            'extraFields' => BookProjectCatalog::extraFields($vendorType),
            'timeSlots' => BookProjectCatalog::timeSlots(),
            'quoteUrl' => route('site.book.quote'),
        ]);
    }

    public function quote(Request $request): JsonResponse|RedirectResponse
    {
        SiteAuth::applyClientHeader($request);
        $response = $this->api->quote($request);
        if ($request->expectsJson()) {
            return $response;
        }
        if ($response->getStatusCode() >= 400) {
            return back()->withErrors($this->jsonData($response)['errors'] ?? ['form' => 'Quote failed.'])->withInput();
        }

        return back()->with('quote', $this->jsonData($response))->withInput();
    }

    public function store(Request $request): RedirectResponse
    {
        SiteAuth::applyClientHeader($request);
        $response = $this->api->store($request);
        $payload = $this->jsonData($response);
        if ($response->getStatusCode() >= 400) {
            return back()->withErrors($payload['errors'] ?? ['form' => $payload['message'] ?? 'Booking failed.'])->withInput();
        }

        $id = $payload['id'] ?? $payload['booking']['id'] ?? null;

        return redirect()->route('site.book.confirmed', ['booking' => $id])->with('status', 'Booking confirmed.');
    }

    public function confirmed(Booking $booking): View
    {
        $user = SiteAuth::user();
        abort_unless($user && ((int) $booking->client_id === (int) $user->id), 404);
        $this->shareShell('bookings');

        return view('site.book.confirmed', [
            'booking' => $booking->load(['vendor.vendorType', 'vendor.city']),
        ]);
    }

    public function approve(Request $request, Booking $booking): RedirectResponse
    {
        SiteAuth::applyClientHeader($request);
        $response = $this->api->approve($booking);
        if ($response->getStatusCode() >= 400) {
            return back()->withErrors($this->jsonData($response)['errors'] ?? ['form' => 'Could not approve.']);
        }

        return back()->with('status', 'Delivery approved.');
    }

    public function refuse(Request $request, Booking $booking): RedirectResponse
    {
        SiteAuth::applyClientHeader($request);
        $response = $this->api->refuse($request, $booking);
        if ($response->getStatusCode() >= 400) {
            return back()->withErrors($this->jsonData($response)['errors'] ?? ['form' => 'Could not refuse.']);
        }

        return back()->with('status', 'Delivery refused.');
    }

    public function cancel(Request $request, Booking $booking): RedirectResponse
    {
        SiteAuth::applyClientHeader($request);
        $response = $this->api->cancelByVendor($request, $booking);
        if ($response->getStatusCode() >= 400) {
            return back()->withErrors($this->jsonData($response)['errors'] ?? ['form' => 'Could not cancel.']);
        }

        return back()->with('status', 'Booking canceled.');
    }

    public function dispute(Request $request, Booking $booking): RedirectResponse
    {
        SiteAuth::applyClientHeader($request);
        $response = $this->api->openDispute($request, $booking);
        if ($response->getStatusCode() >= 400) {
            return back()->withErrors($this->jsonData($response)['errors'] ?? ['form' => 'Could not open dispute.']);
        }

        return back()->with('status', 'Dispute opened.');
    }

    public function deliverables(Booking $booking): View
    {
        SiteAuth::applyClientHeader(request());
        $payload = $this->jsonData($this->api->deliverables($booking));
        $this->shareShell('bookings');

        return view('site.book.deliverables', [
            'booking' => $booking->load('vendor'),
            'deliverables' => $payload['deliverables'] ?? $payload,
        ]);
    }

    public function uploadDelivery(Request $request, Booking $booking): RedirectResponse
    {
        SiteAuth::applyClientHeader($request);
        $response = $this->api->uploadDelivery($request, $booking);
        if ($response->getStatusCode() >= 400) {
            return back()->withErrors($this->jsonData($response)['errors'] ?? ['form' => 'Upload failed.']);
        }

        return back()->with('status', 'Delivery uploaded.');
    }

    public function requestEdit(Request $request, Booking $booking): RedirectResponse
    {
        SiteAuth::applyClientHeader($request);
        $response = $this->api->requestEdit($request, $booking);
        if ($response->getStatusCode() >= 400) {
            return back()->withErrors($this->jsonData($response)['errors'] ?? ['form' => 'Could not request edit.']);
        }

        return back()->with('status', 'Edit requested.');
    }
}
