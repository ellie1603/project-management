<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Project;
use App\Models\ProjectExpense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseBatchTest extends TestCase
{
    use RefreshDatabase;

    private User $finance;

    private Project $road;

    private Project $office;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finance = User::factory()->create(['role' => 'finance_accounting']);
        $this->road = Project::factory()->create(['project_code' => 'BMPC-PRJ-2026-0101', 'title' => 'Farm Road', 'approved_budget' => 100000]);
        $this->office = Project::factory()->create(['project_code' => 'BMPC-PRJ-2026-0102', 'title' => 'Branch Office', 'approved_budget' => 50000]);
    }

    public function test_finance_adds_expenses_in_a_dialog_on_the_expenses_page(): void
    {
        $this->actingAs($this->finance)->get(route('budget.expenses'))
            ->assertOk()
            ->assertSee("\$dispatch('open-modal', 'add-expenses')", false)
            ->assertSee('BMPC-PRJ-2026-0101')
            ->assertSee('Branch Office')
            ->assertSee('Total Amount');
    }

    public function test_the_old_add_expenses_address_opens_the_dialog(): void
    {
        $this->actingAs($this->finance)->get(route('budget.expenses.batch', ['project' => $this->office->id]))
            ->assertRedirect(route('budget.expenses', ['add' => 1, 'project' => $this->office->id]));
    }

    public function test_the_dialog_is_also_on_a_project_budget_tab_with_that_project_preselected(): void
    {
        $this->actingAs($this->finance)->get(route('projects.show', $this->office))
            ->assertOk()
            ->assertSee('Add several at once')
            ->assertSee('\u0022defaultProjectId\u0022:'.$this->office->id, false);
    }

    public function test_finance_saves_several_expenses_with_just_amount_date_and_reference(): void
    {
        $response = $this->actingAs($this->finance)->postJson(route('budget.expenses.batch.store'), ['rows' => [
            $this->row(['project_id' => $this->road->id, 'amount' => 25000, 'reference_number' => 'OR-1001']),
            $this->row(['project_id' => $this->office->id, 'amount' => '12000.50']),
        ]]);

        $response->assertOk()->assertJson(['redirect' => route('budget.expenses')]);
        $this->assertSame('Recorded 2 expenses totalling ₱37,000.50.', session('status'));

        $this->assertSame(2, ProjectExpense::count());
        $this->assertDatabaseHas('project_expenses', [
            'project_id' => $this->road->id,
            'amount' => '25000.00',
            'reference_number' => 'OR-1001',
            'category' => null,
            'description' => null,
            'created_by' => $this->finance->id,
        ]);
        $this->assertDatabaseHas('project_expenses', [
            'project_id' => $this->office->id,
            'amount' => '12000.50',
            'reference_number' => null,
        ]);
        $this->assertSame(2, AuditLog::where('module', 'project_expenses')->where('action', 'created')->count());
        $this->assertSame('75000.00', $this->road->budgetSummary()['remaining_budget']);
    }

    public function test_problems_are_reported_per_row_and_nothing_is_saved(): void
    {
        $this->road->expenses()->create([
            'expense_date' => now()->subDays(3)->toDateString(),
            'amount' => 1000,
            'reference_number' => 'OR-0900',
            'created_by' => $this->finance->id,
        ]);

        $this->actingAs($this->finance)->postJson(route('budget.expenses.batch.store'), ['rows' => [
            $this->row(['reference_number' => 'OR-2000']),
            $this->row(['amount' => '']),
            $this->row(['amount' => -50]),
            $this->row(['expense_date' => now()->addDays(2)->toDateString()]),
            $this->row(['reference_number' => 'or-0900']),
            $this->row(['reference_number' => 'OR-2000']),
            $this->row(['project_id' => 999999]),
        ]])
            ->assertUnprocessable()
            ->assertJsonMissingValidationErrors(['rows.0.amount', 'rows.0.reference_number'])
            ->assertJsonValidationErrors([
                'rows.1.amount' => 'The amount field is required.',
                'rows.2.amount' => 'The amount field must be greater than 0.',
                'rows.3.expense_date' => 'The date cannot be in the future.',
                'rows.4.reference_number' => 'or-0900 is already recorded for this project.',
                'rows.5.reference_number' => 'OR-2000 is used twice in this batch.',
                'rows.6.project_id' => 'The selected project is invalid.',
            ]);

        $this->assertSame(1, ProjectExpense::count());
    }

    public function test_a_reference_person_can_repeat_but_a_reference_number_cannot(): void
    {
        $this->road->expenses()->create([
            'expense_date' => now()->subDays(3)->toDateString(),
            'amount' => 1000,
            'reference_number' => 'Juan Dela Cruz',
            'created_by' => $this->finance->id,
        ]);

        // The same person on several expenses is normal.
        $this->actingAs($this->finance)->postJson(route('budget.expenses.batch.store'), ['rows' => [
            $this->row(['reference_number' => 'Juan Dela Cruz']),
            $this->row(['reference_number' => 'juan dela cruz']),
        ]])->assertOk();

        // A receipt or voucher number is still recorded only once.
        $this->actingAs($this->finance)->postJson(route('budget.expenses.batch.store'), ['rows' => [
            $this->row(['reference_number' => 'CV-2026-014']),
            $this->row(['reference_number' => 'CV-2026-014']),
        ]])->assertJsonValidationErrors(['rows.1.reference_number' => 'CV-2026-014 is used twice in this batch.']);

        $this->assertSame(3, ProjectExpense::count());
    }

    public function test_the_entry_form_says_a_reference_can_be_a_number_or_a_person(): void
    {
        $this->actingAs($this->finance)->get(route('budget.expenses'))
            ->assertOk()
            ->assertSee('Reference No. or Person')
            ->assertSee('OR / voucher no. or name');
    }

    public function test_the_single_expense_form_needs_only_amount_and_date(): void
    {
        $this->actingAs($this->finance)->post(route('projects.expenses.store', $this->road), [
            'expense_date' => now()->toDateString(),
            'amount' => 1500,
        ])->assertRedirect(route('projects.show', $this->road));

        $this->assertDatabaseHas('project_expenses', [
            'project_id' => $this->road->id,
            'amount' => '1500.00',
            'reference_number' => null,
        ]);
    }

    public function test_project_personnel_cannot_use_expense_entry(): void
    {
        $personnel = User::factory()->create(['role' => 'project_personnel', 'position_type' => 'Foreman']);

        $this->actingAs($personnel)->get(route('budget.expenses.batch'))->assertForbidden();
        $this->actingAs($personnel)->postJson(route('budget.expenses.batch.store'), ['rows' => [$this->row()]])->assertForbidden();
        $this->assertSame(0, ProjectExpense::count());
    }

    public function test_finance_sees_the_entry_button_and_the_ceo_does_not(): void
    {
        $this->actingAs($this->finance)->get(route('budget.expenses'))
            ->assertOk()
            ->assertSee('Add Expenses');

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get(route('budget.expenses'))
            ->assertOk()
            ->assertDontSee('Add Expenses');
    }

    public function test_admin_sees_the_recorded_expenses_on_a_project(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->road->expenses()->create([
            'expense_date' => now()->toDateString(),
            'amount' => 3000,
            'reference_number' => 'CV-0042',
            'created_by' => $this->finance->id,
        ]);

        $this->actingAs($admin)->get(route('projects.show', $this->road))
            ->assertOk()
            ->assertSee('CV-0042')
            ->assertDontSee(route('projects.expenses.store', $this->road));
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function row(array $overrides = []): array
    {
        return [
            'project_id' => $this->road->id,
            'expense_date' => now()->subDay()->toDateString(),
            'amount' => 500,
            'reference_number' => null,
            ...$overrides,
        ];
    }
}
