// Effetti legati allo scorrimento, con GSAP e ScrollTrigger (in js/vendor/).
// Caricato solo dalla home e dalle pagine mostra (vedi SCRIPT_SCORRIMENTO in php/header_footer.php).

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

gsap.matchMedia().add('(prefers-reduced-motion: no-preference)', () => {
    // Home: scendendo, il titolo si allontana verso l'alto
    const ingresso = document.querySelector('.ingresso-testo');
    if (ingresso) {
        gsap.to(ingresso, {
            yPercent: -18,
            opacity: 0,
            ease: 'none',
            scrollTrigger: { trigger: '.ingresso', start: 'top top', end: 'bottom top', scrub: true },
        });
    }

    // Home: le nicchie ad arco salgono un po' più lente del testo, come se fossero più lontane
    gsap.utils.toArray('.mondo .arco').forEach((arco) => {
        gsap.fromTo(arco, { y: 60 }, {
            y: -60,
            ease: 'none',
            scrollTrigger: { trigger: arco.closest('.mondo'), start: 'top bottom', end: 'bottom top', scrub: true },
        });
    });

    // Pagina mostra: il titolo d'apertura resta un attimo indietro e si dissolve nel velo
    const apertura = document.querySelector('.apertura .contenitore');
    if (apertura) {
        gsap.to(apertura, {
            y: () => window.innerHeight * 0.15,
            opacity: 0.2,
            ease: 'none',
            scrollTrigger: { trigger: '.apertura', start: 'top top', end: 'bottom top', scrub: true },
        });
    }
});
