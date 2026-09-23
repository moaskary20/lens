<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorType;
use App\Services\CancellationService;
use App\Services\EscrowService;
use App\Services\ReputationService;
use App\Services\ReviewService;
use App\Services\VendorSearch;
use App\Support\SearchQuery;
use App\Support\VendorMetrics;
use Database\Seeders\LensSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

class QualityEngineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(LensSeeder::class);
        $this->actingAs(User::query()->where('email', 'admin@lens.app')->firstOrFail());
    }

    public function test_admin_quality_pages_render(): void
    {
        $this->get('/admin/session-metrics')->assertOk()->assertSee('Booked vs completed', false);
        $this->get('/admin/reviews')->assertOk()->assertSee('Star ratings', false);
        $this->get('/admin/badges')->assertOk()->assertSee('Incentive badges', false);
        $this->get('/admin')->assertOk()->assertSee('Marketplace rating', false);
    }

    public function test_review_is_blocked_until_session_approval(): void
    {
        $booking = Booking::query()->where('reference', 'LN-1003')->firstOrFail();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('approve');

        app(ReviewService::class)->submit($booking, 5, 'Too early');
    }

    public function test_approved_session_review_updates_rating_and_ranking(): void
    {
        $client = User::query()->where('email', 'client@lens.app')->firstOrFail();
        $photographer = VendorType::query()->where('slug', 'photographer')->firstOrFail();
        $cairo = Vendor::query()->where('display_name', 'Fahad Studio Light')->firstOrFail();

        $newcomer = Vendor::query()->create([
            'user_id' => User::query()->create([
                'name' => 'Low Rated Shooter',
                'email' => 'low-rated@lens.app',
                'password' => 'password',
                'role' => 'vendor',
                'is_active' => true,
            ])->id,
            'vendor_type_id' => $photographer->id,
            'city_id' => $cairo->city_id,
            'display_name' => 'Low Rated Shooter',
            'verification_status' => 'verified',
            'is_active' => true,
            'is_featured' => false,
            'half_day_price' => 1800,
            'rating_avg' => 0,
            'rating_count' => 0,
        ]);

        $booking = Booking::query()->create([
            'reference' => 'LN-RATE-1',
            'client_id' => $client->id,
            'vendor_id' => $newcomer->id,
            'status' => 'approved',
            'session_price' => 1800,
        ]);

        app(ReviewService::class)->submit($booking, 2, 'Late delivery');

        $newcomer = $newcomer->fresh();
        $this->assertEquals(2.0, (float) $newcomer->rating_avg);
        $this->assertSame(1, $newcomer->rating_count);

        $results = app(VendorSearch::class)->search(SearchQuery::fromArray([
            'vendor_type_slugs' => ['photographer'],
            'city_id' => $cairo->city_id,
        ]));

        $this->assertSame('Fahad Studio Light', $results['vendors'][0]['vendor']->display_name);
        $this->assertTrue(collect($results['vendors'][0]['reasons'])->contains(
            fn (string $reason): bool => str_contains($reason, 'Client rating') || str_contains($reason, 'Top Rated') || str_contains($reason, 'Featured'),
        ));
    }

    public function test_vendor_no_show_logs_failed_session_and_penalty(): void
    {
        $vendor = Vendor::query()->where('display_name', 'Fahad Studio Light')->firstOrFail();
        $client = User::query()->where('email', 'client@lens.app')->firstOrFail();

        $booking = Booking::query()->create([
            'reference' => 'LN-FAIL-1',
            'client_id' => $client->id,
            'vendor_id' => $vendor->id,
            'status' => 'accepted',
            'scheduled_at' => now()->addHours(6),
            'session_price' => 1800,
            'escrow_status' => 'none',
            'payout_status' => 'none',
        ]);

        app(EscrowService::class)->checkout($booking);

        $failed = $vendor->fresh()->failed_sessions;
        $penalties = (float) $vendor->fresh()->penalty_total;

        app(CancellationService::class)->cancel($booking->fresh(), 'vendor', 'No-show');

        $vendor = $vendor->fresh();
        $this->assertSame($failed + 1, $vendor->failed_sessions);
        $this->assertGreaterThan($penalties, (float) $vendor->penalty_total);
        $this->assertGreaterThan(0, VendorMetrics::platform()['failed']);
    }

    public function test_high_completion_and_reviews_award_popular_and_featured(): void
    {
        $vendor = Vendor::query()->where('display_name', 'Noor Cyclorama')->firstOrFail();
        $client = User::query()->where('email', 'client@lens.app')->firstOrFail();
        $vendor->update([
            'booked_sessions' => 25,
            'completed_sessions' => 22,
            'is_featured' => false,
        ]);
        $vendor->badges()->sync([]);

        foreach ([5, 5, 5] as $i => $stars) {
            $booking = Booking::query()->create([
                'reference' => 'LN-POP-'.$i,
                'client_id' => $client->id,
                'vendor_id' => $vendor->id,
                'status' => 'approved',
                'session_price' => 1000,
            ]);

            app(ReviewService::class)->submit($booking, $stars, 'Great studio');
        }

        $vendor = $vendor->fresh(['badges']);
        $this->assertTrue($vendor->badges->contains('slug', 'top-rated'));
        $this->assertTrue($vendor->badges->contains('slug', 'popular'));
        $this->assertTrue($vendor->is_featured);

        app(ReputationService::class)->refreshAll();
        $this->assertTrue($vendor->fresh('badges')->badges->contains('slug', 'popular'));
    }
}
