<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use App\Models\Favorite;
use App\Models\Vendor;
use App\Support\AppClient;
use App\Support\Feature;
use App\Support\Roles;
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
        Roles::abortUnlessCan($user, 'save_favorites', 'Favorites are disabled for this role.');
        $vendors = $user->favorites()
            ->with(['vendor.portfolios', 'vendor.city', 'vendor.badges', 'vendor.categories', 'vendor.vendorType'])
            ->latest()
            ->get()
            ->map(fn (Favorite $favorite): ?array => $favorite->vendor?->isMarketplaceVisible() ? $this->app->publicCard($favorite->vendor) : null)
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
        abort_unless($vendor->isMarketplaceVisible(), 404);

        $user = AppClient::requireUser();
        Roles::abortUnlessCan($user, 'save_favorites', 'Favorites are disabled for this role.');
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
        Roles::abortUnlessCan($user, 'save_favorites', 'Favorites are disabled for this role.');
        $user->favoriteVendors()->detach($vendor->id);

        return response()->json(['favorited' => false, 'id' => $vendor->id]);
    }

    public function notifications(): JsonResponse
    {
        abort_unless(Feature::enabled('notifications'), 404);

        $user = AppClient::requireUser();
        Roles::abortUnlessCan($user, 'receive_notifications', 'Notifications are disabled for this role.');

        $rows = $user->notifications()->latest()->limit(80)->get();
        $unread = (int) $user->notifications()->whereNull('read_at')->count();

        return response()->json([
            'unread' => $unread,
            'notifications' => $rows->map(fn (DatabaseNotification $item): array => $this->present($item))->values()->all(),
        ]);
    }

    public function read(string $notification): JsonResponse
    {
        abort_unless(Feature::enabled('notifications'), 404);

        $user = AppClient::requireUser();
        Roles::abortUnlessCan($user, 'receive_notifications', 'Notifications are disabled for this role.');
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
        Roles::abortUnlessCan($user, 'receive_notifications', 'Notifications are disabled for this role.');
        $user->notifications()->whereNull('read_at')->update(['read_at' => now()]);

        return response()->json(['unread' => 0]);
    }

    public function clearAll(): JsonResponse
    {
        abort_unless(Feature::enabled('notifications'), 404);

        $user = AppClient::requireUser();
        Roles::abortUnlessCan($user, 'receive_notifications', 'Notifications are disabled for this role.');
        $deleted = $user->notifications()->delete();

        return response()->json([
            'unread' => 0,
            'deleted' => $deleted,
            'notifications' => [],
        ]);
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

    public function registerDevice(Request $request): JsonResponse
    {
        abort_unless(Feature::enabled('notifications'), 404);

        $user = AppClient::requireUser();
        Roles::abortUnlessCan($user, 'receive_notifications', 'Notifications are disabled for this role.');

        $data = $request->validate([
            'token' => ['required', 'string', 'max:512'],
            'platform' => ['nullable', 'string', 'in:android,ios,web'],
        ]);

        if (str_starts_with($data['token'], 'lens-local-')) {
            return response()->json([
                'registered' => false,
                'message' => 'Local placeholder tokens are not registered for FCM push.',
            ], 422);
        }

        $token = DeviceToken::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'token' => $data['token'],
            ],
            [
                'platform' => $data['platform'] ?? 'android',
            ],
        );

        return response()->json([
            'registered' => true,
            'id' => $token->id,
            'platform' => $token->platform,
        ]);
    }

    public function forgetDevice(Request $request): JsonResponse
    {
        abort_unless(Feature::enabled('notifications'), 404);

        $user = AppClient::requireUser();
        $data = $request->validate([
            'token' => ['required', 'string', 'max:512'],
        ]);

        DeviceToken::query()
            ->where('user_id', $user->id)
            ->where('token', $data['token'])
            ->delete();

        return response()->json(['registered' => false]);
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
