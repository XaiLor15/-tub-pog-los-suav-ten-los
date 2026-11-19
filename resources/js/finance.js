import { debounce, esc, getJson } from './http';

/* find_similar_transactions: warn (never block) when a transaction with the same amount and
 * category exists within 24 hours. The client_request_id hidden field makes resubmits idempotent. */
document.querySelectorAll('[data-similar-tx]').forEach((form) => {
    const box = form.querySelector('[data-similar-box]');
    const check = debounce(async () => {
        const date = form.querySelector('[name=date]')?.value;
        const amount = form.querySelector('[name=amount]')?.value;
        const category = form.querySelector('[name=category]')?.value;
        const action = form.getAttribute('action') || '';
        const exclude = (action.match(/transactions\/(\d+)/) || [])[1] || '';
        if (!date || !amount || !category || !box) return;
        try {
            const list = await getJson(`/finance/similar?date=${date}&amount=${amount}&category=${encodeURIComponent(category)}&exclude=${exclude}`);
            box.hidden = !list.length;
            box.querySelector('[data-similar-list]').innerHTML = list.map((t) => `<li>${esc(t.date)} · ${esc(t.description)} · <b>${esc(t.amount)}</b></li>`).join('');
        } catch { box.hidden = true; }
    }, 400);
    form.addEventListener('change', check);
    form.addEventListener('input', (e) => { if (e.target.name === 'amount') check(); });

    /* Show only categories that match the selected type. */
    const sync = () => {
        const type = form.querySelector('[name=type]:checked')?.value;
        form.querySelectorAll('[name=category] option[data-type]').forEach((o) => { o.hidden = type && o.dataset.type !== type; });
    };
    form.querySelectorAll('[name=type]').forEach((r) => r.addEventListener('change', sync));
    sync();
});

/* Live "1.250.000 ₭" preview under amount inputs. */
document.addEventListener('input', (e) => {
    const el = e.target;
    if (!el.matches?.('[data-money-preview]')) return;
    const out = document.getElementById(el.dataset.moneyPreview);
    if (!out) return;
    const n = Number(el.value);
    out.textContent = el.value && Number.isFinite(n) && n > 0 ? `${Math.trunc(n).toLocaleString('de-DE')} ₭` : '';
});
