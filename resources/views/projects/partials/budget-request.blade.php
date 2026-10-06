@php
    $budget = $project->budgetSummary();
    $canRequest = $project->hasPersonnel(auth()->user());
@endphp

<section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
    <h2 class="text-lg font-semibold text-slate-900">Budget Request</h2>

    <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
            <p class="text-xs uppercase tracking-wide text-slate-500">Approved Project Budget</p>
            <p class="mt-2 text-lg font-semibold text-slate-900">₱{{ number_format((float) $budget['approved_budget'], 2) }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
            <p class="text-xs uppercase tracking-wide text-slate-500">Budget Utilized</p>
            <p class="mt-2 text-lg font-semibold text-slate-900">₱{{ number_format((float) $budget['total_expenses'], 2) }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
            <p class="text-xs uppercase tracking-wide text-slate-500">Remaining Budget</p>
            <p class="mt-2 text-lg font-semibold text-slate-900">₱{{ number_format((float) $budget['remaining_budget'], 2) }}</p>
        </div>
    </div>
</section>

@if ($canRequest)
    <section class="mt-6 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <h3 class="text-sm font-semibold text-slate-900">Request Budget</h3>
        <form method="POST" action="{{ route('projects.budget-requests.store', $project) }}" enctype="multipart/form-data" class="mt-4 grid gap-3 sm:grid-cols-2">
            @csrf
            <div>
                <label for="request-amount" class="mb-1 block text-sm font-medium text-slate-700">Request Amount</label>
                <input id="request-amount" type="number" step="0.01" min="0.01" name="amount" placeholder="0.00" class="w-full rounded-md border-slate-300 text-sm" required>
            </div>
            <div>
                <label for="request-purpose" class="mb-1 block text-sm font-medium text-slate-700">Purpose</label>
                <input id="request-purpose" name="purpose" placeholder="Construction work / project expense" class="w-full rounded-md border-slate-300 text-sm" required>
            </div>
            <div class="sm:col-span-2">
                <label for="request-description" class="mb-1 block text-sm font-medium text-slate-700">Description</label>
                <textarea id="request-description" name="description" rows="2" placeholder="Description of requested funding" class="w-full rounded-md border-slate-300 text-sm"></textarea>
            </div>
            <div class="sm:col-span-2">
                <label for="request-document" class="mb-1 block text-sm font-medium text-slate-700">Supporting Document</label>
                <input id="request-document" type="file" name="document" accept=".{{ str_replace(',', ',.', \App\Http\Controllers\ProjectController::DOCUMENT_FILE_TYPES) }}" class="w-full text-sm">
            </div>
            <button class="rounded-md bg-brand-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition-all duration-150 hover:bg-brand-700 sm:col-span-2">Submit Request</button>
        </form>
    </section>
@endif

<section class="mt-6 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
    <h3 class="text-sm font-semibold text-slate-900">My Budget Requests</h3>
    <div x-data="pager(10)" data-pager>
    <div class="mt-3 overflow-x-auto">
        <table class="min-w-full text-left text-sm">
            <thead class="border-b border-slate-200 text-xs uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-2 py-2">Date</th>
                    <th class="px-2 py-2">Purpose</th>
                    <th class="px-2 py-2">Amount</th>
                    <th class="px-2 py-2">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($project->budgetRequests->sortByDesc('created_at') as $budgetRequest)
                    <tr data-page-item>
                        <td class="px-2 py-2 whitespace-nowrap">{{ $budgetRequest->created_at->format('M d, Y') }}</td>
                        <td class="px-2 py-2">
                            {{ $budgetRequest->purpose }}
                            @if ($budgetRequest->status === 'Rejected' && $budgetRequest->remarks)
                                <p class="mt-0.5 text-xs text-red-600">Reason: {{ $budgetRequest->remarks }}</p>
                            @endif
                        </td>
                        <td class="px-2 py-2">₱{{ number_format((float) $budgetRequest->amount, 2) }}</td>
                        <td class="px-2 py-2"><x-status-badge :status="$budgetRequest->status" /></td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-2 py-5 text-center text-slate-500">No budget requests submitted yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <x-pager-controls class="!px-0" />
    </div>
</section>
