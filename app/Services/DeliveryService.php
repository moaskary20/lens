<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Conversation;
use App\Models\Deliverable;
use App\Models\Dispute;
use App\Models\Message;
use App\Support\DeliveryProtection;
use App\Support\Feature;
use App\Support\LensNotifier;
use App\Support\StorageQuota;
use Illuminate\Support\Str;
use LogicException;

class DeliveryService
{
    public function __construct(
        protected EscrowService $escrow,
        protected ReputationService $reputation,
    ) {}

    public function canUpload(Booking $booking): bool
    {
        return in_array($booking->status, ['accepted', 'checked_in', 'in_progress', 'delivered', 'in_revision'], true);
    }

    public function upload(Booking $booking, string $path, ?string $originalName = null): Deliverable
    {
        if (! $this->canUpload($booking)) {
            throw new LogicException('Files can be uploaded only while the session is in delivery or revision.');
        }

        $settings = DeliveryProtection::settings();
        $version = ((int) $booking->deliverables()->max('version')) + 1;

        $booking->loadMissing('deliverables');
        StorageQuota::assertBookingFits($booking, StorageQuota::fileBytes($path));

        $deliverable = Deliverable::query()->create([
            'booking_id' => $booking->id,
            'path' => $path,
            'original_name' => $originalName,
            'is_watermarked' => Feature::enabled('protected_delivery') && $settings['watermark_enabled'],
            'is_unlocked' => false,
            'version' => $version,
        ]);

        if (in_array($booking->status, ['accepted', 'checked_in', 'in_progress', 'in_revision'], true)) {
            $booking->update(['status' => 'delivered']);
        }

        return $deliverable->fresh();
    }

    public function requestRevision(Booking $booking, ?string $note = null, ?int $senderId = null, array $attachmentPaths = []): Booking
    {
        if (! Feature::enabled('revisions')) {
            throw new LogicException('Revision cycles are disabled.');
        }

        $updated = $this->escrow->requestRevision($booking);
        if (filled($note)) {
            $updated->update(['notes' => $note]);
        }

        $this->postRevisionThread($updated, $note, $senderId, $attachmentPaths);

        LensNotifier::staff(
            LensNotifier::BOOKING_STATUS,
            'Revision requested',
            $updated->reference.(filled($note) ? ' · '.Str::limit(trim($note), 120) : ''),
            $updated->client_id,
        );

        return $updated->fresh();
    }

    /**
     * @param  array<int, string>  $attachmentPaths
     */
    protected function postRevisionThread(Booking $booking, ?string $note, ?int $senderId, array $attachmentPaths): void
    {
        if (! DeliveryProtection::settings()['revision_opens_chat'] || ! Feature::enabled('chat')) {
            return;
        }
        if (! filled($note) && $attachmentPaths === []) {
            return;
        }

        $conversation = Conversation::query()->firstOrCreate(
            [
                'booking_id' => $booking->id,
                'client_id' => $booking->client_id,
                'vendor_id' => $booking->vendor_id,
            ],
            ['last_message_at' => now()],
        );

        $urls = array_values(array_filter(array_map(
            fn (string $path): ?string => $path === '' ? null : asset('storage/'.$path),
            $attachmentPaths,
        )));

        Message::query()->create([
            'conversation_id' => $conversation->id,
            'sender_id' => $senderId ?? $booking->client_id,
            'body' => filled($note) ? $note : 'Reference images attached.',
            'type' => filled($note) ? 'text' : 'images',
            'attachments' => $urls === [] ? null : [
                'paths' => array_values($attachmentPaths),
                'urls' => $urls,
            ],
        ]);

        $conversation->update(['last_message_at' => now()]);
    }

    public function approve(Booking $booking): Booking
    {
        $updated = $this->escrow->approve($booking);
        $this->reputation->bump($updated->vendor, 'completed_sessions');

        return $updated;
    }

    /**
     * Opens a dispute/complaint and freezes the booking for admin review.
     * Funds stay in escrow until the admin refunds, pays the vendor, splits, or closes.
     */
    public function rejectAndDispute(Booking $booking, int $openedBy, string $reason): Dispute
    {
        return app(DisputeService::class)->open($booking, $openedBy, $reason, DisputeService::KIND_DISPUTE);
    }

    public function settle(Dispute $dispute): Dispute
    {
        return app(DisputeService::class)->split($dispute);
    }

    public function previewState(Booking $booking): array
    {
        $settings = DeliveryProtection::settings();
        $approved = in_array($booking->status, ['approved', 'completed'], true);

        return [
            'watermark_text' => $settings['watermark_text'],
            'watermarked' => $settings['watermark_enabled'] && ! $approved,
            'anti_screenshot' => $settings['anti_screenshot'] && ! $approved,
            'block_recording' => $settings['block_recording'] && ! $approved,
            'downloads_unlocked' => $approved || ! DeliveryProtection::downloadsLocked(),
        ];
    }
}
