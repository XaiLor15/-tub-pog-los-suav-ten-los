/*
 * Shared markup for JS-rendered UI: sprite icons and the standard empty / error / loading states.
 * Mirrors the Blade components (x-empty, x-error-state, x-skeleton) so both look identical.
 */
import { esc } from './http';

/** Lucide icon from the page sprite (see icon_sprite() in the layout). */
export const iconUse = (name, cls = 'size-4') =>
    `<svg class="${cls} shrink-0 ic" aria-hidden="true" focusable="false" stroke-width="1.85"><use href="#i-${name}"></use></svg>`;

export const emptyState = (message, icon = 'inbox') =>
    `<div class="flex flex-col items-center gap-3 px-4 py-10 text-center" role="status">
        <span class="state-icon">${iconUse(icon, 'size-6')}</span><p class="text-sm font-medium">${esc(message)}</p></div>`;

/** Error with a retry button carrying data-retry (callers listen for clicks on it). */
export const errorState = (message = 'ບໍ່ສາມາດເຊື່ອມຕໍ່ເຄືອຂ່າຍໄດ້') =>
    `<div class="flex flex-col items-center gap-3 px-4 py-10 text-center" role="alert">
        <span class="state-icon is-error">${iconUse('wifi-off', 'size-6')}</span>
        <p class="text-sm font-medium text-destructive">${esc(message)}</p>
        <button type="button" class="btn-outline btn-sm" data-retry>${iconUse('refresh-cw')} ລອງໃໝ່</button></div>`;

export const skeletonRows = (rows = 3) =>
    `<div class="space-y-3 p-3" aria-busy="true" aria-label="ກຳລັງໂຫຼດ">${Array.from({ length: rows }, () =>
        '<div class="flex items-center gap-3"><div class="skeleton size-9 rounded-lg"></div><div class="flex-1 space-y-2"><div class="skeleton h-3.5 w-2/3"></div><div class="skeleton h-3 w-1/3"></div></div></div>').join('')}</div>`;

export const spinner = (cls = 'size-4') => `<svg class="${cls} spinner shrink-0" aria-hidden="true" stroke-width="2"><use href="#i-loader-circle"></use></svg>`;
