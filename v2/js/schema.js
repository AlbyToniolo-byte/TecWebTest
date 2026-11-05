// Modalità notte. Lo schema (chiaro o scuro) sta in <html data-schema>: lo imposta già lo script
// nel <head> di php/header_footer.php, prima del primo disegno, dalla scelta salvata o dal sistema.
// Qui il bottone lo alterna e ricorda la scelta in questo browser; i colori sono nei CSS.

document.addEventListener('DOMContentLoaded', () => {
    const radice = document.documentElement;
    const bottoni = document.querySelectorAll('[data-cambia-schema]');
    const meta = document.querySelector('meta[name="theme-color"]');

    const applica = (schema) => {
        radice.dataset.schema = schema;
        bottoni.forEach((b) => b.setAttribute('aria-pressed', String(schema === 'scuro')));
        if (meta) meta.content = getComputedStyle(document.body).backgroundColor;
        // le scene 3D ascoltano questo evento per ridipingersi con i nuovi colori
        document.dispatchEvent(new CustomEvent('kronos:schema', { detail: schema }));
    };

    bottoni.forEach((b) => {
        b.hidden = false;
        b.addEventListener('click', () => {
            const nuovo = radice.dataset.schema === 'scuro' ? 'chiaro' : 'scuro';
            try { localStorage.setItem('kronos-schema', nuovo); } catch { /* senza memoria vale solo per questa pagina */ }
            applica(nuovo);
        });
    });
    applica(radice.dataset.schema === 'scuro' ? 'scuro' : 'chiaro');
});
