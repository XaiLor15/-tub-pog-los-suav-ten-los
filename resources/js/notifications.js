import { esc, getJson, postJson } from './http';

/* Notifications popover: events in 7 days, news from the last 7 days, pending users (Admin). */
const btn = document.getElementById('notif-button');
const panel = document.getElementById('notif-panel');
const list = document.getElementById('notif-list');
const countEl = document.getElementById('notif-count');
let data = { items: [], unread: 0 };
let tab = 'all';

async function load() {
    try {
        data = await getJson('/notifications');
        badge();
        if (!panel.hidden) render();
    } catch {
        if (!panel.hidden) list.innerHTML = '<div class="p-6 text-center"><p class="text-destructive">ບໍ່ສາມາດເຊື່ອມຕໍ່ເຄືອຂ່າຍໄດ້</p><button type="button" class="btn-outline btn-sm mt-3" data-notif-retry>ລອງໃໝ່</button></div>';
    }
}

function badge() {
    if (!countEl) return;
    countEl.textContent = data.unread > 9 ? '9+' : String(data.unread);
    countEl.classList.toggle('hidden', !data.unread);
    document.querySelectorAll('[data-notif-unread]').forEach((el) => { el.textContent = data.unread; });
}

function render() {
    const items = tab === 'unread' ? data.items.filter((i) => !i.read) : data.items;
    if (!items.length) { list.innerHTML = '<p class="p-8 text-center text-sm text-muted">ບໍ່ມີແຈ້ງເຕືອນ</p>'; return; }
    const groups = [['today', 'ມື້ນີ້'], ['week', '7 ວັນຜ່ານມາ'], ['upcoming', 'ກຳລັງຈະມາເຖິງ']];
    list.innerHTML = groups.map(([g, label]) => {
        const rows = items.filter((i) => i.group === g);
        if (!rows.length) return '';
        return `<p class="px-3 pt-3 pb-1 text-xs font-semibold text-muted">${label}</p>` + rows.map((i) => `
            <a href="${esc(i.url)}" data-key="${esc(i.key)}" class="flex items-start gap-3 rounded-xl px-3 py-2.5 hover:bg-surface-2 ${i.read ? '' : 'bg-primary-soft/50'}">
                <span class="mt-0.5 inline-flex size-9 shrink-0 items-center justify-center rounded-lg ${i.kind === 'news' ? 'bg-primary-soft text-primary' : 'bg-warning-bg text-warning'} text-sm font-bold">${esc(i.glyph)}</span>
                <span class="min-w-0 flex-1"><span class="block font-semibold">${esc(i.title)}</span><span class="block text-sm text-muted">${esc(i.subtitle)}</span></span>
                ${i.read ? '' : '<span class="mt-2 size-2 shrink-0 rounded-full bg-info" aria-label="ຍັງບໍ່ອ່ານ"></span>'}
            </a>`).join('');
    }).join('');
}

if (btn && panel) {
    btn.addEventListener('click', (e) => {
        e.stopPropagation();
        panel.hidden = !panel.hidden;
        btn.setAttribute('aria-expanded', String(!panel.hidden));
        if (!panel.hidden) render();
    });
    document.addEventListener('click', (e) => { if (!panel.hidden && !panel.contains(e.target) && e.target !== btn) { panel.hidden = true; btn.setAttribute('aria-expanded', 'false'); } });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && !panel.hidden) { panel.hidden = true; btn.focus(); } });
    panel.querySelectorAll('[data-notif-tab]').forEach((t) => t.addEventListener('click', () => {
        tab = t.dataset.notifTab;
        panel.querySelectorAll('[data-notif-tab]').forEach((o) => {
            const on = o === t;
            o.setAttribute('aria-selected', String(on));
            o.classList.toggle('border-primary', on); o.classList.toggle('border-transparent', !on); o.classList.toggle('text-muted', !on);
        });
        render();
    }));
    list.addEventListener('click', (e) => {
        if (e.target.closest('[data-notif-retry]')) { load(); return; }
        const a = e.target.closest('a[data-key]');
        if (a) postJson('/notifications/read', { key: a.dataset.key }).catch(() => {});
    });
    document.getElementById('notif-mark-all')?.addEventListener('click', async () => {
        await postJson('/notifications/read', { all: true }).catch(() => {});
        data.items.forEach((i) => { i.read = true; });
        data.unread = 0;
        badge(); render();
    });
    load();
    // Lightweight "realtime": refresh every 60 s and when the tab regains focus.
    setInterval(() => { if (document.visibilityState === 'visible') load(); }, 60000);
    document.addEventListener('visibilitychange', () => { if (document.visibilityState === 'visible') load(); });
}
