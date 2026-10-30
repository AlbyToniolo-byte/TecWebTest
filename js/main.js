// Kronos: comportamenti di pagina. Il sito funziona anche senza JavaScript;
// qui si aggiungono il menu a scomparsa, la sfumatura allo scorrimento e il totale dei biglietti.

document.documentElement.classList.add('js');

document.addEventListener('DOMContentLoaded', () => {
    menuMobile();
    sfumaturaMostra();
    totaleBiglietti();
    rivelaAlloScorrimento();
});

// Elementi con data-rivela o data-rivela-gruppo: ricevono la classe "visibile" quando entrano
// nella finestra. Lo stato si cambia solo con classi; l'aspetto è in css/animazioni.css.
function rivelaAlloScorrimento() {
    const elementi = document.querySelectorAll('[data-rivela], [data-rivela-gruppo]');
    if (!elementi.length) return;

    const mostra = (el) => el.classList.add('visibile');

    if (!('IntersectionObserver' in window)) {
        elementi.forEach(mostra);
        return;
    }

    const osservatore = new IntersectionObserver((voci) => {
        voci.forEach((v) => {
            if (v.isIntersecting) {
                mostra(v.target);
                osservatore.unobserve(v.target);
            }
        });
    }, { rootMargin: '0px 0px -12% 0px' });

    elementi.forEach((el) => {
        osservatore.observe(el);
        // Chi naviga con la tastiera non deve mai finire su qualcosa di ancora trasparente
        el.addEventListener('focusin', () => mostra(el), { once: true });
    });
}

// Dopo il caricamento, così il salto all'ancora dell'URL (#prenota) non sposta il focus altrove
window.addEventListener('load', () => focusIniziale());

// Menu principale: su schermi stretti diventa un pannello aperto dal bottone "Menu".
function menuMobile() {
    const bottone = document.querySelector('.apri-menu');
    const menu = document.getElementById('menu-principale');
    if (!bottone || !menu) return;

    const stretto = window.matchMedia('(max-width: 56rem)');
    bottone.hidden = false;

    const chiudi = () => {
        bottone.setAttribute('aria-expanded', 'false');
        menu.hidden = true;
    };
    const adatta = () => {
        if (stretto.matches) {
            chiudi();
        } else {
            menu.hidden = false;
        }
    };

    bottone.addEventListener('click', () => {
        const aperto = bottone.getAttribute('aria-expanded') === 'true';
        bottone.setAttribute('aria-expanded', String(!aperto));
        menu.hidden = aperto;
    });

    // Esc chiude il pannello e riporta il focus sul bottone
    document.addEventListener('keydown', (ev) => {
        if (ev.key === 'Escape' && stretto.matches && !menu.hidden) {
            chiudi();
            bottone.focus();
        }
    });

    stretto.addEventListener('change', adatta);
    adatta();
}

// Pagina mostra: scorrendo la prima schermata l'immagine sfuma nel colore della mostra.
function sfumaturaMostra() {
    if (!document.querySelector('.sfondo-mostra')) return;

    const radice = document.documentElement;
    let inAttesa = false;

    const aggiorna = () => {
        const p = Math.min(1, Math.max(0, window.scrollY / (window.innerHeight * 0.9)));
        radice.style.setProperty('--p', p.toFixed(3));
        inAttesa = false;
    };

    window.addEventListener('scroll', () => {
        if (!inAttesa) {
            inAttesa = true;
            requestAnimationFrame(aggiorna);
        }
    }, { passive: true });
    window.addEventListener('resize', aggiorna);
    aggiorna();
}

// Prenotazione: aggiorna il totale mentre si scelgono le quantità.
function totaleBiglietti() {
    const form = document.querySelector('[data-totale-biglietti]');
    if (!form) return;

    const uscita = form.querySelector('[data-totale]');
    const campi = form.querySelectorAll('input[data-prezzo]');
    const formato = new Intl.NumberFormat('it-IT', { style: 'currency', currency: 'EUR' });

    // Riavvia l'animazione "battito" del totale (classe in css/animazioni.css)
    const batti = () => {
        uscita.classList.remove('aggiornato');
        void uscita.offsetWidth;
        uscita.classList.add('aggiornato');
    };

    const calcola = () => {
        let totale = 0;
        let pezzi = 0;
        campi.forEach((c) => {
            const q = Math.max(0, parseInt(c.value, 10) || 0);
            totale += q * parseFloat(c.dataset.prezzo);
            pezzi += q;
        });
        uscita.textContent = pezzi === 0
            ? 'Nessun biglietto selezionato'
            : `${pezzi} ${pezzi === 1 ? 'biglietto' : 'biglietti'}, totale ${formato.format(totale)}`;
    };

    campi.forEach((c) => c.addEventListener('input', () => { calcola(); batti(); }));
    calcola();
}

// Dopo un invio non valido gestito dal server, porta il focus sul riepilogo degli errori.
function focusIniziale() {
    const el = document.querySelector('[data-focus-iniziale]');
    if (el) el.focus();
}
