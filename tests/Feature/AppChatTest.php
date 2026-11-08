<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Models\Vendor;
use Database\Seeders\LensSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppChatTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(LensSeeder::class);
    }

    public function test_client_opens_real_vendor_chat_and_admin_sees_it(): void
    {
        $vendor = Vendor::query()->where('display_name', 'Fahad Studio Light')->firstOrFail();

        $opened = $this->withHeaders(['X-Lens-Client' => 'client@lens.app'])
            ->postJson('/api/app/conversations', ['vendor_id' => $vendor->id])
            ->assertOk();

        $conversationId = $opened->json('id');
        $this->assertNotEmpty($conversationId);

        $vendorUser = User::query()->where('email', 'vendor@lens.app')->firstOrFail();
        $beforeMessages = $vendorUser->notifications()
            ->get()
            ->filter(fn ($n) => ($n->data['title'] ?? null) === 'New message')
            ->count();

        $this->withHeaders(['X-Lens-Client' => 'client@lens.app'])
            ->postJson('/api/app/conversations/'.$conversationId.'/messages', [
                'type' => 'text',
                'body' => 'Hi, can we confirm the lighting setup?',
            ])
            ->assertCreated()
            ->assertJsonPath('message.body', 'Hi, can we confirm the lighting setup?');

        $this->assertTrue(Message::query()->where('body', 'Hi, can we confirm the lighting setup?')->exists());
        $this->assertGreaterThan(
            $beforeMessages,
            $vendorUser->fresh()->notifications()->get()->filter(fn ($n) => ($n->data['title'] ?? null) === 'New message')->count(),
            'Sending a chat message must create a New message notification for the other party.',
        );

        $conversation = Conversation::query()->findOrFail($conversationId);

        $this->actingAs(User::query()->where('email', 'admin@lens.app')->firstOrFail())
            ->get('/admin/conversations/'.$conversation->id.'/edit')
            ->assertOk()
            ->assertSee('Hi, can we confirm the lighting setup?')
            ->assertSee('Live chat')
            ->assertSee('Send message');

        auth()->logout();
        $this->flushSession();

        $this->actingAs(User::query()->where('email', 'vendor@lens.app')->firstOrFail())
            ->get('/vendor/messages/'.$conversation->id.'/edit')
            ->assertOk()
            ->assertSee('Hi, can we confirm the lighting setup?')
            ->assertSee('Live chat')
            ->assertSee('Send message')
            ->assertSee('lens-live-chat', false)
            ->assertDontSee('Participants');
    }

    public function test_client_can_share_location_in_chat(): void
    {
        $vendor = Vendor::query()->where('display_name', 'Fahad Studio Light')->firstOrFail();
        $id = $this->withHeaders(['X-Lens-Client' => 'client@lens.app'])
            ->postJson('/api/app/conversations', ['vendor_id' => $vendor->id])
            ->json('id');

        $this->withHeaders(['X-Lens-Client' => 'client@lens.app'])
            ->postJson('/api/app/conversations/'.$id.'/messages', [
                'type' => 'location',
                'location_text' => 'Zamalek, Cairo',
                'location_lat' => 30.0626,
                'location_lng' => 31.2197,
            ])
            ->assertCreated()
            ->assertJsonPath('message.type', 'location');
    }

    public function test_vendor_reply_notifies_the_client(): void
    {
        $booking = \App\Models\Booking::query()->where('reference', 'LN-1001')->firstOrFail();
        $client = User::query()->where('email', 'client@lens.app')->firstOrFail();
        $before = $client->notifications()
            ->get()
            ->filter(fn ($n) => ($n->data['title'] ?? null) === 'New message')
            ->count();

        $opened = $this->withHeaders(['X-Lens-Client' => 'vendor@lens.app'])
            ->postJson('/api/app/conversations', [
                'client_id' => $booking->client_id,
                'booking_id' => $booking->id,
            ])
            ->assertOk();

        $this->withHeaders(['X-Lens-Client' => 'vendor@lens.app'])
            ->postJson('/api/app/conversations/'.$opened->json('id').'/messages', [
                'type' => 'text',
                'body' => 'I can start editing tonight.',
            ])
            ->assertCreated();

        $after = $client->fresh()->notifications()
            ->get()
            ->filter(fn ($n) => ($n->data['title'] ?? null) === 'New message');

        $this->assertGreaterThan(
            $before,
            $after->count(),
            'Vendor chat replies must create a New message notification for the client.',
        );
        $this->assertSame(
            (string) $opened->json('id'),
            (string) data_get($after->last()->data, 'conversation_id'),
        );
    }
}
