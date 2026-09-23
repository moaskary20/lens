<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Message;
use App\Models\User;
use App\Models\Vendor;
use App\Services\DeliveryService;
use App\Services\EscrowService;
use App\Support\Finance;
use Database\Seeders\LensSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApprovalWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(LensSeeder::class);
        $this->actingAs(User::query()->where('email', 'admin@lens.app')->firstOrFail());
    }

    public function test_scenario_a_approve_unlocks_downloads_and_releases_session_minus_commission(): void
    {
        $booking = $this->photographerSession();
        $delivery = app(DeliveryService::class);

        app(EscrowService::class)->checkout($booking);
        $file = $delivery->upload($booking->fresh(), 'deliverables/final.jpg', 'final.jpg');

        $this->assertFalse($file->is_unlocked);
        $this->assertFalse($delivery->previewState($booking->fresh())['downloads_unlocked']);

        $delivery->approve($booking->fresh());
        $booking->refresh();
        $quote = Finance::quote(1800);

        $this->assertSame('approved', $booking->status);
        $this->assertSame('released', $booking->escrow_status);
        $this->assertSame('pending', $booking->payout_status);
        $this->assertTrue((bool) $booking->deliverables()->first()?->is_unlocked);
        $this->assertTrue($delivery->previewState($booking)['downloads_unlocked']);
        $this->assertEquals($quote['vendor_commission'], (float) $booking->vendor_commission);
        $this->assertEquals(360.0, (float) $booking->vendor_commission);
        $this->assertEquals($quote['vendor_net'], (float) $booking->payouts()->sum('amount'));
        $this->assertEquals(1440.0, (float) $booking->payouts()->sum('amount'));
        $this->assertEquals(360.0, (float) $booking->escrowTransactions()->where('type', 'fee')->sum('amount'));
        $this->assertEquals(1440.0, (float) $booking->escrowTransactions()->where('type', 'release')->sum('amount'));
    }

    public function test_scenario_b_request_edit_holds_funds_chats_and_repeats_after_reupload(): void
    {
        $booking = $this->photographerSession();
        $delivery = app(DeliveryService::class);

        app(EscrowService::class)->checkout($booking);
        $v1 = $delivery->upload($booking->fresh(), 'deliverables/v1.jpg', 'v1.jpg');
        $delivery->requestRevision($booking->fresh(), 'Soften the shadows on the cake.');

        $booking->refresh();
        $this->assertSame('in_revision', $booking->status);
        $this->assertSame('held', $booking->escrow_status);
        $this->assertSame(1, $booking->revision_count);
        $this->assertSame(0, $booking->payouts()->count());
        $this->assertFalse((bool) $v1->fresh()->is_unlocked);
        $this->assertTrue(Message::query()->where('body', 'Soften the shadows on the cake.')->where('sender_id', $booking->client_id)->exists());

        $v2 = $delivery->upload($booking->fresh(), 'deliverables/v2.jpg', 'v2.jpg');
        $booking->refresh();

        $this->assertSame('delivered', $booking->status);
        $this->assertSame('held', $booking->escrow_status);
        $this->assertSame(2, $v2->version);
        $this->assertFalse($v2->is_unlocked);
        $this->assertFalse($delivery->previewState($booking)['downloads_unlocked']);
        $this->assertSame(0, $booking->payouts()->count());

        $delivery->approve($booking->fresh());
        $booking->refresh();

        $this->assertSame('approved', $booking->status);
        $this->assertSame('released', $booking->escrow_status);
        $this->assertTrue((bool) $v1->fresh()->is_unlocked);
        $this->assertTrue((bool) $v2->fresh()->is_unlocked);
        $this->assertTrue($delivery->previewState($booking)['downloads_unlocked']);
        $this->assertEquals(1440.0, (float) $booking->payouts()->sum('amount'));
    }

    public function test_scenario_c_unresolved_rejection_splits_eighty_ten_ten_and_logs_failed_session(): void
    {
        $booking = $this->photographerSession();
        $vendor = $booking->vendor;
        $failed = $vendor->failed_sessions;
        $delivery = app(DeliveryService::class);

        app(EscrowService::class)->checkout($booking);
        $file = $delivery->upload($booking->fresh(), 'deliverables/bad.jpg', 'bad.jpg');
        $dispute = $delivery->rejectAndDispute($booking->fresh(), $booking->client_id, 'Looks nothing like the brief.');

        $booking->refresh();
        $this->assertSame('disputed', $booking->status);
        $this->assertSame('held', $booking->escrow_status);
        $this->assertSame('open', $dispute->status);
        $this->assertSame(0, $booking->payouts()->count());

        $dispute = $delivery->settle($dispute->fresh());
        $booking->refresh();
        $this->assertSame('failed', $booking->status);
        $this->assertSame('split', $booking->escrow_status);
        $this->assertEquals(80.0, (float) $dispute->client_refund_percent);
        $this->assertEquals(10.0, (float) $dispute->vendor_payout_percent);
        $this->assertEquals(10.0, (float) $dispute->platform_fee_percent);
        $this->assertEquals(1440.0, (float) $booking->escrowTransactions()->where('type', 'refund')->sum('amount'));
        $this->assertEquals(180.0, (float) $booking->escrowTransactions()->where('notes', 'like', '%vendor time%')->sum('amount'));
        $this->assertEquals(180.0, (float) $booking->escrowTransactions()->where('notes', 'like', '%admin fee%')->sum('amount'));
        $this->assertEquals(180.0, (float) $booking->payouts()->sum('amount'));
        $this->assertSame($failed + 1, $vendor->fresh()->failed_sessions);
        $this->assertFalse((bool) $file->fresh()->is_unlocked);
        $this->assertFalse($delivery->previewState($booking)['downloads_unlocked']);
    }

    protected function photographerSession(): Booking
    {
        $vendor = Vendor::query()->whereHas('vendorType', fn ($query) => $query->where('slug', 'photographer'))->firstOrFail();
        $client = User::query()->where('email', 'client@lens.app')->firstOrFail();

        return Booking::query()->create([
            'reference' => 'LN-5-2-'.uniqid(),
            'client_id' => $client->id,
            'vendor_id' => $vendor->id,
            'status' => 'accepted',
            'scheduled_at' => now()->addDays(4),
            'session_price' => 1800,
            'escrow_status' => 'none',
            'payout_status' => 'none',
        ]);
    }
}
