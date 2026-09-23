<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\VendorAvailability;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class SlotService
{
    /**
     * @return list<string>
     */
    public static function occupyingStatuses(): array
    {
        return [
            'pending', 'accepted', 'checked_in', 'in_progress',
            'delivered', 'in_revision', 'approved', 'completed', 'disputed',
        ];
    }

    /**
     * @return array{start: Carbon, end: Carbon}|null
     */
    public function window(Booking $booking): ?array
    {
        if (! $booking->scheduled_at) {
            return null;
        }

        $hours = $booking->durationHours();

        return [
            'start' => $booking->scheduled_at->copy(),
            'end' => $booking->scheduled_at->copy()->addHours($hours),
        ];
    }

    public function guard(Booking $booking): void
    {
        if (! in_array($booking->status, self::occupyingStatuses(), true)) {
            return;
        }

        $window = $this->window($booking);

        if (! $window || ! $booking->vendor_id) {
            return;
        }

        if ($this->hasOverlap($booking, $window['start'], $window['end'])) {
            throw ValidationException::withMessages([
                'scheduled_at' => 'This vendor is already booked for that time. The slot cannot be selected again.',
                'availability_id' => 'That calendar slot is already reserved by another booking.',
            ]);
        }

        if ($booking->availability_id) {
            $slot = VendorAvailability::query()->find($booking->availability_id);

            if ($slot && $slot->status === 'booked' && (int) $slot->vendor_id === (int) $booking->vendor_id) {
                $takenByOther = Booking::query()
                    ->where('availability_id', $slot->id)
                    ->where('id', '!=', $booking->id ?? 0)
                    ->whereIn('status', self::occupyingStatuses())
                    ->exists();

                if ($takenByOther) {
                    throw ValidationException::withMessages([
                        'availability_id' => 'This slot already reached the vendor and cannot be booked again.',
                    ]);
                }
            }
        }
    }

    public function sync(Booking $booking): void
    {
        if (! in_array($booking->status, self::occupyingStatuses(), true)) {
            $this->release($booking);

            return;
        }

        $window = $this->window($booking);

        if (! $window) {
            return;
        }

        $slot = $booking->availability_id
            ? VendorAvailability::query()->find($booking->availability_id)
            : $this->matchingOpenSlot($booking, $window['start'], $window['end']);

        if (! $slot) {
            $slot = VendorAvailability::query()->create([
                'vendor_id' => $booking->vendor_id,
                'starts_at' => $window['start'],
                'ends_at' => $window['end'],
                'status' => 'booked',
                'notes' => 'Reserved by '.$booking->reference,
            ]);
        }

        $slot->update([
            'status' => 'booked',
            'notes' => $slot->notes ?: 'Reserved by '.$booking->reference,
        ]);

        if ($booking->availability_id !== $slot->id) {
            $booking->forceFill(['availability_id' => $slot->id])->saveQuietly();
        }
    }

    public function release(Booking $booking): void
    {
        if (! $booking->availability_id) {
            return;
        }

        $stillHeld = Booking::query()
            ->where('availability_id', $booking->availability_id)
            ->where('id', '!=', $booking->id)
            ->whereIn('status', self::occupyingStatuses())
            ->exists();

        if ($stillHeld) {
            return;
        }

        VendorAvailability::query()
            ->where('id', $booking->availability_id)
            ->where('status', 'booked')
            ->update(['status' => 'open']);
    }

    /**
     * @return array<int, string>
     */
    public function openSlotOptions(int $vendorId, ?int $keepId = null): array
    {
        return VendorAvailability::query()
            ->where('vendor_id', $vendorId)
            ->where(function ($query) use ($keepId): void {
                $query->where('status', 'open');

                if ($keepId) {
                    $query->orWhere('id', $keepId);
                }
            })
            ->orderBy('starts_at')
            ->get()
            ->mapWithKeys(function (VendorAvailability $slot): array {
                $label = $slot->starts_at->format('Y-m-d H:i').' – '.$slot->ends_at->format('H:i');

                if ($slot->status === 'booked') {
                    $label .= ' (current booking)';
                }

                return [$slot->id => $label];
            })
            ->all();
    }

    /**
     * @param  array{start: Carbon, end: Carbon}  $window
     */
    protected function matchingOpenSlot(Booking $booking, Carbon $start, Carbon $end): ?VendorAvailability
    {
        return VendorAvailability::query()
            ->where('vendor_id', $booking->vendor_id)
            ->where('starts_at', '<=', $start)
            ->where('ends_at', '>=', $end)
            ->whereIn('status', ['open', 'booked'])
            ->orderBy('starts_at')
            ->first();
    }

    protected function hasOverlap(Booking $booking, Carbon $start, Carbon $end): bool
    {
        return Booking::query()
            ->where('vendor_id', $booking->vendor_id)
            ->where('id', '!=', $booking->id ?? 0)
            ->whereIn('status', self::occupyingStatuses())
            ->whereNotNull('scheduled_at')
            ->get()
            ->contains(function (Booking $existing) use ($start, $end): bool {
                $other = $this->window($existing);

                if (! $other) {
                    return false;
                }

                return $other['start']->lt($end) && $other['end']->gt($start);
            });
    }
}
