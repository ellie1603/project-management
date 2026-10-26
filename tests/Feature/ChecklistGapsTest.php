<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use App\Notifications\BudgetExceededNotification;
use App\Notifications\ProjectProgressUpdatedNotification;
use App\Services\NotificationPreferences;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ChecklistGapsTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_turn_off_a_notification_type_and_stops_receiving_it(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $project = Project::factory()->create();
        $progress = $project->progress()->create(['user_id' => $admin->id, 'progress_percentage' => 10, 'progress_date' => now()->toDateString(), 'accomplishments' => 'Work']);

        $this->actingAs($admin)->get(route('profile.edit'))->assertOk()->assertSee('Project progress updated');

        $muted = array_values(array_diff(array_keys(NotificationPreferences::forUser($admin)), ['progress_updated']));
        $this->actingAs($admin)
            ->patch(route('profile.notifications'), ['enabled' => $muted])
            ->assertRedirect(route('profile.edit'));

        $admin->refresh();
        $admin->notify(new ProjectProgressUpdatedNotification($project, $progress));
        $this->assertSame(0, $admin->notifications()->count());

        // Turning it back on resumes delivery.
        $this->actingAs($admin)->patch(route('profile.notifications'), ['enabled' => array_keys(NotificationPreferences::forUser($admin))]);
        $admin->refresh()->notify(new ProjectProgressUpdatedNotification($project, $progress));
        $this->assertSame(1, $admin->notifications()->count());
    }

    public function test_required_notifications_cannot_be_turned_off(): void
    {
        $finance = User::factory()->create(['role' => 'finance_accounting']);
        $project = Project::factory()->create();

        $this->actingAs($finance)->patch(route('profile.notifications'), ['enabled' => []]);

        $finance->refresh()->notify(new BudgetExceededNotification($project, 120));
        $this->assertSame(1, $finance->notifications()->count());
    }

    public function test_only_one_of_two_simultaneous_decisions_on_a_budget_request_wins(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $contractor = User::factory()->create(['role' => 'project_personnel', 'position_type' => 'Contractor']);
        $project = Project::factory()->create();
        $project->assignPersonnel($contractor, 'Contractor', 'Builder');
        $budgetRequest = $project->budgetRequests()->create(['requested_by' => $contractor->id, 'amount' => 1000, 'purpose' => 'Billing', 'status' => 'Pending']);

        $this->actingAs($admin)->patch(route('projects.budget-requests.approve', [$project, $budgetRequest]))->assertRedirect();
        // The second decision arrives with the old page still open.
        $this->actingAs($admin)->patch(route('projects.budget-requests.reject', [$project, $budgetRequest]), ['remarks' => 'No'])->assertStatus(422);

        $this->assertSame('Approved', $budgetRequest->fresh()->status);
    }

    public function test_friendly_error_pages_are_shown(): void
    {
        Route::middleware('web')->get('/_test/server-error', fn () => abort(500));
        Route::middleware('web')->get('/_test/expired', fn () => abort(419));
        Route::middleware('web')->get('/_test/maintenance', fn () => abort(503));

        $this->get('/no-such-page')->assertNotFound()->assertSee('Page not found');
        $this->get('/_test/server-error')->assertStatus(500)->assertSee('Something went wrong');
        $this->get('/_test/expired')->assertStatus(419)->assertSee('Your session expired');
        $this->get('/_test/maintenance')->assertStatus(503)->assertSee('Down for maintenance');
    }

    public function test_pdf_reports_end_with_signature_lines(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'name' => 'Ana Admin']);
        Project::factory()->create();

        $this->actingAs($admin)->get(route('reports.budget'))
            ->assertOk()
            ->assertSeeInOrder(['Prepared by', 'Ana Admin', 'Noted by', 'Approved by']);
    }
}
