// Caricato nel <head> senza defer: gira prima del primo disegno della pagina.
// - classe "js": i CSS nascondono gli elementi da animare solo se JavaScript è attivo;
// - data-schema: chiaro o scuro, dalla scelta salvata (js/schema.js) o dal sistema, senza lampi di colore.
document.documentElement.classList.add('js');
(function () {
    let schema = null;
    try { schema = localStorage.getItem('kronos-schema'); } catch (e) { /* memoria non disponibile */ }
    document.documentElement.dataset.schema = schema || (matchMedia('(prefers-color-scheme: dark)').matches ? 'scuro' : 'chiaro');
})();
