<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorAvailability;
use App\Services\SlotService;
use Database\Seeders\LensSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SlotLockTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(LensSeeder::class);
    }

    public function test_booking_locks_the_slot_so_it_cannot_be_selected_again(): void
    {
        $vendor = $this->photoVendor();
        $slot = VendorAvailability::query()->create([
            'vendor_id' => $vendor->id,
            'starts_at' => now()->addDays(2)->setTime(9, 0),
            'ends_at' => now()->addDays(2)->setTime(15, 0),
            'status' => 'open',
        ]);

        $first = $this->makeBooking($vendor, $slot->starts_at, 6, 'LN-SLOT-1', 'pending', $slot->id);

        $this->assertSame('booked', $slot->fresh()->status);
        $this->assertSame($slot->id, $first->fresh()->availability_id);
        $this->assertArrayNotHasKey($slot->id, app(SlotService::class)->openSlotOptions($vendor->id));
    }

    public function test_overlapping_booking_for_the_same_vendor_is_rejected(): void
    {
        $vendor = $this->photoVendor();
        $start = now()->addDays(5)->setTime(10, 0);

        $this->makeBooking($vendor, $start, 6, 'LN-SLOT-A', 'accepted');

        $this->expectException(ValidationException::class);
        $this->makeBooking($vendor, $start->copy()->addHours(2), 6, 'LN-SLOT-B', 'pending');
    }

    public function test_non_overlapping_booking_is_allowed(): void
    {
        $vendor = $this->photoVendor();
        $start = now()->addDays(22)->setTime(9, 0);

        $this->makeBooking($vendor, $start, 6, 'LN-SLOT-C', 'accepted');
        $later = $this->makeBooking($vendor, $start->copy()->addHours(7), 4, 'LN-SLOT-D', 'pending');

        $this->assertSame('pending', $later->status);
    }

    public function test_cancelled_booking_frees_the_slot(): void
    {
        $vendor = $this->photoVendor();
        $slot = VendorAvailability::query()->create([
            'vendor_id' => $vendor->id,
            'starts_at' => now()->addDays(9)->setTime(9, 0),
            'ends_at' => now()->addDays(9)->setTime(15, 0),
            'status' => 'open',
        ]);

        $booking = $this->makeBooking($vendor, $slot->starts_at, 6, 'LN-SLOT-E', 'pending', $slot->id);
        $this->assertSame('booked', $slot->fresh()->status);

        $booking->update(['status' => 'cancelled']);

        $this->assertSame('open', $slot->fresh()->status);
        $this->assertArrayHasKey($slot->id, app(SlotService::class)->openSlotOptions($vendor->id));
    }

    protected function makeBooking(
        Vendor $vendor,
        mixed $scheduledAt,
        int $hours,
        string $reference,
        string $status,
        ?int $availabilityId = null,
    ): Booking {
        $client = User::query()->where('email', 'client@lens.app')->firstOrFail();

        return Booking::query()->create([
            'reference' => $reference,
            'client_id' => $client->id,
            'vendor_id' => $vendor->id,
            'status' => $status,
            'scheduled_at' => $scheduledAt,
            'duration_hours' => $hours,
            'availability_id' => $availabilityId,
            'session_price' => 1800,
        ]);
    }

    protected function photoVendor(): Vendor
    {
        return Vendor::query()
            ->whereHas('vendorType', fn ($query) => $query->where('slug', 'photographer'))
            ->firstOrFail();
    }
}
