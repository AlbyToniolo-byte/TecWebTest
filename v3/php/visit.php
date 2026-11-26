<?php
require_once __DIR__ . '/funzioni.php';
require_once __DIR__ . '/header_footer.php';
require_once __DIR__ . '/prenotazione.php';

$orari = query('SELECT giorno, apertura, chiusura FROM orari ORDER BY giorno');
$tipi = tipi_biglietto();

$errori = [];
$v = ['data' => '', 'fascia' => '', 'nome' => '', 'email' => '', 'note' => '', 'q' => []];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    [$v, $errori] = prenotazione_leggi($_POST, $tipi);
    if (!$errori) {
        $codice = prenotazione_salva($v, $tipi);
        $totale = array_sum(array_map(fn($t) => $v['q'][$t['id']] * (float) $t['prezzo'], $tipi));
        flash(sprintf('Prenotazione confermata per %s, %s. Codice %s: mostralo in biglietteria e paga %s all\'ingresso. '
            . 'Con il codice e la tua email puoi modificarla o cancellarla da «Gestisci la prenotazione».',
            data_estesa($v['data'], true), mb_strtolower(FASCE[$v['fascia']]), $codice, prezzo($totale)));
        vai('visit.php');
    }
}

pagina_inizio('Visita', 'Orari, biglietti, come arrivare e prenotazione della visita al museo Kronos.', 'visit');
?>
<div class="contenitore">
    <?php briciole([[null, 'Visita']]); ?>
    <div class="intestazione-pagina">
        <h1>Visita il museo</h1>
        <p class="introduzione"><?= e(stato_apertura_oggi()) ?>. Per vedere tutte e cinque le mostre servono circa tre ore; il percorso si fa dall'alto verso il basso, partendo dalla terrazza.</p>
    </div>

    <div class="griglia-due">
        <table>
            <caption>Orari di apertura</caption>
            <thead><tr><th scope="col">Giorno</th><th scope="col">Orario</th></tr></thead>
            <tbody>
                <?php foreach ($orari as $o): ?>
                    <tr>
                        <th scope="row"><?= e(ucfirst(GIORNI[$o['giorno']])) ?></th>
                        <td><?= $o['apertura'] ? e(ora($o['apertura']) . '–' . ora($o['chiusura'])) : 'Chiuso' ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div>
            <table>
                <caption>Biglietti</caption>
                <thead><tr><th scope="col">Tipo</th><th scope="col">Per chi</th><th scope="col" class="numero">Prezzo</th></tr></thead>
                <tbody>
                    <?php foreach ($tipi as $t): ?>
                        <tr>
                            <th scope="row"><?= e($t['nome']) ?></th>
                            <td><?= e($t['descrizione']) ?></td>
                            <td class="numero"><?= e(prezzo($t['prezzo'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <p>Il biglietto include tutte le mostre. Soci e accompagnatori di persone con disabilità entrano gratis.</p>
        </div>
    </div>

    <div class="griglia-due">
        <section aria-labelledby="titolo-arrivare">
            <h2 id="titolo-arrivare">Come arrivare</h2>
            <address>Piazza Crono 1</address>
            <p>Il museo è a dieci minuti a piedi dalla stazione. Le biciclette si lasciano nella rastrelliera coperta davanti all'ingresso; il parcheggio più vicino è a 300 metri.</p>
        </section>
        <section aria-labelledby="titolo-accessibile">
            <h2 id="titolo-accessibile">Accessibilità</h2>
            <p>Tutti i piani sono raggiungibili con l'ascensore. In ogni mostra c'è almeno un oggetto da toccare, con didascalia in braille. Il primo sabato del mese c'è una visita guidata in LIS.</p>
            <p>In biglietteria puoi chiedere gratuitamente sedie pieghevoli, cuffie antirumore e lenti d'ingrandimento.</p>
        </section>
    </div>

    <section id="prenota" class="blocco-form" aria-labelledby="titolo-prenota">
        <h2 id="titolo-prenota">Prenota la visita</h2>
        <p>Scegli giorno, fascia oraria e biglietti. Paghi in biglietteria il giorno della visita.</p>
        <p>Hai già prenotato? <a href="gestisci.php">Modifica o cancella la tua prenotazione</a>.</p>
        <?= riepilogo_errori($errori) ?>
        <form action="visit.php#prenota" method="post" novalidate="novalidate" data-valida="" data-totale-biglietti="">
            <?= campo_csrf() ?>
            <?php prenotazione_campi($v, $errori, $tipi); ?>

            <button class="bottone" type="submit">Conferma la prenotazione</button>
        </form>
    </section>
</div>
<?php pagina_fine();
