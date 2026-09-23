<?php

namespace App\Services;

use App\Models\AssistantConversation;
use App\Models\AssistantMessage;
use App\Models\Category;
use App\Models\City;
use App\Models\FilterTag;
use App\Models\RecommendationRule;
use App\Models\User;
use App\Models\VendorType;
use App\Support\Feature;
use App\Support\Finance;
use App\Support\SearchEngine;
use App\Support\SearchQuery;
use Carbon\Carbon;
use Throwable;

class AiAssistant
{
    public function __construct(
        protected GeminiClient $gemini,
        protected VendorSearch $search,
    ) {}

    public function enabled(): bool
    {
        $settings = SearchEngine::settings();

        return Feature::enabled('ai_assistant') && (bool) ($settings['ai_enabled'] ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function assist(string $brief, array $overrides = []): array
    {
        $interpreted = $this->interpret($brief);
        $query = SearchQuery::fromArray(array_merge($interpreted, $overrides, ['brief' => $brief]));
        $results = $this->search->search($query);
        $addOns = $this->addOns($brief, $query);

        return [
            'prompt' => SearchEngine::settings()['ai_prompt'] ?? 'What will you create today?',
            'provider' => $this->gemini->configured() ? 'gemini' : 'lexicon',
            'interpreted' => $query->toArray(),
            'summary' => $interpreted['summary'] ?? null,
            'style' => $interpreted['style'] ?? null,
            'mood' => $interpreted['mood'] ?? null,
            'add_ons' => $addOns,
            'results' => $results,
            'moodboard_locked' => (SearchEngine::settings()['moodboard_mode'] ?? 'after_payment') === 'after_payment',
        ];
    }

    /**
     * Conversational turn for the website and app. Asks location → date → budget, then compares vendors.
     *
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public function chat(AssistantConversation $conversation, string $message, array $context = []): array
    {
        $message = trim($message);
        if ($message === '') {
            throw new \InvalidArgumentException('Message cannot be empty.');
        }

        if (preg_match('/\p{Arabic}/u', $message)) {
            $conversation->locale = 'ar';
        } elseif (! $conversation->locale) {
            $conversation->locale = $context['locale'] ?? 'en';
        }

        $conversation->messages()->create([
            'role' => 'user',
            'body' => $message,
        ]);

        $catalog = $this->catalog();
        $lexicon = $this->fromLexicon($message, $catalog);
        $gemini = $this->fromGeminiChat($conversation, $message, $catalog);
        $incoming = $this->mergeSlots($lexicon, $gemini['slots'] ?? []);
        $slots = $this->mergeSlots($conversation->slotBag(), $incoming);
        $ask = $this->nextQuestion($slots);
        $vendors = [];
        $comparison = [];
        $pick = null;
        $provider = ($gemini['used'] ?? false) ? 'gemini' : 'lexicon';

        if ($ask === null) {
            $query = $this->queryFromSlots($slots, $message);
            $results = $this->search->search($query);
            $vendors = array_map(fn (array $row) => $this->search->present($row), $results['vendors']);
            $comparison = $this->compare($vendors);
            $pick = $this->pick($comparison, $conversation->locale);
            $conversation->status = 'ready';
            $reply = $gemini['reply'] ?? null;
            if (! filled($reply) || $ask !== null) {
                $reply = $this->recommendationReply($conversation->locale, $slots, $pick, $comparison);
            }
        } else {
            $conversation->status = 'gathering';
            $reply = $gemini['reply'] ?? null;
            $geminiAsk = $gemini['ask'] ?? null;
            if ($geminiAsk && $this->nextQuestion($slots) === $geminiAsk && filled($reply)) {
                // keep Gemini wording when it asks for the slot we still need
            } else {
                $reply = $this->question($ask, $conversation->locale, $slots);
            }
        }

        $conversation->slots = $slots;
        $conversation->save();

        $meta = [
            'ask' => $ask,
            'slots' => $slots,
            'vendor_ids' => array_column($vendors, 'id'),
        ];

        $conversation->messages()->create([
            'role' => 'assistant',
            'body' => $reply,
            'meta' => $meta,
        ]);

        return [
            'conversation_id' => $conversation->uuid,
            'provider' => $provider,
            'locale' => $conversation->locale,
            'status' => $conversation->status,
            'reply' => $reply,
            'ask' => $ask,
            'slots' => $slots,
            'missing' => $ask ? [$ask] : [],
            'vendors' => $vendors,
            'comparison' => $comparison,
            'pick' => $pick,
            'add_ons' => $this->addOns($message, $this->queryFromSlots($slots, $message)),
            'messages' => $conversation->messages()->get()->map(fn (AssistantMessage $item): array => [
                'role' => $item->role,
                'body' => $item->body,
                'created_at' => $item->created_at?->toIso8601String(),
            ])->all(),
        ];
    }

    public function start(?User $user = null, string $locale = 'en'): AssistantConversation
    {
        return AssistantConversation::query()->create([
            'user_id' => $user?->id,
            'locale' => $locale,
            'status' => 'gathering',
            'slots' => [],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function interpret(string $brief): array
    {
        $catalog = $this->catalog();
        $parsed = $this->gemini->configured()
            ? $this->fromGemini($brief, $catalog)
            : $this->fromLexicon($brief, $catalog);

        if (! empty($parsed['governorate'])) {
            $city = City::query()
                ->where('name_en', $parsed['governorate'])
                ->orWhere('name_ar', $parsed['governorate'])
                ->orWhere('governorate', $parsed['governorate'])
                ->first();
            $parsed['city_id'] = $city?->id;
        }

        return $parsed;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function addOns(string $brief, SearchQuery $query): array
    {
        if (! Feature::enabled('recommendation_rules')) {
            return [];
        }

        $haystack = mb_strtolower($brief.' '.implode(' ', $query->categorySlugs).' '.implode(' ', $query->filterTagSlugs));

        return RecommendationRule::query()
            ->where('is_active', true)
            ->with('suggestedVendorType')
            ->orderBy('sort_order')
            ->get()
            ->filter(function (RecommendationRule $rule) use ($query, $haystack): bool {
                $value = mb_strtolower((string) $rule->trigger_value);

                return match ($rule->trigger_type) {
                    'category' => in_array($rule->trigger_value, $query->categorySlugs, true) || str_contains($haystack, $value),
                    'keyword' => str_contains($haystack, $value),
                    'tag' => in_array($rule->trigger_value, $query->filterTagSlugs, true),
                    default => false,
                };
            })
            ->map(fn (RecommendationRule $rule): array => [
                'name' => $rule->name,
                'message' => $rule->suggest_message,
                'vendor_type' => $rule->suggestedVendorType?->slug,
                'vendor_type_name' => $rule->suggestedVendorType?->name_en,
                'filter_tag_ids' => $rule->suggest_filter_tag_ids,
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $catalog
     * @return array<string, mixed>
     */
    protected function fromGemini(string $brief, array $catalog): array
    {
        $settings = SearchEngine::settings();
        $allowed = json_encode($catalog, JSON_UNESCAPED_UNICODE);
        $system = $settings['ai_system_prompt'] ?? 'Map the client brief to Lens catalog slugs only.';

        try {
            $parsed = $this->gemini->generateJson(
                $system."\n\nAllowed catalog JSON:\n{$allowed}\n\nClient brief:\n{$brief}\n\n"
                .'Return JSON with keys: category_slugs, vendor_type_slugs, filter_tag_slugs, governorate, package_type, min_price, max_price, style, mood, summary. Use only catalog slugs. Empty arrays if unknown.'
            );
        } catch (Throwable) {
            return $this->fromLexicon($brief, $catalog);
        }

        return $this->normalizeParsed($parsed);
    }

    /**
     * @param  array<string, mixed>  $catalog
     * @return array<string, mixed>
     */
    protected function fromLexicon(string $brief, array $catalog): array
    {
        $text = mb_strtolower($brief);
        $categories = $this->matchSlugs($text, $catalog['categories']);
        $types = $this->matchSlugs($text, $catalog['vendor_types']);
        $tags = $this->matchSlugs($text, $catalog['filter_tags']);
        $city = $this->detectCity($brief);
        $governorate = $city?->name_en ?? $this->matchName($text, $catalog['governorates']);

        if ($categories === [] && (str_contains($text, 'food') || str_contains($text, 'طعام') || str_contains($text, 'مطعم'))) {
            $categories[] = 'fnb';
        }

        if ($categories === [] && (str_contains($text, 'wedding') || str_contains($text, 'فرح') || str_contains($text, 'زفاف') || str_contains($text, 'عرس'))) {
            $categories[] = 'wedding';
        }

        if ($types === [] && (str_contains($text, 'photo') || str_contains($text, 'صور') || str_contains($text, 'مصور'))) {
            $types[] = 'photographer';
        }

        $package = null;
        if (str_contains($text, 'half day') || str_contains($text, 'نصف يوم')) {
            $package = 'half_day';
        } elseif (str_contains($text, 'full day') || str_contains($text, 'يوم كامل')) {
            $package = 'full_day';
        } elseif (str_contains($text, 'per hour') || str_contains($text, 'بالساعة')) {
            $package = 'hourly';
        } elseif (str_contains($text, 'ugc') || str_contains($text, 'video')) {
            $package = str_contains($text, 'ugc') ? 'per_video' : $package;
        }

        [$band, $min, $max] = $this->detectBudget($text);
        $relative = $this->detectRelativeDate($text);
        $availableOn = $this->detectDate($text);

        return $this->normalizeParsed([
            'category_slugs' => array_values(array_unique($categories)),
            'vendor_type_slugs' => array_values(array_unique($types)),
            'filter_tag_slugs' => array_values(array_unique($tags)),
            'governorate' => $governorate,
            'city_id' => $city?->id,
            'package_type' => $package,
            'budget_band' => $band,
            'min_price' => $min,
            'max_price' => $max,
            'relative_date' => $relative,
            'available_on' => $availableOn,
            'summary' => 'Matched from the Lens catalog using the client brief.',
        ]);
    }

    /**
     * @return array{categories: array<string, list<string>>, vendor_types: array<string, list<string>>, filter_tags: array<string, list<string>>, governorates: list<string>, packages: list<string>}
     */
    protected function catalog(): array
    {
        return [
            'categories' => Category::query()->where('is_active', true)->get()
                ->mapWithKeys(fn (Category $item): array => [$item->slug => array_filter([$item->slug, mb_strtolower((string) $item->name_en), mb_strtolower((string) $item->name_ar)])])
                ->all(),
            'vendor_types' => VendorType::query()->marketplace()->get()
                ->mapWithKeys(fn (VendorType $item): array => [$item->slug => array_filter([$item->slug, mb_strtolower((string) $item->name_en), mb_strtolower((string) $item->name_ar)])])
                ->all(),
            'filter_tags' => FilterTag::query()
                ->where('is_active', true)
                ->whereHas('group', fn ($query) => $query->where('facet_level', 'secondary'))
                ->get()
                ->mapWithKeys(function (FilterTag $tag): array {
                    $words = array_merge(
                        [$tag->slug, mb_strtolower((string) $tag->name_en), mb_strtolower((string) $tag->name_ar)],
                        array_map(fn ($word) => mb_strtolower((string) $word), $tag->synonyms ?? []),
                    );

                    return [$tag->slug => array_values(array_filter($words))];
                })
                ->all(),
            'governorates' => City::query()->where('is_active', true)->orderBy('sort_order')->pluck('name_en')->all(),
            'packages' => ['half_day', 'full_day', 'hourly', 'per_video'],
        ];
    }

    /**
     * @param  array<string, list<string>>  $map
     * @return list<string>
     */
    protected function matchSlugs(string $text, array $map): array
    {
        $hits = [];

        foreach ($map as $slug => $words) {
            foreach ($words as $word) {
                if ($word !== '' && mb_strlen($word) >= 3 && str_contains($text, $word)) {
                    $hits[] = $slug;
                    break;
                }
            }
        }

        return $hits;
    }

    /**
     * @param  list<string>  $names
     */
    protected function matchName(string $text, array $names): ?string
    {
        foreach ($names as $name) {
            if (str_contains($text, mb_strtolower($name))) {
                return $name;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $parsed
     * @return array<string, mixed>
     */
    protected function normalizeParsed(array $parsed): array
    {
        return [
            'category_slugs' => array_values(array_filter((array) ($parsed['category_slugs'] ?? []))),
            'vendor_type_slugs' => array_values(array_filter((array) ($parsed['vendor_type_slugs'] ?? []))),
            'filter_tag_slugs' => array_values(array_filter((array) ($parsed['filter_tag_slugs'] ?? []))),
            'governorate' => $parsed['governorate'] ?? null,
            'city_id' => ! empty($parsed['city_id']) ? (int) $parsed['city_id'] : null,
            'package_type' => $parsed['package_type'] ?? null,
            'min_price' => $parsed['min_price'] ?? null,
            'max_price' => $parsed['max_price'] ?? null,
            'budget_band' => $parsed['budget_band'] ?? null,
            'relative_date' => $parsed['relative_date'] ?? null,
            'available_on' => $parsed['available_on'] ?? null,
            'style' => $parsed['style'] ?? null,
            'mood' => $parsed['mood'] ?? null,
            'summary' => $parsed['summary'] ?? null,
        ];
    }

    /**
     * @param  array<string, mixed>  $catalog
     * @return array{used: bool, reply?: ?string, ask?: ?string, slots?: array<string, mixed>}
     */
    protected function fromGeminiChat(AssistantConversation $conversation, string $message, array $catalog): array
    {
        if (! $this->gemini->configured()) {
            return ['used' => false];
        }

        $settings = SearchEngine::settings();
        $history = $conversation->messages()
            ->orderByDesc('id')
            ->limit(8)
            ->get()
            ->reverse()
            ->map(fn (AssistantMessage $item): string => strtoupper($item->role).': '.$item->body)
            ->implode("\n");

        $system = $settings['ai_system_prompt'] ?? 'You are the Lens booking assistant.';

        try {
            $parsed = $this->gemini->generateJson(
                $system."\n\nAllowed catalog JSON:\n".json_encode($catalog, JSON_UNESCAPED_UNICODE)
                ."\n\nCurrent slots JSON:\n".json_encode($conversation->slotBag(), JSON_UNESCAPED_UNICODE)
                ."\n\nRecent chat:\n{$history}\n\nLatest client message:\n{$message}\n\n"
                .'Return JSON keys: reply (string in the client language), ask (location|date|budget or null), '
                .'slots object with category_slugs, vendor_type_slugs, filter_tag_slugs, governorate, available_on (YYYY-MM-DD), relative_date, budget_band (low|mid|high), min_price, max_price, package_type. '
                .'Ask only if location, date, or budget is still missing — one slot, order location then date then budget. Use only catalog slugs and Egyptian governorates.'
            );
        } catch (Throwable) {
            return ['used' => false];
        }

        return [
            'used' => true,
            'reply' => $parsed['reply'] ?? null,
            'ask' => in_array($parsed['ask'] ?? null, ['location', 'date', 'budget'], true) ? $parsed['ask'] : null,
            'slots' => $this->normalizeParsed($parsed['slots'] ?? $parsed),
        ];
    }

    /**
     * @param  array<string, mixed>  $current
     * @param  array<string, mixed>  $incoming
     * @return array<string, mixed>
     */
    protected function mergeSlots(array $current, array $incoming): array
    {
        foreach (['category_slugs', 'vendor_type_slugs', 'filter_tag_slugs'] as $key) {
            $add = array_values(array_filter((array) ($incoming[$key] ?? [])));
            $current[$key] = array_values(array_unique(array_merge((array) ($current[$key] ?? []), $add)));
        }

        foreach (['governorate', 'available_on', 'relative_date', 'budget_band', 'package_type', 'style', 'mood'] as $key) {
            if (filled($incoming[$key] ?? null)) {
                $current[$key] = $incoming[$key];
            }
        }

        foreach (['min_price', 'max_price', 'city_id'] as $key) {
            if (isset($incoming[$key]) && $incoming[$key] !== null && $incoming[$key] !== '') {
                $current[$key] = $incoming[$key];
            }
        }

        $this->resolveCity($current);
        $this->applyBudgetBand($current);

        return $current;
    }

    /**
     * @param  array<string, mixed>  $slots
     */
    protected function resolveCity(array &$slots): void
    {
        if (! empty($slots['city_id'])) {
            $city = City::query()->find($slots['city_id']);
            if ($city) {
                $slots['governorate'] = $city->name_en;
                $slots['city_id'] = $city->id;

                return;
            }
        }

        if (! empty($slots['governorate'])) {
            $city = $this->detectCity((string) $slots['governorate']);
            if ($city) {
                $slots['city_id'] = $city->id;
                $slots['governorate'] = $city->name_en;
            }
        }
    }

    /**
     * @param  array<string, mixed>  $slots
     */
    protected function applyBudgetBand(array &$slots): void
    {
        $band = $slots['budget_band'] ?? null;
        if (! $band || (isset($slots['min_price']) && $slots['min_price'] !== null && isset($slots['max_price']) && $slots['max_price'] !== null)) {
            return;
        }

        [$min, $max] = match ($band) {
            'low' => [0, 1500],
            'high' => [2500, null],
            default => [1200, 2800],
        };

        $slots['min_price'] = $slots['min_price'] ?? $min;
        $slots['max_price'] = $slots['max_price'] ?? $max;
    }

    /**
     * @param  array<string, mixed>  $slots
     */
    protected function nextQuestion(array $slots): ?string
    {
        if (empty($slots['city_id']) && empty($slots['governorate'])) {
            return 'location';
        }

        if (empty($slots['available_on']) && empty($slots['relative_date'])) {
            return 'date';
        }

        if (empty($slots['budget_band']) && ($slots['min_price'] ?? null) === null && ($slots['max_price'] ?? null) === null) {
            return 'budget';
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $slots
     */
    protected function queryFromSlots(array $slots, string $brief): SearchQuery
    {
        $payload = [
            'brief' => $brief,
            'category_slugs' => $slots['category_slugs'] ?? [],
            'vendor_type_slugs' => $slots['vendor_type_slugs'] ?? [],
            'filter_tag_slugs' => $slots['filter_tag_slugs'] ?? [],
            'city_id' => $slots['city_id'] ?? null,
            'package_type' => $slots['package_type'] ?? null,
            'min_price' => $slots['min_price'] ?? null,
            'max_price' => $slots['max_price'] ?? null,
            'limit' => 4,
        ];

        if (! empty($slots['available_on'])) {
            $payload['available_on'] = $slots['available_on'];
        }

        return SearchQuery::fromArray($payload);
    }

    /**
     * @param  list<array<string, mixed>>  $vendors
     * @return list<array<string, mixed>>
     */
    protected function compare(array $vendors): array
    {
        return collect($vendors)->take(4)->map(function (array $vendor, int $index): array {
            $price = $vendor['half_day_price'] ?? $vendor['hourly_price'] ?? $vendor['full_day_price'] ?? $vendor['per_video_price'];

            return [
                'rank' => $index + 1,
                'id' => $vendor['id'],
                'display_name' => $vendor['display_name'],
                'vendor_type_name' => $vendor['vendor_type_name'],
                'city' => $vendor['city'],
                'rating_avg' => $vendor['rating_avg'],
                'rating_count' => $vendor['rating_count'],
                'completed_sessions' => $vendor['completed_sessions'],
                'price' => $price,
                'currency' => Finance::currency(),
                'badges' => $vendor['badges'] ?? [],
                'score' => $vendor['score'] ?? 0,
                'reasons' => $vendor['reasons'] ?? [],
                'is_featured' => $vendor['is_featured'] ?? false,
            ];
        })->values()->all();
    }

    /**
     * @param  list<array<string, mixed>>  $comparison
     * @return array<string, mixed>|null
     */
    protected function pick(array $comparison, string $locale): ?array
    {
        $best = $comparison[0] ?? null;
        if (! $best) {
            return null;
        }

        $why = $locale === 'ar'
            ? ($best['display_name'].' الأنسب: تقييم '.$best['rating_avg'].' و'.(int) $best['completed_sessions'].' جلسة مكتملة في '.$best['city'].'.')
            : ($best['display_name'].' is the best fit: '.$best['rating_avg'].' stars and '.(int) $best['completed_sessions'].' completed sessions in '.$best['city'].'.');

        return [
            'id' => $best['id'],
            'display_name' => $best['display_name'],
            'why' => $why,
        ];
    }

    /**
     * @param  array<string, mixed>  $slots
     * @param  list<array<string, mixed>>  $comparison
     */
    protected function recommendationReply(string $locale, array $slots, ?array $pick, array $comparison): string
    {
        if ($comparison === []) {
            return $locale === 'ar'
                ? 'ما لقيتش مقدم مناسب بالمواصفات دي. نقدر نوسّع الميزانية أو نغيّر المحافظة.'
                : 'I could not find a matching vendor. We can widen the budget or try another governorate.';
        }

        $lines = $locale === 'ar'
            ? 'دي مقارنة سريعة بين أفضل الخيارات:'
            : 'Here is a quick comparison of the best matches:';

        foreach ($comparison as $row) {
            $price = $row['price'] !== null ? number_format((float) $row['price'], 0).' '.$row['currency'] : '—';
            $lines .= "\n".$row['rank'].'. '.$row['display_name'].' · '.$row['city'].' · '.$price.' · '.$row['rating_avg'].'★';
        }

        if ($pick) {
            $lines .= "\n\n".($locale === 'ar' ? 'ترشيحي: ' : 'My pick: ').$pick['why'];
            $lines .= $locale === 'ar'
                ? ' تقدر تفتح ملفه وتحجز لما تكون جاهز.'
                : ' Open their profile and book when you are ready.';
        }

        return $lines;
    }

    /**
     * @param  array<string, mixed>  $slots
     */
    protected function question(string $ask, string $locale, array $slots): string
    {
        if ($locale === 'ar') {
            return match ($ask) {
                'location' => 'تمام. الفرح أو التصوير هيكون في أنهي محافظة؟',
                'date' => 'تحب الجلسة يوم أنهي تاريخ تقريباً؟',
                default => 'ميزانيتك للكاميرا/التصوير كام تقريباً بالجنيه؟ رخيص، متوسط، ولا أعلى؟',
            };
        }

        return match ($ask) {
            'location' => 'Which Egyptian governorate should the shoot be in?',
            'date' => 'Which date works for the session?',
            default => 'What budget range should I stay within (EGP)? Low, mid, or high is enough.',
        };
    }

    protected function detectCity(string $text): ?City
    {
        $haystack = mb_strtolower($text);

        return City::query()->where('is_active', true)->get()->first(function (City $city) use ($haystack): bool {
            foreach ([$city->name_en, $city->name_ar, $city->governorate] as $name) {
                if ($name && mb_stripos($haystack, mb_strtolower((string) $name)) !== false) {
                    return true;
                }
            }

            return false;
        });
    }

    /**
     * @return array{0: ?string, 1: ?float, 2: ?float}
     */
    protected function detectBudget(string $text): array
    {
        if (str_contains($text, 'متوسط') || str_contains($text, 'medium') || preg_match('/\bmid\b/', $text)) {
            return ['mid', 1200, 2800];
        }
        if (str_contains($text, 'رخيص') || str_contains($text, 'رخيصة') || str_contains($text, 'cheap') || str_contains($text, 'low budget')) {
            return ['low', 0, 1500];
        }
        if (str_contains($text, 'غالي') || str_contains($text, 'premium') || str_contains($text, 'expensive') || str_contains($text, 'high end')) {
            return ['high', 2500, null];
        }

        if (preg_match('/(\d{3,6})\s*(?:-|to|الى|إلى)\s*(\d{3,6})/u', $text, $match)) {
            return [null, (float) $match[1], (float) $match[2]];
        }

        if (preg_match('/(\d{3,6})\s*(?:egp|جنيه|le)?/u', $text, $match) && (str_contains($text, 'ميزاني') || str_contains($text, 'budget') || str_contains($text, 'سعر'))) {
            $amount = (float) $match[1];

            return ['mid', round($amount * 0.75, 2), round($amount * 1.25, 2)];
        }

        return [null, null, null];
    }

    protected function detectRelativeDate(string $text): ?string
    {
        if (str_contains($text, 'الشهر الجاي') || str_contains($text, 'الشهر القادم') || str_contains($text, 'next month')) {
            return 'next_month';
        }
        if (str_contains($text, 'الاسبوع الجاي') || str_contains($text, 'الأسبوع الجاي') || str_contains($text, 'next week')) {
            return 'next_week';
        }
        if (str_contains($text, 'weekend') || str_contains($text, 'الويكند') || str_contains($text, 'نهاية الأسبوع')) {
            return 'weekend';
        }

        return null;
    }

    protected function detectDate(string $text): ?string
    {
        if (preg_match('/\b(\d{4}-\d{2}-\d{2})\b/', $text, $match)) {
            return $match[1];
        }

        if (preg_match('/\b(\d{1,2})[\/\-.](\d{1,2})(?:[\/\-.](\d{2,4}))?\b/', $text, $match)) {
            $year = isset($match[3]) ? (int) $match[3] : now()->year;
            if ($year < 100) {
                $year += 2000;
            }
            try {
                return Carbon::createFromDate($year, (int) $match[2], (int) $match[1])->toDateString();
            } catch (Throwable) {
                return null;
            }
        }

        return null;
    }
}
