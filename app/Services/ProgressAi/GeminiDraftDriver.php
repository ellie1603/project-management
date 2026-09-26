<?php

namespace App\Services\ProgressAi;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Google Gemini API (Google AI Studio key) via the generateContent endpoint.
 * The free tier is rate limited, and Google may use free-tier content to
 * improve its products.
 */
class GeminiDraftDriver implements DraftDriver
{
    private const ENDPOINT = 'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent';

    public function isConfigured(): bool
    {
        return filled(config('services.gemini.key'));
    }

    public function generate(string $system, string $prompt, array $images, array $fields): array
    {
        $parts = [];
        foreach ($images as $image) {
            $parts[] = ['inline_data' => ['mime_type' => $image['mime'], 'data' => $image['data']]];
        }
        $parts[] = ['text' => $prompt];

        $body = [
            'systemInstruction' => ['parts' => [['text' => $system]]],
            'contents' => [['role' => 'user', 'parts' => $parts]],
            'generationConfig' => [
                'responseMimeType' => 'application/json',
                'responseSchema' => [
                    'type' => 'OBJECT',
                    'properties' => collect($fields)->mapWithKeys(fn (string $field): array => [$field => ['type' => 'STRING']])->all(),
                    'required' => $fields,
                ],
                // Factual rewriting, not creative writing.
                'temperature' => 0.2,
                'maxOutputTokens' => 8192,
            ],
        ];

        // Free-tier models are often briefly overloaded (503) or rate limited (429);
        // each model has its own quota, so fall through to the next one.
        $models = collect([config('services.gemini.model'), config('services.gemini.fallback_model')])
            ->filter()
            ->map(fn (string $model): string => preg_replace('#^models/#', '', $model))
            ->unique()
            ->values();

        foreach ($models as $model) {
            try {
                $response = Http::withHeaders(['x-goog-api-key' => config('services.gemini.key')])
                    ->acceptJson()
                    ->timeout(60)
                    ->post(sprintf(self::ENDPOINT, rawurlencode($model)), $body);
            } catch (ConnectionException $e) {
                Log::error('Gemini progress assistant connection error', ['error' => $e->getMessage()]);
                throw new RuntimeException('Could not reach the AI assistant. Check your connection and try again.', previous: $e);
            }

            if ($response->status() !== 429 && $response->status() < 500) {
                break;
            }

            Log::warning('Gemini progress assistant model unavailable', ['model' => $model, 'status' => $response->status()]);
        }

        if ($response->status() === 429 || $response->status() >= 500) {
            throw new RuntimeException('The free AI service is busy right now. Try again in a minute, or write the update yourself.');
        }

        if ($response->failed()) {
            // Log Google's error message, never the request (it carries the API key header).
            Log::error('Gemini progress assistant API error', [
                'status' => $response->status(),
                'error' => $response->json('error.message'),
            ]);
            throw new RuntimeException('The AI assistant could not draft this update. Please write it yourself.');
        }

        if ($response->json('promptFeedback.blockReason') !== null) {
            throw new RuntimeException('The AI assistant could not draft this update. Please write it yourself.');
        }

        $candidate = $response->json('candidates.0');
        $finishReason = $candidate['finishReason'] ?? null;

        if ($finishReason === 'MAX_TOKENS') {
            throw new RuntimeException('The AI assistant returned an incomplete draft. Please try again.');
        }

        if ($finishReason !== null && $finishReason !== 'STOP') {
            Log::warning('Gemini progress assistant stopped early', ['finish_reason' => $finishReason]);
            throw new RuntimeException('The AI assistant could not draft this update. Please write it yourself.');
        }

        $text = collect($candidate['content']['parts'] ?? [])
            ->reject(fn (array $part): bool => (bool) ($part['thought'] ?? false))
            ->pluck('text')
            ->filter()
            ->implode('');

        $json = json_decode($text, true);

        if (! is_array($json)) {
            Log::error('Gemini progress assistant returned non-JSON output');
            throw new RuntimeException('The AI assistant returned an incomplete draft. Please try again.');
        }

        return $json;
    }
}
