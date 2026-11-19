import { postJson } from './http';
import { closeDialog } from './motion';
import { spinner } from './ui-states';

const isGuest = () => document.documentElement.hasAttribute('data-guest');

/* ---------- Confirm dialog for <form data-confirm> ---------- */
document.addEventListener('submit', (e) => {
    const form = e.target;
    if (!(form instanceof HTMLFormElement) || !form.dataset.confirm || form.dataset.confirmed) return;
    e.preventDefault();
    const dlg = document.getElementById('confirm-dialog');
    if (!dlg) { if (window.confirm(form.dataset.confirm)) { form.dataset.confirmed = '1'; form.requestSubmit(e.submitter ?? undefined); } return; }
    dlg.querySelector('[data-confirm-message]').textContent = form.dataset.confirm;
    const ok = dlg.querySelector('[data-confirm-ok]');
    const danger = form.hasAttribute('data-confirm-danger');
    ok.className = danger ? 'btn-danger' : 'btn-primary';
    ok.textContent = form.dataset.confirmLabel || 'ຢືນຢັນ';
    dlg.querySelector('[data-confirm-icon]')?.classList.toggle('is-error', danger);
    dlg.returnValue = '';
    dlg.showModal();
    dlg.addEventListener('close', function onClose() {
        dlg.removeEventListener('close', onClose);
        if (dlg.returnValue === 'ok') { form.dataset.confirmed = '1'; form.requestSubmit(e.submitter ?? undefined); }
    });
}, true);

/* ---------- Busy state: disable submit while saving ("ກຳລັງບັນທຶກ...") ---------- */
document.addEventListener('submit', (e) => {
    const form = e.target;
    if (!(form instanceof HTMLFormElement) || e.defaultPrevented || (form.dataset.confirm && !form.dataset.confirmed)) return;
    if (form.getAttribute('method') === 'dialog') return;   // in-dialog forms (confirm) close the dialog, they do not submit
    const btn = e.submitter && e.submitter.form === form ? e.submitter : form.querySelector('button:not([type=button]),[type=submit]');
    if (!btn || btn.disabled) return;
    if (form.dataset.submitting) { e.preventDefault(); return; }
    form.dataset.submitting = '1';
    setTimeout(() => {
        btn.disabled = true;
        btn.setAttribute('data-loading', '');
        btn.setAttribute('aria-busy', 'true');
        if (form.hasAttribute('data-busy') || btn.dataset.busyLabel) {
            btn.innerHTML = `${spinner()} <span>${btn.dataset.busyLabel || 'ກຳລັງບັນທຶກ...'}</span>`;
        } else if (!btn.querySelector('.spinner')) {
            btn.insertAdjacentHTML('afterbegin', spinner());
            btn.querySelector('svg[data-icon]')?.remove();
        }
    }, 0);
});

/* ---------- Search fields: the clear button empties the field and reapplies the filter ---------- */
document.addEventListener('click', (e) => {
    const clear = e.target.closest('[data-clear-search]');
    if (!clear) return;
    const input = clear.parentElement.querySelector('input');
    input.value = '';
    if (input.form) input.form.requestSubmit(); else input.focus();
});

/* ---------- Click delegation: modals, dropdowns, drawer, row links, reveal ---------- */
const drawer = { open() { document.getElementById('sidebar')?.classList.remove('-translate-x-full'); document.getElementById('drawer-backdrop')?.classList.remove('hidden'); },
    close() { document.getElementById('sidebar')?.classList.add('-translate-x-full'); document.getElementById('drawer-backdrop')?.classList.add('hidden'); } };

/* Dropdown menus: one open at a time; close on outside click, item choice or Esc; arrow keys move focus. */
const menuItems = (menu) => [...menu.querySelectorAll('[role^=menuitem]:not([disabled])')];
function closeDropdown(menu, { restoreFocus = false } = {}) {
    if (!menu || menu.classList.contains('hidden')) return;
    menu.classList.add('hidden');
    const trigger = document.querySelector(`[data-toggle="${menu.id}"]`);
    trigger?.setAttribute('aria-expanded', 'false');
    if (restoreFocus) trigger?.focus();
}
const closeAllDropdowns = (except = null) => document.querySelectorAll('[data-dropdown]:not(.hidden)').forEach((m) => { if (m !== except) closeDropdown(m); });

document.addEventListener('keydown', (e) => {
    const menu = document.activeElement?.closest?.('[data-dropdown]') ?? document.querySelector('[data-dropdown]:not(.hidden)');
    if (!menu || menu.classList.contains('hidden')) return;
    if (e.key === 'Escape') { e.preventDefault(); closeDropdown(menu, { restoreFocus: true }); return; }
    if (e.key !== 'ArrowDown' && e.key !== 'ArrowUp' && e.key !== 'Home' && e.key !== 'End') return;
    const items = menuItems(menu);
    if (!items.length) return;
    e.preventDefault();
    const i = items.indexOf(document.activeElement);
    const next = e.key === 'Home' ? 0 : e.key === 'End' ? items.length - 1
        : e.key === 'ArrowDown' ? (i + 1) % items.length : (i - 1 + items.length) % items.length;
    items[next].focus();
});

document.addEventListener('click', (e) => {
    const menuChoice = e.target.closest('[data-dropdown] [role^=menuitem]');
    if (menuChoice && menuChoice.tagName !== 'A') setTimeout(() => closeDropdown(menuChoice.closest('[data-dropdown]')), 0);

    const t = e.target.closest('[data-modal-open],[data-modal-close],[data-toggle],[data-drawer-open],[data-drawer-close],[data-reveal],[data-dismiss]');
    if (!t) {
        document.querySelectorAll('[data-dropdown]:not(.hidden)').forEach((el) => { if (!el.parentElement.contains(e.target)) closeDropdown(el); });
        const row = e.target.closest('tr[data-href]');
        if (row && !e.target.closest('a,button,input,select,form,label')) window.location.href = row.dataset.href;
        return;
    }
    if (t.dataset.modalOpen) {
        const dlg = document.getElementById(t.dataset.modalOpen);
        if (dlg) { prefillDialog(dlg, t); dlg.showModal(); }
    }
    if (t.hasAttribute('data-modal-close')) closeDialog(t.closest('dialog'));
    if (t.dataset.toggle) {
        const el = document.getElementById(t.dataset.toggle);
        if (el?.hasAttribute('data-dropdown')) closeAllDropdowns(el);
        el?.classList.toggle('hidden');
        t.setAttribute('aria-expanded', el && !el.classList.contains('hidden') ? 'true' : 'false');
    }
    if (t.hasAttribute('data-drawer-open')) drawer.open();
    if (t.hasAttribute('data-drawer-close')) drawer.close();
    if (t.dataset.reveal) {
        const input = document.getElementById(t.dataset.reveal);
        if (input) input.type = input.type === 'password' ? 'text' : 'password';
    }
    if (t.hasAttribute('data-dismiss')) dismissToast(t.closest('[data-dismissable]'));
});

/* Clicking the dialog backdrop closes it. */
document.addEventListener('click', (e) => { if (e.target instanceof HTMLDialogElement) closeDialog(e.target); });
document.querySelectorAll('dialog[data-autoopen]').forEach((d) => d.showModal());

/**
 * Generic edit dialogs: a trigger with data-modal-open + data-fill='{"field":"value"}'
 * + data-action="/url" fills the dialog's form (used for edit transaction / payment etc.).
 */
function prefillDialog(dlg, trigger) {
    const form = dlg.querySelector('form[data-fillable]');
    if (!form) return;
    form.reset();
    if (trigger.dataset.action) form.action = trigger.dataset.action;
    const method = form.querySelector('input[name=_method]');
    if (method) method.value = trigger.dataset.method || method.dataset.default || 'POST';
    const title = dlg.querySelector('h2');
    if (title && trigger.dataset.title) title.textContent = trigger.dataset.title;
    let data = {};
    try { data = JSON.parse(trigger.dataset.fill || '{}'); } catch { /* ignore */ }
    Object.entries(data).forEach(([name, value]) => {
        form.querySelectorAll(`[name="${name}"]`).forEach((el) => {
            if (el.type === 'radio' || el.type === 'checkbox') el.checked = String(el.value) === String(value) || value === true;
            else el.value = value ?? '';
            el.dispatchEvent(new Event('change', { bubbles: true }));
        });
    });
    form.querySelectorAll('[data-show-when-edit]').forEach((el) => { el.hidden = !trigger.dataset.action; });
    form.querySelectorAll('[name=client_request_id]').forEach((el) => { el.value = crypto.randomUUID?.() ?? String(Date.now()) + Math.random(); });
}

export function savePreference(prefs) {
    if (isGuest()) {
        if (prefs.theme) try { localStorage.setItem('kths.theme', prefs.theme); } catch { /* blocked */ }
        if (prefs.font_scale) try { localStorage.setItem('kths.fontScale', String(prefs.font_scale)); } catch { /* blocked */ }
        return;
    }
    const url = document.body.dataset.prefsUrl || '/profile/preferences';
    postJson(url, prefs).catch(() => {});
}

/* Profile font-scale control applies instantly (theme radios are handled in theme.js). */
document.addEventListener('change', (e) => {
    const el = e.target;
    if (el.name === 'pref_font_scale') {
        document.documentElement.setAttribute('data-font-scale', el.value);
        savePreference({ font_scale: Number(el.value) });
    }
});

/* ---------- Password strength (4 segments) ---------- */
document.addEventListener('input', (e) => {
    const input = e.target;
    if (!(input instanceof HTMLInputElement) || !input.dataset.strength) return;
    const meter = document.getElementById(input.dataset.strength);
    if (!meter) return;
    const v = input.value;
    let score = 0;
    if (v.length >= 8) score++;
    if (/[a-z]/.test(v) && /[A-Z]/.test(v)) score++;
    if (/\d/.test(v)) score++;
    if (/[^A-Za-z0-9]/.test(v) || v.length >= 12) score++;
    if (v.length < 8) score = Math.min(score, 1);
    const colors = ['bg-destructive', 'bg-destructive', 'bg-warning', 'bg-success', 'bg-success'];
    meter.querySelectorAll('[data-seg]').forEach((seg, i) => { seg.className = `h-1 rounded-full ${i < score ? colors[score] : 'bg-surface-2'}`; });
    const label = meter.nextElementSibling;
    if (label?.hasAttribute('data-strength-label')) {
        label.textContent = v.length < 8 ? `ລະຫັດຜ່ານຕ້ອງມີຢ່າງໜ້ອຍ 8 ຕົວອັກສອນ (ລະດັບ ${score})` : `ລະດັບຄວາມແຂງ ${score}/4`;
        label.className = v.length && v.length < 8 ? 'error-text' : 'hint';
    }
});

/* ---------- File input preview (UploadField) ---------- */
document.addEventListener('change', (e) => {
    const input = e.target;
    if (!(input instanceof HTMLInputElement) || input.type !== 'file' || !input.dataset.preview) return;
    const out = document.getElementById(input.dataset.preview);
    if (!out) return;
    const max = Number(input.dataset.maxMb || 10) * 1048576;
    const files = [...(input.files || [])];
    const tooBig = files.filter((f) => f.size > max);
    out.textContent = files.length ? files.map((f) => `${f.name} · ${(f.size / 1048576).toFixed(2)} MB`).join(', ') + (tooBig.length ? ` — ໃຫຍ່ເກີນ ${input.dataset.maxMb || 10} MB` : '') : '';
    out.className = tooBig.length ? 'error-text' : 'hint';
});

/* ---------- Toasts: fade out, then remove ---------- */
function dismissToast(el) {
    if (!el) return;
    el.style.transition = 'opacity 200ms, transform 200ms';
    el.style.opacity = '0';
    el.style.transform = 'translateY(8px) scale(0.97)';
    setTimeout(() => el.remove(), 210);
}
document.querySelectorAll('[data-autodismiss]').forEach((el) => {
    let timer = setTimeout(() => dismissToast(el), 5000);
    // Pause while hovered so the message can be read.
    el.addEventListener('mouseenter', () => clearTimeout(timer));
    el.addEventListener('mouseleave', () => { timer = setTimeout(() => dismissToast(el), 2000); });
});

/* ---------- Esc closes the mobile drawer ---------- */
document.addEventListener('keydown', (e) => { if (e.key === 'Escape') drawer.close(); });
document.addEventListener('click', (e) => { if (e.target.closest('#sidebar a[href]') && window.innerWidth < 1024) drawer.close(); });

/* ---------- Print buttons ---------- */
document.addEventListener('click', (e) => { if (e.target.closest('[data-print]')) window.print(); });

/* ---------- Auto-submit selects (filters) ---------- */
document.addEventListener('change', (e) => { if (e.target.matches?.('[data-autosubmit]')) e.target.form?.requestSubmit(); });

/* ---------- Copy link ---------- */
document.addEventListener('click', async (e) => {
    const b = e.target.closest('[data-copy]');
    if (!b) return;
    try { await navigator.clipboard.writeText(b.dataset.copy); const t = b.innerHTML; b.textContent = 'ສຳເນົາແລ້ວ ✓'; setTimeout(() => { b.innerHTML = t; }, 1500); } catch { window.prompt('ສຳເນົາລິ້ງ:', b.dataset.copy); }
});
