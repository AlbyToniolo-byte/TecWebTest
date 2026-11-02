<?php
// Testata e piè di pagina della seconda versione, ispirata a miligram.pt.
// Lo stile è in css/kronos2.css (caricato dopo i fogli di base), il menu a schermo intero in js/kronos2.js.

const VOCI_MENU = [
    'esibizioni' => ['esibizioni.php', 'Mostre ed eventi'],
    'visit'      => ['visit.php', 'Visita'],
    'about'      => ['about.php', 'Chi siamo'],
    'support'    => ['support.php', 'Sostienici'],
    'shop'       => ['shop.php', 'Shop'],
];

// Sfera armillare del logo, la stessa della prima versione del sito.
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
 * @param string|null $tema        mostra della pagina: ne prende il colore d'accento (vedi css/kronos2.css)
 * @param bool        $sopraImmagine non usato qui: la testata non si sovrappone mai alle immagini
 * @param string[]    $script      script in più della pagina, relativi a js/
 */
function pagina_inizio(string $titolo, string $descrizione, string $attiva = '', ?string $tema = null, bool $sopraImmagine = false, array $script = []): void
{
    $classi = 'v2 ' . ($tema ? 'tema-' . $tema : 'tema-casa');
    ?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($titolo) ?> | Kronos, Museo dei Mondi Perduti</title>
    <meta name="description" content="<?= e($descrizione) ?>">
    <meta name="theme-color" content="#f4f4f2">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,300;0,9..144,400;1,9..144,300;1,9..144,400&family=Hanken+Grotesk:wght@400;600&display=swap">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/layout.css">
    <link rel="stylesheet" href="../css/themes.css">
    <link rel="stylesheet" href="../css/animazioni.css">
    <link rel="stylesheet" href="../css/kronos2.css">
    <link rel="icon" href="../img/favicon.svg" type="image/svg+xml">
    <script>document.documentElement.classList.add('js');</script>
    <script src="../js/main.js" defer></script>
    <script src="../js/validation.js" defer></script>
    <script src="../js/kronos2.js" defer></script>
    <?php foreach ($script as $s): ?>
    <script src="../js/<?= e($s) ?>" defer></script>
    <?php endforeach; ?>
</head>
<body class="<?= e($classi) ?>">
<a class="salta" href="#contenuto">Vai al contenuto</a>
<header class="v2-testata">
    <a class="v2-marchio marchio" href="index.php"<?= $attiva === 'home' ? ' aria-current="page"' : '' ?>>
        <?= marchio_sfera(34) ?>
        <span translate="no">Kronos</span>
    </a>
    <button class="v2-menu-bottone" type="button" aria-expanded="false" aria-controls="v2-menu" hidden>
        <span class="v2-menu-icona" aria-hidden="true"></span>Menu
    </button>
</header>
<nav id="v2-menu" class="v2-menu" aria-label="Principale">
    <ul>
        <?php foreach (VOCI_MENU as $chiave => [$href, $etichetta]): ?>
            <li><a href="<?= e($href) ?>"<?= $chiave === $attiva ? ' aria-current="page"' : '' ?>><?= e($etichetta) ?></a></li>
        <?php endforeach; ?>
    </ul>
    <p class="v2-menu-piede"><a class="bottone bottone-secondario" href="visit.php#prenota">Prenota la visita</a></p>
</nav>
<main id="contenuto" tabindex="-1">
<?php
    $flash = leggi_flash();
    if ($flash): ?>
    <div class="avviso-conferma" role="status"><p><?= e($flash) ?></p></div>
<?php endif;
}

function pagina_fine(): void
{
    $orari = query('SELECT giorno, apertura, chiusura FROM orari ORDER BY giorno');
    ?>
</main>
<footer class="v2-piede v2-scuro">
    <div class="v2-piede-griglia">
        <p class="v2-piede-motto" aria-hidden="true">Mondi <em>perduti</em><br>per menti curiose.</p>
        <section aria-labelledby="piede-dove">
            <h2 id="piede-dove">Dove siamo</h2>
            <address>Piazza Crono 1<br><a href="mailto:info@kronos-museo.it">info@kronos-museo.it</a></address>
            <ul class="v2-piede-link">
                <?php foreach (VOCI_MENU as [$href, $etichetta]): ?>
                    <li><a href="<?= e($href) ?>"><?= e($etichetta) ?></a></li>
                <?php endforeach; ?>
            </ul>
        </section>
        <section aria-labelledby="piede-orari">
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
        <section aria-labelledby="piede-news">
            <h2 id="piede-news">Newsletter</h2>
            <p>Una mail al mese con le nuove mostre e gli eventi.</p>
            <form class="form-newsletter" action="newsletter.php" method="post" novalidate data-valida>
                <?= campo_csrf() ?>
                <input type="hidden" name="torna" value="<?= e(basename($_SERVER['REQUEST_URI'] ?? 'index.php')) ?>">
                <label for="news-email">Indirizzo email</label>
                <div class="riga-campo">
                    <input type="email" spellcheck="false" id="news-email" name="email" autocomplete="email" placeholder="nome@esempio.it" required aria-required="true" aria-describedby="news-email-errore">
                    <button class="bottone" type="submit">Iscriviti</button>
                </div>
                <p class="errore" id="news-email-errore" hidden></p>
            </form>
        </section>
    </div>
    <p class="v2-piede-fondo">Kronos è un museo immaginario, progetto per il corso di Tecnologie Web. <a href="../../v1/">Vai alla prima versione del sito</a></p>
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
