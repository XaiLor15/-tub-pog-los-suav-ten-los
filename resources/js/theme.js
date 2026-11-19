/*
 * Theme: light | dark | system.
 * theme-init.js resolves the preference before first paint; this module keeps it in sync afterwards:
 *  - the header theme menu ([data-theme-option]) and the Profile radios (name=pref_theme) set the preference,
 *  - "system" follows the OS setting live,
 *  - the switch is revealed with a circular view transition from the control that triggered it.
 */
import { savePreference } from './ui';
import { reducedMotion } from './motion';

const root = document.documentElement;
const media = window.matchMedia?.('(prefers-color-scheme: dark)');

const resolve = (pref) => (pref === 'light' || pref === 'dark' ? pref : (media?.matches ? 'dark' : 'light'));
export const currentPreference = () => root.dataset.themePref || 'system';

function paint(theme) {
    if (root.dataset.theme === theme) return;
    root.setAttribute('data-theme-switching', '');
    root.dataset.theme = theme;
    requestAnimationFrame(() => requestAnimationFrame(() => root.removeAttribute('data-theme-switching')));
}

function reveal(theme, origin) {
    if (!document.startViewTransition || reducedMotion() || !origin) { paint(theme); return; }
    const rect = origin.getBoundingClientRect();
    const x = rect.left + rect.width / 2;
    const y = rect.top + rect.height / 2;
    const radius = Math.hypot(Math.max(x, innerWidth - x), Math.max(y, innerHeight - y));
    root.dataset.themeReveal = '';
    const transition = document.startViewTransition(() => paint(theme));
    transition.ready.then(() => {
        root.animate(
            { clipPath: [`circle(0px at ${x}px ${y}px)`, `circle(${radius}px at ${x}px ${y}px)`] },
            { duration: 480, easing: 'cubic-bezier(0.22, 1, 0.36, 1)', pseudoElement: '::view-transition-new(root)' },
        );
    }).catch(() => {});
    transition.finished.finally(() => delete root.dataset.themeReveal);
}

function syncControls() {
    const pref = currentPreference();
    document.querySelectorAll('[data-theme-option]').forEach((el) => el.setAttribute('aria-checked', String(el.dataset.themeOption === pref)));
    document.querySelectorAll('input[name=pref_theme]').forEach((el) => { el.checked = el.value === pref; });
    document.querySelectorAll('[data-theme-current]').forEach((el) => { el.dataset.themeCurrent = pref; });
}

export function setTheme(pref, origin = null) {
    root.dataset.themePref = pref;
    reveal(resolve(pref), origin);
    syncControls();
    savePreference({ theme: pref });
}

/** Quick toggle (command palette): flips between light and dark. */
export function toggleTheme(origin = null) {
    setTheme(root.dataset.theme === 'dark' ? 'light' : 'dark', origin);
}

document.addEventListener('click', (e) => {
    const option = e.target.closest('[data-theme-option]');
    if (!option) return;
    const trigger = option.closest('[data-dropdown]')?.previousElementSibling ?? option;
    setTheme(option.dataset.themeOption, trigger);
});

document.addEventListener('change', (e) => {
    if (e.target.name === 'pref_theme') setTheme(e.target.value, e.target.closest('label'));
});

media?.addEventListener('change', () => {
    if (currentPreference() === 'system') paint(resolve('system'));
});

syncControls();
