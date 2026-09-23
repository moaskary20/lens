<?php

namespace App\Services;

use App\Support\SearchEngine;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GeminiClient
{
    public function configured(): bool
    {
        return filled($this->apiKey());
    }

    public function apiKey(): ?string
    {
        $settings = SearchEngine::settings();
        $fromSettings = $settings['gemini_api_key'] ?? null;

        return filled($fromSettings) ? (string) $fromSettings : (config('services.gemini.key') ?: null);
    }

    public function model(): string
    {
        $settings = SearchEngine::settings();

        return (string) ($settings['gemini_model'] ?? config('services.gemini.model', 'gemini-2.5-flash'));
    }

    /**
     * @param  array<string, mixed>  $schema
     * @return array<string, mixed>
     */
    public function generateJson(string $prompt, array $schema = []): array
    {
        if (! $this->configured()) {
            throw new RuntimeException('Gemini API key is not configured.');
        }

        $payload = [
            'contents' => [
                ['parts' => [['text' => $prompt]]],
            ],
            'generationConfig' => [
                'temperature' => 0.2,
                'responseMimeType' => 'application/json',
            ],
        ];

        if ($schema !== []) {
            $payload['generationConfig']['responseJsonSchema'] = $schema;
        }

        $response = Http::timeout(40)
            ->acceptJson()
            ->post($this->endpoint(), $payload);

        if ($response->failed()) {
            throw new RuntimeException('Gemini request failed: '.$response->body());
        }

        $text = (string) data_get($response->json(), 'candidates.0.content.parts.0.text', '');

        return $this->decode($text);
    }

    protected function endpoint(): string
    {
        $model = rawurlencode($this->model());
        $key = rawurlencode((string) $this->apiKey());

        return "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$key}";
    }

    /**
     * @return array<string, mixed>
     */
    protected function decode(string $text): array
    {
        $clean = trim($text);
        $clean = preg_replace('/^```(?:json)?\s*|\s*```$/', '', $clean) ?? $clean;
        $decoded = json_decode($clean, true);

        if (! is_array($decoded)) {
            throw new RuntimeException('Gemini returned invalid JSON.');
        }

        return $decoded;
    }
}
