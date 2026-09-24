@php
    $actionMeta = [
        'created' => ['icon' => 'plus', 'chip' => 'chip-ink'],
        'updated' => ['icon' => 'pencil', 'chip' => 'chip-ink'],
        'deleted' => ['icon' => 'trash-2', 'chip' => 'chip-ink'],
        'activated' => ['icon' => 'user-check', 'chip' => 'chip-ink'],
        'deactivated' => ['icon' => 'user-x', 'chip' => 'chip-ink'],
        'assigned' => ['icon' => 'user-plus', 'chip' => 'chip-ink'],
        'uploaded' => ['icon' => 'upload', 'chip' => 'chip-ink'],
    ];
@endphp

<section class="overflow-hidden rounded-3xl bg-white shadow-soft ring-1 ring-slate-900/5">
    <div class="overflow-x-auto">
        <table class="min-w-full">
            <thead>
                <tr class="border-b border-slate-100">
                    <th class="px-5 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Time</th>
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">User</th>
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Action</th>
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Module</th>
                    <th class="px-5 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Description</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($auditLogs as $auditLog)
                    @php $meta = $actionMeta[$auditLog->action] ?? ['icon' => 'activity', 'chip' => 'chip-ink']; @endphp
                    <tr class="transition-colors duration-150 hover:bg-slate-50/80">
                        <td class="whitespace-nowrap px-5 py-3.5 text-sm text-slate-500">{{ $auditLog->created_at->format('M d, Y h:i A') }}</td>
                        <td class="px-4 py-3.5">
                            <div class="flex items-center gap-2.5">
                                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-slate-100 text-[11px] font-semibold text-slate-600">
                                    {{ strtoupper(substr($auditLog->user?->name ?? 'S', 0, 1)) }}
                                </span>
                                <span class="text-sm font-medium text-slate-800">{{ $auditLog->user?->name ?? 'System' }}</span>
                            </div>
                        </td>
                        <td class="px-4 py-3.5">
                            <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold text-white {{ $meta['chip'] }}">
                                <i data-lucide="{{ $meta['icon'] }}" class="h-3 w-3"></i>
                                {{ ucfirst($auditLog->action) }}
                            </span>
                        </td>
                        <td class="px-4 py-3.5">
                            <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">{{ ucwords(str_replace('_', ' ', $auditLog->module)) }}</span>
                        </td>
                        <td class="px-5 py-3.5 text-sm text-slate-600">{{ $auditLog->description }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-5 py-16 text-center">
                            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                                <i data-lucide="clock" class="h-6 w-6"></i>
                            </div>
                            <p class="mt-4 text-sm font-semibold text-slate-700">No audit activity yet.</p>
                            <p class="mt-1 text-xs text-slate-500">Actions like project registration, assignments, and financial entries will appear here.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>

<div class="ajax-pagination mt-5" data-turbo="false">
    {{ $auditLogs->links() }}
</div>
