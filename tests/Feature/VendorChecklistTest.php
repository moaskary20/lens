<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\User;
use App\Models\Vendor;
use App\Services\DeliveryService;
use App\Services\EscrowService;
use App\Services\ReviewService;
use Carbon\Carbon;
use Database\Seeders\LensSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VendorChecklistTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(LensSeeder::class);
    }

    public function test_admin_vendor_form_includes_kyc_contact_bank_and_services(): void
    {
        $admin = User::query()->where('email', 'admin@lens.app')->firstOrFail();
        $vendor = Vendor::query()->where('display_name', 'Fahad Studio Light')->firstOrFail();

        $this->assertSame(Carbon::parse('1992-04-18')->age, $vendor->age());
        $this->assertSame('Wedding & product photographer', $vendor->profession);
        $this->assertSame('Banque Misr', $vendor->bank_name);
        $this->assertNotNull($vendor->national_id_image);

        $this->actingAs($admin)
            ->get('/admin/vendors/'.$vendor->id.'/edit')
            ->assertOk()
            ->assertSee('Personal photo')
            ->assertSee('National ID card')
            ->assertSee('Date of birth')
            ->assertSee('Profession / job title')
            ->assertSee('Phone')
            ->assertSee('WhatsApp')
            ->assertSee('Bank name')
            ->assertSee('InstaPay / wallet')
            ->assertSee('Services / categories')
            ->assertSee('Previous projects')
            ->assertSee('Half-day price (6 hours)');
    }

    public function test_vendor_panel_exposes_profile_gallery_requests_chat_earnings_and_ratings(): void
    {
        $vendor = User::query()->where('email', 'vendor@lens.app')->firstOrFail();

        $this->actingAs($vendor)->get('/vendor/my-profile')
            ->assertOk()
            ->assertSee('National ID card')
            ->assertSee('Date of birth')
            ->assertSee('Profession / job title')
            ->assertSee('Personal photo')
            ->assertSee('Services you offer')
            ->assertSee('Home governorate')
            ->assertSee('Bank name')
            ->assertSee('InstaPay / wallet')
            ->assertSee('Vendor type')
            ->assertSee('Photographer')
            ->assertSee('Cameras')
            ->assertSee('Lenses')
            ->assertSee('Lighting / strobes')
            ->assertSee('Filter tags')
            ->assertSee('Verified — you can receive bookings.');

        $this->actingAs($vendor)->get('/vendor/portfolio')->assertOk()->assertSee('Yasmin Hall wedding');
        $this->actingAs($vendor)->get('/vendor/availability')->assertOk()->assertSee('Half-day product slot');
        $this->actingAs($vendor)->get('/vendor/travel-fees')->assertOk()->assertSee('Travel fees')->assertSee('Alexandria');
        $this->actingAs($vendor)->get('/vendor/requests')->assertOk()->assertSee('LN-1003');
        $this->actingAs($vendor)->get('/vendor/my-pricing')->assertOk();
        $this->actingAs($vendor)->get('/vendor/earnings')->assertOk()->assertSee('Pending');
        $this->actingAs($vendor)->get('/vendor/ratings')->assertOk()->assertSee('Professional shoot and fast delivery.');
        $conversation = $vendor->vendor->conversations()->firstOrFail();
        $this->actingAs($vendor)->get('/vendor/messages')->assertOk();
        $this->actingAs($vendor)->get('/vendor/messages/'.$conversation->id.'/edit')
            ->assertOk()
            ->assertSee('Please confirm the hall access time.');
        $this->actingAs($vendor)->get('/vendor')->assertOk()->assertSee('Pending earnings')->assertSee('Verified');
    }

    public function test_studio_vendor_panel_shows_rooms_and_hourly_calendar(): void
    {
        $studio = User::query()->where('email', 'studio@lens.app')->firstOrFail();

        $this->actingAs($studio)->get('/vendor/my-profile')
            ->assertOk()
            ->assertSee('Room / space inventory')
            ->assertSee('Available props')
            ->assertSee('Pending review by Lens staff.');
        $this->actingAs($studio)->get('/vendor/availability')
            ->assertOk()
            ->assertSee('Hourly slot booking calendar')
            ->assertSee('Kitchen + cyclorama hourly block');
    }

    public function test_vendor_is_notified_for_requests_revisions_reviews_and_payouts(): void
    {
        $vendorUser = User::query()->where('email', 'vendor@lens.app')->firstOrFail();
        $client = User::query()->where('email', 'client@lens.app')->firstOrFail();
        $vendor = $vendorUser->vendor;

        $before = $vendorUser->notifications()->count();

        $booking = Booking::query()->create([
            'reference' => 'LN-CHECK-1',
            'client_id' => $client->id,
            'vendor_id' => $vendor->id,
            'status' => 'pending',
            'scheduled_at' => now()->addDays(12),
            'session_price' => 1800,
            'escrow_status' => 'none',
            'payout_status' => 'none',
        ]);

        $this->assertTrue(
            $vendorUser->fresh()->notifications()->get()->contains(fn ($n) => ($n->data['title'] ?? null) === 'New client request'),
        );

        $booking->update(['status' => 'accepted']);
        app(EscrowService::class)->checkout($booking->fresh());
        app(DeliveryService::class)->upload($booking->fresh(), 'deliverables/check.jpg', 'check.jpg');
        app(DeliveryService::class)->requestRevision($booking->fresh(), 'Warm the skin tones.', $client->id);

        $this->assertTrue(
            $vendorUser->fresh()->notifications()->get()->contains(fn ($n) => ($n->data['title'] ?? null) === 'Revision requested'),
        );

        app(DeliveryService::class)->upload($booking->fresh(), 'deliverables/check-v2.jpg', 'check-v2.jpg');
        app(DeliveryService::class)->approve($booking->fresh());

        $this->assertTrue(
            $vendorUser->fresh()->notifications()->get()->contains(fn ($n) => ($n->data['title'] ?? null) === 'Payout queued'),
        );

        app(ReviewService::class)->submit($booking->fresh(), 5, 'Great work');

        $this->assertTrue(
            $vendorUser->fresh()->notifications()->get()->contains(fn ($n) => ($n->data['title'] ?? null) === 'New star rating'),
        );

        $this->assertGreaterThan($before, $vendorUser->fresh()->notifications()->count());
    }
}
