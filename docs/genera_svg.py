import random, math, sys, os

OUT = sys.argv[1]
os.makedirs(OUT, exist_ok=True)
W, H = 1600, 1000

def svg(body, defs=""):
    return (f'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 {W} {H}" preserveAspectRatio="xMidYMid slice">'
            f'<defs>{defs}</defs>{body}</svg>')

def lg(id_, stops, x2="0", y2="1"):
    s = "".join(f'<stop offset="{o}" stop-color="{c}"' + (f' stop-opacity="{a}"' if a is not None else '') + '/>' for o, c, a in stops)
    return f'<linearGradient id="{id_}" x1="0" y1="0" x2="{x2}" y2="{y2}">{s}</linearGradient>'

def ridge(y0, amp, step, seed, color, jag=False):
    r = random.Random(seed)
    pts = []
    x = -20
    y = y0
    while x <= W + 40:
        y = y0 + r.uniform(-amp, amp) if jag else y0 + amp * math.sin(x / 210 + seed) + amp * 0.5 * math.sin(x / 77 + seed * 2)
        pts.append(f"{x:.0f},{y:.0f}")
        x += step if not jag else r.randint(step // 2, step)
    return f'<polygon fill="{color}" points="-20,{H} {" ".join(pts)} {W+40},{H}"/>'

def stelle(n, seed, ymax, colore="#ffffff"):
    r = random.Random(seed)
    out = []
    for _ in range(n):
        out.append(f'<circle cx="{r.uniform(0,W):.0f}" cy="{r.uniform(0,ymax):.0f}" r="{r.uniform(0.5,2):.1f}" fill="{colore}" opacity="{r.uniform(0.35,1):.2f}"/>')
    return "".join(out)

# ---------------------------------------------------------------- Mondo Celeste
def celeste():
    d = (lg("cielo", [(0, "#000000", None), (0.7, "#14213d", None), (1, "#14213d", None)])
         + lg("bagliore", [(0, "#fca311", 0), (1, "#fca311", 0.35)])
         + '<radialGradient id="pianeta" cx="0.35" cy="0.35" r="0.75"><stop offset="0" stop-color="#ffffff" stop-opacity="0.45"/><stop offset="0.45" stop-color="#fca311" stop-opacity="0"/><stop offset="1" stop-color="#000000" stop-opacity="0.55"/></radialGradient>'
         + '<filter id="sfoca" x="-50%" y="-50%" width="200%" height="200%"><feGaussianBlur stdDeviation="40"/></filter>'
         + '<filter id="alone"><feGaussianBlur stdDeviation="3"/></filter>')
    b = f'<rect width="{W}" height="{H}" fill="url(#cielo)"/>'
    b += '<ellipse cx="800" cy="380" rx="900" ry="120" fill="#e5e5e5" opacity="0.16" transform="rotate(-18 800 380)" filter="url(#sfoca)"/>'
    b += stelle(420, 7, 820)
    # costellazione
    pts = [(260, 180), (330, 240), (300, 330), (390, 345), (470, 300), (420, 420), (360, 470)]
    b += '<polyline fill="none" stroke="#fca311" stroke-width="1.5" opacity="0.6" points="' + " ".join(f"{x},{y}" for x, y in pts) + '"/>'
    for x, y in pts:
        b += f'<circle cx="{x}" cy="{y}" r="6" fill="#ffffff" opacity="0.5" filter="url(#alone)"/><circle cx="{x}" cy="{y}" r="3" fill="#ffffff"/>'
    # pianeta con anello
    b += '<g transform="rotate(-16 1230 260)"><ellipse cx="1230" cy="260" rx="200" ry="44" fill="none" stroke="#e5e5e5" stroke-width="10" opacity="0.5"/>'
    b += '<circle cx="1230" cy="260" r="110" fill="#fca311"/><circle cx="1230" cy="260" r="110" fill="url(#pianeta)"/>'
    b += '<path d="M1030,260 A200,44 0 0 0 1430,260" fill="none" stroke="#e5e5e5" stroke-width="10" opacity="0.75"/></g>'
    b += '<rect y="640" width="1600" height="260" fill="url(#bagliore)"/>'
    b += ridge(820, 30, 40, 3, "#14213d")
    # osservatorio
    b += '<g fill="#000000"><rect x="1160" y="740" width="170" height="90"/><path d="M1150,742 A95,95 0 0 1 1340,742 Z"/></g>'
    b += '<rect x="1236" y="660" width="18" height="80" fill="#fca311" opacity="0.8"/>'
    b += ridge(880, 18, 30, 9, "#000000")
    return svg(b, d)

# ---------------------------------------------------------------- Mitologia: il cavallo di Troia
def mitologia():
    d = (lg("cielo", [(0, "#90323d", None), (0.5, "#90323d", None), (0.72, "#bc8034", None), (0.88, "#d9cab3", None), (1, "#d9cab3", None)])
         + '<filter id="sfoca"><feGaussianBlur stdDeviation="18"/></filter>')
    b = f'<rect width="{W}" height="{H}" fill="url(#cielo)"/>'
    b += stelle(90, 11, 330, "#d9cab3")
    b += '<circle cx="400" cy="740" r="150" fill="#d9cab3"/>'
    # mura di Troia
    mura = '<g fill="#8c7a6b">'
    mura += '<rect x="-10" y="700" width="1620" height="120"/>'
    for x in range(0, 1600, 36):
        mura += f'<rect x="{x}" y="686" width="20" height="16"/>'
    for x in (120, 520, 960, 1380):
        mura += f'<rect x="{x}" y="620" width="110" height="100"/>'
        for k in range(4):
            mura += f'<rect x="{x + k * 30}" y="604" width="18" height="18"/>'
    mura += '</g>'
    b += mura
    b += ridge(800, 14, 40, 5, "#5e0b15")
    # cavallo
    c = "#5e0b15"
    h = f'<g fill="{c}">'
    h += '<rect x="470" y="822" width="640" height="34" rx="4"/>'
    for x in (540, 700, 880, 1040):
        h += f'<circle cx="{x}" cy="872" r="34"/>'
    for x in (565, 650, 880, 965):
        h += f'<rect x="{x}" y="650" width="50" height="180"/>'
    h += '<polygon points="530,500 1010,486 1030,670 520,676"/>'
    h += '<polygon points="900,520 1000,500 1100,250 1010,226"/>'
    h += '<polygon points="990,196 1096,180 1204,316 1182,362 1104,348 1080,268"/>'
    h += '<polygon points="1006,200 1028,140 1052,192"/><polygon points="1046,190 1072,134 1090,186"/>'
    h += '<path d="M532,520 C470,560 456,640 486,720 L506,716 C490,650 506,590 548,556 Z"/>'
    mane = "M998,206 " + " ".join(f"L{998 - i * 12 + (14 if i % 2 else 0)},{206 + i * 24}" for i in range(13)) + " L900,520 Z"
    h += f'<path d="{mane}"/>'
    h += '</g>'
    venature = '<g stroke="#90323d" stroke-width="3" fill="none">'
    for y in (540, 580, 620, 655):
        venature += f'<line x1="532" y1="{y}" x2="1018" y2="{y - 6}"/>'
    for x in (640, 760, 880):
        venature += f'<line x1="{x}" y1="496" x2="{x + 2}" y2="672"/>'
    venature += '<line x1="930" y1="470" x2="1020" y2="240"/><line x1="960" y1="490" x2="1050" y2="250"/>'
    venature += '</g>'
    h += venature
    h += '<rect x="1112" y="250" width="24" height="12" fill="#bc8034"/>'
    b += h
    # torce
    for x in (360, 1240):
        b += f'<rect x="{x}" y="760" width="8" height="120" fill="{c}"/><circle cx="{x + 4}" cy="752" r="30" fill="#bc8034" opacity="0.7" filter="url(#sfoca)"/><circle cx="{x + 4}" cy="752" r="8" fill="#d9cab3"/>'
    b += f'<rect y="900" width="{W}" height="100" fill="#5e0b15"/>'
    return svg(b, d)

# ---------------------------------------------------------------- Dinosauri
def dinosauri():
    d = (lg("cielo", [(0, "#fefae0", None), (0.55, "#dda15e", None), (1, "#dda15e", None)])
         + '<filter id="sfoca"><feGaussianBlur stdDeviation="24"/></filter>')
    b = f'<rect width="{W}" height="{H}" fill="url(#cielo)"/>'
    b += '<circle cx="1180" cy="300" r="90" fill="#fefae0"/>'
    # vulcano e fumo
    b += '<polygon points="80,720 330,400 380,410 640,720" fill="#bc6c25"/>'
    for i, (x, y, r) in enumerate([(355, 370, 50), (390, 300, 70), (440, 220, 90), (520, 150, 110)]):
        b += f'<circle cx="{x}" cy="{y}" r="{r}" fill="#283618" opacity="{0.45 - i * 0.08:.2f}" filter="url(#sfoca)"/>'
    b += ridge(700, 26, 30, 2, "#bc6c25")
    b += ridge(760, 22, 30, 4, "#606c38")
    # brachiosauro
    g = '<g fill="#283618">'
    g += '<ellipse cx="1080" cy="680" rx="170" ry="80"/>'
    for x in (960, 1010, 1120, 1170):
        g += f'<rect x="{x}" y="690" width="36" height="120" rx="10"/>'
    g += '<path d="M1170,640 C1220,560 1240,450 1270,330 L1310,335 C1290,470 1270,580 1230,690 Z"/>'
    g += '<ellipse cx="1305" cy="322" rx="44" ry="20"/>'
    g += '<path d="M920,650 C840,670 780,700 700,740 C790,725 860,712 930,700 Z"/>'
    g += '</g>'
    b += g
    # pterosauro
    b += '<path d="M600,250 L660,262 L700,240 L720,262 L780,250 L725,276 L700,292 L680,276 Z" fill="#283618"/>'
    b += ridge(830, 18, 30, 8, "#283618")
    # felci e palme in primo piano
    p = '<g fill="#283618">'
    def palma(x, y, alt, s):
        out = f'<path d="M{x},{y} C{x + 10 * s},{y - alt * 0.5} {x + 30 * s},{y - alt * 0.8} {x + 40 * s},{y - alt} L{x + 52 * s},{y - alt + 4} C{x + 40 * s},{y - alt * 0.75} {x + 24 * s},{y - alt * 0.45} {x + 18},{y} Z"/>'
        cx, cy = x + 46 * s, y - alt
        for ang in range(-160, 30, 32):
            a = math.radians(ang)
            ex, ey = cx + math.cos(a) * 190, cy + math.sin(a) * 110 + 60
            mx, my = cx + math.cos(a) * 90, cy + math.sin(a) * 90 - 20
            out += f'<path d="M{cx},{cy} Q{mx:.0f},{my:.0f} {ex:.0f},{ey:.0f} Q{mx + 10:.0f},{my + 20:.0f} {cx},{cy + 8} Z"/>'
        return out
    p += palma(120, 1000, 420, 1) + palma(1460, 1000, 360, -1)
    for x in range(-40, 1700, 90):
        hgt = 60 + (x * 37) % 80
        p += f'<path d="M{x},1000 Q{x + 20},{1000 - hgt} {x + 60},{1000 - hgt - 30} Q{x + 40},{1000 - hgt + 20} {x + 70},1000 Z"/>'
    p += '</g>'
    b += p
    return svg(b, d)

# ---------------------------------------------------------------- Era glaciale
def glaciale():
    d = lg("cielo", [(0, "#88ccf1", None), (0.45, "#a5d6f1", None), (1, "#dcedf6", None)])
    b = f'<rect width="{W}" height="{H}" fill="url(#cielo)"/>'
    b += '<circle cx="420" cy="260" r="70" fill="#dcedf6"/>'
    b += ridge(520, 120, 140, 21, "#dcedf6", jag=True)
    b += ridge(600, 90, 120, 23, "#557e96", jag=True)
    # riflessi di neve sulle cime intermedie
    b += ridge(660, 40, 100, 24, "#a5d6f1", jag=True)
    b += ridge(760, 16, 40, 25, "#dcedf6")
    # mammut
    m = '<g fill="#243640">'
    m += '<path d="M760,700 C760,600 840,560 940,570 C1020,520 1110,540 1150,610 C1190,640 1200,700 1180,740 L760,760 Z"/>'
    m += '<path d="M1130,600 C1180,560 1250,580 1260,640 C1270,690 1250,740 1230,800 C1225,830 1250,850 1270,840 L1265,860 C1230,870 1200,840 1206,800 C1212,750 1220,710 1190,690 Z"/>'
    for x in (790, 860, 1040, 1110):
        m += f'<rect x="{x}" y="720" width="56" height="110" rx="14"/>'
    franga = "M760,740 " + " ".join(f"L{760 + i * 20},{760 + (16 if i % 2 else 0)}" for i in range(22)) + " L1180,740 Z"
    m += f'<path d="{franga}"/>'
    m += '</g>'
    m += '<path d="M1215,700 C1260,760 1330,770 1370,720" fill="none" stroke="#dcedf6" stroke-width="14" stroke-linecap="round"/>'
    m += '<circle cx="1205" cy="630" r="6" fill="#dcedf6"/>'
    b += m
    b += ridge(850, 20, 40, 27, "#dcedf6")
    b += '<path d="M0,900 C300,870 600,920 900,890 C1200,860 1400,900 1600,880 L1600,1000 L0,1000 Z" fill="#a5d6f1"/>'
    b += stelle(260, 31, H, "#dcedf6")
    return svg(b, d)

# ---------------------------------------------------------------- Atlantide
def atlantide():
    d = (lg("acqua", [(0, "#b5e48c", None), (0.2, "#52b69a", None), (0.45, "#34a0a4", None), (0.75, "#168aad", None), (1, "#168aad", None)])
         + lg("raggio", [(0, "#d9ed92", 0.35), (1, "#d9ed92", 0)]))
    b = f'<rect width="{W}" height="{H}" fill="url(#acqua)"/>'
    r = random.Random(41)
    for _ in range(7):
        x = r.uniform(100, 1500)
        w = r.uniform(40, 110)
        b += f'<polygon points="{x:.0f},0 {x + w:.0f},0 {x + w * 3 + 200:.0f},1000 {x + w + 160:.0f},1000" fill="url(#raggio)" opacity="0.5"/>'
    # tempio a gradoni con cupola sullo sfondo
    t = '<g fill="#184e77">'
    t += '<rect x="560" y="560" width="480" height="40"/><rect x="600" y="520" width="400" height="40"/><rect x="640" y="480" width="320" height="40"/>'
    t += '<path d="M680,480 A120,120 0 0 1 920,480 Z"/>'
    for x in range(600, 1000, 60):
        t += f'<rect x="{x}" y="600" width="26" height="160"/>'
    t += '</g>'
    b += t
    b += ridge(760, 20, 40, 43, "#184e77")
    # colonne spezzate e arco in primo piano
    c = '<g fill="#184e77">'
    for x, h_ in ((180, 360), (300, 220), (1300, 300), (1430, 180)):
        c += f'<rect x="{x}" y="{900 - h_}" width="56" height="{h_}"/><rect x="{x - 12}" y="{900 - h_ - 18}" width="80" height="20"/>'
    c += '<path d="M380,900 L380,640 A140,140 0 0 1 660,640 L660,900 L600,900 L600,650 A80,80 0 0 0 440,650 L440,900 Z"/>'
    c += '</g>'
    b += c
    # alghe
    for x in range(40, 1600, 110):
        hgt = 140 + (x * 13) % 120
        b += f'<path d="M{x},1000 C{x - 30},{1000 - hgt * 0.4} {x + 30},{1000 - hgt * 0.7} {x},{1000 - hgt}" fill="none" stroke="#52b69a" stroke-width="7" opacity="0.6" stroke-linecap="round"/>'
    b += f'<path d="M0,900 C400,880 800,920 1200,890 C1400,880 1500,900 1600,895 L1600,1000 L0,1000 Z" fill="#184e77"/>'
    # pesci e bolle
    for x, y, s in ((1100, 260, 1), (1160, 300, 0.8), (1060, 320, 0.7), (300, 360, 1.1)):
        b += f'<path transform="translate({x} {y}) scale({s})" d="M0,0 C20,-14 50,-14 64,0 C50,14 20,14 0,0 Z M64,0 L84,-12 L84,12 Z" fill="#184e77"/>'
    for _ in range(60):
        b += f'<circle cx="{r.uniform(0,W):.0f}" cy="{r.uniform(0,900):.0f}" r="{r.uniform(2,9):.1f}" fill="none" stroke="#d9ed92" stroke-width="1.5" opacity="{r.uniform(0.2,0.6):.2f}"/>'
    return svg(b, d)

for nome, f in (("mondo-celeste", celeste), ("mitologia", mitologia), ("dinosauri", dinosauri),
                ("era-glaciale", glaciale), ("atlantide", atlantide)):
    with open(os.path.join(OUT, nome + ".svg"), "w") as fh:
        fh.write(f())
print("ok")
