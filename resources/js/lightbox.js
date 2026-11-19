/* Full-screen image viewer: any <a data-lightbox="group" href="full.jpg"> opens in an overlay. ←/→ navigate, Esc closes. */
let dlg;
let group = [];
let index = 0;

function ensure() {
    if (dlg) return dlg;
    dlg = document.createElement('dialog');
    dlg.className = 'm-0 h-dvh w-dvw max-w-none bg-black/95 p-0 text-white';
    dlg.setAttribute('aria-label', 'ເບິ່ງຮູບເຕັມຈໍ');
    dlg.innerHTML = `
        <div class="flex h-full flex-col">
            <div class="flex items-center justify-between px-4 py-3"><span data-lb-count class="text-sm"></span>
                <button type="button" data-lb-close class="inline-flex size-11 items-center justify-center rounded-lg hover:bg-white/10" aria-label="ປິດ">✕</button></div>
            <div class="relative flex flex-1 items-center justify-center overflow-hidden px-12">
                <button type="button" data-lb-prev class="absolute left-2 inline-flex size-11 items-center justify-center rounded-full bg-white/10 hover:bg-white/20" aria-label="ຮູບກ່ອນ">‹</button>
                <div data-lb-stage class="flex max-h-full max-w-full items-center justify-center"></div>
                <button type="button" data-lb-next class="absolute right-2 inline-flex size-11 items-center justify-center rounded-full bg-white/10 hover:bg-white/20" aria-label="ຮູບຕໍ່ໄປ">›</button>
            </div>
        </div>`;
    document.body.appendChild(dlg);
    dlg.querySelector('[data-lb-close]').addEventListener('click', () => dlg.close());
    dlg.querySelector('[data-lb-prev]').addEventListener('click', () => show(index - 1));
    dlg.querySelector('[data-lb-next]').addEventListener('click', () => show(index + 1));
    dlg.addEventListener('keydown', (e) => { if (e.key === 'ArrowLeft') show(index - 1); if (e.key === 'ArrowRight') show(index + 1); });
    return dlg;
}

function show(i) {
    index = (i + group.length) % group.length;
    const a = group[index];
    const stage = dlg.querySelector('[data-lb-stage]');
    stage.innerHTML = '';
    const el = a.dataset.video ? Object.assign(document.createElement('video'), { controls: true, autoplay: true, src: a.href })
        : Object.assign(document.createElement('img'), { src: a.href, alt: a.dataset.alt || '' });
    el.className = 'max-h-[calc(100dvh-5rem)] max-w-full object-contain';
    stage.appendChild(el);
    dlg.querySelector('[data-lb-count]').textContent = `${index + 1} / ${group.length}`;
    dlg.querySelectorAll('[data-lb-prev],[data-lb-next]').forEach((b) => { b.hidden = group.length < 2; });
}

document.addEventListener('click', (e) => {
    const a = e.target.closest('a[data-lightbox]');
    if (!a) return;
    e.preventDefault();
    group = [...document.querySelectorAll(`a[data-lightbox="${CSS.escape(a.dataset.lightbox)}"]`)];
    ensure().showModal();
    show(group.indexOf(a));
});
