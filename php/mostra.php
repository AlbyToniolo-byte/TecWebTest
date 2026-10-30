<?php
// Pagina di una singola mostra: mostra.php?m=<slug>
require_once __DIR__ . '/funzioni.php';
require_once __DIR__ . '/header_footer.php';

$slug = (string) ($_GET['m'] ?? '');
$mostra = query_riga('SELECT * FROM mostre WHERE slug = :s', ['s' => $slug]);

if (!$mostra) {
    http_response_code(404);
    pagina_inizio('Mostra non trovata', 'La mostra richiesta non esiste.', 'esibizioni');
    ?>
    <div class="pagina-stretta">
        <h1>Questa mostra non esiste</h1>
        <p>L'indirizzo potrebbe essere sbagliato o la mostra potrebbe essere stata chiusa. Trovi tutte le mostre aperte nella pagina <a href="esibizioni.php">Mostre ed eventi</a>.</p>
    </div>
    <?php
    pagina_fine();
    exit;
}

$id = $mostra['id'];
$sezioni = query('SELECT titolo, testo FROM sezioni_mostra WHERE mostra_id = :id ORDER BY ordine', ['id' => $id]);
$reperti = query('SELECT nome, datazione, descrizione FROM reperti WHERE mostra_id = :id ORDER BY ordine', ['id' => $id]);
$eventi = query('SELECT titolo, descrizione, inizio, durata_min, luogo, prezzo FROM eventi
                 WHERE mostra_id = :id AND inizio >= NOW() ORDER BY inizio', ['id' => $id]);
$prodotti = query('SELECT nome, prezzo FROM prodotti WHERE mostra_id = :id ORDER BY nome', ['id' => $id]);

pagina_inizio($mostra['titolo'], $mostra['sommario'], 'esibizioni', $mostra['tema'], true);
?>
<div class="sfondo-mostra"><img src="<?= e(asset($mostra['immagine'])) ?>" alt="" width="1600" height="1000"></div>

<header class="apertura">
    <div class="contenitore">
        <?php if ($mostra['speciale']): ?>
            <p class="etichetta-speciale">Installazione speciale fino al <?= e(data_estesa($mostra['data_fine'], true)) ?></p>
        <?php endif; ?>
        <h1><?= e($mostra['titolo']) ?></h1>
        <p class="sottotitolo"><?= e($mostra['sottotitolo']) ?></p>
        <dl class="dati-mostra">
            <div><dt>Dove</dt><dd><?= e($mostra['collocazione']) ?></dd></div>
            <div><dt>Ingresso</dt><dd>Incluso nel biglietto del museo</dd></div>
        </dl>
    </div>
</header>

<div class="corpo-mostra">
    <div class="contenitore">
        <p class="introduzione"><?= e($mostra['introduzione']) ?></p>
        <?php if ($mostra['speciale']): ?>
            <div class="nota-speciale"><p><?= e($mostra['nota_speciale']) ?></p></div>
        <?php endif; ?>

        <div class="sezioni-mostra">
            <?php foreach ($sezioni as $s): ?>
                <section class="sezione-mostra" data-rivela>
                    <h2><?= e($s['titolo']) ?></h2>
                    <p><?= e($s['testo']) ?></p>
                </section>
            <?php endforeach; ?>
        </div>

        <?php if ($reperti): ?>
            <section aria-labelledby="titolo-reperti">
                <h2 id="titolo-reperti">Da non perdere</h2>
                <ul class="cartellini" data-rivela-gruppo>
                    <?php foreach ($reperti as $r): ?>
                        <li class="cartellino">
                            <h3><?= e($r['nome']) ?></h3>
                            <p class="cartellino-data"><?= e($r['datazione']) ?></p>
                            <p><?= e($r['descrizione']) ?></p>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </section>
        <?php endif; ?>

        <?php if ($eventi): ?>
            <section aria-labelledby="titolo-eventi-mostra">
                <h2 id="titolo-eventi-mostra">Eventi in questa mostra</h2>
                <ul class="eventi">
                    <?php foreach ($eventi as $ev): ?>
                        <li class="evento">
                            <p class="evento-quando"><time datetime="<?= e(date('c', strtotime($ev['inizio']))) ?>"><?= e(data_estesa($ev['inizio'])) ?><span>ore <?= e(ora($ev['inizio'])) ?></span></time></p>
                            <div>
                                <h3><?= e($ev['titolo']) ?></h3>
                                <p><?= e($ev['descrizione']) ?></p>
                                <dl class="evento-dettagli">
                                    <div><dt>Dove</dt><dd><?= e($ev['luogo']) ?></dd></div>
                                    <div><dt>Durata</dt><dd><?= (int) $ev['durata_min'] ?> minuti</dd></div>
                                    <div><dt>Costo</dt><dd><?= e(prezzo($ev['prezzo'])) ?></dd></div>
                                </dl>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </section>
        <?php endif; ?>

        <?php if ($prodotti): ?>
            <section aria-labelledby="titolo-shop-mostra">
                <h2 id="titolo-shop-mostra">Dallo shop</h2>
                <ul>
                    <?php foreach ($prodotti as $p): ?>
                        <li><?= e($p['nome']) ?>, <?= e(prezzo($p['prezzo'])) ?></li>
                    <?php endforeach; ?>
                </ul>
                <p><a href="shop.php?mostra=<?= e($mostra['slug']) ?>">Vedi i prodotti di questa mostra nello shop</a></p>
            </section>
        <?php endif; ?>

        <section class="invito" aria-labelledby="titolo-invito">
            <div>
                <h2 id="titolo-invito">Vieni a vederla</h2>
                <p>La mostra è inclusa nel biglietto d'ingresso al museo.</p>
            </div>
            <a class="bottone" href="visit.php#prenota">Prenota la visita</a>
        </section>
    </div>
</div>
<?php pagina_fine();
