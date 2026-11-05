<?php
// Testata e piè di pagina condivisi.

const VOCI_MENU = [
    'esibizioni' => ['esibizioni.php', 'Mostre ed eventi'],
    'visit'      => ['visit.php', 'Visita'],
    'about'      => ['about.php', 'Chi siamo'],
    'support'    => ['support.php', 'Sostienici'],
    'shop'       => ['shop.php', 'Shop'],
];

// Colore di fondo di ogni tema per <meta name="theme-color">: deve combaciare con --sfondo in css/themes.css
const SFONDO_TEMA = [
    'casa' => '#e5e5e5', 'neutro' => '#e5e5e5', 'celeste' => '#14213d', 'mitologia' => '#5e0b15',
    'dinosauri' => '#283618', 'glaciale' => '#243640', 'atlantide' => '#184e77',
];

// Sfera armillare del logo, la stessa della prima schermata della home.
// Usa il colore del testo, quindi segue ogni tema. L'anello .marchio-gira ruota al passaggio (css/animazioni.css).
function marchio_sfera(int $lato = 40): string
{
    return '<svg class="marchio-sfera" viewBox="0 0 48 48" width="' . $lato . '" height="' . $lato . '" aria-hidden="true" focusable="false">'
        . '<g fill="none" stroke="currentColor" stroke-linecap="round">'
        . '<circle cx="24" cy="24" r="20" stroke-width="2.5"/>'
        . '<ellipse cx="24" cy="24" rx="23" ry="5" stroke-width="2"/>'
        . '<g transform="rotate(-35 24 24)">'
        . '<line x1="24" y1="1" x2="24" y2="47" stroke-width="1.5"/>'
        . '<g class="marchio-gira"><circle cx="24" cy="24" r="14" stroke-width="2"/>'
        . '<circle cx="33.9" cy="14.1" r="2.6" fill="currentColor" stroke="none"/></g>'
        . '</g>'
        . '<circle cx="24" cy="24" r="4" fill="currentColor" stroke="none"/>'
        . '</g></svg>';
}

// Script per gli effetti allo scorrimento (GSAP), usati dalla home e dalle pagine mostra
const SCRIPT_SCORRIMENTO = ['vendor/gsap.min.js', 'vendor/ScrollTrigger.min.js', 'scorrimento.js'];

/**
 * @param string      $titolo      parte specifica del <title>
 * @param string      $descrizione meta description
 * @param string      $attiva      chiave di VOCI_MENU della pagina corrente
 * @param string|null $tema        palette di una mostra (vedi css/themes.css)
 * @param bool        $sopraImmagine la testata è sovrapposta a un'immagine di apertura
 * @param string[]    $script      script in più della pagina, relativi a js/
 */
function pagina_inizio(string $titolo, string $descrizione, string $attiva = '', ?string $tema = null, bool $sopraImmagine = false, array $script = []): void
{
    $classi = trim(($tema ? 'tema-' . $tema : 'tema-casa') . ($sopraImmagine ? ' sopra-immagine' : ''));
    ?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($titolo) ?> | Kronos, Museo dei Mondi Perduti</title>
    <meta name="description" content="<?= e($descrizione) ?>">
    <meta name="theme-color" content="<?= e(SFONDO_TEMA[$tema ?? 'casa'] ?? SFONDO_TEMA['casa']) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Atkinson+Hyperlegible:ital,wght@0,400;0,700;1,400&family=Marcellus&display=swap">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/layout.css">
    <link rel="stylesheet" href="../css/themes.css">
    <link rel="stylesheet" href="../css/animazioni.css">
    <link rel="icon" href="../img/favicon.svg" type="image/svg+xml">
    <!-- Prima del primo disegno: classe js (animazioni) e schema chiaro/scuro, senza lampi di colore sbagliato -->
    <script>
        document.documentElement.classList.add('js');
        (function () {
            var s = null;
            try { s = localStorage.getItem('kronos-schema'); } catch (e) {}
            document.documentElement.dataset.schema = s || (matchMedia('(prefers-color-scheme: dark)').matches ? 'scuro' : 'chiaro');
        })();
    </script>
    <script src="../js/schema.js" defer></script>
    <script src="../js/main.js" defer></script>
    <script src="../js/validation.js" defer></script>
    <?php foreach ($script as $s): ?>
    <script src="../js/<?= e($s) ?>" defer></script>
    <?php endforeach; ?>
</head>
<body class="<?= e($classi) ?>">
<a class="salta" href="#contenuto">Vai al contenuto</a>
<header class="testata">
    <div class="testata-interno">
        <a class="marchio" href="index.php"<?= $attiva === 'home' ? ' aria-current="page"' : '' ?>>
            <?= marchio_sfera() ?>
            <span class="marchio-nome" translate="no">Kronos</span>
            <span class="marchio-sotto">Museo dei Mondi Perduti</span>
        </a>
        <button class="apri-menu" type="button" aria-expanded="false" aria-controls="menu-principale" hidden>
            <span class="apri-menu-icona" aria-hidden="true"></span>Menu
        </button>
        <nav id="menu-principale" class="menu" aria-label="Principale">
            <ul>
                <?php foreach (VOCI_MENU as $chiave => [$href, $etichetta]): ?>
                    <li><a href="<?= e($href) ?>"<?= $chiave === $attiva ? ' aria-current="page"' : '' ?>><?= e($etichetta) ?></a></li>
                <?php endforeach; ?>
                <li><button class="bottone-testo cambia-schema" type="button" aria-pressed="false" data-cambia-schema hidden><svg class="icona-schema" viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false"><path d="M20 14.5A8.5 8.5 0 0 1 9.5 4a8.5 8.5 0 1 0 10.5 10.5Z" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linejoin="round"/></svg>Modalità notte</button></li>
                <li class="menu-prenota"><a class="bottone" href="visit.php#prenota">Prenota la visita</a></li>
            </ul>
        </nav>
    </div>
</header>
<main id="contenuto" tabindex="-1">
<?php
    $flash = leggi_flash();
    if ($flash): ?>
    <div class="avviso-conferma" role="status"><p><?= e($flash) ?></p></div>
<?php endif;
}

// Il piè di pagina non ha un tema suo: prende i colori della pagina dal <body>
function pagina_fine(): void
{
    $orari = query('SELECT giorno, apertura, chiusura FROM orari ORDER BY giorno');
    ?>
</main>
<footer class="piede">
    <div class="piede-interno">
        <section class="piede-blocco" aria-labelledby="piede-dove">
            <h2 id="piede-dove">Dove siamo</h2>
            <address>Kronos, Museo dei Mondi Perduti<br>Piazza Crono 1<br><a href="mailto:info@kronos-museo.it">info@kronos-museo.it</a></address>
        </section>
        <section class="piede-blocco" aria-labelledby="piede-orari">
            <h2 id="piede-orari">Orari</h2>
            <dl class="orari-brevi">
                <?php foreach ($orari as $o): ?>
                    <div>
                        <dt><?= e(ucfirst(GIORNI[$o['giorno']])) ?></dt>
                        <dd><?= $o['apertura'] ? e(ora($o['apertura']) . '–' . ora($o['chiusura'])) : 'Chiuso' ?></dd>
                    </div>
                <?php endforeach; ?>
            </dl>
        </section>
        <section class="piede-blocco" aria-labelledby="piede-news">
            <h2 id="piede-news">Newsletter</h2>
            <p>Una mail al mese con le nuove mostre e gli eventi.</p>
            <form class="form-newsletter" action="newsletter.php" method="post" novalidate data-valida>
                <?= campo_csrf() ?>
                <input type="hidden" name="torna" value="<?= e(basename($_SERVER['REQUEST_URI'] ?? 'index.php')) ?>">
                <label for="news-email">Indirizzo email</label>
                <div class="riga-campo">
                    <input type="email" spellcheck="false" id="news-email" name="email" autocomplete="email" required aria-required="true" aria-describedby="news-email-errore">
                    <button class="bottone" type="submit">Iscriviti</button>
                </div>
                <p class="errore" id="news-email-errore" hidden></p>
            </form>
        </section>
    </div>
    <p class="piede-fondo">Kronos è un museo immaginario, progetto per il corso di Tecnologie Web.</p>
</footer>
</body>
</html>
<?php
}

// Pagina d'errore quando il database non risponde.
function pagina_errore_db(Throwable $e): never
{
    http_response_code(503);
    error_log('Kronos DB: ' . $e->getMessage());
    echo '<!DOCTYPE html><html lang="it"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">'
        . '<title>Servizio non disponibile | Kronos</title><link rel="stylesheet" href="../css/style.css"></head>'
        . '<body class="tema-casa"><main class="pagina-stretta"><h1>Il sito non riesce a leggere i dati</h1>'
        . '<p>Il database non risponde. Controlla che PostgreSQL sia avviato e che php/config.php contenga le credenziali corrette, poi ricarica la pagina.</p>'
        . '</main></body></html>';
    exit;
}

set_exception_handler(function (Throwable $e) {
    if ($e instanceof PDOException) {
        pagina_errore_db($e);
    }
    http_response_code(500);
    error_log('Kronos: ' . $e);
    echo '<p>Si è verificato un errore. Ricarica la pagina tra qualche istante.</p>';
});
