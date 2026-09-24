@php
    $hasContractorHistory = $project->contractors->isNotEmpty();
    $quotationCount = $project->quotations->count();
@endphp

<div class="mb-6">
    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Contractors &amp; Providers</p>
    <h2 class="mt-1 text-xl font-semibold text-slate-900">{{ $project->title }}</h2>
</div>

@if (! $hasContractorHistory && $quotationCount < 3)
    <div class="mb-6 flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
        <i data-lucide="alert-triangle" class="mt-0.5 h-4 w-4 shrink-0"></i>
        <p>This project has no prior contractor history. At least three provider quotations are recommended before selecting a contractor ({{ $quotationCount }} of 3 recorded so far). The system does not automatically choose or rank providers &mdash; this is informational only.</p>
    </div>
@endif

<section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
    <h3 class="text-sm font-semibold text-slate-900">Attached Contractors</h3>
    <div class="mt-3 overflow-x-auto">
        <table class="min-w-full text-left text-sm">
            <thead class="border-b border-slate-200 text-xs uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-2 py-2">Contractor</th>
                    <th class="px-2 py-2">Role</th>
                    <th class="px-2 py-2">Contract Amount</th>
                    <th class="px-2 py-2">Start</th>
                    <th class="px-2 py-2">End</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($project->contractors as $contractor)
                    <tr>
                        <td class="px-2 py-2">{{ $contractor->name }}</td>
                        <td class="px-2 py-2">{{ $contractor->pivot->role }}</td>
                        <td class="px-2 py-2">{{ $contractor->pivot->contract_amount ? '₱'.number_format((float) $contractor->pivot->contract_amount, 2) : 'N/A' }}</td>
                        <td class="px-2 py-2">{{ $contractor->pivot->start_date ?? 'N/A' }}</td>
                        <td class="px-2 py-2">{{ $contractor->pivot->end_date ?? 'N/A' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-2 py-5 text-center text-slate-500">No contractors attached yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @can('manageContractors', $project)
        <form method="POST" action="{{ route('projects.contractors.store', $project) }}" class="mt-4 grid gap-3 border-t border-slate-100 pt-4 sm:grid-cols-2">
            @csrf
            <select name="contractor_id" aria-label="Select contractor" class="rounded-md border-slate-300 text-sm sm:col-span-2" required>
                <option value="">Select contractor</option>
                @foreach ($availableContractors as $contractor)
                    <option value="{{ $contractor->id }}">{{ $contractor->name }}</option>
                @endforeach
            </select>
            <input name="role" placeholder="Role (e.g. Main Contractor)" aria-label="Contractor role" class="rounded-md border-slate-300 text-sm" required>
            <input type="number" step="0.01" name="contract_amount" placeholder="Contract amount" aria-label="Contract amount" class="rounded-md border-slate-300 text-sm">
            <input type="date" name="start_date" aria-label="Contract start date" class="rounded-md border-slate-300 text-sm">
            <input type="date" name="end_date" aria-label="Contract end date" class="rounded-md border-slate-300 text-sm">
            <textarea name="remarks" placeholder="Remarks" aria-label="Remarks" class="rounded-md border-slate-300 text-sm sm:col-span-2"></textarea>
            <button class="rounded-md bg-slate-900 px-3 py-2 text-sm font-medium text-white sm:col-span-2">Attach Contractor</button>
        </form>
    @endcan
</section>

<section class="mt-6 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
    <h3 class="text-sm font-semibold text-slate-900">Provider Quotations</h3>
    <div class="mt-3 overflow-x-auto">
        <table class="min-w-full text-left text-sm">
            <thead class="border-b border-slate-200 text-xs uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-2 py-2">Provider</th>
                    <th class="px-2 py-2">Quotation Amount</th>
                    <th class="px-2 py-2">Quotation Date</th>
                    <th class="px-2 py-2">Remarks</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($project->quotations as $quotation)
                    <tr>
                        <td class="px-2 py-2">{{ $quotation->contractor->name }}</td>
                        <td class="px-2 py-2">₱{{ number_format((float) $quotation->quotation_amount, 2) }}</td>
                        <td class="px-2 py-2">{{ $quotation->quotation_date }}</td>
                        <td class="px-2 py-2">{{ $quotation->remarks ?? 'None' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-2 py-5 text-center text-slate-500">No quotations recorded.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @can('manageContractors', $project)
        <form method="POST" action="{{ route('projects.quotations.store', $project) }}" class="mt-4 grid gap-3 border-t border-slate-100 pt-4 sm:grid-cols-2">
            @csrf
            <select name="contractor_id" aria-label="Select provider" class="rounded-md border-slate-300 text-sm" required>
                <option value="">Select provider</option>
                @foreach ($availableContractors as $contractor)
                    <option value="{{ $contractor->id }}">{{ $contractor->name }}</option>
                @endforeach
            </select>
            <input type="number" step="0.01" name="quotation_amount" placeholder="Quotation amount" aria-label="Quotation amount" class="rounded-md border-slate-300 text-sm" required>
            <input type="date" name="quotation_date" value="{{ now()->toDateString() }}" aria-label="Quotation date" class="rounded-md border-slate-300 text-sm" required>
            <input name="document_path" placeholder="Quotation document path" aria-label="Quotation document path" class="rounded-md border-slate-300 text-sm">
            <textarea name="remarks" placeholder="Remarks" aria-label="Remarks" class="rounded-md border-slate-300 text-sm sm:col-span-2"></textarea>
            <button class="rounded-md bg-slate-900 px-3 py-2 text-sm font-medium text-white sm:col-span-2">Record Quotation</button>
        </form>
    @endcan
</section>
