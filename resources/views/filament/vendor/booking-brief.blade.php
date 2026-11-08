@php
    /** @var \App\Models\Booking $booking */
    $details = is_array($booking->project_details) ? $booking->project_details : [];
@endphp

<div class="space-y-4 text-sm">
    <div>
        <div class="font-semibold text-gray-950 dark:text-white">Reference</div>
        <div class="text-gray-600 dark:text-gray-300">{{ $booking->reference }}</div>
    </div>

    <div>
        <div class="font-semibold text-gray-950 dark:text-white">Client</div>
        <div class="text-gray-600 dark:text-gray-300">{{ $booking->client?->name ?: '—' }}</div>
    </div>

    <div>
        <div class="font-semibold text-gray-950 dark:text-white">Project</div>
        <div class="text-gray-600 dark:text-gray-300">{{ $booking->project_name ?: '—' }}</div>
    </div>

    <div>
        <div class="font-semibold text-gray-950 dark:text-white">Project type</div>
        <div class="text-gray-600 dark:text-gray-300">{{ $booking->project_type ?: '—' }}</div>
    </div>

    <div>
        <div class="font-semibold text-gray-950 dark:text-white">Client brief</div>
        <div class="whitespace-pre-wrap text-gray-600 dark:text-gray-300">{{ filled($booking->client_brief) ? $booking->client_brief : '—' }}</div>
    </div>

    @if ($details !== [])
        <div>
            <div class="font-semibold text-gray-950 dark:text-white">Project details</div>
            <ul class="mt-1 list-disc space-y-1 pl-5 text-gray-600 dark:text-gray-300">
                @foreach ($details as $key => $value)
                    <li>
                        <span class="font-medium">{{ str_replace('_', ' ', (string) $key) }}:</span>
                        {{ is_scalar($value) ? $value : json_encode($value) }}
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid gap-3 sm:grid-cols-2">
        <div>
            <div class="font-semibold text-gray-950 dark:text-white">When</div>
            <div class="text-gray-600 dark:text-gray-300">{{ $booking->scheduled_at?->format('Y-m-d H:i') ?: '—' }}</div>
        </div>
        <div>
            <div class="font-semibold text-gray-950 dark:text-white">Package</div>
            <div class="text-gray-600 dark:text-gray-300">{{ str_replace('_', ' ', (string) ($booking->package_type ?: '—')) }}</div>
        </div>
        <div>
            <div class="font-semibold text-gray-950 dark:text-white">Duration</div>
            <div class="text-gray-600 dark:text-gray-300">{{ $booking->duration_hours ? $booking->duration_hours.' hours' : '—' }}</div>
        </div>
        <div>
            <div class="font-semibold text-gray-950 dark:text-white">Location</div>
            <div class="text-gray-600 dark:text-gray-300">{{ $booking->location_text ?: '—' }}</div>
        </div>
    </div>

    @if (filled($booking->notes))
        <div>
            <div class="font-semibold text-gray-950 dark:text-white">Notes / moodboard</div>
            <div class="whitespace-pre-wrap text-gray-600 dark:text-gray-300">{{ $booking->notes }}</div>
        </div>
    @endif
</div>
