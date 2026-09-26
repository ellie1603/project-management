<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\ProjectPhase;
use App\Models\User;
use App\Services\ProgressReportAssistant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class GeminiProgressAssistantTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.progress_ai.provider' => 'gemini',
            'services.gemini.key' => 'test-gemini-key',
            'services.gemini.model' => 'gemini-3.8-flash',
        ]);
    }

    public function test_sends_notes_and_photos_to_gemini_and_returns_the_structured_draft(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response($this->candidate([
                'accomplishments' => 'Roof sheets were installed on the east side.',
                'activities_completed' => "Roof sheet installation\nFlashing",
                'activities_remaining' => 'Gutter installation',
                'issues' => '',
            ])),
        ]);

        [$project, $phase] = $this->projectWithPhase();

        $draft = app(ProgressReportAssistant::class)->draft(
            $project,
            $phase,
            'In Progress',
            'natapos roofing east side',
            [UploadedFile::fake()->image('roof.jpg', 800, 600)],
        );

        $this->assertSame('Roof sheets were installed on the east side.', $draft['accomplishments']);
        $this->assertSame("Roof sheet installation\nFlashing", $draft['activities_completed']);
        $this->assertSame('', $draft['issues']);

        Http::assertSent(function (Request $request): bool {
            $parts = $request['contents'][0]['parts'];

            return str_ends_with($request->url(), '/v1beta/models/gemini-3.8-flash:generateContent')
                && $request->hasHeader('x-goog-api-key', 'test-gemini-key')
                && $parts[0]['inline_data']['mime_type'] === 'image/jpeg'
                && $parts[0]['inline_data']['data'] !== ''
                && str_contains($parts[1]['text'], 'natapos roofing east side')
                && $request['generationConfig']['responseMimeType'] === 'application/json'
                && $request['generationConfig']['responseSchema']['required'] === ['accomplishments', 'activities_completed', 'activities_remaining', 'issues'];
        });
    }

    public function test_falls_back_to_the_second_model_when_the_first_is_overloaded(): void
    {
        config(['services.gemini.fallback_model' => 'gemini-3.5-flash']);
        Http::fake([
            '*gemini-3.8-flash:generateContent' => Http::response(['error' => ['code' => 503, 'message' => 'high demand']], 503),
            '*gemini-3.5-flash:generateContent' => Http::response($this->candidate([
                'accomplishments' => 'Trusses installed.',
                'activities_completed' => '',
                'activities_remaining' => '',
                'issues' => '',
            ])),
        ]);
        [$project, $phase] = $this->projectWithPhase();

        $draft = app(ProgressReportAssistant::class)->draft($project, $phase, 'In Progress', 'trusses done');

        $this->assertSame('Trusses installed.', $draft['accomplishments']);
        Http::assertSentCount(2);
    }

    public function test_free_tier_rate_limit_gives_a_readable_message(): void
    {
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response(['error' => ['code' => 429, 'message' => 'Resource exhausted']], 429)]);
        [$project, $phase] = $this->projectWithPhase();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The free AI service is busy right now.');

        app(ProgressReportAssistant::class)->draft($project, $phase, 'In Progress', 'natapos roofing');
    }

    public function test_blocked_or_cut_off_responses_are_not_returned_as_drafts(): void
    {
        [$project, $phase] = $this->projectWithPhase();

        foreach ([
            ['promptFeedback' => ['blockReason' => 'SAFETY']],
            ['candidates' => [['finishReason' => 'SAFETY', 'content' => ['parts' => []]]]],
            ['candidates' => [['finishReason' => 'MAX_TOKENS', 'content' => ['parts' => [['text' => '{"accomplishments": "Roof']]]]]],
        ] as $body) {
            Http::fake(['generativelanguage.googleapis.com/*' => Http::response($body)]);

            try {
                app(ProgressReportAssistant::class)->draft($project, $phase, 'In Progress', 'natapos roofing');
                $this->fail('Expected the assistant to reject: '.json_encode($body));
            } catch (RuntimeException $e) {
                $this->assertNotSame('', $e->getMessage());
            }
        }
    }

    public function test_invalid_api_key_error_does_not_leak_details_to_the_user(): void
    {
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response(['error' => ['code' => 400, 'message' => 'API key not valid.']], 400)]);
        [$project, $phase] = $this->projectWithPhase();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The AI assistant could not draft this update. Please write it yourself.');

        app(ProgressReportAssistant::class)->draft($project, $phase, 'In Progress', 'natapos roofing');
    }

    public function test_assist_endpoint_works_end_to_end_with_gemini(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response($this->candidate([
                'accomplishments' => 'Excavation completed.',
                'activities_completed' => 'Excavation',
                'activities_remaining' => '',
                'issues' => 'Rain stopped work for one afternoon.',
            ])),
        ]);

        [$project, $phase] = $this->projectWithPhase();
        $personnel = User::factory()->create(['role' => 'project_personnel', 'position_type' => 'Foreman']);
        $project->assignPersonnel($personnel, 'Foreman', 'Site monitoring');

        $this->actingAs($personnel)
            ->postJson(route('projects.progress.assist', $project), [
                'phase_id' => $phase->id,
                'phase_status' => 'Completed',
                'notes' => 'tapos excavation, umulan hapon',
            ])
            ->assertOk()
            ->assertJsonPath('draft.accomplishments', 'Excavation completed.')
            ->assertJsonPath('draft.issues', 'Rain stopped work for one afternoon.');
    }

    /**
     * @param  array<string, string>  $draft
     * @return array<string, mixed>
     */
    private function candidate(array $draft): array
    {
        return [
            'candidates' => [[
                'content' => ['role' => 'model', 'parts' => [['text' => json_encode($draft)]]],
                'finishReason' => 'STOP',
            ]],
        ];
    }

    /**
     * @return array{0: Project, 1: ProjectPhase}
     */
    private function projectWithPhase(): array
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $project = Project::factory()->create(['created_by' => $admin->id]);
        $phase = ProjectPhase::query()->create([
            'project_id' => $project->id,
            'name' => Project::DEFAULT_PHASES[0],
            'sequence' => 1,
            'status' => 'In Progress',
        ]);

        return [$project, $phase];
    }
}
