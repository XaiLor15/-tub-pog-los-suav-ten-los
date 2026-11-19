/* Runs before first paint (loaded synchronously in <head>) to avoid a theme flash.
 * Signed-in users: preference comes from the server (data-theme-pref).
 * Guests: preference is kept in localStorage. */
(function () {
    var root = document.documentElement;
    var pref = root.getAttribute('data-theme-pref');
    var scale = root.getAttribute('data-font-scale');
    try {
        if (root.hasAttribute('data-guest')) {
            pref = localStorage.getItem('kths.theme') || pref;
            scale = localStorage.getItem('kths.fontScale') || scale;
        }
    } catch (e) { /* storage blocked */ }
    if (pref !== 'light' && pref !== 'dark') {
        pref = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    }
    root.setAttribute('data-theme', pref);
    if (scale) root.setAttribute('data-font-scale', scale);
})();
