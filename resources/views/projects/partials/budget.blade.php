@php
    $budget = $project->budgetSummary();
    $canReview = auth()->user()->can('reviewBudgetRequest', $project);
    $seesRequests = auth()->user()->hasPermission('budget_requests.view');
    $canRecordExpense = auth()->user()->can('create', App\Models\ProjectExpense::class);
@endphp

<div class="mb-6 flex flex-wrap items-end justify-between gap-4">
    <div>
        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Budget Monitoring</p>
        <h2 class="mt-1 text-xl font-semibold text-slate-900">{{ $project->title }}</h2>
    </div>
    <x-status-badge :status="$budget['budget_status']" />
</div>

{{-- Cards wrap onto more rows as space runs out, so long peso amounts always fit. --}}
<div class="grid grid-cols-[repeat(auto-fit,minmax(min(100%,12rem),1fr))] gap-4">
    <div class="min-w-0 rounded-xl glass p-5">
        <p class="text-xs uppercase tracking-wide text-slate-500">Approved Budget</p>
        <p class="mt-2 text-lg font-semibold tabular-nums [overflow-wrap:anywhere] sm:text-xl text-slate-900">₱{{ number_format((float) $budget['approved_budget'], 2) }}</p>
    </div>
    @if ($seesRequests)
    <div class="min-w-0 rounded-xl glass p-5">
        <p class="text-xs uppercase tracking-wide text-slate-500">Pending Requests</p>
        <p class="mt-2 text-lg font-semibold tabular-nums [overflow-wrap:anywhere] sm:text-xl text-amber-600">₱{{ number_format((float) $budget['pending_requests'], 2) }}</p>
    </div>
    <div class="min-w-0 rounded-xl glass p-5">
        <p class="text-xs uppercase tracking-wide text-slate-500">Approved Requests</p>
        <p class="mt-2 text-lg font-semibold tabular-nums [overflow-wrap:anywhere] sm:text-xl text-slate-900">₱{{ number_format((float) $budget['approved_requests'], 2) }}</p>
    </div>
    @endif
    <div class="min-w-0 rounded-xl glass p-5">
        <p class="text-xs uppercase tracking-wide text-slate-500">Utilized</p>
        <p class="mt-2 text-lg font-semibold tabular-nums [overflow-wrap:anywhere] sm:text-xl text-slate-900">₱{{ number_format((float) $budget['total_expenses'], 2) }}</p>
    </div>
    <div class="min-w-0 rounded-xl glass p-5">
        <p class="text-xs uppercase tracking-wide text-slate-500">Remaining Budget</p>
        <p class="mt-2 text-lg font-semibold tabular-nums [overflow-wrap:anywhere] sm:text-xl text-slate-900">₱{{ number_format((float) $budget['remaining_budget'], 2) }}</p>
    </div>
</div>

<div class="mt-6 rounded-xl glass p-5">
    <p class="text-sm text-slate-500">Utilization</p>
    <p class="mt-1 text-2xl font-semibold text-slate-900">{{ number_format((float) $budget['budget_utilization_percent'], 2) }}%</p>
    <x-progress-bar :percent="$budget['budget_utilization_percent']" class="mt-3 h-2" :tone="match(true) {
        $budget['budget_utilization_percent'] >= 100 => 'red',
        $budget['budget_utilization_percent'] >= 80 => 'amber',
        default => 'slate',
    }" />
</div>

@if ($seesRequests)
<section class="mt-6 rounded-xl glass p-5">
    <h3 class="text-sm font-semibold text-slate-900">Budget Requests</h3>
    <div x-data="pager(10)" data-pager>
    <div class="mt-3 overflow-x-auto">
        <table class="min-w-full text-left text-sm">
            <thead class="border-b border-slate-200 text-xs uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-2 py-2">Date</th>
                    <th class="px-2 py-2">Requested By</th>
                    <th class="px-2 py-2">Purpose</th>
                    <th class="px-2 py-2">Amount</th>
                    <th class="px-2 py-2">Status</th>
                    @if ($canReview)
                        <th class="px-2 py-2">Action</th>
                    @endif
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($project->budgetRequests->sortByDesc('created_at') as $budgetRequest)
                    <tr data-page-item>
                        <td class="px-2 py-2 whitespace-nowrap">{{ $budgetRequest->created_at->format('M d, Y') }}</td>
                        <td class="px-2 py-2">{{ $budgetRequest->requester?->name ?? 'N/A' }}</td>
                        <td class="px-2 py-2">
                            {{ $budgetRequest->purpose }}
                            @if ($budgetRequest->status === 'Rejected' && $budgetRequest->remarks)
                                <p class="mt-0.5 text-xs text-red-600">{{ $budgetRequest->remarks }}</p>
                            @endif
                        </td>
                        <td class="px-2 py-2">
                            ₱{{ number_format((float) $budgetRequest->amount, 2) }}
                            @if ($canReview)
                                <x-budget-fit :request="$budgetRequest" :remaining="$budget['remaining_budget']" />
                            @endif
                        </td>
                        <td class="px-2 py-2"><x-status-badge :status="$budgetRequest->status" /></td>
                        @if ($canReview)
                            <td class="px-2 py-2">
                                @if ($budgetRequest->status === 'Pending')
                                    @php $rejectModal = 'reject-budget-request-'.$budgetRequest->id; @endphp
                                    <div class="flex items-center gap-2">
                                        <form method="POST" action="{{ route('projects.budget-requests.approve', [$project, $budgetRequest]) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button class="rounded-md border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700 hover:bg-emerald-100">Approve</button>
                                        </form>
                                        <button type="button" @click="$dispatch('open-modal', '{{ $rejectModal }}')" class="rounded-md border border-red-200 bg-red-50 px-2.5 py-1 text-xs font-medium text-red-700 hover:bg-red-100">Reject</button>
                                        <x-modal :name="$rejectModal" max-width="sm">
                                            <div class="p-5">
                                                <h4 class="text-sm font-semibold text-slate-900">Reject Budget Request</h4>
                                                <form method="POST" action="{{ route('projects.budget-requests.reject', [$project, $budgetRequest]) }}" class="mt-3 space-y-3">
                                                    @csrf
                                                    @method('PATCH')
                                                    <textarea name="remarks" rows="3" placeholder="Reason for rejection" class="w-full rounded-md border-slate-300 text-sm" required></textarea>
                                                    <div class="flex justify-end gap-2">
                                                        <button type="button" @click="$dispatch('close-modal', '{{ $rejectModal }}')" class="rounded-md border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-700">Cancel</button>
                                                        <button class="rounded-md bg-red-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-red-700">Reject Request</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </x-modal>
                                    </div>
                                @else
                                    <span class="text-xs text-slate-400">{{ $budgetRequest->reviewer?->name ?? '—' }}</span>
                                @endif
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr><td colspan="{{ $canReview ? 6 : 5 }}" class="px-2 py-5 text-center text-slate-500">No budget requests yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <x-pager-controls class="!px-0" />
    </div>
</section>
@endif

    <section class="mt-6 rounded-xl glass p-5">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h3 class="text-sm font-semibold text-slate-900">Recorded Expenses</h3>
            @if ($canRecordExpense)
                <button type="button" @click="$dispatch('open-modal', 'add-expenses')" class="text-xs font-medium text-slate-500 underline hover:text-slate-900">Add several at once</button>
            @endif
        </div>
        <div x-data="pager(10)" data-pager>
        <div class="mt-3 overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="border-b border-slate-200 text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-2 py-2">Date</th>
                        <th class="px-2 py-2">Amount</th>
                        <th class="px-2 py-2">Reference</th>
                        <th class="px-2 py-2">Recorded By</th>
                        <th class="px-2 py-2"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($project->expenses->sortByDesc('expense_date') as $expense)
                        <tr data-page-item>
                            <td class="px-2 py-2">{{ $expense->expense_date }}</td>
                            <td class="px-2 py-2"><x-expense-entry :expense="$expense" part="amount" /></td>
                            <td class="px-2 py-2"><x-expense-entry :expense="$expense" part="reference" /></td>
                            <td class="px-2 py-2">{{ $expense->creator?->name ?? 'N/A' }}</td>
                            <td class="px-2 py-2 text-right"><x-expense-entry :expense="$expense" part="action" /></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-2 py-5 text-center text-slate-500">No expenses recorded yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <x-pager-controls class="!px-0" />
        </div>

        @if ($canRecordExpense)
        <form method="POST" action="{{ route('projects.expenses.store', $project) }}" class="mt-4 grid gap-3 border-t border-slate-100 pt-4 sm:grid-cols-3">
            @csrf
            <input type="number" step="0.01" min="0" inputmode="decimal" name="amount" value="{{ old('amount') }}" placeholder="Total amount (₱)" aria-label="Total amount" class="rounded-md border-slate-300 text-sm" required>
            <input type="date" name="expense_date" value="{{ old('expense_date', now()->toDateString()) }}" max="{{ now()->toDateString() }}" aria-label="Expense date" class="rounded-md border-slate-300 text-sm" required>
            <input name="reference_number" value="{{ old('reference_number') }}" placeholder="Reference no. or person (optional)" aria-label="Reference number or person" class="rounded-md border-slate-300 text-sm">
            @if ($errors->hasAny(['amount', 'expense_date', 'reference_number']))
                <p class="text-xs text-red-600 sm:col-span-3">{{ $errors->first('amount') ?: ($errors->first('expense_date') ?: $errors->first('reference_number')) }}</p>
            @endif
            <button class="rounded-md bg-brand-600 px-3 py-2 text-sm font-medium text-white sm:col-span-3">Record Expense</button>
        </form>
        @endif
    </section>

<x-add-expenses-modal :project="$project" />
