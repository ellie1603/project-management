@php
    $roleMeta = [
        'admin' => ['label' => 'Admin / CEO', 'chip' => 'chip-ink'],
        'project_personnel' => ['label' => 'Project Personnel', 'chip' => 'chip-ink'],
        'finance_accounting' => ['label' => 'Finance & Accounting', 'chip' => 'chip-ink'],
    ];
@endphp

<section class="overflow-hidden rounded-3xl bg-white shadow-soft ring-1 ring-slate-900/5">
    <div class="overflow-x-auto">
        <table class="min-w-full">
            <thead>
                <tr class="border-b border-slate-100">
                    <th class="px-5 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Name</th>
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Role</th>
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Position</th>
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Status</th>
                    <th class="px-5 py-3 text-right text-[11px] font-semibold uppercase tracking-wider text-slate-400">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($users as $user)
                    @php $meta = $roleMeta[$user->role] ?? $roleMeta['admin']; @endphp
                    <tr class="transition-colors duration-150 hover:bg-slate-50/80">
                        <td class="px-5 py-3.5">
                            <div class="flex items-center gap-3">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full {{ $meta['chip'] }} text-xs font-semibold text-white shadow-sm">
                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                </span>
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-slate-900">{{ $user->name }}</p>
                                    <p class="truncate text-xs text-slate-500">{{ $user->email }}</p>
                                    @if ($user->contractor)
                                        <a href="{{ route('contractors.show', $user->contractor) }}" class="mt-0.5 inline-flex items-center gap-1 text-xs font-medium text-brand-600 transition-colors duration-150 hover:text-brand-700">
                                            <i data-lucide="hard-hat" class="h-3 w-3"></i>
                                            {{ $user->contractor->name }}
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3.5">
                            <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">{!! $meta['label'] !!}</span>
                        </td>
                        <td class="px-4 py-3.5 text-sm text-slate-600">{{ $user->position_type ?? '—' }}</td>
                        <td class="px-4 py-3.5">
                            <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold {{ $user->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700' }}">
                                <span class="h-1.5 w-1.5 rounded-full bg-current opacity-70"></span>
                                {{ $user->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="px-5 py-3.5 text-right">
                            <div class="flex items-center justify-end gap-3">
                                <button type="button" @click="$dispatch('open-modal', 'edit-user-{{ $user->id }}')" class="inline-flex items-center gap-1 text-sm font-medium text-slate-500 transition-colors duration-150 hover:text-brand-600">
                                    <i data-lucide="pencil" class="h-3.5 w-3.5"></i>
                                    Edit
                                </button>
                                @if ($user->id !== auth()->id())
                                    <form method="POST" action="{{ route('users.toggle-status', $user) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="inline-flex items-center gap-1 text-sm font-medium text-slate-500 transition-colors duration-150 {{ $user->is_active ? 'hover:text-red-600' : 'hover:text-emerald-600' }}">
                                            <i data-lucide="{{ $user->is_active ? 'user-x' : 'user-check' }}" class="h-3.5 w-3.5"></i>
                                            {{ $user->is_active ? 'Deactivate' : 'Activate' }}
                                        </button>
                                    </form>
                                    <button type="button" @click="$dispatch('open-modal', 'delete-user-{{ $user->id }}')" class="inline-flex items-center gap-1 text-sm font-medium text-slate-500 transition-colors duration-150 hover:text-red-600">
                                        <i data-lucide="trash-2" class="h-3.5 w-3.5"></i>
                                        Delete
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-5 py-16 text-center">
                            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                                <i data-lucide="users" class="h-6 w-6"></i>
                            </div>
                            <p class="mt-4 text-sm font-semibold text-slate-700">No user accounts found.</p>
                            <p class="mt-1 text-xs text-slate-500">Try adjusting your search or role filter.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>

<div class="ajax-pagination mt-5" data-turbo="false">
    {{ $users->links() }}
</div>

@foreach ($users as $user)
    <x-modal :name="'edit-user-'.$user->id" :show="$errors->isNotEmpty() && (string) old('_editing_user_id') === (string) $user->id" max-width="lg">
        <form method="POST" action="{{ route('users.update', $user) }}" class="flex max-h-[85vh] flex-col">
            @csrf
            @method('PUT')
            <input type="hidden" name="_editing_user_id" value="{{ $user->id }}">

            <div class="flex items-start justify-between gap-4 border-b border-slate-100 px-6 py-5">
                <div class="flex items-center gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ ($roleMeta[$user->role] ?? $roleMeta['admin'])['chip'] }} text-xs font-semibold text-white shadow-sm">
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    </span>
                    <div>
                        <h2 class="font-display text-lg font-semibold text-slate-900">Edit {{ $user->name }}</h2>
                        <p class="mt-0.5 text-sm text-slate-500">{{ $user->email }}</p>
                    </div>
                </div>
                <button type="button" @click="$dispatch('close-modal', 'edit-user-{{ $user->id }}')" class="shrink-0 rounded-lg p-1.5 text-slate-400 outline-none transition-colors duration-150 hover:bg-slate-100 hover:text-slate-700 focus-visible:ring-2 focus-visible:ring-brand-200" aria-label="Close">
                    <i data-lucide="x" class="h-5 w-5"></i>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto px-6 py-5">
                @include('users._form', ['user' => $user])
            </div>

            <div class="flex shrink-0 items-center justify-end gap-3 border-t border-slate-100 bg-slate-50/80 px-6 py-4">
                <button type="button" @click="$dispatch('close-modal', 'edit-user-{{ $user->id }}')" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 shadow-sm transition-colors duration-150 hover:bg-slate-50">Cancel</button>
                <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-all duration-200 ease-elegant hover:-translate-y-px hover:bg-brand-700 hover:shadow-elevated active:translate-y-0">
                    <i data-lucide="check" class="h-4 w-4"></i>
                    Save Changes
                </button>
            </div>
        </form>
    </x-modal>

    @if ($user->id !== auth()->id())
        @php
            $recordedWork = $user->recordedWork();
            $needsForce = $recordedWork !== [];
            $isDeleteError = (string) old('_deleting_user_id') === (string) $user->id;
        @endphp
        <x-modal :name="'delete-user-'.$user->id" :show="$isDeleteError && $errors->has('confirmation')" max-width="md">
            <form method="POST" action="{{ route('users.destroy', $user) }}" class="px-6 pb-6 pt-7" x-data="{ typed: '' }">
                @csrf
                @method('DELETE')
                <input type="hidden" name="_deleting_user_id" value="{{ $user->id }}">

                <div class="flex items-start gap-4">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-red-50 text-red-600">
                        <i data-lucide="triangle-alert" class="h-5 w-5"></i>
                    </span>
                    <div class="min-w-0">
                        <h2 class="font-display text-lg font-semibold text-slate-900">{{ $needsForce ? 'Force delete' : 'Delete' }} {{ $user->name }}?</h2>
                        @if ($needsForce)
                            <p class="mt-1 text-sm text-slate-500">
                                This account has recorded <span class="font-medium text-slate-700">{{ implode(', ', $recordedWork) }}</span>.
                            </p>
                            <ul class="mt-3 space-y-1.5 rounded-xl border border-red-200 bg-red-50 px-3.5 py-3 text-sm text-red-700">
                                <li class="flex gap-2"><i data-lucide="x" class="mt-0.5 h-3.5 w-3.5 shrink-0"></i>The login for {{ $user->email }} is removed and can no longer sign in.</li>
                                <li class="flex gap-2"><i data-lucide="x" class="mt-0.5 h-3.5 w-3.5 shrink-0"></i>They are removed from every project they are assigned to.</li>
                                <li class="flex gap-2"><i data-lucide="check" class="mt-0.5 h-3.5 w-3.5 shrink-0"></i>Their recorded work stays, still showing their name.</li>
                            </ul>
                            <p class="mt-3 text-sm font-medium text-slate-700">This cannot be undone.</p>
                        @else
                            <p class="mt-1 text-sm text-slate-500">This permanently removes the login for <span class="font-medium text-slate-700">{{ $user->email }}</span> and any project assignments. It cannot be undone.</p>
                        @endif
                    </div>
                </div>

                @if ($needsForce)
                    <div class="mt-5">
                        <label for="delete-confirm-{{ $user->id }}" class="mb-1.5 block text-sm text-slate-600">Type <span class="font-mono font-semibold text-slate-900">confirm</span> to continue</label>
                        <input
                            id="delete-confirm-{{ $user->id }}"
                            name="confirmation"
                            x-model="typed"
                            autocomplete="off"
                            spellcheck="false"
                            placeholder="confirm"
                            class="w-full rounded-xl border-slate-200 bg-slate-50/60 font-mono shadow-sm focus:border-red-400 focus:bg-white focus:ring-2 focus:ring-red-100"
                        >
                        @if ($isDeleteError)
                            @error('confirmation')
                                <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p>
                            @enderror
                        @endif
                    </div>
                @endif

                <div class="mt-6 flex items-center justify-end gap-3">
                    <button type="button" @click="$dispatch('close-modal', 'delete-user-{{ $user->id }}')" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 shadow-sm transition-colors duration-150 hover:bg-slate-50">Cancel</button>
                    <button
                        type="submit"
                        @if ($needsForce) :disabled="typed.trim().toLowerCase() !== 'confirm'" @endif
                        class="inline-flex items-center gap-2 rounded-xl bg-red-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors duration-150 hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        <i data-lucide="trash-2" class="h-4 w-4"></i>
                        {{ $needsForce ? 'Force Delete' : 'Delete User' }}
                    </button>
                </div>
            </form>
        </x-modal>
    @endif
@endforeach
