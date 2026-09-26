<?php

namespace App\Services\ProgressAi;

use Anthropic\Client;
use Anthropic\Core\Exceptions\APIConnectionException;
use Anthropic\Core\Exceptions\APIStatusException;
use Anthropic\Core\Exceptions\RateLimitException;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Anthropic Claude API (paid). Kept as an alternative to Gemini.
 */
class AnthropicDraftDriver implements DraftDriver
{
    public function isConfigured(): bool
    {
        return filled(config('services.anthropic.key'));
    }

    public function generate(string $system, string $prompt, array $images, array $fields): array
    {
        $content = [];
        foreach ($images as $image) {
            $content[] = [
                'type' => 'image',
                'source' => ['type' => 'base64', 'mediaType' => $image['mime'], 'data' => $image['data']],
            ];
        }
        $content[] = ['type' => 'text', 'text' => $prompt];

        $client = new Client(
            apiKey: config('services.anthropic.key'),
            requestOptions: ['timeout' => 60, 'maxRetries' => 1],
        );

        try {
            $message = $client->beta->messages->create(
                model: config('services.anthropic.model'),
                maxTokens: 8000,
                system: $system,
                messages: [['role' => 'user', 'content' => $content]],
                // A short rewriting task: low effort keeps it quick on a phone.
                outputConfig: [
                    'effort' => 'low',
                    'format' => [
                        'type' => 'json_schema',
                        'schema' => [
                            'type' => 'object',
                            'properties' => collect($fields)->mapWithKeys(fn (string $field): array => [$field => ['type' => 'string']])->all(),
                            'required' => $fields,
                            'additionalProperties' => false,
                        ],
                    ],
                ],
                // If the primary model declines, the API retries on a fallback model in the same call.
                fallbacks: 'default',
                betas: ['server-side-fallback-2026-07-01'],
            );
        } catch (RateLimitException $e) {
            Log::warning('Claude progress assistant rate limited');
            throw new RuntimeException('The AI assistant is busy right now. Try again in a minute, or write the update yourself.', previous: $e);
        } catch (APIStatusException $e) {
            Log::error('Claude progress assistant API error', ['error' => $e->getMessage()]);
            throw new RuntimeException('The AI assistant could not draft this update. Please write it yourself.', previous: $e);
        } catch (APIConnectionException $e) {
            Log::error('Claude progress assistant connection error', ['error' => $e->getMessage()]);
            throw new RuntimeException('Could not reach the AI assistant. Check your connection and try again.', previous: $e);
        }

        if ($message->stopReason === 'refusal') {
            throw new RuntimeException('The AI assistant could not draft this update. Please write it yourself.');
        }

        $json = null;
        foreach ($message->content as $block) {
            if ($block->type === 'text') {
                $json = json_decode($block->text, true);
                break;
            }
        }

        if (! is_array($json) || $message->stopReason === 'max_tokens') {
            Log::error('Claude progress assistant returned an unusable draft', ['stop_reason' => $message->stopReason]);
            throw new RuntimeException('The AI assistant returned an incomplete draft. Please try again.');
        }

        return $json;
    }
}
