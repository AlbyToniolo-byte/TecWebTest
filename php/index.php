<?php
require_once __DIR__ . '/funzioni.php';
require_once __DIR__ . '/header_footer.php';

$mostre = query('SELECT slug, titolo, sottotitolo, sommario, collocazione, immagine, tema, speciale, nota_speciale
                 FROM mostre ORDER BY ordine');
$eventi = query('SELECT e.titolo, e.descrizione, e.inizio, e.luogo, e.prezzo, m.titolo AS mostra, m.slug
                 FROM eventi e LEFT JOIN mostre m ON m.id = e.mostra_id
                 WHERE e.inizio >= NOW() ORDER BY e.inizio LIMIT 3');
$prezzoMin = query_riga('SELECT MIN(prezzo) AS p FROM tipi_biglietto WHERE prezzo > 0')['p'];
$oggi = stato_apertura_oggi();

pagina_inizio('Home', 'Kronos è il museo dei mondi perduti: dinosauri, mitologia greca, era glaciale, Atlantide e mondo celeste in un unico percorso.', 'home', 'celeste', true);
?>
<section class="ingresso" aria-labelledby="titolo-home">
    <div class="ingresso-immagine"><img src="<?= e(asset('img/mostre/mondo-celeste.svg')) ?>" alt="" width="1600" height="1000"></div>
    <div class="contenitore">
        <h1 id="titolo-home"><span class="visually-hidden">Kronos</span><span class="titolo-lettere" aria-hidden="true"><span>K</span><span>r</span><span>o</span><span>n</span><span>o</span><span>s</span></span></h1>
        <p class="introduzione">Il museo dei mondi perduti. Cinque mondi scomparsi, o mai esistiti, da percorrere in discesa: si parte dalle stelle e si arriva in fondo al mare.</p>
        <div class="azioni">
            <a class="bottone" href="visit.php#prenota">Prenota la visita</a>
            <a class="bottone bottone-secondario" href="esibizioni.php">Vedi mostre ed eventi</a>
        </div>
        <nav aria-label="Il percorso, dal cielo all'abisso">
            <ol class="percorso">
                <?php foreach ($mostre as $m): ?>
                    <li><a href="#mondo-<?= e($m['slug']) ?>"><?= e($m['titolo']) ?></a></li>
                <?php endforeach; ?>
            </ol>
        </nav>
        <p class="oggi"><?= e($oggi) ?></p>
    </div>
</section>

<?php
$precedente = COLORE_TEMA['celeste'];
foreach ($mostre as $m): ?>
<section id="mondo-<?= e($m['slug']) ?>" class="mondo tema-<?= e($m['tema']) ?>" style="--da: <?= e($precedente) ?>" aria-labelledby="titolo-<?= e($m['slug']) ?>">
    <div class="contenitore mondo-interno">
        <div data-rivela>
            <h2 id="titolo-<?= e($m['slug']) ?>"><?= e($m['titolo']) ?></h2>
            <p class="mondo-sottotitolo"><?= e($m['sottotitolo']) ?></p>
            <p><?= e($m['sommario']) ?></p>
            <?php if ($m['speciale']): ?>
                <div class="nota-speciale">
                    <h3>Installazione speciale</h3>
                    <p><?= e($m['nota_speciale']) ?></p>
                </div>
            <?php endif; ?>
            <p class="mondo-dove"><?= e($m['collocazione']) ?></p>
            <a class="bottone" href="mostra.php?m=<?= e($m['slug']) ?>">Entra nella mostra<span class="visually-hidden"> <?= e($m['titolo']) ?></span></a>
        </div>
        <figure class="arco" data-rivela>
            <img src="<?= e(asset($m['immagine'])) ?>" alt="" width="1600" height="1000" loading="lazy">
        </figure>
    </div>
</section>
<?php
    $precedente = COLORE_TEMA[$m['tema']];
endforeach; ?>

<section class="dopo-discesa tema-atlantide" aria-labelledby="titolo-eventi">
    <div class="contenitore">
        <div class="griglia-due">
            <div>
                <h2 id="titolo-eventi">Prossimi eventi</h2>
                <?php if ($eventi): ?>
                    <ul class="eventi">
                        <?php foreach ($eventi as $ev): ?>
                            <li class="evento">
                                <p class="evento-quando"><time datetime="<?= e(date('c', strtotime($ev['inizio']))) ?>"><?= e(data_estesa($ev['inizio'])) ?><span>ore <?= e(ora($ev['inizio'])) ?></span></time></p>
                                <div>
                                    <h3><?= e($ev['titolo']) ?></h3>
                                    <p><?= e($ev['descrizione']) ?></p>
                                    <p class="evento-dettagli"><?= e($ev['luogo']) ?>, <?= e(prezzo($ev['prezzo'])) ?></p>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p>Non ci sono eventi in programma. Iscriviti alla newsletter per sapere quando ne annunciamo di nuovi.</p>
                <?php endif; ?>
                <p><a href="esibizioni.php#eventi">Tutti gli eventi</a></p>
            </div>
            <div>
                <h2>Pianifica la visita</h2>
                <p><?= e($oggi) ?>. Il lunedì il museo è chiuso, il venerdì resta aperto fino alle 22.</p>
                <p>Biglietti da <?= e(prezzo($prezzoMin)) ?>, ingresso gratuito fino a 5 anni. Prenotando online scegli la fascia oraria ed eviti la coda in biglietteria.</p>
                <a class="bottone" href="visit.php">Orari, biglietti e come arrivare</a>
            </div>
        </div>
    </div>
</section>
<?php pagina_fine();
