// Home v2: una sfera di puntini in 3D (Three.js) che gira e, scorrendo, si apre in un alone
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

    // Due forme con gli stessi puntini: un toro a griglia regolare (l'effetto retino)
    // e un alone largo, più fitto verso l'esterno, che lascia libero il centro per il testo
    const GIRI = 160, SEZIONI = 56, N = GIRI * SEZIONI;
    const toro = new Float32Array(N * 3);
    const alone = new Float32Array(N * 3);
    const caso = new Float32Array(N);
    for (let i = 0; i < GIRI; i++) {
        for (let j = 0; j < SEZIONI; j++) {
            const k = i * SEZIONI + j;
            const u = (i / GIRI) * Math.PI * 2;
            const v = (j / SEZIONI) * Math.PI * 2;
            const R = 2.6, r = 1.05;
            toro[k * 3] = (R + r * Math.cos(v)) * Math.cos(u);
            toro[k * 3 + 1] = (R + r * Math.cos(v)) * Math.sin(u);
            toro[k * 3 + 2] = r * Math.sin(v);

            const a = Math.random() * Math.PI * 2;
            const d = 3.9 + Math.pow(Math.random(), 0.55) * 6;
            alone[k * 3] = Math.cos(a) * d * 1.5;
            alone[k * 3 + 1] = Math.sin(a) * d;
            alone[k * 3 + 2] = (Math.random() - 0.5) * 4;
            caso[k] = Math.random();
        }
    }
    const geometria = new THREE.BufferGeometry();
    geometria.setAttribute('position', new THREE.BufferAttribute(toro, 3));
    geometria.setAttribute('alone', new THREE.BufferAttribute(alone, 3));
    geometria.setAttribute('caso', new THREE.BufferAttribute(caso, 1));

    const materiale = new THREE.ShaderMaterial({
        transparent: true,
        depthWrite: false,
        uniforms: {
            uProgresso: { value: 0 },
            uScala: { value: 1 },
            uColore: { value: new THREE.Color(0x161616) },
        },
        vertexShader: /* glsl */ `
            attribute vec3 alone;
            attribute float caso;
            uniform float uProgresso;
            uniform float uScala;
            varying float vAlfa;
            void main() {
                // ogni puntino parte un po' prima o dopo degli altri: l'apertura non è rigida
                float p = smoothstep(caso * 0.4, caso * 0.4 + 0.6, uProgresso);
                vec4 mv = modelViewMatrix * vec4(mix(position, alone, p), 1.0);
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
        punti.rotation.x = 1.0 - progresso * 0.8;
        punti.rotation.z = tempo * 0.18 + progresso * 1.4;
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

    if (fermo) return;

    // La frase compare quando la sfera si è aperta abbastanza da lasciarle spazio
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
