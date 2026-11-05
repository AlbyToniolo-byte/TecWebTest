# TEST PER IL PROGETTO DI TECWEB

# Kronos: Museo dei Mondi Perduti

Sito in HTML, PHP e JavaScript senza framework, con database PostgreSQL.

## Due versioni indipendenti

| Cartella | Versione |
|---|---|
| `v1/` | Prima versione: sfera armillare 3D, palette delle cinque mostre da `refs.md` |
| `v2/` | Seconda versione, ispirata a miligram.pt: carta e inchiostro, macchina da scrivere, sfera di puntini 3D |

Ogni cartella contiene tutto il proprio codice (pagine PHP, CSS, JavaScript, immagini e librerie): si può modificare una versione senza toccare l'altra.
In comune c'è solo il database, in `database/`: le due versioni leggono e scrivono gli stessi dati.

## Avvio in locale

```bash
brew install php postgresql@17
pg_ctl -D /opt/homebrew/var/postgresql@17 -l /opt/homebrew/var/log/postgresql@17.log start
createdb kronos
psql -d kronos -f database/schema.sql
psql -d kronos -f database/seed.sql
cp v1/php/config.example.php v1/php/config.php   # poi inserisci utente e password
cp v2/php/config.example.php v2/php/config.php   # le stesse credenziali
php -S localhost:8000
```

Su http://localhost:8000 una pagina rimanda alle due versioni: http://localhost:8000/v1/ e http://localhost:8000/v2/.

## Struttura di una versione

`v1/` e `v2/` hanno la stessa struttura:

| Percorso | Contenuto |
|---|---|
| `index.php` | Punto di ingresso della versione: rimanda a `php/index.php` |
| `php/index.php` | Home |
| `php/esibizioni.php` | Elenco delle mostre e calendario eventi (filtro per mostra) |
| `php/mostra.php?m=<slug>` | Pagina di una mostra: l'immagine di apertura sfuma nel colore della mostra |
| `php/visit.php` | Orari, biglietti, accessibilità e prenotazione |
| `php/support.php` | Tessere soci, donazioni, volontariato |
| `php/shop.php` | Prodotti, carrello in sessione e ordine con ritiro al museo |
| `php/about.php` | Il nome, i temi e il gruppo di lavoro |
| `php/` | Anche connessione al database, funzioni comuni, testata e piè di pagina (`header_footer.php`), iscrizione newsletter |
| `css/style.css`, `css/layout.css` | Base, tipografia, form e struttura delle pagine |
| `css/themes.css` | Le palette delle cinque mostre (da `refs.md`) |
| `css/animazioni.css` | Effetti di movimento, tutti disattivati se il sistema chiede meno movimento |
| `js/main.js` | Menu mobile, comparse allo scorrimento, totale biglietti |
| `js/validation.js` | Validazione dei form lato client (ripetuta in PHP) |
| `js/schema.js` | Modalità notte: il bottone alterna chiaro e scuro e ricorda la scelta nel browser (al primo accesso segue il sistema) |
| `js/scorrimento.js` | Effetti legati allo scorrimento con GSAP |
| `js/vendor/` | Librerie GSAP 3.13 e Three.js r186, salvate in locale |
| `img/mostre/`, `img/prodotti/` | Illustrazioni delle mostre e dei prodotti dello shop |

In più, solo in `v1/`:

| Percorso | Contenuto |
|---|---|
| `js/armilla.js` | Sfera armillare 3D della home con Three.js |

Solo in `v2/`:

| Percorso | Contenuto |
|---|---|
| `css/kronos2.css` | Stile della seconda versione, caricato dopo i fogli di base |
| `js/kronos2.js` | Menu a schermo intero, macchina da scrivere, pausa delle animazioni |
| `js/puntini.js` | Sfera di puntini 3D che si apre scorrendo (Three.js + GSAP) |

Fuori dalle versioni:

| Percorso | Contenuto |
|---|---|
| `index.php` | Pagina di scelta tra le due versioni |
| `database/` | Schema e dati di esempio, comuni alle due versioni |
| `docs/schema-er.drawio` | Schema E/R del database (notazione di Chen), da aprire con draw.io o app.diagrams.net |
| `docs/genera_svg.py`, `docs/genera_prodotti.py` | Generano le illustrazioni nei colori di `refs.md`, per esempio `python3 docs/genera_svg.py v1/img/mostre` |
| `docs/contrasto.json` | Coppie di colori della prima versione verificate per il contrasto WCAG AA |

Il sito funziona anche con JavaScript disattivato: il menu resta sempre visibile e i form vengono validati dal server.
