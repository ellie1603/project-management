<?php

namespace Tests\Feature;

use App\Models\Contractor;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContractorsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_contractor_user_and_attach_it_to_a_project(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $project = Project::factory()->create([
            'project_code' => 'BMPC-PRJ-'.date('Y').'-0001',
            'created_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->post('/users', [
            'name' => 'Alex Rivera',
            'email' => 'alex.rivera@example.test',
            'password' => 'password123',
            'role' => 'project_personnel',
            'position_type' => 'Contractor',
            'contractor_name' => 'Northstar Builders',
            'contact_person' => 'Alex Rivera',
            'contact_number' => '09170000000',
            'contractor_email' => 'northstar@example.test',
            'address' => 'Cebu City',
            'registration_information' => 'SEC-2026-001',
            'contractor_status' => 'active',
        ]);

        $response->assertRedirect('/users');
        $this->assertDatabaseHas('users', ['email' => 'alex.rivera@example.test', 'position_type' => 'Contractor']);
        $this->assertDatabaseHas('contractors', ['name' => 'Northstar Builders']);

        $contractor = Contractor::query()->where('name', 'Northstar Builders')->firstOrFail();
        $this->assertSame($contractor->id, User::query()->where('email', 'alex.rivera@example.test')->value('contractor_id'));

        $this->actingAs($admin)
            ->post('/projects/'.$project->id.'/contractors', [
                'contractor_id' => $contractor->id,
                'role' => 'General Contractor',
                'contract_amount' => 250000,
                'start_date' => now()->toDateString(),
                'end_date' => now()->addMonths(3)->toDateString(),
                'remarks' => 'Primary provider',
            ])
            ->assertRedirect('/projects/'.$project->id);

        $this->assertDatabaseHas('project_contractors', [
            'project_id' => $project->id,
            'contractor_id' => $contractor->id,
            'role' => 'General Contractor',
        ]);
    }

    public function test_admin_can_record_multiple_quotations_without_replacing_history(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $project = Project::factory()->create([
            'project_code' => 'BMPC-PRJ-'.date('Y').'-0002',
            'created_by' => $admin->id,
        ]);
        $contractor = Contractor::factory()->create();

        foreach ([120000, 135000, 128000] as $amount) {
            $this->actingAs($admin)
                ->post('/projects/'.$project->id.'/quotations', [
                    'contractor_id' => $contractor->id,
                    'quotation_amount' => $amount,
                    'quotation_date' => now()->toDateString(),
                    'remarks' => 'Quotation for comparison',
                ])
                ->assertRedirect('/projects/'.$project->id);
        }

        $this->assertDatabaseCount('contractor_quotations', 3);
        $this->assertDatabaseMissing('contractor_quotations', ['selected' => true]);
    }

    public function test_unassigned_personnel_cannot_view_project_contractor_history(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $personnel = User::factory()->create(['role' => 'project_personnel', 'position_type' => 'Foreman']);
        $project = Project::factory()->create([
            'project_code' => 'BMPC-PRJ-'.date('Y').'-0003',
            'created_by' => $admin->id,
        ]);
        $contractor = Contractor::factory()->create();
        $project->contractors()->attach($contractor->id, ['role' => 'Provider']);

        $this->actingAs($personnel)
            ->get('/projects/'.$project->id.'/contractors')
            ->assertStatus(403);
    }
}
