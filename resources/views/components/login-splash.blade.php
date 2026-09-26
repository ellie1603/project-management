@if (session('just_logged_in'))
    <div
        x-data="{ show: true }"
        x-init="setTimeout(() => show = false, 5000 + Math.random() * 3000)"
        x-show="show"
        x-transition:leave="transition-all ease-elegant duration-700"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-[1.04]"
        class="fixed inset-0 z-[100] flex flex-col items-center justify-center bg-brand-900 palette-fixed hero-brand"
    >
        <div class="pointer-events-none absolute inset-0 overflow-hidden">
        </div>

        <div class="relative flex flex-col items-center">
            <div class="relative flex h-36 w-36 items-center justify-center">
                <div class="splash-halo absolute h-36 w-36 rounded-full bg-white/10 blur-2xl"></div>

                <svg class="absolute inset-0 h-36 w-36 -rotate-90" viewBox="0 0 144 144">
                    <circle cx="72" cy="72" r="64" fill="none" stroke="rgba(255,255,255,0.08)" stroke-width="3" />
                    <circle
                        cx="72" cy="72" r="64" fill="none" stroke="#e4e4e7" stroke-width="3"
                        stroke-linecap="round" stroke-dasharray="402"
                        class="splash-ring"
                    />
                </svg>

                <x-application-logo class="splash-fade-in relative h-20 w-20 rounded-2xl bg-white p-3 shadow-glow" />
            </div>

            <p class="splash-fade-in mt-9 text-xs font-semibold uppercase tracking-[0.35em] text-brand-300" style="animation-delay: 260ms">
                {{ config('app.name', 'BMPC') }}
            </p>
            <p class="splash-fade-in mt-3 font-display text-2xl font-semibold tracking-tight text-white" style="animation-delay: 380ms">
                Welcome back, {{ explode(' ', auth()->user()->name)[0] }}
            </p>
            <p class="splash-fade-in mt-2 text-base text-slate-400" style="animation-delay: 480ms">
                Preparing your workspace&hellip;
            </p>

            <div class="splash-fade-in relative mt-9 h-[4px] w-56 overflow-hidden rounded-full bg-white/10" style="animation-delay: 600ms">
                <span class="splash-sweep absolute inset-y-0 w-1/3 rounded-full bg-brand-300/40"></span>
            </div>
        </div>
    </div>

    <style>
        .splash-fade-in {
            opacity: 0;
            animation: splash-fade-in 700ms cubic-bezier(0.16, 1, 0.3, 1) both;
        }

        @keyframes splash-fade-in {
            from { opacity: 0; transform: translateY(10px) scale(0.98); filter: blur(4px); }
            to { opacity: 1; transform: translateY(0) scale(1); filter: blur(0); }
        }

        .splash-ring {
            stroke-dashoffset: 402;
            animation: splash-ring-draw 1300ms cubic-bezier(0.22, 1, 0.36, 1) 150ms forwards;
        }

        @keyframes splash-ring-draw {
            to { stroke-dashoffset: 0; }
        }

        .splash-halo {
            animation: splash-halo-pulse 2.6s ease-in-out infinite;
        }

        @keyframes splash-halo-pulse {
            0%, 100% { opacity: 0.5; transform: scale(1); }
            50% { opacity: 0.85; transform: scale(1.1); }
        }


        .splash-sweep {
            left: -40%;
            animation: splash-sweep 1.6s cubic-bezier(0.4, 0, 0.2, 1) infinite;
        }

        @keyframes splash-sweep {
            0% { left: -40%; }
            100% { left: 140%; }
        }
    </style>
@endif
