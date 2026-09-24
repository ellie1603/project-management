<div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Date</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Project</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Category</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Description</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Amount</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Recorded By</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($expenses as $expense)
                    <tr class="transition-colors duration-150 hover:bg-slate-50">
                        <td class="px-4 py-3 text-sm whitespace-nowrap text-slate-500">{{ $expense->expense_date }}</td>
                        <td class="px-4 py-3 text-sm">
                            @if ($expense->project)
                                <a href="{{ route('projects.show', $expense->project) }}" class="font-medium text-slate-900 transition-colors duration-150 hover:text-brand-600">{{ $expense->project->title }}</a>
                            @else
                                <span class="text-slate-400">Deleted project</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-slate-600">{{ $expense->category }}</td>
                        <td class="px-4 py-3 text-sm text-slate-600">{{ $expense->description }}</td>
                        <td class="px-4 py-3 text-sm font-medium text-slate-900">₱{{ number_format((float) $expense->amount, 2) }}</td>
                        <td class="px-4 py-3 text-sm text-slate-600">{{ $expense->creator?->name ?? 'N/A' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-12 text-center text-sm text-slate-500">No expenses match the selected filters.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="ajax-pagination mt-4" data-turbo="false">
    {{ $expenses->links() }}
</div>
