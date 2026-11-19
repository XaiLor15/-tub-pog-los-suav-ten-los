/*
 * Tooltips for icon buttons and the collapsed sidebar.
 * Source text: data-tip, else aria-label on .icon-btn / .icon-btn-sm.
 * data-tip-when="collapsed" → only while the desktop sidebar is collapsed.
 * data-tip-side="right" → beside the element (sidebar), otherwise above (below if no room).
 * Shown on hover (after a short delay) and on keyboard focus; never on touch.
 */
const tip = document.getElementById('tooltip');
let timer;
let current = null;

const SELECTOR = '[data-tip], .icon-btn[aria-label], .icon-btn-sm[aria-label]';

function textFor(el) {
    if (el.dataset.tipWhen === 'collapsed' && (document.documentElement.dataset.sidebar !== 'collapsed' || window.innerWidth < 1024)) return '';
    return el.dataset.tip || el.getAttribute('aria-label') || '';
}

function place(el) {
    const r = el.getBoundingClientRect();
    const t = tip.getBoundingClientRect();
    const gap = 8;
    let side = el.dataset.tipSide || 'top';
    let x;
    let y;
    if (side === 'right') {
        x = r.right + gap;
        y = r.top + r.height / 2 - t.height / 2;
    } else {
        x = Math.min(Math.max(8, r.left + r.width / 2 - t.width / 2), window.innerWidth - t.width - 8);
        y = r.top - t.height - gap;
        if (y < 8) { y = r.bottom + gap; side = 'bottom'; }
    }
    tip.dataset.side = side;
    tip.style.left = `${Math.round(x)}px`;
    tip.style.top = `${Math.round(y)}px`;
}

function show(el, delay) {
    const text = textFor(el);
    if (!text || !tip) return;
    clearTimeout(timer);
    timer = setTimeout(() => {
        current = el;
        tip.textContent = text;
        tip.removeAttribute('data-show');
        place(el);
        requestAnimationFrame(() => tip.setAttribute('data-show', ''));
        if (!el.hasAttribute('aria-describedby')) el.setAttribute('aria-describedby', 'tooltip');
    }, delay);
}

function hide() {
    clearTimeout(timer);
    current = null;
    tip?.removeAttribute('data-show');
}

if (tip) {
    document.addEventListener('pointerover', (e) => {
        if (e.pointerType === 'touch') return;
        const el = e.target.closest(SELECTOR);
        if (el && el !== current) show(el, 350);
        else if (!el && current) hide();
    });
    document.addEventListener('pointerout', (e) => {
        const el = e.target.closest(SELECTOR);
        if (el && !el.contains(e.relatedTarget)) hide();
    });
    document.addEventListener('focusin', (e) => {
        const el = e.target.closest(SELECTOR);
        if (el && e.target.matches(':focus-visible')) show(el, 0);
    });
    document.addEventListener('focusout', hide);
    document.addEventListener('pointerdown', hide);
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') hide(); });
    window.addEventListener('scroll', hide, true);
}
