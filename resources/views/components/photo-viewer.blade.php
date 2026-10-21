{{-- Shared full-screen photo viewer, opened with $dispatch('view-photos', { photos, index }). See resources/js/photo-viewer.js. --}}
<div
    x-data="photoViewer"
    x-show="isOpen"
    x-transition.opacity.duration.200ms
    @view-photos.window="open($event.detail)"
    @keydown.window.capture="onKey($event)"
    class="palette-fixed fixed inset-0 z-[100] flex flex-col bg-black"
    style="display: none;"
    role="dialog"
    aria-modal="true"
    aria-label="Photo viewer"
>
    <div class="flex items-center justify-between gap-3 px-4 py-3 text-white sm:px-6">
        <div class="min-w-0">
            <p class="truncate text-sm font-semibold" x-text="current.caption"></p>
            <p x-show="hasMany" class="text-xs tabular-nums text-white/60" x-text="(index + 1) + ' of ' + photos.length"></p>
        </div>

        <div class="flex shrink-0 items-center gap-1">
            <a
                x-show="current.download"
                :href="current.download"
                data-turbo="false"
                class="flex h-10 w-10 items-center justify-center rounded-full text-white transition-colors duration-150 hover:bg-white/15"
                aria-label="Download original"
                title="Download original"
            >
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m7 10 5 5 5-5"/><path d="M12 15V3"/></svg>
            </a>
            <button
                type="button"
                x-ref="close"
                @click="close()"
                class="flex h-10 w-10 items-center justify-center rounded-full text-white transition-colors duration-150 hover:bg-white/15"
                aria-label="Close photo"
            >
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" class="h-5 w-5" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
            </button>
        </div>
    </div>

    <div class="relative min-h-0 flex-1">
        <div
            x-ref="stage"
            class="absolute inset-0"
            :class="zoomed ? 'overflow-auto' : 'flex items-center justify-center overflow-hidden px-4 pb-4 sm:px-16 sm:pb-8'"
            @click.self="zoomed ? (zoomed = false) : close()"
            @touchstart.passive="touchStart($event)"
            @touchend="touchEnd($event)"
        >
            <img
                x-ref="image"
                :src="current.src"
                :alt="current.caption"
                x-on:load="loading = false"
                x-on:error="loading = false"
                @click="toggleZoom($event)"
                draggable="false"
                class="select-none rounded-lg transition-opacity duration-200"
                :class="[zoomed ? 'max-w-none cursor-zoom-out' : 'max-h-full max-w-full object-contain cursor-zoom-in', loading ? 'opacity-0' : 'opacity-100']"
                :style="zoomed ? `width: ${zoomWidth}px; margin: auto;` : ''"
            >
        </div>

        <div x-show="loading" class="pointer-events-none absolute inset-0 flex items-center justify-center" style="display: none;">
            <span class="h-8 w-8 animate-spin rounded-full border-2 border-white/25 border-t-white"></span>
        </div>

        <button
            type="button"
            x-show="hasMany && ! zoomed"
            @click="prev()"
            :disabled="index === 0"
            class="absolute left-2 top-1/2 flex h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full bg-white/10 text-white transition-colors duration-150 hover:bg-white/25 disabled:opacity-0 sm:left-4"
            aria-label="Previous photo"
        >
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
        </button>
        <button
            type="button"
            x-show="hasMany && ! zoomed"
            @click="next()"
            :disabled="index === photos.length - 1"
            class="absolute right-2 top-1/2 flex h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full bg-white/10 text-white transition-colors duration-150 hover:bg-white/25 disabled:opacity-0 sm:right-4"
            aria-label="Next photo"
        >
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
        </button>
    </div>

    <p class="pb-4 text-center text-xs text-white/50" x-text="zoomed ? 'Tap the photo to fit it to the screen' : 'Tap the photo to zoom in'"></p>
</div>
