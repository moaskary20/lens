<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Message;
use App\Models\ReplacementOffer;
use App\Models\Review;
use App\Models\Setting;
use App\Models\User;
use App\Models\Vendor;
use App\Services\CancellationService;
use App\Services\DeliveryService;
use App\Services\EscrowService;
use App\Services\ReputationService;
use App\Support\SearchEngine;
use Database\Seeders\LensSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketplaceOpsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(LensSeeder::class);
        $this->actingAs(User::query()->where('email', 'admin@lens.app')->firstOrFail());
    }

    public function test_protected_upload_stays_locked_until_approve(): void
    {
        $booking = $this->photographerBooking(now()->addDays(4));
        $escrow = app(EscrowService::class);
        $delivery = app(DeliveryService::class);

        $escrow->checkout($booking);
        $file = $delivery->upload($booking->fresh(), 'deliverables/edit-v1.jpg', 'edit-v1.jpg');

        $this->assertTrue($file->is_watermarked);
        $this->assertFalse($file->is_unlocked);
        $this->assertSame('delivered', $booking->fresh()->status);
        $this->assertFalse($delivery->previewState($booking->fresh())['downloads_unlocked']);
        $this->assertSame('Lens Protected', $delivery->previewState($booking->fresh())['watermark_text']);
    }

    public function test_revision_keeps_escrow_and_posts_chat_note(): void
    {
        $booking = $this->photographerBooking(now()->addDays(4));
        $escrow = app(EscrowService::class);
        $delivery = app(DeliveryService::class);

        $escrow->checkout($booking);
        $delivery->upload($booking->fresh(), 'deliverables/edit-v1.jpg', 'edit-v1.jpg');
        $delivery->requestRevision($booking->fresh(), 'Soften the shadows on the cake.', $booking->client_id);

        $booking->refresh();
        $this->assertSame('in_revision', $booking->status);
        $this->assertSame('held', $booking->escrow_status);
        $this->assertSame(1, $booking->revision_count);
        $this->assertSame(0, $booking->payouts()->count());
        $this->assertTrue(Message::query()->where('body', 'Soften the shadows on the cake.')->exists());
    }

    public function test_approve_unlocks_originals_and_releases_vendor_net(): void
    {
        $booking = $this->photographerBooking(now()->addDays(4));
        $vendor = $booking->vendor;
        $completed = $vendor->completed_sessions;

        $escrow = app(EscrowService::class);
        $delivery = app(DeliveryService::class);
        $escrow->checkout($booking);
        $delivery->upload($booking->fresh(), 'deliverables/final.jpg', 'final.jpg');
        $delivery->approve($booking->fresh());

        $booking->refresh();
        $this->assertSame('approved', $booking->status);
        $this->assertSame('released', $booking->escrow_status);
        $this->assertTrue((bool) $booking->deliverables()->first()?->is_unlocked);
        $this->assertTrue($delivery->previewState($booking)['downloads_unlocked']);
        $this->assertEquals(1440.0, (float) $booking->payouts()->sum('amount'));
        $this->assertSame($completed + 1, $vendor->fresh()->completed_sessions);
    }

    public function test_rejection_splits_eighty_ten_ten_and_logs_failed_session(): void
    {
        $booking = $this->photographerBooking(now()->addDays(4));
        $failed = $booking->vendor->failed_sessions;

        app(EscrowService::class)->checkout($booking);
        app(DeliveryService::class)->upload($booking->fresh(), 'deliverables/bad.jpg', 'bad.jpg');
        $dispute = app(DeliveryService::class)->rejectAndDispute($booking->fresh(), $booking->client_id, 'Looks nothing like the brief.');
        $this->assertSame('disputed', $booking->fresh()->status);
        $dispute = app(DeliveryService::class)->settle($dispute);

        $booking->refresh();
        $this->assertSame('failed', $booking->status);
        $this->assertSame('split', $booking->escrow_status);
        $this->assertEquals(80.0, (float) $dispute->client_refund_percent);
        $this->assertEquals(10.0, (float) $dispute->vendor_payout_percent);
        $this->assertEquals(10.0, (float) $dispute->platform_fee_percent);
        $this->assertEquals(1440.0, (float) $booking->escrowTransactions()->where('type', 'refund')->sum('amount'));
        $this->assertEquals(180.0, (float) $booking->escrowTransactions()->where('notes', 'like', '%vendor time%')->sum('amount'));
        $this->assertEquals(180.0, (float) $booking->escrowTransactions()->where('type', 'fee')->sum('amount'));
        $this->assertSame($failed + 1, $booking->vendor->fresh()->failed_sessions);
        $this->assertFalse((bool) $booking->deliverables()->first()?->is_unlocked);
    }

    public function test_client_cancel_over_72_hours_full_refund(): void
    {
        $booking = $this->heldBooking(now()->addHours(80));

        app(CancellationService::class)->cancel($booking, 'client', 'Plans changed');

        $booking->refresh();
        $this->assertSame('cancelled', $booking->status);
        $this->assertSame('refunded', $booking->escrow_status);
        $this->assertEquals(1800.0, (float) $booking->escrowTransactions()->where('type', 'refund')->sum('amount'));
        $this->assertEquals(180.0, (float) $booking->escrowTransactions()->where('type', 'fee')->sum('amount'));
        $this->assertSame(0, $booking->payouts()->count());
    }

    public function test_client_cancel_within_24_hours_pays_vendor_net(): void
    {
        $booking = $this->heldBooking(now()->addHours(10));

        $quote = app(CancellationService::class)->quote($booking, 'client');
        app(CancellationService::class)->cancel($booking, 'client');

        $booking->refresh();
        $this->assertSame('Client cancel < 24 hours', $quote['policy']->name);
        $this->assertEquals(0.0, $quote['client_refund']);
        $this->assertEquals(1440.0, $quote['vendor_net']);
        $this->assertSame('released', $booking->escrow_status);
        $this->assertEquals(1440.0, (float) $booking->payouts()->sum('amount'));
    }

    public function test_same_day_vendor_cancel_charges_50_percent_and_opens_replacement(): void
    {
        $booking = $this->heldBooking(now()->addHours(6));

        $quote = app(CancellationService::class)->quote($booking, 'vendor');
        app(CancellationService::class)->cancel($booking, 'vendor', 'No-show');

        $booking->refresh();
        $this->assertSame('Same-day vendor cancel / no-show', $quote['policy']->name);
        $this->assertEquals(900.0, $quote['vendor_penalty']);
        $this->assertEquals(1800.0, (float) $booking->escrowTransactions()->where('type', 'refund')->sum('amount'));
        $this->assertEquals(900.0, (float) $booking->escrowTransactions()->where('type', 'penalty')->sum('amount'));
        $this->assertTrue(ReplacementOffer::query()->where('booking_id', $booking->id)->where('status', 'open')->exists());
    }

    public function test_vendor_cancel_over_24_hours_charges_25_percent(): void
    {
        $booking = $this->heldBooking(now()->addHours(30));

        $quote = app(CancellationService::class)->quote($booking, 'vendor');
        $this->assertSame('Vendor cancel > 24 hours', $quote['policy']->name);
        $this->assertEquals(450.0, $quote['vendor_penalty']);
        $this->assertEquals(1800.0, $quote['client_refund']);
    }

    public function test_reviews_refresh_rating_and_award_automatic_badges(): void
    {
        $vendor = Vendor::query()->whereHas('vendorType', fn ($query) => $query->where('slug', 'studio'))->firstOrFail();
        $client = User::query()->where('email', 'client@lens.app')->firstOrFail();
        $vendor->update([
            'completed_sessions' => 20,
            'rating_avg' => 0,
            'rating_count' => 0,
            'is_featured' => false,
        ]);
        $vendor->badges()->sync([]);

        foreach ([5, 5, 5] as $i => $stars) {
            $booking = Booking::query()->create([
                'reference' => 'LN-REV-'.$i,
                'client_id' => $client->id,
                'vendor_id' => $vendor->id,
                'status' => 'approved',
                'session_price' => 1000,
            ]);

            Review::query()->create([
                'booking_id' => $booking->id,
                'client_id' => $client->id,
                'vendor_id' => $vendor->id,
                'rating' => $stars,
                'comment' => 'Excellent session',
                'is_visible' => true,
            ]);
        }

        $vendor = $vendor->fresh(['badges']);
        $this->assertEquals(5.0, (float) $vendor->rating_avg);
        $this->assertSame(3, $vendor->rating_count);
        $this->assertTrue($vendor->badges->contains('slug', 'top-rated'));
        $this->assertTrue($vendor->badges->contains('slug', 'popular'));
        $this->assertTrue($vendor->is_featured);
    }

    public function test_search_ranking_can_ignore_ratings_from_admin_setting(): void
    {
        Setting::setValue('reputation.ranking_uses_ratings', false);

        $blueprint = SearchEngine::blueprint();

        $this->assertSame(0, $blueprint['settings']['weight_rating']);
        $this->assertFalse($blueprint['settings']['rank_reviews']);
        $this->assertFalse($blueprint['reputation']['ranking_uses_ratings']);
    }

    public function test_reputation_refresh_action_keeps_manual_badges(): void
    {
        $vendor = Vendor::query()->where('display_name', 'Fahad Studio Light')->firstOrFail();
        $this->assertTrue($vendor->badges->contains('slug', 'verified'));

        app(ReputationService::class)->refresh($vendor->fresh());

        $this->assertTrue($vendor->fresh('badges')->badges->contains('slug', 'verified'));
    }

    protected function photographerBooking(\DateTimeInterface $scheduledAt): Booking
    {
        $vendor = Vendor::query()->whereHas('vendorType', fn ($query) => $query->where('slug', 'photographer'))->firstOrFail();
        $client = User::query()->where('email', 'client@lens.app')->firstOrFail();

        return Booking::query()->create([
            'reference' => 'LN-OPS-'.uniqid(),
            'client_id' => $client->id,
            'vendor_id' => $vendor->id,
            'status' => 'accepted',
            'scheduled_at' => $scheduledAt,
            'session_price' => 1800,
            'escrow_status' => 'none',
            'payout_status' => 'none',
        ]);
    }

    protected function heldBooking(\DateTimeInterface $scheduledAt): Booking
    {
        $booking = $this->photographerBooking($scheduledAt);
        app(EscrowService::class)->checkout($booking);

        return $booking->fresh();
    }
}
