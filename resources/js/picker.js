import { debounce, esc, getJson } from './http';

/*
 * MemberCombobox: <div data-member-picker data-sex="male?"> with an <input type=hidden data-picker-value>
 * and a text <input data-picker-input>. Debounced 300 ms search on /members/options.
 */
function init(root) {
    const hidden = root.querySelector('[data-picker-value]') ?? document.createElement('input');
    const input = root.querySelector('[data-picker-input]');
    const list = document.createElement('ul');
    list.className = 'absolute z-30 mt-1 hidden max-h-64 w-full overflow-y-auto rounded-xl border border-border bg-card p-1 shadow-pop';
    list.setAttribute('role', 'listbox');
    root.classList.add('relative');
    root.appendChild(list);
    input.setAttribute('role', 'combobox');
    input.setAttribute('autocomplete', 'off');
    let options = [];
    let active = -1;

    const render = () => {
        list.innerHTML = options.length ? options.map((o, i) => `<li role="option" data-i="${i}" aria-selected="${i === active}"
            class="flex cursor-pointer items-center gap-3 rounded-lg px-3 py-2 hover:bg-surface-2 aria-selected:bg-primary-soft">
            <span class="inline-flex size-8 items-center justify-center rounded-full bg-avatar-male text-avatar-male-fg text-sm font-bold">${esc(o.initial)}</span>
            <span class="min-w-0"><span class="block font-semibold">${esc(o.name)}</span><span class="block text-xs text-muted">${esc(o.sub)}</span></span></li>`).join('')
            : '<li class="px-3 py-3 text-sm text-muted">ບໍ່ພົບ</li>';
        list.classList.remove('hidden');
    };
    const load = debounce(async () => {
        try {
            const sex = root.dataset.sex ? `&sex=${root.dataset.sex}` : '';
            options = await getJson(`/members/options?q=${encodeURIComponent(input.value)}${sex}`);
            active = -1;
            render();
        } catch { list.innerHTML = '<li class="px-3 py-3 text-sm text-destructive">ບໍ່ສາມາດເຊື່ອມຕໍ່ເຄືອຂ່າຍໄດ້</li>'; list.classList.remove('hidden'); }
    }, 300);
    const chips = root.parentElement.querySelector('[data-picker-chips]');
    const choose = (o) => {
        if (!o) return;
        if (root.hasAttribute('data-multi')) {
            if (!chips.querySelector(`input[value="${o.id}"]`)) addChip(chips, root.dataset.multi, o.id, o.name);
            input.value = '';
            list.classList.add('hidden');
            return;
        }
        hidden.value = o.id;
        input.value = o.name;
        list.classList.add('hidden');
        hidden.dispatchEvent(new Event('change', { bubbles: true }));
    };
    input.addEventListener('input', () => { hidden.value = ''; load(); });
    input.addEventListener('focus', load);
    input.addEventListener('blur', () => setTimeout(() => list.classList.add('hidden'), 150));
    input.addEventListener('keydown', (e) => {
        if (e.key === 'ArrowDown') { e.preventDefault(); active = Math.min(options.length - 1, active + 1); render(); }
        else if (e.key === 'ArrowUp') { e.preventDefault(); active = Math.max(0, active - 1); render(); }
        else if (e.key === 'Enter' && active >= 0) { e.preventDefault(); choose(options[active]); }
    });
    list.addEventListener('mousedown', (e) => { const li = e.target.closest('[data-i]'); if (li) { e.preventDefault(); choose(options[Number(li.dataset.i)]); } });
}

export function addChip(chips, name, id, label) {
    const chip = document.createElement('span');
    chip.className = 'inline-flex items-center gap-1 rounded-full border border-border bg-card py-1 pr-1 pl-3 text-sm';
    chip.innerHTML = `${esc(label)}<input type="hidden" name="${esc(name)}[]" value="${esc(id)}"><button type="button" class="inline-flex size-6 items-center justify-center rounded-full hover:bg-surface-2" aria-label="ເອົາອອກ">×</button>`;
    chip.querySelector('button').addEventListener('click', () => chip.remove());
    chips.appendChild(chip);
}

document.querySelectorAll('[data-member-picker]').forEach(init);
document.querySelectorAll('[data-picker-chips] button').forEach((b) => b.addEventListener('click', () => b.closest('span').remove()));
