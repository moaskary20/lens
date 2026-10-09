<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Models\Vendor;
use App\Support\AppClient;
use App\Support\Feature;
use App\Support\Roles;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ChatController extends Controller
{
    public function open(Request $request): JsonResponse
    {
        $this->assertChatEnabled();
        $user = AppClient::requireUser();
        Roles::abortUnlessCan($user, 'in_app_chat', 'Chat is disabled for this role.');

        $data = $request->validate([
            'vendor_id' => ['nullable', 'integer', 'exists:vendors,id', 'required_without:client_id'],
            'client_id' => ['nullable', 'integer', 'exists:users,id', 'required_without:vendor_id'],
            'booking_id' => ['nullable', 'integer', 'exists:bookings,id'],
        ]);

        if ($user->isVendor()) {
            abort_unless($user->vendor && isset($data['booking_id'], $data['client_id']), 422, 'Vendor chat requires a client and booking.');
            $booking = Booking::query()
                ->whereKey($data['booking_id'])
                ->where('vendor_id', $user->vendor->id)
                ->where('client_id', $data['client_id'])
                ->firstOrFail();
            $vendor = $user->vendor;
            $clientId = $booking->client_id;
        } else {
            abort_unless($user->canUseClientApp(), 403, 'Only clients and vendors can open a booking chat.');
            $vendor = Vendor::query()->findOrFail($data['vendor_id']);
            $booking = $this->resolveBooking($user->id, $vendor->id, $data['booking_id'] ?? null);
            $clientId = $user->id;
        }

        $conversation = Conversation::query()->firstOrCreate(
            [
                'client_id' => $clientId,
                'vendor_id' => $vendor->id,
            ],
            [
                'booking_id' => $booking?->id,
                'last_message_at' => now(),
            ],
        );

        if ($booking && (int) $conversation->booking_id !== (int) $booking->id) {
            $conversation->update(['booking_id' => $booking->id]);
        }

        return response()->json($this->present($conversation->fresh(['booking.vendor.vendorType', 'vendor', 'client']), $user));
    }

    public function show(Conversation $conversation): JsonResponse
    {
        $this->assertChatEnabled();
        $user = AppClient::requireUser();
        Roles::abortUnlessCan($user, 'in_app_chat', 'Chat is disabled for this role.');
        $this->authorizeConversation($conversation, $user);

        return response()->json($this->present($conversation->load(['booking.vendor.vendorType', 'vendor', 'client']), $user));
    }

    public function store(Request $request, Conversation $conversation): JsonResponse
    {
        $this->assertChatEnabled();
        $user = AppClient::requireUser();
        Roles::abortUnlessCan($user, 'in_app_chat', 'Chat is disabled for this role.');
        $this->authorizeConversation($conversation, $user);

        $data = $request->validate([
            'type' => ['nullable', Rule::in(['text', 'images', 'file', 'audio', 'location'])],
            'body' => ['nullable', 'string', 'max:4000'],
            'location_text' => ['nullable', 'string', 'max:255'],
            'location_lat' => ['nullable', 'numeric'],
            'location_lng' => ['nullable', 'numeric'],
            'duration' => ['nullable', 'string', 'max:12'],
        ]);

        $type = $data['type'] ?? 'text';
        $files = $request->file('files', []);
        if (! is_array($files)) {
            $files = $files ? [$files] : [];
        }

        $attachments = [];
        $urls = [];
        foreach ($files as $file) {
            $path = $file->store('chat', 'public');
            $urls[] = $this->publicUrl($path);
            $attachments['paths'][] = $path;
            $attachments['file_name'] = $file->getClientOriginalName();
            $attachments['file_size'] = $this->humanSize((int) $file->getSize());
        }
        if ($urls !== []) {
            $attachments['urls'] = $urls;
        }
        if (filled($data['duration'] ?? null)) {
            $attachments['duration'] = $data['duration'];
        }
        if ($type === 'location') {
            $attachments['location'] = $data['location_text'] ?? '';
            $attachments['latitude'] = $data['location_lat'] ?? null;
            $attachments['longitude'] = $data['location_lng'] ?? null;
        }

        $body = trim((string) ($data['body'] ?? ''));
        if ($body === '') {
            $body = match ($type) {
                'images' => 'Shared reference images.',
                'file' => $attachments['file_name'] ?? 'Shared a file.',
                'audio' => 'Voice note'.(isset($attachments['duration']) ? ' '.$attachments['duration'] : ''),
                'location' => $attachments['location'] ?: 'Shared a location.',
                default => '',
            };
        }

        abort_if($body === '' && $attachments === [], 422, 'Write a message or attach a file.');

        $message = Message::query()->create([
            'conversation_id' => $conversation->id,
            'sender_id' => $user->id,
            'body' => $body,
            'type' => $type,
            'attachments' => $attachments === [] ? null : $attachments,
        ]);
        $conversation->update(['last_message_at' => now()]);

        return response()->json([
            'message' => $this->presentMessage($message->fresh('sender'), $user),
            ...$this->present($conversation->fresh(['booking.vendor.vendorType', 'vendor', 'client']), $user),
        ], 201);
    }

    protected function assertChatEnabled(): void
    {
        abort_unless(Feature::enabled('chat'), 403, 'Chat is disabled.');
    }

    protected function authorizeConversation(Conversation $conversation, User $user): void
    {
        if ($user->isClient() && (int) $conversation->client_id === (int) $user->id) {
            return;
        }
        if ($user->isVendor() && (int) $conversation->vendor_id === (int) ($user->vendor?->id ?: 0)) {
            return;
        }
        abort_unless($user->isAdmin() || $user->isSupervisor(), 403, 'You cannot open this chat.');
    }

    protected function resolveBooking(int $clientId, int $vendorId, mixed $bookingId): ?Booking
    {
        $query = Booking::query()->where('client_id', $clientId)->where('vendor_id', $vendorId);
        if ($bookingId) {
            return $query->where('id', $bookingId)->first();
        }

        return $query->latest('id')->first();
    }

    /**
     * @return array<string, mixed>
     */
    protected function present(Conversation $conversation, User $user): array
    {
        $booking = $conversation->booking;
        $vendor = $conversation->vendor;

        return [
            'id' => $conversation->id,
            'booking_id' => $conversation->booking_id,
            'session' => [
                'title' => $booking?->project_name ?: (($vendor?->vendorType?->name_en ?: 'Creative').' Session'),
                'type' => $vendor?->vendorType?->name_en ?: 'Session',
                'status' => $this->statusLabel($booking?->status),
                'when' => $booking?->scheduled_at?->format('l, j F Y • g:i A'),
                'where' => $booking?->location_text ?: ($vendor?->address ?: $vendor?->city?->name_en),
            ],
            'messages' => $conversation->messages()->with('sender')->orderBy('id')->get()
                ->map(fn (Message $message): array => $this->presentMessage($message, $user))
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function presentMessage(Message $message, User $user): array
    {
        $attachments = is_array($message->attachments) ? $message->attachments : [];
        $urls = collect($attachments['urls'] ?? [])
            ->map(fn ($url) => $this->publicUrl(is_string($url) ? $url : null) ?? (string) $url)
            ->filter()
            ->values()
            ->all();
        if ($urls === [] && isset($attachments['paths']) && is_array($attachments['paths'])) {
            $urls = collect($attachments['paths'])->map(fn ($path) => $this->publicUrl((string) $path))->filter()->values()->all();
        }

        return [
            'id' => $message->id,
            'mine' => (int) $message->sender_id === (int) $user->id,
            'type' => $message->type ?: 'text',
            'body' => $message->body,
            'time' => $message->created_at?->format('g:i A') ?: '',
            'day' => $message->created_at?->toDateString(),
            'urls' => $urls,
            'file_name' => $attachments['file_name'] ?? null,
            'file_size' => $attachments['file_size'] ?? null,
            'duration' => $attachments['duration'] ?? null,
            'location' => $attachments['location'] ?? null,
        ];
    }

    protected function statusLabel(?string $status): string
    {
        return match ($status) {
            'accepted', 'checked_in', 'in_progress' => 'Confirmed',
            'pending' => 'Pending',
            'delivered' => 'Delivered',
            'completed', 'approved' => 'Completed',
            'cancelled' => 'Cancelled',
            default => $status ? ucfirst(str_replace('_', ' ', $status)) : 'Confirmed',
        };
    }

    protected function publicUrl(?string $path): ?string
    {
        if (! filled($path)) {
            return null;
        }
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return asset('storage/'.$path);
    }

    protected function humanSize(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 1).' MB';
        }

        return max(1, (int) round($bytes / 1024)).' KB';
    }
}
