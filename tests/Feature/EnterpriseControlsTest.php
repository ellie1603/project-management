<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\BudgetRequest;
use App\Models\Project;
use App\Models\ProjectExpense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnterpriseControlsTest extends TestCase
{
    use RefreshDatabase;

    private User $finance;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finance = User::factory()->create(['role' => 'finance_accounting']);
        $this->project = Project::factory()->create(['approved_budget' => 100000]);
    }

    public function test_finance_reverses_a_wrong_expense_instead_of_deleting_it(): void
    {
        $expense = $this->expense(25000, 'OR-1001');

        $this->actingAs($this->finance)
            ->post(route('expenses.reverse', $expense), ['reason' => 'Encoded the wrong amount; should be 2,500'])
            ->assertRedirect();

        $reversal = ProjectExpense::where('reverses_expense_id', $expense->id)->sole();
        $this->assertSame('-25000.00', $reversal->amount);
        $this->assertSame('REV-OR-1001', $reversal->reference_number);
        $this->assertStringContainsString('wrong amount', $reversal->remarks);
        $this->assertSame($this->finance->id, $reversal->created_by);

        // The original stays; together they net to zero.
        $this->assertDatabaseHas('project_expenses', ['id' => $expense->id, 'amount' => '25000.00']);
        $this->assertTrue($expense->fresh()->isReversed());
        $this->assertSame('0.00', $this->project->budgetSummary()['total_expenses']);
        $this->assertTrue(AuditLog::where('module', 'project_expenses')->where('action', 'reversed')->where('record_id', $expense->id)->exists());
    }

    public function test_an_expense_can_be_reversed_only_once_and_reversals_cannot_be_reversed(): void
    {
        $expense = $this->expense(1000);

        $this->actingAs($this->finance)->post(route('expenses.reverse', $expense), ['reason' => 'Duplicate'])->assertRedirect();
        $this->actingAs($this->finance)->post(route('expenses.reverse', $expense), ['reason' => 'Again'])->assertStatus(422);

        $reversal = ProjectExpense::where('reverses_expense_id', $expense->id)->sole();
        $this->actingAs($this->finance)->post(route('expenses.reverse', $reversal), ['reason' => 'Undo'])->assertStatus(422);

        $this->assertSame(2, ProjectExpense::count());
    }

    public function test_a_reason_is_required_and_only_expense_recorders_can_reverse(): void
    {
        $expense = $this->expense(1000);

        $this->actingAs($this->finance)->post(route('expenses.reverse', $expense), ['reason' => ''])->assertSessionHasErrors('reason');

        $ceo = User::factory()->create(['role' => 'admin']);
        $this->actingAs($ceo)->post(route('expenses.reverse', $expense), ['reason' => 'x'])->assertForbidden();

        $this->assertSame(1, ProjectExpense::count());
    }

    public function test_expense_list_shows_reversed_and_reversal_entries(): void
    {
        $expense = $this->expense(4000, 'OR-77');
        $this->actingAs($this->finance)->post(route('expenses.reverse', $expense), ['reason' => 'Wrong project']);

        $this->actingAs($this->finance)->get(route('budget.expenses'))
            ->assertOk()
            ->assertSee('Reversed')
            ->assertSee('-₱4,000.00')
            ->assertSee('REV-OR-77');
    }

    public function test_the_ceo_sees_whether_a_request_fits_the_remaining_budget(): void
    {
        $ceo = User::factory()->create(['role' => 'admin']);
        $contractor = User::factory()->create(['role' => 'project_personnel', 'position_type' => 'Contractor']);
        $this->expense(90000);
        BudgetRequest::create(['project_id' => $this->project->id, 'requested_by' => $contractor->id, 'amount' => 15000, 'purpose' => '60% billing', 'status' => 'Pending']);

        $this->actingAs($ceo)->get(route('budget.requests'))
            ->assertOk()
            ->assertSee('Over budget by ₱5,000.00');

        $this->actingAs($ceo)->get(route('projects.show', $this->project))
            ->assertOk()
            ->assertSee('Over budget by ₱5,000.00');
    }

    public function test_a_deactivated_user_is_signed_out_on_their_next_request(): void
    {
        $this->actingAs($this->finance)->get('/dashboard')->assertOk();

        $this->finance->forceFill(['is_active' => false])->save();

        $this->get('/dashboard')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    private function expense(float $amount, ?string $reference = null): ProjectExpense
    {
        return $this->project->expenses()->create([
            'expense_date' => now()->subDay()->toDateString(),
            'amount' => $amount,
            'reference_number' => $reference,
            'created_by' => $this->finance->id,
        ]);
    }
}
