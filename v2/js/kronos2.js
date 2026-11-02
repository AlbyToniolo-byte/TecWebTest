// Kronos, seconda versione: menu a schermo intero, macchina da scrivere della home
// e pausa delle animazioni automatiche. Gli stati passano per classi e attributi;
// l'aspetto è in css/kronos2.css.

const movimentoRidotto = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
let animazioniFerme = false;

document.addEventListener('DOMContentLoaded', () => {
    menuSchermoIntero();
    pausaAnimazioni();
    macchinaDaScrivere();
});

// Menu: pannello a schermo intero. Mentre è aperto, pagina e piè di pagina sono inerti,
// così il Tab resta nel menu; Esc lo chiude e riporta il focus sul bottone.
function menuSchermoIntero() {
    const bottone = document.querySelector('.v2-menu-bottone');
    const menu = document.getElementById('v2-menu');
    if (!bottone || !menu) return;

    const fuori = document.querySelectorAll('main, footer');
    const imposta = (aperto) => {
        bottone.setAttribute('aria-expanded', String(aperto));
        menu.hidden = !aperto;
        document.documentElement.classList.toggle('menu-aperto', aperto);
        fuori.forEach((el) => { el.inert = aperto; });
    };

    bottone.hidden = false;
    imposta(false);
    bottone.addEventListener('click', () => imposta(bottone.getAttribute('aria-expanded') !== 'true'));
    document.addEventListener('keydown', (ev) => {
        if (ev.key === 'Escape' && !menu.hidden) {
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
