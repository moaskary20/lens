<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Site\Concerns\InteractsWithSite;
use App\Models\AssistantConversation;
use App\Services\AiAssistant;
use App\Support\Feature;
use App\Support\SearchEngine;
use App\Support\SiteAuth;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AiController extends Controller
{
    use InteractsWithSite;

    public function __construct(protected AiAssistant $assistant) {}

    public function show(Request $request): View
    {
        $this->shareShell('search');
        $data = $this->bootstrap();
        $settings = SearchEngine::settings();
        $moodboardMode = (string) ($settings['moodboard_mode'] ?? 'after_payment');

        return view('site.ai', [
            'data' => $data,
            'enabled' => $this->assistant->enabled(),
            'prompt' => (string) ($data['ai_prompt'] ?? $settings['ai_prompt'] ?? 'What will you create today?'),
            'helper' => (string) ($data['ai_helper'] ?? 'Describe your idea and let AI find the right creatives for you.'),
            'voiceInput' => (bool) ($data['voice_input_enabled'] ?? $settings['voice_input_enabled'] ?? true),
            'moodboardOn' => Feature::enabled('moodboard') && $moodboardMode !== 'off',
            'moodboardLocked' => $moodboardMode === 'after_payment',
            'cities' => collect($data['cities'] ?? [])
                ->map(fn ($city): string => trim((string) (is_array($city) ? ($city['name'] ?? $city['name_en'] ?? '') : $city)))
                ->filter()
                ->unique()
                ->take(8)
                ->values()
                ->all(),
            'chatUrl' => route('site.ai.chat'),
            'seed' => trim((string) $request->query('q', '')),
        ]);
    }

    public function chat(Request $request): JsonResponse
    {
        if (! $this->assistant->enabled()) {
            return response()->json(['message' => 'AI assistant is temporarily offline.'], 403);
        }

        $message = trim((string) $request->input('message', ''));
        if ($message === '') {
            return response()->json([
                'message' => 'Send a message to start. Example: I need a photographer for a wedding next month at a mid price.',
            ], 422);
        }

        $conversation = null;
        if ($request->filled('conversation_id')) {
            $conversation = AssistantConversation::query()->where('uuid', $request->input('conversation_id'))->first();
            if (! $conversation) {
                return response()->json(['message' => 'Conversation not found.'], 404);
            }
        }

        $user = SiteAuth::user();
        $client = $user?->isClient() ? $user : null;
        $conversation ??= $this->assistant->start($client, preg_match('/\p{Arabic}/u', $message) ? 'ar' : 'en');

        $result = $this->assistant->chat($conversation, $message, [
            'locale' => $request->input('locale'),
        ]);

        $vendors = collect($result['vendors'] ?? [])->map(function ($vendor): array {
            $row = is_array($vendor) ? $vendor : [];

            return [
                'id' => $row['id'] ?? null,
                'display_name' => $row['display_name'] ?? $row['public_name'] ?? 'Creator',
                'vendor_type' => $row['vendor_type'] ?? $row['vendor_type_name'] ?? $row['profession'] ?? '',
                'city' => $row['city'] ?? '',
                'rating_avg' => $row['rating_avg'] ?? null,
                'starting_from' => $row['starting_from'] ?? $row['price'] ?? null,
                'url' => filled($row['id'] ?? null) ? route('site.vendor', $row['id']) : null,
            ];
        })->values()->all();

        return response()->json([
            ...$result,
            'vendors' => $vendors,
            'add_ons' => $result['add_ons'] ?? $result['addOns'] ?? [],
        ]);
    }
}
