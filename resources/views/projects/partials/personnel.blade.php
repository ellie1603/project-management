<section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
    <div class="flex items-center justify-between gap-4">
        <h2 class="text-lg font-semibold text-slate-900">Personnel</h2>
        <span class="text-sm text-slate-500">{{ $project->assignments->count() }} assigned</span>
    </div>

    <ul class="mt-4 divide-y divide-slate-100">
        @forelse ($project->assignments as $assignment)
            <li class="flex justify-between gap-4 py-3 text-sm">
                <span class="font-medium text-slate-800">{{ $assignment->user->name }}</span>
                <span class="text-slate-500">{{ $assignment->position_type }} · {{ $assignment->responsibility }}</span>
            </li>
        @empty
            <li class="py-3 text-sm text-slate-500">No personnel assigned.</li>
        @endforelse
    </ul>

    @if (auth()->user()->isAdmin())
        <form method="POST" action="{{ route('projects.assign', $project) }}" class="mt-4 grid gap-3 border-t border-slate-100 pt-4 sm:grid-cols-3">
            @csrf
            <select name="user_id" aria-label="Select personnel" class="rounded-md border-slate-300 text-sm" required>
                <option value="">Select personnel</option>
                @foreach ($projectPersonnel as $person)
                    <option value="{{ $person->id }}">{{ $person->name }}</option>
                @endforeach
            </select>
            <select name="position_type" aria-label="Position type" class="rounded-md border-slate-300 text-sm" required>
                <option value="">Position</option>
                @foreach (\App\Models\ProjectAssignment::POSITION_TYPES as $position)
                    <option>{{ $position }}</option>
                @endforeach
            </select>
            <input name="responsibility" placeholder="Responsibility" aria-label="Responsibility" class="rounded-md border-slate-300 text-sm" required>
            <button class="rounded-md bg-brand-600 px-3 py-2 text-sm font-medium text-white sm:col-span-3">Assign Personnel</button>
        </form>
    @endif
</section>
