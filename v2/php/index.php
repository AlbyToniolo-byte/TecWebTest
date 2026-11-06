<?php
// Home della seconda versione.
require_once __DIR__ . '/funzioni.php';
require_once __DIR__ . '/header_footer.php';

$mostre = query("SELECT m.slug, m.titolo, m.sottotitolo, m.sommario, m.collocazione, m.immagine, m.tema, m.speciale,
                        m.nota_speciale, m.data_fine,
                        (SELECT string_agg(s.titolo, '|' ORDER BY s.ordine) FROM sezioni_mostra s WHERE s.mostra_id = m.id) AS sezioni
                 FROM mostre m ORDER BY m.ordine");
$eventi = query('SELECT e.titolo, e.descrizione, e.inizio, e.luogo, e.prezzo
                 FROM eventi e WHERE e.inizio >= NOW() ORDER BY e.inizio LIMIT 3');
$biglietti = query('SELECT nome, prezzo FROM tipi_biglietto ORDER BY prezzo DESC');
$speciale = array_values(array_filter($mostre, fn($m) => $m['speciale']))[0] ?? null;
$oggi = stato_apertura_oggi();

pagina_inizio('Home', 'Kronos è il museo dei mondi perduti: dinosauri, mitologia greca, era glaciale, Atlantide e mondo celeste in un unico percorso.',
    'home', null, false, ['vendor/gsap.min.js', 'vendor/ScrollTrigger.min.js', 'puntini.js']);
?>

<section class="v2-eroe" aria-labelledby="titolo-home">
    <h1 id="titolo-home">
        <span class="visually-hidden">Mondi perduti per menti curiose</span>
        <span aria-hidden="true">
            <span class="v2-riga">Mondi <em class="v2-macchina" data-parole="perduti,estinti,leggendari,ghiacciati,sommersi,stellati">perduti</em></span>
            <span class="v2-riga v2-rientro">per menti curiose</span>
        </span>
    </h1>
    <div class="v2-eroe-testo">
        <p>Kronos è il museo dei mondi scomparsi, o mai esistiti. Cinque mostre su quattro piani: dal cielo stellato in terrazza fino al fondale di Atlantide.</p>
        <div class="azioni">
            <a class="bottone" href="visit.php#prenota">Prenota la visita</a>
            <a class="bottone bottone-secondario" href="esibizioni.php">Le mostre</a>
        </div>
        <p class="v2-nota"><?= e($oggi) ?></p>
        <button class="bottone-testo v2-pausa" type="button" data-pausa="" hidden="hidden">Ferma le animazioni</button>
    </div>
</section>

<section class="v2-sezione v2-doppia" aria-labelledby="titolo-tempo">
    <figure class="v2-media" data-rivela=""><img src="<?= e(asset('img/mostre/dinosauri.svg')) ?>" alt="" width="1600" height="1000" loading="lazy" /></figure>
    <div>
        <h2 id="titolo-tempo">Qui, il <em>tempo</em> si visita a piedi.</h2>
        <p>Prende il nome da Crono, il titano che divorava i figli, e da Chronos, il tempo che tutto consuma. Ogni piano è un'epoca che non c'è più: la attraversi in un pomeriggio, scendendo dalle stelle al mare.</p>
        <p>Fossili, reperti e ricostruzioni, con almeno un oggetto da toccare in ogni sala.</p>
        <a class="bottone bottone-secondario" href="about.php">Chi siamo</a>
    </div>
</section>

<?php if ($speciale): ?>
<section class="v2-scuro v2-fascia" aria-labelledby="titolo-speciale">
    <div class="v2-vetrina">
        <img src="<?= e(asset($speciale['immagine'])) ?>" alt="" width="1600" height="1000" loading="lazy" />
        <div class="v2-vetrina-scheda">
            <p class="v2-occhiello">Fino al <?= e(data_estesa($speciale['data_fine'], true)) ?></p>
            <h2 id="titolo-speciale"><?= e($speciale['titolo']) ?></h2>
            <p><?= e($speciale['nota_speciale']) ?></p>
            <a class="bottone" href="mostra.php?m=<?= e($speciale['slug']) ?>">Scopri l'installazione</a>
        </div>
    </div>
</section>
<?php endif; ?>

<section class="v2-sezione" aria-labelledby="titolo-biglietto">
    <h2 id="titolo-biglietto" class="v2-titolo-largo">Non serve una macchina del tempo,<br />basta un <em>biglietto</em>.</h2>
    <div class="v2-colonne">
        <p class="v2-colonna-intro">Il biglietto vale per tutte e cinque le mostre, compresa l'installazione speciale. Prenotando online scegli la fascia oraria ed eviti la coda.</p>
        <div>
            <h3>Quando</h3>
            <p><?= e($oggi) ?>.</p>
            <p>Il lunedì il museo è chiuso, il venerdì resta aperto fino alle 22.</p>
        </div>
        <div>
            <h3>Quanto</h3>
            <dl class="v2-prezzi">
                <?php foreach ($biglietti as $b): ?>
                    <div><dt><?= e($b['nome']) ?></dt><dd><?= e(prezzo($b['prezzo'])) ?></dd></div>
                <?php endforeach; ?>
            </dl>
        </div>
    </div>
</section>

<section class="v2-puntini" aria-labelledby="titolo-percorso">
    <div class="v2-puntini-fisso">
        <div class="v2-puntini-scena" aria-hidden="true"></div>
        <div class="v2-puntini-testo">
            <h2 id="titolo-percorso">Dal cielo all'abisso,<br /><em>un solo</em> percorso.</h2>
            <button class="bottone-testo v2-pausa" type="button" data-pausa="" hidden="hidden">Ferma le animazioni</button>
        </div>
    </div>
</section>

<section class="v2-sezione" aria-labelledby="titolo-mondi">
    <h2 id="titolo-mondi" class="visually-hidden">Le cinque mostre</h2>
    <ul class="v2-mondi">
        <?php foreach ($mostre as $m): ?>
            <li class="v2-mondo tema-<?= e($m['tema']) ?>" data-rivela="">
                <h3><a href="mostra.php?m=<?= e($m['slug']) ?>"><?= e($m['titolo']) ?></a></h3>
                <p><?= e($m['sommario']) ?> <span class="v2-dove"><?= e($m['collocazione']) ?>.</span></p>
                <?php if ($m['sezioni']): ?>
                    <ul class="v2-pillole" aria-label="Sezioni della mostra">
                        <?php foreach (explode('|', $m['sezioni']) as $s): ?>
                            <li><?= e($s) ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
</section>

<section class="v2-scuro v2-fascia" aria-labelledby="titolo-eventi">
    <h2 id="titolo-eventi" class="v2-titolo-centro">Prossimi <em>eventi</em></h2>
    <?php if ($eventi): ?>
        <ul class="v2-eventi">
            <?php foreach ($eventi as $ev): ?>
                <li>
                    <p class="v2-evento-data"><time datetime="<?= e(date('c', strtotime($ev['inizio']))) ?>"><?= e(data_estesa($ev['inizio'])) ?>, ore <?= e(ora($ev['inizio'])) ?></time></p>
                    <h3><?= e($ev['titolo']) ?></h3>
                    <p><?= e($ev['descrizione']) ?></p>
                    <p class="v2-nota"><?= e($ev['luogo']) ?>, <?= e(prezzo($ev['prezzo'])) ?></p>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p class="v2-titolo-centro">Non ci sono eventi in programma. Iscriviti alla newsletter per sapere quando ne annunciamo di nuovi.</p>
    <?php endif; ?>
    <p class="v2-titolo-centro"><a class="bottone bottone-secondario" href="esibizioni.php#eventi">Tutti gli eventi</a></p>
</section>

<section class="v2-sezione v2-domande" aria-labelledby="titolo-domande">
    <h2 id="titolo-domande">Le domande di chi viene<br />per la <em>prima</em> volta</h2>
    <div>
        <details>
            <summary>Serve prenotare?</summary>
            <p>No, ma prenotando online scegli la fascia oraria ed eviti la coda in biglietteria. <a href="visit.php#prenota">Prenota la visita</a>.</p>
        </details>
        <details>
            <summary>L'installazione speciale ha un biglietto a parte?</summary>
            <p>No: tutte le mostre, compresa quella speciale, sono incluse nel biglietto del museo.</p>
        </details>
        <details>
            <summary>Il museo è accessibile?</summary>
            <p>Tutti i piani sono raggiungibili con l'ascensore e in ogni mostra c'è almeno un oggetto da toccare, con didascalia in braille. Il primo sabato del mese c'è una visita guidata in LIS.</p>
        </details>
        <details>
            <summary>Quando è chiuso?</summary>
            <p>Il lunedì. Gli altri giorni gli orari sono nel piè di pagina e nella pagina <a href="visit.php">Visita</a>.</p>
        </details>
        <details>
            <summary>Posso visitare solo lo shop?</summary>
            <p>Sì, il bookshop è aperto anche senza biglietto. Puoi ordinare online e ritirare in museo: <a href="shop.php">vai allo shop</a>.</p>
        </details>
    </div>
</section>
<?php pagina_fine();
