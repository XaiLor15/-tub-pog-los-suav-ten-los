import { esc, getJson } from './http';
import { emptyState, errorState, iconUse, skeletonRows } from './ui-states';

/*
 * Family tree (Phaj rows). Data: GET /members/tree-data (get_family_tree), side panel:
 * GET /members/{id}/lineage (get_ancestors). Tidy layout: leaves left→right in birth_order,
 * parents centred over their children. Daughters are terminal branches (no + button).
 * Zoom: buttons / Ctrl+wheel / pinch. Pan: drag.
 */
const vp = document.getElementById('tree-viewport');
const canvas = document.getElementById('tree-canvas');
const statusEl = document.getElementById('tree-status');
const panel = document.getElementById('lineage-panel');
const canWrite = vp.dataset.canWrite === '1';
const memberUrl = vp.dataset.memberUrl;

const NODE_W = 150;
const NODE_H = 56;
const GAP_X = 20;
const ROW_H = 114;
const LABEL_W = 96;
const PAD = 24;

let nodes = new Map();
let children = new Map();
let roots = [];
const collapsed = new Set();
let selected = Number(vp.dataset.selected) || null;
let lineage = new Set();
let lineageOnly = false;
let scale = 1;
let tx = 0;
let ty = 0;
let minGen = 1;
let maxGen = 1;
let laid = new Map();

async function load() {
    try {
        const { nodes: list } = await getJson('/members/tree-data?depth=12');
        if (!list.length) {
            statusEl.innerHTML = emptyState('ຍັງບໍ່ມີຂໍ້ມູນແຜນຜັງ', 'users');
            return;
        }
        statusEl.remove();
        list.forEach((n) => { nodes.set(n.id, n); children.set(n.id, []); });
        list.forEach((n) => { if (n.father_id && nodes.has(n.father_id)) children.get(n.father_id).push(n.id); else roots.push(n.id); });
        children.forEach((ids) => ids.sort((a, b) => (nodes.get(a).birth_order ?? 999) - (nodes.get(b).birth_order ?? 999) || a - b));
        minGen = Math.min(...list.map((n) => n.generation));
        maxGen = Math.max(...list.map((n) => n.generation));
        // Large trees start collapsed below the 3rd row, except along the selected lineage.
        if (list.length > 150) list.forEach((n) => { if (n.generation - minGen >= 2 && children.get(n.id).length) collapsed.add(n.id); });
        if (!selected || !nodes.has(selected)) selected = roots[0];
        await select(selected, true);
    } catch {
        statusEl.innerHTML = errorState();
        statusEl.querySelector('[data-retry]')?.addEventListener('click', () => location.reload());
    }
}

function ancestorsOf(id) {
    const set = new Set();
    let cur = nodes.get(id);
    while (cur) { set.add(cur.id); cur = cur.father_id ? nodes.get(cur.father_id) : null; }
    return set;
}

function visibleChildren(id) {
    if (collapsed.has(id)) return [];
    const kids = children.get(id) || [];
    if (!lineageOnly) return kids;
    return kids.filter((k) => lineage.has(k) || id === selected);
}

function layout() {
    laid = new Map();
    let cursor = 0;
    const place = (id) => {
        const kids = visibleChildren(id);
        const n = nodes.get(id);
        const y = PAD + (n.generation - minGen) * ROW_H + (ROW_H - NODE_H) / 2;
        if (!kids.length) {
            laid.set(id, { x: LABEL_W + PAD + cursor * (NODE_W + GAP_X), y });
            cursor++;
            return;
        }
        kids.forEach(place);
        const first = laid.get(kids[0]);
        const last = laid.get(kids[kids.length - 1]);
        laid.set(id, { x: (first.x + last.x) / 2, y });
    };
    (lineageOnly ? roots.filter((r) => lineage.has(r)) : roots).forEach((r) => { place(r); cursor += 0.5; });
}

function render() {
    layout();
    const width = Math.max(vp.clientWidth / scale, LABEL_W + PAD * 2 + Math.max(...[...laid.values()].map((p) => p.x)) + NODE_W);
    const height = PAD * 2 + (maxGen - minGen + 1) * ROW_H;
    canvas.style.width = `${width}px`;
    canvas.style.height = `${height}px`;

    let html = '';
    for (let g = minGen; g <= maxGen; g++) {
        const top = PAD + (g - minGen) * ROW_H;
        html += `<div class="absolute left-0 ${(g - minGen) % 2 ? 'bg-surface-2/50' : ''}" style="top:${top}px;height:${ROW_H}px;width:${width}px"></div>
                 <span class="badge absolute bg-primary-soft text-primary" style="left:12px;top:${top + ROW_H / 2 - 13}px">Phaj ${g}</span>`;
    }

    let edges = '';
    laid.forEach((p, id) => {
        const kids = visibleChildren(id).filter((k) => laid.has(k));
        if (!kids.length) return;
        const px = p.x + NODE_W / 2;
        const py = p.y + NODE_H;
        const midY = py + (ROW_H - NODE_H) / 2;
        const xs = kids.map((k) => laid.get(k).x + NODE_W / 2);
        edges += `<path d="M${px} ${py} V${midY} M${Math.min(...xs)} ${midY} H${Math.max(...xs)}" class="stroke-border" stroke-width="1.5" fill="none"/>`;
        kids.forEach((k) => {
            const c = laid.get(k);
            const hot = lineage.has(k) && lineage.has(id);
            edges += `<path d="M${c.x + NODE_W / 2} ${midY} V${c.y}" class="${hot ? 'stroke-primary' : 'stroke-border'}" stroke-width="${hot ? 2.5 : 1.5}" fill="none"/>`;
            if (hot) edges += `<path d="M${px} ${py} V${midY} H${c.x + NODE_W / 2}" class="stroke-primary" stroke-width="2.5" fill="none"/>`;
        });
    });
    html += `<svg class="pointer-events-none absolute inset-0" width="${width}" height="${height}" aria-hidden="true">${edges}</svg>`;

    laid.forEach((p, id) => {
        const n = nodes.get(id);
        const isSel = id === selected;
        const onLine = lineage.has(id);
        const female = n.sex === 'female';
        const sub = n.birth_order ? `${female ? 'ລູກສາວ ' : 'ຄົນທີ '}${n.birth_order} · ${n.years}` : n.years;
        const kids = children.get(id) || [];
        const hidden = collapsed.has(id) ? kids.length : 0;
        html += `<div class="absolute" style="left:${p.x}px;top:${p.y}px;width:${NODE_W}px">
            <button type="button" data-node="${id}" class="tree-node ${female ? 'is-female' : 'is-male'} ${onLine ? 'is-lineage' : ''} ${n.is_alive ? '' : 'is-dead'}" style="height:${NODE_H}px"
                aria-pressed="${isSel}" aria-label="${esc(n.name)}, Phaj ${n.generation}">
                <span class="inline-flex size-8 shrink-0 items-center justify-center rounded-full text-xs font-bold ${n.is_alive ? (female ? 'bg-avatar-female text-avatar-female-fg' : 'bg-avatar-male text-avatar-male-fg') : 'bg-surface-2 text-muted'}">${esc(initial(n.name))}</span>
                <span class="min-w-0 leading-tight"><span class="block truncate text-[0.8125rem] font-semibold">${esc(n.name)}</span><span class="block truncate text-[0.6875rem] text-muted">${esc(sub)}</span></span>
            </button>
            ${kids.length && !female ? `<button type="button" data-toggle-node="${id}" class="tree-toggle ${hidden ? 'is-collapsed' : ''}" aria-label="${hidden ? 'ເປີດ' : 'ພັບ'}ລູກ ${kids.length} ຄົນ">${hidden ? '+' + hidden : '−'}</button>` : ''}
        </div>`;
    });
    canvas.innerHTML = html;
    applyTransform();
}

const LEADING = ['ເ', 'ແ', 'ໂ', 'ໃ', 'ໄ'];
function initial(name) { for (const ch of [...String(name).trim()]) { if (!LEADING.includes(ch)) return ch.toUpperCase(); } return '?'; }

function applyTransform() {
    canvas.style.transform = `translate(${tx}px, ${ty}px) scale(${scale})`;
    document.querySelector('[data-zoom-label]').textContent = `${Math.round(scale * 100)}%`;
}

function centerOn(id) {
    const p = laid.get(id);
    if (!p) return;
    tx = vp.clientWidth / 2 - (p.x + NODE_W / 2) * scale;
    ty = vp.clientHeight / 2 - (p.y + NODE_H / 2) * scale;
    applyTransform();
}

async function select(id, center = false) {
    selected = id;
    lineage = ancestorsOf(id);
    // Make sure the selected node is visible.
    lineage.forEach((a) => { if (a !== id) collapsed.delete(a); });
    render();
    if (center) centerOn(id);
    const url = new URL(location.href);
    url.searchParams.set('id', id);
    history.replaceState(null, '', url);
    renderPanel(id);
}

async function renderPanel(id) {
    panel.innerHTML = skeletonRows(4);
    try {
        const d = await getJson(`/members/${id}/lineage`);
        const m = d.member;
        const steps = d.ancestors.map((a, i) => {
            const last = i === d.ancestors.length - 1;
            const parent = i > 0 ? d.ancestors[i - 1] : null;
            return `<li class="relative flex gap-3 pb-5">
                ${last ? '' : '<span class="absolute top-10 bottom-0 left-[1.1875rem] w-0.5 bg-primary"></span>'}
                <span class="relative z-10 inline-flex size-10 shrink-0 items-center justify-center rounded-full border-2 border-primary ${last ? 'bg-primary text-primary-foreground' : 'bg-card'} font-bold">${a.generation}</span>
                <span class="min-w-0"><span class="block text-xs font-semibold text-muted">Phaj ${a.generation} · ${i === 0 ? 'ຕົ້ນຕະກູນ' : esc(a.label.replace(/ຄົນທີ.*/, '').trim() || 'ລູກ')}</span>
                <button type="button" data-node="${a.id}" class="block text-left text-[1.0625rem] font-bold hover:underline">${esc(a.name)}</button>
                <span class="block text-sm text-muted">${parent ? esc(a.label) + ' ຂອງ ' + esc(parent.name) + ' · ' : ''}${esc(a.years)}</span>
                ${parent ? (a.phaj_ok ? `<span class="inline-flex items-center gap-1 text-xs font-semibold text-success">${iconUse('circle-check', 'size-3.5')} Phaj ຕໍ່ເນື່ອງ (${parent.generation} → ${a.generation})</span>` : `<span class="inline-flex items-center gap-1 text-xs font-semibold text-destructive">${iconUse('circle-alert', 'size-3.5')} Phaj ບໍ່ຕໍ່ເນື່ອງ — ກວດຄືນ</span>`) : ''}</span></li>`;
        }).join('');
        const kids = d.children.map((c) => `<button type="button" data-node="${c.id}" class="inline-flex items-center gap-2 rounded-full border border-border py-1 pr-3 pl-1 text-sm hover:bg-surface-2">
            <span class="inline-flex size-6 items-center justify-center rounded-full text-xs font-bold ${c.sex === 'female' ? 'bg-avatar-female text-avatar-female-fg' : 'bg-avatar-male text-avatar-male-fg'}">${c.birth_order ?? '·'}</span>${esc(c.name)}</button>`).join('');
        panel.innerHTML = `
            <div class="border-b border-border p-5">
                <p class="text-xs font-semibold text-muted">ສາຍບັນພະບຸລຸດ · ສາຍພໍ່</p>
                <h2 class="text-[1.375rem]">${esc(m.name)}</h2>
                <p class="text-sm text-muted">Phaj ${m.generation} · ${esc(m.label)}${m.birth_year ? ' · ' + m.birth_year : ''}</p>
            </div>
            <div class="p-5"><ol>${steps}</ol>
                <h3 class="mt-2 mb-2 font-semibold">ລູກຂອງ ${esc(m.name)} (${d.children.length})</h3>
                <div class="flex flex-wrap gap-2">${kids || '<span class="text-sm text-muted">ຍັງບໍ່ມີລູກໃນລະບົບ</span>'}</div>
                <div class="mt-5 grid grid-cols-2 gap-2">
                    <a href="${esc(d.url)}" class="btn-primary ${canWrite && m.sex === 'male' ? '' : 'col-span-2'}">ເປີດຂໍ້ມູນສະມາຊິກ</a>
                    ${canWrite && m.sex === 'male' ? `<a href="${memberUrl}?new=1&father=${m.id}" class="btn-outline">${iconUse('plus', 'size-4')} ເພີ່ມລູກ</a>` : ''}
                </div>
            </div>`;
    } catch {
        panel.innerHTML = errorState();
    }
}

/* ----- Interaction ----- */
const onNodeClick = (e) => {
    const t = e.target.closest('[data-toggle-node]');
    if (t) {
        const id = Number(t.dataset.toggleNode);
        collapsed.has(id) ? collapsed.delete(id) : collapsed.add(id);
        render();
        return;
    }
    const n = e.target.closest('[data-node]');
    if (n) select(Number(n.dataset.node));
};
canvas.addEventListener('click', (e) => { if (!moved) onNodeClick(e); });
panel.addEventListener('click', (e) => { const n = e.target.closest('[data-node]'); if (n) select(Number(n.dataset.node), true); });

document.querySelector('[data-tree-expand]')?.addEventListener('click', () => { collapsed.clear(); lineageOnly = false; syncLineageBtn(); render(); centerOn(selected); });
const lineageBtn = document.querySelector('[data-tree-lineage]');
const syncLineageBtn = () => { lineageBtn?.setAttribute('aria-pressed', String(lineageOnly)); lineageBtn?.classList.toggle('ring-2', lineageOnly); };
lineageBtn?.addEventListener('click', () => { lineageOnly = !lineageOnly; syncLineageBtn(); render(); centerOn(selected); });

const zoomTo = (next, cx = vp.clientWidth / 2, cy = vp.clientHeight / 2) => {
    next = Math.min(2, Math.max(0.3, next));
    tx = cx - ((cx - tx) * next) / scale;
    ty = cy - ((cy - ty) * next) / scale;
    scale = next;
    applyTransform();
};
document.querySelectorAll('[data-zoom]').forEach((b) => b.addEventListener('click', () => zoomTo(scale + Number(b.dataset.zoom) * 0.15)));
document.querySelector('[data-zoom-fit]')?.addEventListener('click', () => {
    const w = parseFloat(canvas.style.width);
    const h = parseFloat(canvas.style.height);
    scale = Math.min(1, vp.clientWidth / w, vp.clientHeight / h);
    tx = (vp.clientWidth - w * scale) / 2;
    ty = 0;
    applyTransform();
});
vp.addEventListener('wheel', (e) => {
    if (e.ctrlKey || e.metaKey) {
        e.preventDefault();
        const r = vp.getBoundingClientRect();
        zoomTo(scale * (e.deltaY < 0 ? 1.1 : 0.9), e.clientX - r.left, e.clientY - r.top);
    } else {
        e.preventDefault();
        tx -= e.deltaX;
        ty -= e.deltaY;
        applyTransform();
    }
}, { passive: false });
vp.addEventListener('keydown', (e) => {
    const step = 60;
    if (e.key === 'ArrowLeft') tx += step; else if (e.key === 'ArrowRight') tx -= step;
    else if (e.key === 'ArrowUp') ty += step; else if (e.key === 'ArrowDown') ty -= step;
    else if (e.key === '+' || e.key === '=') zoomTo(scale + 0.15); else if (e.key === '-') zoomTo(scale - 0.15);
    else return;
    e.preventDefault();
    applyTransform();
});

/* Drag to pan, two-finger pinch to zoom. */
const pointers = new Map();
let moved = false;
let last = null;
let pinchDist = 0;
vp.addEventListener('pointerdown', (e) => {
    if (e.target.closest('[data-zoom],[data-zoom-fit]')) return;
    pointers.set(e.pointerId, { x: e.clientX, y: e.clientY });
    moved = false;
    last = { x: e.clientX, y: e.clientY };
    if (pointers.size === 2) { const [a, b] = [...pointers.values()]; pinchDist = Math.hypot(a.x - b.x, a.y - b.y); }
});
vp.addEventListener('pointermove', (e) => {
    if (!pointers.has(e.pointerId)) return;
    pointers.set(e.pointerId, { x: e.clientX, y: e.clientY });
    if (pointers.size === 2) {
        const [a, b] = [...pointers.values()];
        const d = Math.hypot(a.x - b.x, a.y - b.y);
        if (pinchDist) { const r = vp.getBoundingClientRect(); zoomTo(scale * (d / pinchDist), (a.x + b.x) / 2 - r.left, (a.y + b.y) / 2 - r.top); }
        pinchDist = d;
        moved = true;
        return;
    }
    const dx = e.clientX - last.x;
    const dy = e.clientY - last.y;
    if (!moved && Math.hypot(dx, dy) < 4) return;
    if (!moved) vp.setPointerCapture(e.pointerId);
    moved = true;
    vp.classList.add('dragging');
    tx += dx;
    ty += dy;
    last = { x: e.clientX, y: e.clientY };
    applyTransform();
});
const endPointer = (e) => { pointers.delete(e.pointerId); if (pointers.size < 2) pinchDist = 0; vp.classList.remove('dragging'); setTimeout(() => { moved = false; }, 0); };
vp.addEventListener('pointerup', endPointer);
vp.addEventListener('pointercancel', endPointer);

load();

/* ----- Find a member in the tree (searches the data already loaded) ----- */
const findInput = document.getElementById('tree-search');
const findList = document.getElementById('tree-search-results');
const LIMIT = 8;
function closeFind() { findList.hidden = true; findInput.setAttribute('aria-expanded', 'false'); }
function renderFind() {
    const q = findInput.value.trim().toLowerCase();
    if (!q) { closeFind(); return; }
    const hits = [...nodes.values()].filter((n) => String(n.name).toLowerCase().includes(q)).slice(0, LIMIT);
    findList.innerHTML = hits.length
        ? hits.map((n, i) => `<button type="button" role="option" id="tree-opt-${n.id}" data-find="${n.id}" aria-selected="${i === 0}" class="menu-item">
            <span class="inline-flex size-7 shrink-0 items-center justify-center rounded-full text-xs font-bold ${n.sex === 'female' ? 'bg-avatar-female text-avatar-female-fg' : 'bg-avatar-male text-avatar-male-fg'}">${esc(initial(n.name))}</span>
            <span class="min-w-0 flex-1 truncate">${esc(n.name)}</span><span class="badge bg-primary-soft text-xs text-primary">Phaj ${n.generation}</span></button>`).join('')
        : `<p class="px-3 py-2.5 text-sm text-muted">ບໍ່ພົບຊື່ນີ້ໃນແຜນຜັງ</p>`;
    findList.hidden = false;
    findInput.setAttribute('aria-expanded', 'true');
}
function pick(id) {
    collapsed.clear();
    lineageOnly = false;
    syncLineageBtn();
    select(id, true);
    findInput.value = '';
    closeFind();
}
findInput?.addEventListener('input', renderFind);
findInput?.addEventListener('keydown', (e) => {
    const opts = [...findList.querySelectorAll('[data-find]')];
    const cur = opts.findIndex((o) => o.getAttribute('aria-selected') === 'true');
    if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
        e.preventDefault();
        if (!opts.length) return;
        const next = (cur + (e.key === 'ArrowDown' ? 1 : -1) + opts.length) % opts.length;
        opts.forEach((o, i) => o.setAttribute('aria-selected', String(i === next)));
        findInput.setAttribute('aria-activedescendant', opts[next].id);
    } else if (e.key === 'Enter') {
        e.preventDefault();
        if (opts[Math.max(0, cur)]) pick(Number(opts[Math.max(0, cur)].dataset.find));
    } else if (e.key === 'Escape') {
        findInput.value = '';
        closeFind();
    }
});
findList?.addEventListener('click', (e) => { const b = e.target.closest('[data-find]'); if (b) pick(Number(b.dataset.find)); });
document.addEventListener('click', (e) => { if (findInput && !e.target.closest('[data-tree-find]')) closeFind(); });
