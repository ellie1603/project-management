<?php

namespace App\Services\ProgressAi;

use RuntimeException;

/**
 * One AI provider behind the progress-report assistant. Drivers only talk to
 * their API; the prompt, the fields, and clean-up live in ProgressReportAssistant.
 */
interface DraftDriver
{
    public function isConfigured(): bool;

    /**
     * Ask the model for a JSON object with exactly the given string fields.
     *
     * @param  array<int, array{mime: string, data: string}>  $images  base64-encoded
     * @param  array<int, string>  $fields
     * @return array<string, mixed> the decoded JSON object
     *
     * @throws RuntimeException with a message safe to show the user
     */
    public function generate(string $system, string $prompt, array $images, array $fields): array;
}
