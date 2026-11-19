/*
 * Motion & feedback: entrance stagger indices, number count-up, page progress bar,
 * animated dialog close, bell ring. All movement respects prefers-reduced-motion
 * (CSS also neutralises durations in motion.css).
 */
export const reducedMotion = () => window.matchMedia?.('(prefers-reduced-motion: reduce)').matches ?? false;

/* ---------- Stagger indices: CSS reads --i for animation-delay (capped so long lists stay snappy) ---------- */
function indexChildren(parent, cap = 12) {
    [...parent.children].forEach((el, i) => el.style.setProperty('--i', String(Math.min(i, cap))));
}
const main = document.querySelector('main.page-enter');
if (main) indexChildren(main, 8);
document.querySelectorAll('[data-stagger]').forEach((el) => indexChildren(el));
document.querySelectorAll('.bar-grow, .bar-rise').forEach((bar) => {
    const siblings = [...(bar.closest('[data-bars]')?.querySelectorAll('.bar-grow, .bar-rise') ?? [])];
    const i = siblings.indexOf(bar);
    if (i > -1) bar.style.setProperty('--i', String(Math.min(i, 14)));
});

/* ---------- Count-up for statistics: <p data-countup="10450000" data-suffix=" ₭">10.450.000 ₭</p> ---------- */
const fmt = (n) => Math.round(n).toLocaleString('de-DE');   // 1.250.000 (dot thousands, like Format::number)
function countUp(el) {
    const to = Number(el.dataset.countup);
    const suffix = el.dataset.suffix || '';
    if (!Number.isFinite(to) || to <= 0) return;
    const duration = Math.min(1400, 600 + Math.log10(to + 1) * 180);
    const start = performance.now();
    const step = (now) => {
        const t = Math.min(1, (now - start) / duration);
        const eased = 1 - Math.pow(1 - t, 3);
        el.textContent = fmt(to * eased) + suffix;
        if (t < 1) requestAnimationFrame(step); else el.textContent = fmt(to) + suffix;
    };
    requestAnimationFrame(step);
}
const counters = document.querySelectorAll('[data-countup]');
if (counters.length && !reducedMotion()) {
    if ('IntersectionObserver' in window) {
        const io = new IntersectionObserver((entries) => entries.forEach((e) => {
            if (e.isIntersecting) { countUp(e.target); io.unobserve(e.target); }
        }), { threshold: 0.4 });
        counters.forEach((el) => io.observe(el));
    } else counters.forEach(countUp);
}

/* ---------- Page progress bar for navigations and form posts ---------- */
const bar = document.getElementById('page-progress');
export function startProgress() {
    if (!bar) return;
    bar.removeAttribute('data-done');
    void bar.offsetWidth;          // restart the transition
    bar.setAttribute('data-active', '');
}
function finishProgress() {
    if (!bar || !bar.hasAttribute('data-active')) return;
    bar.setAttribute('data-done', '');
    setTimeout(() => bar.removeAttribute('data-active'), 400);
}
document.addEventListener('click', (e) => {
    const a = e.target.closest('a[href]');
    if (!a || e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
    if (a.target && a.target !== '_self') return;
    if (a.hasAttribute('download') || a.dataset.lightbox !== undefined) return;
    const url = new URL(a.href, location.href);
    if (url.origin !== location.origin || (url.pathname === location.pathname && url.search === location.search && url.hash)) return;
    if (/\.(csv|pdf|zip)$/i.test(url.pathname)) return;   // downloads do not navigate
    startProgress();
});
document.addEventListener('submit', (e) => {
    const f = e.target;
    if (f instanceof HTMLFormElement && f.method !== 'dialog' && (f.method || '').toLowerCase() !== 'dialog') {
        setTimeout(() => { if (!e.defaultPrevented) startProgress(); }, 0);
    }
});
window.addEventListener('pageshow', finishProgress);   // also fires on back/forward cache restore

/* ---------- Dialogs close with a short exit animation ---------- */
export function closeDialog(dlg, returnValue) {
    if (!dlg?.open || dlg.hasAttribute('data-closing')) return;
    if (reducedMotion()) { dlg.close(returnValue); return; }
    dlg.setAttribute('data-closing', '');
    setTimeout(() => { dlg.removeAttribute('data-closing'); dlg.close(returnValue); }, 140);
}
// Esc: animate instead of the instant native close.
document.addEventListener('cancel', (e) => {
    if (e.target instanceof HTMLDialogElement) { e.preventDefault(); closeDialog(e.target); }
}, true);

/* ---------- Bell rings once when there are unread notifications ---------- */
export function ringBell() {
    const btn = document.getElementById('notif-button');
    if (!btn || reducedMotion()) return;
    btn.classList.remove('bell-ring');
    void btn.offsetWidth;
    btn.classList.add('bell-ring');
}
