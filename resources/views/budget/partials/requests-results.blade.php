@php $canReview = auth()->user()->isAdmin() || auth()->user()->isFinance(); @endphp

<div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Date</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Project</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Requested By</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Purpose</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Amount</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Status</th>
                    @if ($canReview)
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Action</th>
                    @endif
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($budgetRequests as $budgetRequest)
                    <tr class="transition-colors duration-150 hover:bg-slate-50">
                        <td class="px-4 py-3 text-sm whitespace-nowrap text-slate-500">{{ $budgetRequest->created_at->format('M d, Y') }}</td>
                        <td class="px-4 py-3 text-sm">
                            @if ($budgetRequest->project)
                                <a href="{{ route('projects.show', $budgetRequest->project) }}" class="font-medium text-slate-900 transition-colors duration-150 hover:text-brand-600">{{ $budgetRequest->project->title }}</a>
                                <p class="text-xs text-slate-400">{{ $budgetRequest->project->project_code }}</p>
                            @else
                                <span class="text-slate-400">Deleted project</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-slate-600">{{ $budgetRequest->requester?->name ?? 'N/A' }}</td>
                        <td class="px-4 py-3 text-sm text-slate-600">
                            {{ $budgetRequest->purpose }}
                            @if ($budgetRequest->status === 'Rejected' && $budgetRequest->remarks)
                                <p class="mt-0.5 text-xs text-red-600">{{ $budgetRequest->remarks }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm font-medium text-slate-900">₱{{ number_format((float) $budgetRequest->amount, 2) }}</td>
                        <td class="px-4 py-3 text-sm"><x-status-badge :status="$budgetRequest->status" /></td>
                        @if ($canReview)
                            <td class="px-4 py-3 text-sm">
                                @if ($budgetRequest->status === 'Pending' && $budgetRequest->project)
                                    @php $rejectModal = 'reject-budget-request-'.$budgetRequest->id; @endphp
                                    <div class="flex items-center gap-2">
                                        <form method="POST" action="{{ route('projects.budget-requests.approve', [$budgetRequest->project, $budgetRequest]) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button class="rounded-md border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700 hover:bg-emerald-100">Approve</button>
                                        </form>
                                        <button type="button" @click="$dispatch('open-modal', '{{ $rejectModal }}')" class="rounded-md border border-red-200 bg-red-50 px-2.5 py-1 text-xs font-medium text-red-700 hover:bg-red-100">Reject</button>
                                        <x-modal :name="$rejectModal" max-width="sm">
                                            <div class="p-5">
                                                <h4 class="text-sm font-semibold text-slate-900">Reject Budget Request</h4>
                                                <form method="POST" action="{{ route('projects.budget-requests.reject', [$budgetRequest->project, $budgetRequest]) }}" class="mt-3 space-y-3">
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
                    <tr>
                        <td colspan="{{ $canReview ? 7 : 6 }}" class="px-4 py-12 text-center text-sm text-slate-500">No budget requests match the selected filters.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="ajax-pagination mt-4" data-turbo="false">
    {{ $budgetRequests->links() }}
</div>
