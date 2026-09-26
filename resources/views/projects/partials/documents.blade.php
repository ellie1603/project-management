@php
    $canWriteProgress = auth()->user()->isProjectPersonnel() && $project->hasPersonnel(auth()->user());
@endphp

<section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
    <div class="flex items-center justify-between gap-4">
        <h2 class="text-lg font-semibold text-slate-900">Documents</h2>
        <span class="text-sm text-slate-500">{{ $project->documents->count() }} files</span>
    </div>

    <ul class="mt-4 divide-y divide-slate-100">
        @forelse ($project->documents as $document)
            <li class="flex items-center justify-between gap-4 py-3 text-sm">
                <div>
                    <p class="font-medium text-slate-800">
                        {{ $document->document_type }}
                        <span class="text-xs text-slate-500">v{{ $document->version }}</span>
                        @if ($document->is_current)
                            <span class="text-xs text-emerald-600">(current)</span>
                        @endif
                    </p>
                    <p class="text-xs text-slate-500">{{ $document->document_name }} · uploaded by {{ $document->uploader?->name ?? 'N/A' }}</p>
                </div>
                <a href="{{ route('projects.documents.download', [$project, $document]) }}" data-turbo="false" class="font-medium text-slate-700 underline">Download</a>
            </li>
        @empty
            <li class="py-3 text-sm text-slate-500">No documents uploaded.</li>
        @endforelse
    </ul>

    @if (auth()->user()->isAdmin() || $canWriteProgress)
        <form method="POST" action="{{ route('projects.documents.store', $project) }}" enctype="multipart/form-data" class="mt-4 space-y-3 border-t border-slate-100 pt-4">
            @csrf
            <select name="document_type" aria-label="Document type" class="w-full rounded-md border-slate-300 text-sm" required>
                <option value="">Document type</option>
                @foreach (\App\Models\Project::DOCUMENT_TYPES as $type)
                    <option>{{ $type }}</option>
                @endforeach
            </select>
            <input type="file" name="file" aria-label="Document file" class="w-full text-sm" required>
            <input name="description" placeholder="Description" aria-label="Document description" class="w-full rounded-md border-slate-300 text-sm">
            <button class="rounded-md bg-brand-600 px-3 py-2 text-sm font-medium text-white">Upload Document</button>
        </form>
    @endif
</section>
