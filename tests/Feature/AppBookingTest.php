<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Message;
use App\Models\User;
use App\Models\Vendor;
use Database\Seeders\LensSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AppBookingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(LensSeeder::class);
    }

    public function test_pending_vendor_cannot_receive_bookings_or_appear_publicly(): void
    {
        $pending = Vendor::query()->where('display_name', 'Noor Cyclorama')->firstOrFail();
        $this->assertSame('pending', $pending->verification_status);

        $this->getJson('/api/app/vendors/'.$pending->id)->assertNotFound();

        $this->withHeaders(['X-Lens-Client' => 'client@lens.app'])
            ->postJson('/api/app/bookings', [
                'vendor_id' => $pending->id,
                'project_name' => 'Should Fail',
                'project_type' => 'Studio',
                'client_brief' => 'Pending vendor must not be bookable.',
                'location_text' => 'Alexandria',
                'package_type' => 'hourly',
                'session_price' => 250,
                'payment_method' => 'wallet',
                'payment_details' => ['wallet_phone' => '01011112233'],
            ])
            ->assertStatus(422)
            ->assertJsonPath('errors.vendor_id.0', 'This vendor is pending verification and cannot receive bookings.');
    }

    public function test_client_can_book_with_specialty_price_and_map_pin(): void
    {
        $vendor = Vendor::query()->where('display_name', 'Fahad Studio Light')->firstOrFail();

        $this->withHeaders(['X-Lens-Client' => 'client@lens.app'])
            ->postJson('/api/app/bookings', [
                'vendor_id' => $vendor->id,
                'project_name' => 'Summer Menu Campaign',
                'project_type' => 'Food & drinks',
                'client_brief' => 'Lifestyle plates and close-ups of the new summer menu.',
                'location_text' => 'Zamalek, Cairo',
                'location_lat' => 30.0626,
                'location_lng' => 31.2197,
                'package_type' => 'half_day',
                'session_price' => 1800,
                'scheduled_at' => '2026-10-12 10:00:00',
                'duration_hours' => 6,
                'payment_method' => 'wallet',
                'payment_details' => [
                    'wallet_phone' => '01011112233',
                    'wallet_telecom' => 'Vodafone Cash',
                    'card_number' => '4242424242424242',
                    'cvv' => '123',
                ],
                'promo_code' => 'FAHAD100',
                'project_details' => [
                    'style' => 'Natural light',
                    'deliverables' => '50 edited photos',
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('project_name', 'Summer Menu Campaign')
            ->assertJsonPath('payment_method', 'wallet');

        $booking = Booking::query()->where('project_name', 'Summer Menu Campaign')->firstOrFail();
        $this->assertSame('Food & drinks', $booking->project_type);
        $this->assertSame('half_day', $booking->package_type);
        $this->assertSame('Zamalek, Cairo', $booking->location_text);
        $this->assertEquals(30.0626, (float) $booking->location_lat);
        $this->assertEquals((float) $vendor->half_day_price, (float) $booking->session_price);
        $this->assertEquals(1800.0, (float) $booking->session_price);
        $this->assertSame('wallet', $booking->payment_method);
        $this->assertSame('Natural light', $booking->project_details['style']);
        $this->assertSame('01011112233', $booking->payment_details['wallet_phone']);
        $this->assertSame('Vodafone Cash', $booking->payment_details['wallet_telecom']);
        $this->assertArrayNotHasKey('card_number', $booking->payment_details);
        $this->assertArrayNotHasKey('cvv', $booking->payment_details);

        $this->actingAs(\App\Models\User::query()->where('email', 'admin@lens.app')->firstOrFail())
            ->get('/admin/bookings/'.$booking->id.'/edit')
            ->assertOk()
            ->assertSee('Summer Menu Campaign')
            ->assertSee('Food & drinks')
            ->assertSee('Zamalek, Cairo')
            ->assertSee('Natural light')
            ->assertSee('Mobile wallet')
            ->assertSee('Vodafone Cash')
            ->assertSee('01011112233');

        $this->withHeaders(['X-Lens-Client' => 'client@lens.app'])
            ->getJson('/api/app/vendors/'.$vendor->id)
            ->assertOk()
            ->assertJsonPath('contact_unlocked', true)
            ->assertJsonPath('contact_phone', $vendor->contact_phone);

        $locked = Vendor::query()->where('display_name', 'Dina Plates')->firstOrFail();
        $this->withHeaders(['X-Lens-Client' => 'client@lens.app'])
            ->getJson('/api/app/vendors/'.$locked->id)
            ->assertOk()
            ->assertJsonPath('contact_unlocked', false)
            ->assertJsonPath('contact_phone', null);
    }

    public function test_booking_uses_backend_half_and_full_day_prices(): void
    {
        $vendor = Vendor::query()->where('display_name', 'Fahad Studio Light')->firstOrFail();
        $vendor->update(['half_day_price' => 2100, 'full_day_price' => 3900]);

        $profile = $this->getJson('/api/app/vendors/'.$vendor->id)->assertOk();
        $packages = collect($profile->json('packages'))->keyBy('key');
        $this->assertEquals(2100.0, (float) $packages['half_day']['price']);
        $this->assertEquals(3900.0, (float) $packages['full_day']['price']);
        $projectLabels = collect($profile->json('project_types'))->pluck('label')->all();
        $this->assertContains('Weddings & Parties', $projectLabels);
        $this->assertContains('Food & Drinks', $projectLabels);
        $this->assertContains('Products & E-Commerce', $projectLabels);

        $this->withHeaders(['X-Lens-Client' => 'client@lens.app'])
            ->postJson('/api/app/bookings', [
                'vendor_id' => $vendor->id,
                'project_name' => 'Half Day From Backend',
                'project_type' => 'Wedding',
                'client_brief' => 'Ceremony coverage only.',
                'location_text' => 'Cairo, Egypt',
                'package_type' => 'half_day',
                'session_price' => 1,
                'payment_method' => 'card',
                'payment_details' => [
                    'card_holder' => 'Client Lens',
                    'card_last4' => '4242',
                    'card_expiry' => '12/30',
                    'card_brand' => 'Visa',
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('session_price', 2100);

        $this->withHeaders(['X-Lens-Client' => 'client@lens.app'])
            ->postJson('/api/app/bookings/quote', [
                'vendor_id' => $vendor->id,
                'package_type' => 'full_day',
                'session_price' => 1,
            ])
            ->assertOk()
            ->assertJsonPath('session_price', 3900);
    }

    public function test_quote_rejects_unknown_promo_code(): void
    {
        $vendor = Vendor::query()->where('display_name', 'Fahad Studio Light')->firstOrFail();

        $this->withHeaders(['X-Lens-Client' => 'client@lens.app'])
            ->postJson('/api/app/bookings/quote', [
                'vendor_id' => $vendor->id,
                'session_price' => 1800,
                'promo_code' => 'NOT-A-CODE',
            ])
            ->assertStatus(422);
    }

    public function test_client_bookings_include_project_and_delivery_actions(): void
    {
        $headers = ['X-Lens-Client' => 'client@lens.app'];
        $booking = Booking::query()->where('reference', 'LN-1001')->firstOrFail();
        app(\App\Services\DeliveryService::class)->upload($booking->fresh(), 'deliverables/final.jpg', 'final.jpg');

        $this->withHeaders($headers)
            ->getJson('/api/app/bookings')
            ->assertOk()
            ->assertJsonFragment(['project_name' => 'Yasmin Hall wedding'])
            ->assertJsonFragment(['project_name' => 'Zamalek rooftop session']);

        $deliverables = $this->withHeaders($headers)
            ->getJson('/api/app/bookings/'.$booking->id.'/deliverables')
            ->assertOk()
            ->assertJsonPath('project_name', 'Yasmin Hall wedding')
            ->assertJsonPath('deliverables.0.name', 'final.jpg')
            ->assertJsonPath('total', 1980)
            ->assertJsonPath('watermarked', true)
            ->assertJsonPath('preview.watermark_text', 'Lens Protected')
            ->assertJsonPath('can_request_edit', true)
            ->assertJsonPath('can_approve', true)
            ->assertJsonPath('can_refuse', true);

        $fileUrl = (string) $deliverables->json('deliverables.0.url');
        $this->assertNotEmpty($fileUrl);
        $this->assertTrue(str_starts_with($fileUrl, 'http://') || str_starts_with($fileUrl, 'https://'));
        $vendorPhoto = (string) $deliverables->json('vendor_photo');
        $this->assertNotEmpty($vendorPhoto);
        $this->assertTrue(str_starts_with($vendorPhoto, 'http://') || str_starts_with($vendorPhoto, 'https://'));

        $this->withHeaders($headers)
            ->postJson('/api/app/bookings/'.$booking->id.'/request-edit', [
                'note' => 'Soften the shadows on the cake.',
            ])
            ->assertOk()
            ->assertJsonPath('status', 'in_revision');

        app(\App\Services\DeliveryService::class)->upload($booking->fresh(), 'deliverables/final-v2.jpg', 'final-v2.jpg');

        $this->withHeaders($headers)
            ->postJson('/api/app/bookings/'.$booking->id.'/approve')
            ->assertOk()
            ->assertJsonPath('status', 'approved')
            ->assertJsonPath('downloads_unlocked', true)
            ->assertJsonPath('watermarked', false)
            ->assertJsonPath('message', 'Delivery approved. Files are unlocked.');

        $booking->refresh();
        $this->assertSame('approved', $booking->status);
        $this->assertTrue((bool) $booking->deliverables()->where('is_unlocked', true)->exists());
    }

    public function test_approve_requires_delivered_status(): void
    {
        $headers = ['X-Lens-Client' => 'client@lens.app'];
        $booking = Booking::query()->where('reference', 'LN-1003')->firstOrFail();

        $this->withHeaders($headers)
            ->postJson('/api/app/bookings/'.$booking->id.'/approve')
            ->assertStatus(422)
            ->assertJsonPath('message', 'Approve is available after delivery.');
    }

    public function test_client_cancel_uses_matching_cancellation_policy(): void
    {
        $headers = ['X-Lens-Client' => 'client@lens.app'];
        $client = User::query()->where('email', 'client@lens.app')->firstOrFail();
        $vendor = Vendor::query()->where('display_name', 'Fahad Studio Light')->firstOrFail();

        $booking = Booking::query()->create([
            'reference' => 'LN-CLIENT-CANCEL',
            'client_id' => $client->id,
            'vendor_id' => $vendor->id,
            'status' => 'accepted',
            'scheduled_at' => now()->addHours(80),
            'session_price' => 1800,
            'total_paid' => 1800,
            'escrow_status' => 'held',
            'payout_status' => 'none',
            'project_name' => 'Policy cancel test',
            'package_type' => 'half_day',
        ]);

        $quote = $this->withHeaders($headers)
            ->getJson('/api/app/bookings/'.$booking->id.'/cancellation-quote')
            ->assertOk()
            ->assertJsonPath('actor', 'client')
            ->assertJsonPath('policy.name', 'Client cancel > 72 hours')
            ->assertJsonPath('client_refund', 1800)
            ->assertJsonPath('vendor_net', 0);

        $this->assertGreaterThanOrEqual(72, (int) $quote->json('hours_notice'));

        $this->withHeaders($headers)
            ->postJson('/api/app/bookings/'.$booking->id.'/cancel', [
                'reason' => 'Plans changed and I need to cancel.',
            ])
            ->assertOk()
            ->assertJsonPath('status', 'cancelled')
            ->assertJsonPath('actor', 'client')
            ->assertJsonPath('settlement.policy.name', 'Client cancel > 72 hours')
            ->assertJsonPath('settlement.client_refund', 1800);

        $this->assertSame('cancelled', $booking->fresh()->status);
        $this->assertSame('client', $booking->fresh()->cancelled_by);
    }

    public function test_vendor_can_contact_client_deliver_cancel_and_open_booking_complaints(): void
    {
        Storage::fake('public');
        $headers = ['X-Lens-Client' => 'vendor@lens.app'];
        $booking = Booking::query()->where('reference', 'LN-1001')->firstOrFail();

        $booking->update([
            'client_brief' => 'Wedding recap at Yasmin Hall with soft light portraits.',
            'project_type' => 'Weddings & Parties',
            'project_details' => ['style' => 'Natural light', 'shots' => '50 edited'],
        ]);

        $list = $this->withHeaders($headers)
            ->getJson('/api/app/bookings')
            ->assertOk();
        $row = collect($list->json('bookings'))->firstWhere('id', $booking->id);
        $this->assertNotNull($row);
        $this->assertSame($booking->client_id, $row['client_id'] ?? null);
        $this->assertSame('01011112233', $row['phone'] ?? null);
        $this->assertSame('Wedding recap at Yasmin Hall with soft light portraits.', $row['client_brief'] ?? null);
        $this->assertSame('Weddings & Parties', $row['project_type'] ?? null);
        $this->assertSame('Natural light', data_get($row, 'project_details.style'));

        $chat = $this->withHeaders($headers)
            ->postJson('/api/app/conversations', [
                'client_id' => $booking->client_id,
                'booking_id' => $booking->id,
            ])
            ->assertOk()
            ->assertJsonPath('booking_id', $booking->id);
        $this->withHeaders($headers)
            ->postJson('/api/app/conversations/'.$chat->json('id').'/messages', [
                'type' => 'text',
                'body' => 'I have received your project details.',
            ])
            ->assertCreated()
            ->assertJsonPath('message.mine', true);

        $this->withHeaders($headers)
            ->post('/api/app/bookings/'.$booking->id.'/deliverables', [
                'files' => [UploadedFile::fake()->image('final.jpg')],
            ])
            ->assertCreated()
            ->assertJsonPath('uploaded.0.name', 'final.jpg');
        $deliverable = \App\Models\Deliverable::query()->where('booking_id', $booking->id)->latest('id')->firstOrFail();
        Storage::disk('public')->assertExists($deliverable->path);

        $pending = Booking::query()->where('reference', 'LN-1003')->firstOrFail();
        $this->withHeaders($headers)
            ->postJson('/api/app/bookings/'.$pending->id.'/dispute', [
                'kind' => 'complaint',
                'reason' => 'The project requirements need staff review.',
            ])
            ->assertCreated()
            ->assertJsonPath('kind', 'complaint');
        $this->assertDatabaseHas('disputes', [
            'booking_id' => $pending->id,
            'opened_by' => \App\Models\User::query()->where('email', 'vendor@lens.app')->value('id'),
            'kind' => 'complaint',
        ]);

        $cancellable = $pending->replicate();
        $cancellable->reference = 'LN-CANCEL-'.random_int(10000, 99999);
        $cancellable->status = 'pending';
        $cancellable->scheduled_at = now()->addDays(45)->setTime(10, 0);
        $cancellable->availability_id = null;
        $cancellable->save();
        $this->withHeaders($headers)
            ->postJson('/api/app/bookings/'.$cancellable->id.'/cancel', [
                'reason' => 'I am no longer available on this date.',
            ])
            ->assertOk()
            ->assertJsonPath('status', 'cancelled');

        $otherVendorBooking = Booking::query()->where('reference', 'LN-1002')->firstOrFail();
        $this->withHeaders($headers)
            ->postJson('/api/app/bookings/'.$otherVendorBooking->id.'/cancel', [
                'reason' => 'Attempted unauthorized cancellation.',
            ])
            ->assertForbidden();
        $this->withHeaders($headers)
            ->postJson('/api/app/bookings/'.$otherVendorBooking->id.'/dispute', [
                'kind' => 'complaint',
                'reason' => 'Attempted unauthorized complaint.',
            ])
            ->assertForbidden();
        $this->withHeaders($headers)
            ->post('/api/app/bookings/'.$otherVendorBooking->id.'/deliverables', [
                'files' => [UploadedFile::fake()->image('private.jpg')],
            ])
            ->assertForbidden();
    }

    public function test_request_edit_stores_note_chat_files_and_notifies_staff(): void
    {
        Storage::fake('public');
        $headers = ['X-Lens-Client' => 'client@lens.app'];
        $booking = Booking::query()->where('reference', 'LN-1001')->firstOrFail();
        app(\App\Services\DeliveryService::class)->upload($booking->fresh(), 'deliverables/preview.jpg', 'preview.jpg');

        $this->withHeaders($headers)
            ->post('/api/app/bookings/'.$booking->id.'/request-edit', [
                'change' => 'Warm the skin tones.',
                'comment' => 'Keep the cake sharp.',
                'file_name' => 'preview.jpg',
                'quick_requests' => ['Color', 'Retouching'],
                'references' => [UploadedFile::fake()->image('mood.jpg', 80, 80)],
            ])
            ->assertOk()
            ->assertJsonPath('status', 'in_revision')
            ->assertJsonPath('revision_count', 1);

        $booking->refresh();
        $this->assertSame('in_revision', $booking->status);
        $this->assertSame('held', $booking->escrow_status);
        $this->assertStringContainsString('Quick requests: Color, Retouching', (string) $booking->notes);
        $this->assertStringContainsString('Warm the skin tones.', (string) $booking->notes);
        $this->assertStringContainsString('Photo preview.jpg: Keep the cake sharp.', (string) $booking->notes);
        $this->assertNotEmpty(Storage::disk('public')->files('revision-refs/'.$booking->id));
        $this->assertTrue(Message::query()->where('body', $booking->notes)->exists());
        $this->assertTrue(
            User::query()->where('email', 'admin@lens.app')->firstOrFail()
                ->notifications()
                ->get()
                ->contains(fn ($notification) => ($notification->data['title'] ?? null) === 'Revision requested'),
        );
    }

    public function test_request_edit_requires_delivered_files(): void
    {
        $headers = ['X-Lens-Client' => 'client@lens.app'];
        $booking = Booking::query()->where('reference', 'LN-1003')->firstOrFail();
        $this->assertNotSame('delivered', $booking->status);
        $this->assertNotSame('in_revision', $booking->status);

        $this->withHeaders($headers)
            ->postJson('/api/app/bookings/'.$booking->id.'/request-edit', [
                'change' => 'Please brighten the room.',
            ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'You can request edits after the creator delivers files.');
    }

    public function test_refuse_opens_dispute_after_delivery(): void
    {
        $headers = ['X-Lens-Client' => 'client@lens.app'];
        $booking = Booking::query()->where('reference', 'LN-1001')->firstOrFail();
        app(\App\Services\DeliveryService::class)->upload($booking->fresh(), 'deliverables/final.jpg', 'final.jpg');

        $this->withHeaders($headers)
            ->postJson('/api/app/bookings/'.$booking->id.'/refuse', [
                'reason' => 'The final gallery does not match the brief.',
            ])
            ->assertOk()
            ->assertJsonPath('status', 'disputed')
            ->assertJsonPath('message', 'Delivery refused. Admin will review the complaint.');

        $booking->refresh();
        $this->assertSame('disputed', $booking->status);
        $this->assertNotNull($booking->dispute);
        $this->assertSame('The final gallery does not match the brief.', $booking->dispute->reason);
    }

    public function test_refuse_requires_delivered_files_and_reason(): void
    {
        $headers = ['X-Lens-Client' => 'client@lens.app'];
        $booking = Booking::query()->where('reference', 'LN-1003')->firstOrFail();

        $this->withHeaders($headers)
            ->postJson('/api/app/bookings/'.$booking->id.'/refuse', [
                'reason' => 'Too late.',
            ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'You can refuse only after files are delivered.');

        $delivered = Booking::query()->where('reference', 'LN-1001')->firstOrFail();
        app(\App\Services\DeliveryService::class)->upload($delivered->fresh(), 'deliverables/final.jpg', 'final.jpg');

        $this->withHeaders($headers)
            ->postJson('/api/app/bookings/'.$delivered->id.'/refuse', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['reason']);
    }

    public function test_client_can_open_and_view_dispute(): void
    {
        $headers = ['X-Lens-Client' => 'client@lens.app'];
        $booking = Booking::query()->where('reference', 'LN-1001')->firstOrFail();
        app(\App\Services\DeliveryService::class)->upload($booking->fresh(), 'deliverables/final.jpg', 'final.jpg');

        $this->withHeaders($headers)
            ->postJson('/api/app/bookings/'.$booking->id.'/dispute', [
                'kind' => 'dispute',
                'reason' => 'Delivery quality. Shadows are too harsh on the cake.',
            ])
            ->assertCreated()
            ->assertJsonPath('kind', 'dispute')
            ->assertJsonPath('status', 'open')
            ->assertJsonPath('message', 'Dispute opened. Escrow stays held until Lens support decides.');

        $booking->refresh();
        $this->assertSame('disputed', $booking->status);
        $this->assertNotNull($booking->dispute);

        $this->withHeaders($headers)
            ->getJson('/api/app/bookings/'.$booking->id.'/dispute')
            ->assertOk()
            ->assertJsonPath('id', $booking->dispute->id)
            ->assertJsonPath('kind_label', 'Dispute')
            ->assertJsonPath('status_label', 'Open');

        $this->withHeaders($headers)
            ->postJson('/api/app/bookings/'.$booking->id.'/dispute', [
                'kind' => 'complaint',
                'reason' => 'Second note should reuse the open case.',
            ])
            ->assertOk()
            ->assertJsonPath('id', $booking->dispute->id)
            ->assertJsonPath('message', 'A dispute is already open for this booking.');
    }

    public function test_vendor_can_accept_and_reject_incoming_requests(): void
    {
        $headers = ['X-Lens-Client' => 'vendor@lens.app'];
        $pending = Booking::query()->where('reference', 'LN-1003')->firstOrFail();
        $this->assertSame('pending', $pending->status);

        $list = $this->withHeaders($headers)
            ->getJson('/api/app/bookings')
            ->assertOk();
        $row = collect($list->json('bookings'))->firstWhere('id', $pending->id);
        $this->assertNotNull($row);
        $this->assertTrue((bool) ($row['can_accept'] ?? false));
        $this->assertTrue((bool) ($row['can_reject'] ?? false));

        $this->withHeaders($headers)
            ->postJson('/api/app/bookings/'.$pending->id.'/accept')
            ->assertOk()
            ->assertJsonPath('status', 'accepted')
            ->assertJsonPath('status_label', 'Accepted');
        $this->assertSame('accepted', $pending->fresh()->status);
        $this->assertTrue(
            User::query()->where('email', 'client@lens.app')->firstOrFail()
                ->notifications()
                ->get()
                ->contains(fn ($notification) => ($notification->data['title'] ?? null) === 'Request accepted'),
        );

        $another = Booking::query()->create([
            'reference' => 'LN-VENDOR-REJECT',
            'client_id' => $pending->client_id,
            'vendor_id' => $pending->vendor_id,
            'status' => 'pending',
            'scheduled_at' => now()->addDays(12),
            'session_price' => 900,
            'total_paid' => 900,
            'escrow_status' => 'none',
            'payout_status' => 'none',
            'project_name' => 'Reject flow',
            'package_type' => 'hourly',
        ]);

        $this->withHeaders($headers)
            ->postJson('/api/app/bookings/'.$another->id.'/reject', [
                'reason' => 'Schedule conflict that week.',
            ])
            ->assertOk()
            ->assertJsonPath('status', 'rejected');
        $this->assertSame('rejected', $another->fresh()->status);
        $this->assertStringContainsString('Schedule conflict', (string) $another->fresh()->notes);
    }
}
