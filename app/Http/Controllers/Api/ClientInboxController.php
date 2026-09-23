<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Favorite;
use App\Models\Vendor;
use App\Support\AppClient;
use App\Support\Feature;
use App\Support\LensNotifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class ClientInboxController extends Controller
{
    public function __construct(protected AppController $app) {}

    public function favorites(): JsonResponse
    {
        abort_unless(Feature::enabled('favorites'), 404);

        $user = AppClient::requireUser();
        $vendors = $user->favorites()
            ->with(['vendor.portfolios', 'vendor.city', 'vendor.badges', 'vendor.categories', 'vendor.vendorType'])
            ->latest()
            ->get()
            ->map(fn (Favorite $favorite): ?array => $favorite->vendor?->is_active ? $this->app->publicCard($favorite->vendor) : null)
            ->filter()
            ->values()
            ->all();

        return response()->json([
            'ids' => array_column($vendors, 'id'),
            'vendors' => $vendors,
        ]);
    }

    public function save(Vendor $vendor): JsonResponse
    {
        abort_unless(Feature::enabled('favorites'), 404);
        abort_unless($vendor->is_active, 404);

        $user = AppClient::requireUser();
        $user->favoriteVendors()->syncWithoutDetaching([$vendor->id]);

        return response()->json([
            'favorited' => true,
            'vendor' => $this->app->publicCard($vendor->loadMissing(['portfolios', 'city', 'badges', 'categories', 'vendorType'])),
        ]);
    }

    public function forget(Vendor $vendor): JsonResponse
    {
        abort_unless(Feature::enabled('favorites'), 404);

        $user = AppClient::requireUser();
        $user->favoriteVendors()->detach($vendor->id);

        return response()->json(['favorited' => false, 'id' => $vendor->id]);
    }

    public function notifications(): JsonResponse
    {
        abort_unless(Feature::enabled('notifications'), 404);

        $user = AppClient::requireUser();
        $items = $user->notifications()->latest()->limit(80)->get()->map(fn (DatabaseNotification $item): array => $this->present($item));

        return response()->json([
            'unread' => $user->unreadNotifications()->count(),
            'notifications' => $items->values()->all(),
        ]);
    }

    public function read(string $notification): JsonResponse
    {
        abort_unless(Feature::enabled('notifications'), 404);

        $user = AppClient::requireUser();
        $item = $user->notifications()->whereKey($notification)->firstOrFail();
        $item->markAsRead();

        return response()->json([
            'unread' => $user->unreadNotifications()->count(),
            'notification' => $this->present($item->fresh()),
        ]);
    }

    public function readAll(): JsonResponse
    {
        abort_unless(Feature::enabled('notifications'), 404);

        $user = AppClient::requireUser();
        $user->unreadNotifications->markAsRead();

        return response()->json(['unread' => 0]);
    }

    public function broadcast(Request $request): JsonResponse
    {
        abort_unless(Feature::enabled('notifications'), 404);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'body' => ['required', 'string', 'max:500'],
        ]);

        $count = LensNotifier::toClients($data['title'], $data['body']);

        return response()->json(['sent' => $count]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function present(DatabaseNotification $item): array
    {
        $data = is_array($item->data) ? $item->data : [];

        return [
            'id' => $item->id,
            'title' => (string) ($data['title'] ?? 'Lens'),
            'body' => (string) ($data['body'] ?? ''),
            'event' => (string) ($data['event'] ?? 'general'),
            'read' => $item->read_at !== null,
            'created_at' => $item->created_at?->toIso8601String(),
        ];
    }
}
