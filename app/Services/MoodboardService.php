<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Moodboard;
use App\Support\Feature;
use App\Support\SearchEngine;
use Throwable;

class MoodboardService
{
    public function __construct(
        protected GeminiClient $gemini,
    ) {}

    public function shouldGenerateAfterPayment(): bool
    {
        $mode = SearchEngine::settings()['moodboard_mode'] ?? 'after_payment';

        return Feature::enabled('moodboard') && in_array($mode, ['after_payment', 'always'], true);
    }

    public function generateForBooking(Booking $booking, ?string $brief = null): ?Moodboard
    {
        if (! Feature::enabled('moodboard')) {
            return null;
        }

        $brief = trim((string) ($brief ?: $booking->client_brief ?: $booking->notes ?: $booking->location_text));
        if ($brief === '') {
            $brief = 'Create a visual direction for this Lens booking.';
        }

        $payload = $this->compose($brief, $booking);

        return Moodboard::query()->updateOrCreate(
            ['booking_id' => $booking->id],
            [
                'brief' => $brief,
                'payload' => $payload,
                'provider' => $this->gemini->configured() ? 'gemini' : 'template',
                'generated_at' => now(),
            ],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function compose(string $brief, ?Booking $booking = null): array
    {
        if ($this->gemini->configured()) {
            try {
                return $this->fromGemini($brief, $booking);
            } catch (Throwable) {
                // fall through to local template
            }
        }

        return $this->template($brief);
    }

    /**
     * @return array<string, mixed>
     */
    protected function fromGemini(string $brief, ?Booking $booking): array
    {
        $context = $booking?->vendor?->display_name
            ? 'Vendor: '.$booking->vendor->display_name.'. Type: '.$booking->vendor->vendorType?->name_en
            : '';

        $parsed = $this->gemini->generateJson(
            "You are Lens, a production assistant. After the client paid, create a moodboard and shoot script.\n"
            ."Brief: {$brief}\n{$context}\n"
            .'Return JSON keys: title, style, mood, visual_direction, colors (array of {name, hex}), shot_list (array of strings), script (short spoken/on-camera or shot-by-shot script), music_tone.'
        );

        return array_merge($this->template($brief), $parsed);
    }

    /**
     * @return array<string, mixed>
     */
    protected function template(string $brief): array
    {
        $food = str_contains(mb_strtolower($brief), 'food') || str_contains($brief, 'طعام');

        return [
            'title' => $food ? 'Commercial food story' : 'Project visual direction',
            'style' => $food ? 'Warm commercial food photography, shallow depth, natural window light plus fill' : 'Clean editorial, natural tones, confident subject',
            'mood' => $food ? 'Appetizing, fresh, inviting' : 'Cinematic and approachable',
            'visual_direction' => $food
                ? 'Hero plate first, steam and texture close-ups, hands plating, environment of a working kitchen.'
                : 'Wide establishing frame, mid portraits, detail inserts, and a closing hero image.',
            'colors' => $food
                ? [
                    ['name' => 'Paprika', 'hex' => '#FF5A1F'],
                    ['name' => 'Cream', 'hex' => '#F2EFE9'],
                    ['name' => 'Charcoal', 'hex' => '#0D0D0F'],
                    ['name' => 'Olive', 'hex' => '#3D8B5F'],
                    ['name' => 'Gold', 'hex' => '#F79646'],
                ]
                : [
                    ['name' => 'Lens orange', 'hex' => '#FF5A1F'],
                    ['name' => 'Graphite', 'hex' => '#2A2D34'],
                    ['name' => 'Cream', 'hex' => '#F2EFE9'],
                    ['name' => 'Slate', 'hex' => '#5C5F66'],
                    ['name' => 'Ink', 'hex' => '#0D0D0F'],
                ],
            'shot_list' => $food
                ? ['Hero dish 50mm', 'Texture macro', 'Hands plating', 'Kitchen environment', 'Steam / pour insert', 'Final table spread']
                : ['Establishing wide', 'Subject mid-shot', 'Detail insert', 'Movement / candid', 'Hero portrait', 'Closing frame'],
            'script' => $food
                ? "Open on the empty plate. Hands enter with the hero dish. Cut to steam and texture. Pull back to the kitchen. End on the styled table, ready to serve."
                : "Open wide on the space. Move in on the subject. Capture one honest candid. Close on the hero portrait held for two beats.",
            'music_tone' => $food ? 'Soft acoustic, warm percussion' : 'Minimal piano with a light pulse',
        ];
    }
}
