import Alpine from 'alpinejs';
import progressUpdate from './progress-update';

window.Alpine = Alpine;

Alpine.data('progressUpdate', progressUpdate);

/**
 * Switches the light/dark theme, remembers the choice, and briefly enables a
 * cross-fade so every surface transitions together. Pages with charts listen
 * for `bmpc:theme` to redraw with the new palette. Returns the new state.
 */
window.bmpcSetTheme = function bmpcSetTheme(dark) {
    const root = document.documentElement;

    root.classList.add('theme-transition');
    root.classList.toggle('dark', dark);
    window.setTimeout(() => root.classList.remove('theme-transition'), 300);

    try {
        localStorage.setItem('bmpc-theme', dark ? 'dark' : 'light');
    } catch (e) {
        // Storage can be unavailable (private mode); the theme still applies for this page.
    }

    document.dispatchEvent(new CustomEvent('bmpc:theme', { detail: { dark } }));

    return dark;
};

/**
 * Reads a theme colour variable (e.g. '--fg-slate-900') as an rgb() string for
 * canvas-based charts, which can't use CSS classes.
 */
window.bmpcColor = function bmpcColor(name, alpha = 1) {
    const value = getComputedStyle(document.documentElement).getPropertyValue(name).trim();

    return value ? `rgb(${value} / ${alpha})` : '';
};

Alpine.start();

/**
 * Marks a single notification as read from the bell-icon dropdown without
 * closing the dropdown or navigating away. Called via a plain onclick from
 * the fetched dropdown fragment (that HTML isn't Alpine-processed since it
 * arrives through x-html).
 */
window.markNotificationRead = function markNotificationRead(id, button) {
    fetch(`/notifications/${id}/read`, {
        method: 'PATCH',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
        },
    }).then(() => {
        const row = button.closest('div.flex.items-start');
        row?.classList.remove('bg-brand-50/40');
        button.remove();

        const badge = document.getElementById('notification-badge');
        if (badge) {
            const remaining = parseInt(badge.textContent, 10) - 1;
            if (remaining > 0) {
                badge.textContent = remaining;
            } else {
                badge.remove();
            }
        }
    });
};

/**
 * Animates stat-card figures counting up from 0 on first view, purely as a
 * polish touch — parses the number out of the server-rendered text (which
 * may carry a currency prefix, thousands separators, or a % suffix) and
 * replays it with the same formatting so the animated and final text match
 * exactly. Falls back to showing the value immediately for non-numeric
 * content or when the user prefers reduced motion.
 */
(function countUpStats() {
    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const numberPattern = /-?[\d,]+(\.\d+)?/;
    const easeOutCubic = (t) => 1 - Math.pow(1 - t, 3);

    const animate = (el) => {
        const original = el.textContent.trim();
        const match = original.match(numberPattern);

        if (!match || prefersReducedMotion) {
            return;
        }

        const numeric = match[0];
        const target = parseFloat(numeric.replace(/,/g, ''));

        if (Number.isNaN(target)) {
            return;
        }

        const prefix = original.slice(0, match.index);
        const suffix = original.slice(match.index + numeric.length);
        const decimals = numeric.includes('.') ? numeric.split('.')[1].length : 0;
        const duration = 900;
        const start = performance.now();

        const frame = (now) => {
            const progress = Math.min(1, (now - start) / duration);
            const current = target * easeOutCubic(progress);
            el.textContent = prefix + current.toLocaleString('en-US', {
                minimumFractionDigits: decimals,
                maximumFractionDigits: decimals,
            }) + suffix;

            if (progress < 1) {
                requestAnimationFrame(frame);
            } else {
                el.textContent = original;
            }
        };

        requestAnimationFrame(frame);
    };

    const observer = 'IntersectionObserver' in window
        ? new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    animate(entry.target);
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.4 })
        : null;

    const scan = () => {
        document.querySelectorAll('[data-count-up]').forEach((el) => {
            observer ? observer.observe(el) : animate(el);
        });
    };

    document.addEventListener('DOMContentLoaded', scan);
    document.addEventListener('turbo:load', scan);
})();

/**
 * Lightweight top-of-page progress bar so navigations (full-page link
 * clicks, form submits, and AJAX region swaps) feel immediate rather than
 * blank/frozen while the next page/fragment loads.
 */
const navProgress = (function navProgress() {
    const bar = document.createElement('div');
    bar.id = 'nav-progress';

    // Turbo replaces <body> wholesale on every visit, which would otherwise
    // detach this bar from the document after the very first navigation —
    // re-attach it if that's happened before showing/hiding it.
    const ensureAttached = () => {
        if (!bar.isConnected) {
            document.body.appendChild(bar);
        }
    };

    const start = () => {
        ensureAttached();
        bar.classList.remove('nav-progress-done');
        // Force reflow so the width transition re-triggers on repeat navigations.
        void bar.offsetWidth;
        bar.classList.add('nav-progress-active');
    };

    const done = () => {
        ensureAttached();
        bar.classList.remove('nav-progress-active');
        bar.classList.add('nav-progress-done');
    };

    ensureAttached();
    window.addEventListener('pageshow', done);
    document.addEventListener('turbo:load', done);

    document.addEventListener('click', (event) => {
        const link = event.target.closest('a[href]');

        if (!link || link.target === '_blank' || link.hasAttribute('download') || link.closest('.ajax-pagination') || link.closest('[data-turbo="false"]')) {
            return;
        }

        const url = new URL(link.href, window.location.href);
        const isSamePageHash = url.pathname === window.location.pathname && url.hash;
        const isModifiedClick = event.metaKey || event.ctrlKey || event.shiftKey || event.altKey;

        if (url.origin !== window.location.origin || isSamePageHash || isModifiedClick) {
            return;
        }

        start();
    });

    document.addEventListener('submit', (event) => {
        const form = event.target;

        if (form instanceof HTMLFormElement && form.method.toLowerCase() !== 'get' && !form.hasAttribute('data-ajax-form')) {
            start();
        }
    });

    return { start, done };
})();

/**
 * Prefetch same-origin pages on hover/focus so the browser has a head
 * start by the time the user actually clicks — makes navigation feel
 * instant without changing how the app renders pages.
 */
(function hoverPrefetch() {
    const prefetched = new Set();

    const prefetch = (link) => {
        if (!(link instanceof HTMLAnchorElement) || !link.href) {
            return;
        }

        const url = new URL(link.href, window.location.href);

        if (
            url.origin !== window.location.origin ||
            prefetched.has(url.href) ||
            link.hasAttribute('download') ||
            link.target === '_blank'
        ) {
            return;
        }

        prefetched.add(url.href);

        const hint = document.createElement('link');
        hint.rel = 'prefetch';
        hint.href = url.href;
        document.head.appendChild(hint);
    };

    document.addEventListener('mouseover', (event) => prefetch(event.target.closest('a[href]')), { passive: true });
    document.addEventListener('focusin', (event) => prefetch(event.target.closest('a[href]')));
})();

/**
 * AJAX-driven pagination and filtering for list pages, so paging through
 * or filtering a table never triggers a full page reload.
 *
 * Markup contract:
 * - A container: <div id="X" data-ajax-region>...</div>
 * - Pagination links inside it wrapped in: <div class="ajax-pagination">...</div>
 * - Optional filter forms anywhere on the page: <form data-ajax-form="X" method="GET">
 *
 * The server must respond to requests carrying the X-Requested-With:
 * XMLHttpRequest header with just the region's inner HTML (see
 * Controller::respond()), not a full page.
 */
(function ajaxRegions() {
    const reinitIcons = () => {
        if (window.lucide) {
            window.lucide.createIcons();
        }
    };

    const swapRegion = (region, html, url) => {
        region.innerHTML = html;
        reinitIcons();
        // Re-run Alpine's directive binding on the freshly injected markup —
        // innerHTML alone doesn't wire up x-data components like <x-modal>.
        window.Alpine?.initTree(region);

        if (url) {
            window.history.pushState({ ajaxRegion: region.id }, '', url);
        }
    };

    const loadInto = (region, url) => {
        navProgress.start();
        region.classList.add('opacity-40', 'pointer-events-none', 'transition-opacity', 'duration-200', 'ease-elegant');

        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then((response) => response.text())
            .then((html) => swapRegion(region, html, url))
            .catch(() => {
                window.location.href = url;
            })
            .finally(() => {
                navProgress.done();
                region.classList.remove('opacity-40', 'pointer-events-none');
            });
    };

    document.addEventListener('click', (event) => {
        const link = event.target.closest('[data-ajax-region] .ajax-pagination a[href]');

        if (!link) {
            return;
        }

        const isModifiedClick = event.metaKey || event.ctrlKey || event.shiftKey || event.altKey;

        if (isModifiedClick) {
            return;
        }

        event.preventDefault();
        loadInto(link.closest('[data-ajax-region]'), link.href);
    });

    document.addEventListener('submit', (event) => {
        const form = event.target;
        const targetId = form instanceof HTMLFormElement ? form.dataset.ajaxForm : null;

        if (!targetId || form.method.toLowerCase() !== 'get') {
            return;
        }

        const region = document.getElementById(targetId);

        if (!region) {
            return;
        }

        event.preventDefault();

        const params = new URLSearchParams(new FormData(form)).toString();
        // Without an action attribute, form.action is the current URL including its old query string.
        const url = form.action.split('?')[0] + (params ? `?${params}` : '');

        loadInto(region, url);
    });

    const ajaxFormOf = (element) => {
        const form = element?.closest?.('form[data-ajax-form]');

        return form && form.method.toLowerCase() === 'get' ? form : null;
    };

    document.addEventListener('change', (event) => {
        const form = ajaxFormOf(event.target);

        if (form && event.target.matches('select, input[type="date"], input[type="checkbox"], input[type="radio"]')) {
            form.requestSubmit();
        }
    });

    let searchTimer;

    document.addEventListener('input', (event) => {
        const form = ajaxFormOf(event.target);

        if (!form || !event.target.matches('input[type="text"], input[type="search"]')) {
            return;
        }

        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => form.requestSubmit(), 400);
    });

    window.addEventListener('popstate', () => {
        document.querySelectorAll('[data-ajax-region]').forEach((region) => {
            loadInto(region, window.location.href);
        });
    });
})();
