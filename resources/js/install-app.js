/**
 * Installable app support.
 *
 * - Registers the service worker (public/sw.js) so browsers offer to install.
 * - Keeps the browser's install offer (Android and desktop Chrome/Edge) so the
 *   /install page's button can show it when the person taps Install.
 * - `installApp` powers the /install page: it works out the device and shows
 *   the matching steps first.
 */

let deferredPrompt = null;

export function registerInstallSupport() {
    if ('serviceWorker' in navigator && window.isSecureContext) {
        window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js').catch(() => {}));
    }

    window.addEventListener('beforeinstallprompt', (event) => {
        event.preventDefault();
        deferredPrompt = event;
        window.dispatchEvent(new CustomEvent('bmpc:installable'));
    });

    window.addEventListener('appinstalled', () => {
        deferredPrompt = null;
        window.dispatchEvent(new CustomEvent('bmpc:installed'));
    });
}

const detectDevice = () => {
    const agent = navigator.userAgent;
    const isIpadOs = navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1;

    if (/iPhone|iPad|iPod/.test(agent) || isIpadOs) {
        return 'ios';
    }

    return /Android/.test(agent) ? 'android' : 'desktop';
};

export const installApp = () => ({
    device: detectDevice(),
    canPrompt: deferredPrompt !== null,
    installed: window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true,
    secure: window.isSecureContext,
    isWindows: /Windows/.test(navigator.userAgent),

    init() {
        window.addEventListener('bmpc:installable', () => (this.canPrompt = true));
        window.addEventListener('bmpc:installed', () => {
            this.installed = true;
            this.canPrompt = false;
        });
    },

    async install() {
        if (!deferredPrompt) {
            return;
        }

        deferredPrompt.prompt();
        const choice = await deferredPrompt.userChoice;
        deferredPrompt = null;
        this.canPrompt = false;

        if (choice.outcome === 'accepted') {
            this.installed = true;
        }
    },
});
