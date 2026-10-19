{{-- Offered on the login page to Windows browsers; see App\View\Components\DesktopInstallPrompt. --}}
<div
    x-data="{
        visible: false,
        started: false,
        storageKey: 'bmpc.desktopPrompt.dismissedAt',
        init() {
            let dismissedAt = 0;
            try { dismissedAt = Number(localStorage.getItem(this.storageKey)) || 0; } catch (e) {}
            // 'Not now' hides the offer for 30 days in this browser.
            this.visible = Date.now() - dismissedAt > 30 * 24 * 60 * 60 * 1000;
        },
        dismiss() {
            this.visible = false;
            try { localStorage.setItem(this.storageKey, String(Date.now())); } catch (e) {}
        },
    }"
    x-show="visible"
    x-transition.opacity.duration.200ms
    style="display: none"
    class="mt-8 rounded-xl border border-slate-200 bg-slate-50 p-4"
    role="region"
    aria-label="{{ __('Desktop app') }}"
>
    <div class="flex items-start gap-3">
        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white text-brand-600 shadow-sm ring-1 ring-slate-200">
            <i data-lucide="monitor-down" class="h-4 w-4"></i>
        </span>

        <div class="min-w-0 flex-1">
            <div x-show="!started">
                <p class="text-sm font-semibold text-slate-900">{{ __('Install the desktop app?') }}</p>
                <p class="mt-0.5 text-xs leading-relaxed text-slate-500">
                    {{ __('Open BMPC Project Management in its own window, straight from the Start menu. For Windows 10 and 11, :size download.', ['size' => $downloadSize]) }}
                </p>

                <div class="mt-3 flex flex-wrap items-center gap-2">
                    <a
                        href="{{ route('desktop.installer') }}"
                        x-on:click="started = true"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-brand-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm transition-colors duration-150 hover:bg-brand-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2"
                    >
                        <i data-lucide="download" class="h-3.5 w-3.5"></i>
                        {{ __('Install') }}
                    </a>
                    <button
                        type="button"
                        x-on:click="dismiss()"
                        class="inline-flex items-center rounded-lg px-3 py-1.5 text-xs font-medium text-slate-600 transition-colors duration-150 hover:bg-slate-200/60 hover:text-slate-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2"
                    >
                        {{ __('Not now') }}
                    </button>
                </div>
            </div>

            <div x-show="started" style="display: none" aria-live="polite">
                <p class="flex items-center gap-1.5 text-sm font-semibold text-slate-900">
                    <i data-lucide="check" class="h-4 w-4 text-emerald-600"></i>
                    {{ __('Download started') }}
                </p>
                <p class="mt-0.5 text-xs leading-relaxed text-slate-500">
                    {{ __('Open :file from your browser\'s downloads. It installs by itself and opens the app. If Windows shows "Windows protected your PC", choose More info, then Run anyway.', ['file' => config('desktop.download_name')]) }}
                </p>
                <div class="mt-3 flex flex-wrap items-center gap-2">
                    <a
                        href="{{ route('desktop.installer') }}"
                        class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-medium text-brand-700 transition-colors duration-150 hover:bg-slate-200/60 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2"
                    >
                        <i data-lucide="rotate-cw" class="h-3.5 w-3.5"></i>
                        {{ __('Download again') }}
                    </a>
                    <button
                        type="button"
                        x-on:click="dismiss()"
                        class="inline-flex items-center rounded-lg px-3 py-1.5 text-xs font-medium text-slate-600 transition-colors duration-150 hover:bg-slate-200/60 hover:text-slate-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2"
                    >
                        {{ __('Close') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
