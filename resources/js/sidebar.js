/* Desktop sidebar collapse/expand (icon rail). State persists in localStorage; theme-init.js restores it before paint. */
const root = document.documentElement;
const toggle = document.querySelector('[data-sidebar-toggle]');

function sync() {
    const collapsed = root.dataset.sidebar === 'collapsed';
    toggle?.setAttribute('aria-expanded', String(!collapsed));
    toggle?.setAttribute('aria-label', collapsed ? 'ຂະຫຍາຍເມນູ' : 'ຫຍໍ້ເມນູ');
}

toggle?.addEventListener('click', () => {
    root.classList.add('sidebar-anim');
    setTimeout(() => root.classList.remove('sidebar-anim'), 450);
    const collapsed = root.dataset.sidebar !== 'collapsed';
    if (collapsed) root.dataset.sidebar = 'collapsed'; else delete root.dataset.sidebar;
    try { localStorage.setItem('kths.sidebar', collapsed ? 'collapsed' : 'expanded'); } catch { /* storage blocked */ }
    sync();
});
sync();
