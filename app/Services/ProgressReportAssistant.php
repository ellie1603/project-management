<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectPhase;
use App\Services\ProgressAi\AnthropicDraftDriver;
use App\Services\ProgressAi\DraftDriver;
use App\Services\ProgressAi\GeminiDraftDriver;
use Illuminate\Http\UploadedFile;
use RuntimeException;

/**
 * Turns a site worker's rough notes (any language, any length) plus optional
 * site photos into a clear, structured English progress report the admin can
 * read at a glance. The draft is only a suggestion: personnel review and edit
 * it before saving, and nothing is stored from here.
 *
 * The AI provider is chosen by config('services.progress_ai.provider').
 */
class ProgressReportAssistant
{
    /** Photos sent per request — enough context without slowing a phone upload. */
    public const MAX_IMAGES = 4;

    private const IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

    private const FIELDS = ['accomplishments', 'activities_completed', 'activities_remaining', 'issues'];

    private const SYSTEM_PROMPT = <<<'PROMPT'
        You help construction site personnel of Barbaza Multi-Purpose Cooperative (BMPC) in the
        Philippines write project progress reports. The reports are read by the cooperative's
        admin/CEO, who is not on site and needs to understand exactly what changed.

        The personnel's notes may be short, informal, or written in English, Filipino/Tagalog,
        Hiligaynon, Kinaray-a, or a mix. Always write the report in clear, plain English.

        Rules:
        - Only state what the notes say or what is clearly visible in the photos. Never invent
          quantities, measurements, dates, names, or causes. If something is unclear, leave it out.
        - Describe photo content only when it is clearly visible, and do not guess at hidden work.
        - Keep it short and concrete: each field is one to three sentences, or a few short lines.
        - accomplishments: what was done in this update, in one or two sentences.
        - activities_completed: the specific tasks finished, one per line, no bullets or numbering.
        - activities_remaining: next tasks for this stage if the notes mention them, one per line;
          otherwise an empty string.
        - issues: problems, delays, or risks mentioned (weather, materials, manpower, equipment,
          permits); otherwise an empty string.
        PROMPT;

    public function isEnabled(): bool
    {
        return $this->driver()?->isConfigured() ?? false;
    }

    /**
     * @param  array<int, UploadedFile>  $photos
     * @return array{accomplishments: string, activities_completed: string, activities_remaining: string, issues: string}
     *
     * @throws RuntimeException with a message safe to show the user
     */
    public function draft(Project $project, ProjectPhase $phase, string $phaseStatus, string $notes, array $photos = []): array
    {
        $driver = $this->driver();

        if ($driver === null || ! $driver->isConfigured()) {
            throw new RuntimeException('The AI assistant is not configured.');
        }

        $images = array_map(
            fn (UploadedFile $photo): array => [
                'mime' => (string) $photo->getMimeType(),
                'data' => base64_encode((string) file_get_contents($photo->getRealPath())),
            ],
            array_slice($this->usableImages($photos), 0, self::MAX_IMAGES),
        );

        $prompt = implode("\n", [
            "Project: {$project->title}",
            'Location: '.($project->location ?? 'Not specified'),
            "Construction stage: {$phase->name}",
            'Stage status after this update: '.($phaseStatus === 'Completed' ? 'Finished' : 'Still in progress'),
            count($images) > 0 ? 'Site photos from this update are attached.' : 'No photos attached.',
            '',
            'Site notes from the personnel:',
            $notes,
        ]);

        $json = $driver->generate(self::SYSTEM_PROMPT, $prompt, $images, self::FIELDS);

        $draft = [];
        foreach (self::FIELDS as $field) {
            $draft[$field] = trim((string) ($json[$field] ?? ''));
        }

        if ($draft['accomplishments'] === '') {
            throw new RuntimeException('The AI assistant returned an empty draft. Please try again or write it yourself.');
        }

        return $draft;
    }

    private function driver(): ?DraftDriver
    {
        return match (config('services.progress_ai.provider')) {
            'gemini' => app(GeminiDraftDriver::class),
            'anthropic' => app(AnthropicDraftDriver::class),
            default => null,
        };
    }

    /**
     * @param  array<int, UploadedFile>  $files
     * @return array<int, UploadedFile>
     */
    private function usableImages(array $files): array
    {
        return array_values(array_filter(
            $files,
            fn ($file): bool => $file instanceof UploadedFile
                && $file->isValid()
                && in_array($file->getMimeType(), self::IMAGE_TYPES, true)
                && $file->getSize() <= 5 * 1024 * 1024,
        ));
    }
}
