<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Api\AppController;
use App\Http\Controllers\Api\ClientInboxController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Site\Concerns\InteractsWithSite;
use App\Models\Vendor;
use App\Support\SiteAuth;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InboxController extends Controller
{
    use InteractsWithSite;

    public function __construct(
        protected ClientInboxController $api,
        protected AppController $app,
    ) {}

    public function favorites(): View
    {
        SiteAuth::applyClientHeader(request());
        $payload = $this->jsonData($this->api->favorites());
        $this->shareShell('account');

        return view('site.inbox.favorites', [
            'favorites' => $payload['favorites'] ?? $payload['vendors'] ?? $payload,
        ]);
    }

    public function saveFavorite(Request $request, Vendor $vendor): JsonResponse|RedirectResponse
    {
        SiteAuth::applyClientHeader($request);
        $response = $this->api->save($vendor);
        if ($request->expectsJson()) {
            return $response;
        }

        return back()->with('status', 'Saved to favorites.');
    }

    public function forgetFavorite(Request $request, Vendor $vendor): JsonResponse|RedirectResponse
    {
        SiteAuth::applyClientHeader($request);
        $response = $this->api->forget($vendor);
        if ($request->expectsJson()) {
            return $response;
        }

        return back()->with('status', 'Removed from favorites.');
    }

    public function notifications(): View
    {
        SiteAuth::applyClientHeader(request());
        $payload = $this->jsonData($this->api->notifications());
        $this->shareShell('account');

        return view('site.inbox.notifications', [
            'notifications' => $payload['notifications'] ?? $payload,
        ]);
    }

    public function readNotification(Request $request, string $notification): RedirectResponse
    {
        SiteAuth::applyClientHeader($request);
        $this->api->read($notification);

        return back();
    }

    public function readAll(Request $request): RedirectResponse
    {
        SiteAuth::applyClientHeader($request);
        $this->api->readAll();

        return back()->with('status', 'All notifications marked read.');
    }

    public function clearAll(Request $request): RedirectResponse
    {
        SiteAuth::applyClientHeader($request);
        $this->api->clearAll();

        return back()->with('status', 'Notifications cleared.');
    }
}
