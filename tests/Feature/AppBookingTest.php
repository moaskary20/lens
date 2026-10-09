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
        $this->assertSame('Zamalek, Cairo', $booking->location_text);
        $this->assertEquals(30.0626, (float) $booking->location_lat);
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

        $this->withHeaders($headers)
            ->getJson('/api/app/bookings/'.$booking->id.'/deliverables')
            ->assertOk()
            ->assertJsonPath('project_name', 'Yasmin Hall wedding')
            ->assertJsonPath('deliverables.0.name', 'final.jpg')
            ->assertJsonPath('total', 1980)
            ->assertJsonPath('watermarked', true)
            ->assertJsonPath('preview.watermark_text', 'Lens Protected');

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
            ->assertJsonPath('status', 'approved');
    }

    public function test_vendor_can_contact_client_deliver_cancel_and_open_booking_complaints(): void
    {
        Storage::fake('public');
        $headers = ['X-Lens-Client' => 'vendor@lens.app'];
        $booking = Booking::query()->where('reference', 'LN-1001')->firstOrFail();

        $this->withHeaders($headers)
            ->getJson('/api/app/bookings')
            ->assertOk()
            ->assertJsonPath('bookings.0.client_id', $booking->client_id)
            ->assertJsonPath('bookings.0.phone', '01011112233');

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
}
