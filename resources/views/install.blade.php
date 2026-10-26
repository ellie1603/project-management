<x-guest-layout>
    {{-- One link for every device. The card for the device it's opened on comes first. --}}
    <div x-data="installApp">
        <div class="mb-6">
            <h1 class="font-display text-2xl font-semibold tracking-tight text-slate-900">Install BMPC</h1>
            <p class="mt-1.5 text-sm text-slate-500">
                Put BMPC Project Management on your phone or computer like an app. It's the same system with the same data, and you can always keep using it in your browser instead.
            </p>
        </div>

        <div x-show="installed" class="mb-4 flex gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" style="display: none;">
            <i data-lucide="circle-check" class="mt-0.5 h-4 w-4 shrink-0"></i>
            <p>BMPC is installed on this device. Open it from your home screen, Start menu, or app list.</p>
        </div>

        <div x-show="! secure" class="mb-4 flex gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800" style="display: none;">
            <i data-lucide="triangle-alert" class="mt-0.5 h-4 w-4 shrink-0"></i>
            <p>Installing needs a secure address (one starting with https://). You can still use BMPC in this browser.</p>
        </div>

        <div class="flex flex-col gap-3">
            {{-- Android --}}
            <section data-install-for="android" class="rounded-2xl border p-4" :class="device === 'android' ? 'order-first border-brand-400 bg-white ring-2 ring-brand-100' : 'order-last border-slate-200 bg-white'">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="flex items-center gap-2 text-sm font-semibold text-slate-900"><i data-lucide="smartphone" class="h-4 w-4"></i> Android phone or tablet</h2>
                    <span x-show="device === 'android'" class="rounded-full bg-brand-50 px-2 py-0.5 text-[10px] font-semibold uppercase text-brand-700" style="display: none;">This device</span>
                </div>
                <button type="button" x-show="device === 'android' && canPrompt && ! installed" @click="install()" class="mt-3 inline-flex w-full items-center justify-center gap-2 rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-700" style="display: none;">
                    <i data-lucide="download" class="h-4 w-4"></i> Install app
                </button>
                <ol class="mt-3 list-decimal space-y-1 pl-5 text-sm text-slate-600">
                    <li>Open this page in <span class="font-medium text-slate-800">Chrome</span>.</li>
                    <li>Tap <span class="font-medium text-slate-800">Install app</span> above, or the <span class="font-medium text-slate-800">⋮</span> menu → <span class="font-medium text-slate-800">Install app</span> (or <span class="font-medium text-slate-800">Add to Home screen</span>).</li>
                    <li>Open <span class="font-medium text-slate-800">BMPC</span> from your home screen and sign in.</li>
                </ol>
            </section>

            {{-- iPhone / iPad --}}
            <section data-install-for="ios" class="rounded-2xl border p-4" :class="device === 'ios' ? 'order-first border-brand-400 bg-white ring-2 ring-brand-100' : 'order-last border-slate-200 bg-white'">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="flex items-center gap-2 text-sm font-semibold text-slate-900"><i data-lucide="tablet-smartphone" class="h-4 w-4"></i> iPhone or iPad</h2>
                    <span x-show="device === 'ios'" class="rounded-full bg-brand-50 px-2 py-0.5 text-[10px] font-semibold uppercase text-brand-700" style="display: none;">This device</span>
                </div>
                <ol class="mt-3 list-decimal space-y-1 pl-5 text-sm text-slate-600">
                    <li>Open this page in <span class="font-medium text-slate-800">Safari</span>.</li>
                    <li>Tap the <span class="font-medium text-slate-800">Share</span> button <i data-lucide="share" class="inline h-3.5 w-3.5 align-[-2px]"></i> at the bottom (top on iPad).</li>
                    <li>Scroll and tap <span class="font-medium text-slate-800">Add to Home Screen</span>, then <span class="font-medium text-slate-800">Add</span>.</li>
                    <li>Open <span class="font-medium text-slate-800">BMPC</span> from your home screen and sign in.</li>
                </ol>
            </section>

            {{-- Computers --}}
            <section data-install-for="desktop" class="rounded-2xl border p-4" :class="device === 'desktop' ? 'order-first border-brand-400 bg-white ring-2 ring-brand-100' : 'order-last border-slate-200 bg-white'">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="flex items-center gap-2 text-sm font-semibold text-slate-900"><i data-lucide="monitor" class="h-4 w-4"></i> Computer</h2>
                    <span x-show="device === 'desktop'" class="rounded-full bg-brand-50 px-2 py-0.5 text-[10px] font-semibold uppercase text-brand-700" style="display: none;">This device</span>
                </div>

                @if ($windowsInstaller)
                    <a href="{{ route('desktop.installer') }}" data-turbo="false" class="mt-3 inline-flex w-full items-center justify-center gap-2 rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-700">
                        <i data-lucide="download" class="h-4 w-4"></i> Download for Windows
                    </a>
                    <p class="mt-2 text-xs text-slate-500">Windows 10 and 11. Open the downloaded file and it installs by itself. If Windows shows "Windows protected your PC", choose More info, then Run anyway.</p>
                @endif

                <button type="button" x-show="device === 'desktop' && canPrompt && ! installed" @click="install()" class="mt-3 inline-flex w-full items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50" style="display: none;">
                    <i data-lucide="app-window" class="h-4 w-4"></i> Install from this browser
                </button>
                <p class="mt-3 text-sm text-slate-600">
                    In <span class="font-medium text-slate-800">Chrome</span> or <span class="font-medium text-slate-800">Edge</span>, you can also click the install icon at the right end of the address bar, or the browser menu → <span class="font-medium text-slate-800">Install BMPC</span> (Edge: Apps → Install this site as an app).
                </p>
            </section>
        </div>

        <a href="{{ route('login') }}" class="mt-6 inline-flex w-full items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">
            <i data-lucide="globe" class="h-4 w-4"></i> Open in your browser instead
        </a>
        <p class="mt-3 text-center text-xs text-slate-400">Share this page's link with anyone who needs the app: {{ route('app.install') }}</p>
    </div>
</x-guest-layout>
