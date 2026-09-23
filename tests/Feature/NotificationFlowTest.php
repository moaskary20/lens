<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Coupon;
use App\Models\Message;
use App\Models\User;
use App\Models\Vendor;
use App\Services\BookingWorkflow;
use App\Services\CancellationService;
use App\Services\DeliveryService;
use App\Services\EscrowService;
use App\Services\ReviewService;
use App\Services\WalletService;
use App\Support\LensNotifier;
use Database\Seeders\LensSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(LensSeeder::class);
        $this->actingAs(User::query()->where('email', 'admin@lens.app')->firstOrFail());
    }

    public function test_admin_pages_and_vendor_bell_render(): void
    {
        $this->get('/admin/notification-settings')
            ->assertOk()
            ->assertSee('New account')
            ->assertSee('Request accepted')
            ->assertSee('Money transfers');

        $this->get('/admin/notification-log')->assertOk();
    }

    public function test_registration_and_verification_notify_staff_and_vendor(): void
    {
        $admin = User::query()->where('email', 'admin@lens.app')->firstOrFail();
        $before = $admin->notifications()->count();

        $user = User::query()->create([
            'name' => 'New vendor',
            'email' => 'notify-vendor@lens.app',
            'password' => 'password',
            'role' => 'vendor',
            'is_active' => true,
        ]);
        $vendor = Vendor::query()->create([
            'user_id' => $user->id,
            'vendor_type_id' => Vendor::query()->firstOrFail()->vendor_type_id,
            'display_name' => 'Notify Studio',
            'verification_status' => 'pending',
            'is_active' => true,
        ]);

        $this->assertTrue($this->saw($admin->fresh(), 'New account'));
        $this->assertTrue($this->saw($user->fresh(), 'Welcome to Lens'));
        $this->assertGreaterThan($before, $admin->fresh()->notifications()->count());

        $vendor->update(['verification_status' => 'verified']);
        $this->assertTrue($this->saw($user->fresh(), 'Account approved'));

        $vendor->update(['verification_status' => 'rejected', 'verification_notes' => 'Incomplete ID']);
        $this->assertTrue($this->saw($user->fresh(), 'Account rejected'));
    }

    public function test_booking_payment_message_status_cancel_review_offer_and_money(): void
    {
        $client = User::query()->where('email', 'client@lens.app')->firstOrFail();
        $vendorUser = User::query()->where('email', 'vendor@lens.app')->firstOrFail();
        $vendor = $vendorUser->vendor;
        $admin = User::query()->where('email', 'admin@lens.app')->firstOrFail();

        $booking = Booking::query()->create([
            'reference' => 'LN-NOTE-1',
            'client_id' => $client->id,
            'vendor_id' => $vendor->id,
            'status' => 'pending',
            'scheduled_at' => now()->addDays(9),
            'session_price' => 1800,
            'escrow_status' => 'none',
            'payout_status' => 'none',
        ]);

        $this->assertTrue($this->saw($vendorUser->fresh(), 'New client request'));
        $this->assertTrue($this->saw($admin->fresh(), 'New request'));

        app(BookingWorkflow::class)->accept($booking->fresh());
        $this->assertTrue($this->saw($client->fresh(), 'Request accepted'));

        app(EscrowService::class)->checkout($booking->fresh());
        $this->assertTrue($this->saw($client->fresh(), 'Payment received'));
        $this->assertTrue($this->saw($vendorUser->fresh(), 'Payment received'));

        $conversation = $booking->conversation()->first();
        if (! $conversation) {
            $conversation = $booking->conversation()->create([
                'client_id' => $client->id,
                'vendor_id' => $vendor->id,
            ]);
        }
        Message::query()->create([
            'conversation_id' => $conversation->id,
            'sender_id' => $client->id,
            'body' => 'What time should we meet?',
        ]);
        $this->assertTrue($this->saw($vendorUser->fresh(), 'New message'));

        app(CancellationService::class)->cancel($booking->fresh(), 'client', 'Plans changed');
        $this->assertTrue($this->saw($client->fresh(), 'Booking cancelled'));
        $this->assertTrue($this->saw($vendorUser->fresh(), 'Booking cancelled'));
        $this->assertTrue($this->saw($client->fresh(), 'Refund received'));

        $paid = Booking::query()->create([
            'reference' => 'LN-NOTE-2',
            'client_id' => $client->id,
            'vendor_id' => $vendor->id,
            'status' => 'accepted',
            'scheduled_at' => now()->addDays(14),
            'session_price' => 1800,
            'escrow_status' => 'none',
            'payout_status' => 'none',
        ]);
        $escrow = app(EscrowService::class);
        $delivery = app(DeliveryService::class);
        $escrow->checkout($paid);
        $delivery->upload($paid->fresh(), 'deliverables/note.jpg', 'note.jpg');
        $delivery->approve($paid->fresh());
        $this->assertTrue($this->saw($vendorUser->fresh(), 'Payout queued'));
        $this->assertTrue($this->saw($vendorUser->fresh(), 'Commission posted'));

        app(ReviewService::class)->submit($paid->fresh(), 5, 'Excellent');
        $this->assertTrue($this->saw($vendorUser->fresh(), 'New star rating'));

        app(WalletService::class)->topUp($client, 50, 'Test top-up');
        $this->assertTrue($this->saw($client->fresh(), 'Wallet top-up'));

        $coupon = Coupon::query()->create([
            'code' => 'PING50',
            'label' => 'Ping credit',
            'campaign' => Coupon::CAMPAIGN_USER,
            'type' => Coupon::TYPE_WALLET,
            'amount' => 50,
            'user_id' => $client->id,
            'max_uses' => 1,
            'is_active' => true,
        ]);
        $coupon->assignedUsers()->sync([$client->id]);
        LensNotifier::announceOffer($coupon->fresh(['assignedUsers', 'user']));
        $this->assertTrue($this->saw($client->fresh(), 'New offer'));

        app(WalletService::class)->redeemCoupon($client->fresh(), $coupon->fresh());
        $this->assertTrue($this->saw($client->fresh(), 'Offer credited'));
    }

    protected function saw(User $user, string $title): bool
    {
        return $user->notifications()->get()->contains(fn ($n) => ($n->data['title'] ?? null) === $title);
    }
}
