// Home v2: il logo del museo fatto di puntini in 3D (Three.js) che, scorrendo, si apre in un alone
// attorno alla frase "Dal cielo all'abisso, un solo percorso". La sezione resta ferma sullo
// schermo mentre si scorre (position: sticky) e ScrollTrigger dà l'avanzamento, da 0 a 1.
// Chi chiede meno movimento vede l'alone già aperto e fermo; senza WebGL resta solo la frase.

(async () => {
    const sezione = document.querySelector('.v2-puntini');
    const scena3d = sezione?.querySelector('.v2-puntini-scena');
    const testo = sezione?.querySelector('.v2-puntini-testo');
    if (!scena3d) return;

    let THREE, renderer;
    try {
        THREE = await import('./vendor/three.module.js');
        renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true, powerPreference: 'low-power' });
    } catch {
        return;
    }

    const fermo = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
    renderer.setClearColor(0x000000, 0);

    const scena = new THREE.Scene();
    const camera = new THREE.PerspectiveCamera(45, 1, 0.1, 100);
    camera.position.z = 11;

    // Due forme con gli stessi puntini: il logo del museo (la sfera armillare di marchio_sfera()
    // in header_footer.php, ridisegnata a puntini) e un alone largo, più fitto verso l'esterno,
    // che lascia libero il centro per il testo
    const N = 8960;
    const logo = new Float32Array(N * 3);
    const alone = new Float32Array(N * 3);
    const caso = new Float32Array(N);
    const gira = new Float32Array(N);   // 1 per l'orbita e il pianetino, che girano come nel logo

    // Coordinate del logo: viewBox 48x48 con centro (24, 24), y verso il basso; S lo porta in scala 3D
    const S = 0.18, INCL = -35 * Math.PI / 180;
    const ruota = (x, y) => [x * Math.cos(INCL) - y * Math.sin(INCL), x * Math.sin(INCL) + y * Math.cos(INCL)];
    // [quanti puntini, funzione che dà un punto del tratto (x, y relativi al centro, z), spessore]
    const tratti = [
        [2600, () => { const t = Math.random() * 6.283; return [20 * Math.cos(t), 20 * Math.sin(t), 0]; }, 2.5],
        // l'ellisse 23x5 è un anello vero, inclinato in profondità: girando si vede in 3D
        [2600, () => { const t = Math.random() * 6.283; return [23 * Math.cos(t), 5 * Math.sin(t), 22.4 * Math.sin(t)]; }, 2],
        [700, () => { const [x, y] = ruota(0, (Math.random() - 0.5) * 46); return [x, y, 0]; }, 1.5],
        [2000, () => { const t = Math.random() * 6.283; return [...ruota(14 * Math.cos(t), 14 * Math.sin(t)), 0, 1]; }, 2],
        [360, () => { const [x, y] = ruota(9.9, -9.9), a = Math.random() * 6.283, d = Math.sqrt(Math.random()) * 2.6; return [x + d * Math.cos(a), y + d * Math.sin(a), 0, 1]; }, 0],
        [700, () => { const a = Math.random() * 6.283, d = Math.sqrt(Math.random()) * 4; return [d * Math.cos(a), d * Math.sin(a), 0]; }, 0],
    ];
    let k = 0;
    for (const [quanti, punto, spessore] of tratti) {
        for (let q = 0; q < quanti; q++, k++) {
            const [x, y, z, g = 0] = punto();
            const sbava = () => (Math.random() - 0.5) * spessore;
            logo[k * 3] = (x + sbava()) * S;
            logo[k * 3 + 1] = -(y + sbava()) * S;
            logo[k * 3 + 2] = z * S;
            gira[k] = g;

            const a = Math.random() * Math.PI * 2;
            const d = 3.9 + Math.pow(Math.random(), 0.55) * 6;
            alone[k * 3] = Math.cos(a) * d * 1.5;
            alone[k * 3 + 1] = Math.sin(a) * d;
            alone[k * 3 + 2] = (Math.random() - 0.5) * 4;
            caso[k] = Math.random();
        }
    }
    const geometria = new THREE.BufferGeometry();
    geometria.setAttribute('position', new THREE.BufferAttribute(logo, 3));
    geometria.setAttribute('alone', new THREE.BufferAttribute(alone, 3));
    geometria.setAttribute('caso', new THREE.BufferAttribute(caso, 1));
    geometria.setAttribute('gira', new THREE.BufferAttribute(gira, 1));

    const materiale = new THREE.ShaderMaterial({
        transparent: true,
        depthWrite: false,
        uniforms: {
            uProgresso: { value: 0 },
            uScala: { value: 1 },
            uTempo: { value: 0 },
            uColore: { value: new THREE.Color() }, // il colore del testo della pagina, vedi coloraPunti
        },
        vertexShader: /* glsl */ `
            attribute vec3 alone;
            attribute float caso;
            attribute float gira;
            uniform float uProgresso;
            uniform float uTempo;
            uniform float uScala;
            varying float vAlfa;
            void main() {
                // ogni puntino parte un po' prima o dopo degli altri: l'apertura non è rigida
                float p = smoothstep(caso * 0.4, caso * 0.4 + 0.6, uProgresso);
                // l'orbita del logo gira attorno al centro, come .marchio-gira nell'icona
                float a = gira * uTempo * 0.9;
                vec3 forma = vec3(mat2(cos(a), sin(a), -sin(a), cos(a)) * position.xy, position.z);
                vec4 mv = modelViewMatrix * vec4(mix(forma, alone, p), 1.0);
                gl_Position = projectionMatrix * mv;
                gl_PointSize = uScala * (1.2 + caso * 1.6) * (18.0 / -mv.z);
                vAlfa = clamp(1.4 - (-mv.z - 7.0) / 9.0, 0.15, 1.0); // i puntini lontani sbiadiscono
            }`,
        fragmentShader: /* glsl */ `
            uniform vec3 uColore;
            varying float vAlfa;
            void main() {
                vec2 c = gl_PointCoord - 0.5;
                if (dot(c, c) > 0.25) discard;
                gl_FragColor = vec4(uColore, vAlfa);
            }`,
    });
    const punti = new THREE.Points(geometria, materiale);
    // Puntini del colore del testo: inchiostro di giorno, carta in modalità notte
    const coloraPunti = () => materiale.uniforms.uColore.value.set(getComputedStyle(sezione).getPropertyValue('--testo').trim());
    coloraPunti();
    scena.add(punti);

    let tempo = 0, ultimo = null;
    let obiettivo = fermo ? 1 : 0;  // avanzamento dato dallo scorrimento
    let progresso = obiettivo;      // lo stesso, ammorbidito
    let inPausa = false;

    const disegna = (t) => {
        if (t !== undefined) {
            if (ultimo !== null && !inPausa) tempo += Math.min((t - ultimo) / 1000, 0.05);
            ultimo = t;
        }
        progresso += (obiettivo - progresso) * 0.08;
        // il logo all'inizio è di fronte e oscilla piano, poi l'alone si inclina e ruota
        punti.rotation.x = progresso * 0.2;
        punti.rotation.y = Math.sin(tempo * 0.5) * 0.45 * (1 - progresso);
        punti.rotation.z = (tempo * 0.18 + 1.4) * progresso;
        materiale.uniforms.uTempo.value = tempo;
        materiale.uniforms.uProgresso.value = progresso;
        renderer.render(scena, camera);
    };

    const fisso = sezione.querySelector('.v2-puntini-fisso');
    sezione.classList.add(fermo ? 'con-puntini-fermo' : 'con-puntini');
    scena3d.append(renderer.domElement);

    const adatta = () => {
        const { width, height } = fisso.getBoundingClientRect();
        if (!width || !height) return;
        renderer.setSize(width, height, false);
        camera.aspect = width / height;
        camera.updateProjectionMatrix();
        materiale.uniforms.uScala.value = renderer.getPixelRatio() * Math.min(1.4, height / 800);
        disegna();
    };
    new ResizeObserver(adatta).observe(fisso);
    document.addEventListener('kronos:schema', () => { coloraPunti(); disegna(); });

    if (fermo) return;

    // La frase compare quando il logo si è aperto abbastanza da lasciarle spazio
    if (window.gsap && window.ScrollTrigger) {
        gsap.registerPlugin(ScrollTrigger);
        ScrollTrigger.create({
            trigger: sezione,
            start: 'top top',
            end: 'bottom bottom',
            onUpdate: (s) => { obiettivo = s.progress; },
        });
        gsap.fromTo(testo, { opacity: 0, y: 40 }, {
            opacity: 1,
            y: 0,
            ease: 'none',
            // percentuali dell'altezza della sezione: compare a metà apertura ed è piena prima della fine
            scrollTrigger: { trigger: sezione, start: 'top+=25% top', end: 'top+=55% top', scrub: true },
        });
    }

    document.addEventListener('kronos:pausa', (ev) => { inPausa = ev.detail; });

    // Il disegno gira solo quando la sezione è sullo schermo
    new IntersectionObserver(([voce]) => {
        ultimo = null;
        renderer.setAnimationLoop(voce.isIntersecting ? disegna : null);
    }).observe(sezione);
})();
