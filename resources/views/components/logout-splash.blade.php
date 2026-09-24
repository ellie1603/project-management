<div
    x-data="{ show: false }"
    x-init="window.addEventListener('bmpc-logout', (event) => {
        show = true;
        setTimeout(() => document.getElementById(event.detail.formId)?.submit(), 2450);
    })"
    x-show="show"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    class="fixed inset-0 z-[100] flex flex-col items-center justify-center bg-slate-950 palette-fixed"
    style="display: none;"
>
    <div class="pointer-events-none absolute inset-0 overflow-hidden">
        <div class="absolute -left-24 -top-24 h-[26rem] w-[26rem] animate-drift rounded-full bg-slate-500/20 blur-3xl"></div>
        <div class="absolute -bottom-28 -right-16 h-[24rem] w-[24rem] animate-drift-slow rounded-full bg-white/5 blur-3xl"></div>
        <div class="absolute inset-0 [background-image:linear-gradient(to_right,theme(colors.white/0.05)_1px,transparent_1px),linear-gradient(to_bottom,theme(colors.white/0.05)_1px,transparent_1px)] [background-size:56px_56px] [mask-image:radial-gradient(ellipse_60%_55%_at_50%_45%,black_15%,transparent_75%)]"></div>
        <div class="absolute inset-0 bg-[radial-gradient(ellipse_60%_55%_at_50%_45%,transparent,rgba(9,9,11,0.65))]"></div>
    </div>

    <div class="relative flex flex-col items-center">
        <div class="relative flex h-28 w-28 items-center justify-center">
            <div class="logout-halo absolute h-28 w-28 rounded-full bg-slate-400/20 blur-2xl"></div>

            <svg class="absolute inset-0 h-28 w-28 -rotate-90" viewBox="0 0 112 112">
                <circle cx="56" cy="56" r="50" fill="none" stroke="rgba(255,255,255,0.08)" stroke-width="2.5" />
                <circle
                    cx="56" cy="56" r="50" fill="none" stroke="#94a3b8" stroke-width="2.5"
                    stroke-linecap="round" stroke-dasharray="314"
                    class="logout-ring"
                />
            </svg>

            <span class="logout-icon-out relative flex h-16 w-16 items-center justify-center rounded-2xl bg-white/10 text-slate-200 ring-1 ring-white/10">
                <i data-lucide="log-out" class="h-7 w-7"></i>
            </span>
        </div>

        <p class="logout-fade-in mt-8 text-[11px] font-semibold uppercase tracking-[0.35em] text-slate-400" style="animation-delay: 150ms">
            {{ config('app.name', 'BMPC') }}
        </p>
        <p class="logout-fade-in mt-2 font-display text-xl font-semibold tracking-tight text-white" style="animation-delay: 260ms">
            Signing out&hellip;
        </p>
        <p class="logout-fade-in mt-1.5 text-sm text-slate-400" style="animation-delay: 360ms">
            See you again soon.
        </p>
    </div>

    <div class="logout-curtain pointer-events-none absolute inset-0 bg-slate-950"></div>

    <style>
        .logout-fade-in {
            opacity: 0;
            animation: logout-fade-in 500ms cubic-bezier(0.4, 0, 0.2, 1) both;
        }

        @keyframes logout-fade-in {
            from { opacity: 0; transform: translateY(-8px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .logout-icon-out {
            animation: logout-icon-out 500ms cubic-bezier(0.4, 0, 0.2, 1) 150ms both;
        }

        @keyframes logout-icon-out {
            from { opacity: 0; transform: scale(1.15); }
            to { opacity: 1; transform: scale(1); }
        }

        .logout-ring {
            stroke-dashoffset: 0;
            animation: logout-ring-erase 900ms cubic-bezier(0.7, 0, 0.84, 0) 250ms forwards;
        }

        @keyframes logout-ring-erase {
            to { stroke-dashoffset: -314; }
        }

        .logout-halo {
            animation: logout-halo-pulse 1.6s ease-in-out infinite;
        }

        @keyframes logout-halo-pulse {
            0%, 100% { opacity: 0.4; transform: scale(1); }
            50% { opacity: 0.7; transform: scale(0.92); }
        }

        .logout-curtain {
            opacity: 0;
            animation: logout-curtain-in 400ms ease-in 2050ms forwards;
        }

        @keyframes logout-curtain-in {
            to { opacity: 1; }
        }
    </style>
</div>
