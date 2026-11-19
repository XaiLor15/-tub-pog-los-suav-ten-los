import { debounce, esc, getJson } from './http';

/* MemberForm behaviour: father combobox (male only, descendants disabled), auto Phaj,
 * birth-order picker, duplicate warning, phone normalisation preview, year-only birth. */
const form = document.querySelector('[data-member-form]');
const memberId = form.dataset.memberId || '';
const $ = (sel) => form.querySelector(sel);

/* ----- Birth date: year only ----- */
$('[data-year-only]')?.addEventListener('change', (e) => {
    $('[data-birth-year]').hidden = !e.target.checked;
    $('[data-dup-birth]').hidden = e.target.checked;
});

/* ----- Alive / deceased ----- */
form.querySelectorAll('[data-alive]').forEach((r) => r.addEventListener('change', () => {
    $('[data-death-year]').hidden = form.querySelector('[data-alive]:checked')?.value !== '0';
}));

/* ----- Phone preview (same rules as App\Support\Format) ----- */
const normalizePhone = (raw) => {
    let d = String(raw).replace(/[^\d+]/g, '');
    if (!d) return null;
    if (d.startsWith('+856')) { /* ok */ } else if (d.startsWith('00856')) d = '+' + d.slice(2);
    else if (d.startsWith('856') && d.length >= 10) d = '+' + d;
    else if (d.startsWith('0')) d = '+856' + d.slice(1);
    else if (/^(20\d{8}|30\d{7})$/.test(d)) d = '+856' + d;
    return d;
};
const displayPhone = (n) => {
    const l = n.slice(4);
    if (l.startsWith('20')) return `+856 20 ${l.slice(2, 6)} ${l.slice(6)}`;
    if (l.startsWith('30')) return `+856 30 ${l.slice(2, 5)} ${l.slice(5)}`;
    return `+856 ${l.slice(0, 2)} ${l.slice(2, 5)} ${l.slice(5)}`;
};
const phone = $('[data-phone]');
const phoneOut = $('[data-phone-preview]');
const updatePhone = () => {
    const n = normalizePhone(phone.value);
    if (!n) { phoneOut.textContent = ''; return; }
    const ok = /^\+856(20\d{8}|30\d{7}|[2-8][1-9]\d{6})$/.test(n);
    phoneOut.textContent = ok ? `✓ ຈະບັນທຶກເປັນ ${displayPhone(n)}` : 'ເບີໂທບໍ່ຖືກຕ້ອງ — ຕົວຢ່າງ 020 5512 3344';
    phoneOut.className = ok ? 'hint-ok' : 'error-text';
};
phone?.addEventListener('input', updatePhone);
if (phone?.value) updatePhone();

/* ----- Father combobox ----- */
const fatherId = $('[data-father-id]');
const search = $('[data-father-search]');
const list = $('[data-father-options]');
let options = [];
let active = -1;

const renderOptions = () => {
    list.innerHTML = options.length ? options.map((o, i) => `
        <li role="option" data-i="${i}" aria-disabled="${o.disabled ? 'true' : 'false'}" aria-selected="${i === active}"
            class="flex items-center gap-3 rounded-lg px-3 py-2 ${o.disabled ? 'cursor-not-allowed opacity-50' : 'cursor-pointer hover:bg-surface-2 aria-selected:bg-primary-soft'}">
            <span class="inline-flex size-8 items-center justify-center rounded-full bg-avatar-male text-avatar-male-fg text-sm font-bold">${esc(o.initial)}</span>
            <span class="min-w-0 flex-1"><span class="block font-semibold">${esc(o.name)}</span><span class="block text-xs text-muted">${esc(o.disabled || o.sub)}</span></span>
        </li>`).join('') : '<li class="flex items-center gap-2 px-3 py-3 text-sm text-muted"><svg class="size-4" aria-hidden="true"><use href="#i-search-x"></use></svg> ບໍ່ພົບ</li>';
    list.classList.remove('hidden');
    search.setAttribute('aria-expanded', 'true');
};
const loadOptions = debounce(async () => {
    try {
        options = await getJson(`/members/options?sex=male&for=${memberId}&q=${encodeURIComponent(search.value.split(' · ')[0])}`);
        active = -1;
        renderOptions();
    } catch { list.innerHTML = '<li class="flex items-center gap-2 px-3 py-3 text-sm text-destructive"><svg class="size-4" aria-hidden="true"><use href="#i-wifi-off"></use></svg> ບໍ່ສາມາດເຊື່ອມຕໍ່ເຄືອຂ່າຍໄດ້</li>'; list.classList.remove('hidden'); }
}, 300);
const closeList = () => { list.classList.add('hidden'); search.setAttribute('aria-expanded', 'false'); };

search?.addEventListener('input', loadOptions);
search?.addEventListener('focus', loadOptions);
search?.addEventListener('keydown', (e) => {
    if (e.key === 'ArrowDown') { e.preventDefault(); active = Math.min(options.length - 1, active + 1); renderOptions(); }
    if (e.key === 'ArrowUp') { e.preventDefault(); active = Math.max(0, active - 1); renderOptions(); }
    if (e.key === 'Enter' && active >= 0) { e.preventDefault(); choose(options[active]); }
    if (e.key === 'Escape') { e.stopPropagation(); closeList(); }
});
list?.addEventListener('mousedown', (e) => {
    const li = e.target.closest('[data-i]');
    if (!li) return;
    e.preventDefault();
    choose(options[Number(li.dataset.i)]);
});
search?.addEventListener('blur', () => setTimeout(closeList, 150));
$('[data-father-clear]')?.addEventListener('click', () => { fatherId.value = ''; search.value = ''; setFather(null); });

function choose(o) {
    if (!o || o.disabled) return;
    fatherId.value = o.id;
    search.value = `${o.name} · Phaj ${o.generation}`;
    closeList();
    $('[data-insert-at]').value = '';
    setFather(o.id);
}

/* ----- Phaj + birth order ----- */
const orderList = $('[data-order-list]');
const orderEmpty = $('[data-order-empty]');
const summary = $('[data-order-summary]');
let siblings = [];
let fatherName = '';

async function setFather(id) {
    $('[data-phaj-auto]').hidden = !id;
    $('[data-phaj-manual]').hidden = !!id;
    orderList.innerHTML = '';
    summary.textContent = '';
    if (!id) { orderEmpty.hidden = false; orderEmpty.textContent = 'ບໍ່ມີພໍ່ — ເປັນຕົ້ນຕະກູນ (ໃສ່ Phaj ເອງ)'; return; }
    orderEmpty.hidden = false;
    orderEmpty.textContent = 'ກຳລັງໂຫຼດ...';
    try {
        const data = await getJson(`/members/${id}/children`);
        fatherName = data.father.name;
        form.querySelectorAll('[data-phaj-value]').forEach((el) => { el.textContent = data.father.generation + 1; });
        form.querySelectorAll('[data-father-phaj]').forEach((el) => { el.textContent = data.father.generation; });
        siblings = data.children.filter((c) => String(c.id) !== memberId);
        const insertAt = $('[data-insert-at]');
        let pos = Number(insertAt.value) || siblings.length + 1;
        pos = Math.min(Math.max(1, pos), siblings.length + 1);
        insertAt.value = pos;
        orderEmpty.hidden = true;
        renderOrder();
    } catch { orderEmpty.textContent = 'ບໍ່ສາມາດເຊື່ອມຕໍ່ເຄືອຂ່າຍໄດ້'; }
}

function renderOrder() {
    const insertAt = $('[data-insert-at]');
    const pos = Number(insertAt.value);
    const name = $('[name=name]').value.trim() || 'ສະມາຊິກໃໝ່';
    const sex = form.querySelector('[name=sex]:checked')?.value || 'male';
    const rows = [...siblings];
    rows.splice(pos - 1, 0, { self: true, name, sex });
    const total = rows.length;
    orderList.innerHTML = rows.map((r, i) => {
        const kind = r.sex === 'female' ? 'ລູກສາວ' : 'ລູກຊາຍ';
        if (r.self) {
            return `<li class="flex items-center gap-3 bg-primary-soft/60 px-3 py-2">
                <span class="inline-flex size-6 items-center justify-center rounded-md bg-primary text-xs font-bold text-primary-foreground">${i + 1}</span>
                <span class="min-w-0 flex-1"><span class="block font-semibold">${esc(name)} (${memberId ? 'ຄົນນີ້' : 'ຄົນໃໝ່'})</span><span class="block text-xs text-muted">${kind}ຄົນທີ ${i + 1} · ໃຊ້ ↑↓ ເພື່ອປ່ຽນລຳດັບ</span></span>
                <button type="button" class="icon-btn-sm size-8" data-move="-1" ${i === 0 ? 'disabled' : ''} aria-label="ຍ້າຍຂຶ້ນ"><svg class="size-4" aria-hidden="true"><use href="#i-arrow-up"></use></svg></button>
                <button type="button" class="icon-btn-sm size-8" data-move="1" ${i === total - 1 ? 'disabled' : ''} aria-label="ຍ້າຍລົງ"><svg class="size-4" aria-hidden="true"><use href="#i-arrow-down"></use></svg></button></li>`;
        }
        return `<li class="flex items-center gap-3 px-3 py-2">
            <span class="inline-flex size-6 items-center justify-center rounded-md bg-surface-2 text-xs font-bold">${i + 1}</span>
            <span class="min-w-0 flex-1"><span class="block font-semibold">${esc(r.name)}</span><span class="block text-xs text-muted">${kind}${r.year ? ' · ເກີດ ' + r.year : ''}</span></span></li>`;
    }).join('');
    const kind = sex === 'female' ? 'ລູກສາວ' : 'ລູກຊາຍ';
    summary.textContent = `✓ ຈະເປັນ${kind}ຄົນທີ ${pos} ຈາກ ${total} ຄົນ ຂອງ ${fatherName}`;
}

orderList?.addEventListener('click', (e) => {
    const b = e.target.closest('[data-move]');
    if (!b) return;
    const insertAt = $('[data-insert-at]');
    insertAt.value = Math.min(Math.max(1, Number(insertAt.value) + Number(b.dataset.move)), siblings.length + 1);
    renderOrder();
});
form.querySelectorAll('[name=sex]').forEach((r) => r.addEventListener('change', () => { if (siblings.length || fatherId.value) renderOrder(); }));
$('[name=name]')?.addEventListener('input', () => { if (fatherId.value && orderEmpty.hidden) renderOrder(); });

if (fatherId.value) setFather(fatherId.value);

/* ----- Duplicate check (warn, never block) ----- */
const dupBox = $('[data-dup-box]');
const dupList = $('[data-dup-list]');
const checkDup = debounce(async () => {
    const name = $('[data-dup-name]').value.trim();
    if (!name) return;
    const yearOnly = $('[data-year-only]')?.checked;
    const birth = yearOnly ? (($('[data-birth-year]').value && `${$('[data-birth-year]').value}-01-01`) || '') : $('[data-dup-birth]').value;
    try {
        const dups = await getJson(`/members/duplicates?name=${encodeURIComponent(name)}&birth_date=${encodeURIComponent(birth)}&exclude=${memberId}`);
        dupBox.classList.toggle('hidden', !dups.length);
        $('[data-dup-footer]').hidden = !dups.length;
        dupList.innerHTML = dups.map((d) => `<div class="flex items-center gap-3 rounded-lg border border-border bg-card px-3 py-2">
            <span class="min-w-0 flex-1"><span class="block font-semibold">${esc(d.name)}</span><span class="block text-xs text-muted">${esc(d.sub)}</span></span>
            <a href="${esc(d.url)}" target="_blank" class="link text-sm">ເປີດເບິ່ງ</a></div>`).join('');
    } catch { /* non-blocking */ }
}, 400);
$('[data-dup-name]')?.addEventListener('input', checkDup);
$('[data-dup-birth]')?.addEventListener('change', checkDup);
$('[data-birth-year]')?.addEventListener('input', checkDup);
