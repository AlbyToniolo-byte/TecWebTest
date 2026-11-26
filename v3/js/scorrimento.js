// Effetti legati allo scorrimento, con GSAP e ScrollTrigger (in js/vendor/).
// Caricato solo dalle pagine mostra (vedi SCRIPT_SCORRIMENTO in php/header_footer.php).

gsap.registerPlugin(ScrollTrigger);

// Pagina mostra: scorrendo la prima schermata l'immagine sfuma nel colore della mostra.
// È una dissolvenza, non un movimento, quindi resta anche per chi chiede meno movimento.
// --p va da 0 a 1 e guida il velo e lo spostamento dell'immagine in css/layout.css.
if (document.querySelector('.sfondo-mostra')) {
    gsap.fromTo(document.documentElement, { '--p': 0 }, {
        '--p': 1,
        ease: 'none',
        scrollTrigger: { start: 0, end: () => window.innerHeight * 0.9, scrub: true },
    });
}
