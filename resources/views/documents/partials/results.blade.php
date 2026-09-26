@php
    $typeMeta = [
        'Approved Design' => ['icon' => 'drafting-compass', 'chip' => 'chip-ink'],
        'Project Plan' => ['icon' => 'clipboard-list', 'chip' => 'chip-ink'],
        'Contract' => ['icon' => 'file-signature', 'chip' => 'chip-ink'],
        'Quotation' => ['icon' => 'receipt', 'chip' => 'chip-ink'],
        'Accomplishment Report' => ['icon' => 'clipboard-check', 'chip' => 'chip-ink'],
        'Progress Report' => ['icon' => 'file-bar-chart', 'chip' => 'chip-ink'],
        'Progress Photo' => ['icon' => 'image', 'chip' => 'chip-ink'],
        'Other' => ['icon' => 'file', 'chip' => 'chip-ink'],
    ];
@endphp

<section class="overflow-hidden rounded-3xl bg-white shadow-soft ring-1 ring-slate-900/5">
    <div class="overflow-x-auto">
        <table class="min-w-full">
            <thead>
                <tr class="border-b border-slate-100">
                    <th class="px-5 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Document</th>
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Project</th>
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Version</th>
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Uploaded By</th>
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Date</th>
                    <th class="px-5 py-3 text-right text-[11px] font-semibold uppercase tracking-wider text-slate-400">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($documents as $document)
                    @php $meta = $typeMeta[$document->document_type] ?? $typeMeta['Other']; @endphp
                    <tr class="group transition-colors duration-150 hover:bg-slate-50/80">
                        <td class="px-5 py-3.5">
                            <div class="flex items-center gap-3">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl {{ $meta['chip'] }} text-white shadow-sm">
                                    <i data-lucide="{{ $meta['icon'] }}" class="h-4 w-4"></i>
                                </span>
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-slate-900">{{ $document->document_name }}</p>
                                    <div class="mt-0.5 flex items-center gap-1.5">
                                        <span class="text-xs text-slate-500">{{ $document->document_type }}</span>
                                        @if ($document->is_current)
                                            <span class="inline-flex items-center rounded-full bg-emerald-50 px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-emerald-700">Current</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3.5 text-sm text-slate-600">
                            @if ($document->project)
                                <a href="{{ route('projects.show', $document->project) }}" class="font-medium text-slate-700 transition-colors duration-150 hover:text-brand-600">{{ $document->project->title }}</a>
                            @else
                                <span class="text-slate-400">Deleted project</span>
                            @endif
                        </td>
                        <td class="px-4 py-3.5">
                            <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold tabular-nums text-slate-600">v{{ $document->version }}</span>
                        </td>
                        <td class="px-4 py-3.5 text-sm text-slate-600">{{ $document->uploader?->name ?? 'N/A' }}</td>
                        <td class="px-4 py-3.5 text-sm text-slate-500">{{ $document->created_at->format('M d, Y') }}</td>
                        <td class="px-5 py-3.5 text-right">
                            @if ($document->project)
                                <a href="{{ route('projects.documents.download', [$document->project, $document]) }}" data-turbo="false" class="inline-flex items-center gap-1.5 rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-sm transition-all duration-150 ease-elegant hover:-translate-y-px hover:bg-brand-600 hover:text-white">
                                    <i data-lucide="download" class="h-3.5 w-3.5"></i>
                                    Download
                                </a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-5 py-16 text-center">
                            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                                <i data-lucide="folder-search" class="h-6 w-6"></i>
                            </div>
                            <p class="mt-4 text-sm font-semibold text-slate-700">No documents match the selected filters.</p>
                            <p class="mt-1 text-xs text-slate-500">Try adjusting your search or document type filter.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>

<div class="ajax-pagination mt-5" data-turbo="false">
    {{ $documents->links() }}
</div>
