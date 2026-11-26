// Kronos, terza versione: comportamenti di pagina. Il sito funziona anche senza JavaScript;
// qui si aggiungono il menu (in riga o a schermo intero), le comparse allo scorrimento, il totale
// dei biglietti, la macchina da scrivere della home e la pausa delle animazioni automatiche.
// Gli stati passano per classi e attributi; l'aspetto è nei CSS.
// La classe "js" su <html> la mette js/avvio.js; gli effetti legati allo scorrimento sono in
// js/scorrimento.js (pagine mostra) e js/puntini.js (home).

const movimentoRidotto = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
let animazioniFerme = false;

document.addEventListener('DOMContentLoaded', () => {
    menuNellaTestata();
    pausaAnimazioni();
    macchinaDaScrivere();
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

// Menu: le voci stanno nella testata finché ci entrano. Quando non ci stanno più (finestra
// stretta, testo ingrandito) la pagina prende la classe menu-compatto: il menu si nasconde
// e il bottone Menu lo apre a schermo intero. Mentre è aperto, pagina e piè di pagina sono
// inerti; il focus va sulla prima voce, Esc lo chiude e riporta il focus sul bottone.
function menuNellaTestata() {
    const testata = document.querySelector('.v2-testata');
    const bottone = document.querySelector('.v2-menu-bottone');
    const menu = document.getElementById('v2-menu');
    if (!testata || !bottone || !menu) return;

    const radice = document.documentElement;
    const fuori = document.querySelectorAll('main, footer');
    const imposta = (aperto) => {
        bottone.setAttribute('aria-expanded', String(aperto));
        menu.hidden = !aperto && radice.classList.contains('menu-compatto');
        radice.classList.toggle('menu-aperto', aperto);
        fuori.forEach((el) => { el.inert = aperto; });
    };

    // Si prova il menu in riga e si guarda se la testata trabocca
    const misura = () => {
        const aperto = bottone.getAttribute('aria-expanded') === 'true';
        radice.classList.remove('menu-compatto');
        menu.hidden = false;
        const compatto = testata.scrollWidth > testata.clientWidth;
        radice.classList.toggle('menu-compatto', compatto);
        bottone.hidden = !compatto;
        imposta(compatto && aperto);
    };

    misura();
    window.addEventListener('resize', misura);
    document.fonts?.ready.then(misura);   // i caratteri web cambiano la larghezza delle voci

    bottone.addEventListener('click', () => {
        const aperto = bottone.getAttribute('aria-expanded') !== 'true';
        imposta(aperto);
        if (aperto) menu.querySelector('a')?.focus();
    });
    document.addEventListener('keydown', (ev) => {
        if (ev.key === 'Escape' && bottone.getAttribute('aria-expanded') === 'true') {
            imposta(false);
            bottone.focus();
        }
    });
}

// Un bottone (anche ripetuto in più punti) ferma e riprende tutto ciò che si muove da solo
// per più di cinque secondi (WCAG 2.2.2). Chi chiede meno movimento non ne ha bisogno.
function pausaAnimazioni() {
    const bottoni = document.querySelectorAll('[data-pausa]');
    if (movimentoRidotto) return;

    bottoni.forEach((b) => {
        b.hidden = false;
        b.addEventListener('click', () => {
            animazioniFerme = !animazioniFerme;
            bottoni.forEach((x) => { x.textContent = animazioniFerme ? 'Riprendi le animazioni' : 'Ferma le animazioni'; });
            document.documentElement.classList.toggle('animazioni-ferme', animazioniFerme);
            document.dispatchEvent(new CustomEvent('kronos:pausa', { detail: animazioniFerme }));
        });
    });
}

// Home: la parola in corsivo del titolo si cancella e si riscrive, un mondo alla volta.
// Il titolo letto dai lettori di schermo è fisso (testo nascosto nel markup).
function macchinaDaScrivere() {
    const el = document.querySelector('[data-parole]');
    if (!el || movimentoRidotto) return;

    const parole = el.dataset.parole.split(',');
    let indice = 0;
    let testo = parole[0];
    let cancella = true;

    const passo = () => {
        if (animazioniFerme) {
            setTimeout(passo, 300);
            return;
        }
        if (cancella) {
            testo = testo.slice(0, -1);
            if (!testo) {
                cancella = false;
                indice = (indice + 1) % parole.length;
            }
        } else {
            testo = parole[indice].slice(0, testo.length + 1);
            if (testo === parole[indice]) {
                el.textContent = testo;
                cancella = true;
                setTimeout(passo, 2200);
                return;
            }
        }
        el.textContent = testo || '​'; // spazio invisibile: la riga non perde altezza
        setTimeout(passo, cancella ? 45 : 90);
    };
    setTimeout(passo, 2600);
}
