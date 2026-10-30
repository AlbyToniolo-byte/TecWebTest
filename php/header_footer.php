<?php
// Testata e piè di pagina condivisi.

const VOCI_MENU = [
    'esibizioni' => ['esibizioni.php', 'Mostre ed eventi'],
    'visit'      => ['visit.php', 'Visita'],
    'about'      => ['about.php', 'Chi siamo'],
    'support'    => ['support.php', 'Sostienici'],
    'shop'       => ['shop.php', 'Shop'],
];

/**
 * @param string      $titolo      parte specifica del <title>
 * @param string      $descrizione meta description
 * @param string      $attiva      chiave di VOCI_MENU della pagina corrente
 * @param string|null $tema        palette di una mostra (vedi css/themes.css)
 * @param bool        $sopraImmagine la testata è sovrapposta a un'immagine di apertura
 */
function pagina_inizio(string $titolo, string $descrizione, string $attiva = '', ?string $tema = null, bool $sopraImmagine = false): void
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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Atkinson+Hyperlegible:ital,wght@0,400;0,700;1,400&family=Marcellus&display=swap">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/layout.css">
    <link rel="stylesheet" href="../css/themes.css">
    <link rel="stylesheet" href="../css/animazioni.css">
    <link rel="icon" href="../img/favicon.svg" type="image/svg+xml">
    <!-- Subito, prima del primo disegno: senza questa classe gli elementi animati restano visibili -->
    <script>document.documentElement.classList.add('js');</script>
    <script src="../js/main.js" defer></script>
    <script src="../js/validation.js" defer></script>
</head>
<body class="<?= e($classi) ?>">
<a class="salta" href="#contenuto">Vai al contenuto</a>
<header class="testata">
    <div class="testata-interno">
        <a class="marchio" href="index.php"<?= $attiva === 'home' ? ' aria-current="page"' : '' ?>>
            <span class="marchio-nome">Kronos</span>
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

function pagina_fine(): void
{
    $orari = query('SELECT giorno, apertura, chiusura FROM orari ORDER BY giorno');
    ?>
</main>
<footer class="piede tema-celeste">
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
                    <input type="email" id="news-email" name="email" autocomplete="email" required aria-required="true" aria-describedby="news-email-errore">
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
