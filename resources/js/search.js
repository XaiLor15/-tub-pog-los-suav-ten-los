import { debounce, esc, getJson, store } from './http';
import { closeDialog } from './motion';
import { emptyState, errorState, iconUse, skeletonRows } from './ui-states';

/* CommandSearch — Ctrl/Cmd + K. Groups: members · pages · events · quick commands. */
const dlg = document.getElementById('search-dialog');
const input = document.getElementById('search-input');
const box = document.getElementById('search-results');
const RECENT_KEY = 'kths.recentSearches';
let items = [];
let active = 0;

const open = () => { if (!dlg) return; dlg.showModal(); input.value = ''; render(null); input.focus(); };

document.addEventListener('keydown', (e) => {
    if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') { e.preventDefault(); open(); }
});
document.addEventListener('click', (e) => { if (e.target.closest('[data-search-open]')) open(); });
document.querySelector('[data-search-clear]')?.addEventListener('click', () => { input.value = ''; input.focus(); render(null); });

const search = debounce(async (q) => {
    if (!q.trim()) { render(null); return; }
    box.innerHTML = skeletonRows(3);
    try { render(await getJson(`/search?q=${encodeURIComponent(q)}`), q); }
    catch { box.innerHTML = errorState(); }
}, 300);

input?.addEventListener('input', () => search(input.value));
box?.addEventListener('click', (e) => { if (e.target.closest('[data-retry]')) search(input.value); });

input?.addEventListener('keydown', (e) => {
    if (e.key === 'ArrowDown') { e.preventDefault(); setActive(active + 1); }
    else if (e.key === 'ArrowUp') { e.preventDefault(); setActive(active - 1); }
    else if (e.key === 'Enter') { e.preventDefault(); go(items[active]); }
    else if (e.key === 'Escape') { e.preventDefault(); dlg.close(); } // spec: Esc closes (type=search would only clear)
});

function setActive(i) {
    if (!items.length) return;
    active = (i + items.length) % items.length;
    box.querySelectorAll('[role=option]').forEach((el, idx) => {
        el.setAttribute('aria-selected', idx === active ? 'true' : 'false');
        el.querySelector('[data-enter]')?.classList.toggle('hidden', idx !== active);
        if (idx === active) el.scrollIntoView({ block: 'nearest' });
    });
}

function go(item) {
    if (!item) return;
    const q = input.value.trim();
    if (q) store.set(RECENT_KEY, [q, ...store.get(RECENT_KEY, []).filter((x) => x !== q)].slice(0, 3));
    if (item.action === 'theme') { document.querySelector('[data-theme-toggle]')?.click(); closeDialog(dlg); return; }
    window.location.href = item.url;
}

function render(data, q = '') {
    items = [];
    active = 0;
    if (!data) {
        const recent = store.get(RECENT_KEY, []);
        box.innerHTML = recent.length
            ? `<p class="px-3 pt-2 pb-1 text-xs font-semibold text-muted">ຄົ້ນຫາລ່າສຸດ</p>${recent.map((r) => `<button type="button" class="nav-item w-full" data-recent="${esc(r)}">${esc(r)}</button>`).join('')}`
            : emptyState('ພິມຊື່ສະມາຊິກ (ລາວ ຫຼື ມົ້ງ), ກິດຈະກຳ ຫຼື ໜ້າ', 'search');
        box.querySelectorAll('[data-recent]').forEach((b) => b.addEventListener('click', () => { input.value = b.dataset.recent; search(b.dataset.recent); }));
        return;
    }
    const groups = [['members', 'ສະມາຊິກ'], ['pages', 'ໜ້າ'], ['events', 'ກິດຈະກຳ'], ['commands', 'ຄຳສັ່ງດ່ວນ']];
    const tiles = { pages: ['arrow-right', 'bg-primary-soft text-primary'], events: ['calendar-days', 'bg-warning-bg text-warning'], commands: ['command', 'bg-success-bg text-success'] };
    let html = '';
    groups.forEach(([key, label]) => {
        const list = data[key] || [];
        if (!list.length) return;
        html += `<p class="px-3 pt-3 pb-1 text-xs font-semibold text-muted">${label}</p>`;
        list.forEach((it) => {
            const idx = items.push(it) - 1;
            const avatar = it.initial !== undefined
                ? `<span class="inline-flex size-9 items-center justify-center rounded-lg ${it.sex === 'female' ? 'bg-avatar-female text-avatar-female-fg' : 'bg-avatar-male text-avatar-male-fg'} text-sm font-bold">${esc(it.initial)}</span>`
                : `<span class="inline-flex size-9 items-center justify-center rounded-lg ${tiles[key][1]}">${iconUse(tiles[key][0], 'size-[1.125rem]')}</span>`;
            html += `<div role="option" aria-selected="${idx === 0}" data-idx="${idx}" class="flex cursor-pointer items-center gap-3 rounded-xl px-3 py-2.5 hover:bg-surface-2 aria-selected:bg-primary-soft">
                ${avatar}<span class="min-w-0 flex-1"><span class="block truncate font-semibold">${esc(it.title)}</span><span class="block truncate text-sm text-muted">${esc(it.subtitle || '')}</span></span>
                <kbd data-enter class="${idx === 0 ? '' : 'hidden'} inline-flex items-center gap-1 rounded-md border border-border bg-card px-1.5 py-0.5 font-sans text-xs text-muted">Enter ${iconUse('corner-down-left', 'size-3')}</kbd></div>`;
        });
    });
    box.innerHTML = html || emptyState(`ບໍ່ພົບຜົນສຳລັບ “${q}”`, 'search-x');
    box.querySelectorAll('[role=option]').forEach((el, n) => {
        el.style.animationDelay = `${Math.min(n, 8) * 30}ms`;
        el.addEventListener('click', () => go(items[Number(el.dataset.idx)]));
        el.addEventListener('mousemove', () => setActive(Number(el.dataset.idx)));
    });
}
