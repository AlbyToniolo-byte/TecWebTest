// Home: sfera armillare in 3D con Three.js, lo strumento con cui si misuravano il cielo e il tempo.
// Gli anelli si aprono all'arrivo (GSAP), poi la sfera gira lenta e cinque sfere, i cinque mondi,
// percorrono l'eclittica. Scorrendo si inclina, il puntatore la sposta appena.
// Si muove da sola, quindi ha un bottone per fermarla (WCAG 2.2.2). Chi chiede meno movimento
// la trova ferma, senza animazione d'arrivo, e può avviarla con lo stesso bottone. Senza WebGL la colonna resta nascosta e la prima schermata mostra solo il testo.

(async () => {
    const contenitore = document.querySelector('.ingresso-scena');
    const bottone = contenitore?.querySelector('[data-ferma-sfera]');
    if (!contenitore || !bottone) return;

    let THREE, renderer;
    try {
        THREE = await import('./vendor/three.module.js');
        renderer = new THREE.WebGLRenderer({ antialias: true, powerPreference: 'low-power' });
    } catch {
        return;
    }

    // Colori presi dalla pagina (--sfondo e --testo), così la sfera segue anche la modalità notte
    const colore = (nome) => getComputedStyle(contenitore).getPropertyValue(nome).trim();
    const fermo = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const gradi = THREE.MathUtils.degToRad;

    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
    renderer.domElement.setAttribute('aria-hidden', 'true');

    const scena = new THREE.Scene();
    scena.fog = new THREE.Fog(0xffffff, 9.5, 15.5); // le parti lontane sfumano nel fondo: dà profondità con due soli colori
    const camera = new THREE.PerspectiveCamera(35, 1, 0.1, 50);
    camera.position.set(0, 0.9, 12.5);
    camera.lookAt(0, 0, 0);

    const tratto = new THREE.MeshBasicMaterial();
    const coloraScena = () => {
        const fondo = colore('--sfondo');
        renderer.setClearColor(fondo);
        scena.fog.color.set(fondo);
        tratto.color.set(colore('--testo'));
    };
    coloraScena();
    const anelli = []; // si aprono uno dopo l'altro all'arrivo
    const anello = (raggio, spessore = 0.025) => {
        const m = new THREE.Mesh(new THREE.TorusGeometry(raggio, spessore, 8, 200), tratto);
        anelli.push(m);
        return m;
    };

    // Sfera intera: si inclina con lo scorrimento e segue il puntatore
    const sfera = new THREE.Group();
    scena.add(sfera);
    sfera.rotation.y = gradi(-20); // posa di partenza, di tre quarti

    // Telaio fisso: meridiano graduato e orizzonte
    const meridiano = anello(3.05, 0.04);
    const orizzonte = anello(3.25, 0.035);
    orizzonte.rotation.x = gradi(90);
    sfera.add(meridiano, orizzonte);

    const tacche = new THREE.InstancedMesh(new THREE.BoxGeometry(0.02, 0.16, 0.02), tratto, 72);
    const posa = new THREE.Object3D();
    for (let i = 0; i < 72; i++) {
        const a = (i / 72) * Math.PI * 2;
        posa.position.set(Math.cos(a) * 2.9, Math.sin(a) * 2.9, 0);
        posa.rotation.z = a + Math.PI / 2;
        posa.scale.y = i % 6 === 0 ? 1.8 : 1; // una tacca lunga ogni 30 gradi
        posa.updateMatrix();
        tacche.setMatrixAt(i, posa.matrix);
    }
    meridiano.add(tacche);

    // Cielo mobile, inclinato come l'asse del mondo: equatore, coluri, tropici, eclittica
    const inclinazione = new THREE.Group();
    inclinazione.rotation.z = gradi(-35);
    sfera.add(inclinazione);
    const cielo = new THREE.Group();
    inclinazione.add(cielo);

    const R = 2.6;
    const equatore = anello(R);
    equatore.rotation.x = gradi(90);
    const coluro1 = anello(R, 0.018);
    const coluro2 = anello(R, 0.018);
    coluro2.rotation.y = gradi(90);
    cielo.add(equatore, coluro1, coluro2);

    const obliquita = gradi(23.4);
    for (const verso of [1, -1]) {
        const tropico = anello(R * Math.cos(obliquita), 0.015);
        tropico.rotation.x = gradi(90);
        tropico.position.y = verso * R * Math.sin(obliquita);
        cielo.add(tropico);
    }

    // Eclittica: una fascia doppia su cui corrono i cinque mondi
    const eclittica = new THREE.Group();
    eclittica.rotation.x = gradi(90) - obliquita;
    eclittica.add(anello(R + 0.06, 0.02), anello(R - 0.06, 0.02));
    cielo.add(eclittica);

    const mondi = [0, 1, 2, 3, 4].map((i) => {
        const m = new THREE.Mesh(new THREE.SphereGeometry(0.12 + i * 0.015, 20, 14), tratto);
        m.userData = { angolo: (i / 5) * Math.PI * 2, velocita: 0.18 + i * 0.07 };
        eclittica.add(m);
        return m;
    });

    // Asse e Terra al centro
    const asse = new THREE.Mesh(new THREE.CylinderGeometry(0.02, 0.02, 7, 8), tratto);
    inclinazione.add(asse, new THREE.Mesh(new THREE.SphereGeometry(0.42, 32, 20), tratto));

    // --- Disegno -------------------------------------------------------------------

    let tempo = 0, ultimo = null;
    let discesa = 0;            // 0 in cima alla pagina, 1 quando la prima schermata è passata
    let puntoX = 0, puntoY = 0; // posizione del puntatore, da -1 a 1

    const posizionaMondi = () => {
        for (const m of mondi) {
            const a = m.userData.angolo + tempo * m.userData.velocita;
            m.position.set(Math.cos(a) * R, Math.sin(a) * R, 0);
        }
    };

    const disegna = (t) => {
        if (t !== undefined) {
            tempo += ultimo === null ? 0 : Math.min((t - ultimo) / 1000, 0.05);
            ultimo = t;
        }
        cielo.rotation.y = tempo * 0.12;
        posizionaMondi();
        sfera.rotation.y += (gradi(-20) + puntoX * 0.25 - sfera.rotation.y) * 0.05;
        sfera.rotation.x += (discesa * 0.9 + puntoY * 0.15 - sfera.rotation.x) * 0.08;
        renderer.render(scena, camera);
    };

    contenitore.prepend(renderer.domElement);
    contenitore.hidden = false;

    const adatta = () => {
        const lato = renderer.domElement.clientWidth;
        if (!lato) return;
        renderer.setSize(lato, lato, false);
        disegna(); // ridimensionare svuota la tela: va ridisegnata anche da ferma o in pausa
    };
    new ResizeObserver(adatta).observe(contenitore);
    // Modalità notte cambiata (js/schema.js): nuovi colori e un fotogramma, anche se la sfera è ferma
    document.addEventListener('kronos:schema', () => { coloraScena(); disegna(); });
    adatta();

    // Arrivo: gli anelli si aprono dal centro, uno dopo l'altro
    if (window.gsap && !fermo) {
        gsap.from(anelli.map((a) => a.scale), {
            x: 0.001, y: 0.001, z: 0.001,
            duration: 1.4,
            ease: 'back.out(1.4)',
            stagger: 0.07,
        });
        gsap.from(inclinazione.rotation, { z: 0, duration: 2.2, ease: 'power3.out', delay: 0.3 });
    }

    if (window.ScrollTrigger) {
        ScrollTrigger.create({
            trigger: '.ingresso',
            start: 'top top',
            end: 'bottom top',
            onUpdate: (s) => { discesa = s.progress; },
        });
    }
    window.addEventListener('pointermove', (ev) => {
        puntoX = (ev.clientX / window.innerWidth) * 2 - 1;
        puntoY = (ev.clientY / window.innerHeight) * 2 - 1;
    }, { passive: true });

    // Il disegno gira solo se la sfera è visibile e non è stata fermata
    let inPausa = fermo, visibile = true;
    if (fermo) bottone.textContent = "Avvia l'animazione";
    const aggiornaCiclo = () => {
        ultimo = null;
        renderer.setAnimationLoop(!inPausa && visibile ? disegna : null);
    };
    bottone.addEventListener('click', () => {
        inPausa = !inPausa;
        bottone.textContent = inPausa ? "Riprendi l'animazione" : "Ferma l'animazione";
        aggiornaCiclo();
    });
    new IntersectionObserver(([voce]) => {
        visibile = voce.isIntersecting;
        aggiornaCiclo();
    }).observe(contenitore);
})();
