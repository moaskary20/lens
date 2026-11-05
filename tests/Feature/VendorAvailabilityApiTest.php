<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorAvailability;
use Database\Seeders\LensSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VendorAvailabilityApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(LensSeeder::class);
    }

    public function test_vendor_can_list_create_update_and_delete_availability_slots(): void
    {
        $headers = ['X-Lens-Client' => 'vendor@lens.app', 'Accept' => 'application/json'];
        $vendor = User::query()->where('email', 'vendor@lens.app')->firstOrFail()->vendor;

        $this->withHeaders($headers)
            ->getJson('/api/app/account/availability')
            ->assertOk()
            ->assertJsonStructure(['calendar_title', 'availabilities']);

        $created = $this->withHeaders($headers)
            ->postJson('/api/app/account/availability', [
                'starts_at' => now()->addDays(3)->setTime(10, 0)->toIso8601String(),
                'ends_at' => now()->addDays(3)->setTime(14, 0)->toIso8601String(),
                'status' => 'open',
                'notes' => 'Morning studio',
            ])
            ->assertCreated()
            ->assertJsonPath('availability.status', 'open');

        $id = $created->json('availability.id');
        $this->assertNotEmpty($id);

        $this->withHeaders($headers)
            ->postJson('/api/app/account/availability/'.$id, [
                'starts_at' => now()->addDays(3)->setTime(11, 0)->toIso8601String(),
                'ends_at' => now()->addDays(3)->setTime(15, 0)->toIso8601String(),
                'status' => 'blocked',
                'notes' => 'Blocked for prep',
            ])
            ->assertOk()
            ->assertJsonPath('availability.status', 'blocked');

        $this->withHeaders($headers)
            ->deleteJson('/api/app/account/availability/'.$id)
            ->assertOk()
            ->assertJsonPath('ok', true);

        $this->assertFalse(VendorAvailability::query()->whereKey($id)->exists());
        $this->assertSame((int) $vendor->id, (int) $vendor->fresh()->id);
    }

    public function test_profile_sync_keeps_booked_slots_locked(): void
    {
        $vendorUser = User::query()->where('email', 'vendor@lens.app')->firstOrFail();
        $vendor = $vendorUser->vendor;
        $booked = VendorAvailability::query()->create([
            'vendor_id' => $vendor->id,
            'starts_at' => now()->addDays(5)->setTime(9, 0),
            'ends_at' => now()->addDays(5)->setTime(12, 0),
            'status' => 'booked',
            'notes' => 'Locked booking',
        ]);
        $open = VendorAvailability::query()->create([
            'vendor_id' => $vendor->id,
            'starts_at' => now()->addDays(6)->setTime(9, 0),
            'ends_at' => now()->addDays(6)->setTime(12, 0),
            'status' => 'open',
        ]);

        $profile = $this->withHeaders(['X-Lens-Client' => 'vendor@lens.app'])
            ->getJson('/api/app/account/profile')
            ->assertOk();

        $this->assertTrue(collect($profile->json('vendor.availabilities'))->contains(fn ($slot) => (int) $slot['id'] === $booked->id && $slot['locked'] === true));

        $this->withHeaders(['X-Lens-Client' => 'vendor@lens.app', 'Accept' => 'application/json'])
            ->postJson('/api/app/account/profile', array_merge(
                $this->vendorProfilePayload($vendor),
                [
                    'availabilities' => [
                        [
                            'starts_at' => now()->addDays(7)->setTime(10, 0)->toIso8601String(),
                            'ends_at' => now()->addDays(7)->setTime(13, 0)->toIso8601String(),
                            'status' => 'open',
                        ],
                    ],
                ],
            ))
            ->assertOk();

        $this->assertTrue(VendorAvailability::query()->whereKey($booked->id)->where('status', 'booked')->exists());
        $this->assertFalse(VendorAvailability::query()->whereKey($open->id)->exists());
        $this->assertTrue(
            VendorAvailability::query()
                ->where('vendor_id', $vendor->id)
                ->where('status', 'open')
                ->whereDate('starts_at', now()->addDays(7)->toDateString())
                ->exists()
        );
    }

    public function test_public_vendor_availability_returns_open_slots_only(): void
    {
        $vendor = Vendor::query()->where('display_name', 'Fahad Studio Light')->first()
            ?? User::query()->where('email', 'vendor@lens.app')->firstOrFail()->vendor;

        VendorAvailability::query()->create([
            'vendor_id' => $vendor->id,
            'starts_at' => now()->addDays(2)->setTime(10, 0),
            'ends_at' => now()->addDays(2)->setTime(12, 0),
            'status' => 'open',
        ]);
        VendorAvailability::query()->create([
            'vendor_id' => $vendor->id,
            'starts_at' => now()->addDays(2)->setTime(13, 0),
            'ends_at' => now()->addDays(2)->setTime(15, 0),
            'status' => 'blocked',
        ]);

        $payload = $this->getJson('/api/app/vendors/'.$vendor->id.'/availability')->assertOk();
        $statuses = collect($payload->json('availabilities'))->pluck('status')->unique()->values()->all();
        $this->assertSame(['open'], $statuses);
    }

    public function test_admin_availability_calendar_is_listed(): void
    {
        $admin = User::query()->where('email', 'admin@lens.app')->firstOrFail();

        $this->actingAs($admin)
            ->get('/admin/vendor-availabilities')
            ->assertOk();
    }

    /**
     * @return array<string, mixed>
     */
    protected function vendorProfilePayload(Vendor $vendor): array
    {
        $vendor->loadMissing(['vendorType', 'city', 'user']);

        return [
            'name' => $vendor->user?->name ?? 'Vendor',
            'email' => $vendor->user?->email ?? 'vendor@lens.app',
            'phone' => $vendor->user?->phone ?: '01022223344',
            'locale' => 'en',
            'city_id' => $vendor->city_id ?: 1,
            'vendor_type' => $vendor->vendorType?->slug ?? 'photographer',
            'display_name' => $vendor->display_name,
            'date_of_birth' => $vendor->date_of_birth?->toDateString() ?: '1990-01-01',
            'profession' => $vendor->profession ?: 'Photographer',
            'years_experience' => (int) ($vendor->years_experience ?: 5),
            'bio' => $vendor->bio ?: 'Lens vendor bio for availability tests.',
            'contact_email' => $vendor->contact_email ?: $vendor->user?->email,
            'whatsapp' => $vendor->whatsapp ?: '01022223344',
            'instagram' => $vendor->instagram ?: 'lens.vendor',
            'instagram_followers' => (int) ($vendor->instagram_followers ?: 1000),
            'tiktok_url' => $vendor->tiktok_url ?: 'https://tiktok.com/@lens',
            'tiktok_followers' => (int) ($vendor->tiktok_followers ?: 500),
            'facebook_url' => $vendor->facebook_url ?: 'https://facebook.com/lens',
            'facebook_followers' => (int) ($vendor->facebook_followers ?: 200),
            'address' => $vendor->address ?: 'Cairo',
            'latitude' => (float) ($vendor->latitude ?: 30.0444),
            'longitude' => (float) ($vendor->longitude ?: 31.2357),
            'payout_method' => $vendor->payout_method ?: 'bank',
            'bank_name' => $vendor->bank_name ?: 'Banque Misr',
            'bank_account_holder' => $vendor->bank_account_holder ?: $vendor->display_name,
            'bank_account_number' => $vendor->bank_account_number ?: '1234567890',
            'bank_iban' => $vendor->bank_iban ?: 'EG380003000123456789012345678',
            'bank_swift' => $vendor->bank_swift ?: 'BMISEGCX',
            'bank_branch' => $vendor->bank_branch ?: 'Zamalek',
            'bank_branch_code' => $vendor->bank_branch_code ?: '001',
            'bank_account_type' => $vendor->bank_account_type ?: 'current',
        ];
    }
}
