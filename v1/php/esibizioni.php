<?php
require_once __DIR__ . '/funzioni.php';
require_once __DIR__ . '/header_footer.php';

$mostre = query('SELECT slug, titolo, sottotitolo, sommario, collocazione, immagine, tema, speciale, data_fine
                 FROM mostre ORDER BY ordine');

// Filtro eventi per mostra (?mostra=<slug>), accetta solo valori esistenti
$filtro = (string) ($_GET['mostra'] ?? '');
$slugValidi = array_column($mostre, 'titolo', 'slug');
if (!isset($slugValidi[$filtro])) {
    $filtro = '';
}

$sql = 'SELECT e.titolo, e.descrizione, e.inizio, e.durata_min, e.luogo, e.prezzo, e.posti, m.titolo AS mostra, m.slug
        FROM eventi e LEFT JOIN mostre m ON m.id = e.mostra_id
        WHERE e.inizio >= NOW()' . ($filtro ? ' AND m.slug = :s' : '') . '
        ORDER BY e.inizio';
$eventi = query($sql, $filtro ? ['s' => $filtro] : []);

pagina_inizio('Mostre ed eventi', 'Le cinque mostre di Kronos e il calendario di visite guidate, laboratori e serate.', 'esibizioni');
?>
<div class="contenitore">
    <div class="intestazione-pagina">
        <h1>Mostre ed eventi</h1>
        <p class="introduzione">Cinque mostre permanenti, disposte su quattro piani nell'ordine della discesa: dal cielo, in terrazza, fino al fondale di Atlantide, sotto il livello della strada.</p>
    </div>

    <ul class="elenco-mostre">
        <?php foreach ($mostre as $m): ?>
            <li class="voce-mostra tema-<?= e($m['tema']) ?>">
                <figure class="arco" data-rivela><img src="<?= e(asset($m['immagine'])) ?>" alt="" width="1600" height="1000" loading="lazy"></figure>
                <div>
                    <?php if ($m['speciale']): ?>
                        <p class="etichetta-speciale">Installazione speciale fino al <?= e(data_estesa($m['data_fine'], true)) ?></p>
                    <?php endif; ?>
                    <h2><a href="mostra.php?m=<?= e($m['slug']) ?>"><?= e($m['titolo']) ?></a></h2>
                    <p class="mondo-sottotitolo"><?= e($m['sottotitolo']) ?></p>
                    <p><?= e($m['sommario']) ?></p>
                    <p class="mondo-dove"><?= e($m['collocazione']) ?></p>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>

    <section id="eventi" aria-labelledby="titolo-calendario">
        <h2 id="titolo-calendario">Calendario degli eventi</h2>
        <form class="filtro" action="esibizioni.php#eventi" method="get">
            <div class="campo">
                <label for="filtro-mostra">Mostra</label>
                <select id="filtro-mostra" name="mostra">
                    <option value="">Tutte le mostre</option>
                    <?php foreach ($slugValidi as $s => $titolo): ?>
                        <option value="<?= e($s) ?>"<?= $s === $filtro ? ' selected' : '' ?>><?= e($titolo) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button class="bottone" type="submit">Mostra gli eventi</button>
        </form>

        <p><?= count($eventi) === 1 ? '1 evento in programma' : count($eventi) . ' eventi in programma' ?><?= $filtro ? ' per ' . e($slugValidi[$filtro]) : '' ?>.</p>

        <?php if ($eventi): ?>
            <ul class="eventi">
                <?php foreach ($eventi as $ev): ?>
                    <li class="evento">
                        <p class="evento-quando"><time datetime="<?= e(date('c', strtotime($ev['inizio']))) ?>"><?= e(data_estesa($ev['inizio'])) ?><span>ore <?= e(ora($ev['inizio'])) ?></span></time></p>
                        <div>
                            <h3><?= e($ev['titolo']) ?></h3>
                            <p><?= e($ev['descrizione']) ?></p>
                            <dl class="evento-dettagli">
                                <div><dt>Mostra</dt><dd><?= $ev['slug'] ? '<a href="mostra.php?m=' . e($ev['slug']) . '">' . e($ev['mostra']) . '</a>' : 'Tutto il museo' ?></dd></div>
                                <div><dt>Dove</dt><dd><?= e($ev['luogo']) ?></dd></div>
                                <div><dt>Durata</dt><dd><?= (int) $ev['durata_min'] ?> minuti</dd></div>
                                <div><dt>Costo</dt><dd><?= e(prezzo($ev['prezzo'])) ?></dd></div>
                                <div><dt>Posti</dt><dd><?= (int) $ev['posti'] ?></dd></div>
                            </dl>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>Per questa mostra non ci sono eventi in programma. <a href="esibizioni.php#eventi">Vedi gli eventi di tutte le mostre</a>.</p>
        <?php endif; ?>
        <p>Gli eventi si prenotano in biglietteria o scrivendo a <a href="mailto:eventi@kronos-museo.it">eventi@kronos-museo.it</a>.</p>
    </section>
</div>
<?php pagina_fine();
