@php
    $budget = $project->budgetSummary();
    $canReview = auth()->user()->isAdmin() || auth()->user()->isFinance();
@endphp

<div class="mb-6 flex flex-wrap items-end justify-between gap-4">
    <div>
        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Budget Monitoring</p>
        <h2 class="mt-1 text-xl font-semibold text-slate-900">{{ $project->title }}</h2>
    </div>
    <x-status-badge :status="$budget['budget_status']" />
</div>

<div class="grid gap-4 md:grid-cols-5">
    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <p class="text-xs uppercase tracking-wide text-slate-500">Approved Budget</p>
        <p class="mt-2 text-xl font-semibold text-slate-900">₱{{ number_format((float) $budget['approved_budget'], 2) }}</p>
    </div>
    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <p class="text-xs uppercase tracking-wide text-slate-500">Pending Requests</p>
        <p class="mt-2 text-xl font-semibold text-amber-600">₱{{ number_format((float) $budget['pending_requests'], 2) }}</p>
    </div>
    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <p class="text-xs uppercase tracking-wide text-slate-500">Approved Requests</p>
        <p class="mt-2 text-xl font-semibold text-slate-900">₱{{ number_format((float) $budget['approved_requests'], 2) }}</p>
    </div>
    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <p class="text-xs uppercase tracking-wide text-slate-500">Utilized</p>
        <p class="mt-2 text-xl font-semibold text-slate-900">₱{{ number_format((float) $budget['total_expenses'], 2) }}</p>
    </div>
    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <p class="text-xs uppercase tracking-wide text-slate-500">Remaining Budget</p>
        <p class="mt-2 text-xl font-semibold text-slate-900">₱{{ number_format((float) $budget['remaining_budget'], 2) }}</p>
    </div>
</div>

<div class="mt-6 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
    <p class="text-sm text-slate-500">Utilization</p>
    <p class="mt-1 text-2xl font-semibold text-slate-900">{{ number_format((float) $budget['budget_utilization_percent'], 2) }}%</p>
    <x-progress-bar :percent="$budget['budget_utilization_percent']" class="mt-3 h-2" :tone="match(true) {
        $budget['budget_utilization_percent'] >= 100 => 'red',
        $budget['budget_utilization_percent'] >= 80 => 'amber',
        default => 'slate',
    }" />
</div>

<section class="mt-6 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
    <h3 class="text-sm font-semibold text-slate-900">Budget Requests</h3>
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
                    <tr>
                        <td class="px-2 py-2 whitespace-nowrap">{{ $budgetRequest->created_at->format('M d, Y') }}</td>
                        <td class="px-2 py-2">{{ $budgetRequest->requester?->name ?? 'N/A' }}</td>
                        <td class="px-2 py-2">
                            {{ $budgetRequest->purpose }}
                            @if ($budgetRequest->status === 'Rejected' && $budgetRequest->remarks)
                                <p class="mt-0.5 text-xs text-red-600">{{ $budgetRequest->remarks }}</p>
                            @endif
                        </td>
                        <td class="px-2 py-2">₱{{ number_format((float) $budgetRequest->amount, 2) }}</td>
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
</section>

@if (auth()->user()->isFinance())
    <section class="mt-6 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <h3 class="text-sm font-semibold text-slate-900">Recorded Expenses</h3>
        <div class="mt-3 overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="border-b border-slate-200 text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-2 py-2">Date</th>
                        <th class="px-2 py-2">Category</th>
                        <th class="px-2 py-2">Amount</th>
                        <th class="px-2 py-2">Payee</th>
                        <th class="px-2 py-2">Recorded By</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($project->expenses->sortByDesc('expense_date') as $expense)
                        <tr>
                            <td class="px-2 py-2">{{ $expense->expense_date }}</td>
                            <td class="px-2 py-2">{{ $expense->category }}</td>
                            <td class="px-2 py-2">₱{{ number_format((float) $expense->amount, 2) }}</td>
                            <td class="px-2 py-2">{{ $expense->payee ?? 'N/A' }}</td>
                            <td class="px-2 py-2">{{ $expense->creator?->name ?? 'N/A' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-2 py-5 text-center text-slate-500">No expenses recorded yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <form method="POST" action="{{ route('projects.expenses.store', $project) }}" class="mt-4 grid gap-3 border-t border-slate-100 pt-4 sm:grid-cols-2">
            @csrf
            <input type="date" name="expense_date" value="{{ now()->toDateString() }}" aria-label="Expense date" class="rounded-md border-slate-300 text-sm" required>
            <input name="category" placeholder="Category" aria-label="Expense category" class="rounded-md border-slate-300 text-sm" required>
            <input type="number" step="0.01" name="amount" placeholder="Amount" aria-label="Expense amount" class="rounded-md border-slate-300 text-sm" required>
            <input name="reference_number" placeholder="Reference number" aria-label="Reference number" class="rounded-md border-slate-300 text-sm">
            <input name="payee" placeholder="Payee / Supplier" aria-label="Payee or supplier" class="rounded-md border-slate-300 text-sm">
            <input name="document_path" placeholder="Supporting document path" aria-label="Supporting document path" class="rounded-md border-slate-300 text-sm">
            <textarea name="description" placeholder="Description" aria-label="Expense description" class="rounded-md border-slate-300 text-sm sm:col-span-2" required></textarea>
            <button class="rounded-md bg-slate-900 px-3 py-2 text-sm font-medium text-white sm:col-span-2">Record Expense</button>
        </form>
    </section>
@endif
