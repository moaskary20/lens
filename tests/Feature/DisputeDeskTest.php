<?php

namespace Tests\Feature;

use App\Filament\Resources\DisputeResource;
use App\Filament\Resources\DisputeResource\Pages\EditDispute;
use App\Filament\Resources\DisputeResource\Pages\ListDisputes;
use App\Models\Booking;
use App\Models\Dispute;
use App\Models\User;
use App\Models\Vendor;
use App\Services\DeliveryService;
use App\Services\DisputeService;
use App\Services\EscrowService;
use Database\Seeders\LensSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use LogicException;
use Tests\TestCase;

class DisputeDeskTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(LensSeeder::class);
        $this->actingAs(User::query()->where('email', 'admin@lens.app')->firstOrFail());
    }

    public function test_client_or_vendor_can_open_a_case_without_moving_money(): void
    {
        $booking = $this->deliveredSession();
        $vendorUser = $booking->vendor->user;

        $opened = app(DisputeService::class)->open(
            $booking,
            $vendorUser->id,
            'Client asked for extra hours unpaid.',
            DisputeService::KIND_COMPLAINT,
        );

        $booking->refresh();
        $this->assertSame('disputed', $booking->status);
        $this->assertSame('held', $booking->escrow_status);
        $this->assertSame('open', $opened->status);
        $this->assertSame('complaint', $opened->kind);
        $this->assertSame($vendorUser->id, $opened->opened_by);
        $this->assertSame(0, $booking->payouts()->count());
        $this->assertEquals(0, (float) $booking->escrowTransactions()->where('type', 'refund')->sum('amount'));
    }

    public function test_admin_case_file_shows_complaint_booking_payment_and_chat(): void
    {
        $dispute = Dispute::query()->whereHas('booking', fn ($query) => $query->where('reference', 'LN-1002'))->firstOrFail();

        $this->actingAs(User::query()->where('email', 'admin@lens.app')->firstOrFail())
            ->get('/admin/disputes/'.$dispute->id.'/edit')
            ->assertOk()
            ->assertSee('The booked space does not match the portfolio.')
            ->assertSee('LN-1002')
            ->assertSee('Payment / escrow')
            ->assertSee('Conversation')
            ->assertSee('The cyclorama wall is not the one in the listing photos.')
            ->assertSee('Refund client')
            ->assertSee('Pay vendor')
            ->assertSee('Apply split')
            ->assertSee('Close dispute');
    }

    public function test_start_review_action_updates_case_without_moving_escrow(): void
    {
        $booking = $this->deliveredSession();
        $dispute = app(DisputeService::class)->open($booking, $booking->client_id, 'Needs review.');

        Livewire::test(EditDispute::class, ['record' => $dispute->getRouteKey()])
            ->assertActionVisible('startReview')
            ->callAction('startReview');

        $this->assertSame('reviewing', $dispute->fresh()->status);
        $this->assertSame('held', $booking->fresh()->escrow_status);
        $this->assertSame(0, $booking->payouts()->count());
    }

    public function test_dispute_list_tabs_filter_every_status(): void
    {
        $dispute = Dispute::query()->whereHas('booking', fn ($query) => $query->where('reference', 'LN-1002'))->firstOrFail();

        Livewire::test(ListDisputes::class)
            ->assertSee('All disputes')
            ->assertSee('Open')
            ->assertSee('In review')
            ->assertSee('Resolved')
            ->assertSee('Closed');

        foreach (array_keys(DisputeResource::statuses()) as $status) {
            $dispute->update(['status' => $status]);

            Livewire::test(ListDisputes::class)
                ->set('activeTab', $status)
                ->assertCanSeeTableRecords([$dispute->fresh()]);
        }
    }

    public function test_admin_refund_credits_client_and_pays_nothing_to_vendor(): void
    {
        $booking = $this->deliveredSession();
        $dispute = app(DisputeService::class)->open($booking, $booking->client_id, 'Not usable.');

        app(DisputeService::class)->refundClient($dispute->fresh());

        $booking->refresh();
        $this->assertSame('refunded', $booking->status);
        $this->assertSame('refunded', $booking->escrow_status);
        $this->assertSame('refund', $dispute->fresh()->decision);
        $this->assertEquals(1800.0, (float) $booking->escrowTransactions()->where('type', 'refund')->sum('amount'));
        $this->assertSame(0, $booking->payouts()->count());
        $this->assertGreaterThan(0, (float) $booking->client->fresh()->wallet->lifetime_refunded);
    }

    public function test_refund_action_credits_client_and_resolves_dispute(): void
    {
        $booking = $this->deliveredSession();
        $dispute = app(DisputeService::class)->open($booking, $booking->client_id, 'Not usable.');

        Livewire::test(EditDispute::class, ['record' => $dispute->getRouteKey()])
            ->assertActionVisible('refundClient')
            ->callAction('refundClient');

        $this->assertSame('resolved', $dispute->fresh()->status);
        $this->assertSame('refund', $dispute->fresh()->decision);
        $this->assertSame('refunded', $booking->fresh()->status);
        $this->assertEquals(1800.0, (float) $booking->escrowTransactions()->where('type', 'refund')->sum('amount'));
        $this->assertSame(0, $booking->payouts()->count());
    }

    public function test_admin_can_award_funds_to_vendor(): void
    {
        $booking = $this->deliveredSession();
        $dispute = app(DisputeService::class)->open($booking, $booking->client_id, 'Vendor completed the brief.');

        app(DisputeService::class)->payVendor($dispute->fresh());

        $booking->refresh();
        $this->assertSame('approved', $booking->status);
        $this->assertSame('released', $booking->escrow_status);
        $this->assertSame('payout', $dispute->fresh()->decision);
        $this->assertEquals(1440.0, (float) $booking->payouts()->sum('amount'));
        $this->assertTrue((bool) $booking->deliverables()->first()?->is_unlocked);
    }

    public function test_pay_vendor_action_releases_escrow_and_unlocks_deliverables(): void
    {
        $booking = $this->deliveredSession();
        $dispute = app(DisputeService::class)->open($booking, $booking->client_id, 'Vendor completed the brief.');

        Livewire::test(EditDispute::class, ['record' => $dispute->getRouteKey()])
            ->assertActionVisible('payVendor')
            ->callAction('payVendor');

        $this->assertSame('resolved', $dispute->fresh()->status);
        $this->assertSame('payout', $dispute->fresh()->decision);
        $this->assertSame('released', $booking->fresh()->escrow_status);
        $this->assertEquals(1440.0, (float) $booking->payouts()->sum('amount'));
        $this->assertTrue((bool) $booking->deliverables()->first()?->is_unlocked);
    }

    public function test_apply_split_action_uses_saved_percentages_and_rejects_invalid_totals(): void
    {
        $booking = $this->deliveredSession();
        $dispute = app(DisputeService::class)->open($booking, $booking->client_id, 'Partial service.');
        $dispute->update([
            'client_refund_percent' => 50,
            'vendor_payout_percent' => 40,
            'platform_fee_percent' => 10,
        ]);

        Livewire::test(EditDispute::class, ['record' => $dispute->getRouteKey()])
            ->assertActionVisible('applySplit')
            ->callAction('applySplit');

        $this->assertSame('split', $dispute->fresh()->decision);
        $this->assertEquals(900.0, (float) $booking->escrowTransactions()->where('type', 'refund')->sum('amount'));
        $this->assertEquals(720.0, (float) $booking->payouts()->sum('amount'));

        $invalidBooking = $this->deliveredSession();
        $invalidDispute = app(DisputeService::class)->open($invalidBooking, $invalidBooking->client_id, 'Invalid split.');
        $invalidDispute->update([
            'client_refund_percent' => 50,
            'vendor_payout_percent' => 40,
            'platform_fee_percent' => 20,
        ]);

        Livewire::test(EditDispute::class, ['record' => $invalidDispute->getRouteKey()])
            ->assertActionVisible('applySplit')
            ->callAction('applySplit');

        $this->assertSame('open', $invalidDispute->fresh()->status);
        $this->assertSame(0, $invalidBooking->escrowTransactions()->whereIn('type', ['refund', 'release', 'fee'])->count());
    }

    public function test_settlement_actions_reject_a_stale_already_resolved_dispute(): void
    {
        $booking = $this->deliveredSession();
        $dispute = app(DisputeService::class)->open($booking, $booking->client_id, 'Duplicate action check.');
        $staleDispute = $dispute->fresh();

        app(DisputeService::class)->refundClient($dispute->fresh());

        try {
            app(DisputeService::class)->payVendor($staleDispute);
            $this->fail('A stale dispute must not allow a second settlement.');
        } catch (LogicException $exception) {
            $this->assertSame('This dispute is already closed.', $exception->getMessage());
        }

        $this->assertSame('refund', $dispute->fresh()->decision);
        $this->assertSame(0, $booking->payouts()->count());
    }

    public function test_admin_can_choose_status_and_decision_from_the_form(): void
    {
        $booking = $this->deliveredSession();
        $dispute = app(DisputeService::class)->open($booking, $booking->client_id, 'Not usable.');

        Livewire::test(EditDispute::class, ['record' => $dispute->getRouteKey()])
            ->assertFormFieldIsEnabled('status')
            ->assertFormFieldIsEnabled('decision')
            ->fillForm([
                'status' => 'reviewing',
                'decision' => 'refund',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $booking->refresh();
        $this->assertSame('refunded', $booking->status);
        $this->assertSame('refund', $dispute->fresh()->decision);
        $this->assertSame(0, $booking->payouts()->count());
    }

    public function test_admin_can_change_dispute_status_to_reviewing_from_the_form(): void
    {
        $booking = $this->deliveredSession();
        $dispute = app(DisputeService::class)->open($booking, $booking->client_id, 'Needs investigation.');

        $page = Livewire::test(EditDispute::class, ['record' => $dispute->getRouteKey()])
            ->fillForm(['status' => 'reviewing'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('reviewing', $dispute->fresh()->status);
        $this->assertSame('disputed', $booking->fresh()->status);
        $this->assertSame('held', $booking->fresh()->escrow_status);

        $page->fillForm(['status' => 'open'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('open', $dispute->fresh()->status);
        $this->assertSame('disputed', $booking->fresh()->status);
        $this->assertSame('held', $booking->fresh()->escrow_status);

        $page->fillForm(['status' => 'closed'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('closed', $dispute->fresh()->status);
        $this->assertSame('closed', $dispute->fresh()->decision);
        $this->assertSame('delivered', $booking->fresh()->status);
        $this->assertSame('held', $booking->fresh()->escrow_status);
    }

    public function test_admin_can_close_without_moving_money(): void
    {
        $booking = $this->deliveredSession();
        $dispute = app(DisputeService::class)->open($booking, $booking->client_id, 'False alarm.');

        app(DisputeService::class)->close($dispute->fresh(), 'Parties agreed to continue.');

        $booking->refresh();
        $this->assertSame('delivered', $booking->status);
        $this->assertSame('held', $booking->escrow_status);
        $this->assertSame('closed', $dispute->fresh()->status);
        $this->assertSame(0, $booking->payouts()->count());
    }

    public function test_close_action_preserves_held_escrow_and_saves_closing_note(): void
    {
        $booking = $this->deliveredSession();
        $dispute = app(DisputeService::class)->open($booking, $booking->client_id, 'False alarm.');

        Livewire::test(EditDispute::class, ['record' => $dispute->getRouteKey()])
            ->assertActionVisible('closeCase')
            ->callAction('closeCase', ['notes' => 'Parties agreed to continue.']);

        $this->assertSame('closed', $dispute->fresh()->status);
        $this->assertSame('closed', $dispute->fresh()->decision);
        $this->assertSame('Parties agreed to continue.', $dispute->fresh()->admin_notes);
        $this->assertSame('delivered', $booking->fresh()->status);
        $this->assertSame('held', $booking->fresh()->escrow_status);
        $this->assertSame(0, $booking->payouts()->count());
    }

    public function test_delete_action_removes_open_case_and_restores_booking_without_moving_money(): void
    {
        $booking = $this->deliveredSession();
        $dispute = app(DisputeService::class)->open($booking, $booking->client_id, 'Opened by mistake.');

        Livewire::test(EditDispute::class, ['record' => $dispute->getRouteKey()])
            ->assertActionVisible('delete')
            ->callAction('delete');

        $this->assertDatabaseMissing('disputes', ['id' => $dispute->id]);
        $this->assertSame('delivered', $booking->fresh()->status);
        $this->assertSame('held', $booking->fresh()->escrow_status);
        $this->assertSame(0, $booking->payouts()->count());
        $this->assertEquals(0, (float) $booking->escrowTransactions()->whereIn('type', ['refund', 'release', 'fee'])->sum('amount'));
    }

    public function test_vendor_panel_lists_open_cases(): void
    {
        $vendor = User::query()->where('email', 'studio@lens.app')->firstOrFail();

        $this->actingAs($vendor)
            ->get('/vendor/disputes')
            ->assertOk()
            ->assertSee('LN-1002')
            ->assertSee('The booked space does not match the portfolio.');
    }

    protected function deliveredSession(): Booking
    {
        $vendor = Vendor::query()->whereHas('vendorType', fn ($query) => $query->where('slug', 'photographer'))->firstOrFail();
        $client = User::query()->where('email', 'client@lens.app')->firstOrFail();
        $booking = Booking::query()->create([
            'reference' => 'LN-DSP-'.uniqid(),
            'client_id' => $client->id,
            'vendor_id' => $vendor->id,
            'status' => 'accepted',
            'scheduled_at' => now()->addDays(4),
            'session_price' => 1800,
            'escrow_status' => 'none',
            'payout_status' => 'none',
        ]);

        app(EscrowService::class)->checkout($booking);
        app(DeliveryService::class)->upload($booking->fresh(), 'deliverables/dsp.jpg', 'dsp.jpg');

        return $booking->fresh();
    }
}
