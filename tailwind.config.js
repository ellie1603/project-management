import defaultTheme from 'tailwindcss/defaultTheme';
import defaultColors from 'tailwindcss/colors';
import forms from '@tailwindcss/forms';
import plugin from 'tailwindcss/plugin';

/*
 * Theme palette
 * -------------
 * Neutrals ("slate"/"gray") and the accent ("brand") are an ink/zinc scale, so the
 * whole UI reads black-and-white rather than blue. Every palette is emitted as CSS
 * variables so a single `.dark` class on <html> flips the entire app, with no
 * per-view `dark:` classes needed.
 *
 * Colours are split by role: backgrounds (bg-*, gradients) resolve to `--bg-*`,
 * everything else (text, borders, rings, dividers) to `--fg-*`. That split is what
 * lets `bg-slate-900 text-white` stay a dark button in dark mode while
 * `text-slate-900` turns light.
 *
 * `.palette-fixed` pins a subtree to the light values, for surfaces that are always
 * dark by design (page hero, splash screens).
 */

const SHADES = [50, 100, 200, 300, 400, 500, 600, 700, 800, 900, 950];
const ACCENTS = ['red', 'rose', 'orange', 'amber', 'yellow', 'lime', 'green', 'emerald', 'teal', 'cyan', 'sky', 'blue', 'indigo', 'violet', 'purple', 'fuchsia', 'pink'];

const DARK_CARD = '#151517';

const ink = defaultColors.zinc;
const brandLight = { 50: '#f4f4f5', 100: '#e4e4e7', 200: '#d4d4d8', 300: '#a1a1aa', 400: '#71717a', 500: '#27272a', 600: '#18181b', 700: '#09090b', 800: '#09090b', 900: '#09090b', 950: '#000000' };

const dark = {
    fgSlate: { 50: '#1d1d20', 100: '#26262a', 200: '#303035', 300: '#3f3f46', 400: '#7c7c85', 500: '#a1a1aa', 600: '#c4c4cc', 700: '#d9d9de', 800: '#e8e8ec', 900: '#f4f4f5', 950: '#fafafa' },
    bgSlate: { 50: '#0a0a0b', 100: '#232326', 200: '#2c2c30', 300: '#3a3a40', 400: '#52525b', 500: '#71717a', 600: '#8b8b94', 700: '#52525b', 800: '#46464e', 900: '#34343a', 950: '#1f1f23' },
    fgBrand: { 50: '#1f1f22', 100: '#34343a', 200: '#46464e', 300: '#71717a', 400: '#a1a1aa', 500: '#d4d4d8', 600: '#e4e4e7', 700: '#f4f4f5', 800: '#fafafa', 900: '#ffffff', 950: '#ffffff' },
    bgBrand: { 50: '#1f1f22', 100: '#26262a', 200: '#303035', 300: '#52525b', 400: '#71717a', 500: '#e4e4e7', 600: '#d4d4d8', 700: '#a1a1aa', 800: '#71717a', 900: '#52525b', 950: '#3f3f46' },
};

const toRgb = (hex) => {
    const n = parseInt(hex.replace('#', ''), 16);

    return `${(n >> 16) & 255} ${(n >> 8) & 255} ${n & 255}`;
};

const mix = (hex, base, amount) => {
    const [r1, g1, b1] = toRgb(hex).split(' ').map(Number);
    const [r2, g2, b2] = toRgb(base).split(' ').map(Number);
    const channel = (a, b) => Math.round(a * amount + b * (1 - amount)).toString(16).padStart(2, '0');

    return `#${channel(r1, r2)}${channel(g1, g2)}${channel(b1, b2)}`;
};

// Pale tints (bg-red-50, border-amber-200) become low-alpha washes on dark surfaces,
// and deep text shades (text-emerald-700) lift so they stay readable.
const darkAccentBg = (c) => ({ ...c, 50: mix(c[500], DARK_CARD, 0.12), 100: mix(c[500], DARK_CARD, 0.2), 200: mix(c[500], DARK_CARD, 0.3) });
const darkAccentFg = (c) => ({ ...c, 50: mix(c[500], DARK_CARD, 0.12), 100: mix(c[500], DARK_CARD, 0.22), 200: mix(c[500], DARK_CARD, 0.35), 600: c[400], 700: c[300], 800: c[200], 900: c[100] });

const vars = (prefix, scale) => Object.fromEntries(SHADES.map((s) => [`--${prefix}-${s}`, toRgb(scale[s])]));
const refs = (prefix) => Object.fromEntries(SHADES.map((s) => [s, `rgb(var(--${prefix}-${s}) / <alpha-value>)`]));

const accentVars = (transform) => Object.assign(
    {},
    ...ACCENTS.flatMap((name) => [
        vars(`fg-${name}`, transform.fg(defaultColors[name])),
        vars(`bg-${name}`, transform.bg(defaultColors[name])),
    ]),
);

const lightVars = {
    ...vars('fg-slate', ink),
    ...vars('bg-slate', ink),
    ...vars('fg-brand', brandLight),
    ...vars('bg-brand', brandLight),
    '--bg-white': toRgb('#ffffff'),
    ...accentVars({ fg: (c) => c, bg: (c) => c }),
};

const darkVars = {
    ...vars('fg-slate', dark.fgSlate),
    ...vars('bg-slate', dark.bgSlate),
    ...vars('fg-brand', dark.fgBrand),
    ...vars('bg-brand', dark.bgBrand),
    '--bg-white': toRgb(DARK_CARD),
    ...accentVars({ fg: darkAccentFg, bg: darkAccentBg }),
};

const palette = (role) => ({
    slate: refs(`${role}-slate`),
    gray: refs(`${role}-slate`),
    brand: refs(`${role}-brand`),
    ...Object.fromEntries(ACCENTS.map((name) => [name, refs(`${role}-${name}`)])),
});

const backgrounds = { ...palette('bg'), white: 'rgb(var(--bg-white) / <alpha-value>)' };

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',

    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
                display: ['"Plus Jakarta Sans"', ...defaultTheme.fontFamily.sans],
            },
            colors: palette('fg'),
            backgroundColor: backgrounds,
            gradientColorStops: backgrounds,
            transitionTimingFunction: {
                smooth: 'cubic-bezier(0.4, 0, 0.2, 1)',
                elegant: 'cubic-bezier(0.22, 1, 0.36, 1)',
            },
            boxShadow: {
                soft: '0 1px 2px 0 rgb(9 9 11 / 0.04), 0 1px 3px 0 rgb(9 9 11 / 0.06)',
                elevated: '0 2px 4px -2px rgb(9 9 11 / 0.06), 0 12px 24px -8px rgb(9 9 11 / 0.12)',
                premium: '0 4px 8px -4px rgb(9 9 11 / 0.08), 0 20px 40px -12px rgb(9 9 11 / 0.16)',
                glow: '0 8px 28px -6px rgb(9 9 11 / 0.18)',
            },
            keyframes: {
                fadeIn: {
                    '0%': { opacity: '0', transform: 'translateY(4px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                },
                riseIn: {
                    '0%': { opacity: '0', transform: 'translateY(10px) scale(0.99)' },
                    '100%': { opacity: '1', transform: 'translateY(0) scale(1)' },
                },
                scaleIn: {
                    '0%': { opacity: '0', transform: 'scale(0.96)' },
                    '100%': { opacity: '1', transform: 'scale(1)' },
                },
                shimmer: {
                    '0%': { backgroundPosition: '-400px 0' },
                    '100%': { backgroundPosition: '400px 0' },
                },
                softPulse: {
                    '0%, 100%': { opacity: '1' },
                    '50%': { opacity: '0.45' },
                },
                growX: {
                    '0%': { transform: 'scaleX(0)' },
                    '100%': { transform: 'scaleX(1)' },
                },
                drift: {
                    '0%, 100%': { transform: 'translate(0, 0) scale(1)' },
                    '50%': { transform: 'translate(3%, -4%) scale(1.06)' },
                },
            },
            animation: {
                'fade-in': 'fadeIn 200ms ease-out',
                'rise-in': 'riseIn 500ms cubic-bezier(0.16, 1, 0.3, 1) both',
                'scale-in': 'scaleIn 300ms cubic-bezier(0.16, 1, 0.3, 1) both',
                shimmer: 'shimmer 1.8s ease-in-out infinite',
                'soft-pulse': 'softPulse 2.2s ease-in-out infinite',
                'grow-x': 'growX 700ms cubic-bezier(0.22, 1, 0.36, 1) both',
                drift: 'drift 14s ease-in-out infinite',
                'drift-slow': 'drift 20s ease-in-out infinite reverse',
            },
        },
    },

    plugins: [
        forms,
        plugin(({ addBase }) => {
            addBase({
                ':root': { ...lightVars, colorScheme: 'light' },
                '.dark': { ...darkVars, colorScheme: 'dark' },
                '.dark .palette-fixed': { ...lightVars, colorScheme: 'light' },
            });
        }),
    ],
};
