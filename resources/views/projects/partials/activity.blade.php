<section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm" x-data="pager(10)" data-pager>
    <h2 class="text-lg font-semibold text-slate-900">Activity Log</h2>
    <ul class="mt-4 divide-y divide-slate-100">
        @forelse ($activityLog as $entry)
            <li data-page-item class="py-3 text-sm">
                <p class="text-slate-800">{{ $entry->description }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ $entry->user?->name ?? 'System' }} · {{ $entry->created_at->format('M d, Y g:i A') }}</p>
            </li>
        @empty
            <li class="py-8 text-center text-sm text-slate-500">No activity recorded for this project yet.</li>
        @endforelse
    </ul>
    <x-pager-controls class="!px-0" />
</section>
