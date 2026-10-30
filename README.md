# TEST PER IL PROGETTO DI TECWEB

# Kronos: Museo dei Mondi Perduti

Sito in HTML, PHP e JavaScript senza framework, con database PostgreSQL.

## Avvio in locale

```bash
brew install php postgresql@17
pg_ctl -D /opt/homebrew/var/postgresql@17 -l /opt/homebrew/var/log/postgresql@17.log start
createdb kronos
psql -d kronos -f database/schema.sql
psql -d kronos -f database/seed.sql
cp php/config.example.php php/config.php   # poi inserisci utente e password
php -S localhost:8000
```

## Struttura

| Percorso | Contenuto |
|---|---|
| `php/index.php` | Home: la discesa dal Mondo Celeste ad Atlantide |
| `php/esibizioni.php` | Elenco delle mostre e calendario eventi (filtro per mostra) |
| `php/mostra.php?m=<slug>` | Pagina di una mostra: immagine di apertura che sfuma nel colore del tema |
| `php/visit.php` | Orari, biglietti, accessibilità e prenotazione |
| `php/support.php` | Tessere soci, donazioni, volontariato |
| `php/shop.php` | Prodotti, carrello in sessione e ordine con ritiro al museo |
| `php/about.php` | Il nome, i temi e il gruppo di lavoro |
| `index.php` | Punto di ingresso: rimanda a `php/index.php` |
| `php/` | Tutte le pagine PHP, più connessione al database, funzioni comuni, testata, piè di pagina e iscrizione newsletter |
| `css/style.css` | Base, tipografia, bottoni e form |
| `css/layout.css` | Struttura delle pagine |
| `css/themes.css` | Le palette delle cinque mostre (da `refs.md`) |
| `css/animazioni.css` | Effetti di movimento, tutti disattivati se il sistema chiede meno movimento |
| `js/main.js` | Menu mobile, sfumatura allo scorrimento, totale biglietti |
| `js/validation.js` | Validazione dei form lato client (ripetuta in PHP) |
| `img/mostre/` | Illustrazioni di apertura nei colori di `refs.md`, generate con `python3 docs/genera_svg.py img/mostre`; si possono sostituire con foto aggiornando `mostre.immagine` |
| `img/prodotti/` | Immagini dei prodotti dello shop nei colori della mostra collegata, generate con `python3 docs/genera_prodotti.py img/prodotti` |
| `docs/contrasto.json` | Coppie di colori verificate per il contrasto WCAG AA |

Il sito funziona anche con JavaScript disattivato: il menu resta sempre visibile e i form vengono validati dal server.
