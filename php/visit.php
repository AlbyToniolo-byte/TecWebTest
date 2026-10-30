<?php
require_once __DIR__ . '/funzioni.php';
require_once __DIR__ . '/header_footer.php';

const FASCE = ['mattina' => 'Mattina, 9:30–12:30', 'pomeriggio' => 'Pomeriggio, 12:30–16:00', 'tardi' => 'Dalle 16:00 alla chiusura'];
const MAX_PER_TIPO = 10;

$orari = query('SELECT giorno, apertura, chiusura FROM orari ORDER BY giorno');
$tipi = query('SELECT id, nome, descrizione, prezzo FROM tipi_biglietto ORDER BY ordine');
$chiusi = array_column(array_filter($orari, fn($o) => !$o['apertura']), 'giorno');

$errori = [];
$v = ['data' => '', 'fascia' => '', 'nome' => '', 'email' => '', 'q' => []];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valido()) {
        $errori['nome'] = 'La sessione è scaduta: ricarica la pagina e invia di nuovo il modulo';
    }
    $v['data'] = trim((string) ($_POST['data'] ?? ''));
    $v['fascia'] = (string) ($_POST['fascia'] ?? '');
    $v['nome'] = trim((string) ($_POST['nome'] ?? ''));
    $v['email'] = trim((string) ($_POST['email'] ?? ''));

    $d = DateTime::createFromFormat('!Y-m-d', $v['data']);
    if (!$d || $d->format('Y-m-d') !== $v['data']) {
        $errori['data'] = 'Scegli la data della visita';
    } elseif ($v['data'] < date('Y-m-d')) {
        $errori['data'] = 'Scegli una data a partire da oggi';
    } elseif (in_array((int) $d->format('N'), $chiusi, true)) {
        $errori['data'] = 'Il ' . GIORNI[(int) $d->format('N')] . ' il museo è chiuso: scegli un altro giorno';
    }
    if (!isset(FASCE[$v['fascia']])) {
        $errori['fascia'] = 'Scegli la fascia oraria di ingresso';
    }

    $totale = 0;
    $pezzi = 0;
    foreach ($tipi as $t) {
        $q = filter_var($_POST['q'][$t['id']] ?? 0, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => MAX_PER_TIPO]]);
        if ($q === false) {
            $errori['q' . $t['id']] = 'Biglietti ' . $t['nome'] . ': inserisci un numero tra 0 e ' . MAX_PER_TIPO;
            $q = 0;
        }
        $v['q'][$t['id']] = $q;
        $pezzi += $q;
        $totale += $q * (float) $t['prezzo'];
    }
    if ($pezzi === 0 && !array_filter(array_keys($errori), fn($k) => str_starts_with($k, 'q'))) {
        $errori['q' . $tipi[0]['id']] = 'Scegli almeno un biglietto';
    }
    if ($v['nome'] === '') {
        $errori['nome'] ??= 'Inserisci il tuo nome e cognome';
    }
    if (!email_valida($v['email'])) {
        $errori['email'] = 'Inserisci un indirizzo email valido, per esempio nome@esempio.it';
    }

    if (!$errori) {
        $pdo = db();
        $pdo->beginTransaction();
        $codice = codice_casuale();
        $st = $pdo->prepare('INSERT INTO prenotazioni (codice, nome, email, data_visita, fascia, totale)
                             VALUES (:c, :n, :e, :d, :f, :t) RETURNING id');
        $st->execute(['c' => $codice, 'n' => $v['nome'], 'e' => $v['email'], 'd' => $v['data'], 'f' => $v['fascia'], 't' => $totale]);
        $idPren = $st->fetchColumn();
        $riga = $pdo->prepare('INSERT INTO prenotazioni_righe VALUES (:p, :t, :q, :u)');
        foreach ($tipi as $t) {
            if ($v['q'][$t['id']] > 0) {
                $riga->execute(['p' => $idPren, 't' => $t['id'], 'q' => $v['q'][$t['id']], 'u' => $t['prezzo']]);
            }
        }
        $pdo->commit();

        flash(sprintf('Prenotazione confermata per %s, %s. Codice %s: mostralo in biglietteria e paga %s all\'ingresso.',
            data_estesa($v['data'], true), mb_strtolower(FASCE[$v['fascia']]), $codice, prezzo($totale)));
        vai('visit.php');
    }
}

pagina_inizio('Visita', 'Orari, biglietti, come arrivare e prenotazione della visita al museo Kronos.', 'visit');
?>
<div class="contenitore">
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
        <?= riepilogo_errori($errori) ?>
        <form action="visit.php#prenota" method="post" novalidate data-valida data-totale-biglietti>
            <?= campo_csrf() ?>
            <p class="suggerimento">I campi con <span aria-hidden="true">*</span><span class="visually-hidden">asterisco</span> sono obbligatori.</p>

            <div class="campo">
                <label for="data">Data della visita <span class="obbligatorio" aria-hidden="true">*</span></label>
                <p class="suggerimento" id="data-aiuto">Il museo è chiuso il lunedì.</p>
                <input type="date" id="data" name="data" min="<?= e(date('Y-m-d')) ?>" value="<?= e($v['data']) ?>" required aria-required="true"
                       data-msg-vuoto="Scegli la data della visita"<?= attr_campo('data', $errori, 'data-aiuto') ?>>
                <?= errore_campo('data', $errori) ?>
            </div>

            <div class="campo">
                <label for="fascia">Fascia oraria di ingresso <span class="obbligatorio" aria-hidden="true">*</span></label>
                <select id="fascia" name="fascia" required aria-required="true" data-msg-vuoto="Scegli la fascia oraria di ingresso"<?= attr_campo('fascia', $errori) ?>>
                    <option value="">Scegli una fascia</option>
                    <?php foreach (FASCE as $k => $etichetta): ?>
                        <option value="<?= e($k) ?>"<?= $k === $v['fascia'] ? ' selected' : '' ?>><?= e($etichetta) ?></option>
                    <?php endforeach; ?>
                </select>
                <?= errore_campo('fascia', $errori) ?>
            </div>

            <fieldset id="biglietti" data-almeno-uno="Scegli almeno un biglietto">
                <legend>Biglietti</legend>
                <div class="quantita-biglietti">
                    <?php foreach ($tipi as $t): $id = 'q' . $t['id']; ?>
                        <div class="riga">
                            <div>
                                <label for="<?= e($id) ?>"><?= e($t['nome']) ?>, <?= e(prezzo($t['prezzo'])) ?></label>
                                <p class="suggerimento" id="<?= e($id) ?>-aiuto"><?= e($t['descrizione']) ?></p>
                            </div>
                            <input type="number" id="<?= e($id) ?>" name="q[<?= (int) $t['id'] ?>]" min="0" max="<?= MAX_PER_TIPO ?>" step="1" inputmode="numeric"
                                   value="<?= (int) ($v['q'][$t['id']] ?? 0) ?>" data-prezzo="<?= e($t['prezzo']) ?>"<?= attr_campo($id, $errori, $id . '-aiuto') ?>>
                            <?= errore_campo($id, $errori) ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                <p class="errore" id="biglietti-errore" hidden></p>
                <p class="totale" data-totale aria-live="polite"></p>
            </fieldset>

            <div class="campo">
                <label for="nome">Nome e cognome <span class="obbligatorio" aria-hidden="true">*</span></label>
                <input type="text" id="nome" name="nome" autocomplete="name" value="<?= e($v['nome']) ?>" required aria-required="true"
                       data-msg-vuoto="Inserisci il tuo nome e cognome"<?= attr_campo('nome', $errori) ?>>
                <?= errore_campo('nome', $errori) ?>
            </div>

            <div class="campo">
                <label for="email">Email <span class="obbligatorio" aria-hidden="true">*</span></label>
                <p class="suggerimento" id="email-aiuto">La usiamo solo per eventuali cambi di orario.</p>
                <input type="email" id="email" name="email" autocomplete="email" value="<?= e($v['email']) ?>" required aria-required="true"
                       data-msg-vuoto="Inserisci il tuo indirizzo email"<?= attr_campo('email', $errori, 'email-aiuto') ?>>
                <?= errore_campo('email', $errori) ?>
            </div>

            <button class="bottone" type="submit">Conferma la prenotazione</button>
        </form>
    </section>
</div>
<?php pagina_fine();
