# Genera le immagini dei prodotti dello shop, ognuna con i colori della sua mostra (refs.md).
# Uso: python3 docs/genera_prodotti.py img/prodotti
import os, sys, math, random

OUT = sys.argv[1]
os.makedirs(OUT, exist_ok=True)
W, H = 800, 600


def svg(sfondo, corpo, defs=""):
    return (f'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 {W} {H}">'
            f'<defs>{defs}</defs><rect width="{W}" height="{H}" fill="{sfondo}"/>{corpo}</svg>')


def ombra(cx, cy, rx, colore, opacita=0.35):
    return f'<ellipse cx="{cx}" cy="{cy}" rx="{rx}" ry="{rx * 0.12:.0f}" fill="{colore}" opacity="{opacita}"/>'


def stelle(n, seed, x0, y0, x1, y1, colore, rmax=2.2):
    r = random.Random(seed)
    return "".join(f'<circle cx="{r.uniform(x0, x1):.0f}" cy="{r.uniform(y0, y1):.0f}" r="{r.uniform(0.8, rmax):.1f}" fill="{colore}"/>'
                   for _ in range(n))


def libro(copertina, dorso, pagine, decoro):
    """Libro in piedi, leggermente ruotato, con dorso visibile a sinistra."""
    b = '<g transform="translate(400 300) rotate(-4) translate(-150 -205)">'
    b += f'<rect x="-34" y="6" width="40" height="404" fill="{dorso}"/>'
    b += f'<rect x="0" y="0" width="300" height="410" rx="6" fill="{copertina}"/>'
    b += f'<rect x="294" y="10" width="12" height="390" fill="{pagine}"/>'
    b += decoro
    return b + '</g>'


# --------------------------------------------------------------- Dinosauri
def kit_scavo():
    oliva, foresta, ossa, caramello, rame = "#606c38", "#283618", "#fefae0", "#dda15e", "#bc6c25"
    b = ombra(400, 470, 260, foresta)
    # blocco di gesso
    b += f'<path d="M170,300 L560,270 L620,330 L610,470 L200,480 L150,420 Z" fill="{ossa}"/>'
    b += f'<path d="M560,270 L620,330 L610,470 L560,420 Z" fill="{caramello}" opacity="0.45"/>'
    for x, y, r in ((260, 360, 10), (480, 330, 7), (530, 420, 12), (230, 440, 6)):
        b += f'<circle cx="{x}" cy="{y}" r="{r}" fill="{caramello}" opacity="0.35"/>'
    # dente che affiora
    b += f'<path d="M340,330 C360,250 400,190 440,170 C430,230 440,290 470,330 Z" fill="{caramello}"/>'
    b += f'<path d="M440,170 C430,230 440,290 470,330 L455,330 C430,290 425,240 440,170 Z" fill="{rame}"/>'
    # scalpello
    b += f'<g transform="rotate(-28 640 220)"><rect x="560" y="210" width="170" height="22" rx="4" fill="{foresta}"/>'
    b += f'<polygon points="560,210 520,221 560,232" fill="{ossa}"/></g>'
    # pennello
    b += f'<g transform="rotate(18 150 230)"><rect x="70" y="222" width="150" height="16" rx="8" fill="{rame}"/>'
    b += f'<rect x="220" y="218" width="24" height="24" fill="{foresta}"/><path d="M244,216 L300,206 L300,254 L244,244 Z" fill="{ossa}"/></g>'
    return svg(oliva, b)


def atlante_dinosauri():
    oliva, foresta, ossa, caramello, rame = "#606c38", "#283618", "#fefae0", "#dda15e", "#bc6c25"
    d = f'<rect x="30" y="40" width="240" height="8" fill="{caramello}"/><rect x="30" y="362" width="240" height="8" fill="{caramello}"/>'
    d += f'<rect x="60" y="78" width="180" height="22" rx="3" fill="{ossa}"/><rect x="90" y="112" width="120" height="12" rx="3" fill="{caramello}"/>'
    # sauropode in copertina
    d += f'<g fill="{caramello}"><ellipse cx="150" cy="270" rx="70" ry="34"/><rect x="100" y="280" width="16" height="50" rx="5"/>'
    d += '<rect x="130" y="285" width="16" height="45" rx="5"/><rect x="165" y="285" width="16" height="45" rx="5"/><rect x="190" y="280" width="16" height="50" rx="5"/>'
    d += '<path d="M200,255 C220,220 228,190 238,160 L254,163 C246,200 236,240 222,280 Z"/><ellipse cx="250" cy="158" rx="18" ry="9"/>'
    d += '<path d="M85,262 C60,270 40,284 20,300 C50,295 70,288 95,282 Z"/></g>'
    b = ombra(400, 520, 220, foresta) + libro(foresta, rame, ossa, d)
    return svg(oliva, b)


# --------------------------------------------------------------- Mitologia
def odissea():
    bordeaux, rosa, beige, bronzo, tortora = "#5e0b15", "#90323d", "#d9cab3", "#bc8034", "#8c7a6b"
    # cornice a meandro
    m = f'<rect x="22" y="22" width="256" height="366" fill="none" stroke="{bronzo}" stroke-width="6"/>'
    for i in range(11):
        x = 40 + i * 21
        m += f'<path d="M{x},372 v-14 h14 v8 h-6" fill="none" stroke="{bronzo}" stroke-width="4"/>'
        m += f'<path d="M{x},38 v14 h14 v-8 h-6" fill="none" stroke="{bronzo}" stroke-width="4"/>'
    m += f'<rect x="60" y="80" width="180" height="24" rx="3" fill="{beige}"/><rect x="95" y="114" width="110" height="12" rx="3" fill="{bronzo}"/>'
    # nave
    m += f'<g fill="{bronzo}"><path d="M70,290 L230,290 L205,320 L95,320 Z"/><rect x="146" y="180" width="6" height="110"/>'
    m += f'<path d="M152,186 L215,276 L152,276 Z" fill="{beige}"/><path d="M146,196 L95,276 L146,276 Z" fill="{beige}"/>'
    m += '<path d="M230,290 C245,280 250,265 248,250 L240,252 C242,266 236,278 222,288 Z"/></g>'
    for x in range(80, 230, 26):
        m += f'<path d="M{x},336 q13,-10 26,0" fill="none" stroke="{beige}" stroke-width="3"/>'
    b = ombra(400, 520, 220, bordeaux) + libro(bordeaux, rosa, beige, m)
    return svg(tortora, b)


def cavallo_legno():
    bordeaux, rosa, beige, bronzo, tortora = "#5e0b15", "#90323d", "#d9cab3", "#bc8034", "#8c7a6b"
    b = ombra(400, 500, 250, bordeaux)
    g = f'<g fill="{bronzo}">'
    g += '<rect x="200" y="440" width="380" height="24" rx="4"/>'
    for x in (240, 340, 440, 540):
        g += f'<circle cx="{x}" cy="476" r="22" fill="{tortora}"/><circle cx="{x}" cy="476" r="7" fill="{bordeaux}"/>'
    for x in (250, 300, 460, 510):
        g += f'<rect x="{x}" y="330" width="30" height="115"/>'
    g += '<polygon points="230,230 520,222 530,340 225,344"/>'
    g += '<polygon points="450,240 520,230 580,90 525,76"/>'
    g += '<polygon points="515,58 580,48 640,130 628,156 582,148 568,100"/>'
    g += '<polygon points="525,62 538,26 552,58"/><polygon points="550,56 566,22 578,54"/>'
    g += '<path d="M232,240 C196,262 186,312 204,362 L216,360 C206,320 214,284 240,264 Z"/>'
    g += '</g>'
    # assi del legno e incastri
    g += f'<g stroke="{rosa}" stroke-width="3" fill="none">'
    for y in (258, 286, 314):
        g += f'<line x1="230" y1="{y}" x2="526" y2="{y - 4}"/>'
    for x in (300, 370, 440):
        g += f'<line x1="{x}" y1="228" x2="{x + 1}" y2="342"/>'
    g += '<line x1="475" y1="236" x2="532" y2="86"/>'
    g += '</g>'
    g += f'<rect x="600" y="96" width="14" height="8" fill="{bordeaux}"/>'
    # pezzi sciolti accanto, da montare
    g += f'<rect x="96" y="470" width="80" height="14" rx="3" fill="{bronzo}" transform="rotate(-12 136 477)"/>'
    g += f'<rect x="90" y="440" width="60" height="14" rx="3" fill="{rosa}" transform="rotate(8 120 447)"/>'
    return svg(beige, b + g)


def kylix():
    bordeaux, rosa, beige, bronzo, tortora = "#5e0b15", "#90323d", "#d9cab3", "#bc8034", "#8c7a6b"
    b = ombra(400, 470, 150, bordeaux)
    # piede e stelo
    b += f'<path d="M330,465 C350,450 380,440 385,400 L415,400 C420,440 450,450 470,465 Z" fill="{bordeaux}"/>'
    # anse
    b += f'<path d="M180,300 C120,290 120,250 170,250" fill="none" stroke="{bordeaux}" stroke-width="16" stroke-linecap="round"/>'
    b += f'<path d="M620,300 C680,290 680,250 630,250" fill="none" stroke="{bordeaux}" stroke-width="16" stroke-linecap="round"/>'
    # coppa
    b += f'<path d="M150,270 L650,270 C640,350 540,405 400,405 C260,405 160,350 150,270 Z" fill="{bordeaux}"/>'
    b += f'<ellipse cx="400" cy="270" rx="250" ry="34" fill="{bronzo}"/>'
    b += f'<ellipse cx="400" cy="270" rx="225" ry="26" fill="{bordeaux}"/>'
    # figure rosse sulla parete esterna: un fregio di onde e due figure stilizzate
    for i in range(9):
        x = 210 + i * 42
        b += f'<path d="M{x},318 q12,-16 24,0 q-6,10 -14,4" fill="none" stroke="{bronzo}" stroke-width="5"/>'
    b += f'<g fill="{bronzo}"><circle cx="320" cy="356" r="9"/><path d="M312,366 L328,366 L336,396 L304,396 Z"/>'
    b += '<circle cx="480" cy="356" r="9"/><path d="M472,366 L488,366 L496,396 L464,396 Z"/>'
    b += '<rect x="340" y="370" width="120" height="4" rx="2"/></g>'
    return svg(beige, b)


# --------------------------------------------------------------- Era glaciale
def mammut_peluche():
    jet, blu, baby, icy, azzurro = "#243640", "#557e96", "#88ccf1", "#a5d6f1", "#dcedf6"
    b = ombra(400, 500, 210, jet)
    g = f'<g fill="{blu}">'
    g += '<ellipse cx="360" cy="360" rx="170" ry="120"/>'
    g += '<circle cx="520" cy="290" r="90"/>'
    for x in (240, 310, 410, 470):
        g += f'<rect x="{x}" y="400" width="62" height="96" rx="28"/>'
    g += '<path d="M575,330 C610,380 600,440 560,470 C545,480 530,470 540,456 C568,430 572,390 548,350 Z"/>'
    g += '</g>'
    # pelo: ciuffi più chiari
    for i in range(14):
        x = 210 + i * 22
        g += f'<path d="M{x},440 q10,22 20,0" fill="none" stroke="{baby}" stroke-width="5" stroke-linecap="round"/>'
    g += f'<path d="M470,215 q20,-30 50,-14 q20,-24 46,-4" fill="none" stroke="{baby}" stroke-width="7" stroke-linecap="round"/>'
    g += f'<ellipse cx="470" cy="300" rx="34" ry="46" fill="{baby}"/>'
    g += f'<path d="M560,360 C600,400 650,396 668,350" fill="none" stroke="{azzurro}" stroke-width="16" stroke-linecap="round"/>'
    g += f'<circle cx="548" cy="278" r="9" fill="{jet}"/><circle cx="551" cy="275" r="3" fill="{azzurro}"/>'
    # cartellino di stoffa
    g += f'<rect x="210" y="330" width="40" height="28" rx="4" fill="{azzurro}" transform="rotate(-10 230 344)"/>'
    return svg(icy, b + g)


def tazza():
    jet, blu, baby, icy, azzurro = "#243640", "#557e96", "#88ccf1", "#a5d6f1", "#dcedf6"
    b = ombra(390, 480, 190, blu, 0.6)
    b += f'<path d="M540,250 C620,250 630,380 540,390" fill="none" stroke="{azzurro}" stroke-width="30"/>'
    b += f'<path d="M240,170 L540,170 L530,450 C530,470 515,480 495,480 L285,480 C265,480 250,470 250,450 Z" fill="{azzurro}"/>'
    b += f'<ellipse cx="390" cy="170" rx="150" ry="22" fill="{icy}"/><ellipse cx="390" cy="172" rx="132" ry="15" fill="{jet}"/>'
    # metà alta: ghiaccio; metà bassa: steppa che compare col calore
    b += f'<path d="M252,270 L300,240 L340,268 L390,226 L440,266 L480,238 L536,272 L534,330 L254,330 Z" fill="{baby}"/>'
    b += f'<path d="M254,330 L534,330 L531,400 L255,400 Z" fill="{blu}"/>'
    b += f'<g fill="{jet}"><ellipse cx="350" cy="372" rx="34" ry="20"/><circle cx="384" cy="360" r="16"/>'
    b += '<rect x="326" y="380" width="10" height="18"/><rect x="360" y="380" width="10" height="18"/>'
    b += '<path d="M396,366 C410,380 408,396 398,402 L394,396 C400,390 400,380 390,372 Z"/></g>'
    # vapore
    for x in (340, 390, 440):
        b += f'<path d="M{x},140 c-16,-22 16,-34 0,-60" fill="none" stroke="{azzurro}" stroke-width="7" stroke-linecap="round"/>'
    return svg(jet, b)


# --------------------------------------------------------------- Atlantide
def platone():
    avocado, maturo, acqua, teal, bonnie, blu = "#d9ed92", "#b5e48c", "#52b69a", "#34a0a4", "#168aad", "#184e77"
    d = f'<rect x="0" y="60" width="300" height="70" fill="{avocado}"/>'
    d += f'<rect x="40" y="80" width="150" height="14" rx="3" fill="{blu}"/><rect x="40" y="104" width="90" height="10" rx="3" fill="{teal}"/>'
    # onde e città sommersa
    for row, y in enumerate((210, 240, 270)):
        for i in range(5):
            x = 22 + i * 48 + (24 if row % 2 else 0)
            d += f'<path d="M{x},{y} q12,-14 24,0 q12,14 24,0" fill="none" stroke="{acqua}" stroke-width="4"/>'
    d += f'<g fill="{bonnie}"><rect x="70" y="310" width="160" height="10"/><rect x="80" y="320" width="14" height="50"/>'
    d += '<rect x="113" y="320" width="14" height="50"/><rect x="146" y="320" width="14" height="50"/><rect x="179" y="320" width="14" height="50"/>'
    d += '<rect x="206" y="320" width="14" height="50"/><polygon points="66,310 150,276 234,310"/></g>'
    b = ombra(400, 520, 220, blu, 0.5) + libro(blu, bonnie, avocado, d)
    return svg(maturo, b)


# --------------------------------------------------------------- Mondo Celeste
def planisfero():
    nero, blu, arancione, grigio, bianco = "#000000", "#14213d", "#fca311", "#e5e5e5", "#ffffff"
    b = ombra(400, 530, 230, nero, 0.3)
    b += f'<rect x="170" y="70" width="460" height="460" rx="24" fill="{grigio}"/>'
    b += f'<circle cx="400" cy="300" r="200" fill="{blu}"/>'
    b += stelle(120, 5, 230, 130, 570, 470, bianco)
    # costellazione e eclittica
    b += f'<polyline points="300,220 340,250 330,300 380,310 420,280" fill="none" stroke="{arancione}" stroke-width="3"/>'
    b += f'<ellipse cx="400" cy="300" rx="170" ry="120" fill="none" stroke="{arancione}" stroke-width="2" stroke-dasharray="8 8" transform="rotate(-20 400 300)"/>'
    # finestra ovale della maschera: fuori dalla finestra il disco è coperto
    b += f'<path d="M170,70 h460 v460 h-460 Z M400,170 a190,140 0 1 0 0.1,0 Z" fill="{grigio}" fill-rule="evenodd"/>'
    b += f'<ellipse cx="400" cy="310" rx="190" ry="140" fill="none" stroke="{blu}" stroke-width="4"/>'
    # tacche dei mesi sul bordo
    for i in range(24):
        a = math.radians(i * 15)
        x1, y1 = 400 + math.cos(a) * 214, 300 + math.sin(a) * 214
        x2, y2 = 400 + math.cos(a) * 226, 300 + math.sin(a) * 226
        b += f'<line x1="{x1:.0f}" y1="{y1:.0f}" x2="{x2:.0f}" y2="{y2:.0f}" stroke="{blu}" stroke-width="3"/>'
    b += f'<circle cx="400" cy="300" r="10" fill="{arancione}"/>'
    return svg(arancione, b)


def poster():
    nero, blu, arancione, grigio, bianco = "#000000", "#14213d", "#fca311", "#e5e5e5", "#ffffff"
    b = f'<rect x="232" y="62" width="336" height="452" fill="{nero}" opacity="0.15"/>'
    b += f'<rect x="220" y="50" width="336" height="452" fill="{nero}"/>'
    b += f'<rect x="236" y="66" width="304" height="420" fill="{blu}"/>'
    b += stelle(140, 9, 246, 76, 530, 420, bianco, 1.8)
    for punti in (((280, 130), (320, 160), (310, 210), (360, 220), (400, 190)),
                  ((420, 280), (470, 260), (500, 300), (460, 340), (420, 280)),
                  ((290, 320), (330, 360), (300, 400))):
        b += '<polyline points="' + " ".join(f"{x},{y}" for x, y in punti) + f'" fill="none" stroke="{arancione}" stroke-width="2"/>'
        for x, y in punti:
            b += f'<circle cx="{x}" cy="{y}" r="4" fill="{bianco}"/>'
    b += f'<rect x="300" y="440" width="176" height="12" rx="3" fill="{grigio}"/><rect x="340" y="460" width="96" height="8" rx="3" fill="{arancione}"/>'
    return svg(grigio, b)


PRODOTTI = {
    "kit-scavo-fossile": kit_scavo,
    "atlante-dinosauri": atlante_dinosauri,
    "odissea-illustrata": odissea,
    "cavallo-troia-legno": cavallo_legno,
    "kylix-figure-rosse": kylix,
    "mammut-peluche": mammut_peluche,
    "tazza-pleistocene": tazza,
    "platone-timeo-crizia": platone,
    "planisfero-celeste": planisfero,
    "poster-costellazioni": poster,
}

for nome, f in PRODOTTI.items():
    with open(os.path.join(OUT, nome + ".svg"), "w") as fh:
        fh.write(f())
print(len(PRODOTTI), "immagini")
