@forelse ($notifications as $notification)
    @php
        $projectId = $notification->data['project_id'] ?? null;
        $isUnread = is_null($notification->read_at);
    @endphp
    <div class="flex items-start gap-3 border-b border-slate-50 px-4 py-3 last:border-b-0 {{ $isUnread ? 'bg-brand-50/40' : '' }}">
        <div class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-brand-50 text-brand-600">
            <i data-lucide="bell" class="h-3.5 w-3.5"></i>
        </div>
        <div class="min-w-0 flex-1">
            @if ($projectId)
                <a href="{{ route('projects.show', $projectId) }}" class="block text-sm font-medium text-slate-900 hover:text-brand-600">
                    {{ $notification->data['title'] ?? 'Notification' }}
                </a>
            @else
                <p class="text-sm font-medium text-slate-900">{{ $notification->data['title'] ?? 'Notification' }}</p>
            @endif
            <p class="mt-0.5 line-clamp-2 text-xs text-slate-500">{{ $notification->data['message'] ?? '' }}</p>
            <p class="mt-1 text-[11px] text-slate-400">{{ $notification->created_at->diffForHumans() }}</p>
        </div>
        @if ($isUnread)
            <button
                type="button"
                title="Mark as read"
                onclick="window.markNotificationRead('{{ $notification->id }}', this)"
                class="mt-0.5 shrink-0 rounded p-1 text-slate-400 transition-colors duration-150 hover:bg-slate-100 hover:text-brand-600"
            >
                <i data-lucide="check" class="h-3.5 w-3.5"></i>
            </button>
        @endif
    </div>
@empty
    <p class="px-4 py-10 text-center text-sm text-slate-400">No notifications yet.</p>
@endforelse
