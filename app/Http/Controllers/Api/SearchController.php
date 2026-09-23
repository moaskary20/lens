<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AssistantConversation;
use App\Models\Booking;
use App\Models\User;
use App\Services\AiAssistant;
use App\Services\MoodboardService;
use App\Services\RecommendationService;
use App\Services\VendorSearch;
use App\Support\Feature;
use App\Support\SearchEngine;
use App\Support\SearchQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function catalog(): JsonResponse
    {
        return response()->json(SearchEngine::blueprint());
    }

    public function search(Request $request, VendorSearch $search, RecommendationService $recommendations): JsonResponse
    {
        $query = SearchQuery::fromArray($request->all());
        $results = $search->search($query);
        $this->learn($request, $recommendations, $query->toArray(), 'search');

        return response()->json([
            'query' => $query->toArray(),
            'total' => $results['total'],
            'vendors' => array_map(fn (array $row) => $search->present($row), $results['vendors']),
        ]);
    }

    public function chat(Request $request, AiAssistant $assistant, RecommendationService $recommendations): JsonResponse
    {
        if (! $assistant->enabled()) {
            return response()->json(['message' => 'AI assistant is disabled.'], 403);
        }

        $message = trim((string) $request->input('message', $request->input('brief', '')));
        if ($message === '') {
            return response()->json([
                'prompt' => SearchEngine::settings()['ai_prompt'] ?? 'What will you create today?',
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

        $client = $this->clientFrom($request);
        $conversation ??= $assistant->start($client, preg_match('/\p{Arabic}/u', $message) ? 'ar' : 'en');

        $result = $assistant->chat($conversation, $message, [
            'locale' => $request->input('locale'),
        ]);

        $this->learn($request, $recommendations, $result['slots'] ?? [], 'assistant');

        return response()->json($result);
    }

    public function assistant(Request $request, AiAssistant $assistant, VendorSearch $search, RecommendationService $recommendations): JsonResponse
    {
        $brief = trim((string) $request->input('brief', $request->input('q', '')));

        if ($brief === '') {
            return response()->json([
                'prompt' => SearchEngine::settings()['ai_prompt'] ?? 'What will you create today?',
                'message' => 'Type or speak your idea in everyday language.',
            ], 422);
        }

        $result = $assistant->assist($brief, $request->only([
            'city_id', 'latitude', 'longitude', 'radius_km', 'available_on', 'min_price', 'max_price',
        ]));

        $this->learn($request, $recommendations, $result['interpreted'] ?? [], 'assistant');

        $result['vendors'] = array_map(fn (array $row) => $search->present($row), $result['results']['vendors']);
        $result['total'] = $result['results']['total'];
        unset($result['results']);

        return response()->json($result);
    }

    public function recommendations(Request $request, RecommendationService $recommendations): JsonResponse
    {
        if (! Feature::enabled('smart_recommendations')) {
            return response()->json(['message' => 'Smart recommendations are disabled.'], 403);
        }

        $client = $this->clientFrom($request);
        if (! $client) {
            return response()->json(['message' => 'Pass client_id or authenticate as a client.'], 422);
        }

        return response()->json($recommendations->forClient($client, max(1, (int) $request->integer('limit', 6))));
    }

    public function moodboard(Request $request, Booking $booking, MoodboardService $moodboards): JsonResponse
    {
        if (! Feature::enabled('moodboard')) {
            return response()->json(['message' => 'Moodboard is disabled.'], 403);
        }

        $mode = SearchEngine::settings()['moodboard_mode'] ?? 'after_payment';
        $paid = in_array($booking->escrow_status, ['held', 'released', 'split'], true);

        if ($mode === 'after_payment' && ! $paid) {
            return response()->json([
                'locked' => true,
                'message' => 'The moodboard and shoot script appear after payment.',
            ], 402);
        }

        $board = $moodboards->generateForBooking($booking, $request->input('brief'));

        return response()->json($board);
    }

    /**
     * @param  array<string, mixed>  $query
     */
    protected function learn(Request $request, RecommendationService $recommendations, array $query, string $source): void
    {
        $client = $this->clientFrom($request);
        if ($client) {
            $recommendations->recordFromSearch($client, $query, $source);
        }
    }

    protected function clientFrom(Request $request): ?User
    {
        if ($request->filled('client_id')) {
            return User::query()->where('role', 'client')->find($request->integer('client_id'));
        }

        $user = $request->user();

        return $user?->isClient() ? $user : null;
    }
}
